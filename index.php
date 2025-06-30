<?php
/**
 * Site War - Entry Point
 * 
 * Punto di ingresso principale dell'applicazione Site War.
 * Carica il template base e fornisce le funzionalità essenziali per l'interfaccia utente.
 * Implementa misure di sicurezza e ottimizzazione avanzate.
 */

// Percorso base dell'applicazione
define('BASE_PATH', __DIR__);

// Flag per modalità debug
define('DEBUG_MODE', true); 

// Caricamento librerie e utilità
require_once BASE_PATH . '/server/utils/Security.php';
require_once BASE_PATH . '/server/utils/Cache.php';

// Imposta i parametri di sicurezza della sessione
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Avvia sessione
session_start();

// Imposta header di sicurezza
Security::setSecurityHeaders([], true);

// Inizializza cache
$cache = new Cache();

// Pulisci la cache scaduta a intervalli (1% di probabilità per non sovraccaricare il server)
if (mt_rand(1, 100) === 1) {
    $cache->clearExpired();
}

// Rate limiting per proteggere da abusi
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!Security::checkRateLimit($clientIP, 60, 60)) { // 60 richieste in 60 secondi
    header('HTTP/1.1 429 Too Many Requests');
    header('Retry-After: 60');
    echo 'Troppe richieste. Riprova più tardi.';
    exit;
}

// Funzione per caricare un template
function loadTemplate($name, $variables = []) {
    if (is_array($variables)) {
        extract($variables);
    }
    
    $templatePath = BASE_PATH . '/templates/' . $name . '.php';
    
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        echo "<!-- Template not found: $name -->";
    }
}

// Genera token CSRF per form
$csrfToken = Security::generateCsrfToken(true); // true per utilizzare double-submit cookie

// Genera un nonce unico per CSP script-src
$cspNonce = Security::generateRandomString(16);
// Commentiamo questa intestazione per evitare conflitti con il file .htaccess
// header("Content-Security-Policy: script-src 'self' 'nonce-{$cspNonce}' https://cdn.jsdelivr.net");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site War - La Guerra dei Siti Web</title>
    
    <!-- Meta tag SEO -->
    <meta name="description" content="Site War confronta due siti web e determina il vincitore in base a performance, SEO, sicurezza e aspetti tecnici.">
    <meta name="keywords" content="web testing, confronto siti, SEO, performance web, sicurezza web">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- CSS personalizzato -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/print.css" media="print">
    
    <!-- Content Security Policy nonce per script inline -->
    <meta name="csp-nonce" content="<?php echo $cspNonce; ?>">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <header class="site-header py-3 text-center">
            <h1 class="display-4">Site War</h1>
            <p class="lead">Scopri quale sito vince la battaglia tecnica</p>
        </header>
        
        <main id="app" class="container position-relative">
            <!-- Form di input URL -->
            <section id="input-section" class="card p-4 mb-4 shadow-sm">
                <h2 class="card-title h4 mb-3">Inserisci i siti da confrontare</h2>
                
                <form id="battle-form" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="site1" class="form-label">Primo sito</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="url" class="form-control" id="site1" name="site1" 
                                       placeholder="https://esempio1.com" required>
                                <div class="invalid-feedback">
                                    Inserisci un URL valido.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end justify-content-center">
                            <span class="battle-vs h4 mt-2">VS</span>
                        </div>
                        
                        <div class="col-md-5">
                            <label for="site2" class="form-label">Secondo sito</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="url" class="form-control" id="site2" name="site2" 
                                       placeholder="https://esempio2.com" required>
                                <div class="invalid-feedback">
                                    Inserisci un URL valido.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-4">Inizia la battaglia!</button>
                    </div>
                </form>
            </section>
            
            <!-- Area animazione battaglia -->
            <section id="battle-section" class="card p-4 mb-4 shadow-sm d-none">
                <div class="battle-area position-relative">
                    <div class="row">
                        <div class="col-md-5 text-center site-container" id="site1-container">
                            <div class="site-placeholder rounded">
                                <h3 id="site1-name" class="site-name">Sito 1</h3>
                            </div>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <div class="battle-animation-container">
                                <!-- Qui andranno le animazioni di battaglia -->
                            </div>
                        </div>
                        
                        <div class="col-md-5 text-center site-container" id="site2-container">
                            <div class="site-placeholder rounded">
                                <h3 id="site2-name" class="site-name">Sito 2</h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress mt-4">
                        <div id="analysis-progress" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                    
                    <div id="battle-status" class="text-center mt-2">
                        In preparazione...
                    </div>
                </div>
            </section>
            
            <!-- Area risultati -->
            <section id="results-section" class="card p-4 mb-4 shadow-sm d-none">
                <h2 class="card-title h4 mb-4">Risultati della battaglia</h2>
                
                <!-- Informazioni sui siti analizzati -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header" id="site1-overview-header">Sito 1</div>
                            <div class="card-body">
                                <h5 class="card-title" id="site1-full-url">URL completo</h5>
                                <div class="site1-details">
                                    <p class="mb-1"><strong>Punteggio totale:</strong> <span id="site1-total-score">0</span>/100</p>
                                    <div class="progress mb-3">
                                        <div id="site1-score-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <p class="mb-1"><strong>Performance:</strong> <span id="site1-performance-score">0</span>/100</p>
                                    <p class="mb-1"><strong>SEO:</strong> <span id="site1-seo-score">0</span>/100</p>
                                    <p class="mb-1"><strong>Sicurezza:</strong> <span id="site1-security-score">0</span>/100</p>
                                    <p class="mb-0"><strong>Tecnica:</strong> <span id="site1-technical-score">0</span>/100</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header" id="site2-overview-header">Sito 2</div>
                            <div class="card-body">
                                <h5 class="card-title" id="site2-full-url">URL completo</h5>
                                <div class="site2-details">
                                    <p class="mb-1"><strong>Punteggio totale:</strong> <span id="site2-total-score">0</span>/100</p>
                                    <div class="progress mb-3">
                                        <div id="site2-score-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <p class="mb-1"><strong>Performance:</strong> <span id="site2-performance-score">0</span>/100</p>
                                    <p class="mb-1"><strong>SEO:</strong> <span id="site2-seo-score">0</span>/100</p>
                                    <p class="mb-1"><strong>Sicurezza:</strong> <span id="site2-security-score">0</span>/100</p>
                                    <p class="mb-0"><strong>Tecnica:</strong> <span id="site2-technical-score">0</span>/100</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Annuncio vincitore -->
                <div id="winner-announcement" class="alert shadow-sm text-center mb-4">
                    <div class="winner-badge mb-2">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <h3 class="winner-title">Vincitore: <span id="winner-name">Sito X</span></h3>
                    <p class="winner-score mb-1">Punteggio: <span id="winner-score">85</span>/100</p>
                    <p class="victory-level mb-0">Livello di vittoria: <span id="victory-level">Leggera</span></p>
                </div>
                
                <!-- Grafico di confronto principale -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Confronto delle categorie</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="category-chart" height="250"></canvas>
                            </div>
                            <div class="col-md-6">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Categoria</th>
                                                <th>Peso</th>
                                                <th id="site1-table-header">Sito 1</th>
                                                <th id="site2-table-header">Sito 2</th>
                                                <th>Diff.</th>
                                            </tr>
                                        </thead>
                                        <tbody id="overview-results">
                                            <!-- Qui verranno inseriti i risultati generali -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs per i dettagli -->
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="resultTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="performance-tab" data-bs-toggle="tab" 
                                        data-bs-target="#performance" type="button" role="tab">Performance</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="seo-tab" data-bs-toggle="tab" 
                                        data-bs-target="#seo" type="button" role="tab">SEO</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" 
                                        data-bs-target="#security" type="button" role="tab">Sicurezza</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="technical-tab" data-bs-toggle="tab" 
                                        data-bs-target="#technical" type="button" role="tab">Tecnico</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="details-tab" data-bs-toggle="tab" 
                                        data-bs-target="#details" type="button" role="tab">Dati dettagliati</button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <div class="tab-content" id="resultTabsContent">
                            <!-- Performance -->
                            <div class="tab-pane fade show active" id="performance" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h5>Metriche di performance</h5>
                                        <p class="text-muted small">I tempi più bassi indicano una migliore performance</p>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Metrica</th>
                                                <th id="site1-perf-header">Sito 1</th>
                                                <th id="site2-perf-header">Sito 2</th>
                                                <th>Migliore</th>
                                                <th>Differenza</th>
                                            </tr>
                                        </thead>
                                        <tbody id="performance-results">
                                            <!-- Qui verranno inseriti i risultati di performance -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="card h-100 border-light">
                                            <div class="card-header">Suggerimenti Performance - <span id="site1-perf-header2">Sito 1</span></div>
                                            <div class="card-body">
                                                <ul id="site1-perf-tips" class="list-group list-group-flush">
                                                    <!-- Suggerimenti per sito 1 -->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100 border-light">
                                            <div class="card-header">Suggerimenti Performance - <span id="site2-perf-header2">Sito 2</span></div>
                                            <div class="card-body">
                                                <ul id="site2-perf-tips" class="list-group list-group-flush">
                                                    <!-- Suggerimenti per sito 2 -->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SEO -->
                            <div class="tab-pane fade" id="seo" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h5>Metriche SEO</h5>
                                        <p class="text-muted small">Punteggi più alti indicano una migliore ottimizzazione</p>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Metrica</th>
                                                <th id="site1-seo-header">Sito 1</th>
                                                <th id="site2-seo-header">Sito 2</th>
                                                <th>Migliore</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody id="seo-results">
                                            <!-- Qui verranno inseriti i risultati SEO -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h6>Meta tag analizzati</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Tag</th>
                                                        <th id="site1-metatags-header">Sito 1</th>
                                                        <th id="site2-metatags-header">Sito 2</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="metatags-results">
                                                    <!-- Meta tags analizzati -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sicurezza -->
                            <div class="tab-pane fade" id="security" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h5>Metriche di sicurezza</h5>
                                        <p class="text-muted small">Punteggi più alti indicano una migliore sicurezza</p>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Metrica</th>
                                                <th id="site1-security-header">Sito 1</th>
                                                <th id="site2-security-header">Sito 2</th>
                                                <th>Migliore</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody id="security-results">
                                            <!-- Qui verranno inseriti i risultati sicurezza -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="card h-100 border-light">
                                            <div class="card-header">HTTP Security Headers - <span id="site1-sec-header">Sito 1</span></div>
                                            <div class="card-body">
                                                <ul id="site1-security-headers" class="list-group list-group-flush">
                                                    <!-- Headers di sicurezza per sito 1 -->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100 border-light">
                                            <div class="card-header">HTTP Security Headers - <span id="site2-sec-header">Sito 2</span></div>
                                            <div class="card-body">
                                                <ul id="site2-security-headers" class="list-group list-group-flush">
                                                    <!-- Headers di sicurezza per sito 2 -->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tecnico -->
                            <div class="tab-pane fade" id="technical" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h5>Metriche tecniche</h5>
                                        <p class="text-muted small">Valutazione degli aspetti tecnici dei siti web</p>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Metrica</th>
                                                <th id="site1-tech-header">Sito 1</th>
                                                <th id="site2-tech-header">Sito 2</th>
                                                <th>Migliore</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody id="technical-results">
                                            <!-- Qui verranno inseriti i risultati tecnici -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="card border-light">
                                            <div class="card-header">Tecnologie rilevate - <span id="site1-tech-header2">Sito 1</span></div>
                                            <div class="card-body">
                                                <ul id="site1-technologies" class="list-group list-group-flush">
                                                    <!-- Tecnologie per sito 1 -->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-light">
                                            <div class="card-header">Tecnologie rilevate - <span id="site2-tech-header2">Sito 2</span></div>
                                            <div class="card-body">
                                                <ul id="site2-technologies" class="list-group list-group-flush">
                                                    <!-- Tecnologie per sito 2 -->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dati dettagliati -->
                            <div class="tab-pane fade" id="details" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h5>Informazioni dettagliate</h5>
                                        <p class="text-muted small">Dati grezzi dell'analisi</p>
                                    </div>
                                </div>
                                
                                <div class="accordion" id="detailsAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#rawDataCollapse">
                                                Dati grezzi dell'analisi
                                            </button>
                                        </h2>
                                        <div id="rawDataCollapse" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <pre id="raw-data-json" class="bg-light p-3 rounded" style="max-height: 400px; overflow: auto;"></pre>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#metadataCollapse">
                                                Metadati dell'analisi
                                            </button>
                                        </h2>
                                        <div id="metadataCollapse" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <tbody id="metadata-table">
                                                            <!-- Metadati dell'analisi -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button id="export-results" class="btn btn-outline-secondary">
                        <i class="bi bi-download"></i> Esporta risultati (CSV)
                    </button>
                    <button id="restart-battle" class="btn btn-primary ms-2">
                        <i class="bi bi-arrow-repeat"></i> Nuova battaglia
                    </button>
                </div>
            </section>
        </main>
        
        <footer class="footer mt-auto py-3">
            <div class="container text-center">
                <span class="text-muted">Site War &copy; <?php echo date('Y'); ?> - Tutti i diritti riservati</span>
            </div>
        </footer>
    </div>
    
    <!-- Librerie JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/animejs@3.2.1/lib/anime.min.js"></script>
    
    <!-- Configurazione applicazione -->
    <?php loadTemplate('SiteWarApp'); ?>
    
    <!-- JavaScript applicazione -->
    <script src="assets/js/main.js"></script>
</body>
</html>