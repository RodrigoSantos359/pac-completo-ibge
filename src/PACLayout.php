<?php
/**
 * PACLayout.php - Layout Oficial do PAC Completo IBGE
 * 
 * Define as 308 posições do layout de importação conforme
 * Manual de Importação de Questionário - PAC Completo (página 24-37).
 * Cada posição possui: descrição, tamanho máximo, tipo e código da variável.
 */

class PACLayout {
    const ANO_PADRAO = '2025';

    /**
     * Retorna a lista completa das 308 posições no layout oficial do IBGE
     */
    public static function gerar(string $ano = self::ANO_PADRAO): array {
        $posicoes = self::getPosicoes();
        
        // Substitui XXXX pelo ano informado
        return array_map(function($p) use ($ano) {
            $p['descricao'] = str_replace('XXXX', $ano, $p['descricao']);
            $p['descricao'] = str_replace('<Ano Anterior>', (string)((int)$ano - 1), $p['descricao']);
            $p['descricao'] = str_replace('<Ano Atual>', $ano, $p['descricao']);
            return $p;
        }, $posicoes);
    }

    /**
     * Retorna os nomes das 308 colunas (cabeçalho)
     */
    public static function getCabecalho(string $ano = self::ANO_PADRAO): array {
        $layout = self::gerar($ano);
        return array_map(fn($p) => $p['descricao'], $layout);
    }

    /**
     * Busca uma posição pelo nome da coluna
     */
    public static function buscarPorNome(string $nomeColuna, string $ano = self::ANO_PADRAO): ?array {
        $layout = self::gerar($ano);
        foreach ($layout as $p) {
            if ($p['descricao'] === $nomeColuna) return $p;
        }
        return null;
    }

    /**
     * Busca uma posição pelo número da posição
     */
    public static function buscarPorPosicao(int $posicao): ?array {
        $posicoes = self::getPosicoes();
        foreach ($posicoes as $p) {
            if ($p['posicao'] === $posicao) return $p;
        }
        return null;
    }

    /**
     * Retorna o número total de posições do layout (308)
     */
    public static function getTotalPosicoes(): int {
        return count(self::getPosicoes());
    }

    /**
     * Define as 308 posições exatas conforme o manual do IBGE
     */
    private static function getPosicoes(): array {
        return [
            // ===================================================================
            // BLOCO 1 — DADOS CADASTRAIS DA SEDE (posições 1-22)
            // ===================================================================
            ['posicao' => 1,  'descricao' => 'Raiz CNPJ da Sede',                                          'tamanho' => 8,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 2,  'descricao' => 'Sufixo Sede',                                                 'tamanho' => 4,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 3,  'descricao' => 'Dígito Verificador da Sede',                                  'tamanho' => 2,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 4,  'descricao' => 'Razão Social',                                                'tamanho' => 55, 'tipo' => 'texto',    'cod_variavel' => '01'],
            ['posicao' => 5,  'descricao' => 'Nome Fantasia',                                               'tamanho' => 55, 'tipo' => 'texto',    'cod_variavel' => '02'],
            ['posicao' => 6,  'descricao' => 'Site da Empresa',                                             'tamanho' => 70, 'tipo' => 'texto',    'cod_variavel' => ''],
            ['posicao' => 7,  'descricao' => 'Sigla UF (SEDE)',                                             'tamanho' => 2,  'tipo' => 'texto',    'cod_variavel' => ''],
            ['posicao' => 8,  'descricao' => 'Município (Nome do Município da Sede da Empresa)',            'tamanho' => 45, 'tipo' => 'texto',    'cod_variavel' => ''],
            ['posicao' => 9,  'descricao' => 'Nome do Distrito (SEDE)',                                     'tamanho' => 45, 'tipo' => 'texto',    'cod_variavel' => ''],
            ['posicao' => 10, 'descricao' => 'Tipo de Logradouro (SEDE) (rua, avenida, rodovia, etc..)',    'tamanho' => 60, 'tipo' => 'texto',    'cod_variavel' => ''],
            ['posicao' => 11, 'descricao' => 'Nome do Logradouro (SEDE)',                                   'tamanho' => 52, 'tipo' => 'texto',    'cod_variavel' => '03'],
            ['posicao' => 12, 'descricao' => 'Número (SEDE)',                                               'tamanho' => 6,  'tipo' => 'texto',    'cod_variavel' => '04'],
            ['posicao' => 13, 'descricao' => 'Complemento (SEDE)',                                          'tamanho' => 15, 'tipo' => 'texto',    'cod_variavel' => '05'],
            ['posicao' => 14, 'descricao' => 'Bairro/Distrito/Localidade (SEDE)',                           'tamanho' => 45, 'tipo' => 'texto',    'cod_variavel' => '06'],
            ['posicao' => 15, 'descricao' => 'CEP (SEDE)',                                                  'tamanho' => 8,  'tipo' => 'numerico', 'cod_variavel' => '07'],
            ['posicao' => 16, 'descricao' => 'DDD (SEDE)',                                                  'tamanho' => 2,  'tipo' => 'numerico', 'cod_variavel' => '08'],
            ['posicao' => 17, 'descricao' => 'Telefone (SEDE)',                                             'tamanho' => 9,  'tipo' => 'numerico', 'cod_variavel' => '09'],
            ['posicao' => 18, 'descricao' => 'Ramal (SEDE)',                                                'tamanho' => 5,  'tipo' => 'numerico', 'cod_variavel' => '10'],
            ['posicao' => 19, 'descricao' => 'E-mail da empresa',                                           'tamanho' => 50, 'tipo' => 'texto',    'cod_variavel' => '14'],
            ['posicao' => 20, 'descricao' => 'Descrição da principal atividade(maior receita) da empresa',  'tamanho' => 66, 'tipo' => 'texto',    'cod_variavel' => '15'],
            ['posicao' => 21, 'descricao' => 'Código da CNAE 2.0',                                          'tamanho' => 4,  'tipo' => 'numerico', 'cod_variavel' => '16'],
            ['posicao' => 22, 'descricao' => 'Dígito Verificador do Código da CNAE 2.0',                    'tamanho' => 1,  'tipo' => 'numerico', 'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 2 — UNIDADE DE COLETA (posições 23-36)
            // ===================================================================
            ['posicao' => 23, 'descricao' => 'Sufixo da Unidade de Coleta',                                 'tamanho' => 4,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 24, 'descricao' => 'DV da Unidade de Coleta',                                     'tamanho' => 2,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 25, 'descricao' => 'Sigla UF (UNIDADE DE COLETA)',                                'tamanho' => 2,  'tipo' => 'texto',    'cod_variavel' => '27'],
            ['posicao' => 26, 'descricao' => 'Município (Nome do Município da UC da Empresa)',              'tamanho' => 45, 'tipo' => 'texto',    'cod_variavel' => ''],
            ['posicao' => 27, 'descricao' => 'Nome do Distrito (UNIDADE DE COLETA)',                        'tamanho' => 45, 'tipo' => 'texto',    'cod_variavel' => ''],
            ['posicao' => 28, 'descricao' => 'Tipo de Logradouro (UNIDADE DE COLETA) (rua, avenida, rodovia, etc..)', 'tamanho' => 60, 'tipo' => 'texto', 'cod_variavel' => ''],
            ['posicao' => 29, 'descricao' => 'Nome do Logradouro (UNIDADE DE COLETA)',                      'tamanho' => 52, 'tipo' => 'texto',    'cod_variavel' => '18'],
            ['posicao' => 30, 'descricao' => 'Número (UNIDADE DE COLETA)',                                  'tamanho' => 6,  'tipo' => 'texto',    'cod_variavel' => '19'],
            ['posicao' => 31, 'descricao' => 'Complemento (UNIDADE DE COLETA)',                             'tamanho' => 15, 'tipo' => 'texto',    'cod_variavel' => '20'],
            ['posicao' => 32, 'descricao' => 'Bairro/Distrito/Localidade (UNIDADE DE COLETA)',              'tamanho' => 45, 'tipo' => 'texto',    'cod_variavel' => '21'],
            ['posicao' => 33, 'descricao' => 'CEP (UNIDADE DE COLETA)',                                    'tamanho' => 8,  'tipo' => 'numerico', 'cod_variavel' => '22'],
            ['posicao' => 34, 'descricao' => 'DDD (UNIDADE DE COLETA)',                                    'tamanho' => 2,  'tipo' => 'numerico', 'cod_variavel' => '23'],
            ['posicao' => 35, 'descricao' => 'Telefone (UNIDADE DE COLETA)',                               'tamanho' => 9,  'tipo' => 'numerico', 'cod_variavel' => '24'],
            ['posicao' => 36, 'descricao' => 'Ramal (UNIDADE DE COLETA)',                                  'tamanho' => 5,  'tipo' => 'numerico', 'cod_variavel' => '25'],

            // ===================================================================
            // BLOCO 3 — DADOS DO RESPONSÁVEL (posições 37-42)
            // ===================================================================
            ['posicao' => 37, 'descricao' => 'Nome (Dados do responsável pelo preenchimento)',              'tamanho' => 55, 'tipo' => 'texto',    'cod_variavel' => '30'],
            ['posicao' => 38, 'descricao' => 'Cargo (Dados do responsável pelo preenchimento)',             'tamanho' => 55, 'tipo' => 'texto',    'cod_variavel' => '31'],
            ['posicao' => 39, 'descricao' => 'DDD (Dados do responsável pelo preenchimento)',              'tamanho' => 2,  'tipo' => 'numerico', 'cod_variavel' => '32'],
            ['posicao' => 40, 'descricao' => 'Telefone (Dados do responsável pelo preenchimento)',          'tamanho' => 9,  'tipo' => 'numerico', 'cod_variavel' => '33'],
            ['posicao' => 41, 'descricao' => 'Ramal (Dados do responsável pelo preenchimento)',             'tamanho' => 5,  'tipo' => 'numerico', 'cod_variavel' => '34'],
            ['posicao' => 42, 'descricao' => 'E-mail (Dados do responsável pelo preenchimento)',            'tamanho' => 50, 'tipo' => 'texto',    'cod_variavel' => '29'],

            // ===================================================================
            // BLOCO 4 — SITUAÇÃO CADASTRAL (posições 43-44)
            // ===================================================================
            ['posicao' => 43, 'descricao' => '01. Situação Cadastral em 31/12/XXXX',                        'tamanho' => 2,  'tipo' => 'texto',    'cod_variavel' => ''],
            ['posicao' => 44, 'descricao' => '02. Mudanças na Estrutura da Empresa no Ano de XXXX',         'tamanho' => 2,  'tipo' => 'texto',    'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 5 — DATA OCORRÊNCIA E LIGAÇÕES (posições 45-55)
            // ===================================================================
            ['posicao' => 45, 'descricao' => '01 – Mês: 01A. Data da Ocorrência para os Itens 03 e 04 da Situação Cadastral', 'tamanho' => 2, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 46, 'descricao' => '02 – Ano: 01A. Data da Ocorrência para os Itens 03 e 04 da Situação Cadastral', 'tamanho' => 4, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 47, 'descricao' => '03. Raiz Primeiro CNPJ de Ligação da Empresa',                'tamanho' => 8,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 48, 'descricao' => '03. Sufixo Primeiro CNPJ de Ligação da Empresa',              'tamanho' => 4,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 49, 'descricao' => '03. Dígito Verificador Primeiro CNPJ de Ligação da Empresa',  'tamanho' => 2,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 50, 'descricao' => '03. Raiz Segundo CNPJ de Ligação da Empresa',                 'tamanho' => 8,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 51, 'descricao' => '03. Sufixo Segundo CNPJ de Ligação da Empresa',               'tamanho' => 4,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 52, 'descricao' => '03. Dígito Verificador Segundo CNPJ de Ligação da Empresa',   'tamanho' => 2,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 53, 'descricao' => '03. Raiz Terceiro CNPJ de Ligação da Empresa',                'tamanho' => 8,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 54, 'descricao' => '03. Sufixo Terceiro CNPJ de Ligação da Empresa',              'tamanho' => 4,  'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 55, 'descricao' => '03. Dígito Verificador Terceiro CNPJ de Ligação da Empresa',  'tamanho' => 2,  'tipo' => 'numerico', 'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 6 — MERCADORIAS (posições 56-58)
            // ===================================================================
            ['posicao' => 56, 'descricao' => 'Item 1: 04 - Principais Mercadorias Revendidas por Ordem de Predominância na Receita, inclusive por Representação Comercial', 'tamanho' => 80, 'tipo' => 'texto', 'cod_variavel' => ''],
            ['posicao' => 57, 'descricao' => 'Item 2: 04 - Principais Mercadorias Revendidas por Ordem de Predominância na Receita, inclusive por Representação Comercial', 'tamanho' => 80, 'tipo' => 'texto', 'cod_variavel' => ''],
            ['posicao' => 58, 'descricao' => 'Item 3: 04 - Principais Mercadorias Revendidas por Ordem de Predominância na Receita, inclusive por Representação Comercial', 'tamanho' => 80, 'tipo' => 'texto', 'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 7 — TRIBUTAÇÃO (posição 59)
            // ===================================================================
            ['posicao' => 59, 'descricao' => '05. Forma de Tributação Utilizada pela Empresa',              'tamanho' => 1,  'tipo' => 'numerico', 'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 8 — ENDEREÇO DIFERENTE / ALTERAÇÕES (posições 60-65)
            // ===================================================================
            ['posicao' => 60, 'descricao' => 'O endereço atual da UNIDADE DE COLETA é diferente do informado no ano anterior?', 'tamanho' => 1, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 61, 'descricao' => 'O endereço atual da SEDE da empresa é diferente do informado no ano anterior?',   'tamanho' => 1, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 62, 'descricao' => 'A Razão Social informada é diferente da informada no ano anterior?',             'tamanho' => 1, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 63, 'descricao' => 'Razão Social anterior',                                            'tamanho' => 55, 'tipo' => 'texto', 'cod_variavel' => ''],
            ['posicao' => 64, 'descricao' => 'Motivo da alteração',                                              'tamanho' => 200,'tipo' => 'texto', 'cod_variavel' => ''],
            ['posicao' => 65, 'descricao' => 'Atividade anterior da empresa',                                    'tamanho' => 66, 'tipo' => 'texto', 'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 9 — PESSOAL OCUPADO (posições 66-81)
            // ===================================================================
            ['posicao' => 66,  'descricao' => 'Pessoal Assalariado - Ligado a atividade comercial ou de representação - Em 31/03',     'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '001'],
            ['posicao' => 67,  'descricao' => 'Pessoal Assalariado - Não ligado a atividade comercial - Em 31/03',                     'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '002'],
            ['posicao' => 68,  'descricao' => 'Pessoal Não Assalariado (Proprietário e sócios com atividade na empresa) - Em 31/03',   'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '003'],
            ['posicao' => 69,  'descricao' => 'Pessoal Não Assalariado (Membros da Família sem remuneração) - Em 31/03',               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '004'],
            ['posicao' => 70,  'descricao' => 'Pessoal Assalariado - Ligado a atividade comercial ou de representação - Em 30/06',     'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '006'],
            ['posicao' => 71,  'descricao' => 'Pessoal Assalariado - Não ligado a atividade comercial - Em 30/06',                     'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '007'],
            ['posicao' => 72,  'descricao' => 'Pessoal Não Assalariado (Proprietário e sócios com atividade na empresa) - Em 30/06',   'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '008'],
            ['posicao' => 73,  'descricao' => 'Pessoal Não Assalariado (Membros da Família sem remuneração) - Em 30/06',               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '009'],
            ['posicao' => 74,  'descricao' => 'Pessoal Assalariado - Ligado a atividade comercial ou de representação - Em 30/09',     'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '011'],
            ['posicao' => 75,  'descricao' => 'Pessoal Assalariado - Não ligado a atividade comercial - Em 30/09',                     'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '012'],
            ['posicao' => 76,  'descricao' => 'Pessoal Não Assalariado (Proprietário e sócios com atividade na empresa) - Em 30/09',   'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '013'],
            ['posicao' => 77,  'descricao' => 'Pessoal Não Assalariado (Membros da Família sem remuneração) - Em 30/09',               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '014'],
            ['posicao' => 78,  'descricao' => 'Pessoal Assalariado - Ligado a atividade comercial ou de representação - Em 31/12',     'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '016'],
            ['posicao' => 79,  'descricao' => 'Pessoal Assalariado - Não ligado a atividade comercial - Em 31/12',                     'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '017'],
            ['posicao' => 80,  'descricao' => 'Pessoal Não Assalariado (Proprietário e sócios com atividade na empresa) - Em 31/12',   'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '018'],
            ['posicao' => 81,  'descricao' => 'Pessoal Não Assalariado (Membros da Família sem remuneração) - Em 31/12',               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '019'],

            // ===================================================================
            // BLOCO 10 — RECEITAS (posições 82-91)
            // ===================================================================
            ['posicao' => 82, 'descricao' => 'Revenda de mercadorias (no caso de cooperativa, inclua as vendas a cooperados e de mercadorias de cooperados) - Receita Bruta', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 83, 'descricao' => 'Comissões sobre vendas de representação comercial e agentes de comércio',                  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '021A'],
            ['posicao' => 84, 'descricao' => 'Venda de produtos de fabricação própria - Receita Bruta',                                 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '022'],
            ['posicao' => 85, 'descricao' => 'Serviços de manutenção e reparação (veículos, eletrodomésticos, computadores, telefones, relógios, etc.) - Receita Bruta', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '023'],
            ['posicao' => 86, 'descricao' => 'Royalties de franquias - Receita Bruta',                                                  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '024'],
            ['posicao' => 87, 'descricao' => 'Outras atividades (lanchonetes, restaurantes, armazenagem, transporte, exploração de estacionamento, etc.) - Receita Bruta', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '025'],
            ['posicao' => 88, 'descricao' => 'Vendas canceladas, abatimentos e descontos incondicionais (-) - Deduções',               'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '026'],
            ['posicao' => 89, 'descricao' => 'ICMS sobre vendas (-) - Deduções',                                                        'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '027'],
            ['posicao' => 90, 'descricao' => 'PIS/PASEP (-) - Deduções',                                                               'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '028'],
            ['posicao' => 91, 'descricao' => 'Outros impostos e contribuições sobre vendas (IPI, ISS, SIMPLES NACIONAL, COFINS) (-) - Deduções', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '029'],

            // ===================================================================
            // BLOCO 11 — DEMAIS RECEITAS (posições 92-97)
            // ===================================================================
            ['posicao' => 92, 'descricao' => 'Aluguel de imóveis, máquinas e equipamentos',                                            'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '031'],
            ['posicao' => 93, 'descricao' => 'Receitas financeiras (juros, descontos obtidos, etc.)',                                  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '032'],
            ['posicao' => 94, 'descricao' => 'Variações monetárias ativas',                                                            'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '033'],
            ['posicao' => 95, 'descricao' => 'Resultado positivo em participações societárias e em sociedades em conta de participação','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '034'],
            ['posicao' => 96, 'descricao' => 'Demais receitas operacionais (recuperação de despesas, etc.)',                           'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '035'],
            ['posicao' => 97, 'descricao' => 'Outras receitas - lucro na alienação de bens do ativo imobilizado, etc.',                'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '036'],

            // ===================================================================
            // BLOCO 12 — RECEITA LÍQUIDA DE REVENDA (posições 98-99)
            // ===================================================================
            ['posicao' => 98, 'descricao' => 'Varejo (vendas ao público em geral)',                                                    'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '038'],
            ['posicao' => 99, 'descricao' => 'Atacado (exportações, vendas a instituições públicas, comerciais, indústrias, etc.)',    'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '039'],

            // ===================================================================
            // BLOCO 13 — DESTINO DAS VENDAS (posições 100-103) - Percentuais
            // ===================================================================
            ['posicao' => 100, 'descricao' => 'Exportação direta - Percentual',                                                        'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '041'],
            ['posicao' => 101, 'descricao' => 'Vendas a outras empresas - Percentual',                                                 'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '042'],
            ['posicao' => 102, 'descricao' => 'Vendas para órgãos, empresas e instituições públicas - Percentual',                     'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '043'],
            ['posicao' => 103, 'descricao' => 'Vendas ao público em geral - Percentual',                                               'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '044'],

            // ===================================================================
            // BLOCO 14 — COMPRAS (posições 104-106)
            // ===================================================================
            ['posicao' => 104, 'descricao' => 'Mercadorias para revenda (inclusive mercadorias de cooperados. Inclua os fretes e seguros sobre as compras e não inclua as devoluções e os impostos recuperáveis)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '046'],
            ['posicao' => 105, 'descricao' => 'Matérias-primas para fabricação própria (deve preencher o Código 022)',                 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '047'],
            ['posicao' => 106, 'descricao' => 'Material de embalagem e outros materiais (de reposição, peças, etc.)',                 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '048'],

            // ===================================================================
            // BLOCO 15 — ESTOQUES (posições 107-114)
            // ===================================================================
            ['posicao' => 107, 'descricao' => 'Mercadorias para revenda (inclusive mercadorias de cooperados) - Em 31/12/<Ano Anterior>', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 108, 'descricao' => 'Produtos acabados e em elaboração (ver Código 022) - Em 31/12/<Ano Anterior>',            'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 109, 'descricao' => 'Matérias-primas para fabricação própria (ver Código 022) - Em 31/12/<Ano Anterior>',      'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 110, 'descricao' => 'Material de embalagem e outros materiais (de reposição, peças, etc.) - Em 31/12/<Ano Anterior>', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 111, 'descricao' => 'Mercadorias para revenda (inclusive mercadorias de cooperados) - Em 31/12/<Ano Atual>',     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 112, 'descricao' => 'Produtos acabados e em elaboração (ver Código 022) - Em 31/12/<Ano Atual>',                'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 113, 'descricao' => 'Matérias-primas para fabricação própria (ver Código 022) - Em 31/12/<Ano Atual>',          'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 114, 'descricao' => 'Material de embalagem e outros materiais (de reposição, peças, etc.) - Em 31/12/<Ano Atual>', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 16 — MÉTODO VALORAÇÃO (posições 115-116)
            // ===================================================================
            ['posicao' => 115, 'descricao' => 'Método de Valoração dos estoques',                                                        'tamanho' => 1, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 116, 'descricao' => 'Se responder o item 059A1 com "4 – Outro" é necessário discriminar',                      'tamanho' => 30,'tipo' => 'texto', 'cod_variavel' => '059B1'],

            // ===================================================================
            // BLOCO 17 — CUSTO (posição 117)
            // ===================================================================
            ['posicao' => 117, 'descricao' => 'Custo das mercadorias revendidas (valor contábil apurado e levado à DRE)',                'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 18 — GASTOS COM PESSOAL (posições 118-126)
            // ===================================================================
            ['posicao' => 118, 'descricao' => 'Salários e outras remunerações (inclusive 13º salário, férias, horas extras, etc.) - não incluir bolsa paga a estagiários', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 119, 'descricao' => 'Participação nos lucros (somente a parte distribuída aos empregados) e honorários da diretoria', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 120, 'descricao' => 'Retiradas pró-labore do proprietário ou sócios com atividade na empresa',                 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 121, 'descricao' => 'Contribuições para a Previdência Social (parte da empresa)',                              'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 122, 'descricao' => 'FGTS (se tiver empregados e não preencher, justificar em OBSERVAÇÕES)',                  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 123, 'descricao' => 'Contribuições para a previdência privada (parte da empresa)',                            'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 124, 'descricao' => 'Indenizações trabalhistas, rescisões contratuais e por dispensa incentivada - inclusive os 50% do FGTS', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 125, 'descricao' => 'Benefícios concedidos aos empregados (transporte, alimentação, auxílio-educação, planos de saúde, etc.)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 126, 'descricao' => 'PIS sobre folha de pagamento (somente se for contribuinte nesta modalidade)',            'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 19 — DESPESAS OPERACIONAIS (posições 127-155)
            // ===================================================================
            ['posicao' => 127, 'descricao' => 'Aluguéis de imóveis',                                                                   'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 128, 'descricao' => 'Aluguéis de máquinas, equipamentos e veículos',                                         'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '070A'],
            ['posicao' => 129, 'descricao' => 'Condomínios - inclusive taxas de Administração de shopping, galerias, etc.',             'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 130, 'descricao' => 'Comissões pagas a representantes comerciais e outros intermediários',                   'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 131, 'descricao' => 'Publicidade e propaganda (feiras, eventos, lançamento de produtos, promoções de vendas, material promocional e marketing)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 132, 'descricao' => 'Combustíveis, peças e acessórios consumidos nos meios de transporte próprios',           'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 133, 'descricao' => 'Serviços prestados por profissionais liberais ou autônomos (pessoas físicas)',           'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 134, 'descricao' => 'Serviços técnico-profissionais (serviços jurídicos, contabilidade, auditoria, consultoria, informática, etc.) - Serviços prestados por empresas', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 135, 'descricao' => 'Vigilância, segurança e transporte de valores - Serviços prestados por empresas',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 136, 'descricao' => 'Fretes e carretos (não inclua os fretes sobre as compras) - Serviços prestados por empresas', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 137, 'descricao' => 'Mão-de-obra contratada temporariamente junto a empresas locadoras de mão-de-obra',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 138, 'descricao' => 'Manutenção e reparação de imóveis, instalações, máquinas, veículos e equipamentos',      'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '080'],
            ['posicao' => 139, 'descricao' => 'Outros serviços prestados por empresas (limpeza, zeladoria, portaria, dedetização, cobranças, etc.)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '081'],
            ['posicao' => 140, 'descricao' => 'Serviços de comunicação (correio, fax, telefone e internet)',                            'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '082'],
            ['posicao' => 141, 'descricao' => 'Energia elétrica, gás, água e esgoto',                                                  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '083'],
            ['posicao' => 142, 'descricao' => 'Armazenagem, despesas portuárias, aeroportuárias e despesas com importação e exportação','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '084'],
            ['posicao' => 143, 'descricao' => 'Prêmios de seguros (imóveis, veículos, mercadorias, etc.)',                              'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '085'],
            ['posicao' => 144, 'descricao' => 'Impostos e taxas (IPTU, IPVA, IOF, alvarás, etc). Não incluir ICMS, ISS, COFINS, PIS, IPI, SIMPLES NACIONAL, etc.', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '086'],
            ['posicao' => 145, 'descricao' => 'Viagens e representações (inclusive diárias e estadias)',                                'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '087'],
            ['posicao' => 146, 'descricao' => 'Material de expediente, de uso, de consumo, de escritório e de limpeza',                'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '088'],
            ['posicao' => 147, 'descricao' => 'Royalties pelo uso de marcas, patentes e franquias',                                    'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '089'],
            ['posicao' => 148, 'descricao' => 'PIS/COFINS sobre outras receitas',                                                      'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '089B'],
            ['posicao' => 149, 'descricao' => 'Demais despesas operacionais - não inclua despesas financeiras, depreciação, provisão para IRPJ e CSLL, pagamento de IRPJ e CSLL e constituição das demais provisões', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 150, 'descricao' => 'Item 1: Discrimine os principais valores do código 090 quando ultrapassar 30% do total', 'tamanho' => 50, 'tipo' => 'texto', 'cod_variavel' => ''],
            ['posicao' => 151, 'descricao' => 'Valor 1: Discrimine os principais valores do código 090 quando ultrapassar 30% do total','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 152, 'descricao' => 'Item 2: Discrimine os principais valores do código 090 quando ultrapassar 30% do total', 'tamanho' => 50, 'tipo' => 'texto', 'cod_variavel' => ''],
            ['posicao' => 153, 'descricao' => 'Valor 2: Discrimine os principais valores do código 090 quando ultrapassar 30% do total','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 154, 'descricao' => 'Item 3: Discrimine os principais valores do código 090 quando ultrapassar 30% do total', 'tamanho' => 50, 'tipo' => 'texto', 'cod_variavel' => ''],
            ['posicao' => 155, 'descricao' => 'Valor 3: Discrimine os principais valores do código 090 quando ultrapassar 30% do total','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],

            // ===================================================================
            // BLOCO 20 — DESPESAS FINANCEIRAS (posições 156-160)
            // ===================================================================
            ['posicao' => 156, 'descricao' => 'Despesas com arrendamento mercantil financeiro (leasing financeiro) de máquinas, equipamentos e veículos', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 157, 'descricao' => 'Despesas financeiras - inclusive factoring, taxa de juros de longo prazo e despesas bancárias', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => ''],
            ['posicao' => 158, 'descricao' => 'Variações monetárias passivas',                                                          'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '094'],
            ['posicao' => 159, 'descricao' => 'Comissões pagas a administradoras de cartão de crédito',                                 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '095'],
            ['posicao' => 160, 'descricao' => 'Resultado negativo em participações societárias e em sociedades em conta de participação','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '096'],

            // ===================================================================
            // BLOCO 21 — DEPRECIAÇÃO / AMORTIZAÇÃO / PROVISÕES (posições 161-164)
            // ===================================================================
            ['posicao' => 161, 'descricao' => 'Depreciação e amortização (valores relativos ao ano - não inclua depreciação e amortização acumulada)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '098'],
            ['posicao' => 162, 'descricao' => 'Outras despesas (impairment, prejuízo na alienação de bens do ativo imobilizado, perdas no recebimento de crédito, perdas de estoque, etc.)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '099'],
            ['posicao' => 163, 'descricao' => 'Provisão para IRPJ e CSLL (não inclua constituição de ativo fiscal de IRPJ e CSLL)',   'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '100'],
            ['posicao' => 164, 'descricao' => 'Outras provisões constituídas (provisão para contingências, provisão para créditos de liquidação duvidosa, etc.)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '101'],

            // ===================================================================
            // BLOCO 22 — RESULTADO (posições 165-166)
            // ===================================================================
            ['posicao' => 165, 'descricao' => 'Lucro: Preencher se (030 + 037) > [(049 + 054 - 059) + (069 + 091 + 097 + 102)]',        'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '103'],
            ['posicao' => 166, 'descricao' => 'Prejuízo: Preencher se (030 + 037) < [(049 + 054 - 059) + (069 + 091 + 097 + 102)]',    'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '104'],

            // ===================================================================
            // BLOCO 23 — AQUISIÇÕES DE TERCEIROS (posições 167-172)
            // ===================================================================
            ['posicao' => 167, 'descricao' => 'Terrenos - Aquisições de Terceiros',                                                      'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '105'],
            ['posicao' => 168, 'descricao' => 'Edificações - Aquisições de Terceiros',                                                   'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '105A'],
            ['posicao' => 169, 'descricao' => 'Máquinas, equipamentos e instalações (exclusive processamento de dados) - Aquisições de Terceiros', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '106'],
            ['posicao' => 170, 'descricao' => 'Microcomputadores e equipamentos de Telecomunicações - Aquisições de Terceiros',          'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '106A'],
            ['posicao' => 171, 'descricao' => 'Meios de transporte - Aquisições de Terceiros',                                           'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '107'],
            ['posicao' => 172, 'descricao' => 'Outros (móveis e utensílios etc) - Aquisições de Terceiros',                              'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '108'],

            // ===================================================================
            // BLOCO 24 — BAIXAS DO ATIVO IMOBILIZADO (posições 173-178)
            // ===================================================================
            ['posicao' => 173, 'descricao' => 'Terrenos - Baixas',                                                                     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '110'],
            ['posicao' => 174, 'descricao' => 'Edificações - Baixas',                                                                  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '110A'],
            ['posicao' => 175, 'descricao' => 'Máquinas, equipamentos e instalações (exclusive processamento de dados) - Baixas',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '111'],
            ['posicao' => 176, 'descricao' => 'Microcomputadores e equipamentos de Telecomunicações - Baixas',                          'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '111A'],
            ['posicao' => 177, 'descricao' => 'Meios de transporte - Baixas',                                                           'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '112'],
            ['posicao' => 178, 'descricao' => 'Outros (móveis e utensílios etc) - Baixas',                                              'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '113'],

            // ===================================================================
            // BLOCO 25 — PRODUÇÃO PRÓPRIA E MELHORIAS (posições 179-188)
            // ===================================================================
            ['posicao' => 179, 'descricao' => 'Edificações - Produção própria realizada no ativo imobilizado',                          'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '248'],
            ['posicao' => 180, 'descricao' => 'Máquinas, equipamentos e instalações (exclusive processamento de dados) - Produção própria realizada no ativo imobilizado', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '249'],
            ['posicao' => 181, 'descricao' => 'Microcomputadores e equipamentos de Telecomunicações - Produção própria realizada no ativo imobilizado', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '250'],
            ['posicao' => 182, 'descricao' => 'Outros (móveis e utensílios etc) - Produção própria realizada no ativo imobilizado',     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '251'],
            ['posicao' => 183, 'descricao' => 'Terrenos - Melhorias',                                                                  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '253'],
            ['posicao' => 184, 'descricao' => 'Edificações - Melhorias',                                                               'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '254'],
            ['posicao' => 185, 'descricao' => 'Máquinas, equipamentos e instalações (exclusive processamento de dados) - Melhorias',    'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '255'],
            ['posicao' => 186, 'descricao' => 'Microcomputadores e equipamentos de Telecomunicações - Melhorias',                       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '256'],
            ['posicao' => 187, 'descricao' => 'Meios de transporte - Melhorias',                                                        'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '257'],
            ['posicao' => 188, 'descricao' => 'Outros (móveis e utensílios etc) - Melhorias',                                           'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '258'],

            // ===================================================================
            // BLOCO 26 — ATIVO IMOBILIZADO TOTAL (posições 189-190)
            // ===================================================================
            ['posicao' => 189, 'descricao' => 'Total do Ativo Imobilizado <Ano Anterior>',                                              'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '260'],
            ['posicao' => 190, 'descricao' => 'Total do Ativo Imobilizado <Ano Atual>',                                                 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '261'],

            // ===================================================================
            // BLOCO 27 — FORMA DE REALIZAÇÃO DAS VENDAS (posições 191-198)
            // ===================================================================
            ['posicao' => 191, 'descricao' => 'Lojas (vendas no balcão), estabelecimentos, postos de combustíveis, boxes em mercados, depósitos, galpões, armazéns e salas (Percentual)', 'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '115'],
            ['posicao' => 192, 'descricao' => 'Quiosques, trailers e outros estabelecimentos situados em local fixo fora da loja (Percentual)', 'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '116'],
            ['posicao' => 193, 'descricao' => 'Correio (Percentual)',                                                                  'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '117'],
            ['posicao' => 194, 'descricao' => 'Porta a porta (domicílio), postos móveis ou por ambulantes (Percentual)',                'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '118'],
            ['posicao' => 195, 'descricao' => 'Internet (Percentual)',                                                                 'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '119'],
            ['posicao' => 196, 'descricao' => 'Televendas (Percentual)',                                                               'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '120'],
            ['posicao' => 197, 'descricao' => 'Outros (especificar): (Percentual)',                                                     'tamanho' => 3, 'tipo' => 'numerico', 'cod_variavel' => '121'],
            ['posicao' => 198, 'descricao' => 'Especificar item 121',                                                                  'tamanho' => 50,'tipo' => 'texto', 'cod_variavel' => '121A1'],

            // ===================================================================
            // BLOCO 28 — REGIONALIZAÇÃO: PESSOAL OCUPADO (posições 199-225) - 27 UFs
            // ===================================================================
            ['posicao' => 199, 'descricao' => 'Rondônia - (Pessoal Ocupado em 31/12/XXXX)',                                            'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '123'],
            ['posicao' => 200, 'descricao' => 'Acre - (Pessoal Ocupado em 31/12/XXXX)',                                                'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '124'],
            ['posicao' => 201, 'descricao' => 'Amazonas - (Pessoal Ocupado em 31/12/XXXX)',                                            'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '125'],
            ['posicao' => 202, 'descricao' => 'Roraima - (Pessoal Ocupado em 31/12/XXXX)',                                             'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '126'],
            ['posicao' => 203, 'descricao' => 'Pará - (Pessoal Ocupado em 31/12/XXXX)',                                                'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '127'],
            ['posicao' => 204, 'descricao' => 'Amapá - (Pessoal Ocupado em 31/12/XXXX)',                                               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '128'],
            ['posicao' => 205, 'descricao' => 'Tocantins - (Pessoal Ocupado em 31/12/XXXX)',                                           'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '129'],
            ['posicao' => 206, 'descricao' => 'Maranhão - (Pessoal Ocupado em 31/12/XXXX)',                                            'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '130'],
            ['posicao' => 207, 'descricao' => 'Piauí - (Pessoal Ocupado em 31/12/XXXX)',                                               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '131'],
            ['posicao' => 208, 'descricao' => 'Ceará - (Pessoal Ocupado em 31/12/XXXX)',                                               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '132'],
            ['posicao' => 209, 'descricao' => 'Rio Grande do Norte - (Pessoal Ocupado em 31/12/XXXX)',                                 'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '133'],
            ['posicao' => 210, 'descricao' => 'Paraíba - (Pessoal Ocupado em 31/12/XXXX)',                                             'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '134'],
            ['posicao' => 211, 'descricao' => 'Pernambuco - (Pessoal Ocupado em 31/12/XXXX)',                                          'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '135'],
            ['posicao' => 212, 'descricao' => 'Alagoas - (Pessoal Ocupado em 31/12/XXXX)',                                             'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '136'],
            ['posicao' => 213, 'descricao' => 'Sergipe - (Pessoal Ocupado em 31/12/XXXX)',                                             'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '137'],
            ['posicao' => 214, 'descricao' => 'Bahia - (Pessoal Ocupado em 31/12/XXXX)',                                               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '138'],
            ['posicao' => 215, 'descricao' => 'Minas Gerais - (Pessoal Ocupado em 31/12/XXXX)',                                        'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '139'],
            ['posicao' => 216, 'descricao' => 'Espírito Santo - (Pessoal Ocupado em 31/12/XXXX)',                                      'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '140'],
            ['posicao' => 217, 'descricao' => 'Rio de Janeiro - (Pessoal Ocupado em 31/12/XXXX)',                                      'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '141'],
            ['posicao' => 218, 'descricao' => 'São Paulo - (Pessoal Ocupado em 31/12/XXXX)',                                           'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '142'],
            ['posicao' => 219, 'descricao' => 'Paraná - (Pessoal Ocupado em 31/12/XXXX)',                                              'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '143'],
            ['posicao' => 220, 'descricao' => 'Santa Catarina - (Pessoal Ocupado em 31/12/XXXX)',                                      'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '144'],
            ['posicao' => 221, 'descricao' => 'Rio Grande do Sul - (Pessoal Ocupado em 31/12/XXXX)',                                   'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '145'],
            ['posicao' => 222, 'descricao' => 'Mato Grosso do Sul - (Pessoal Ocupado em 31/12/XXXX)',                                  'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '146'],
            ['posicao' => 223, 'descricao' => 'Mato Grosso - (Pessoal Ocupado em 31/12/XXXX)',                                         'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '147'],
            ['posicao' => 224, 'descricao' => 'Goiás - (Pessoal Ocupado em 31/12/XXXX)',                                               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '148'],
            ['posicao' => 225, 'descricao' => 'Distrito Federal - (Pessoal Ocupado em 31/12/XXXX)',                                    'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '149'],

            // ===================================================================
            // BLOCO 29 — REGIONALIZAÇÃO: SALÁRIOS (posições 226-252) - 27 UFs
            // ===================================================================
            ['posicao' => 226, 'descricao' => 'Rondônia - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',      'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '151'],
            ['posicao' => 227, 'descricao' => 'Acre - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',          'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '152'],
            ['posicao' => 228, 'descricao' => 'Amazonas - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',      'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '153'],
            ['posicao' => 229, 'descricao' => 'Roraima - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '154'],
            ['posicao' => 230, 'descricao' => 'Pará - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',          'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '155'],
            ['posicao' => 231, 'descricao' => 'Amapá - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',         'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '156'],
            ['posicao' => 232, 'descricao' => 'Tocantins - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '157'],
            ['posicao' => 233, 'descricao' => 'Maranhão - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',      'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '158'],
            ['posicao' => 234, 'descricao' => 'Piauí - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',         'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '159'],
            ['posicao' => 235, 'descricao' => 'Ceará - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',         'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '160'],
            ['posicao' => 236, 'descricao' => 'Rio Grande do Norte - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '161'],
            ['posicao' => 237, 'descricao' => 'Paraíba - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '162'],
            ['posicao' => 238, 'descricao' => 'Pernambuco - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',    'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '163'],
            ['posicao' => 239, 'descricao' => 'Alagoas - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '164'],
            ['posicao' => 240, 'descricao' => 'Sergipe - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '165'],
            ['posicao' => 241, 'descricao' => 'Bahia - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',         'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '166'],
            ['posicao' => 242, 'descricao' => 'Minas Gerais - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '167'],
            ['posicao' => 243, 'descricao' => 'Espírito Santo - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '168'],
            ['posicao' => 244, 'descricao' => 'Rio de Janeiro - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '169'],
            ['posicao' => 245, 'descricao' => 'São Paulo - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '170'],
            ['posicao' => 246, 'descricao' => 'Paraná - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',        'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '171'],
            ['posicao' => 247, 'descricao' => 'Santa Catarina - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '172'],
            ['posicao' => 248, 'descricao' => 'Rio Grande do Sul - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '173'],
            ['posicao' => 249, 'descricao' => 'Mato Grosso do Sul - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '174'],
            ['posicao' => 250, 'descricao' => 'Mato Grosso - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',   'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '175'],
            ['posicao' => 251, 'descricao' => 'Goiás - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)',         'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '176'],
            ['posicao' => 252, 'descricao' => 'Distrito Federal - (Salários, Retiradas e Outras Remunerações no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '177'],

            // ===================================================================
            // BLOCO 30 — REGIONALIZAÇÃO: UNIDADES LOCAIS (posições 253-279) - 27 UFs
            // ===================================================================
            ['posicao' => 253, 'descricao' => 'Rondônia - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',            'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '179'],
            ['posicao' => 254, 'descricao' => 'Acre - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',                'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '180'],
            ['posicao' => 255, 'descricao' => 'Amazonas - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',            'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '181'],
            ['posicao' => 256, 'descricao' => 'Roraima - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',             'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '182'],
            ['posicao' => 257, 'descricao' => 'Pará - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',                'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '183'],
            ['posicao' => 258, 'descricao' => 'Amapá - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '184'],
            ['posicao' => 259, 'descricao' => 'Tocantins - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',           'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '185'],
            ['posicao' => 260, 'descricao' => 'Maranhão - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',            'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '186'],
            ['posicao' => 261, 'descricao' => 'Piauí - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '187'],
            ['posicao' => 262, 'descricao' => 'Ceará - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '188'],
            ['posicao' => 263, 'descricao' => 'Rio Grande do Norte - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)', 'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '189'],
            ['posicao' => 264, 'descricao' => 'Paraíba - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',             'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '190'],
            ['posicao' => 265, 'descricao' => 'Pernambuco - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',          'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '191'],
            ['posicao' => 266, 'descricao' => 'Alagoas - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',             'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '192'],
            ['posicao' => 267, 'descricao' => 'Sergipe - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',             'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '193'],
            ['posicao' => 268, 'descricao' => 'Bahia - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '194'],
            ['posicao' => 269, 'descricao' => 'Minas Gerais - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',        'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '195'],
            ['posicao' => 270, 'descricao' => 'Espírito Santo - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',      'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '196'],
            ['posicao' => 271, 'descricao' => 'Rio de Janeiro - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',      'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '197'],
            ['posicao' => 272, 'descricao' => 'São Paulo - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',           'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '198'],
            ['posicao' => 273, 'descricao' => 'Paraná - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',              'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '199'],
            ['posicao' => 274, 'descricao' => 'Santa Catarina - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',      'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '200'],
            ['posicao' => 275, 'descricao' => 'Rio Grande do Sul - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',   'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '201'],
            ['posicao' => 276, 'descricao' => 'Mato Grosso do Sul - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',  'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '202'],
            ['posicao' => 277, 'descricao' => 'Mato Grosso - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',         'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '203'],
            ['posicao' => 278, 'descricao' => 'Goiás - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',               'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '204'],
            ['posicao' => 279, 'descricao' => 'Distrito Federal - (Unidades Locais com Receita de Revenda ou de Repres. Comercial)',    'tamanho' => 5, 'tipo' => 'numerico', 'cod_variavel' => '205'],

            // ===================================================================
            // BLOCO 31 — REGIONALIZAÇÃO: RECEITA (posições 280-306) - 27 UFs
            // ===================================================================
            ['posicao' => 280, 'descricao' => 'Rondônia - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',    'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '207'],
            ['posicao' => 281, 'descricao' => 'Acre - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',        'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '208'],
            ['posicao' => 282, 'descricao' => 'Amazonas - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',    'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '209'],
            ['posicao' => 283, 'descricao' => 'Roraima - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '210'],
            ['posicao' => 284, 'descricao' => 'Pará - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',        'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '211'],
            ['posicao' => 285, 'descricao' => 'Amapá - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '212'],
            ['posicao' => 286, 'descricao' => 'Tocantins - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',   'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '213'],
            ['posicao' => 287, 'descricao' => 'Maranhão - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',    'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '214'],
            ['posicao' => 288, 'descricao' => 'Piauí - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '215'],
            ['posicao' => 289, 'descricao' => 'Ceará - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '216'],
            ['posicao' => 290, 'descricao' => 'Rio Grande do Norte - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '217'],
            ['posicao' => 291, 'descricao' => 'Paraíba - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '218'],
            ['posicao' => 292, 'descricao' => 'Pernambuco - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '219'],
            ['posicao' => 293, 'descricao' => 'Alagoas - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '220'],
            ['posicao' => 294, 'descricao' => 'Sergipe - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '221'],
            ['posicao' => 295, 'descricao' => 'Bahia - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',       'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '222'],
            ['posicao' => 296, 'descricao' => 'Minas Gerais - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)','tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '223'],
            ['posicao' => 297, 'descricao' => 'Espírito Santo - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '224'],
            ['posicao' => 298, 'descricao' => 'Rio de Janeiro - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '225'],
            ['posicao' => 299, 'descricao' => 'São Paulo - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',     'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '226'],
            ['posicao' => 300, 'descricao' => 'Paraná - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',        'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '227'],
            ['posicao' => 301, 'descricao' => 'Santa Catarina - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '228'],
            ['posicao' => 302, 'descricao' => 'Rio Grande do Sul - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '229'],
            ['posicao' => 303, 'descricao' => 'Mato Grosso do Sul - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '230'],
            ['posicao' => 304, 'descricao' => 'Mato Grosso - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',  'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '231'],
            ['posicao' => 305, 'descricao' => 'Goiás - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)',        'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '232'],
            ['posicao' => 306, 'descricao' => 'Distrito Federal - (Receita Bruta de Revenda ou de Representação Comercial no Ano - Valores em Reais)', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '233'],

            // ===================================================================
            // BLOCO 32 — OUTRAS INFORMAÇÕES (posições 307-308)
            // ===================================================================
            ['posicao' => 307, 'descricao' => '02A - Outras Formas de Ligação entre Empresas',           'tamanho' => 2,  'tipo' => 'texto', 'cod_variavel' => ''],
            ['posicao' => 308, 'descricao' => '098A - Depreciação e amortização de ativos decorrentes de arrendamento operacional', 'tamanho' => 12, 'tipo' => 'numerico', 'cod_variavel' => '098A'],
        ];
    }

    /**
     * Retorna o código da variável para uma determinada descrição
     */
    public static function getCodigoVariavel(string $descricao, string $ano = self::ANO_PADRAO): string {
        $descricao = str_replace('XXXX', $ano, $descricao);
        $posicoes = self::gerar($ano);
        foreach ($posicoes as $p) {
            if ($p['descricao'] === $descricao) {
                return $p['cod_variavel'] ?? '';
            }
        }
        return '';
    }
}
