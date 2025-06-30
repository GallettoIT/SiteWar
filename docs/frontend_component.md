# Componente Frontend - Documentazione Tecnica

## 1. Panoramica

Il componente frontend di Site War è responsabile dell'interfaccia utente, della visualizzazione delle animazioni della "guerra tra siti" e della presentazione dei risultati all'utente. Questo componente sfrutta HTML5, CSS3, JavaScript e librerie come jQuery, Bootstrap, Chart.js e Anime.js per creare un'esperienza coinvolgente e interattiva.

## 2. Architettura del Frontend

### 2.1 Diagramma delle Classi
```
┌───────────────────┐
│  AppController    │
├───────────────────┤
│ - initApp()       │
│ - handleRouting() │
│ - loadModules()   │
└─────────┬─────────┘
          │
    ┌─────┴──────┬────────────┬────────────┬────────────┐
    │            │            │            │            │
    ▼            ▼            ▼            ▼            ▼
┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐
│ FormUI  │ │ BattleUI│ │ResultsUI│ │AnalysisUI│ │ExportUI │
├─────────┤ ├─────────┤ ├─────────┤ ├─────────┤ ├─────────┤
│- validate│ │- animate│ │- render │ │- progress│ │- saveCSV│
│- submit  │ │- update │ │- compare│ │- display │ │- print  │
└─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘
                │
                ▼
          ┌─────────────┐
          │AnimationEngine│
          ├─────────────┤
          │- particles() │
          │- effects()   │
          │- timeline()  │
          └─────────────┘
```

### 2.2 Moduli Principali

#### 2.2.1 AppController
Modulo centrale che inizializza l'applicazione, gestisce il routing e carica i moduli necessari.

#### 2.2.2 FormUI
Gestisce il form di inserimento degli URL, inclusa la validazione e la sottomissione.

#### 2.2.3 BattleUI
Responsabile delle animazioni che rappresentano la "guerra" tra i siti durante l'analisi.

#### 2.2.4 ResultsUI
Visualizza i risultati del confronto in modo chiaro e intuitivo, con grafici e tabelle.

#### 2.2.5 AnalysisUI
Mostra l'avanzamento dell'analisi e fornisce feedback in tempo reale all'utente.

#### 2.2.6 ExportUI
Permette all'utente di esportare e salvare i risultati dell'analisi.

#### 2.2.7 AnimationEngine
Motore per la creazione e gestione delle animazioni, basato su Anime.js e Particles.js.

## 3. Implementazione dell'Interfaccia Utente

### 3.1 Struttura HTML
```html
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site War - La battaglia tra siti web</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/animations.css">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="logo">Site War</div>
            <nav class="main-nav">
                <!-- Navigation -->
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <main id="app">
        <!-- Form Section -->
        <section id="form-section" class="section">
            <div class="container">
                <h1>Confronta due siti web in battaglia!</h1>
                <form id="site-war-form">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="site1">Sito 1:</label>
                                <input type="url" id="site1" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-2 text-center align-self-end">
                            <div class="vs-badge">VS</div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="site2">Sito 2:</label>
                                <input type="url" id="site2" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Inizia la battaglia!</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Battle Animation Section -->
        <section id="battle-section" class="section d-none">
            <div class="container">
                <div class="battle-arena">
                    <div id="site1-warrior" class="warrior left"></div>
                    <div id="battle-effects" class="effects-container"></div>
                    <div id="site2-warrior" class="warrior right"></div>
                </div>
                <div class="battle-progress">
                    <div class="progress-text">Analisi in corso...</div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Results Section -->
        <section id="results-section" class="section d-none">
            <div class="container">
                <h2 class="text-center mb-5">Risultato della battaglia</h2>
                <div class="winner-announcement">
                    <div id="winner-badge" class="badge"></div>
                    <h3 id="winner-name"></h3>
                </div>
                
                <div class="score-comparison">
                    <div class="row">
                        <div class="col-md-6">
                            <div id="site1-stats" class="site-stats"></div>
                        </div>
                        <div class="col-md-6">
                            <div id="site2-stats" class="site-stats"></div>
                        </div>
                    </div>
                </div>
                
                <div class="detailed-results mt-5">
                    <ul class="nav nav-tabs" id="resultsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="performance-tab" data-toggle="tab" href="#performance" role="tab">Performance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="seo-tab" data-toggle="tab" href="#seo" role="tab">SEO</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="security-tab" data-toggle="tab" href="#security" role="tab">Sicurezza</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="technical-tab" data-toggle="tab" href="#technical" role="tab">Aspetti tecnici</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="resultsTabsContent">
                        <div class="tab-pane fade show active" id="performance" role="tabpanel"></div>
                        <div class="tab-pane fade" id="seo" role="tabpanel"></div>
                        <div class="tab-pane fade" id="security" role="tabpanel"></div>
                        <div class="tab-pane fade" id="technical" role="tabpanel"></div>
                    </div>
                </div>
                
                <div class="actions mt-4 text-center">
                    <button id="new-battle-btn" class="btn btn-primary">Nuova battaglia</button>
                    <button id="export-results-btn" class="btn btn-secondary">Esporta risultati</button>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <p>© 2025 Site War - Tutti i diritti riservati</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/vendors/jquery.min.js"></script>
    <script src="assets/js/vendors/bootstrap.bundle.min.js"></script>
    <script src="assets/js/vendors/anime.min.js"></script>
    <script src="assets/js/vendors/particles.min.js"></script>
    <script src="assets/js/vendors/chart.min.js"></script>
    <script src="assets/js/vendors/papaparse.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/modules/ui/form.js"></script>
    <script src="assets/js/modules/ui/battle.js"></script>
    <script src="assets/js/modules/ui/results.js"></script>
    <script src="assets/js/modules/ui/analysis.js"></script>
    <script src="assets/js/modules/ui/export.js"></script>
    <script src="assets/js/modules/core/animation-engine.js"></script>
</body>
</html>
```

### 3.2 Stili CSS
I file CSS saranno organizzati in:
- `main.css`: Stili generali dell'applicazione
- `animations.css`: Animazioni specifiche per la battaglia
- `components/`: Stili per componenti specifici
- `vendors/`: CSS di terze parti

### 3.3 Pseudocodice Principale

#### 3.3.1 AppController (main.js)
```javascript
// Module Pattern
var SiteWarApp = (function() {
    // Variabili private
    var currentSection = 'form-section';
    var analysisResults = null;
    
    // Inizializzazione
    function init() {
        // Registrare gli event listeners
        $('#site-war-form').on('submit', FormUI.handleSubmit);
        $('#new-battle-btn').on('click', resetApp);
        $('#export-results-btn').on('click', ExportUI.exportResults);
        
        // Inizializzare i moduli
        FormUI.init();
        BattleUI.init();
        ResultsUI.init();
        AnalysisUI.init();
        ExportUI.init();
        
        // Registrarsi agli eventi custom
        $(document).on('analysis:complete', handleAnalysisComplete);
        $(document).on('analysis:progress', handleAnalysisProgress);
    }
    
    // Cambiare sezione attiva
    function showSection(sectionId) {
        $('#' + currentSection).addClass('d-none');
        $('#' + sectionId).removeClass('d-none');
        currentSection = sectionId;
    }
    
    // Gestire l'invio del form
    function handleFormSubmit(event) {
        event.preventDefault();
        
        var site1Url = $('#site1').val();
        var site2Url = $('#site2').val();
        
        if (FormUI.validateUrls(site1Url, site2Url)) {
            showSection('battle-section');
            startAnalysis(site1Url, site2Url);
        }
    }
    
    // Avviare l'analisi
    function startAnalysis(site1Url, site2Url) {
        // Resettare i risultati precedenti
        analysisResults = null;
        
        // Avviare l'animazione di battaglia
        BattleUI.startBattle(site1Url, site2Url);
        
        // Avviare l'analisi
        $.ajax({
            url: 'server/api/analyze.php',
            method: 'POST',
            data: {
                site1: site1Url,
                site2: site2Url
            },
            success: function(results) {
                analysisResults = results;
                $(document).trigger('analysis:complete', [results]);
            },
            error: function(xhr, status, error) {
                console.error('Analysis error:', error);
                // Gestire l'errore
                alert('Si è verificato un errore durante l\'analisi. Riprova.');
                resetApp();
            }
        });
        
        // Simulare aggiornamenti di progresso (nella versione reale, questi verrebbero dal server)
        simulateProgressUpdates();
    }
    
    // Simulare aggiornamenti di progresso (demo)
    function simulateProgressUpdates() {
        var progress = 0;
        var interval = setInterval(function() {
            progress += Math.random() * 5;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
            }
            $(document).trigger('analysis:progress', [progress]);
        }, 500);
    }
    
    // Gestire il completamento dell'analisi
    function handleAnalysisComplete(event, results) {
        // Attendere che l'animazione finisca
        setTimeout(function() {
            showSection('results-section');
            ResultsUI.displayResults(results);
        }, 2000);
    }
    
    // Gestire gli aggiornamenti di progresso
    function handleAnalysisProgress(event, progress) {
        AnalysisUI.updateProgress(progress);
        BattleUI.updateBattle(progress);
    }
    
    // Resettare l'applicazione
    function resetApp() {
        showSection('form-section');
        FormUI.reset();
        BattleUI.reset();
        AnalysisUI.reset();
        ResultsUI.reset();
    }
    
    // API pubblica
    return {
        init: init
    };
})();

// Inizializzare l'applicazione quando il documento è pronto
$(document).ready(function() {
    SiteWarApp.init();
});
```

#### 3.3.2 AnimationEngine (animation-engine.js)
```javascript
// Module Pattern
var AnimationEngine = (function() {
    // Configurazioni
    var config = {
        particles: {
            particlesPerSite: 50,
            colors: ['#ff4136', '#0074d9', '#ffdc00', '#2ecc40', '#b10dc9'],
            speed: 2,
            size: 3
        },
        effects: {
            explosionSize: 100,
            duration: 1000,
            easing: 'easeOutExpo'
        },
        battle: {
            phases: ['approach', 'clash', 'fight', 'victory']
        }
    };
    
    // Variabili private
    var particlesInstance = null;
    var timeline = null;
    var currentPhase = 'idle';
    var site1El = null;
    var site2El = null;
    var effectsEl = null;
    
    // Inizializzazione
    function init(site1Element, site2Element, effectsElement) {
        site1El = site1Element;
        site2El = site2Element;
        effectsEl = effectsElement;
        
        // Inizializzare Particles.js
        initParticles();
    }
    
    // Inizializzare Particles.js
    function initParticles() {
        if (particlesInstance) {
            particlesInstance.destroy();
        }
        
        particlesInstance = particlesJS('battle-effects', {
            particles: {
                number: {
                    value: config.particles.particlesPerSite * 2,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: config.particles.colors
                },
                shape: {
                    type: 'circle'
                },
                opacity: {
                    value: 0.5,
                    random: true
                },
                size: {
                    value: config.particles.size,
                    random: true
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#ffffff',
                    opacity: 0.2,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: config.particles.speed,
                    direction: 'none',
                    random: true,
                    straight: false,
                    out_mode: 'bounce'
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: {
                        enable: false
                    },
                    onclick: {
                        enable: false
                    },
                    resize: true
                }
            },
            retina_detect: true
        });
    }
    
    // Creare l'animazione della battaglia
    function createBattleAnimation(site1Url, site2Url) {
        // Preparare gli elementi
        $(site1El).attr('data-url', site1Url)
            .text(new URL(site1Url).hostname);
        
        $(site2El).attr('data-url', site2Url)
            .text(new URL(site2Url).hostname);
        
        // Creare la timeline con Anime.js
        timeline = anime.timeline({
            easing: 'easeOutExpo',
            duration: 2000,
            autoplay: false
        });
        
        // Fase di avvicinamento
        timeline.add({
            targets: site1El,
            translateX: '25%',
            rotate: '5deg',
            duration: 3000
        })
        .add({
            targets: site2El,
            translateX: '-25%',
            rotate: '-5deg',
            duration: 3000
        }, '-=3000');
        
        return timeline;
    }
    
    // Creare effetto esplosione
    function createExplosion(x, y, size, color) {
        var explosion = $('<div class="explosion"></div>');
        explosion.css({
            left: x + 'px',
            top: y + 'px',
            background: color || '#ff4136'
        });
        
        $(effectsEl).append(explosion);
        
        anime({
            targets: explosion[0],
            scale: [0, size || config.effects.explosionSize],
            opacity: [1, 0],
            duration: config.effects.duration,
            easing: config.effects.easing,
            complete: function() {
                explosion.remove();
            }
        });
    }
    
    // Aggiornare l'animazione in base al progresso
    function updateAnimation(progress) {
        // Determinare la fase attuale in base al progresso
        var phase;
        if (progress < 25) {
            phase = 'approach';
        } else if (progress < 50) {
            phase = 'clash';
        } else if (progress < 90) {
            phase = 'fight';
        } else {
            phase = 'victory';
        }
        
        // Se la fase è cambiata, aggiornare l'animazione
        if (phase !== currentPhase) {
            currentPhase = phase;
            
            switch (phase) {
                case 'approach':
                    timeline.play();
                    break;
                case 'clash':
                    // Creare effetto di scontro
                    createExplosion('50%', '50%', 150, '#ffdc00');
                    break;
                case 'fight':
                    // Durante la fase di combattimento, creare esplosioni casuali
                    startRandomExplosions();
                    break;
                case 'victory':
                    // Fermare le esplosioni casuali
                    stopRandomExplosions();
                    
                    // Effetto finale
                    createExplosion('50%', '50%', 200, '#2ecc40');
                    break;
            }
        }
    }
    
    // Variabile per intervallo esplosioni
    var explosionsInterval = null;
    
    // Avviare esplosioni casuali
    function startRandomExplosions() {
        if (explosionsInterval) {
            clearInterval(explosionsInterval);
        }
        
        explosionsInterval = setInterval(function() {
            var x = Math.random() * 100;
            var y = Math.random() * 100;
            var size = 30 + Math.random() * 50;
            var colorIndex = Math.floor(Math.random() * config.particles.colors.length);
            createExplosion(x + '%', y + '%', size, config.particles.colors[colorIndex]);
        }, 500);
    }
    
    // Fermare esplosioni casuali
    function stopRandomExplosions() {
        if (explosionsInterval) {
            clearInterval(explosionsInterval);
            explosionsInterval = null;
        }
    }
    
    // Resettare l'engine
    function reset() {
        if (timeline) {
            timeline.pause();
            timeline.seek(0);
        }
        
        stopRandomExplosions();
        
        // Resettare posizioni
        $(site1El).css({
            transform: 'translateX(0) rotate(0)',
            opacity: 1
        });
        
        $(site2El).css({
            transform: 'translateX(0) rotate(0)',
            opacity: 1
        });
        
        // Svuotare il container degli effetti
        $(effectsEl).empty();
        
        currentPhase = 'idle';
    }
    
    // API pubblica
    return {
        init: init,
        createBattleAnimation: createBattleAnimation,
        updateAnimation: updateAnimation,
        createExplosion: createExplosion,
        reset: reset
    };
})();
```

#### 3.3.3 ResultsUI (results.js)
```javascript
// Module Pattern
var ResultsUI = (function() {
    // Inizializzazione
    function init() {
        // Inizializzare i tab di Bootstrap
        $('#resultsTabs a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    }
    
    // Visualizzare i risultati
    function displayResults(results) {
        // Determinare il vincitore
        var winner = determineWinner(results);
        
        // Visualizzare l'annuncio del vincitore
        $('#winner-name').text(winner.name + ' ha vinto la battaglia!');
        $('#winner-badge').addClass(winner.site === 'site1' ? 'left-winner' : 'right-winner');
        
        // Visualizzare le statistiche generali
        displaySiteStats('site1-stats', results.site1, winner.site === 'site1');
        displaySiteStats('site2-stats', results.site2, winner.site === 'site2');
        
        // Visualizzare i risultati dettagliati
        displayDetailedResults(results);
        
        // Creare i grafici comparativi
        createComparisonCharts(results);
    }
    
    // Determinare il vincitore
    function determineWinner(results) {
        var site1Score = calculateTotalScore(results.site1);
        var site2Score = calculateTotalScore(results.site2);
        
        if (site1Score > site2Score) {
            return {
                site: 'site1',
                name: new URL(results.site1.url).hostname,
                score: site1Score
            };
        } else {
            return {
                site: 'site2',
                name: new URL(results.site2.url).hostname,
                score: site2Score
            };
        }
    }
    
    // Calcolare il punteggio totale
    function calculateTotalScore(siteResults) {
        return (
            siteResults.performance.score * 0.3 +
            siteResults.seo.score * 0.25 +
            siteResults.security.score * 0.25 +
            siteResults.technical.score * 0.2
        ).toFixed(2);
    }
    
    // Visualizzare le statistiche di un sito
    function displaySiteStats(containerId, siteResults, isWinner) {
        var container = $('#' + containerId);
        container.empty();
        
        var totalScore = calculateTotalScore(siteResults);
        
        var html = `
            <div class="site-header ${isWinner ? 'winner' : ''}">
                <h4>${new URL(siteResults.url).hostname}</h4>
                <div class="total-score">${totalScore}</div>
            </div>
            <div class="score-breakdown">
                <div class="score-item">
                    <div class="score-label">Performance</div>
                    <div class="score-value">${siteResults.performance.score}</div>
                </div>
                <div class="score-item">
                    <div class="score-label">SEO</div>
                    <div class="score-value">${siteResults.seo.score}</div>
                </div>
                <div class="score-item">
                    <div class="score-label">Sicurezza</div>
                    <div class="score-value">${siteResults.security.score}</div>
                </div>
                <div class="score-item">
                    <div class="score-label">Aspetti tecnici</div>
                    <div class="score-value">${siteResults.technical.score}</div>
                </div>
            </div>
        `;
        
        container.html(html);
    }
    
    // Visualizzare i risultati dettagliati
    function displayDetailedResults(results) {
        // Performance
        displayPerformanceResults(results);
        
        // SEO
        displaySEOResults(results);
        
        // Sicurezza
        displaySecurityResults(results);
        
        // Aspetti tecnici
        displayTechnicalResults(results);
    }
    
    // Visualizzare i risultati di performance
    function displayPerformanceResults(results) {
        var container = $('#performance');
        container.empty();
        
        // Creare la tabella comparativa
        var html = `
            <div class="comparison-table">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Metrica</th>
                            <th>${new URL(results.site1.url).hostname}</th>
                            <th>${new URL(results.site2.url).hostname}</th>
                            <th>Vincitore</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>First Contentful Paint</td>
                            <td>${results.site1.performance.fcp} ms</td>
                            <td>${results.site2.performance.fcp} ms</td>
                            <td>${results.site1.performance.fcp < results.site2.performance.fcp ? '⭐ Sito 1' : '⭐ Sito 2'}</td>
                        </tr>
                        <tr>
                            <td>Largest Contentful Paint</td>
                            <td>${results.site1.performance.lcp} ms</td>
                            <td>${results.site2.performance.lcp} ms</td>
                            <td>${results.site1.performance.lcp < results.site2.performance.lcp ? '⭐ Sito 1' : '⭐ Sito 2'}</td>
                        </tr>
                        <tr>
                            <td>Time to Interactive</td>
                            <td>${results.site1.performance.tti} ms</td>
                            <td>${results.site2.performance.tti} ms</td>
                            <td>${results.site1.performance.tti < results.site2.performance.tti ? '⭐ Sito 1' : '⭐ Sito 2'}</td>
                        </tr>
                        <tr>
                            <td>Cumulative Layout Shift</td>
                            <td>${results.site1.performance.cls}</td>
                            <td>${results.site2.performance.cls}</td>
                            <td>${results.site1.performance.cls < results.site2.performance.cls ? '⭐ Sito 1' : '⭐ Sito 2'}</td>
                        </tr>
                        <tr>
                            <td>Total Page Size</td>
                            <td>${formatSize(results.site1.performance.totalSize)}</td>
                            <td>${formatSize(results.site2.performance.totalSize)}</td>
                            <td>${results.site1.performance.totalSize < results.site2.performance.totalSize ? '⭐ Sito 1' : '⭐ Sito 2'}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <h5>Grafico Comparativo Performance</h5>
                    <canvas id="performance-chart"></canvas>
                </div>
            </div>
        `;
        
        container.html(html);
        
        // Creare il grafico
        createPerformanceChart(results);
    }
    
    // Formattare la dimensione in KB o MB
    function formatSize(bytes) {
        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }
    }
    
    // Creare il grafico di performance
    function createPerformanceChart(results) {
        var ctx = document.getElementById('performance-chart').getContext('2d');
        
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['FCP (ms)', 'LCP (ms)', 'TTI (ms)', 'CLS x 1000', 'Size (KB)'],
                datasets: [
                    {
                        label: new URL(results.site1.url).hostname,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1,
                        data: [
                            results.site1.performance.fcp,
                            results.site1.performance.lcp,
                            results.site1.performance.tti,
                            results.site1.performance.cls * 1000,
                            results.site1.performance.totalSize / 1024
                        ]
                    },
                    {
                        label: new URL(results.site2.url).hostname,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgb(255, 99, 132)',
                        borderWidth: 1,
                        data: [
                            results.site2.performance.fcp,
                            results.site2.performance.lcp,
                            results.site2.performance.tti,
                            results.site2.performance.cls * 1000,
                            results.site2.performance.totalSize / 1024
                        ]
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Metriche di Performance'
                    }
                }
            }
        });
    }
    
    // Implementare le altre funzioni di visualizzazione per SEO, Sicurezza e Aspetti tecnici
    function displaySEOResults(results) {
        // Simile a displayPerformanceResults ma per SEO
    }
    
    function displaySecurityResults(results) {
        // Simile a displayPerformanceResults ma per Sicurezza
    }
    
    function displayTechnicalResults(results) {
        // Simile a displayPerformanceResults ma per Aspetti tecnici
    }
    
    // Creare i grafici comparativi generali
    function createComparisonCharts(results) {
        // Implementare i grafici radar o a torta per il confronto generale
    }
    
    // Resettare l'UI dei risultati
    function reset() {
        $('#winner-name').text('');
        $('#winner-badge').removeClass('left-winner right-winner');
        $('#site1-stats').empty();
        $('#site2-stats').empty();
        $('#performance').empty();
        $('#seo').empty();
        $('#security').empty();
        $('#technical').empty();
    }
    
    // API pubblica
    return {
        init: init,
        displayResults: displayResults,
        reset: reset
    };
})();
```

## 4. Animazioni

### 4.1 Concetto "Guerra tra Siti"
Le animazioni rappresentano visivamente la "guerra" tra i due siti web, utilizzando effetti che riflettono la qualità tecnica dei siti analizzati.

### 4.2 Fasi della Battaglia
1. **Approccio (0-25%)**:
   - I siti si avvicinano l'uno all'altro
   - Effetti particellari iniziali
   - Preparazione alla battaglia

2. **Scontro (25-50%)**:
   - Primo "impatto" tra i siti
   - Esplosione iniziale
   - Effetti visivi intensificati

3. **Combattimento (50-90%)**:
   - Scambio di "colpi"
   - Esplosioni multiple
   - Effetti particellari intensi
   - I risultati parziali influenzano l'andamento della battaglia

4. **Vittoria (90-100%)**:
   - Effetto finale di vittoria
   - Il sito vincitore "sovrasta" l'altro
   - Transizione verso la schermata dei risultati

### 4.3 Tecnologie di Animazione
- **Anime.js**: Per animazioni fluide e timeline
- **Particles.js**: Per effetti particellari
- **CSS Animations**: Per transizioni di base
- **Canvas**: Per effetti più complessi

## 5. Interfaccia di Visualizzazione Risultati

### 5.1 Dashboard Principale
La dashboard principale presenta:
- Il vincitore della battaglia
- Punteggi complessivi per entrambi i siti
- Grafici comparativi delle principali categorie
- Opzioni per visualizzare dettagli specifici

### 5.2 Visualizzazione per Categoria
Ogni categoria (Performance, SEO, Sicurezza, Aspetti tecnici) ha una visualizzazione dedicata con:
- Tabella comparativa dei valori specifici
- Grafici che evidenziano le differenze
- Indicatori visivi del vincitore per ogni metrica
- Suggerimenti per miglioramenti

### 5.3 Sistema di Punteggio Visivo
- Uso di colori per indicare buone (verde) e cattive (rosso) metriche
- Badge per il vincitore di ogni categoria
- Visualizzazione proporzionale dei punteggi

## 6. Responsive Design

### 6.1 Principi di Design
- Layout fluido basato su Bootstrap
- Media queries per adattare l'interfaccia a diverse dimensioni di schermo
- Approccio mobile-first per garantire una buona esperienza su dispositivi mobili

### 6.2 Ottimizzazioni per Mobile
- Visualizzazione semplificata delle animazioni su dispositivi mobili
- Grafici adattivi che si ridimensionano in base allo schermo
- Navigazione touch-friendly
- Loading ottimizzato per connessioni più lente

## 7. Accessibilità

### 7.1 Conformità WCAG 2.1 AA
- Contrasto adeguato per testo e elementi UI
- Etichette ARIA per componenti interattivi
- Focus visibile per navigazione da tastiera
- Testo alternativo per elementi visivi

### 7.2 Compatibilità con Screen Reader
- Markup semantico
- Annunci dinamici per aggiornamenti UI
- Ordine di tabulazione logico
- Descrizioni per grafici e visualizzazioni

## 8. Performance

### 8.1 Ottimizzazioni
- Lazy loading per componenti non essenziali
- Minimizzazione e compressione di CSS e JavaScript
- Caricamento asincrono delle librerie
- Utilizzo di sprites CSS per icone

### 8.2 Monitoraggio Performance
- Tracciamento dei tempi di rendering
- Ottimizzazione delle animazioni per dispositivi meno potenti
- Rilevamento automatico delle capacità del dispositivo