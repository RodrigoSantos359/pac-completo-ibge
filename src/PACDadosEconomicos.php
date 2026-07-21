<?php
/**
 * PACDadosEconomicos.php - Parser de arquivos com dados econômicos (CSV/Excel)
 * 
 * Faz upload de arquivo CSV/Excel preenchido pelo usuário com códigos econômicos
 * (21, 21A, 22, 61, 70, etc.) e converte automaticamente para as chaves internas
 * do sistema, eliminando a transcrição manual dos dados.
 * 
 * Formato esperado do CSV:
 *   Coluna A: Código (21, 21A, ESTOQUE, CMV)
 *   Coluna B: Descrição do campo
 *   Coluna C: Valor
 *   Separador: ponto-e-vírgula (;)
 */

class PACDadosEconomicos {
    
    /**
     * Mapeamento: código do arquivo do usuário → chave interna do sistema
     */
    const CODIGO_ECONOMICO_MAP = [
        // ========== RECEITA LÍQUIDA DE REVENDA (códigos 038-039) ==========
        '038'   => 'receita_varejo',
        '039'   => 'receita_atacado',

        // ========== DESTINO DAS VENDAS (códigos 041-044) ==========
        '041'   => 'destino_exportacao',
        '042'   => 'destino_outras_empresas',
        '043'   => 'destino_orgaos_publicos',
        '044'   => 'destino_publico_geral',

        // ========== RECEITAS (códigos 21-29) ==========
        '21'    => 'receita_revenda',
        '21A'   => 'comissoes_vendas',
        '22'    => 'receita_venda_produtos',
        '23'    => 'receita_servicos_manutencao',
        '24'    => 'receita_royalties',
        '25'    => 'receita_outras_atividades',
        '26'    => 'deducoes_vendas_canceladas',
        '27'    => 'deducoes_icms',
        '28'    => 'deducoes_pis_pasep',
        '29'    => 'deducoes_outros_impostos',

        // ========== DEMAIS RECEITAS (códigos 31-36) ==========
        '31'    => 'receita_aluguel',
        '32'    => 'receitas_financeiras',
        '33'    => 'variacoes_monetarias_ativas',
        '34'    => 'resultado_positivo_participacoes',
        '35'    => 'demais_receitas_operacionais',
        '36'    => 'outras_receitas',

        // ========== COMPRAS / CUSTOS (códigos 046-048) ==========
        'ESTOQUE' => 'compras_mercadorias',
        'CMV'    => 'custo_mercadorias_revendidas',
        '046'   => 'compras_mercadorias',
        '047'   => 'compras_materias_primas',
        '048'   => 'compras_material_embalagem',

        // ========== GASTOS COM PESSOAL (códigos 61-68) ==========
        '61'    => 'gastos_salarios',
        '62'    => 'gastos_participacao_lucros',
        '63'    => 'gastos_prolabore',
        '64'    => 'gastos_previdencia_social',
        '65'    => 'gastos_fgts',
        '66'    => 'gastos_previdencia_privada',
        '67'    => 'gastos_indenizacoes',
        '68'    => 'gastos_beneficios',
        '068A'  => 'gastos_pis_folha',

        // ========== DESPESAS OPERACIONAIS (códigos 70-90) ==========
        '70'    => 'despesa_alugueis_imoveis',
        '70A'   => 'despesa_alugueis_maquinas',
        '71'    => 'despesa_condominios',
        '72'    => 'despesa_comissoes',
        '73'    => 'despesa_publicidade',
        '74'    => 'despesa_combustiveis',
        '75'    => 'despesa_servicos_autonomos',
        '76'    => 'despesa_servicos_tecnicos',
        '77'    => 'despesa_vigilancia',
        '78'    => 'despesa_fretes',
        '79'    => 'despesa_mao_obra_temporaria',
        '80'    => 'despesa_manutencao',
        '81'    => 'despesa_outros_servicos',
        '82'    => 'despesa_comunicacao',
        '83'    => 'despesa_energia',
        '84'    => 'despesa_armazenagem',
        '85'    => 'despesa_seguros',
        '86'    => 'despesa_impostos_taxas',
        '87'    => 'despesa_viagens',
        '88'    => 'despesa_material_expediente',
        '89'    => 'despesa_royalties',
        '89B'   => 'despesa_pis_cofins',
        '90'    => 'despesa_demais_operacionais',

        // ========== ESTOQUES (códigos 050-058) ==========
        '050'   => 'estoque_mercadorias_anterior',
        '051'   => 'estoque_produtos_anterior',
        '052'   => 'estoque_materias_primas_anterior',
        '053'   => 'estoque_material_embalagem_anterior',
        '055'   => 'estoque_mercadorias_atual',
        '056'   => 'estoque_produtos_atual',
        '057'   => 'estoque_materias_primas_atual',
        '058'   => 'estoque_material_embalagem_atual',

        // ========== DESPESAS FINANCEIRAS (códigos 92-96) ==========
        '92'    => 'despesa_leasing',
        '93'    => 'despesa_financeiras',
        '94'    => 'variacoes_monetarias_passivas',
        '95'    => 'despesa_comissoes_cartao',
        '96'    => 'resultado_negativo_participacoes',
        '092'   => 'despesa_leasing',
        '093'   => 'despesa_financeiras',

        // ========== DEPRECIAÇÃO / PROVISÕES (códigos 98-101, 098A) ==========
        '98'    => 'despesa_depreciacao',
        '098A'  => 'depreciacao_arrendamento_operacional',
        '99'    => 'despesa_outras',
        '100'   => 'provisao_irpj_csll',
        '101'   => 'outras_provisoes',

        // ========== AQUISIÇÕES DE TERCEIROS (códigos 105-108) ==========
        '105'   => 'aquisicoes_terrenos',
        '105A'  => 'aquisicoes_edificacoes',
        '106'   => 'aquisicoes_maquinas',
        '106A'  => 'aquisicoes_microcomputadores',
        '107'   => 'aquisicoes_transportes',
        '108'   => 'aquisicoes_outros',

        // ========== BAIXAS DO ATIVO IMOBILIZADO (códigos 110-113) ==========
        '110'   => 'baixas_terrenos',
        '110A'  => 'baixas_edificacoes',
        '111'   => 'baixas_maquinas',
        '111A'  => 'baixas_microcomputadores',
        '112'   => 'baixas_transportes',
        '113'   => 'baixas_outros',

        // ========== PRODUÇÃO PRÓPRIA E MELHORIAS (códigos 248-258) ==========
        '248'   => 'prodpropria_edificacoes',
        '249'   => 'prodpropria_maquinas',
        '250'   => 'prodpropria_microcomputadores',
        '251'   => 'prodpropria_outros',
        '253'   => 'melhorias_terrenos',
        '254'   => 'melhorias_edificacoes',
        '255'   => 'melhorias_maquinas',
        '256'   => 'melhorias_microcomputadores',
        '257'   => 'melhorias_transportes',
        '258'   => 'melhorias_outros',

        // ========== ATIVO IMOBILIZADO TOTAL (códigos 260-261) ==========
        '260'   => 'imobilizado_total_anterior',
        '261'   => 'imobilizado_total_atual',

        // ========== RESULTADO DO EXERCÍCIO (códigos 103-104) ==========
        '103'   => 'resultado_lucro',
        '104'   => 'resultado_prejuizo',
    ];

    /**
     * Códigos válidos (para validação rápida)
     */
    const CODIGOS_VALIDOS = [
        '21','21A','22','23','24','25','26','27','28','29',
        '31','32','33','34','35','36',
        'ESTOQUE','CMV',
        '046','047','048',
        '050','051','052','053',
        '055','056','057','058',
        '61','62','63','64','65','66','67','68','068A',
        '70','70A','71','72','73','74','75','76','77','78','79',
        '80','81','82','83','84','85','86','87','88','89','89B','90',
        '092','093','92','93','94','95','96',
        '038','039',
        '041','042','043','044',
        '98','098A','99','100','101',
        '105','105A','106','106A','107','108',
        '110','110A','111','111A','112','113',
        '248','249','250','251',
        '253','254','255','256','257','258',
        '260','261',
        '103','104',
    ];

    /**
     * Descrições amigáveis de cada código (para exibição na UI)
     */
    const CODIGO_DESCRICOES = [
        '21'    => 'Revenda de Mercadorias',
        '21A'   => 'Comissões sobre Vendas',
        '22'    => 'Venda de Produtos Fabricados',
        '23'    => 'Serviço de Manutenção',
        '24'    => 'Royalties de Franquias',
        '25'    => 'Outras Atividades',
        '26'    => '(-) Vendas Canceladas',
        '27'    => '(-) ICMS',
        '28'    => '(-) PIS/PASEP',
        '29'    => '(-) Demais Impostos s/ Vendas',
        '31'    => 'Receita de Aluguéis',
        '32'    => 'Receita Financeira',
        '33'    => 'Variações Monetárias Ativas',
        '34'    => 'Resultado Positivo - Participações',
        '35'    => 'Demais Receitas Operacionais',
        '36'    => 'Outras Receitas (Venda Imobilizado)',
        '038'   => 'Vendas a Varejo (Público em Geral)',
        '039'   => 'Vendas a Atacado (Empresas/Órgãos Públicos)',
        '041'   => 'Exportação Direta (%)',
        '042'   => 'Vendas a Outras Empresas (%)',
        '043'   => 'Vendas a Órgãos/Instituições Públicas (%)',
        '044'   => 'Vendas ao Público em Geral (%)',
        'ESTOQUE' => 'Mercadorias p/ Revenda (Compras)',
        '046'   => 'Mercadorias p/ Revenda (Compras)',
        '047'   => 'Matérias-Primas (Compras)',
        '048'   => 'Material de Embalagem (Compras)',
        'CMV'   => 'Custo das Mercadorias Vendidas',
        '61'    => 'Salários e Remunerações',
        '62'    => 'Participação nos Lucros',
        '63'    => 'Pró-labore',
        '64'    => 'INSS',
        '65'    => 'FGTS',
        '66'    => 'Previdência Privada',
        '67'    => 'Rescisões Trabalhistas',
        '68'    => 'Demais Benefícios',
        '068A'  => 'PIS sobre Folha de Pagamento',
        '050'   => 'Estoque Inicial - Mercadorias',
        '051'   => 'Estoque Inicial - Produtos',
        '052'   => 'Estoque Inicial - Matérias-Primas',
        '053'   => 'Estoque Inicial - Mat. Embalagem',
        '055'   => 'Estoque Final - Mercadorias',
        '056'   => 'Estoque Final - Produtos',
        '057'   => 'Estoque Final - Matérias-Primas',
        '058'   => 'Estoque Final - Mat. Embalagem',
        '70'    => 'Aluguéis de Imóveis',
        '70A'   => 'Aluguéis de Veículos/Máquinas',
        '71'    => 'Condomínios',
        '72'    => 'Comissões a Representantes',
        '73'    => 'Publicidade e Propaganda',
        '74'    => 'Combustíveis e Manutenção Veículos',
        '75'    => 'Serviços Prestados por PF',
        '76'    => 'Serviços Técnico-Profissionais (PJ)',
        '77'    => 'Vigilância e Segurança',
        '78'    => 'Fretes (sobre vendas)',
        '79'    => 'Mão-de-obra Temporária',
        '80'    => 'Manutenções Diversas',
        '81'    => 'Serviços Diversos PJ',
        '82'    => 'Comunicação (Tel./Internet)',
        '83'    => 'Energia, Gás, Água e Esgoto',
        '84'    => 'Armazenagem',
        '85'    => 'Seguros',
        '86'    => 'Despesas Tributárias',
        '87'    => 'Estadias e Viagens',
        '88'    => 'Material de Consumo/Expediente',
        '89'    => 'Royalties',
        '89B'   => 'PIS/COFINS s/ outras Receitas',
        '90'    => 'Outras Despesas Gerais',
        '092'   => 'Arrendamento Mercantil',
        '093'   => 'Despesas Financeiras',
        '92'    => 'Arrendamento Mercantil',
        '93'    => 'Despesas Financeiras',
        '94'    => 'Variação Monetária Passiva',
        '95'    => 'Taxa Cartão de Crédito',
        '96'    => 'Resultado Negativo - Participações',
        '98'    => 'Depreciação e Amortização',
        '098A'  => 'Depreciação de Arrendamento Operacional',
        '99'    => 'Perdas (Merc./Imob./Crédito)',
        '100'   => 'Provisão IRPJ e CSLL',
        '101'   => 'Outras Provisões (PDD, Estoque)',
        '105'   => 'Aquisições - Terrenos',
        '105A'  => 'Aquisições - Edificações',
        '106'   => 'Aquisições - Máquinas/Equipamentos/Instalações',
        '106A'  => 'Aquisições - Microcomputadores/Telecomunicações',
        '107'   => 'Aquisições - Meios de Transporte',
        '108'   => 'Aquisições - Outros (Móveis/Utensílios)',
        '110'   => 'Baixas - Terrenos',
        '110A'  => 'Baixas - Edificações',
        '111'   => 'Baixas - Máquinas/Equipamentos/Instalações',
        '111A'  => 'Baixas - Microcomputadores/Telecomunicações',
        '112'   => 'Baixas - Meios de Transporte',
        '113'   => 'Baixas - Outros (Móveis/Utensílios)',
        '248'   => 'Produção Própria - Edificações',
        '249'   => 'Produção Própria - Máquinas/Equipamentos',
        '250'   => 'Produção Própria - Microcomputadores',
        '251'   => 'Produção Própria - Outros',
        '253'   => 'Melhorias - Terrenos',
        '254'   => 'Melhorias - Edificações',
        '255'   => 'Melhorias - Máquinas/Equipamentos',
        '256'   => 'Melhorias - Microcomputadores',
        '257'   => 'Melhorias - Meios de Transporte',
        '258'   => 'Melhorias - Outros',
        '260'   => 'Ativo Imobilizado Total (Ano Anterior)',
        '261'   => 'Ativo Imobilizado Total (Ano Atual)',
        '103'   => 'Lucro (Resultado do Exercício)',
        '104'   => 'Prejuízo (Resultado do Exercício)',
    ];

    /**
     * Faz o parse de um CSV de dados econômicos
     *
     * @param string $caminhoArquivo Caminho do arquivo no servidor (pode ser .tmp para uploads)
     * @param string|null $nomeOriginal Nome original do arquivo (para detectar extensão real em uploads)
     * @return array Dados parseados: ['dados' => [chave => valor], 'codigos_nao_mapeados' => [], 'erros' => []]
     */
    public static function parse(string $caminhoArquivo, ?string $nomeOriginal = null): array {
        // Detecta extensão: primeiro tenta do nome original (upload), senão do caminho
        $extensao = '';
        if ($nomeOriginal) {
            $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
        }
        if (empty($extensao)) {
            $extensao = strtolower(pathinfo($caminhoArquivo, PATHINFO_EXTENSION));
        }

        return match ($extensao) {
            'csv' => self::parseCSV($caminhoArquivo),
            'xlsx', 'xls' => self::parseXLSX($caminhoArquivo),
            default => throw new InvalidArgumentException(
                "Formato não suportado: .{$extensao}. Use .csv ou .xlsx"
            ),
        };
    }

    /**
     * Faz o parse de arquivo CSV
     */
    private static function parseCSV(string $caminhoArquivo): array {
        if (!file_exists($caminhoArquivo) || !is_readable($caminhoArquivo)) {
            throw new RuntimeException("Arquivo não encontrado ou não pode ser lido: {$caminhoArquivo}");
        }

        $handle = fopen($caminhoArquivo, 'r');
        if ($handle === false) {
            throw new RuntimeException("Não foi possível abrir o arquivo: {$caminhoArquivo}");
        }

        $dados = [];
        $codigosNaoMapeados = [];
        $erros = [];
        $linhaNum = 0;
        $encontrouCabecalho = false;

        // Detecta encoding: tenta UTF-8, senão assume Windows-1252
        $primeirosBytes = fread($handle, 3);
        $bom = ($primeirosBytes === "\xEF\xBB\xBF");
        if (!$bom) {
            rewind($handle);
        }

        while (($linha = fgetcsv($handle, 0, ';', '"', '')) !== false) {
            $linhaNum++;

            // Pula linhas vazias
            if (count($linha) < 2 || (count($linha) === 2 && trim($linha[1] ?? '') === '')) {
                continue;
            }

            // Limpa encoding se necessário
            foreach ($linha as $i => $val) {
                if (!mb_check_encoding($val, 'UTF-8')) {
                    $linha[$i] = mb_convert_encoding($val, 'UTF-8', 'Windows-1252');
                }
                $linha[$i] = trim($linha[$i]);
            }

            $codigo = $linha[0] ?? '';
            $descricao = $linha[1] ?? '';
            $valor = $linha[2] ?? '';

            // Ignora linhas de cabeçalho (ex: ";INFORMAÇÕES ECONOMICAS DA EMPRESA;")
            if (empty($codigo) && !empty($descricao)) {
                // Verifica se é um cabeçalho de seção
                if (preg_match('/^(INFORMAÇÕES|CÓDIGO|RECEITAS|CUSTOS|DESPESAS|DEMAIS|DEPRECIAÇÃO|GASTOS)/i', $descricao)) {
                    continue;
                }
            }

            // Ignora linha de cabeçalho da tabela
            if (preg_match('/CÓDIGO|DESCRIÇÃO/i', $codigo) && preg_match('/DESCRIÇÃO|VALOR/i', $descricao)) {
                $encontrouCabecalho = true;
                continue;
            }

            // Se não tem código, pula (linha de seção)
            if (empty($codigo)) {
                continue;
            }

            // Normaliza o código: maiúsculo
            $codigo = strtoupper(trim($codigo));

            // Verifica se é um código válido
            if (!in_array($codigo, self::CODIGOS_VALIDOS, true)) {
                $codigosNaoMapeados[] = "Linha {$linhaNum}: Código '{$codigo}' não reconhecido";
                continue;
            }

            // Limpa o valor
            $valorLimpo = self::limparValor($valor);

            // Mapeia para chave interna
            $chaveInterna = self::CODIGO_ECONOMICO_MAP[$codigo] ?? null;
            if ($chaveInterna === null) {
                $codigosNaoMapeados[] = "Linha {$linhaNum}: Código '{$codigo}' sem mapeamento interno";
                continue;
            }

            $dados[$chaveInterna] = $valorLimpo;
        }

        fclose($handle);

        return [
            'dados' => $dados,
            'codigos_nao_mapeados' => $codigosNaoMapeados,
            'erros' => $erros,
            'total_linhas' => $linhaNum,
            'total_codigos_encontrados' => count($dados),
        ];
    }

    /**
     * Faz o parse de arquivo XLSX usando Python (pandas/openpyxl)
     * Se Python não estiver disponível, tenta uma abordagem alternativa
     */
    private static function parseXLSX(string $caminhoArquivo): array {
        // Tenta converter XLSX para CSV usando Python
        $comandoPython = sprintf(
            'python -c "
import sys
try:
    import pandas as pd
    df = pd.read_excel(\"%s\", engine=\"openpyxl\", header=None)
    df.to_csv(sys.stdout, sep=\";\", index=False, header=False)
except ImportError:
    try:
        import openpyxl
        wb = openpyxl.load_workbook(\"%s\", read_only=True)
        ws = wb.active
        for row in ws.iter_rows(values_only=True):
            print(\";\".join(str(v) if v is not None else \"\" for v in row))
    except ImportError:
        print(\"ERRO: pandas ou openpyxl nao disponivel\", file=sys.stderr)
        sys.exit(1)
"',
            str_replace_backslashes($caminhoArquivo),
            str_replace_backslashes($caminhoArquivo)
        );

        $csvTemporario = tempnam(sys_get_temp_dir(), 'pac_econ_') . '.csv';
        exec($comandoPython . ' 2>' . escapeshellarg($csvTemporario . '.err'), $output, $exitCode);

        if ($exitCode !== 0) {
            $erro = file_exists($csvTemporario . '.err') 
                ? file_get_contents($csvTemporario . '.err')
                : 'Erro desconhecido ao processar XLSX';
            unlink($csvTemporario . '.err');
            
            // Se Python não está disponível, tenta usar biblioteca PHP pura
            if (strpos($erro, 'nao disponivel') !== false || strpos($erro, 'No module') !== false) {
                 throw new RuntimeException(
                    "Para arquivos .xlsx, instale pandas/openpyxl no Python:\n" .
                    "pip install pandas openpyxl\n\n" .
                    "Ou converta o arquivo para CSV manualmente."
                );
            }
            throw new RuntimeException("Erro ao processar XLSX: {$erro}");
        }

        // Salva a saída do Python em um CSV temporário
        file_put_contents($csvTemporario, implode("\n", $output));
        
        try {
            $resultado = self::parseCSV($csvTemporario);
        } finally {
            if (file_exists($csvTemporario)) unlink($csvTemporario);
            if (file_exists($csvTemporario . '.err')) unlink($csvTemporario . '.err');
        }

        return $resultado;
    }

    /**
     * Limpa e formata o valor numérico
     * 
     * Regras:
     * - "-" (travessão) vira '' (vazio)
     * - "4.782.963" (pontos como milhar) vira 4782963
     * - "-189.921.157" (negativo) mantém sinal
     * - Valores com vírgula decimal são tratados (ex: "1.234,56")
     */
    public static function limparValor(string $valor): string {
        $valor = trim($valor);

        // Vazio ou nulo
        if ($valor === '' || $valor === null) return '';

        // Travessão, traço simples → 0 (conforme requisito do usuário: "-" vira zero)
        if (in_array($valor, ['-', '–', '—'], true)) {
            return '0';
        }

        // Indicações de vazio/ausência → vazio (não preenche o campo)
        if (in_array($valor, ['', 'ND', 'N/D', 'NA', 'N/A'], true)) {
            return '';
        }

        // Verifica se tem vírgula como separador decimal (formato BR: 1.234,56)
        $temVirgulaDecimal = preg_match('/,\d{1,2}$/', $valor);
        if ($temVirgulaDecimal) {
            // Remove pontos de milhar, troca vírgula por ponto
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } else {
            // Apenas remove pontos de milhar (formato: 4.782.963 ou -189.921.157)
            $valor = str_replace('.', '', $valor);
        }

        // Remove espaços, R$, $, etc.
        $valor = preg_replace('/[^\d\.,\-]/', '', $valor);
        $valor = trim($valor);

        if ($valor === '' || $valor === '-' || $valor === '.') return '0';

        // Converte para inteiro (remove decimais se existirem)
        if (is_numeric($valor)) {
            return (string)(int)round((float)$valor);
        }

        // Fallback
        $soDigitos = preg_replace('/[^0-9\-]/', '', $valor);
        return $soDigitos !== '' ? $soDigitos : '0';
    }

    /**
     * Retorna o mapeamento código → chave interna
     */
    public static function getCodigoEconomicoMap(): array {
        return self::CODIGO_ECONOMICO_MAP;
    }

    /**
     * Retorna descrições dos códigos
     */
    public static function getCodigoDescricoes(): array {
        return self::CODIGO_DESCRICOES;
    }

    /**
     * Retorna lista de códigos válidos
     */
    public static function getCodigosValidos(): array {
        return self::CODIGOS_VALIDOS;
    }

    /**
     * Retorna a chave inversa (chave interna → código econômico)
     */
    public static function getChaveParaCodigo(): array {
        return array_flip(self::CODIGO_ECONOMICO_MAP);
    }
}

/**
 * Utilitário: substitui barras invertidas em path (Windows compat)
 */
if (!function_exists('str_replace_backslashes')) {
    function str_replace_backslashes(string $path): string {
        return str_replace('\\', '/', $path);
    }
}
