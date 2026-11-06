<?php

/**
 * PAC Completo IBGE - Gerador Web (Vercel)
 * =========================================
 * 
 * Aplicação web para consulta de CNPJ via BrasilAPI,
 * integração com WK Radar e geração de CSV para o PAC.
 * Suporta múltiplos CNPJs em lote.
 * 
 * Local:  php -S localhost:8000 api/index.php
 * Vercel: vercel dev  ou  vercel --prod
 */

// Configura sessão para Vercel (usa /tmp que é gravável em serverless)
if (is_dir('/tmp') && is_writable('/tmp')) {
    session_save_path('/tmp');
}
session_start();

// Carrega classes
require_once __DIR__ . '/../src/PACLayout.php';
require_once __DIR__ . '/../src/PACValidator.php';
require_once __DIR__ . '/../src/BrasilAPI.php';
require_once __DIR__ . '/../src/WKRadar.php';
require_once __DIR__ . '/../src/PACGenerator.php';
require_once __DIR__ . '/../src/PACDadosEconomicos.php';

// =========================================================================
// ROTEAMENTO
// =========================================================================

$action = $_GET['action'] ?? 'home';

try {
    switch ($action) {
        case 'consultar':
            handleConsultarCNPJ();
            break;
        case 'editar':
            handleEditar();
            break;
        case 'salvar':
            handleSalvar();
            break;
        case 'testar_wk':
            handleTestarWK();
            break;
        case 'upload_economico':
            handleUploadEconomico();
            break;
        case 'get_economico_data':
            handleGetEconomicoData();
            break;
        case 'preview_csv':
            handlePreviewCSV();
            break;
        case 'limpar':
            handleLimpar();
            break;
        case 'remover_empresa':
            handleRemoverEmpresa();
            break;
        default:
            renderHome();
    }
} catch (Exception $e) {
    if (isAjax()) {
        jsonResponse(false, null, $e->getMessage());
    } else {
        renderHome('error', $e->getMessage());
    }
}

// =========================================================================
// HOME PAGE
// =========================================================================

function renderHome(string $tipoAlerta = '', string $mensagem = ''): void
{
    $totalEmpresas = count($_SESSION['empresas'] ?? []);
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- Open Graph / Social Meta Tags -->
        <meta property="og:title" content="PAC Completo IBGE - Gerador Multi-empresa">
        <meta property="og:description" content="Sistema web para gerar arquivos CSV do PAC (Pesquisa Anual de Comércio) do IBGE. Consulta CNPJs via BrasilAPI, integração com WK Radar e editor multi-empresa.">
        <meta property="og:image" content="https://pac-completo-ibge.vercel.app/assets/img/preview-home.png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:url" content="https://pac-completo-ibge.vercel.app">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="PAC Completo IBGE">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="PAC Completo IBGE - Gerador Multi-empresa">
        <meta name="twitter:description" content="Gere arquivos CSV do PAC IBGE com consulta automática de CNPJs.">
        <meta name="twitter:image" content="https://pac-completo-ibge.vercel.app/assets/img/preview-home.png">

        <title>PAC Completo IBGE - Gerador Multi-empresa</title>
        <link rel="stylesheet" href="/assets/style.css">
        <link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAAI0UlEQVR4nO2ZC2xb1R3GL9t4TOxRUgaDFIrSkubRkDShUR5O7NiJY8dpnLi9zsNO4tiOE9ehUQntmqart5CmLg0g6EBbi/YQG2gbLVCgHbTSNBW0KSuuBgOZwDRBVySmiTJgGr2l/U3HuSYhaxsDieOifNJf1vnOd75z75f/vY7vlaR5zGMe85jHPGYci3rwpPbwemoP76YG+OV3b+M7n9frunXcmNrD+kUBDqQGeCW1hxOpPby0KMDe1B46rw+wUEomLPZjuykAk2txgMgNAa7/LD7ixG4K8MDitZya6jfF+/3FAX6wuJ0rpGRAmp8nl/hh6VrW39xFapqfsBgv8RNZFmcIS9aSnebnDbEurZvTS/w8uqQL+9IusoRHWoCcpX48ad0cVL1J8/PHG31cJ801bu7iyfRuSO/GL8ZZHlLSuwgL7uau+EJI97MyvYuT6d38aVk3yy6kXeanJL2Lv8X8c/xcJc0lMnzYMn2Q4UPJ9FEfCyGjk7DgMzuJLHNPH0K2h8xYW6f7yMj08UCGj79m+nhb9dqeof7FMwIsjPlndPJEAk7z/Mh2cUO2l/8s74RsL8rySSEs7yQc5eMMQSDHiy/by2mx7hz1fo4Xq6pblN3JScFn+TBJcwFZ5qu5Hl7I80Kel7fEZ64HJdc9EUKel7A6H1kRRwh5nazM8/Bunofdt3ipXuEhM8+LNdfDkzH/W3xooloPfVHOy0FpLpDvprfADfkdHC/wcXW+myExLuhAye+gQWhy21mQ72Y0yrsZy20jdTrf813X+R3sUn1einkXdPBRvpuPC3x8W0okijykFLp4r7ADVnZQG+MLOwgJrtCFUtg+3gmqNqzykZI4OmHzC1gGjvCPzUc4vvkIZsEtvY3LC138U/gUuchX93tejG/toFJKJIpd/LDYFT2QZ6fOFbUTEnPF7SjFk0IochGOrmmfPoTdhzjx0GEQtecwb33i7eLRqLcLt3ocv1D3apUShQIfl2raeEfTBqWt49fjVGhaCYl5TSuKxjkRQmkb4Sjfdv4QNC1c9cReTh1+BqL19EQApa08LNaXteFVxz9V/TqkRKHcySpdK+haeflCOm0roajOiaJVQzB6SNE5CQte20qksunTIYiT1zo5etsd8OKvOD32W068vjd2l+cSrZM3o57tlApG5+SQGFe0UiMlCnoHuw0O0DsYmE5rcBASWkMLij4WgkyK3kFY5T8JQZy8wcFR1XtMP+WGWeGkJDrXwpviG8hk4nKDgw8MDs7qm7hWShSqWnjD2ALVDnRx6kNCb2xGMTZPhGBsISz4qmYiJgdZxhaORnUt/3/y0TXNtKn6vaqvSx2PSomCycG3zM2cNTdzusrJlfGuMzcRMjeDuQnFPCkEczNhwZuaOKN+jlnkc39VipDE3qZmTpmbecTUxEmxpqYxgTdAi0xBbSPUNl74+j8Xau2EomvtKLX2iRAsdsKq59laOysvuH8jmy12zqh6LI08FgzyFSlRqG+ktt4O9TJ/+DzrrXaGxHqrjFInj/+zZLWywCozGuXt5++ASceQaZVx1dkplxINm0zjahlWr+F3n9djtcyQ8LCtQWmYFMLqNYxGvWXG5GlCmDPIDayxrwbZ9sV+hdlthFQfxW4bvxxkmRTZRljwdhuRprrP9lAlIWixUdlsg6YGnv+iXk0NDKleSlPDeCe0W1nQbGNU5ZOvE5wNLHfWg8PKOzPh56gnpPopTutEJzjqCQveWZ9knSDLXOaq45TLCj55Zn6BuawMCb/2OpS2uolOaLcyGuWtjLVZkqgT3Ks46qkDdx2WmfL01BGKeq5CcdeOd4JHJsWzirDgPauIuJOlEzprCflqwVfLz2fS12dhSPVVvJaJTvBZGBV8Zy1jXcnQCV0WCv0W6K7hpLgkZtLbbyEkvP0WFH+sE4yk+C2EVT4SSIZOCJh5uacGempwzrR3j5mQ6q30mMdDWG8kJVBDWPCBmiQIYZ2J9l4TrDMR8RVw6Yz7VxMS/r0mlN5JIfRWE47tG6icwxCCWr7WZ+S1vmq4vZqNs7HH7dWEhH9fNcrtxokQ+oyEo/saiWyYyxA2GqnaWAUbqvjv9wysmI09NlQxJPbYWIVyR9X4jbFXy4INVYyq/Nh6/RzeGDdV8uP+SthkYKzf9OmXon1VXNNvYMumSl7sr+S9fgMfbjJwbFMld07VXgj9BkJij/5KlH79eCcEjaT0VxJW9z42G5dhXAhquWLAwNEtBtii58+bNOOPtLdUYtui519R/hw1oOffQhPfLpI0YCCkrlW2TAphQM+rqp8szRX69Vy7tYLXgxWwVccrQR1rgzo+FuOgjv1BLbq+Kq4U9X09FVsreErVnglWxB/CVh07o54VKEGtGoKOB1WvXmkuMaQndVDHsTt1EKtBLf3n0w/qGFB1J4Narp7Of7CC3EEtf5nkf3pQywuDWj6+U8sZMS/NNXaU8M1t5Rwd1sKwlv0XeskhsK2cp6LacoLn8xw2sHBbOcPDWk4J7TYtrw6Xc9cn43I+Gi5nnZQsCJVxbEcZbC+deGC65xDH94y/4GD3pJcc28vQC21Iw4tTfe4qIS+kYWSHhg+imjLO7ijjvruL+LqY31nMNaFSNCNxdE9CMVLKhyMa+JGWb8S4557m+HPPQLQmveQQHSO0Ixre31mKeaeG9SOl7N5Zyt9Vnp0azo5o2H93GYXSxYB7S0DUZC6yD/Nr+zj+2l7emnjJ8Wn91LqnhOP3FPPgvSXkSRcTdhWDqM+qv7+Yg7uKuG9XMYFdJdyKxCXSxYgHi0BUvLp49RcNflIIouLVxau/aPDQShA1W/qkx89uBVGzpU96PJwPoqabn1rSlwWPrABR081PLenLgl/ngqjZ0ic9HrsFRM2WPmnwmywu27ecHY/n8PbjOTCTtW85J/blEBJ7SMmK/dmEnsqGWa7tUrLimSxOHMiCp7MpmWnvA1lohLfYQ0pWPJsJoi5W/7hweBkkc0mzjd+nQzLXrAcwj3nMYx7zkL48+B/0+DymOYFzcQAAAABJRU5ErkJggg==">
    </head>

    <body>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div style="text-align:center">
                <div class="spinner"></div>
                <p id="loadingText" style="margin-top:1rem;color:var(--gray-600);font-weight:500;">Consultando BrasilAPI...</p>
            </div>
        </div>

        <!-- HEADER -->
        <header class="header">
            <div class="header-inner">
                <a href="?" class="header-brand">
                    <div class="header-brand-icon">PAC</div>
                    <div class="header-brand-text">
                        <h1>PAC Completo IBGE</h1>
                        <small>Gerador Multi-empresa</small>
                    </div>
                </a>
                <div class="header-actions">
                    <?php if ($totalEmpresas > 0): ?>
                        <a href="?action=editar" class="btn btn-success btn-sm">
                            <img width="20" height="20" src="https://img.icons8.com/nolan/64/edit--v1.png" alt="edit--v1"/>
                             Editar (<?= $totalEmpresas ?> empresa<?= $totalEmpresas > 1 ? 's' : '' ?>)
                        </a>
                        <a href="?action=limpar" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza? Todas as empresas serão removidas.')">
                            <img width="20" height="20" src="https://img.icons8.com/parakeet-line/48/delete.png" alt="delete"/>
                            Nova Consulta
                        </a>
                    <?php endif; ?>
                    <button type="button" id="toggleWkConfig" class="btn btn-secondary btn-sm">
                        <img width="20"height="20" src="https://img.icons8.com/nolan/64/settings--v1.png" alt="settings--v1"/>
                         WK Radar
                    </button>
                    <button type="button" id="toggleUploadConfig" class="btn btn-primary btn-sm">
                        <img width="20" height="20" src="https://img.icons8.com/ios/50/import-folder.png" alt="import-folder"/>
                         Dados Econômicos
                    </button>
                </div>
            </div>
        </header>

        <main class="container">

            <!-- WK Radar Config -->
            <div id="wkConfigFields" class="wk-config hidden">
                <div class="wk-config-header">
                    <img width="25" height="25" src="https://img.icons8.com/nolan/64/lock.png" alt="lock"/>
                    <h3>Configuração do WK Radar (API Contábil)</h3>
                    <span class="badge badge-info">Opcional</span>
                </div>
                <div class="wk-config-fields">
                    <div class="form-group">
                        <label class="form-label">Token da API WK Radar</label>
                        <input type="text" id="wk_token" class="form-input"
                            placeholder="Cole seu token aqui..."
                            value="<?= htmlspecialchars($_SESSION['wk_token'] ?? '') ?>">
                        <div class="form-help">Token para consultar dados financeiros (balancete/DRE) de todas as empresas</div>
                    </div>
                    <button type="button" id="testWkToken" class="btn btn-secondary"><img width="15" height="15" src="https://img.icons8.com/nolan/64/attach.png" alt="attach"/> Testar Conexão</button>
                </div>
            </div>

            <!-- Upload Dados Econômicos Config -->
            <div id="uploadConfigFields" class="wk-config hidden">
                <div class="wk-config-header">
                    <img width="25" height="20" src="https://img.icons8.com/nolan/64/import.png" alt="import"/>
                    <h3>Importar Dados Econômicos (CSV/Excel)</h3>
                    <span class="badge badge-info">Opcional</span>
                </div>
                <div class="wk-config-fields">
                    <div class="form-group">
                        <label class="form-label">Arquivo com dados econômicos da empresa</label>
                        <input type="file" id="uploadEconomico" class="form-input"
                            accept=".csv,.xlsx,.xls"
                            style="padding:0.5rem;background:white;">
                        <div class="form-help">
                            Envie o arquivo CSV ou Excel que você preencheu com os códigos econômicos
                            (21, 21A, 22, ESTOQUE, CMV, 61-68, 70-90, etc.) e seus valores.
                            O sistema extrai automaticamente os dados e aplica a todas as empresas.
                        </div>
                    </div>
                    <button type="button" id="uploadEconomicoBtn" class="btn btn-primary">
                        <img width="20" height="20" src="https://img.icons8.com/ios/50/import.png" alt="import"/>
                         Importar Dados</button>
                    <div id="uploadEconomicoStatus" class="mt-1" style="font-size:0.85rem;"></div>
                </div>
            </div>

            <!-- HERO / SEARCH -->
            <section class="hero">
                <h1><img width="64" height="64" src="https://img.icons8.com/nolan/64/gps-receiving.png" alt="gps-receiving--v1"/>
                     Gerador PAC Completo</h1>
                <p>
                    Consulte dados cadastrais de <strong>uma ou várias empresas</strong> gratuitamente via BrasilAPI,
                    depois revise e edite os dados para gerar o CSV do PAC (Pesquisa Anual de Comércio) do IBGE.
                </p>

                <?php if ($tipoAlerta && $mensagem): ?>
                    <div class="alert alert-<?= $tipoAlerta === 'error' ? 'error' : 'success' ?>" style="text-align:left;max-width:600px;margin:0 auto 1.5rem;">
                        <span class="alert-icon"><?= $tipoAlerta === 'error' ? '<img src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20">' : '<img src="/assets/img/icons8-ok.gif" alt="OK" width="20" height="20">' ?></span>
                        <span><?= htmlspecialchars($mensagem) ?></span>
                    </div>
                <?php endif; ?>

                <div class="search-box" style="max-width:600px;flex-direction:column;">
                    <textarea id="cnpjs" class="form-input textarea-multi"
                        rows="4"
                        placeholder="Digite os CNPJs (um por linha)&#10;Ex:&#10;27865757000102&#10;00279531001307&#10;08747503000137"
                        autocomplete="off" autofocus><?= htmlspecialchars($_SESSION['ultimos_cnpjs'] ?? '') ?></textarea>
                    <div style="display:flex;gap:0.75rem;justify-content:center;">
                        <button href="?action=atualizar" type="button" id="searchBtn" class="btn btn-primary btn-lg" style="flex:1;">
                            <img width="20" height="20" src="https://img.icons8.com/hatch/64/search.png" alt="search"/> Consultar CNPJs
                        </button>
                    </div>
                </div>
                <div class="form-help mt-1">
                    🔓 Consulta gratuita via BrasilAPI · Use <strong>Ctrl+Enter</strong> para consultar ·
                    Um CSV será gerado com <strong>todas as empresas</strong>
                </div>
            </section>

            <!-- Error Section -->
            <div id="errorSection" class="hidden">
                <div class="alert alert-error">
                    <span class="alert-icon"><img src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20"></span>
                    <span id="errorMessage"></span>
                </div>
            </div>

            <!-- CARD: Como funciona -->
            <div class="card mt-3">
                <div class="card-header">
                    <img width="34"height="34" src="https://img.icons8.com/nolan/64/backend-development.png" alt="backend-development"/>
                    <h2>Fluxo de Funcionamento</h2>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.5rem;">
                        <div style="text-align:center;padding:0.75rem;">
                            <div style="font-size:2rem;margin-bottom:0.5rem;"><img src="https://play-lh.googleusercontent.com/-m-vYWeUtLrNz5np6CoYVD1suPjH1TkARITNCdKXigjZRJZ237P17sZU0qpLJ6I3devSrDChk2_Plm2KGXeQ6A" alt="Importar" width="50" height="50"></div>
                            <h3 style="font-size:0.9rem;font-weight:600;margin-bottom:0.3rem;">1. Informar CNPJs</h3>
                            <p style="font-size:0.8rem;color:var(--gray-500);">Um ou vários CNPJs (um por linha)</p>
                        </div>
                        <div style="text-align:center;padding:0.75rem;">
                            
                            <div style="font-size:2rem;margin-bottom:0.5rem;"><img src="https://brasilapi.com.br/images/api-schema.svg" alt="Importar" width="50" height="50"/></div>
                            <h3 style="font-size:0.9rem;font-weight:600;margin-bottom:0.3rem;">2. BrasilAPI</h3>
                            <p style="font-size:0.8rem;color:var(--gray-500);">Dados cadastrais gratuitos de todas</p>
                        </div>
                        <div style="text-align:center;padding:0.75rem;">
                            <div style="font-size:2rem;margin-bottom:0.5rem;"><img src="https://wk.com.br/wp-content/uploads/2021/11/nova-logo-wk.png" alt="Importar" width="50" height="50"></div>
                            <h3 style="font-size:0.9rem;font-weight:600;margin-bottom:0.3rem;">3. WK Radar</h3>
                            <p style="font-size:0.8rem;color:var(--gray-500);">Dados financeiros (opcional, por token)</p>
                        </div>
                        <div style="text-align:center;padding:0.75rem;">
                            <div style="font-size:2rem;margin-bottom:0.5rem;"><img src="https://cdn-icons-png.flaticon.com/512/4301/4301966.png" alt="Importar" width="50" height="50"></div>
                            <h3 style="font-size:0.9rem;font-weight:600;margin-bottom:0.3rem;">4. Revisar</h3>
                            <p style="font-size:0.8rem;color:var(--gray-500);">Navegue entre empresas e edite</p>
                        </div>
                        <div style="text-align:center;padding:0.75rem;">
                            <div style="font-size:2rem;margin-bottom:0.5rem;"><img width="50" height="50" src="https://img.icons8.com/nolan/64/import-csv.png" alt="import-csv"/>
                            <h3 style="font-size:0.9rem;font-weight:600;margin-bottom:0.3rem;">5. Gerar CSV</h3>
                            <p style="font-size:0.8rem;color:var(--gray-500);">Download único com todas as empresas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD: Empresas na sessão -->
            <?php if ($totalEmpresas > 0):
                $empresas = $_SESSION['empresas'] ?? [];
            ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <img width="30" height="30" src="https://img.icons8.com/nolan/64/mac-folder.png" alt="mac-folder"/>
                        <h2>Empresas Carregadas na Sessão</h2>
                        <span class="badge badge-info"><?= $totalEmpresas ?> empresa<?= $totalEmpresas > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;flex-direction:column;gap:0.4rem;">
                            <?php foreach ($empresas as $i => $emp):
                                $razao = $emp['razao_social'] ?? 'Sem nome';
                                $cnpj = ($emp['cnpj_raiz'] ?? '') . ($emp['cnpj_sufixo'] ?? '') . ($emp['cnpj_dv'] ?? '');
                                $val = PACValidator::validarEmpresa($emp);
                                $pct = 0;
                                $total = count(PACValidator::getCamposObrigatorios());
                                foreach (PACValidator::getCamposObrigatorios() as $ch) {
                                    if (!empty($emp[$ch])) $pct++;
                                }
                            ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0.75rem;background:var(--gray-50);border-radius:var(--radius-xs);border:1px solid var(--gray-200);">
                                    <div style="flex:1;min-width:0;">
                                        <strong style="font-size:0.9rem;"><?= htmlspecialchars(mb_substr($razao, 0, 60)) ?></strong>
                                        <span style="font-size:0.78rem;color:var(--gray-500);margin-left:0.5rem;">
                                            CNPJ: <?= htmlspecialchars($cnpj) ?>
                                        </span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
                                        <a href="?action=remover_empresa&idx=<?= $i ?>"
                                            class="btn-icon btn-icon-danger"
                                            title="Remover esta empresa"
                                            onclick="return confirm('Remover <?= htmlspecialchars(addslashes($razao)) ?> da lista?')">
                                            ✖
                                        </a>
                                        <span style="font-size:0.78rem;color:var(--gray-500);"><?= $pct ?>/<?= $total ?></span>
                                        <?php if ($val['valida']): ?>
                                            <span class="badge badge-success"><img width="20" height="20" src="/assets/img/icons8-ok.gif" alt="OK"/></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?= count($val['erros']) ?> pend.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2 text-center">
                            <a href="?action=editar" class="btn btn-primary"><img width="20" height="20" src="https://img.icons8.com/external-flaticons-lineal-color-flat-icons/64/external-proofreading-digital-nomad-flaticons-lineal-color-flat-icons.png" alt="external-proofreading-digital-nomad-flaticons-lineal-color-flat-icons"/> Revisar e Editar Todas</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CARD: Histórico -->
            <?php if (!empty($_SESSION['csv_history'])): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <img width="30" height="30" src="https://img.icons8.com/nolan/64/mac-folder.png" alt="mac-folder"/>
                        <h2>CSVs Gerados Nesta Sessão</h2>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;flex-direction:column;gap:0.5rem;">
                            <?php foreach (array_reverse($_SESSION['csv_history']) as $csv): ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0.75rem;background:var(--gray-50);border-radius:var(--radius-xs);border:1px solid var(--gray-200);">
                                    <div>
                                        <strong style="font-size:0.9rem;"><?= htmlspecialchars($csv['empresas']) ?></strong>
                                        <span style="font-size:0.8rem;color:var(--gray-500);margin-left:0.75rem;">
                                            <img width="15" height="15" src="https://img.icons8.com/nolan/64/file.png" alt="file"/> <?= htmlspecialchars($csv['arquivo']) ?>
                                            &middot; <?= $csv['total'] ?> empresa<?= $csv['total'] > 1 ? 's' : '' ?>
                                        </span>
                                    </div>
                                    <span class="badge badge-success"><img width="20" height="20" src="/assets/img/icons8-ok.gif" alt="OK"/> Gerado</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <!-- Toast de feedback -->
        <div id="toast-container" class="toast-container"></div>

        <?php if (!empty($_SESSION['flash_toast'])):
            $flash = $_SESSION['flash_toast'];
            unset($_SESSION['flash_toast']);
        ?>
            <script>
                (function() {
                    showToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type'] ?? 'success') ?>);
                })();
            </script>
        <?php endif; ?>

        </main>

        <!-- Toast de feedback -->
        <div id="toast-container" class="toast-container"></div>

        <?php if (!empty($_SESSION['flash_toast'])):
            $flash = $_SESSION['flash_toast'];
            unset($_SESSION['flash_toast']);
        ?>
            <script>
                (function() {
                    showToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type'] ?? 'success') ?>);
                })();
            </script>
        <?php endif; ?>

        <script src="/assets/script.js"></script>
    </body>

    </html>
<?php
}

// =========================================================================
// CONSULTAR CNPJs (AJAX)
// =========================================================================

function handleConsultarCNPJ(): void
{
    $raw = $_POST['cnpjs'] ?? '[]';
    $cnpjs = json_decode($raw, true);
    if (!is_array($cnpjs) || empty($cnpjs)) {
        jsonResponse(false, null, 'Nenhum CNPJ informado.');
    }

    // Guardar os CNPJs digitados
    $_SESSION['ultimos_cnpjs'] = implode("\n", $cnpjs);

    $api = new BrasilAPI();
    $empresas = [];
    $erros = [];

    foreach ($cnpjs as $i => $cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) !== 14) {
            $erros[] = "CNPJ '{$cnpj}' inválido (posição " . ($i + 1) . ")";
            continue;
        }
        try {
            $empresa = $api->extrairDadosPAC($cnpj);
            $empresa['_cnpj_original'] = $cnpj;
            $empresas[] = $empresa;
        } catch (Exception $e) {
            $erros[] = "CNPJ {$cnpj}: " . $e->getMessage();
        }
    }

    if (empty($empresas)) {
        jsonResponse(false, null, 'Nenhuma empresa pôde ser consultada: ' . implode('; ', $erros));
    }

    $_SESSION['empresas'] = $empresas;

    // Se tem token WK, tenta buscar dados financeiros para todas
    $wkToken = $_POST['wk_token'] ?? $_SESSION['wk_token'] ?? '';
    if ($wkToken) {
        $_SESSION['wk_token'] = $wkToken;
        try {
            $wk = new WKRadar($wkToken);
            $ano = $_SESSION['ano'] ?? 2025;
            $balancete = $wk->obterBalancete((int)$ano);
            if (!empty($balancete)) {
                $wkDados = $wk->extrairDadosPAC($balancete);
                foreach ($_SESSION['empresas'] as $i => &$emp) {
                    foreach ($wkDados as $chave => $valor) {
                        $emp[$chave] = $valor;
                    }
                }
                unset($emp);
            }
        } catch (Exception $e) {
            // Ignora erro do WK
        }
    }

    $msg = count($empresas) . ' empresa' . (count($empresas) > 1 ? 's' : '') . ' carregada' . (count($empresas) > 1 ? 's' : '');
    if (!empty($erros)) {
        $msg .= ' (' . count($erros) . ' erro' . (count($erros) > 1 ? 's' : '') . ')';
    }

    jsonResponse(true, [
        'total' => count($empresas),
        'erros' => count($erros),
        'mensagem' => $msg,
        'empresas' => array_map(fn($e) => $e['razao_social'] ?? 'N/A', $empresas),
    ]);
}

// =========================================================================
// EDITAR (tela de edição com abas)
// =========================================================================

function handleEditar(): void
{
    $empresas = $_SESSION['empresas'] ?? [];

    if (empty($empresas)) {
        renderHome('error', 'Nenhuma empresa carregada. Faça uma consulta primeiro.');
        return;
    }

    $ano = $_SESSION['ano'] ?? 2025;
    renderEditorMulti($empresas, $ano);
}

/**
 * Renderiza o editor multi-empresa com abas
 */
function renderEditorMulti(array $empresas, int $ano): void
{
    $total = count($empresas);
    $camposObrigatorios = PACValidator::getCamposObrigatorios();

    // Validação geral
    $validacoes = [];
    $totalPreenchidos = 0;
    $totalPendentes = 0;
    foreach ($empresas as $i => $emp) {
        $v = PACValidator::validarEmpresa($emp, (string)$ano);
        $validacoes[$i] = $v;
        if (!$v['valida']) $totalPendentes += count($v['erros']);
        foreach ($camposObrigatorios as $ch) {
            if (!empty($emp[$ch])) $totalPreenchidos++;
        }
    }
    $totalCampos = $total * count($camposObrigatorios);

?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- Open Graph / Social Meta Tags -->
        <meta property="og:title" content="PAC Completo IBGE - Editor Multi-empresa">
        <meta property="og:description" content="Revise e edite os dados de todas as empresas antes de gerar o CSV do PAC IBGE.">
        <meta property="og:image" content="https://pac-completo-ibge.vercel.app/assets/img/preview-editor.png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:url" content="https://pac-completo-ibge.vercel.app">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="PAC Completo IBGE">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="PAC Completo IBGE - Editor Multi-empresa">
        <meta name="twitter:description" content="Revise e edite os dados de todas as empresas antes de gerar o CSV do PAC IBGE.">
        <meta name="twitter:image" content="https://pac-completo-ibge.vercel.app/assets/img/preview-editor.png">

        <title>Editar (<?= $total ?> empresas) - PAC Completo IBGE</title>
        <link rel="stylesheet" href="/assets/style.css">
        <link rel="icon" src="https://img.icons8.com/nolan/64/gps-receiving.png">
    </head>

    <body>

        <!-- HEADER -->
        <header class="header">
            <div class="header-inner">
                <a href="?" class="header-brand">
                    <div class="header-brand-icon">PAC</div>
                    <div class="header-brand-text">
                        <h1>PAC Completo IBGE</h1>
                        <small><?= $total ?> empresa<?= $total > 1 ? 's' : '' ?> para revisar</small>
                    </div>
                </a>
                <div class="header-actions">
                    <span class="badge badge-info"><?= $totalPreenchidos ?>/<?= $totalCampos ?> campos</span>
                    <a href="?action=limpar" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza? Todas as empresas serão removidas.')">
                    <img width="20" height="20" src="https://img.icons8.com/parakeet-line/48/delete.png" alt="delete"/>
                    Nova Consulta
                    </a>
                    <a href="?" class="btn btn-secondary btn-sm"><img width="20" height="20" src="https://img.icons8.com/nolan/64/circled-left-2.png" alt="circled-left-2"/> Início</a>
                </div>
            </div>
        </header>

        <main class="container">

            <!-- Stepper -->
            <div class="stepper">
                <div class="step completed">
                    <span class="step-number">✓</span>
                    Consultar CNPJs
                </div>
                <div class="step active">
                    <span class="step-number">2</span>
                    Revisar & Editar
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    Gerar CSV
                </div>
            </div>

            <!-- Alertas gerais -->
            <?php if ($totalPendentes > 0): ?>
                <div class="alert alert-warning">
                    <img class="alert-icon" src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20">
                    <div>
                        <strong><?= $totalPendentes ?> campo(s) obrigatório(s) pendente(s) no total.</strong>
                        Revise cada empresa abaixo e preencha os campos necessários.
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['wk_token']) && $_SESSION['wk_token']): ?>
                <div class="alert alert-success">
                    <img class="alert-icon" src="/assets/img/icons8-sucesso.gif" alt="Sucesso" width="20" height="20">
                    <div>
                        <strong>WK Radar configurado.</strong> Dados financeiros serão aplicados a todas as empresas.
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="?action=salvar" id="formMultiEmpresa">

                <!-- ================================================================ -->
                <!-- COMPANY TABS -->
                <!-- ================================================================ -->
                <div class="company-tabs">
                    <?php foreach ($empresas as $i => $emp):
                        $razao = $emp['razao_social'] ?? "Empresa #" . ($i + 1);
                        $cnpj = ($emp['cnpj_raiz'] ?? '') . ($emp['cnpj_sufixo'] ?? '') . ($emp['cnpj_dv'] ?? '');
                        $status = $validacoes[$i]['valida'] ? '<img src="/assets/img/icons8-ok.gif" alt="OK" width="20" height="20">' : '<img src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20">';
                    ?>
                        <button type="button" class="company-tab <?= $i === 0 ? 'active' : '' ?>" data-company-tab="<?= $i ?>">
                            <span class="tab-status"><?= $status ?></span>
                            <span><?= htmlspecialchars(mb_substr($razao, 0, 28)) ?><?= mb_strlen($razao) > 28 ? '...' : '' ?></span>
                            <span class="tab-cnpj"><?= htmlspecialchars($cnpj) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" id="current_empresa_index" name="current_index" value="0">

                <!-- ================================================================ -->
                <!-- COMPANY PANELS -->
                <!-- ================================================================ -->

                <?php foreach ($empresas as $idx => $empresa):
                    $mercadorias = $empresa['mercadorias'] ?? [];
                    $validacao = $validacoes[$idx];
                    $pct = 0;
                    foreach ($camposObrigatorios as $ch) {
                        if (!empty($empresa[$ch])) $pct++;
                    }
                    $totalCamposEmp = count($camposObrigatorios);
                ?>

                    <div class="company-panel <?= $idx === 0 ? 'active' : '' ?>" data-company-panel="<?= $idx ?>">

                        <!-- Status da empresa -->
                        <div class="flex-between mb-2">
                            <div>
                                <h2 style="font-size:1.2rem;font-weight:600;">
                                    <?= htmlspecialchars($empresa['razao_social'] ?? 'Sem razão social') ?>
                                </h2>
                                <span style="font-size:0.85rem;color:var(--gray-500);">
                                    CNPJ: <?= htmlspecialchars(($empresa['cnpj_raiz'] ?? '') . ($empresa['cnpj_sufixo'] ?? '') . ($empresa['cnpj_dv'] ?? '')) ?>
                                    &middot; Empresa <?= $idx + 1 ?> de <?= $total ?>
                                </span>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:0.85rem;color:var(--gray-600);margin-bottom:0.3rem;">
                                    Campos: <?= $pct ?>/<?= $totalCamposEmp ?>
                                </div>
                                <?php if ($validacao['valida']): ?>
                                    <span class="badge badge-success"><img src="/assets/img/icons8-ok.gif" alt="OK" width="20" height="20"> OK</span>
                                <?php else: ?>
                                    <!-- <img src="/assets/img/icons8-erro(1).gif" alt="Erro" width="20" height="20"> -->
                                    <span class="badge badge-warning"><img src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20"><?= count($validacao['erros']) ?> pend.</span>
                                <?php endif; ?>
                                <div style="margin-top:0.5rem;">
                                    <a href="?action=remover_empresa&idx=<?= $idx ?>&voltar=editor"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Remover <?= htmlspecialchars(addslashes($empresa['razao_social'] ?? 'esta empresa')) ?> da lista?')"
                                        style="font-size:0.75rem;padding:0.2rem 0.6rem;">
                                        <img src="/assets/img/icons8-erro (1).gif" alt="Remover" width="20" height="20"> Remover
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Erros desta empresa -->
                        <?php if (!empty($validacao['erros'])): ?>
                            <div class="alert alert-warning">
                                <img class="alert-icon" src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20">
                                <div>
                                    <strong>Campos pendentes:</strong>
                                    <ul class="error-list mt-1">
                                        <?php foreach ($validacao['erros'] as $erro): ?>
                                            <li><?= htmlspecialchars($erro) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- ============================================================ -->
                        <!-- BLOCO 1: DADOS CADASTRAIS -->
                        <!-- ============================================================ -->
                        <div class="card company-section">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/nolan/64/organization.png" alt="organization"/>
                                <h2>Dados Cadastrais da Sede</h2>
                            </div>
                            <div class="card-body">
                                <div class="data-grid">
                                    <div class="data-field">
                                        <label class="data-field-label">CNPJ Raiz <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][cnpj_raiz]" value="<?= htmlspecialchars($empresa['cnpj_raiz'] ?? '') ?>" maxlength="8" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Sufixo <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][cnpj_sufixo]" value="<?= htmlspecialchars($empresa['cnpj_sufixo'] ?? '') ?>" maxlength="4" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">DV <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][cnpj_dv]" value="<?= htmlspecialchars($empresa['cnpj_dv'] ?? '') ?>" maxlength="2" required>
                                        </div>
                                    </div>
                                    <div class="data-field" style="grid-column:span 2;">
                                        <label class="data-field-label">Razão Social <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][razao_social]" value="<?= htmlspecialchars($empresa['razao_social'] ?? '') ?>" maxlength="150" required style="font-weight:600;">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Nome Fantasia</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][nome_fantasia]" value="<?= htmlspecialchars($empresa['nome_fantasia'] ?? '') ?>" maxlength="100">
                                        </div>
                                    </div>
                                    <div class="data-field" style="grid-column:span 2;">
                                        <label class="data-field-label">Site / Redes Sociais</label>
                                        <div class="data-field-value">
                                            <input type="url" name="empresa[<?= $idx ?>][site_empresa]" value="<?= htmlspecialchars($empresa['site_empresa'] ?? '') ?>" maxlength="200" placeholder="https://exemplo.com.br">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">UF <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uf_sede]" value="<?= htmlspecialchars($empresa['uf_sede'] ?? '') ?>" maxlength="2" required style="text-transform:uppercase;">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Município <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][municipio_sede]" value="<?= htmlspecialchars($empresa['municipio_sede'] ?? '') ?>" maxlength="60" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Distrito (SEDE)</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][distrito_sede]" value="<?= htmlspecialchars($empresa['distrito_sede'] ?? '') ?>" maxlength="60" placeholder="SP, Brasília, Goiânia">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Tipo Logradouro <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][logradouro_sede_tipo]" value="<?= htmlspecialchars($empresa['logradouro_sede_tipo'] ?? '') ?>" maxlength="30" required placeholder="Rua, Av, etc.">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Nome Logradouro <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][logradouro_sede_nome]" value="<?= htmlspecialchars($empresa['logradouro_sede_nome'] ?? '') ?>" maxlength="100" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Número <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][logradouro_sede_numero]" value="<?= htmlspecialchars($empresa['logradouro_sede_numero'] ?? '') ?>" maxlength="10" required placeholder="S/N">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Complemento</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][logradouro_sede_comp]" value="<?= htmlspecialchars($empresa['logradouro_sede_comp'] ?? '') ?>" maxlength="50">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Bairro <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][bairro_sede]" value="<?= htmlspecialchars($empresa['bairro_sede'] ?? '') ?>" maxlength="60" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">CEP <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][cep_sede]" value="<?= htmlspecialchars($empresa['cep_sede'] ?? '') ?>" maxlength="8" required placeholder="00000000">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">DDD <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][ddd_sede]" value="<?= htmlspecialchars($empresa['ddd_sede'] ?? '') ?>" maxlength="2" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Telefone <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][telefone_sede]" value="<?= htmlspecialchars($empresa['telefone_sede'] ?? '') ?>" maxlength="10" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Ramal</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][ramal_sede]" value="<?= htmlspecialchars($empresa['ramal_sede'] ?? '') ?>" maxlength="10">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">E-mail</label>
                                        <div class="data-field-value">
                                            <input type="email" name="empresa[<?= $idx ?>][email_empresa]" value="<?= htmlspecialchars($empresa['email_empresa'] ?? '') ?>" maxlength="100">
                                        </div>
                                    </div>
                                    <div class="data-field" style="grid-column:span 2;">
                                        <label class="data-field-label">Descrição da Atividade <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][descricao_atividade]" value="<?= htmlspecialchars($empresa['descricao_atividade'] ?? '') ?>" maxlength="200" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">CNAE (7 dígitos) <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][cnae_principal]" value="<?= htmlspecialchars($empresa['cnae_principal'] ?? '') ?>" maxlength="7" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">DV CNAE</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][cnae_dv]" value="<?= htmlspecialchars($empresa['cnae_dv'] ?? '') ?>" maxlength="2">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO 1B: UNIDADE DE COLETA (UC) -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30"height="30" src="https://img.icons8.com/external-gradients-pause-08/64/external-pin-travel2-gradients-pause-08.png" alt="external-pin-travel2-gradients-pause-08"/>
                                <h2>Unidade de Coleta (UC)</h2>
                                <span class="badge badge-info">Opcional - preencha se diferente da sede</span>
                            </div>
                            <div class="card-body">
                                <div class="data-grid">
                                    <div class="data-field">
                                        <label class="data-field-label">Sufixo UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_sufixo]" value="<?= htmlspecialchars($empresa['uc_sufixo'] ?? '') ?>" maxlength="4" placeholder="0001">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">DV UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_dv]" value="<?= htmlspecialchars($empresa['uc_dv'] ?? '') ?>" maxlength="2">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">UF UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_uf]" value="<?= htmlspecialchars($empresa['uc_uf'] ?? '') ?>" maxlength="2" style="text-transform:uppercase;">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Município UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_municipio]" value="<?= htmlspecialchars($empresa['uc_municipio'] ?? '') ?>" maxlength="60">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Distrito UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_distrito]" value="<?= htmlspecialchars($empresa['uc_distrito'] ?? '') ?>" maxlength="60">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Tipo Lograd. UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_logradouro_tipo]" value="<?= htmlspecialchars($empresa['uc_logradouro_tipo'] ?? '') ?>" maxlength="30" placeholder="Rua, Av">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Logradouro UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_logradouro_nome]" value="<?= htmlspecialchars($empresa['uc_logradouro_nome'] ?? '') ?>" maxlength="100">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Número UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_logradouro_numero]" value="<?= htmlspecialchars($empresa['uc_logradouro_numero'] ?? '') ?>" maxlength="10" placeholder="S/N">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Complemento UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_complemento]" value="<?= htmlspecialchars($empresa['uc_complemento'] ?? '') ?>" maxlength="50">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Bairro UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_bairro]" value="<?= htmlspecialchars($empresa['uc_bairro'] ?? '') ?>" maxlength="60">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">CEP UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_cep]" value="<?= htmlspecialchars($empresa['uc_cep'] ?? '') ?>" maxlength="8" placeholder="00000000">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">DDD UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_ddd]" value="<?= htmlspecialchars($empresa['uc_ddd'] ?? '') ?>" maxlength="2">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Telefone UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_telefone]" value="<?= htmlspecialchars($empresa['uc_telefone'] ?? '') ?>" maxlength="10">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Ramal UC</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][uc_ramal]" value="<?= htmlspecialchars($empresa['uc_ramal'] ?? '') ?>" maxlength="10">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO 2: RESPONSÁVEL -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/nolan/64/men-age-group-4--v2.png" alt="men-age-group-4--v2"/>
                                <h2>Responsável pelo Preenchimento</h2>
                            </div>
                            <div class="card-body">
                                <div class="data-grid">
                                    <div class="data-field" style="grid-column:span 2;">
                                        <label class="data-field-label">Nome <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][responsavel_nome]" value="<?= htmlspecialchars($empresa['responsavel_nome'] ?? '') ?>" maxlength="100" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Cargo <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][responsavel_cargo]" value="<?= htmlspecialchars($empresa['responsavel_cargo'] ?? '') ?>" maxlength="50" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">DDD</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][responsavel_ddd]" value="<?= htmlspecialchars($empresa['responsavel_ddd'] ?? '') ?>" maxlength="2">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Telefone</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][responsavel_telefone]" value="<?= htmlspecialchars($empresa['responsavel_telefone'] ?? '') ?>" maxlength="11">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Ramal</label>
                                        <div class="data-field-value">
                                            <input type="text" name="empresa[<?= $idx ?>][responsavel_ramal]" value="<?= htmlspecialchars($empresa['responsavel_ramal'] ?? '') ?>" maxlength="10">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">E-mail</label>
                                        <div class="data-field-value">
                                            <input type="email" name="empresa[<?= $idx ?>][responsavel_email]" value="<?= htmlspecialchars($empresa['responsavel_email'] ?? '') ?>" maxlength="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO 3: SITUAÇÃO CADASTRAL -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/external-gradients-pause-08/64/external-pin-travel2-gradients-pause-08.png" alt="external-pin-travel2-gradients-pause-08"/>
                                <h2>Situação Cadastral</h2>
                            </div>
                            <div class="card-body">
                                <div class="data-grid">
                                    <div class="data-field">
                                        <label class="data-field-label">Situação <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <select name="empresa[<?= $idx ?>][situacao_cadastral]" class="form-input" required style="background:white;">
                                                <option value="">Selecione...</option>
                                                <?php foreach (['01-Ativa', '02-Suspensa', '03-Rec. Judicial', '04-Rec. Extrajudicial', '05-Falida', '06-Extinta', '07-Sem movimento', '08-Implantação', '09-Não iniciou', '10-Condomínio'] as $opt):
                                                    $val = substr($opt, 0, 2);
                                                    $sel = ($empresa['situacao_cadastral'] ?? '') === $val ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $val ?>" <?= $sel ?>><?= $opt ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Mudança Estrutural <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <select name="empresa[<?= $idx ?>][mudanca_estrutural]" class="form-input" required style="background:white;">
                                                <option value="">Selecione...</option>
                                                <option value="00" <?= ($empresa['mudanca_estrutural'] ?? '') === '00' ? 'selected' : '' ?>>00 - Não houve mudança</option>
                                                <option value="01" <?= ($empresa['mudanca_estrutural'] ?? '') === '01' ? 'selected' : '' ?>>01 - Incorporou outra</option>
                                                <option value="02" <?= ($empresa['mudanca_estrutural'] ?? '') === '02' ? 'selected' : '' ?>>02 - Foi incorporada</option>
                                                <option value="03" <?= ($empresa['mudanca_estrutural'] ?? '') === '03' ? 'selected' : '' ?>>03 - Cisão total</option>
                                                <option value="04" <?= ($empresa['mudanca_estrutural'] ?? '') === '04' ? 'selected' : '' ?>>04 - Cisão parcial</option>
                                                <option value="05" <?= ($empresa['mudanca_estrutural'] ?? '') === '05' ? 'selected' : '' ?>>05 - Fusão</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Forma de Tributação <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <select name="empresa[<?= $idx ?>][forma_tributacao]" class="form-input" required style="background:white;">
                                                <option value="">Selecione...</option>
                                                <?php foreach (['1-Lucro Real', '2-Lucro Presumido', '3-Simples Nacional', '4-MEI', '5-Imune/Isenta'] as $opt):
                                                    $val = substr($opt, 0, 1);
                                                    $sel = ($empresa['forma_tributacao'] ?? '') === $val ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $val ?>" <?= $sel ?>><?= $opt ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO 9 — PESSOAL OCUPADO (posições 66-81) -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/nolan/64/group-task.png" alt="group-task"/>
                                <h2>Pessoal Ocupado — Posições 66 a 81</h2>
                                <?php
                                $pessoalCampos = PACValidator::getPessoalCampos();
                                $pessoalPreenchidos = 0;
                                foreach ($pessoalCampos as $ch) {
                                    if (!empty($empresa[$ch]) || (isset($empresa[$ch]) && $empresa[$ch] === '0')) $pessoalPreenchidos++;
                                }
                                $pessoalTotal = count($pessoalCampos);
                                if ($pessoalPreenchidos === $pessoalTotal):
                                ?>
                                    <span class="badge badge-success"><img width="30" height="30" src="/assets/img/icons8-ok.gif" alt="verified-account"/>
                                <?php elseif ($pessoalPreenchidos > 0): ?>
                                    <span class="badge badge-warning"><img width="20" height="20" src="/assets/img/icons8-erro.gif" alt="alert"/> <?= $pessoalPreenchidos ?>/<?= $pessoalTotal ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><img width="20" height="20" src="/assets/img/icons8-erro (1).gif" alt="cancel"/> 0/<?= $pessoalTotal ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <p style="font-size:0.85rem;color:var(--gray-500);margin-bottom:1rem;">
                                    Informe para <strong>cada trimestre</strong> o número de pessoas ocupadas em cada categoria.
                                    Estas informações correspondem às posições <strong>66 a 81</strong> do layout oficial.
                                </p>
                                <div class="pessoal-grid">
                                    <table class="pessoal-table">
                                        <thead>
                                            <tr>
                                                <th style="width:40%;">Categoria</th>
                                                <th style="width:15%;">31/03 (66-69)</th>
                                                <th style="width:15%;">30/06 (70-73)</th>
                                                <th style="width:15%;">30/09 (74-77)</th>
                                                <th style="width:15%;">31/12 (78-81)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $categorias = [
                                                'assalariado_comercial'  => 'Assalariado — Comercial/Repres.',
                                                'assalariado_nao_comercial' => 'Assalariado — Não comercial',
                                                'nao_assalariado_socios' => 'Não assalariado (Sócios)',
                                                'nao_assalariado_familia' => 'Não assalariado (Família)',
                                            ];
                                            $trimestres = [
                                                'marco' => '66',
                                                'junho' => '70',
                                                'setembro' => '74',
                                                'dezembro' => '78',
                                            ];
                                            foreach ($categorias as $catKey => $catLabel):
                                            ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($catLabel) ?></strong></td>
                                                    <?php foreach ($trimestres as $sufixo => $posInicial):
                                                        $chave = "pessoal_{$catKey}_{$sufixo}";
                                                        $valor = $empresa[$chave] ?? '';
                                                    ?>
                                                        <td>
                                                            <input type="number" name="empresa[<?= $idx ?>][<?= $chave ?>]"
                                                                value="<?= htmlspecialchars($valor) ?>"
                                                                min="0" class="form-input pessoal-input"
                                                                placeholder="0"
                                                                title="Posição <?= $posInicial ?> — <?= htmlspecialchars($catLabel) ?> em <?= $sufixo === 'marco' ? '31/03' : ($sufixo === 'junho' ? '30/06' : ($sufixo === 'setembro' ? '30/09' : '31/12')) ?>">
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO: SALÁRIOS E ENCARGOS -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/external-gradients-pause-08/64/external-bag-business-gradients-pause-08-2.png" alt="external-bag-business-gradients-pause-08-2"/>
                                <h2>Salários, Retiradas e Remunerações</h2>
                            </div>
                            <div class="card-body">
                                <div class="data-grid">
                                    <div class="data-field">
                                        <label class="data-field-label">Salários Anuais (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][salarios_anuais]" value="<?= htmlspecialchars($empresa['salarios_anuais'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Encargos Sociais (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][encargos_sociais]" value="<?= htmlspecialchars($empresa['encargos_sociais'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO: DADOS ECONÔMICOS IMPORTADOS -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/external-gradients-pause-08/64/external-arrow-lined-shipping-gradients-pause-08.png" alt="external-arrow-lined-shipping-gradients-pause-08"/>
                                <h2>Dados Econômicos Importados</h2>
                                <span class="badge badge-primary">Automático</span>
                            </div>
                            <div class="card-body">
                                <p style="font-size:0.85rem;color:var(--gray-500);margin-bottom:1rem;">
                                    Faça upload do arquivo CSV/Excel com os códigos econômicos (21, 21A, 22, CMV, 61, 70, etc.)
                                    para preencher automaticamente os campos abaixo. Os dados importados são mesclados
                                    com os dados já preenchidos manualmente.
                                </p>

                                <!-- Upload per-company -->
                                <div class="upload-economico-empresa">
                                    <input type="file" class="upload-economico-input"
                                        data-empresa-idx="<?= $idx ?>"
                                        accept=".csv,.xlsx,.xls"
                                        style="display:none;">
                                    <button type="button" class="btn btn-primary btn-sm upload-economico-btn"
                                        data-empresa-idx="<?= $idx ?>">
                                        <img
                                            src="https://img.icons8.com/external-outline-icons-pause-08/64/external-arrow-lined-shipping-outline-icons-pause-08.png"
                                            alt="Importar"
                                            width="20"
                                            height="20"
                                            style="vertical-align: middle; margin-right: 5px;">
                                        Importar CSV/Excel para esta empresa
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm upload-economico-global-btn"
                                        data-empresa-idx="<?= $idx ?>">
                                        <img 
                                        width="20"
                                        height="20" 
                                        src="https://img.icons8.com/external-gradients-pause-08/64/external-and-industry-gradients-pause-08.png" 
                                        alt="external-and-industry-gradients-pause-08"
                                        style="vertical-align: middle; margin-right: 5px;">
                                    Usar dados da importação global
                                    </button>
                                    <span class="upload-economico-status" style="font-size:0.8rem;color:var(--gray-500);margin-left:0.5rem;"></span>
                                </div>

                                <!-- Tabela de códigos econômicos (collapsible) -->
                                <div class="economico-wrapper collapsed" data-economico-wrapper="<?= $idx ?>">
                                    <button type="button" class="btn btn-sm btn-secondary toggle-economico-btn"
                                        data-toggle-economico="<?= $idx ?>" style="margin-bottom:0.75rem;">
                                        <img 
                                        width="20" 
                                        height="20" 
                                        src="https://img.icons8.com/nolan/64/ophthalmology.png" 
                                        alt="ophthalmology"/>
                                        Mostrar tabela de códigos (<?= count(PACDadosEconomicos::getCodigosValidos()) ?> campos)
                                    </button>
                                    <div class="economico-grid" id="economico-grid-<?= $idx ?>">
                                        <?php
                                        $codigos = PACDadosEconomicos::getCodigosValidos();
                                        $descricoes = PACDadosEconomicos::getCodigoDescricoes();
                                        $chaveParaCodigo = PACDadosEconomicos::getChaveParaCodigo();
                                        $map = PACDadosEconomicos::getCodigoEconomicoMap();
                                        ?>
                                        <table class="economico-table">
                                            <thead>
                                                <tr>
                                                    <th style="width:60px;">Código</th>
                                                    <th>Descrição</th>
                                                    <th style="width:160px;">Valor (R$)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($codigos as $cod):
                                                    $chaveInterna = $map[$cod] ?? '';
                                                    $desc = $descricoes[$cod] ?? $cod;
                                                    $valorAtual = $empresa[$chaveInterna] ?? '';

                                                    // Classifica por categoria para cor
                                                    $classe = '';
                                                    if (in_array($cod, ['21', '21A', '22', '23', '24', '25', '31', '32', '33', '34', '35', '36'])) $classe = 'linha-receita';
                                                    elseif (in_array($cod, ['26', '27', '28', '29'])) $classe = 'linha-deducao';
                                                    elseif (in_array($cod, ['61', '62', '63', '64', '65', '66', '67', '68', '70', '70A', '71', '72', '73', '74', '75', '76', '77', '78', '79', '80', '81', '82', '83', '84', '85', '86', '87', '88', '89', '89B', '90', '92', '93', '94', '95', '96', '98', '99', '100', '101'])) $classe = 'linha-despesa';
                                                    elseif (in_array($cod, ['ESTOQUE', 'CMV'])) $classe = 'linha-custo';
                                                    elseif (in_array($cod, ['103', '104'])) $classe = 'linha-resultado';
                                                ?>
                                                    <tr class="<?= $classe ?>">
                                                        <td><code><?= htmlspecialchars($cod) ?></code></td>
                                                        <td style="font-size:0.82rem;"><?= htmlspecialchars($desc) ?></td>
                                                        <td>
                                                            <input type="text"
                                                                name="empresa[<?= $idx ?>][<?= $chaveInterna ?>]"
                                                                value="<?= htmlspecialchars($valorAtual) ?>"
                                                                class="form-input"
                                                                style="text-align:right;font-family:monospace;width:100%;"
                                                                placeholder="0"
                                                                inputmode="numeric">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO 5: RECEITAS -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/external-gradients-pause-08/64/external-analytics-business-charts-and-diagrams-gradients-pause-08-5.png" alt="external-analytics-business-charts-and-diagrams-gradients-pause-08-5"/>
                                <h2>Receitas</h2>
                            </div>
                            <div class="card-body">
                                <div class="data-grid">
                                    <div class="data-field">
                                        <label class="data-field-label">Receita Revenda <span class="required">*</span></label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][receita_revenda]" value="<?= htmlspecialchars($empresa['receita_revenda'] ?? '') ?>" min="0" step="1" required>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Receita Serviços (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][receita_servicos]" value="<?= htmlspecialchars($empresa['receita_servicos'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Deduções (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][deducoes_vendas]" value="<?= htmlspecialchars($empresa['deducoes_vendas'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Outras Receitas (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][outras_receitas]" value="<?= htmlspecialchars($empresa['outras_receitas'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO 6: DESPESAS -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/nolan/64/split-money.png" alt="split-money"/>
                                <h2>Despesas</h2>
                            </div>
                            <div class="card-body">
                                <div class="data-grid">
                                    <div class="data-field">
                                        <label class="data-field-label">CMV/Insumos (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][despesa_mercadorias_insumos]" value="<?= htmlspecialchars($empresa['despesa_mercadorias_insumos'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Desp. Pessoal (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][despesa_pessoal]" value="<?= htmlspecialchars($empresa['despesa_pessoal'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Aluguéis (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][despesa_alugueis]" value="<?= htmlspecialchars($empresa['despesa_alugueis'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Tributos (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][despesa_tributos]" value="<?= htmlspecialchars($empresa['despesa_tributos'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Desp. Financeiras (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][despesa_financeiras]" value="<?= htmlspecialchars($empresa['despesa_financeiras'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Utilidades (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][despesa_utilidades]" value="<?= htmlspecialchars($empresa['despesa_utilidades'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Serv. Terceiros (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][despesa_servicos_terceiros]" value="<?= htmlspecialchars($empresa['despesa_servicos_terceiros'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO 7: ESTOQUES -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/external-xnimrodx-lineal-gradient-xnimrodx/64/external-box-e-commerce-and-business-xnimrodx-lineal-gradient-xnimrodx.png" alt="external-box-e-commerce-and-business-xnimrodx-lineal-gradient-xnimrodx"/>
                                <h2>Estoques</h2>
                            </div>
                            <div class="card-body">
                                <div class="data-grid">
                                    <div class="data-field">
                                        <label class="data-field-label">Estoque Início Ano (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][estoque_inicio_ano]" value="<?= htmlspecialchars($empresa['estoque_inicio_ano'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">Estoque Fim Ano (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number" name="empresa[<?= $idx ?>][estoque_fim_ano]" value="<?= htmlspecialchars($empresa['estoque_fim_ano'] ?? '') ?>" min="0" step="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO 8: MERCADORIAS -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/external-xnimrodx-lineal-gradient-xnimrodx/64/external-contract-job-amp-resume-xnimrodx-lineal-gradient-xnimrodx.png" alt="external-contract-job-amp-resume-xnimrodx-lineal-gradient-xnimrodx"/>
                                <h2>Mercadorias Revendidas</h2>
                                <span class="badge badge-info">Máx. 10 itens</span>
                            </div>
                            <div class="card-body">
                                <table class="mercadorias-table">
                                    <thead>
                                        <tr>
                                            <th style="width:25%;">Código / NCM</th>
                                            <th style="width:55%;">Descrição</th>
                                            <th style="width:12%;">%</th>
                                            <th style="width:8%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($mercadorias)): ?>
                                            <tr>
                                                <td colspan="4" style="text-align:center;color:var(--gray-400);padding:1.5rem;">
                                                    Nenhuma mercadoria cadastrada.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($mercadorias as $item): ?>
                                                <tr>
                                                    <td><input type="text" name="empresa[<?= $idx ?>][mercadoria_codigo][]" class="form-input" value="<?= htmlspecialchars($item['codigo'] ?? $item['item'] ?? '') ?>" maxlength="20" placeholder="NCM"></td>
                                                    <td><input type="text" name="empresa[<?= $idx ?>][mercadoria_descricao][]" class="form-input" value="<?= htmlspecialchars($item['descricao'] ?? '') ?>" maxlength="200" placeholder="Descrição"></td>
                                                    <td><input type="number" name="empresa[<?= $idx ?>][mercadoria_percentual][]" class="form-input" value="<?= htmlspecialchars($item['percentual'] ?? '') ?>" min="0" max="100" style="width:80px" placeholder="%"></td>
                                                    <td><button type="button" class="btn-icon" onclick="this.closest('tr').remove()" title="Remover"><img width="20" height="20" src="https://img.icons8.com/nolan/64/waste.png" alt="waste"/></button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <div class="mercadorias-actions">
                                    <button type="button" data-add-mercadoria="1" class="btn btn-secondary btn-sm"><img width="20"height="20" src="https://img.icons8.com/nolan/64/add.png" alt="Adicionar Mercadoria">Adicionar Mercadoria</button>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- BLOCO: OUTRAS INFORMAÇÕES (posições 307-308) -->
                        <!-- ============================================================ -->
                        <div class="card company-section mt-2">
                            <div class="card-header">
                                <img width="30" height="30" src="https://img.icons8.com/external-xnimrodx-lineal-gradient-xnimrodx/64/external-contract-job-amp-resume-xnimrodx-lineal-gradient-xnimrodx.png" alt="external-contract-job-amp-resume-xnimrodx-lineal-gradient-xnimrodx"/>
                                <h2>Outras Informações — Posições 307 e 308</h2>
                            </div>
                            <div class="card-body">
                                <div class="data-grid">
                                    <div class="data-field">
                                        <label class="data-field-label">02A - Outras Formas de Ligação</label>
                                        <div class="data-field-value">
                                            <select name="empresa[<?= $idx ?>][outras_formas_ligacao]" class="form-input" style="background:white;">
                                                <option value="">-- Selecione --</option>
                                                <option value="00" <?= ($empresa['outras_formas_ligacao'] ?? '') === '00' ? 'selected' : '' ?>>00 - Não há outras formas de ligação</option>
                                                <option value="03" <?= ($empresa['outras_formas_ligacao'] ?? '') === '03' ? 'selected' : '' ?>>03 - Compartilhamento ou concentração de custos e despesas</option>
                                            </select>
                                            <div class="form-help">Posição 307 - Informe se a empresa possui outras formas de ligação (compartilhamento de custos, etc.)</div>
                                        </div>
                                    </div>
                                    <div class="data-field">
                                        <label class="data-field-label">098A - Depreciação de Arrendamento Operacional (R$)</label>
                                        <div class="data-field-value">
                                            <input type="number"
                                                name="empresa[<?= $idx ?>][depreciacao_arrendamento_operacional]"
                                                value="<?= htmlspecialchars($empresa['depreciacao_arrendamento_operacional'] ?? '') ?>"
                                                min="0" step="1" class="form-input"
                                                placeholder="0">
                                            <div class="form-help">Posição 308 - Depreciação e amortização de ativos decorrentes de arrendamento operacional</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /company-panel -->
                <?php endforeach; ?>

                <!-- ================================================================ -->
                <!-- BOTÕES DE AÇÃO -->
                <!-- ================================================================ -->
                <div class="mt-3" style="display:flex;gap:1rem;justify-content:space-between;align-items:center;flex-wrap:wrap;">
                    <div>
                        <label style="font-size:0.85rem;color:var(--gray-600);margin-right:0.5rem;">Ano-base:</label>
                        <select name="ano" class="form-input form-input-sm" style="display:inline-block;width:auto;">
                            <option value="2024" <?= $ano == 2024 ? 'selected' : '' ?>>2024</option>
                            <option value="2025" <?= $ano == 2025 ? 'selected' : '' ?>>2025</option>
                            <option value="2026" <?= $ano == 2026 ? 'selected' : '' ?>>2026</option>
                        </select>
                    </div>
                    <div style="display:flex;gap:0.75rem;">
                        <a href="?" class="btn btn-secondary">↩️ Voltar</a>
                        <button type="button" id="previewCSVBtn" class="btn btn-primary btn-lg">
                            👁️ Preview CSV
                        </button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <img src="https://img.icons8.com/color-glass/48/downloading-updates.png" alt="Gerar" width="20" height="20"> Gerar CSV (<?= $total ?> empresa<?= $total > 1 ? 's' : '' ?>)
                        </button>
                    </div>
                </div>

            </form>

            <!-- ================================================================ -->
            <!-- PREVIEW CSV MODAL -->
            <!-- ================================================================ -->
            <div id="previewModal" class="modal-overlay hidden">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2>👁️ Preview do CSV</h2>
                        <button type="button" class="modal-close" id="previewModalClose">&times;</button>
                    </div>
                    <div class="modal-body">
                        <!-- Summary -->
                        <div class="preview-summary">
                            <div class="preview-stat">
                                <span class="preview-stat-value" id="previewTotal">0</span>
                                <span class="preview-stat-label">Empresas</span>
                            </div>
                            <div class="preview-stat success">
                                <span class="preview-stat-value" id="previewValidas">0</span>
                                <span class="preview-stat-label"><img src="/assets/img/icons8-ok.gif" alt="OK" width="20" height="20" style="vertical-align: middle;"> Válidas</span>
                            </div>
                            <div class="preview-stat danger">
                                <span class="preview-stat-value" id="previewErros">0</span>
                                <span class="preview-stat-label"><img src="/assets/img/icons8-erro (1).gif" alt="Erro" width="20" height="20" style="vertical-align: middle;"> Com erros</span>
                            </div>
                            <div class="preview-stat">
                                <span class="preview-stat-value" id="previewAno">2025</span>
                                <span class="preview-stat-label">Ano-base</span>
                            </div>
                        </div>

                        <!-- Errors detail -->
                        <div id="previewErrorsContainer" class="hidden mt-2">
                            <div class="alert alert-warning">
                                <span class="alert-icon"><img src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20"></span>
                                <div>
                                    <strong>Empresas com pendências de validação:</strong>
                                    <div id="previewErrorsList"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Companies table -->
                        <div class="preview-table-wrapper mt-2">
                            <table class="preview-table" id="previewTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>CNPJ</th>
                                        <th>Razão Social</th>
                                        <th>UF</th>
                                        <th>CNAE</th>
                                        <th>Campos</th>
                                        <th>Econ.</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody">
                                    <tr>
                                        <td colspan="8" style="text-align:center;padding:2rem;color:var(--gray-400);">Carregando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Info about CSV format -->
                        <div class="preview-info mt-2">
                            <span>📄</span>
                            <span>
                                O CSV será gerado com <strong id="previewTotal2">0</strong> empresa(s) ·
                                <strong>306 colunas</strong> por linha ·
                                Codificação <strong>Windows-1252 (ANSI)</strong> ·
                                Delimitador <strong>;</strong> ·
                                <strong>Sem cabeçalho</strong>
                            </span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="previewModalCancel">↩️ Voltar e editar</button>
                        <button type="submit" class="btn btn-success btn-lg" id="previewModalConfirm">
                            <img src="https://img.icons8.com/color-glass/48/downloading-updates.png" alt="Confirmar" width="20" height="20"> Confirmar e Baixar CSV
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Toast de feedback -->
        <div id="toast-container" class="toast-container"></div>

        <?php if (!empty($_SESSION['flash_toast'])):
            $flash = $_SESSION['flash_toast'];
            unset($_SESSION['flash_toast']);
        ?>
            <script>
                (function() {
                    showToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type'] ?? 'success') ?>);
                })();
            </script>
        <?php endif; ?>

        <script src="/assets/script.js"></script>
    </body>

    </html>
<?php
}

// =========================================================================
// SALVAR e gerar CSV
// =========================================================================

function handleSalvar(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('?');
    }

    $empresasPost = $_POST['empresa'] ?? [];
    if (empty($empresasPost)) {
        renderHome('error', 'Nenhuma empresa enviada no formulário.');
        return;
    }

    $ano = $_POST['ano'] ?? 2025;
    $_SESSION['ano'] = $ano;

    $empresas = [];
    $mapeamento = PACValidator::getMapeamentoDireto();

    foreach ($empresasPost as $idx => $dados) {
        $empresa = $dados; // Captura TODOS os campos do formulário primeiro

        // Remove campos não-dados
        unset($empresa['mercadoria_codigo']);
        unset($empresa['mercadoria_descricao']);
        unset($empresa['mercadoria_percentual']);

        // Garante que campos em branco sejam string vazia
        foreach ($empresa as $chave => $valor) {
            if ($valor === null) {
                $empresa[$chave] = '';
            }
        }

        // Mercadorias (formato antigo: arrays paralelos)
        $codigos = $dados['mercadoria_codigo'] ?? [];
        $descricoes = $dados['mercadoria_descricao'] ?? [];
        $percentuais = $dados['mercadoria_percentual'] ?? [];

        $mercadorias = [];
        for ($i = 0; $i < count($codigos); $i++) {
            $cod = trim($codigos[$i] ?? '');
            $desc = trim($descricoes[$i] ?? '');
            if (!empty($cod) || !empty($desc)) {
                $mercadorias[] = [
                    'codigo' => $cod,
                    'descricao' => $desc,
                    'percentual' => (int)($percentuais[$i] ?? 0),
                ];
            }
        }
        $empresa['mercadorias'] = $mercadorias;

        // Preenche as 3 primeiras mercadorias como itens individuais (posições 56-58)
        for ($i = 0; $i < min(3, count($mercadorias)); $i++) {
            $desc = $mercadorias[$i]['descricao'] ?? $mercadorias[$i]['codigo'] ?? '';
            $empresa['mercadoria_item_' . ($i + 1)] = $desc;
        }

        // Garante que todas as chaves do mapeamento existam (compatibilidade)
        foreach ($mapeamento as $chave => $nomeColuna) {
            if (!isset($empresa[$chave]) || $empresa[$chave] === null) {
                $empresa[$chave] = '';
            }
        }

        $empresas[] = $empresa;
    }

    // Salva na sessão atualizada
    $_SESSION['empresas'] = $empresas;

    // Gera CSV
    try {
        $generator = new PACGenerator((string)$ano);

        // Nome do arquivo: pac_ano_totalempresas.csv
        $nomeArquivo = sprintf('pac_%s_%d_empresas.csv', $ano, count($empresas));

        // Histórico
        $nomes = array_map(fn($e) => mb_substr($e['razao_social'] ?? 'N/A', 0, 30), $empresas);
        $_SESSION['csv_history'][] = [
            'empresas' => implode(', ', $nomes),
            'arquivo' => $nomeArquivo,
            'total' => count($empresas),
        ];
        $_SESSION['csv_history'] = array_slice($_SESSION['csv_history'], -20);

        $generator->downloadCSV($empresas, $nomeArquivo);
    } catch (Exception $e) {
        renderHome('error', 'Erro ao gerar CSV: ' . $e->getMessage());
    }
}

// =========================================================================
// UPLOAD DADOS ECONÔMICOS (AJAX)
// =========================================================================

function handleUploadEconomico(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, null, 'Método não permitido.');
    }

    // Verifica upload via multipart (recomendado)
    $arquivo = $_FILES['arquivo_economico'] ?? null;
    $nomeOriginal = null;
    if ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK) {
        $caminho = $arquivo['tmp_name'];
        $nomeOriginal = $arquivo['name']; // Nome original para detectar extensão real
    } else {
        // Tenta receber caminho via POST (apenas arquivos temporários do sistema)
        $caminho = $_POST['caminho_arquivo'] ?? '';
        if (empty($caminho)) {
            jsonResponse(false, null, 'Nenhum arquivo enviado.');
        }
        // Validação de segurança: só permite arquivos temporários conhecidos
        $tempDir = sys_get_temp_dir();
        $caminhoReal = realpath($caminho);
        if ($caminhoReal === false || strpos($caminhoReal, $tempDir) !== 0) {
            jsonResponse(false, null, 'Caminho de arquivo inválido.');
        }
        if (!file_exists($caminhoReal) || !is_file($caminhoReal) || !is_readable($caminhoReal)) {
            jsonResponse(false, null, 'Arquivo não encontrado ou não pode ser lido.');
        }
        $caminho = $caminhoReal;
    }

    try {
        $resultado = PACDadosEconomicos::parse($caminho, $nomeOriginal);

        // Se é upload para empresa específica
        $empresaIdx = $_POST['empresa_idx'] ?? null;
        if ($empresaIdx !== null && isset($_SESSION['empresas'][$empresaIdx])) {
            $empresaIdx = (int)$empresaIdx;
            foreach ($resultado['dados'] as $chave => $valor) {
                $_SESSION['empresas'][$empresaIdx][$chave] = $valor;
            }
            // Auto-calcula Resultado do Exercício (códigos 103/104)
            $empCalc = $_SESSION['empresas'][$empresaIdx];
            $genCalc2 = new PACGenerator();
            if (empty($empCalc['resultado_lucro']) && empty($empCalc['resultado_prejuizo'])) {
                $r = $genCalc2->calcularResultado($empCalc);
                if ($r !== null) {
                    if (!empty($r['resultado_lucro'])) $_SESSION['empresas'][$empresaIdx]['resultado_lucro'] = $r['resultado_lucro'];
                    if (!empty($r['resultado_prejuizo'])) $_SESSION['empresas'][$empresaIdx]['resultado_prejuizo'] = $r['resultado_prejuizo'];
                }
            }
            jsonResponse(true, [
                'mensagem' => count($resultado['dados']) . ' campos econômicos importados para a empresa selecionada.',
                'dados' => $resultado['dados'],
                'total' => count($resultado['dados']),
                'codigos_nao_mapeados' => $resultado['codigos_nao_mapeados'],
            ]);
            return;
        }

        // Armazena os dados parseados na sessão para uso posterior (ex: editor)
        $_SESSION['ultimos_dados_economicos'] = $resultado['dados'];

        // Aplica os dados a TODAS as empresas na sessão
        $empresas = $_SESSION['empresas'] ?? [];
        $aplicadas = 0;
        $genCalc = new PACGenerator();
        foreach ($empresas as $i => &$emp) {
            foreach ($resultado['dados'] as $chave => $valor) {
                // Só preenche se estiver vazio (não sobrescreve dados manuais)
                if (empty($emp[$chave])) {
                    $emp[$chave] = $valor;
                }
            }
            $aplicadas++;

            // Auto-calcula Resultado do Exercício (códigos 103/104) só se vazio
            if (empty($emp['resultado_lucro']) && empty($emp['resultado_prejuizo'])) {
                $r = $genCalc->calcularResultado($emp);
                if ($r !== null) {
                    if (!empty($r['resultado_lucro'])) {
                        $emp['resultado_lucro'] = $r['resultado_lucro'];
                    }
                    if (!empty($r['resultado_prejuizo'])) {
                        $emp['resultado_prejuizo'] = $r['resultado_prejuizo'];
                    }
                }
            }
        }
        unset($emp);
        $_SESSION['empresas'] = $empresas;

        jsonResponse(true, [
            'mensagem' => count($resultado['dados']) . ' campos econômicos importados e aplicados a ' . $aplicadas . ' empresa(s).',
            'dados' => $resultado['dados'],
            'total' => count($resultado['dados']),
            'aplicadas' => $aplicadas,
            'codigos_nao_mapeados' => $resultado['codigos_nao_mapeados'],
        ]);
    } catch (Exception $e) {
        jsonResponse(false, null, $e->getMessage());
    }
}

// =========================================================================
// PREVIEW CSV (AJAX)
// =========================================================================

function handlePreviewCSV(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, null, 'Método não permitido.');
    }

    $empresasPost = $_POST['empresa'] ?? [];
    if (empty($empresasPost)) {
        jsonResponse(false, null, 'Nenhuma empresa enviada.');
    }

    $ano = $_POST['ano'] ?? 2025;
    $mapeamento = PACValidator::getMapeamentoDireto();
    $camposObrigatorios = PACValidator::getCamposObrigatorios();

    // Processa os dados igual ao handleSalvar
    $empresas = [];
    foreach ($empresasPost as $idx => $dados) {
        $empresa = $dados;
        unset($empresa['mercadoria_codigo'], $empresa['mercadoria_descricao'], $empresa['mercadoria_percentual']);
        foreach ($empresa as $chave => $valor) {
            if ($valor === null) $empresa[$chave] = '';
        }
        // Mercadorias
        $codigos = $dados['mercadoria_codigo'] ?? [];
        $descricoes = $dados['mercadoria_descricao'] ?? [];
        $percentuais = $dados['mercadoria_percentual'] ?? [];
        $mercadorias = [];
        for ($i = 0; $i < count($codigos); $i++) {
            $cod = trim($codigos[$i] ?? '');
            $desc = trim($descricoes[$i] ?? '');
            if (!empty($cod) || !empty($desc)) {
                $mercadorias[] = ['codigo' => $cod, 'descricao' => $desc, 'percentual' => (int)($percentuais[$i] ?? 0)];
            }
        }
        $empresa['mercadorias'] = $mercadorias;
        for ($i = 0; $i < min(3, count($mercadorias)); $i++) {
            $desc = $mercadorias[$i]['descricao'] ?? $mercadorias[$i]['codigo'] ?? '';
            $empresa['mercadoria_item_' . ($i + 1)] = $desc;
        }
        foreach ($mapeamento as $chave => $nomeColuna) {
            if (!isset($empresa[$chave]) || $empresa[$chave] === null) {
                $empresa[$chave] = '';
            }
        }
        $empresas[] = $empresa;
    }

    // Valida cada empresa
    $preview = [];
    $totalValidas = 0;
    $totalErros = 0;
    $errosDetalhados = [];

    foreach ($empresas as $i => $emp) {
        $validacao = PACValidator::validarEmpresa($emp, (string)$ano);
        $razao = $emp['razao_social'] ?? 'Sem nome';
        $cnpj = ($emp['cnpj_raiz'] ?? '') . '/' . ($emp['cnpj_sufixo'] ?? '') . '-' . ($emp['cnpj_dv'] ?? '');
        $uf = $emp['uf_sede'] ?? '';
        $cnae = $emp['cnae_principal'] ?? '';

        // Conta quantos campos econômicos foram preenchidos
        $econPreenchidos = 0;
        $econMap = PACDadosEconomicos::getCodigoEconomicoMap();
        foreach ($econMap as $cod => $chave) {
            if (!empty($emp[$chave])) $econPreenchidos++;
        }

        if ($validacao['valida']) {
            $totalValidas++;
        } else {
            $totalErros++;
            $errosDetalhados[] = [
                'empresa' => $razao,
                'cnpj' => $cnpj,
                'erros' => $validacao['erros'],
            ];
        }

        $preview[] = [
            'idx' => $i + 1,
            'razao' => mb_substr($razao, 0, 50),
            'cnpj' => $cnpj,
            'uf' => $uf,
            'cnae' => $cnae,
            'valida' => $validacao['valida'],
            'econ_preenchidos' => $econPreenchidos,
            'total_campos_obrig' => count($camposObrigatorios),
            'preenchidos' => count(array_filter($camposObrigatorios, fn($ch) => !empty($emp[$ch]))),
        ];
    }

    jsonResponse(true, [
        'total' => count($empresas),
        'validas' => $totalValidas,
        'erros' => $totalErros,
        'preview' => $preview,
        'erros_detalhados' => $errosDetalhados,
        'ano' => $ano,
    ]);
}

// =========================================================================
// OBTER DADOS ECONÔMICOS DA SESSÃO (AJAX)
// =========================================================================

function handleGetEconomicoData(): void
{
    $dados = $_SESSION['ultimos_dados_economicos'] ?? [];
    if (empty($dados)) {
        jsonResponse(false, null, 'Nenhum dado econômico importado na sessão. Faça upload primeiro.');
    }
    jsonResponse(true, [
        'dados' => $dados,
        'total' => count($dados),
    ]);
}

// =========================================================================
// TESTAR WK RADAR (AJAX)
// =========================================================================

// =========================================================================
// LIMPAR SESSÃO
// =========================================================================

function handleLimpar(): void
{
    // Remove dados de empresas e histórico
    unset($_SESSION['empresas']);
    unset($_SESSION['csv_history']);
    unset($_SESSION['ultimos_dados_economicos']);
    unset($_SESSION['ultimos_cnpjs']);

    // Redireciona para home
    header('Location: ?');
    exit;
}

// =========================================================================
// REMOVER EMPRESA INDIVIDUAL
// =========================================================================

function handleRemoverEmpresa(): void
{
    $idx = $_GET['idx'] ?? null;
    $empresas = $_SESSION['empresas'] ?? [];
    $voltar = $_GET['voltar'] ?? ''; // 'home' ou 'editor'

    if ($idx === null || !isset($empresas[(int)$idx])) {
        header('Location: ?');
        exit;
    }

    $idx = (int)$idx;
    $razao = $empresas[$idx]['razao'] ?? 'Empresa';
    array_splice($empresas, $idx, 1);

    if (empty($empresas)) {
        unset($_SESSION['empresas']);
    } else {
        $_SESSION['empresas'] = array_values($empresas); // re-indexa
    }

    // Salva flash message na sessão
    $_SESSION['flash_toast'] = [
        'message' => mb_substr($razao, 0, 60) . ' removida!',
        'type' => 'success'
    ];

    // Redireciona de volta para onde estava
    if ($voltar === 'editor') {
        // Se removeu a última empresa, vai pra home
        if (empty($empresas)) {
            header('Location: ?');
        } else {
            header('Location: ?action=editar');
        }
    } else {
        header('Location: ?');
    }
    exit;
}

// =========================================================================
// TESTAR WK
// =========================================================================

function handleTestarWK(): void
{
    $token = $_POST['wk_token'] ?? '';
    if (empty($token)) {
        jsonResponse(false, null, 'Token não informado.');
    }

    try {
        $wk = new WKRadar($token);
        $ano = (int)date('Y');
        $wk->obterBalancete($ano);
        $_SESSION['wk_token'] = $token;
        jsonResponse(true, ['message' => 'Conexão estabelecida com sucesso!']);
    } catch (Exception $e) {
        jsonResponse(false, null, $e->getMessage());
    }
}

// =========================================================================
// UTILITÁRIOS
// =========================================================================

function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(bool $success, $data = null, ?string $error = null): void
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}
