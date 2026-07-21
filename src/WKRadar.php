<?php
/**
 * WKRadar.php - Cliente para integração com a API do WK Radar
 * 
 * API para obter dados financeiros (balancete/DRE) de empresas.
 * Requer token de autenticação.
 */

class WKRadar {
    private string $baseUrl;
    private ?string $token;
    private ?string $empresaId;
    private int $timeout;
    private array $mapeamento = [];

    public function __construct(?string $token = null, ?string $baseUrl = null, ?string $empresaId = null, int $timeout = 30) {
        $this->token = $token;
        $this->baseUrl = $baseUrl ?: 'https://api.wk.com.br/api/v1';
        $this->empresaId = $empresaId;
        $this->timeout = $timeout;
        $this->carregarMapeamentoPadrao();
    }

    public function setToken(string $token): void {
        $this->token = $token;
    }

    public function hasToken(): bool {
        return !empty($this->token);
    }

    /**
     * Carrega mapeamento de contas de arquivo JSON externo
     */
    public function carregarMapeamento(string $caminhoConfig): bool {
        if (!file_exists($caminhoConfig)) {
            return false;
        }
        $conteudo = file_get_contents($caminhoConfig);
        $config = json_decode($conteudo, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($config['campos'])) {
            return false;
        }
        $this->mapeamento = $config['campos'];
        return true;
    }

    /**
     * Obtém balancete anual do WK Radar
     * 
     * @param int $ano Ano do balancete
     * @param int $mesInicio Mês inicial (1=janeiro)
     * @param int $mesFim Mês final (12=dezembro)
     * @return array Lista de contas com saldos
     */
    public function obterBalancete(int $ano, int $mesInicio = 1, int $mesFim = 12): array {
        if (empty($this->token)) {
            throw new RuntimeException("WK_TOKEN não configurado. Configure o token nas configurações.");
        }

        $url = "{$this->baseUrl}/contabil/balancete";
        $params = http_build_query([
            'ano' => $ano,
            'mes_inicio' => $mesInicio,
            'mes_fim' => $mesFim,
            ...($this->empresaId ? ['empresa_id' => $this->empresaId] : []),
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "{$url}?{$params}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'PACGenerator/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                "Authorization: Bearer {$this->token}",
            ],
        ]);

        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = curl_error($ch);
        curl_close($ch);

        if ($erro) {
            throw new RuntimeException("Erro de conexão com WK Radar: {$erro}");
        }

        if ($httpCode === 401) {
            throw new RuntimeException("Token WK inválido ou expirado. Verifique seu token nas configurações.");
        }
        if ($httpCode === 403) {
            throw new RuntimeException("Sem permissão para acessar o balancete no WK Radar.");
        }
        if ($httpCode === 404) {
            throw new RuntimeException("Balancete não encontrado para o ano {$ano}.");
        }
        if ($httpCode !== 200) {
            throw new RuntimeException("Erro HTTP {$httpCode} do WK Radar: " . substr($resposta, 0, 200));
        }

        $dados = json_decode($resposta, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Erro ao decodificar resposta do WK Radar: " . json_last_error_msg());
        }

        return $dados;
    }

    /**
     * Processa o balancete e extrai valores financeiros para os campos do PAC
     * 
     * @param array $balancete Lista de contas com saldos
     * @return array Dicionário com chaves financeiras do PAC preenchidas
     */
    public function extrairDadosPAC(array $balancete): array {
        $dados = [];

        // Índice por código de conta
        $contas = [];
        foreach ($balancete as $conta) {
            $codigo = $conta['codigo_conta'] ?? '';
            $saldo = $conta['saldo_final'] ?? $conta['saldo_periodo'] ?? 0;
            $contas[$codigo] = (float)$saldo;
        }

        // Para cada campo PAC, soma as contas mapeadas
        foreach ($this->mapeamento as $chavePAC => $mapeamento) {
            $valorTotal = 0;

            foreach ($mapeamento['contas_incluir'] ?? [] as $prefixo) {
                $soma = self::somarContasPorPrefixo($contas, $prefixo);
                $valorTotal += $soma;
            }

            foreach ($mapeamento['contas_excluir'] ?? [] as $prefixo) {
                $soma = self::somarContasPorPrefixo($contas, $prefixo);
                $valorTotal -= $soma;
            }

            if (abs($valorTotal) > 0) {
                $dados[$chavePAC] = (int)round($valorTotal);
            }
        }

        return $dados;
    }

    // =========================================================================
    // UTILITÁRIOS
    // =========================================================================

    private function carregarMapeamentoPadrao(): void {
        $this->mapeamento = [
            'receita_revenda' => ['contas_incluir' => ['3.01.01'], 'contas_excluir' => ['3.01.02', '3.01.03']],
            'receita_venda_produtos' => ['contas_incluir' => ['3.01.02']],
            'receita_servicos' => ['contas_incluir' => ['3.01.03']],
            'receita_outras_atividades' => ['contas_incluir' => ['3.01.99']],
            'deducoes_vendas' => ['contas_incluir' => ['3.02']],
            'outras_receitas' => ['contas_incluir' => ['3.06']],
            'despesa_mercadorias_insumos' => ['contas_incluir' => ['3.02.01']],
            'despesa_combustiveis' => ['contas_incluir' => ['3.03.10']],
            'despesa_leasing' => ['contas_incluir' => ['3.03.11']],
            'despesa_alugueis' => ['contas_incluir' => ['3.03.04']],
            'despesa_utilidades' => ['contas_incluir' => ['3.03.06']],
            'despesa_servicos_terceiros' => ['contas_incluir' => ['3.03.05']],
            'despesa_pessoal' => ['contas_incluir' => ['3.03.01', '3.03.02', '3.03.03']],
            'despesa_tributos' => ['contas_incluir' => ['3.04']],
            'despesa_depreciacao' => ['contas_incluir' => ['3.03.08']],
            'despesa_financeiras' => ['contas_incluir' => ['3.05']],
            'despesa_outras_operacionais' => ['contas_incluir' => ['3.03.99']],
            'salarios_anuais' => ['contas_incluir' => ['3.03.01.001', '3.03.01.002']],
            'encargos_sociais' => ['contas_incluir' => ['3.03.02']],
            'estoque_fim_ano' => ['contas_incluir' => ['1.01.01']],
            'imobilizado_fim_ano' => ['contas_incluir' => ['1.02.01']],
            'depreciacao_acumulada_fim' => ['contas_incluir' => ['1.02.02']],
        ];
    }

    private static function somarContasPorPrefixo(array $contas, string $prefixo): float {
        $total = 0;
        foreach ($contas as $codigo => $saldo) {
            if (str_starts_with($codigo, $prefixo)) {
                $total += $saldo;
            }
        }
        return $total;
    }

    public function getMapeamento(): array {
        return $this->mapeamento;
    }
}
