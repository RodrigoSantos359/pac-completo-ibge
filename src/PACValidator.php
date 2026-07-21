<?php
/**
 * PACValidator.php - Validação e mapeamento para o PAC Completo IBGE
 * 
 * Mapeamento atualizado conforme layout oficial de 308 posições (Manual páginas 24-37).
 */

class PACValidator {
    private const UFS_VALIDAS = [
        'AC','AL','AP','AM','BA','CE','DF','ES','GO',
        'MA','MT','MS','MG','PA','PB','PR','PE','PI',
        'RJ','RN','RS','RO','RR','SC','SP','SE','TO',
    ];

    /**
     * MAPEAMENTO DIRETO: chave interna → descrição da posição no layout oficial (308 posições)
     */
    private const MAPEAMENTO_DIRETO = [
        // BLOCO 1 - SEDE (posições 1-22)
        'cnpj_raiz' => 'Raiz CNPJ da Sede',
        'cnpj_sufixo' => 'Sufixo Sede',
        'cnpj_dv' => 'Dígito Verificador da Sede',
        'razao_social' => 'Razão Social',
        'nome_fantasia' => 'Nome Fantasia',
        'site_empresa' => 'Site da Empresa',
        'uf_sede' => 'Sigla UF (SEDE)',
        'municipio_sede' => 'Município (Nome do Município da Sede da Empresa)',
        'distrito_sede' => 'Nome do Distrito (SEDE)',
        'logradouro_sede_tipo' => 'Tipo de Logradouro (SEDE) (rua, avenida, rodovia, etc..)',
        'logradouro_sede_nome' => 'Nome do Logradouro (SEDE)',
        'logradouro_sede_numero' => 'Número (SEDE)',
        'logradouro_sede_comp' => 'Complemento (SEDE)',
        'bairro_sede' => 'Bairro/Distrito/Localidade (SEDE)',
        'cep_sede' => 'CEP (SEDE)',
        'ddd_sede' => 'DDD (SEDE)',
        'telefone_sede' => 'Telefone (SEDE)',
        'ramal_sede' => 'Ramal (SEDE)',
        'email_empresa' => 'E-mail da empresa',
        'descricao_atividade' => 'Descrição da principal atividade(maior receita) da empresa',
        'cnae_principal' => 'Código da CNAE 2.0',
        'cnae_dv' => 'Dígito Verificador do Código da CNAE 2.0',

        // BLOCO 2 - UNIDADE DE COLETA (posições 23-36)
        'uc_sufixo' => 'Sufixo da Unidade de Coleta',
        'uc_dv' => 'DV da Unidade de Coleta',
        'uc_uf' => 'Sigla UF (UNIDADE DE COLETA)',
        'uc_municipio' => 'Município (Nome do Município da UC da Empresa)',
        'uc_distrito' => 'Nome do Distrito (UNIDADE DE COLETA)',
        'uc_logradouro_tipo' => 'Tipo de Logradouro (UNIDADE DE COLETA) (rua, avenida, rodovia, etc..)',
        'uc_logradouro_nome' => 'Nome do Logradouro (UNIDADE DE COLETA)',
        'uc_logradouro_numero' => 'Número (UNIDADE DE COLETA)',
        'uc_complemento' => 'Complemento (UNIDADE DE COLETA)',
        'uc_bairro' => 'Bairro/Distrito/Localidade (UNIDADE DE COLETA)',
        'uc_cep' => 'CEP (UNIDADE DE COLETA)',
        'uc_ddd' => 'DDD (UNIDADE DE COLETA)',
        'uc_telefone' => 'Telefone (UNIDADE DE COLETA)',
        'uc_ramal' => 'Ramal (UNIDADE DE COLETA)',

        // BLOCO 3 - RESPONSÁVEL (posições 37-42)
        'responsavel_nome' => 'Nome (Dados do responsável pelo preenchimento)',
        'responsavel_cargo' => 'Cargo (Dados do responsável pelo preenchimento)',
        'responsavel_ddd' => 'DDD (Dados do responsável pelo preenchimento)',
        'responsavel_telefone' => 'Telefone (Dados do responsável pelo preenchimento)',
        'responsavel_ramal' => 'Ramal (Dados do responsável pelo preenchimento)',
        'responsavel_email' => 'E-mail (Dados do responsável pelo preenchimento)',

        // BLOCO 4 - SITUAÇÃO (posições 43-44)
        'situacao_cadastral' => '01. Situação Cadastral em 31/12/XXXX',
        'mudanca_estrutural' => '02. Mudanças na Estrutura da Empresa no Ano de XXXX',

        // BLOCO 5 - DATA OCORRÊNCIA (posições 45-46)
        'data_ocorrencia_mes' => '01 – Mês: 01A. Data da Ocorrência para os Itens 03 e 04 da Situação Cadastral',
        'data_ocorrencia_ano' => '02 – Ano: 01A. Data da Ocorrência para os Itens 03 e 04 da Situação Cadastral',

        // BLOCO 5 - LIGAÇÃO 1 (posições 47-49)
        'ligacao1_cnpj_raiz' => '03. Raiz Primeiro CNPJ de Ligação da Empresa',
        'ligacao1_cnpj_sufixo' => '03. Sufixo Primeiro CNPJ de Ligação da Empresa',
        'ligacao1_cnpj_dv' => '03. Dígito Verificador Primeiro CNPJ de Ligação da Empresa',

        // LIGAÇÃO 2 (posições 50-52)
        'ligacao2_cnpj_raiz' => '03. Raiz Segundo CNPJ de Ligação da Empresa',
        'ligacao2_cnpj_sufixo' => '03. Sufixo Segundo CNPJ de Ligação da Empresa',
        'ligacao2_cnpj_dv' => '03. Dígito Verificador Segundo CNPJ de Ligação da Empresa',

        // LIGAÇÃO 3 (posições 53-55)
        'ligacao3_cnpj_raiz' => '03. Raiz Terceiro CNPJ de Ligação da Empresa',
        'ligacao3_cnpj_sufixo' => '03. Sufixo Terceiro CNPJ de Ligação da Empresa',
        'ligacao3_cnpj_dv' => '03. Dígito Verificador Terceiro CNPJ de Ligação da Empresa',

        // BLOCO 6 - MERCADORIAS (posições 56-58)
        'mercadoria_item_1' => 'Item 1: 04 - Principais Mercadorias Revendidas por Ordem de Predominância na Receita, inclusive por Representação Comercial',
        'mercadoria_item_2' => 'Item 2: 04 - Principais Mercadorias Revendidas por Ordem de Predominância na Receita, inclusive por Representação Comercial',
        'mercadoria_item_3' => 'Item 3: 04 - Principais Mercadorias Revendidas por Ordem de Predominância na Receita, inclusive por Representação Comercial',

        // BLOCO 7 - TRIBUTAÇÃO (posição 59)
        'forma_tributacao' => '05. Forma de Tributação Utilizada pela Empresa',

        // BLOCO 8 - ENDEREÇO / ALTERAÇÕES (posições 60-65)
        'endereco_uc_diferente' => 'O endereço atual da UNIDADE DE COLETA é diferente do informado no ano anterior?',
        'endereco_sede_diferente' => 'O endereço atual da SEDE da empresa é diferente do informado no ano anterior?',
        'razao_social_diferente' => 'A Razão Social informada é diferente da informada no ano anterior?',
        'razao_social_anterior' => 'Razão Social anterior',
        'motivo_alteracao' => 'Motivo da alteração',
        'atividade_anterior' => 'Atividade anterior da empresa',

        // BLOCO 9 - PESSOAL OCUPADO (posições 66-81)
        'pessoal_assalariado_comercial_marco' => 'Pessoal Assalariado - Ligado a atividade comercial ou de representação - Em 31/03',
        'pessoal_assalariado_nao_comercial_marco' => 'Pessoal Assalariado - Não ligado a atividade comercial - Em 31/03',
        'pessoal_nao_assalariado_socios_marco' => 'Pessoal Não Assalariado (Proprietário e sócios com atividade na empresa) - Em 31/03',
        'pessoal_nao_assalariado_familia_marco' => 'Pessoal Não Assalariado (Membros da Família sem remuneração) - Em 31/03',
        'pessoal_assalariado_comercial_junho' => 'Pessoal Assalariado - Ligado a atividade comercial ou de representação - Em 30/06',
        'pessoal_assalariado_nao_comercial_junho' => 'Pessoal Assalariado - Não ligado a atividade comercial - Em 30/06',
        'pessoal_nao_assalariado_socios_junho' => 'Pessoal Não Assalariado (Proprietário e sócios com atividade na empresa) - Em 30/06',
        'pessoal_nao_assalariado_familia_junho' => 'Pessoal Não Assalariado (Membros da Família sem remuneração) - Em 30/06',
        'pessoal_assalariado_comercial_setembro' => 'Pessoal Assalariado - Ligado a atividade comercial ou de representação - Em 30/09',
        'pessoal_assalariado_nao_comercial_setembro' => 'Pessoal Assalariado - Não ligado a atividade comercial - Em 30/09',
        'pessoal_nao_assalariado_socios_setembro' => 'Pessoal Não Assalariado (Proprietário e sócios com atividade na empresa) - Em 30/09',
        'pessoal_nao_assalariado_familia_setembro' => 'Pessoal Não Assalariado (Membros da Família sem remuneração) - Em 30/09',
        'pessoal_assalariado_comercial_dezembro' => 'Pessoal Assalariado - Ligado a atividade comercial ou de representação - Em 31/12',
        'pessoal_assalariado_nao_comercial_dezembro' => 'Pessoal Assalariado - Não ligado a atividade comercial - Em 31/12',
        'pessoal_nao_assalariado_socios_dezembro' => 'Pessoal Não Assalariado (Proprietário e sócios com atividade na empresa) - Em 31/12',
        'pessoal_nao_assalariado_familia_dezembro' => 'Pessoal Não Assalariado (Membros da Família sem remuneração) - Em 31/12',

        // BLOCO 10 - RECEITAS (posições 82-91)
        'receita_revenda' => 'Revenda de mercadorias (no caso de cooperativa, inclua as vendas a cooperados e de mercadorias de cooperados) - Receita Bruta',
        'comissoes_vendas' => 'Comissões sobre vendas de representação comercial e agentes de comércio',
        'receita_venda_produtos' => 'Venda de produtos de fabricação própria - Receita Bruta',
        'receita_servicos_manutencao' => 'Serviços de manutenção e reparação (veículos, eletrodomésticos, computadores, telefones, relógios, etc.) - Receita Bruta',
        'receita_royalties' => 'Royalties de franquias - Receita Bruta',
        'receita_outras_atividades' => 'Outras atividades (lanchonetes, restaurantes, armazenagem, transporte, exploração de estacionamento, etc.) - Receita Bruta',
        'deducoes_vendas_canceladas' => 'Vendas canceladas, abatimentos e descontos incondicionais (-) - Deduções',
        'deducoes_icms' => 'ICMS sobre vendas (-) - Deduções',
        'deducoes_pis_pasep' => 'PIS/PASEP (-) - Deduções',
        'deducoes_outros_impostos' => 'Outros impostos e contribuições sobre vendas (IPI, ISS, SIMPLES NACIONAL, COFINS) (-) - Deduções',

        // BLOCO 11 - DEMAIS RECEITAS (posições 92-97)
        'receita_aluguel' => 'Aluguel de imóveis, máquinas e equipamentos',
        'receitas_financeiras' => 'Receitas financeiras (juros, descontos obtidos, etc.)',
        'variacoes_monetarias_ativas' => 'Variações monetárias ativas',
        'resultado_positivo_participacoes' => 'Resultado positivo em participações societárias e em sociedades em conta de participação',
        'demais_receitas_operacionais' => 'Demais receitas operacionais (recuperação de despesas, etc.)',
        'outras_receitas' => 'Outras receitas - lucro na alienação de bens do ativo imobilizado, etc.',

        // BLOCO 12 - RECEITA LÍQUIDA (posições 98-99)
        'receita_varejo' => 'Varejo (vendas ao público em geral)',
        'receita_atacado' => 'Atacado (exportações, vendas a instituições públicas, comerciais, indústrias, etc.)',

        // BLOCO 13 - DESTINO VENDAS (posições 100-103)
        'destino_exportacao' => 'Exportação direta - Percentual',
        'destino_outras_empresas' => 'Vendas a outras empresas - Percentual',
        'destino_orgaos_publicos' => 'Vendas para órgãos, empresas e instituições públicas - Percentual',
        'destino_publico_geral' => 'Vendas ao público em geral - Percentual',

        // BLOCO 14 - COMPRAS (posições 104-106)
        'compras_mercadorias' => 'Mercadorias para revenda (inclusive mercadorias de cooperados. Inclua os fretes e seguros sobre as compras e não inclua as devoluções e os impostos recuperáveis)',
        'compras_materias_primas' => 'Matérias-primas para fabricação própria (deve preencher o Código 022)',
        'compras_material_embalagem' => 'Material de embalagem e outros materiais (de reposição, peças, etc.)',

        // BLOCO 15 - ESTOQUES ANO ANTERIOR (posições 107-110)
        'estoque_mercadorias_anterior' => 'Mercadorias para revenda (inclusive mercadorias de cooperados) - Em 31/12/<Ano Anterior>',
        'estoque_produtos_anterior' => 'Produtos acabados e em elaboração (ver Código 022) - Em 31/12/<Ano Anterior>',
        'estoque_materias_primas_anterior' => 'Matérias-primas para fabricação própria (ver Código 022) - Em 31/12/<Ano Anterior>',
        'estoque_material_embalagem_anterior' => 'Material de embalagem e outros materiais (de reposição, peças, etc.) - Em 31/12/<Ano Anterior>',

        // ESTOQUES ANO ATUAL (posições 111-114)
        'estoque_mercadorias_atual' => 'Mercadorias para revenda (inclusive mercadorias de cooperados) - Em 31/12/<Ano Atual>',
        'estoque_produtos_atual' => 'Produtos acabados e em elaboração (ver Código 022) - Em 31/12/<Ano Atual>',
        'estoque_materias_primas_atual' => 'Matérias-primas para fabricação própria (ver Código 022) - Em 31/12/<Ano Atual>',
        'estoque_material_embalagem_atual' => 'Material de embalagem e outros materiais (de reposição, peças, etc.) - Em 31/12/<Ano Atual>',

        // BLOCO 16 - MÉTODO VALORAÇÃO (posições 115-116)
        'metodo_valoracao_estoques' => 'Método de Valoração dos estoques',
        'metodo_valoracao_outro' => 'Se responder o item 059A1 com "4 – Outro" é necessário discriminar',

        // BLOCO 17 - CUSTO (posição 117)
        'custo_mercadorias_revendidas' => 'Custo das mercadorias revendidas (valor contábil apurado e levado à DRE)',

        // BLOCO 18 - GASTOS PESSOAL (posições 118-126)
        'gastos_salarios' => 'Salários e outras remunerações (inclusive 13º salário, férias, horas extras, etc.) - não incluir bolsa paga a estagiários',
        'gastos_participacao_lucros' => 'Participação nos lucros (somente a parte distribuída aos empregados) e honorários da diretoria',
        'gastos_prolabore' => 'Retiradas pró-labore do proprietário ou sócios com atividade na empresa',
        'gastos_previdencia_social' => 'Contribuições para a Previdência Social (parte da empresa)',
        'gastos_fgts' => 'FGTS (se tiver empregados e não preencher, justificar em OBSERVAÇÕES)',
        'gastos_previdencia_privada' => 'Contribuições para a previdência privada (parte da empresa)',
        'gastos_indenizacoes' => 'Indenizações trabalhistas, rescisões contratuais e por dispensa incentivada - inclusive os 50% do FGTS',
        'gastos_beneficios' => 'Benefícios concedidos aos empregados (transporte, alimentação, auxílio-educação, planos de saúde, etc.)',
        'gastos_pis_folha' => 'PIS sobre folha de pagamento (somente se for contribuinte nesta modalidade)',

        // BLOCO 19 - DESPESAS OPERACIONAIS (posições 127-155)
        'despesa_alugueis_imoveis' => 'Aluguéis de imóveis',
        'despesa_alugueis_maquinas' => 'Aluguéis de máquinas, equipamentos e veículos',
        'despesa_condominios' => 'Condomínios - inclusive taxas de Administração de shopping, galerias, etc.',
        'despesa_comissoes' => 'Comissões pagas a representantes comerciais e outros intermediários',
        'despesa_publicidade' => 'Publicidade e propaganda (feiras, eventos, lançamento de produtos, promoções de vendas, material promocional e marketing)',
        'despesa_combustiveis' => 'Combustíveis, peças e acessórios consumidos nos meios de transporte próprios',
        'despesa_servicos_autonomos' => 'Serviços prestados por profissionais liberais ou autônomos (pessoas físicas)',
        'despesa_servicos_tecnicos' => 'Serviços técnico-profissionais (serviços jurídicos, contabilidade, auditoria, consultoria, informática, etc.) - Serviços prestados por empresas',
        'despesa_vigilancia' => 'Vigilância, segurança e transporte de valores - Serviços prestados por empresas',
        'despesa_fretes' => 'Fretes e carretos (não inclua os fretes sobre as compras) - Serviços prestados por empresas',
        'despesa_mao_obra_temporaria' => 'Mão-de-obra contratada temporariamente junto a empresas locadoras de mão-de-obra',
        'despesa_manutencao' => 'Manutenção e reparação de imóveis, instalações, máquinas, veículos e equipamentos',
        'despesa_outros_servicos' => 'Outros serviços prestados por empresas (limpeza, zeladoria, portaria, dedetização, cobranças, etc.)',
        'despesa_comunicacao' => 'Serviços de comunicação (correio, fax, telefone e internet)',
        'despesa_energia' => 'Energia elétrica, gás, água e esgoto',
        'despesa_armazenagem' => 'Armazenagem, despesas portuárias, aeroportuárias e despesas com importação e exportação',
        'despesa_seguros' => 'Prêmios de seguros (imóveis, veículos, mercadorias, etc.)',
        'despesa_impostos_taxas' => 'Impostos e taxas (IPTU, IPVA, IOF, alvarás, etc). Não incluir ICMS, ISS, COFINS, PIS, IPI, SIMPLES NACIONAL, etc.',
        'despesa_viagens' => 'Viagens e representações (inclusive diárias e estadias)',
        'despesa_material_expediente' => 'Material de expediente, de uso, de consumo, de escritório e de limpeza',
        'despesa_royalties' => 'Royalties pelo uso de marcas, patentes e franquias',
        'despesa_pis_cofins' => 'PIS/COFINS sobre outras receitas',
        'despesa_demais_operacionais' => 'Demais despesas operacionais - não inclua despesas financeiras, depreciação, provisão para IRPJ e CSLL, pagamento de IRPJ e CSLL e constituição das demais provisões',
        'despesa_discrimine_item1' => 'Item 1: Discrimine os principais valores do código 090 quando ultrapassar 30% do total',
        'despesa_discrimine_valor1' => 'Valor 1: Discrimine os principais valores do código 090 quando ultrapassar 30% do total',
        'despesa_discrimine_item2' => 'Item 2: Discrimine os principais valores do código 090 quando ultrapassar 30% do total',
        'despesa_discrimine_valor2' => 'Valor 2: Discrimine os principais valores do código 090 quando ultrapassar 30% do total',
        'despesa_discrimine_item3' => 'Item 3: Discrimine os principais valores do código 090 quando ultrapassar 30% do total',
        'despesa_discrimine_valor3' => 'Valor 3: Discrimine os principais valores do código 090 quando ultrapassar 30% do total',

        // BLOCO 20 - DESPESAS FINANCEIRAS (posições 156-160)
        'despesa_leasing' => 'Despesas com arrendamento mercantil financeiro (leasing financeiro) de máquinas, equipamentos e veículos',
        'despesa_financeiras' => 'Despesas financeiras - inclusive factoring, taxa de juros de longo prazo e despesas bancárias',
        'variacoes_monetarias_passivas' => 'Variações monetárias passivas',
        'despesa_comissoes_cartao' => 'Comissões pagas a administradoras de cartão de crédito',
        'resultado_negativo_participacoes' => 'Resultado negativo em participações societárias e em sociedades em conta de participação',

        // BLOCO 21 - DEPRECIAÇÃO / PROVISÕES (posições 161-164)
        'despesa_depreciacao' => 'Depreciação e amortização (valores relativos ao ano - não inclua depreciação e amortização acumulada)',
        'despesa_outras' => 'Outras despesas (impairment, prejuízo na alienação de bens do ativo imobilizado, perdas no recebimento de crédito, perdas de estoque, etc.)',
        'provisao_irpj_csll' => 'Provisão para IRPJ e CSLL (não inclua constituição de ativo fiscal de IRPJ e CSLL)',
        'outras_provisoes' => 'Outras provisões constituídas (provisão para contingências, provisão para créditos de liquidação duvidosa, etc.)',

        // BLOCO 22 - RESULTADO (posições 165-166)
        'resultado_lucro' => 'Lucro: Preencher se (030 + 037) > [(049 + 054 - 059) + (069 + 091 + 097 + 102)]',
        'resultado_prejuizo' => 'Prejuízo: Preencher se (030 + 037) < [(049 + 054 - 059) + (069 + 091 + 097 + 102)]',

        // BLOCO 23 - AQUISIÇÕES (posições 167-172)
        'aquisicoes_terrenos' => 'Terrenos - Aquisições de Terceiros',
        'aquisicoes_edificacoes' => 'Edificações - Aquisições de Terceiros',
        'aquisicoes_maquinas' => 'Máquinas, equipamentos e instalações (exclusive processamento de dados) - Aquisições de Terceiros',
        'aquisicoes_microcomputadores' => 'Microcomputadores e equipamentos de Telecomunicações - Aquisições de Terceiros',
        'aquisicoes_transportes' => 'Meios de transporte - Aquisições de Terceiros',
        'aquisicoes_outros' => 'Outros (móveis e utensílios etc) - Aquisições de Terceiros',

        // BLOCO 24 - BAIXAS (posições 173-178)
        'baixas_terrenos' => 'Terrenos - Baixas',
        'baixas_edificacoes' => 'Edificações - Baixas',
        'baixas_maquinas' => 'Máquinas, equipamentos e instalações (exclusive processamento de dados) - Baixas',
        'baixas_microcomputadores' => 'Microcomputadores e equipamentos de Telecomunicações - Baixas',
        'baixas_transportes' => 'Meios de transporte - Baixas',
        'baixas_outros' => 'Outros (móveis e utensílios etc) - Baixas',

        // BLOCO 25 - PRODUÇÃO PRÓPRIA (posições 179-182)
        'prodpropria_edificacoes' => 'Edificações - Produção própria realizada no ativo imobilizado',
        'prodpropria_maquinas' => 'Máquinas, equipamentos e instalações (exclusive processamento de dados) - Produção própria realizada no ativo imobilizado',
        'prodpropria_microcomputadores' => 'Microcomputadores e equipamentos de Telecomunicações - Produção própria realizada no ativo imobilizado',
        'prodpropria_outros' => 'Outros (móveis e utensílios etc) - Produção própria realizada no ativo imobilizado',

        // MELHORIAS (posições 183-188)
        'melhorias_terrenos' => 'Terrenos - Melhorias',
        'melhorias_edificacoes' => 'Edificações - Melhorias',
        'melhorias_maquinas' => 'Máquinas, equipamentos e instalações (exclusive processamento de dados) - Melhorias',
        'melhorias_microcomputadores' => 'Microcomputadores e equipamentos de Telecomunicações - Melhorias',
        'melhorias_transportes' => 'Meios de transporte - Melhorias',
        'melhorias_outros' => 'Outros (móveis e utensílios etc) - Melhorias',

        // BLOCO 26 - ATIVO IMOBILIZADO TOTAL (posições 189-190)
        'imobilizado_total_anterior' => 'Total do Ativo Imobilizado <Ano Anterior>',
        'imobilizado_total_atual' => 'Total do Ativo Imobilizado <Ano Atual>',

        // BLOCO 27 - FORMA DE VENDAS (posições 191-198)
        'vendas_lojas' => 'Lojas (vendas no balcão), estabelecimentos, postos de combustíveis, boxes em mercados, depósitos, galpões, armazéns e salas (Percentual)',
        'vendas_quiosques' => 'Quiosques, trailers e outros estabelecimentos situados em local fixo fora da loja (Percentual)',
        'vendas_correio' => 'Correio (Percentual)',
        'vendas_porta_porta' => 'Porta a porta (domicílio), postos móveis ou por ambulantes (Percentual)',
        'vendas_internet' => 'Internet (Percentual)',
        'vendas_televendas' => 'Televendas (Percentual)',
        'vendas_outros' => 'Outros (especificar): (Percentual)',
        'vendas_outros_especificar' => 'Especificar item 121',

        // BLOCO 28-31 - REGIONALIZAÇÃO: mantemos mapeamento genérico para UFs
        // Os campos de regionalização (199-306) são mapeados individualmente abaixo

        // BLOCO 32 - OUTRAS INFORMAÇÕES (posições 307-308)
        'outras_formas_ligacao' => '02A - Outras Formas de Ligação entre Empresas',
        'depreciacao_arrendamento_operacional' => '098A - Depreciação e amortização de ativos decorrentes de arrendamento operacional',
    ];

    /** Nomes das UFs em ordem alfabética para regionalização */

    /** Nomes das UFs em ordem alfabética para regionalização */
    private const UFS_ORDEM = [
        'RO' => 'Rondônia', 'AC' => 'Acre', 'AM' => 'Amazonas',
        'RR' => 'Roraima', 'PA' => 'Pará', 'AP' => 'Amapá',
        'TO' => 'Tocantins', 'MA' => 'Maranhão', 'PI' => 'Piauí',
        'CE' => 'Ceará', 'RN' => 'Rio Grande do Norte', 'PB' => 'Paraíba',
        'PE' => 'Pernambuco', 'AL' => 'Alagoas', 'SE' => 'Sergipe',
        'BA' => 'Bahia', 'MG' => 'Minas Gerais', 'ES' => 'Espírito Santo',
        'RJ' => 'Rio de Janeiro', 'SP' => 'São Paulo', 'PR' => 'Paraná',
        'SC' => 'Santa Catarina', 'RS' => 'Rio Grande do Sul',
        'MS' => 'Mato Grosso do Sul', 'MT' => 'Mato Grosso',
        'GO' => 'Goiás', 'DF' => 'Distrito Federal',
    ];

    /** Campos obrigatórios mínimos para importação */
    private const CAMPOS_OBRIGATORIOS = [
        'cnpj_raiz', 'cnpj_sufixo', 'cnpj_dv', 'razao_social',
        'uf_sede', 'municipio_sede', 'logradouro_sede_tipo',
        'logradouro_sede_nome', 'logradouro_sede_numero', 'bairro_sede',
        'cep_sede', 'ddd_sede', 'telefone_sede', 'descricao_atividade',
        'cnae_principal', 'responsavel_nome', 'responsavel_cargo',
        'situacao_cadastral', 'mudanca_estrutural', 'forma_tributacao',
    ];

    /** Lista das 16 chaves internas para pessoal ocupado (posições 66-81) */
    private const PESSOAL_CAMPOS = [
        'pessoal_assalariado_comercial_marco',
        'pessoal_assalariado_nao_comercial_marco',
        'pessoal_nao_assalariado_socios_marco',
        'pessoal_nao_assalariado_familia_marco',
        'pessoal_assalariado_comercial_junho',
        'pessoal_assalariado_nao_comercial_junho',
        'pessoal_nao_assalariado_socios_junho',
        'pessoal_nao_assalariado_familia_junho',
        'pessoal_assalariado_comercial_setembro',
        'pessoal_assalariado_nao_comercial_setembro',
        'pessoal_nao_assalariado_socios_setembro',
        'pessoal_nao_assalariado_familia_setembro',
        'pessoal_assalariado_comercial_dezembro',
        'pessoal_assalariado_nao_comercial_dezembro',
        'pessoal_nao_assalariado_socios_dezembro',
        'pessoal_nao_assalariado_familia_dezembro',
    ];

    /**
     * Retorna os 16 campos de pessoal para validação
     */
    public static function getPessoalCampos(): array {
        return self::PESSOAL_CAMPOS;
    }

    /**
     * Normaliza um valor de acordo com o tipo de campo esperado
     * Usa o tipo do layout oficial (numerico ou texto)
     */
    public static function normalizarValor($valor, string $tipoCampo = 'texto'): string {
        if ($valor === null || $valor === '') return '';
        $texto = trim((string)$valor);
        if ($texto === '') return '';

        return match ($tipoCampo) {
            'numerico' => preg_replace('/[^0-9]/', '', $texto) ?: '',
            'monetario' => self::normalizarMonetario($texto),
            'cnpj' => preg_replace('/[^0-9]/', '', $texto),
            'cep' => substr(preg_replace('/[^0-9]/', '', $texto), 0, 8),
            'telefone' => preg_replace('/[^0-9]/', '', $texto),
            'percentual' => self::normalizarPercentual($texto),
            default => self::normalizarTexto($texto),
        };
    }

    /**
     * Valida todos os campos de uma empresa
     */
    public static function validarEmpresa(array $empresa, string $ano = '2025'): array {
        $erros = [];
        $avisos = [];

        // 1. Campos obrigatórios
        foreach (self::CAMPOS_OBRIGATORIOS as $chave) {
            $valor = $empresa[$chave] ?? null;
            if ($valor === null || (is_string($valor) && trim($valor) === '')) {
                $erros[] = "Campo obrigatório ausente: '{$chave}'";
            }
        }

        // 2. CNPJ
        if (!empty($empresa['cnpj_raiz'])) {
            $raiz = preg_replace('/[^0-9]/', '', $empresa['cnpj_raiz']);
            if (strlen($raiz) !== 8) $erros[] = "Raiz CNPJ deve ter 8 dígitos";
        }
        if (!empty($empresa['cnpj_sufixo'])) {
            $s = preg_replace('/[^0-9]/', '', $empresa['cnpj_sufixo']);
            if (strlen($s) !== 4) $erros[] = "Sufixo CNPJ deve ter 4 dígitos";
        }
        if (!empty($empresa['cnpj_dv'])) {
            $d = preg_replace('/[^0-9]/', '', $empresa['cnpj_dv']);
            if (strlen($d) !== 2) $erros[] = "DV CNPJ deve ter 2 dígitos";
        }

        // 3. UF
        if (!empty($empresa['uf_sede'])) {
            $uf = strtoupper(trim($empresa['uf_sede']));
            if (!in_array($uf, self::UFS_VALIDAS, true)) {
                $erros[] = "UF inválida: '{$empresa['uf_sede']}'";
            }
        }

        // 4. CEP
        if (!empty($empresa['cep_sede'])) {
            $cep = preg_replace('/[^0-9]/', '', $empresa['cep_sede']);
            if (strlen($cep) !== 8) $erros[] = "CEP deve ter 8 dígitos";
        }

        // 5. Pessoal Ocupado (posições 66-81) - avisos, não bloqueia
        $pessoalVazios = 0;
        foreach (self::PESSOAL_CAMPOS as $chave) {
            $valor = $empresa[$chave] ?? null;
            if ($valor === null || (is_string($valor) && trim($valor) === '')) {
                $pessoalVazios++;
            }
        }
        if ($pessoalVazios > 0) {
            $totalPessoal = count(self::PESSOAL_CAMPOS);
            $avisos[] = "Pessoal ocupado (posições 66-81): {$pessoalVazios} de {$totalPessoal} campos não preenchidos. Informe os valores manualmente em cada trimestre.";
        }

        return [
            'valida' => empty($erros),
            'erros' => $erros,
            'avisos' => $avisos,
        ];
    }

    // =========================================================================
    // UTILITÁRIOS
    // =========================================================================

    private static function normalizarMonetario(string $texto): string {
        $texto = str_replace(['R$', '$'], '', $texto);
        $texto = trim($texto);
        $texto = str_replace('.', '', $texto);
        $texto = str_replace(',', '.', $texto);
        if (is_numeric($texto)) return number_format(round((float)$texto), 0, '', '');
        return preg_replace('/[^0-9]/', '', $texto) ?: '';
    }

    private static function normalizarPercentual(string $texto): string {
        $texto = str_replace('%', '', $texto);
        $texto = trim($texto);
        $texto = str_replace(',', '.', $texto);
        if (is_numeric($texto)) return (string)round((float)$texto);
        return preg_replace('/[^0-9]/', '', $texto) ?: '';
    }

    private static function normalizarTexto(string $texto): string {
        $texto = str_replace([";", "\n", "\r"], ' ', $texto);
        return preg_replace('/\s+/', ' ', $texto);
    }

    public static function getMapeamentoDireto(): array {
        return self::MAPEAMENTO_DIRETO;
    }

    public static function getCamposObrigatorios(): array {
        return self::CAMPOS_OBRIGATORIOS;
    }

    /**
     * Retorna os nomes completos das UFs em ordem alfabética
     */
    public static function getUFSOrdem(): array {
        return self::UFS_ORDEM;
    }
}
