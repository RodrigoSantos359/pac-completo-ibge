<?php
/**
 * BrasilAPI.php - Cliente para consulta de CNPJ via BrasilAPI
 * 
 * API gratuita para obter dados cadastrais de empresas brasileiras.
 * Documentação: https://brasilapi.com.br/docs#tag/CNPJ
 */

class BrasilAPI {
    private string $baseUrl = 'https://brasilapi.com.br/api';
    private int $timeout;
    private int $maxRetries;

    public function __construct(int $timeout = 15, int $maxRetries = 3) {
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;
    }

    /**
     * Consulta dados cadastrais de um CNPJ com retry automático
     * 
     * @param string $cnpj CNPJ com 14 dígitos (apenas números)
     * @return array Dados brutos da API
     * @throws Exception Se a API estiver indisponível ou CNPJ inválido
     */
    public function consultarCNPJ(string $cnpj): array {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            throw new InvalidArgumentException("CNPJ deve ter 14 dígitos (recebido: {$cnpj})");
        }

        $url = "{$this->baseUrl}/cnpj/v1/{$cnpj}";
        $ultimoErro = null;

        for ($tentativa = 1; $tentativa <= $this->maxRetries; $tentativa++) {
            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $this->timeout,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_USERAGENT => 'PACGenerator/1.0',
                    CURLOPT_HTTPHEADER => ['Accept: application/json'],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                ]);

                $resposta = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $erro = curl_error($ch);
                curl_close($ch);

                if ($erro) {
                    throw new RuntimeException("Erro de conexão: {$erro}");
                }

                if ($httpCode === 200) {
                    $dados = json_decode($resposta, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException("Erro ao decodificar JSON: " . json_last_error_msg());
                    }
                    return $dados;
                }

                if ($httpCode === 429) {
                    $espera = $tentativa * 3;
                    if ($tentativa < $this->maxRetries) {
                        sleep($espera);
                        $ultimoErro = new RuntimeException("Rate limit (429). Tentativa {$tentativa}/{$this->maxRetries}");
                        continue;
                    }
                    throw new RuntimeException("Rate limit excedido após {$this->maxRetries} tentativas");
                }

                if ($httpCode === 400) {
                    throw new InvalidArgumentException("CNPJ inválido: {$cnpj}");
                }
                if ($httpCode === 404) {
                    throw new InvalidArgumentException("CNPJ não encontrado: {$cnpj}");
                }

                throw new RuntimeException("Erro HTTP {$httpCode}: " . substr($resposta, 0, 200));

            } catch (RuntimeException $e) {
                $ultimoErro = $e;
                if ($tentativa < $this->maxRetries) {
                    sleep(3);
                    continue;
                }
                throw $e;
            }
        }

        throw $ultimoErro ?? new RuntimeException("Não foi possível consultar o CNPJ");
    }

    /**
     * Extrai dados no formato PAC a partir da resposta da BrasilAPI
     * Preenche todos os campos possíveis do layout de 306 posições
     * 
     * @param string $cnpj
     * @return array Dicionário com chaves do PAC
     */
    public function extrairDadosPAC(string $cnpj): array {
        $dadosBrutos = $this->consultarCNPJ($cnpj);
        $empresa = [];

        // CNPJ (posições 1-3)
        $cnpjCompleto = $dadosBrutos['cnpj'] ?? '';
        $empresa['cnpj_raiz'] = strlen($cnpjCompleto) >= 14 ? substr($cnpjCompleto, 0, 8) : '';
        $empresa['cnpj_sufixo'] = strlen($cnpjCompleto) >= 14 ? substr($cnpjCompleto, 8, 4) : '';
        $empresa['cnpj_dv'] = strlen($cnpjCompleto) >= 14 ? substr($cnpjCompleto, 12, 2) : '';

        // Nome e fantasia (posições 4-5)
        $empresa['razao_social'] = $dadosBrutos['razao_social'] ?? '';
        $empresa['nome_fantasia'] = $dadosBrutos['nome_fantasia'] ?? '';

        // Site (posição 6)
        $empresa['site_empresa'] = '';

        // Endereço SEDE (posições 7-20)
        $empresa['uf_sede'] = $dadosBrutos['uf'] ?? '';
        $empresa['municipio_sede'] = $dadosBrutos['municipio'] ?? '';

        $logradouro = $dadosBrutos['logradouro'] ?? '';
        $empresa['logradouro_sede_tipo'] = self::extrairTipoLogradouro($logradouro);
        $empresa['logradouro_sede_nome'] = self::extrairNomeLogradouro($logradouro);
        $empresa['logradouro_sede_numero'] = $dadosBrutos['numero'] ?? 'S/N';
        $empresa['logradouro_sede_comp'] = $dadosBrutos['complemento'] ?? '';
        $empresa['bairro_sede'] = $dadosBrutos['bairro'] ?? '';
        $empresa['cep_sede'] = str_replace('-', '', $dadosBrutos['cep'] ?? '');
        $empresa['distrito_sede'] = '';

        // Telefone (posições 16-18)
        list($ddd, $telefone) = self::tratarDDDTelefone($dadosBrutos['ddd_telefone_1'] ?? '');
        $empresa['ddd_sede'] = $ddd;
        $telefoneSeparado = $dadosBrutos['telefone_1'] ?? '';
        $empresa['telefone_sede'] = $telefoneSeparado ?: $telefone;
        $empresa['ramal_sede'] = '';

        // E-mail (posição 19)
        $empresa['email_empresa'] = $dadosBrutos['email'] ?? '';

        // CNAE e atividade (posições 20-22)
        $cnae = $dadosBrutos['cnae_fiscal'] ?? '';
        $empresa['cnae_principal'] = substr(preg_replace('/[^0-9]/', '', (string)$cnae), 0, 4);
        $empresa['cnae_dv'] = '';
        $empresa['descricao_atividade'] = $dadosBrutos['cnae_fiscal_descricao'] ?? '';

        // Situação cadastral (posições 43-44)
        $empresa['situacao_cadastral'] = self::mapearSituacao($dadosBrutos['descricao_situacao_cadastral'] ?? '');
        $empresa['mudanca_estrutural'] = '00';

        // Unidade de Coleta (posições 23-36) - preenchido com dados da SEDE por padrão
        $empresa['uc_sufixo'] = $empresa['cnpj_sufixo'] ?? '';
        $empresa['uc_dv'] = $empresa['cnpj_dv'] ?? '';
        $empresa['uc_uf'] = $empresa['uf_sede'] ?? '';
        $empresa['uc_municipio'] = $empresa['municipio_sede'] ?? '';
        $empresa['uc_distrito'] = '';
        $empresa['uc_logradouro_tipo'] = $empresa['logradouro_sede_tipo'] ?? '';
        $empresa['uc_logradouro_nome'] = $empresa['logradouro_sede_nome'] ?? '';
        $empresa['uc_logradouro_numero'] = $empresa['logradouro_sede_numero'] ?? '';
        $empresa['uc_complemento'] = '';
        $empresa['uc_bairro'] = $empresa['bairro_sede'] ?? '';
        $empresa['uc_cep'] = $empresa['cep_sede'] ?? '';
        $empresa['uc_ddd'] = $empresa['ddd_sede'] ?? '';
        $empresa['uc_telefone'] = $empresa['telefone_sede'] ?? '';
        $empresa['uc_ramal'] = '';

        // Responsável (posições 37-42) - valores fixos do contador
        $empresa['responsavel_nome'] = 'FILIPE REINALDO SANTOS';
        $empresa['responsavel_cargo'] = 'contador';
        $empresa['responsavel_ddd'] = '87';
        $empresa['responsavel_telefone'] = '996507334';
        $empresa['responsavel_ramal'] = '';
        $empresa['responsavel_email'] = 'filipe@acenecontabilidade.com.br';

        // Data ocorrência (posições 45-46)
        $empresa['data_ocorrencia_mes'] = '';
        $empresa['data_ocorrencia_ano'] = '';

        // Ligações (posições 47-55)
        $empresa['ligacao1_cnpj_raiz'] = '';
        $empresa['ligacao1_cnpj_sufixo'] = '';
        $empresa['ligacao1_cnpj_dv'] = '';
        $empresa['ligacao2_cnpj_raiz'] = '';
        $empresa['ligacao2_cnpj_sufixo'] = '';
        $empresa['ligacao2_cnpj_dv'] = '';
        $empresa['ligacao3_cnpj_raiz'] = '';
        $empresa['ligacao3_cnpj_sufixo'] = '';
        $empresa['ligacao3_cnpj_dv'] = '';

        // Mercadorias (posições 56-58)
        $empresa['mercadoria_item_1'] = '';
        $empresa['mercadoria_item_2'] = '';
        $empresa['mercadoria_item_3'] = '';
        $empresa['mercadorias'] = []; // compatibilidade

        // Tributação (posição 59) - captura do campo regime_tributario da BrasilAPI
        $empresa['forma_tributacao'] = self::extrairFormaTributacao($dadosBrutos);

        // Endereço diferente (posições 60-62) - padrão: empresa nova na pesquisa
        $empresa['endereco_uc_diferente'] = '3';
        $empresa['endereco_sede_diferente'] = '3';
        $empresa['razao_social_diferente'] = '2';

        // Alterações (posições 63-65)
        $empresa['razao_social_anterior'] = '';
        $empresa['motivo_alteracao'] = '';
        $empresa['atividade_anterior'] = '';

        // Pessoal (posições 66-81) - valores zerados
        $empresa['pessoal_assalariado_comercial_marco'] = '';
        $empresa['pessoal_assalariado_nao_comercial_marco'] = '';
        $empresa['pessoal_nao_assalariado_socios_marco'] = '';
        $empresa['pessoal_nao_assalariado_familia_marco'] = '';
        $empresa['pessoal_assalariado_comercial_junho'] = '';
        $empresa['pessoal_assalariado_nao_comercial_junho'] = '';
        $empresa['pessoal_nao_assalariado_socios_junho'] = '';
        $empresa['pessoal_nao_assalariado_familia_junho'] = '';
        $empresa['pessoal_assalariado_comercial_setembro'] = '';
        $empresa['pessoal_assalariado_nao_comercial_setembro'] = '';
        $empresa['pessoal_nao_assalariado_socios_setembro'] = '';
        $empresa['pessoal_nao_assalariado_familia_setembro'] = '';
        $empresa['pessoal_assalariado_comercial_dezembro'] = '';
        $empresa['pessoal_assalariado_nao_comercial_dezembro'] = '';
        $empresa['pessoal_nao_assalariado_socios_dezembro'] = '';
        $empresa['pessoal_nao_assalariado_familia_dezembro'] = '';

        // Regionalização
        $empresa['regional_pessoal'] = [];
        $empresa['regional_salarios'] = [];
        $empresa['regional_unidades'] = [];
        $empresa['regional_receita'] = [];

        return $empresa;
    }

    // =========================================================================
    // UTILITÁRIOS
    // =========================================================================

    private static array $tiposLogradouro = [
        'RUA', 'AVENIDA', 'RODOVIA', 'PRACA', 'TRAVESSA', 'ESTRADA',
        'ALAMEDA', 'BECO', 'LARGO', 'VIA', 'PASSAGEM', 'SITIO',
        'FAZENDA', 'CONDOMINIO', 'QUADRA', 'CONJUNTO',
        'SETOR', 'SAUN', 'SAS', 'SBS', 'SQS', 'SRS', 'SCS', 'SHS',
        'SIA', 'SIG', 'SMPW', 'SGAN', 'CLN', 'CLS',
        'AC', 'AV', 'PCA', 'TV', 'EST',
    ];

    private static function extrairTipoLogradouro(?string $logradouro): string {
        if (empty($logradouro)) return '';
        $upper = strtoupper(trim($logradouro));
        foreach (self::$tiposLogradouro as $tipo) {
            if (str_starts_with($upper, $tipo)) {
                return ucfirst(strtolower($tipo));
            }
        }
        return 'Rua';
    }

    private static function extrairNomeLogradouro(?string $logradouro): string {
        if (empty($logradouro)) return '';
        $upper = strtoupper(trim($logradouro));
        foreach (self::$tiposLogradouro as $tipo) {
            if (str_starts_with($upper, $tipo)) {
                return trim(substr($logradouro, strlen($tipo)));
            }
        }
        return $logradouro;
    }

    private static function mapearSituacao(?string $descricao): string {
        if (empty($descricao)) return '01';
        $desc = strtolower(trim($descricao));

        $mapa = [
            'ativa' => '01', 'suspensa' => '02', 'inapta' => '02',
            'recuperacao judicial' => '03', 'recuperacao extrajudicial' => '04',
            'falida' => '05', 'falencia' => '05',
            'baixada' => '06', 'extinta' => '06', 'encerrada' => '06', 'nula' => '06',
            'sem movimento' => '07', 'implantacao' => '08',
            'nao iniciou' => '09', 'condominio' => '10',
        ];

        foreach ($mapa as $chave => $codigo) {
            if (str_contains($desc, $chave)) return $codigo;
        }
        return '01';
    }

    /**
     * Extrai a forma de tributação do campo regime_tributario da BrasilAPI
     * O campo retorna um array histórico com {ano, forma_de_tributacao}.
     * Pega o registro mais recente e mapeia para o código numérico do PAC.
     * 
     * @param array $dadosBrutos Resposta completa da BrasilAPI
     * @return string Código: 1-Simples, 2-Lucro Presumido, 3-Lucro Real, 4-MEI, 5-Imune/Isenta
     */
    private static function extrairFormaTributacao(array $dadosBrutos): string {
        // 1. Tenta pelo regime_tributario (mais confiável)
        $regimeTrib = $dadosBrutos['regime_tributario'] ?? [];
        if (!empty($regimeTrib) && is_array($regimeTrib)) {
            // Pega o registro mais recente
            $ultimo = null;
            foreach ($regimeTrib as $r) {
                if (isset($r['forma_de_tributacao'])) {
                    $ultimo = $r['forma_de_tributacao'];
                }
            }
            if ($ultimo !== null) {
                $mapa = [
                    'SIMPLES NACIONAL' => '3',
                    'LUCRO PRESUMIDO' => '2',
                    'LUCRO REAL' => '1',
                    'MEI' => '4',
                    'IMUNE' => '5',
                    'ISENTA' => '5',
                    'IMUNE/ISENTA' => '5',
                ];
                $upper = trim(strtoupper($ultimo));
                foreach ($mapa as $chave => $codigo) {
                    if (str_contains($upper, $chave)) {
                        return $codigo;
                    }
                }
            }
        }

        // 2. Fallback: opcao_pelo_mei
        if (!empty($dadosBrutos['opcao_pelo_mei'])) {
            return '4'; // MEI
        }

        // 3. Fallback: opcao_pelo_simples
        if (!empty($dadosBrutos['opcao_pelo_simples'])) {
            return '1'; // Simples Nacional
        }

        // 4. Se não achou nada, retorna vazio para preenchimento manual
        return '';
    }

    /**
     * Trata o campo ddd_telefone_1 que retorna DDD + telefone concatenados
     * @return array [ddd, telefone]
     */
    private static function tratarDDDTelefone(?string $valor): array {
        if (empty($valor)) return ['', ''];
        $texto = trim($valor);
        if (strlen($texto) >= 10 && ctype_digit($texto)) {
            return [substr($texto, 0, 2), substr($texto, 2)];
        }
        $ddd = ctype_digit(substr($texto, 0, 2)) ? substr($texto, 0, 2) : '';
        $fone = ctype_digit(substr($texto, 2)) ? substr($texto, 2) : $texto;
        return [$ddd, $fone];
    }
}
