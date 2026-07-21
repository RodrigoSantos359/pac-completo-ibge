<?php
/**
 * PACGenerator.php - Geração de CSV para o PAC Completo IBGE
 * 
 * Monta as linhas do CSV com exatamente 308 posições conforme layout oficial,
 * e gera o arquivo final formatado (Windows-1252, delimitador ;, CRLF, sem cabeçalho).
 *
 * O arquivo NÃO deve ter cabeçalho - apenas os dados separados por ;
 * com exatamente 308 campos por linha.
 *
 * ATENÇÃO: Não usar BOM UTF-8. O sistema do IBGE lê como Windows-1252 e o BOM
 * vira caracteres literais 'ï»¿' no primeiro campo (CNPJ Raiz).
 */

class PACGenerator {
    private string $ano;
    private array $layout;
    private array $cabecalho;
    private array $posicaoIndex;   // nome → posição (1-308)

    public function __construct(string $ano = PACLayout::ANO_PADRAO) {
        $this->ano = $ano;
        $this->layout = PACLayout::gerar($ano);
        $this->cabecalho = array_map(fn($col) => $col['descricao'], $this->layout);
        // Índice rápido: nome da coluna → posição (1-308)
        $this->posicaoIndex = [];
        foreach ($this->layout as $idx => $col) {
            $this->posicaoIndex[$col['descricao']] = $idx + 1;
        }
    }

    /**
     * MAPEAMENTO IBGE → CHAVES INTERNAS para o cálculo de Resultado do Exercício
     * 
     * A fórmula do layout (posições 165-166) usa códigos IBGE:
     * Lucro se: (030 + 037) > [(049 + 054 - 059) + (069 + 091 + 097 + 102)]
     * Prejuízo se: (030 + 037) < [(049 + 054 - 059) + (069 + 091 + 097 + 102)]
     */
    private const IBGE_RESULTADO_MAP = [
        '030' => [ // Receita Líquida (códigos 021-029)
            'receita_revenda', 'comissoes_vendas',
            'receita_venda_produtos', 'receita_servicos_manutencao',
            'receita_royalties', 'receita_outras_atividades',
            'deducoes_vendas_canceladas', 'deducoes_icms',
            'deducoes_pis_pasep', 'deducoes_outros_impostos',
        ],
        '037' => [ // Demais Receitas (códigos 031-036)
            'receita_aluguel', 'receitas_financeiras',
            'variacoes_monetarias_ativas', 'resultado_positivo_participacoes',
            'demais_receitas_operacionais', 'outras_receitas',
        ],
        '049' => [ // Compras (códigos 046-048)
            'compras_mercadorias', 'compras_materias_primas', 'compras_material_embalagem',
        ],
        '054' => [ // Estoques Iniciais (códigos 050-053)
            'estoque_mercadorias_anterior', 'estoque_produtos_anterior',
            'estoque_materias_primas_anterior', 'estoque_material_embalagem_anterior',
        ],
        '059' => [ // Estoques Finais (códigos 055-058)
            'estoque_mercadorias_atual', 'estoque_produtos_atual',
            'estoque_materias_primas_atual', 'estoque_material_embalagem_atual',
        ],
        '069' => [ // Gastos Pessoal (códigos 061-068A)
            'gastos_salarios', 'gastos_participacao_lucros',
            'gastos_prolabore', 'gastos_previdencia_social',
            'gastos_fgts', 'gastos_previdencia_privada',
            'gastos_indenizacoes', 'gastos_beneficios',
            'gastos_pis_folha',
        ],
        '091' => [ // Despesas Operacionais (códigos 070-090)
            'despesa_alugueis_imoveis', 'despesa_alugueis_maquinas',
            'despesa_condominios', 'despesa_comissoes',
            'despesa_publicidade', 'despesa_combustiveis',
            'despesa_servicos_autonomos', 'despesa_servicos_tecnicos',
            'despesa_vigilancia', 'despesa_fretes',
            'despesa_mao_obra_temporaria', 'despesa_manutencao',
            'despesa_outros_servicos', 'despesa_comunicacao',
            'despesa_energia', 'despesa_armazenagem',
            'despesa_seguros', 'despesa_impostos_taxas',
            'despesa_viagens', 'despesa_material_expediente',
            'despesa_royalties', 'despesa_pis_cofins',
            'despesa_demais_operacionais',
        ],
        '097' => [ // Despesas Financeiras (códigos 092-096)
            'despesa_leasing', 'despesa_financeiras',
            'variacoes_monetarias_passivas', 'despesa_comissoes_cartao',
            'resultado_negativo_participacoes',
        ],
        '102' => [ // Depreciação + Provisões (códigos 098-101)
            'despesa_depreciacao', 'despesa_outras',
            'provisao_irpj_csll', 'outras_provisoes',
        ],
    ];

    /**
     * Mapeamento de compatibilidade retroativa: chaves antigas → novas chaves
     * Garante que dados de sessões anteriores ou formulários antigos ainda funcionem
     */
    private const BACKWARD_MAP = [
        // Pessoal (antigo → novo)
        'salarios_anuais' => 'gastos_salarios',
        'encargos_sociais' => 'gastos_previdencia_social',

        // Receitas
        'receita_servicos' => 'receita_servicos_manutencao',
        'deducoes_vendas' => 'deducoes_vendas_canceladas',

        // Despesas
        'despesa_alugueis' => 'despesa_alugueis_imoveis',
        'despesa_utilidades' => 'despesa_energia',
        'despesa_tributos' => 'despesa_impostos_taxas',
        'despesa_outras_operacionais' => 'despesa_demais_operacionais',
        'despesa_servicos_terceiros' => 'despesa_servicos_tecnicos',
        'despesa_mercadorias_insumos' => 'compras_mercadorias',
        'despesa_pessoal' => 'gastos_salarios',

        // Estoques
        'estoque_fim_ano' => 'estoque_mercadorias_atual',
        'estoque_inicio_ano' => 'estoque_mercadorias_anterior',

        // Imobilizado
        'imobilizado_inicio_ano' => 'imobilizado_total_anterior',
        'imobilizado_fim_ano' => 'imobilizado_total_atual',
        'imobilizado_aquisicoes' => 'aquisicoes_maquinas',
        'imobilizado_baixas' => 'baixas_maquinas',
        'depreciacao_acumulada_inicio' => 'despesa_depreciacao',
        'depreciacao_acumulada_fim' => 'despesa_depreciacao',

        // Ligações
        'ligacao_cnpj_raiz' => 'ligacao1_cnpj_raiz',
        'ligacao_cnpj_sufixo' => 'ligacao1_cnpj_sufixo',
        'ligacao_cnpj_dv' => 'ligacao1_cnpj_dv',

        // Receitas antigas que foram removidas - mapear para a mais próxima
        'receita_construcao' => 'receita_outras_atividades',
        'receita_transporte_cargas' => 'receita_outras_atividades',
        'receita_transporte_passageiros' => 'receita_outras_atividades',
        'receita_liquida' => 'receita_revenda',

        // Margem e valor adicionado (não existem no layout PAC Completo)
        'margem_comercializacao' => '',
        'valor_adicionado' => '',
    ];

    /**
     * Monta uma linha do CSV a partir dos dados da empresa
     * Retorna array com exatamente 308 valores
     */
    public function montarLinha(array $empresa): array {
        // Aplica mapeamento de compatibilidade para chaves antigas
        // Usa empty() porque handleSalvar seta novas chaves como '' 
        foreach (self::BACKWARD_MAP as $chaveAntiga => $chaveNova) {
            if (!empty($empresa[$chaveAntiga]) && empty($empresa[$chaveNova]) && $chaveNova !== '') {
                $empresa[$chaveNova] = $empresa[$chaveAntiga];
            }
        }

        // ATENÇÃO: Cada campo de pessoal (posições 66-81) deve ser preenchido
        // manualmente pelo usuário no formulário. NÃO distribuir automaticamente
        // valores de pessoal_total, media_ano ou similares. O layout exige dados
        // reais de cada trimestre, não estimativas.

        // Auto-calcula Resultado do Exercício se estiver vazio
        // Só aplica para Lucro Real (1) ou Lucro Presumido (2)
        if (empty($empresa['resultado_lucro']) && empty($empresa['resultado_prejuizo'])) {
            $resultado = $this->calcularResultado($empresa);
            if ($resultado !== null) {
                $empresa['resultado_lucro'] = $resultado['resultado_lucro'];
                $empresa['resultado_prejuizo'] = $resultado['resultado_prejuizo'];
            }
        }

        // Default para campos de endereço/alterações (posições 60-62): IBGE espera 1=Sim ou 2=Não
        // Se vazio, o sistema do IBGE rejeita com "fora da faixa válida"
        foreach (['endereco_uc_diferente', 'endereco_sede_diferente', 'razao_social_diferente'] as $ch) {
            if (empty($empresa[$ch] ?? '')) {
                $empresa[$ch] = '2';
            }
        }

        // Default para campo OUTFORMASLIG (posição 307): IBGE espera 00=Não há ou 03=Compartilhamento de custos
        if (empty($empresa['outras_formas_ligacao'] ?? '')) {
            $empresa['outras_formas_ligacao'] = '00';
        }

        // Inicializa todas as 308 posições como vazias
        $valores = array_fill(0, 308, '');

        $mapeamento = PACValidator::getMapeamentoDireto();

        // 1. Mapeamento direto (chave interna → nome da coluna)
        foreach ($mapeamento as $chaveInterna => $nomeColuna) {
            $nomeColunaReal = str_replace('XXXX', $this->ano, $nomeColuna);
            $nomeColunaReal = str_replace('<Ano Anterior>', (string)((int)$this->ano - 1), $nomeColunaReal);
            $nomeColunaReal = str_replace('<Ano Atual>', $this->ano, $nomeColunaReal);

            $valorBruto = $empresa[$chaveInterna] ?? null;

            // Busca a definição da coluna para saber o tipo
            $colunaDef = PACLayout::buscarPorNome($nomeColunaReal, $this->ano);
            $tipo = $colunaDef['tipo'] ?? 'texto';

            // Encontra a posição no layout
            $posicao = $this->encontrarPosicao($nomeColunaReal);
            if ($posicao !== null) {
                $valores[$posicao - 1] = PACValidator::normalizarValor($valorBruto, $tipo);
            }
        }

        // 2. Mercadorias (agora são 3 itens simples de texto - posições 56-58)
        if (!empty($empresa['mercadorias']) && is_array($empresa['mercadorias'])) {
            for ($i = 0; $i < min(3, count($empresa['mercadorias'])); $i++) {
                $item = $empresa['mercadorias'][$i];
                // Pega a descrição do item de mercadoria
                $descricao = '';
                if (is_string($item)) {
                    $descricao = $item;
                } elseif (is_array($item)) {
                    $descricao = $item['descricao'] ?? $item['item'] ?? $item['codigo'] ?? '';
                }
                $posicao = 56 + $i; // posições 56, 57, 58
                $valores[$posicao - 1] = PACValidator::normalizarValor($descricao, 'texto');
            }
        }

        // 3. Mercadorias individuais (chaves específicas)
        for ($i = 1; $i <= 3; $i++) {
            $chave = "mercadoria_item_{$i}";
            if (isset($empresa[$chave])) {
                $posicao = 55 + $i; // 56, 57, 58
                $valores[$posicao - 1] = PACValidator::normalizarValor($empresa[$chave], 'texto');
            }
        }

        // 4. Dados de regionalização (posições 199-306): 4 blocos de 27 UFs
        $this->preencherRegionalizacao($valores, $empresa, 'regional_pessoal', 199);   // Pessoal por UF
        $this->preencherRegionalizacao($valores, $empresa, 'regional_salarios', 226);   // Salários por UF
        $this->preencherRegionalizacao($valores, $empresa, 'regional_unidades', 253);   // Unidades Locais por UF
        $this->preencherRegionalizacao($valores, $empresa, 'regional_receita', 280);    // Receita por UF

        return $valores;
    }

    /**
     * Calcula o Resultado do Exercício (Lucro/Prejuízo) conforme fórmula do IBGE.
     * 
     * Fórmula (posições 165-166 do layout):
     *   Lucro se:    (030 + 037) > [(049 + 054 - 059) + (069 + 091 + 097 + 102)]
     *   Prejuízo se: (030 + 037) < [(049 + 054 - 059) + (069 + 091 + 097 + 102)]
     * 
     * Só aplica para empresas optantes de Lucro Real (1) ou Lucro Presumido (2).
     * 
     * @param array $empresa Dados da empresa com os campos econômicos
     * @return array|null ['resultado_lucro' => string, 'resultado_prejuizo' => string] ou null se não aplicável
     */
    public function calcularResultado(array $empresa): ?array {
        $formaTrib = $empresa['forma_tributacao'] ?? '';
        // Só aplica para Lucro Real (1) ou Lucro Presumido (2)
        if (!in_array($formaTrib, ['1', '2'], true)) {
            return null;
        }

        // Helper para somar valores de um grupo de chaves
        $somar = function(array $chaves) use ($empresa): int {
            $total = 0;
            foreach ($chaves as $ch) {
                $v = $empresa[$ch] ?? '';
                $v = trim((string)$v);
                if ($v === '' || $v === '-') continue;
                $v = preg_replace('/[^0-9\-]/', '', $v);
                $total += (int)$v;
            }
            return $total;
        };

        $ladoA = $somar(self::IBGE_RESULTADO_MAP['030'])
               + $somar(self::IBGE_RESULTADO_MAP['037']);

        $ladoB = ($somar(self::IBGE_RESULTADO_MAP['049'])
                + $somar(self::IBGE_RESULTADO_MAP['054'])
                - $somar(self::IBGE_RESULTADO_MAP['059']))
               + $somar(self::IBGE_RESULTADO_MAP['069'])
               + $somar(self::IBGE_RESULTADO_MAP['091'])
               + $somar(self::IBGE_RESULTADO_MAP['097'])
               + $somar(self::IBGE_RESULTADO_MAP['102']);

        $diferenca = $ladoA - $ladoB;

        if ($diferenca > 0) {
            return [
                'resultado_lucro' => (string)$diferenca,
                'resultado_prejuizo' => '',
            ];
        } elseif ($diferenca < 0) {
            return [
                'resultado_lucro' => '',
                'resultado_prejuizo' => (string)abs($diferenca),
            ];
        }

        // Resultado zero: pode preencher qualquer um com 0
        return [
            'resultado_lucro' => '0',
            'resultado_prejuizo' => '0',
        ];
    }

    /**
     * Encontra a posição (1-308) de uma coluna pelo nome (O(1) via índice)
     */
    private function encontrarPosicao(string $nomeColuna): ?int {
        return $this->posicaoIndex[$nomeColuna] ?? null;
    }

    /**
     * Preenche valores de regionalização (27 UFs) em uma faixa de posições
     * Helper para evitar repetição dos 4 blocos (pessoal, salários, unidades, receita)
     *
     * @param array &$valores Array de 308 posições (referência)
     * @param array $empresa Dados da empresa
     * @param string $chave Chave no array da empresa (ex: 'regional_pessoal')
     * @param int $posBase Posição inicial no layout (ex: 199, 226, 253, 280)
     */
    private function preencherRegionalizacao(array &$valores, array $empresa, string $chave, int $posBase): void {
        if (empty($empresa[$chave]) || !is_array($empresa[$chave])) return;
        
        $idx = 0;
        foreach (PACValidator::getUFSOrdem() as $sigla => $nome) {
            $posicao = $posBase + $idx;
            $valor = $empresa[$chave][$sigla] ?? $empresa[$chave][$idx] ?? null;
            if ($valor !== null) {
                $valores[$posicao - 1] = PACValidator::normalizarValor($valor, 'numerico');
            }
            $idx++;
        }
    }

    /**
     * Gera o arquivo CSV completo com exatamente 308 colunas
     * 
     * @param array $empresas Lista de dicionários com dados das empresas
     * @param string $caminhoArquivo Caminho do arquivo CSV de saída
     * @return array Estatísticas da operação
     */
    public function gerarCSV(array $empresas, string $caminhoArquivo): array {
        $inicio = microtime(true);
        $stats = [
            'total' => count($empresas),
            'processadas' => 0,
            'erros' => 0,
            'avisos' => 0,
            'caminho' => $caminhoArquivo,
        ];

        $empresasValidas = [];

        foreach ($empresas as $i => $empresa) {
            $resultado = PACValidator::validarEmpresa($empresa, $this->ano);
            $stats['avisos'] += count($resultado['avisos']);

            if (!$resultado['valida']) {
                $stats['erros']++;
                continue;
            }

            $empresasValidas[] = $empresa;
        }

        if (empty($empresasValidas)) {
            $stats['tempo_segundos'] = microtime(true) - $inicio;
            return $stats;
        }

        // Gera CSV no formato PAC: sem cabeçalho, sem aspas, ; como delimitador, CRLF
        // Codificação ANSI (Windows-1252) - compatível com o sistema do IBGE
        // ATENÇÃO: NÃO usar BOM UTF-8 aqui! O IBGE lê como Windows-1252 e o BOM vira
        // caracteres literais 'ï»¿' no primeiro campo, quebrando a posição 1 (CNPJ Raiz).
        $handle = fopen($caminhoArquivo, 'w');
        if ($handle === false) {
            throw new RuntimeException("Não foi possível criar o arquivo: {$caminhoArquivo}");
        }

        foreach ($empresasValidas as $empresa) {
            $linha = $this->montarLinha($empresa);

            // Aplica padding nos campos conforme tamanho definido no layout
            foreach ($linha as $i => $valor) {
                $tamanho = $this->layout[$i]['tamanho'] ?? 0;
                $tipo = $this->layout[$i]['tipo'] ?? 'texto';
                if ($tamanho > 0 && $tipo === 'numerico' && $valor !== '') {
                    // Para campos numéricos: padding com zeros à esquerda
                    $linha[$i] = str_pad($valor, (int)$tamanho, '0', STR_PAD_LEFT);
                }
            }
            
            // Converte UTF-8 para ANSI (Windows-1252)
            $linhaAnsi = array_map(function($v) {
                return mb_convert_encoding($v, 'Windows-1252', 'UTF-8');
            }, $linha);
            
            // Junta com ; sem aspas, finaliza com CRLF
            fwrite($handle, implode(';', $linhaAnsi) . "\r\n");
        }

        fclose($handle);

        $stats['processadas'] = count($empresasValidas);
        $stats['tempo_segundos'] = round(microtime(true) - $inicio, 2);

        return $stats;
    }

    /**
     * Gera CSV e força o download pelo navegador
     *
     * @throws RuntimeException Se nenhuma empresa for válida para gerar o CSV
     */
    public function downloadCSV(array $empresas, string $nomeArquivo = 'pac_2025.csv'): void {
        $stats = $this->gerarCSV($empresas, sys_get_temp_dir() . '/' . uniqid('pac_') . '.csv');

        // Se nenhuma empresa foi processada, avisa o usuário com detalhes
        if ($stats['processadas'] === 0 && $stats['erros'] > 0) {
            throw new RuntimeException(
                'Nenhuma empresa pôde ser incluída no CSV. ' . $stats['erros'] . ' empresa(s) com erros de validação. ' .
                'Verifique os campos obrigatórios (CNPJ, Razão Social, UF, CNAE, etc.) no editor antes de gerar.'
            );
        }

        $caminhoTemp = $stats['caminho'];
        if (!file_exists($caminhoTemp)) {
            throw new RuntimeException("Erro ao gerar arquivo temporário");
        }

        // Se tem avisos, loga
        if ($stats['avisos'] > 0) {
            error_log('[PAC] Geração CSV: ' . $stats['processadas'] . ' processadas, ' .
                $stats['erros'] . ' erros, ' . $stats['avisos'] . ' avisos.');
        }

        header('Content-Type: text/csv; charset=windows-1252');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . filesize($caminhoTemp));
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($caminhoTemp);
        unlink($caminhoTemp);
        exit;
    }

    /**
     * Retorna o número de posições do layout (308)
     */
    public function getTotalPosicoes(): int {
        return PACLayout::getTotalPosicoes();
    }
}
