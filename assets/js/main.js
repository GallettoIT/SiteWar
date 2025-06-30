/**
 * Site War - Main JavaScript
 * 
 * File principale che carica e inizializza tutti i moduli dell'applicazione.
 * Implementa il pattern Module per organizzare il codice.
 */

// Namespace principale dell'applicazione
const SiteWar = {};

// Costanti di configurazione
const CONFIG = {
  // Configurazione per le chiamate API e timeouts
  API: {
    MAX_POLLING_ATTEMPTS: 30,       // Numero massimo di tentativi di polling
    POLLING_INTERVAL: 2000,         // Intervallo di polling normale (2 secondi)
    RETRY_INTERVAL: 5000,           // Intervallo di retry in caso di errore (5 secondi)
    TIMEOUT_THRESHOLD: 120000,      // Soglia di timeout complessiva (2 minuti)
    RECOVERY_MODE: true,            // Abilita il recupero automatico delle analisi parziali
    PARTIAL_RESULTS_THRESHOLD: 60   // Se almeno il 60% delle metriche è disponibile, mostra i risultati parziali
  },
  
  // Mappature per accedere uniformemente ai dati indipendentemente dalla struttura
  METRICS_PATHS: {
    performance: {
      first_contentful_paint: [
        // Nuova struttura dati con metrics
        'metrics.performance.first_contentful_paint',
        'metrics.performance.localMetrics.timeToFirstByte',
        // Struttura vecchia API
        'performance.performance_localMetrics.timeToFirstByte',
        'performance.timeToFirstByte'
      ],
      largest_contentful_paint: [
        // Nuova struttura dati con metrics
        'metrics.performance.largest_contentful_paint',
        'metrics.performance.localMetrics.loadTime',
        // Struttura vecchia API
        'performance.performance_localMetrics.loadTime',
        'performance.loadTime'
      ],
      time_to_interactive: [
        // Nuova struttura dati con metrics
        'metrics.performance.time_to_interactive',
        'metrics.performance.localMetrics.loadTime',
        // Struttura vecchia API
        'performance.performance_localMetrics.loadTime',
        'performance.timeToInteractive'
      ],
      cumulative_layout_shift: [
        // Nuova struttura dati con metrics
        'metrics.performance.cumulative_layout_shift',
        'metrics.performance.cumulativeLayoutShift',
        // Struttura vecchia API
        'performance.performance_pageSpeed.metrics.cumulativeLayoutShift',
        'performance.cumulativeLayoutShift'
      ]
    },
    seo: {
      meta_title: [
        // Nuova struttura dati con metrics
        'metrics.seo.meta_title',
        'metrics.seo.metaTags.score',
        'metrics.seo.metaTags.titleLength',
        // Struttura vecchia API
        'seo.seo_metaTags.score',
        'seo.seo_metaTags.titleLength',
        'seo.metaTags.score'
      ],
      meta_description: [
        // Nuova struttura dati con metrics
        'metrics.seo.meta_description',
        'metrics.seo.metaTags.score',
        // Struttura vecchia API
        'seo.seo_metaTags.score',
        'seo.metaTags.score'
      ],
      headings_structure: [
        // Nuova struttura dati con metrics
        'metrics.seo.headings_structure',
        'metrics.seo.headings.score',
        // Struttura vecchia API
        'seo.seo_headings.score',
        'seo.headings.score'
      ],
      alt_tags: [
        // Nuova struttura dati con metrics
        'metrics.seo.alt_tags',
        'metrics.seo.images.altPercentage',
        'metrics.seo.images.score',
        // Struttura vecchia API
        'seo.seo_images.altPercentage',
        'seo.seo_images.score',
        'seo.images.altPercentage'
      ],
      url_structure: [
        // Nuova struttura dati con metrics
        'metrics.seo.url_structure',
        'metrics.seo.url.score',
        // Struttura vecchia API
        'seo.seo_url.score',
        'seo.url.score'
      ]
    },
    security: {
      ssl_grade: [
        // Nuova struttura dati con metrics
        'metrics.security.ssl_grade',
        'metrics.security.ssl.certificate.issuer',
        'metrics.security.ssl.score',
        // Struttura vecchia API
        'security.security_ssl.certificate.issuer',
        'security.security_ssl.score',
        'security.ssl.score'
      ],
      headers_score: [
        // Nuova struttura dati con metrics
        'metrics.security.headers_score',
        'metrics.security.securityHeaders.score',
        // Struttura vecchia API
        'security.security_securityHeaders.score',
        'security.securityHeaders.score'
      ],
      vulnerabilities: [
        // Nuova struttura dati con metrics
        'metrics.security.vulnerabilities',
        'metrics.security.vulnerabilities.count',
        // Struttura vecchia API
        'security.security_vulnerabilities.count',
        'security.vulnerabilities.count'
      ]
    },
    technical: {
      html_validation: [
        // Nuova struttura dati con metrics
        'metrics.technical.html_validation',
        'metrics.technical.htmlValidation.score',
        // Struttura vecchia API
        'technical.html_validation.score',
        'technical.htmlValidation.score'
      ],
      css_validation: [
        // Nuova struttura dati con metrics
        'metrics.technical.css_validation',
        'metrics.technical.cssValidation.score',
        // Struttura vecchia API
        'technical.css_validation.score',
        'technical.cssValidation.score'
      ],
      technologies: [
        // Nuova struttura dati con metrics
        'metrics.technical.technologies',
        'metrics.technical.technologies.count',
        // Struttura vecchia API
        'technical.technologies.count',
        'technical.technologiesCount'
      ]
    }
  },
  // Metriche dove valori più bassi sono migliori
  LOWER_IS_BETTER_METRICS: [
    'first_contentful_paint',
    'largest_contentful_paint',
    'time_to_interactive',
    'cumulative_layout_shift',
    'vulnerabilities'
  ]
};

// Modulo principale dell'applicazione (Application Controller)
SiteWar.App = (function() {
  // Riferimenti privati ai moduli utilizzati
  let eventBus;
  let formUI;
  let battleUI;
  let resultsUI;
  let apiConnector;
  
  // Stato dell'applicazione
  const state = {
    site1: null,
    site2: null,
    analysisInProgress: false,
    analysisId: null,
    results: null
  };
  
  // Inizializzazione dell'applicazione
  function init() {
    console.log('Initializing Site War application...');
    
    // Inizializza i moduli
    initModules();
    
    // Registra gli event listener
    registerEventListeners();
    
    console.log('Site War application initialized successfully.');
  }
  
  // Inizializzazione di tutti i moduli
  function initModules() {
    // EventBus per la comunicazione tra moduli
    eventBus = SiteWar.EventBus;
    eventBus.init();
    
    // Moduli UI
    formUI = SiteWar.FormUI;
    formUI.init();
    
    battleUI = SiteWar.BattleUI;
    battleUI.init();
    
    resultsUI = SiteWar.ResultsUI;
    resultsUI.init();
    
    // Connettore API
    apiConnector = SiteWar.APIConnector;
    apiConnector.init();
  }
  
  // Registrazione degli event listener
  function registerEventListeners() {
    // Evento di invio del form
    eventBus.subscribe('form:submit', handleFormSubmit);
    
    // Eventi di analisi
    eventBus.subscribe('analysis:start', handleAnalysisStart);
    eventBus.subscribe('analysis:progress', handleAnalysisProgress);
    eventBus.subscribe('analysis:complete', handleAnalysisComplete);
    eventBus.subscribe('analysis:error', handleAnalysisError);
    
    // Eventi UI
    eventBus.subscribe('ui:restart', handleRestart);
    eventBus.subscribe('ui:export', handleExportResults);
  }
  
  // Handler per l'invio del form
  function handleFormSubmit(data) {
    console.log('Form submitted with data:', data);
    
    // Salva i dati del form nello stato
    state.site1 = data.site1;
    state.site2 = data.site2;
    state.analysisInProgress = true;
    state.analysisId = null;
    
    // Mostra la UI di battaglia
    battleUI.show();
    
    // Nasconde il form
    formUI.hide();
    
    // Prima valida gli URL
    validateUrls(data.site1, data.site2);
  }
  
  // Funzione che valida gli URL
  function validateUrls(site1, site2) {
    apiConnector.validateUrls(site1, site2)
      .then(response => {
        console.log('URL validation successful');
        
        // Se gli URL sono validi, avvia l'analisi
        startAnalysis(site1, site2);
      })
      .catch(error => {
        console.error('URL validation error:', error);
        
        // Mostra l'errore
        eventBus.publish('analysis:error', { 
          message: error.message || 'URL non validi o non raggiungibili' 
        });
      });
  }
  
  // Avvia l'analisi dei siti
  function startAnalysis(site1, site2) {
    console.log(`Starting analysis for ${site1} vs ${site2}`);
    
    // Notifica gli altri moduli
    eventBus.publish('analysis:start', { site1, site2 });
    
    // Avvia l'analisi tramite apiConnector
    apiConnector.analyzeUrls(site1, site2)
      .then(response => {
        console.log('Analysis response:', response);
        
        // Gestisci diversi tipi di risposta
        let responseData = response.data || response;
        
        if (responseData.analysisId && !responseData.complete) {
          // L'analisi è stata avviata in modo asincrono, salva l'ID
          state.analysisId = responseData.analysisId;
          
          // Aggiorna lo stato dell'interfaccia
          battleUI.updateBattleStatus(responseData.message || 'Analisi in corso...');
          
          // Avvia il polling per verificare lo stato dell'analisi
          waitForAnalysisCompletion();
        } else if (responseData.complete && responseData.results) {
          // L'analisi è stata completata immediatamente
          console.log('Analysis completed successfully');
          
          // Salva i risultati
          state.results = responseData.results;
          state.analysisInProgress = false;
          
          // Notifica gli altri moduli
          eventBus.publish('analysis:complete', responseData.results);
        } else {
          throw new Error(responseData.message || 'Formato di risposta non valido');
        }
      })
      .catch(error => {
        console.error('Analysis error:', error);
        
        // Aggiorna lo stato
        state.analysisInProgress = false;
        
        // Notifica gli altri moduli
        eventBus.publish('analysis:error', { 
          message: error.message || 'Errore durante l\'analisi' 
        });
      });
  }
  
  // Funzione che controlla lo stato dell'analisi finché non è completa
  function waitForAnalysisCompletion(pollAttempt = 0, startTime = Date.now()) {
    // Se non è in corso un'analisi, esci
    if (!state.analysisInProgress || !state.analysisId) {
      return;
    }
    
    // Controllo timeout generale dell'analisi
    const elapsedTime = Date.now() - startTime;
    if (pollAttempt >= CONFIG.API.MAX_POLLING_ATTEMPTS || elapsedTime > CONFIG.API.TIMEOUT_THRESHOLD) {
      console.warn(`Analysis timeout reached after ${elapsedTime}ms and ${pollAttempt} attempts`);
      
      if (CONFIG.API.RECOVERY_MODE) {
        // Tenta di recuperare l'analisi parziale
        handleAnalysisRecovery();
      } else {
        // Notifica l'errore di timeout
        eventBus.publish('analysis:error', { 
          message: 'L\'analisi è scaduta. Prova di nuovo o aumenta il limite di tempo.' 
        });
      }
      return;
    }
    
    console.log(`Checking analysis completion status... (Attempt ${pollAttempt + 1}/${CONFIG.API.MAX_POLLING_ATTEMPTS})`);
    
    // Attendi tra le richieste
    setTimeout(() => {
      // Richiedi lo stato dell'analisi
      apiConnector.analyzeUrls(null, null, state.analysisId)
        .then(response => {
          console.log('Analysis status response:', response);
          
          // Verifica la risposta
          if (response.status !== 'success') {
            throw new Error(response.message || 'Errore durante il controllo dello stato');
          }
          
          let responseData = response.data || response;
          
          // Aggiorna l'UI con lo stato
          let progress = responseData.progress || 0;
          let message = responseData.message || 'Analisi in corso...';
          
          battleUI.updateBattleStatus(message);
          battleUI.updateProgress(progress);
          
          // Pubblica evento di progresso
          eventBus.publish('analysis:progress', { 
            status: 'in_progress', 
            progress: progress,
            message: message,
            pollAttempt: pollAttempt,
            elapsedTime: elapsedTime
          });
          
          // Se l'analisi è completa, mostra i risultati
          if (responseData.complete === true && responseData.results) {
            console.log('Analysis completed successfully');
            
            // Salva i risultati
            state.results = responseData.results;
            state.analysisInProgress = false;
            
            // Notifica gli altri moduli
            eventBus.publish('analysis:complete', responseData.results);
          } 
          // Se l'analisi è parzialmente completata
          else if (responseData.status === 'completed_partial' && responseData.results) {
            console.log('Analysis partially completed');
            
            // Verifica se i risultati sono sufficientemente completi
            const completionRate = calculateAnalysisCompletionRate(responseData.results);
            if (completionRate >= CONFIG.API.PARTIAL_RESULTS_THRESHOLD) {
              console.log(`Partial results have sufficient completion rate (${completionRate}%)`);
              
              // Aggiorna lo status
              state.results = responseData.results;
              state.analysisInProgress = false;
              
              // Notifica gli altri moduli con risultati parziali
              eventBus.publish('analysis:complete', responseData.results, true);
            } else {
              console.log(`Partial results have insufficient completion rate (${completionRate}%)`);
              // Continua il polling per ottenere risultati più completi
              waitForAnalysisCompletion(pollAttempt + 1, startTime);
            }
          }
          // Altrimenti, continua il polling
          else {
            waitForAnalysisCompletion(pollAttempt + 1, startTime);
          }
        })
        .catch(error => {
          console.error('Analysis status check error:', error);
          // Riprova dopo un po' con un intervallo più lungo
          setTimeout(() => waitForAnalysisCompletion(pollAttempt + 1, startTime), CONFIG.API.RETRY_INTERVAL);
        });
    }, CONFIG.API.POLLING_INTERVAL);
  }
  
  // Calcola la percentuale di completamento dell'analisi in base ai dati disponibili
  function calculateAnalysisCompletionRate(results) {
    // Definisci i componenti chiave che dovrebbero essere presenti in un'analisi completa
    const keyComponents = {
      site1: ['performanceScore', 'seoScore', 'securityScore', 'technicalScore', 'totalScore'],
      site2: ['performanceScore', 'seoScore', 'securityScore', 'technicalScore', 'totalScore'],
      comparison: true,
      winner: true
    };
    
    let totalComponents = 0;
    let availableComponents = 0;
    
    // Verifica i componenti di site1 e site2
    for (const site of ['site1', 'site2']) {
      if (!results[site]) continue;
      
      for (const component of keyComponents[site]) {
        totalComponents++;
        if (results[site][component] !== undefined) {
          availableComponents++;
        }
      }
    }
    
    // Verifica la presenza della sezione comparison
    if (keyComponents.comparison) {
      totalComponents++;
      if (results.comparison) {
        availableComponents++;
      }
    }
    
    // Verifica la presenza del vincitore
    if (keyComponents.winner) {
      totalComponents++;
      if (results.winner) {
        availableComponents++;
      }
    }
    
    // Calcola la percentuale di completamento
    return totalComponents > 0 ? (availableComponents / totalComponents) * 100 : 0;
  }
  
  // Gestisce il recupero dell'analisi in caso di timeout
  function handleAnalysisRecovery() {
    console.log('Attempting to recover analysis after timeout');
    
    // Richiedi lo stato corrente dell'analisi un'ultima volta
    apiConnector.analyzeUrls(null, null, state.analysisId)
      .then(response => {
        const responseData = response.data || response;
        
        // Verifica se ci sono almeno alcuni risultati parziali
        if (responseData.results) {
          console.log('Partial results found, attempting recovery');
          
          // Completa i risultati mancanti con valori predefiniti per evitare errori
          const recoveredResults = ensureCompleteResults(responseData.results);
          
          // Aggiorna lo stato con i risultati recuperati
          state.results = recoveredResults;
          state.analysisInProgress = false;
          
          // Mostra un messaggio di avviso
          battleUI.updateBattleStatus('Analisi completata parzialmente. Alcuni dati potrebbero essere incompleti.');
          
          // Notifica i moduli con i risultati parziali
          eventBus.publish('analysis:complete', recoveredResults, true);
        } else {
          // Nessun risultato utilizzabile, mostra un errore
          eventBus.publish('analysis:error', { 
            message: 'L\'analisi è scaduta e non è stato possibile recuperare risultati parziali. Prova di nuovo.' 
          });
        }
      })
      .catch(error => {
        console.error('Analysis recovery error:', error);
        eventBus.publish('analysis:error', { 
          message: 'Errore durante il recupero dell\'analisi: ' + error.message 
        });
      });
  }
  
  // Assicura che l'oggetto risultati abbia tutte le proprietà necessarie
  function ensureCompleteResults(partialResults) {
    // Crea una deep copy dei risultati parziali
    const results = JSON.parse(JSON.stringify(partialResults));
    
    // Assicura che le proprietà di base esistano
    results.url1 = results.url1 || state.site1;
    results.url2 = results.url2 || state.site2;
    results.site1 = results.site1 || { url: results.url1 || state.site1 };
    results.site2 = results.site2 || { url: results.url2 || state.site2 };
    
    // Assicura che site1.url e site2.url esistano
    results.site1.url = results.site1.url || results.url1 || state.site1;
    results.site2.url = results.site2.url || results.url2 || state.site2;
    
    // Assicura che i punteggi esistano
    const categories = ['performance', 'seo', 'security', 'technical'];
    categories.forEach(category => {
      const scoreKey = `${category}Score`;
      
      // Per site1
      results.site1[scoreKey] = results.site1[scoreKey] !== undefined ? 
        results.site1[scoreKey] : (results.site1[category]?.aggregatedScore || 0);
      
      // Per site2
      results.site2[scoreKey] = results.site2[scoreKey] !== undefined ? 
        results.site2[scoreKey] : (results.site2[category]?.aggregatedScore || 0);
    });
    
    // Calcola punteggi totali se mancanti
    results.site1.totalScore = results.site1.totalScore || 
      ((results.site1.performanceScore || 0) * 0.3 + 
       (results.site1.seoScore || 0) * 0.25 + 
       (results.site1.securityScore || 0) * 0.25 + 
       (results.site1.technicalScore || 0) * 0.2);
    
    results.site2.totalScore = results.site2.totalScore || 
      ((results.site2.performanceScore || 0) * 0.3 + 
       (results.site2.seoScore || 0) * 0.25 + 
       (results.site2.securityScore || 0) * 0.25 + 
       (results.site2.technicalScore || 0) * 0.2);
    
    // Determina il vincitore se mancante
    if (!results.winner) {
      results.winner = results.site1.totalScore > results.site2.totalScore ? 'site1' : 'site2';
    }
    
    // Crea una sezione comparison di base se mancante
    if (!results.comparison) {
      results.comparison = {
        statistics: {
          winsByCategory: {},
          winsByMetric: {},
          advantagesByCategory: {},
          significantAdvantages: {}
        }
      };
    }
    
    // Aggiungi metadati di recupero
    if (!results.metadata) {
      results.metadata = {};
    }
    results.metadata.recoveryMode = true;
    results.metadata.recoveryTimestamp = Date.now();
    
    // Segna i risultati come parziali
    results.isPartial = true;
    
    return results;
  }
  
  // Handler per l'avvio dell'analisi
  function handleAnalysisStart(data) {
    // Aggiorna i dettagli dei siti nella UI di battaglia
    battleUI.updateSiteDetails(data.site1, data.site2);
    
    // Inizializza la barra di progresso
    battleUI.updateProgress(5);
    
    // Aggiorna lo stato
    battleUI.updateBattleStatus('Avvio dell\'analisi...');
  }
  
  // Handler per l'aggiornamento dell'avanzamento
  function handleAnalysisProgress(progressData) {
    // Aggiorna la barra di progresso
    battleUI.updateProgress(progressData.progress);
    
    // Aggiorna l'animazione di battaglia in base allo stato
    battleUI.updateBattleAnimation(progressData);
  }
  
  // Handler per il completamento dell'analisi
  function handleAnalysisComplete(results, isPartial = false) {
    // Logging avanzato per debug della struttura dati
    console.log("=== ANALYSIS RESULTS STRUCTURE ===");
    console.log("Chiavi principali:", Object.keys(results));
    console.log("Chiavi site1:", Object.keys(results.site1));
    console.log("Chiavi site2:", Object.keys(results.site2));
    console.log("Chiavi comparison:", Object.keys(results.comparison));
    console.log("Risultati parziali:", isPartial);
    
    // Analisi dettagliata dei metrics
    if (results.site1.metrics) {
      console.log("=== SITE1 METRICS ===");
      console.log("Metrics keys:", Object.keys(results.site1.metrics));
      
      // Performance metrics
      if (results.site1.metrics.performance) {
        console.log("Performance keys:", Object.keys(results.site1.metrics.performance));
        
        // Log di metriche specifiche
        ["localMetrics", "pageSpeed", "resourceHints", "renderBlocking", "imageOptimization", "caching"].forEach(key => {
          if (results.site1.metrics.performance[key]) {
            console.log(`${key} keys:`, Object.keys(results.site1.metrics.performance[key]));
          }
        });
      }
      
      // SEO metrics
      if (results.site1.metrics.seo) {
        console.log("SEO keys:", Object.keys(results.site1.metrics.seo));
      }
      
      // Security metrics
      if (results.site1.metrics.security) {
        console.log("Security keys:", Object.keys(results.site1.metrics.security));
      }
    }
    
    console.log("================================");
    
    // Nasconde l'UI di battaglia
    battleUI.hide();
    
    // Se i risultati sono parziali, mostra un avviso
    if (isPartial) {
      const partialNotice = document.createElement('div');
      partialNotice.className = 'alert alert-warning alert-dismissible fade show';
      partialNotice.innerHTML = `
        <strong>Attenzione!</strong> Alcuni dati potrebbero essere incompleti a causa del tempo richiesto dall'analisi.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
      
      // Ottieni il container dei risultati
      const resultsContainer = document.getElementById('results-container');
      if (resultsContainer) {
        // Inserisci l'avviso all'inizio del container
        resultsContainer.insertBefore(partialNotice, resultsContainer.firstChild);
      }
    }
    
    // Mostra l'UI dei risultati
    resultsUI.show(results);
  }
  
  // Handler per gli errori durante l'analisi
  function handleAnalysisError(errorData) {
    // Determina il tipo di errore
    const errorMsg = errorData.message || 'Si è verificato un errore durante l\'analisi';
    const isRelevanceError = errorMsg.toLowerCase().includes('non è rilevante') || 
                          errorMsg.toLowerCase().includes('non pertinent');
    
    // Mostra un tipo di errore diverso per errori di pertinenza
    if (isRelevanceError) {
      battleUI.showRelevanceError(errorMsg);
    } else {
      // Mostra un errore generico
      battleUI.showError(errorMsg);
    }
    
    // Consenti all'utente di riprovare
    setTimeout(() => {
      formUI.show();
      battleUI.hide();
    }, 4000);
  }
  
  // Handler per il restart dell'applicazione
  function handleRestart() {
    // Reset dello stato
    state.analysisInProgress = false;
    state.results = null;
    
    // Nascondi i risultati
    resultsUI.hide();
    
    // Mostra il form
    formUI.show();
  }
  
  // Handler per l'esportazione dei risultati
  function handleExportResults() {
    if (!state.results) {
      return;
    }
    
    // Genera un CSV dai risultati
    const csvData = generateCSV(state.results);
    
    // Crea un link di download
    const blob = new Blob([csvData], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = `site-war-results-${Date.now()}.csv`;
    
    // Aggiunge l'elemento al DOM, clicca, e poi rimuove
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  }
  
  // Funzione per generare un CSV dai risultati
  function generateCSV(results) {
    const site1 = extractDomain(results.site1.url || results.url1);
    const site2 = extractDomain(results.site2.url || results.url2);
    
    // Estrai punteggi per categoria
    const site1Perf = results.site1.performanceScore || 0;
    const site2Perf = results.site2.performanceScore || 0;
    
    const site1Seo = results.site1.seoScore || 0;
    const site2Seo = results.site2.seoScore || 0;
    
    const site1Security = results.site1.securityScore || 0;
    const site2Security = results.site2.securityScore || 0;
    
    const site1Technical = results.site1.technicalScore || 0;
    const site2Technical = results.site2.technicalScore || 0;
    
    const site1Total = results.site1.totalScore || 0;
    const site2Total = results.site2.totalScore || 0;
    
    // Costruisci il CSV
    let csv = `"Categoria","${site1}","${site2}","Vincitore"\n`;
    csv += `"Performance","${site1Perf}","${site2Perf}","${site1Perf > site2Perf ? site1 : site2}"\n`;
    csv += `"SEO","${site1Seo}","${site2Seo}","${site1Seo > site2Seo ? site1 : site2}"\n`;
    csv += `"Sicurezza","${site1Security}","${site2Security}","${site1Security > site2Security ? site1 : site2}"\n`;
    csv += `"Aspetti Tecnici","${site1Technical}","${site2Technical}","${site1Technical > site2Technical ? site1 : site2}"\n`;
    csv += `"Totale","${site1Total}","${site2Total}","${site1Total > site2Total ? site1 : site2}"\n`;
    
    return csv;
  }
  
  // Funzione utility per estrarre il dominio da un URL
  function extractDomain(url) {
    if (!url) return '';
    
    // Rimuovi il protocollo e prendi solo l'hostname
    return url.replace(/^https?:\/\//, '').split('/')[0];
  }
  
  // Esponi l'API pubblica
  return {
    init,
    restart: handleRestart,
    export: handleExportResults
  };
})();

// Modulo EventBus per la comunicazione tra moduli
SiteWar.EventBus = (function() {
  // Memorizza tutti gli abbonamenti
  const subscriptions = {};
  
  // Inizializzazione
  function init() {
    console.log('EventBus initialized');
  }
  
  // Abbonamento a un evento
  function subscribe(event, callback) {
    if (!subscriptions[event]) {
      subscriptions[event] = [];
    }
    
    subscriptions[event].push(callback);
  }
  
  // Pubblicazione di un evento
  function publish(event, data) {
    if (!subscriptions[event]) {
      return;
    }
    
    subscriptions[event].forEach(callback => {
      try {
        callback(data);
      } catch (error) {
        console.error(`Error in event handler for ${event}:`, error);
      }
    });
  }
  
  // Disabbonamento da un evento
  function unsubscribe(event, callback) {
    if (!subscriptions[event]) {
      return;
    }
    
    subscriptions[event] = subscriptions[event].filter(cb => cb !== callback);
  }
  
  // Esponi l'API pubblica
  return {
    init,
    subscribe,
    publish,
    unsubscribe
  };
})();

// Modulo FormUI per la gestione dell'interfaccia del form
SiteWar.FormUI = (function() {
  // Elementi DOM
  let formElement;
  let site1Input;
  let site2Input;
  let submitButton;
  let loadingElement;
  let formContainer;
  
  // Riferimenti ad altri moduli
  let eventBus;
  
  // Inizializzazione
  function init() {
    // Ottieni riferimento a EventBus
    eventBus = SiteWar.EventBus;
    
    // Ottieni riferimenti agli elementi DOM
    formElement = document.getElementById('battle-form'); // Corretto il selettore
    site1Input = document.getElementById('site1'); // Corretto il selettore
    site2Input = document.getElementById('site2'); // Corretto il selettore
    submitButton = document.querySelector('button[type="submit"]'); // Usa un selettore più generico
    loadingElement = document.getElementById('loading-indicator');
    formContainer = document.getElementById('input-section'); // Corretto il selettore per il form container
    
    console.log("Form element found:", formElement);
    console.log("Site1 input found:", site1Input);
    console.log("Site2 input found:", site2Input);
    console.log("Submit button found:", submitButton);
    
    // Registra gli event listener
    if (formElement) {
      console.log("Attaching submit event listener to form");
      formElement.addEventListener('submit', handleSubmit);
    } else {
      console.error("Form element not found! Cannot attach submit event listener");
    }
    
    console.log('FormUI initialized');
  }
  
  // Handler per il submit del form
  function handleSubmit(event) {
    event.preventDefault();
    
    console.log("Form submit handler called");
    
    // Valida gli input
    const site1 = site1Input.value.trim();
    const site2 = site2Input.value.trim();
    
    if (!validateUrl(site1) || !validateUrl(site2)) {
      showError('Inserisci due URL validi');
      return;
    }
    
    // Mostra loading
    if (loadingElement) {
      loadingElement.style.display = 'block';
    }
    
    // Disabilita il form durante l'elaborazione
    if (submitButton) {
      submitButton.disabled = true;
    }
    
    console.log("Publishing form:submit event with:", { site1, site2 });
    
    // Pubblica l'evento di submit del form
    eventBus.publish('form:submit', { site1, site2 });
  }
  
  // Valida un URL
  function validateUrl(url) {
    // Aggiungi http:// se non presente
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://' + url;
    }
    
    try {
      new URL(url);
      return true;
    } catch (e) {
      return false;
    }
  }
  
  // Mostra un messaggio di errore
  function showError(message) {
    const errorElement = document.getElementById('form-error');
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = 'block';
      
      // Nascondi dopo 3 secondi
      setTimeout(() => {
        errorElement.style.display = 'none';
      }, 3000);
    }
  }
  
  // Mostra il form
  function show() {
    console.log("FormUI.show() called");
    if (formContainer) {
      formContainer.classList.remove('d-none');
      console.log("Form container shown");
    } else {
      // Se formContainer non esiste, proviamo con il parent del form
      if (formElement && formElement.parentElement) {
        formElement.parentElement.classList.remove('d-none');
        console.log("Form parent element shown");
      } else {
        console.error("Form container not found!");
      }
    }
    
    // Reset del form
    if (formElement) {
      formElement.reset();
    }
    
    // Reset dello stato UI
    if (submitButton) {
      submitButton.disabled = false;
    }
    
    if (loadingElement) {
      loadingElement.style.display = 'none';
    }
  }
  
  // Nascondi il form
  function hide() {
    console.log("FormUI.hide() called");
    if (formContainer) {
      formContainer.classList.add('d-none');
      console.log("Form container hidden");
    } else {
      // Se formContainer non esiste, proviamo con il parent del form
      if (formElement && formElement.parentElement) {
        formElement.parentElement.classList.add('d-none');
        console.log("Form parent element hidden");
      }
    }
  }
  
  // Esponi l'API pubblica
  return {
    init,
    show,
    hide
  };
})();

// Modulo BattleUI per la gestione dell'interfaccia della battaglia
SiteWar.BattleUI = (function() {
  // Elementi DOM
  let battleContainer;
  let site1Element;
  let site2Element;
  let progressBar;
  let statusElement;
  let errorContainer;
  
  // Riferimenti ad altri moduli
  let eventBus;
  
  // Inizializzazione
  function init() {
    // Ottieni riferimento a EventBus
    eventBus = SiteWar.EventBus;
    
    // Ottieni riferimenti agli elementi DOM
    battleContainer = document.getElementById('battle-section'); // Corretto il selettore
    site1Element = document.getElementById('site1-name');
    site2Element = document.getElementById('site2-name');
    progressBar = document.getElementById('analysis-progress');
    statusElement = document.getElementById('battle-status');
    errorContainer = document.getElementById('error-container');
    
    console.log("BattleUI elements:", {
      battleContainer, 
      site1Element, 
      site2Element, 
      progressBar, 
      statusElement, 
      errorContainer
    });
    
    console.log('BattleUI initialized');
  }
  
  // Mostra l'interfaccia di battaglia
  function show() {
    console.log("BattleUI.show() called, battleContainer:", battleContainer);
    if (battleContainer) {
      battleContainer.classList.remove('d-none'); // Rimuovi la classe d-none invece di impostare display
      console.log("Battle container display class removed");
    } else {
      console.error("Battle container not found!");
    }
    
    // Nascondi eventuali errori precedenti
    if (errorContainer) {
      errorContainer.style.display = 'none';
    }
    
    // Reset della barra di progresso
    updateProgress(0);
    
    // Reset dello stato
    updateBattleStatus('Inizializzazione battaglia...');
  }
  
  // Nascondi l'interfaccia di battaglia
  function hide() {
    if (battleContainer) {
      battleContainer.classList.add('d-none'); // Aggiungi la classe d-none per coerenza
      console.log("Battle container hidden");
    }
  }
  
  // Aggiorna i dettagli dei siti
  function updateSiteDetails(site1, site2) {
    const site1Domain = extractDomain(site1);
    const site2Domain = extractDomain(site2);
    
    if (site1Element) {
      site1Element.textContent = site1Domain;
    }
    
    if (site2Element) {
      site2Element.textContent = site2Domain;
    }
  }
  
  // Aggiorna lo stato della battaglia
  function updateBattleStatus(message) {
    if (statusElement) {
      statusElement.textContent = message;
    }
  }
  
  // Aggiorna la barra di progresso
  function updateProgress(percent) {
    if (progressBar) {
      progressBar.style.width = `${percent}%`;
      progressBar.setAttribute('aria-valuenow', percent);
    }
  }
  
  // Aggiorna l'animazione di battaglia
  function updateBattleAnimation(progressData) {
    // TODO: Implementare animazioni basate sul progresso
    // Questo potrebbe includere effetti di particelle, spostamento di elementi, ecc.
  }
  
  // Mostra un errore
  function showError(message) {
    if (errorContainer) {
      const errorElement = document.getElementById('error-message');
      if (errorElement) {
        errorElement.textContent = message;
      }
      
      errorContainer.style.display = 'block';
      battleContainer.style.display = 'none';
    }
  }
  
  // Mostra un errore di pertinenza
  function showRelevanceError(message) {
    if (errorContainer) {
      const errorElement = document.getElementById('error-message');
      if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('relevance-error');
      }
      
      errorContainer.style.display = 'block';
      battleContainer.style.display = 'none';
      
      // Reset della classe dopo la visualizzazione
      setTimeout(() => {
        if (errorElement) {
          errorElement.classList.remove('relevance-error');
        }
      }, 4000);
    }
  }
  
  // Funzione utility per estrarre il dominio da un URL
  function extractDomain(url) {
    if (!url) return '';
    
    // Rimuovi il protocollo e prendi solo l'hostname
    return url.replace(/^https?:\/\//, '').split('/')[0];
  }
  
  // Esponi l'API pubblica
  return {
    init,
    show,
    hide,
    updateSiteDetails,
    updateBattleStatus,
    updateProgress,
    updateBattleAnimation,
    showError,
    showRelevanceError
  };
})();

// Modulo ResultsUI per la gestione dell'interfaccia dei risultati
SiteWar.ResultsUI = (function() {
  // Elementi DOM
  let resultsContainer;
  let winnerName;
  let winnerScore;
  let restartButton;
  let exportButton;
  let tabs;
  let tabContents;
  
  // Riferimenti ad altri moduli
  let eventBus;
  
  // Grafici
  let charts = {};
  
  // Inizializzazione
  function init() {
    // Ottieni riferimento a EventBus
    eventBus = SiteWar.EventBus;
    
    // Ottieni riferimenti agli elementi DOM
    resultsContainer = document.getElementById('results-container');
    winnerName = document.getElementById('winner-name');
    winnerScore = document.getElementById('winner-score');
    restartButton = document.getElementById('restart-button');
    exportButton = document.getElementById('export-button');
    
    // Ottieni i tab e i contenuti dei tab
    tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabContents = document.querySelectorAll('.tab-pane');
    
    // Registra gli event listener
    if (restartButton) {
      restartButton.addEventListener('click', handleRestart);
    }
    
    if (exportButton) {
      exportButton.addEventListener('click', handleExport);
    }
    
    // Inizializza i tab
    tabs.forEach(tab => {
      tab.addEventListener('shown.bs.tab', handleTabChange);
    });
    
    console.log('ResultsUI initialized');
  }
  
  // Mostra i risultati
  function show(results) {
    if (resultsContainer) {
      resultsContainer.style.display = 'block';
      
      // Popola i risultati
      populateResults(results);
    }
  }
  
  // Nascondi i risultati
  function hide() {
    if (resultsContainer) {
      resultsContainer.style.display = 'none';
    }
  }
  
  // Handler per il cambio di tab
  function handleTabChange(event) {
    // Aggiorna i grafici quando il tab diventa visibile
    const tabId = event.target.getAttribute('href') || event.target.dataset.bsTarget;
    
    // Trova i canvas nel tab appena mostrato
    const canvases = document.querySelectorAll(`${tabId} canvas`);
    
    // Aggiorna i grafici nel tab
    canvases.forEach(canvas => {
      const chartId = canvas.id;
      if (charts[chartId]) {
        charts[chartId].update();
      }
    });
  }
  
  // Handler per il restart
  function handleRestart() {
    eventBus.publish('ui:restart');
  }
  
  // Handler per l'esportazione
  function handleExport() {
    eventBus.publish('ui:export');
  }
  
  // Popola i risultati
  function populateResults(results) {
    // Standardizza i risultati per un accesso uniforme
    const standardizedResults = standardizeResults(results);
    
    // Determina il vincitore
    const winnerKey = standardizedResults.winner === 'site1' ? 'site1' : 'site2';
    const winnerDomain = extractDomain(standardizedResults[winnerKey].url);
    const winnerTotalScore = standardizedResults[winnerKey].totalScore;
    
    // Aggiorna l'interfaccia con i dettagli del vincitore
    if (winnerName) winnerName.textContent = winnerDomain;
    if (winnerScore) winnerScore.textContent = Math.round(winnerTotalScore);
    
    // Mostra badge dei risultati parziali se applicabile
    if (standardizedResults.isPartial) {
      appendPartialResultsBadge(winnerName?.parentElement);
    }
    
    // Aggiorna le intestazioni delle tabelle
    updateTableHeaders(
      standardizedResults.site1.url, 
      standardizedResults.site2.url
    );
    
    // Popola le tabelle di risultati
    populateOverviewTable(standardizedResults);
    populateCategoryTables(standardizedResults);
    
    // Crea grafici
    createCharts(standardizedResults);
  }
  
  // Aggiunge un badge "Dati parziali" accanto al nome del vincitore
  function appendPartialResultsBadge(element) {
    if (!element) return;
    
    const badge = document.createElement('span');
    badge.className = 'badge bg-warning ms-2';
    badge.textContent = 'Dati parziali';
    badge.title = 'Alcuni dati potrebbero essere incompleti a causa di timeout durante l\'analisi';
    badge.style.fontSize = '0.6rem';
    badge.style.verticalAlign = 'middle';
    
    element.appendChild(badge);
  }
  
  // Standardizza i risultati per un accesso uniforme
  function standardizeResults(results) {
    // Crea una deep copy dei risultati per evitare modifiche all'originale
    const standardized = JSON.parse(JSON.stringify(results));
    
    // Assicurati che url1/url2 e site1.url/site2.url siano consistenti
    if (standardized.url1 && !standardized.site1.url) {
      standardized.site1.url = standardized.url1;
    }
    
    if (standardized.url2 && !standardized.site2.url) {
      standardized.site2.url = standardized.url2;
    }
    
    // Se winner non è definito, determinalo dai punteggi
    if (!standardized.winner) {
      const site1Score = standardized.site1.totalScore;
      const site2Score = standardized.site2.totalScore;
      standardized.winner = site1Score > site2Score ? 'site1' : 'site2';
    }
    
    return standardized;
  }
  
  // Aggiorna le intestazioni delle tabelle con i nomi dei siti
  function updateTableHeaders(site1Url, site2Url) {
    // Ottieni i nomi di dominio
    const site1Domain = extractDomain(site1Url);
    const site2Domain = extractDomain(site2Url);
    
    // Aggiorna tutte le intestazioni
    const headers = [
      'site1-overview-header', 'site2-overview-header',
      'site1-perf-header', 'site2-perf-header',
      'site1-seo-header', 'site2-seo-header',
      'site1-security-header', 'site2-security-header',
      'site1-tech-header', 'site2-tech-header'
    ];
    
    headers.forEach(id => {
      const element = document.getElementById(id);
      if (element) {
        element.textContent = id.includes('site1') ? site1Domain : site2Domain;
      }
    });
  }
  
  // Popola la tabella di panoramica
  function populateOverviewTable(results) {
    const tableBody = document.getElementById('overview-results');
    if (!tableBody) return;
    
    // Svuota la tabella
    tableBody.innerHTML = '';
    
    // Definizione delle categorie
    const categories = [
      { id: 'performance', name: 'Performance', weight: '30%' },
      { id: 'seo', name: 'SEO', weight: '25%' },
      { id: 'security', name: 'Sicurezza', weight: '25%' },
      { id: 'technical', name: 'Aspetti Tecnici', weight: '20%' }
    ];
    
    // Aggiungi righe per ogni categoria
    categories.forEach(category => {
      const site1Score = results.site1[`${category.id}Score`] || 0;
      const site2Score = results.site2[`${category.id}Score`] || 0;
      
      console.log(`Categoria ${category.id}: site1=${site1Score}, site2=${site2Score}`);
      
      const winner = site1Score > site2Score ? 'site1' : 'site2';
      
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${category.name}</td>
        <td>${category.weight}</td>
        <td class="${winner === 'site1' ? 'text-site1 fw-bold' : ''}">${Math.round(site1Score)}</td>
        <td class="${winner === 'site2' ? 'text-site2 fw-bold' : ''}">${Math.round(site2Score)}</td>
        <td>${winner === 'site1' ? extractDomain(results.site1.url) : extractDomain(results.site2.url)}</td>
      `;
      
      tableBody.appendChild(row);
    });
    
    // Aggiungi riga per il punteggio totale
    const row = document.createElement('tr');
    row.classList.add('table-active', 'fw-bold');
    
    const site1TotalScore = results.site1.totalScore || 0;
    const site2TotalScore = results.site2.totalScore || 0;
    
    console.log(`Punteggi totali: site1=${site1TotalScore}, site2=${site2TotalScore}`);
    
    row.innerHTML = `
      <td>Punteggio Totale</td>
      <td>100%</td>
      <td class="${results.winner === 'site1' ? 'text-site1' : ''}">${Math.round(site1TotalScore)}</td>
      <td class="${results.winner === 'site2' ? 'text-site2' : ''}">${Math.round(site2TotalScore)}</td>
      <td>${results.winner === 'site1' ? extractDomain(results.site1.url) : extractDomain(results.site2.url)}</td>
    `;
    
    tableBody.appendChild(row);
  }
  
  // Popola le tabelle delle singole categorie
  function populateCategoryTables(results) {
    // Definizione delle tabelle e dei relativi dati
    const categoryTables = [
      {
        id: 'performance-results',
        metrics: [
          { id: 'first_contentful_paint', name: 'First Contentful Paint', format: value => formatTime(value) },
          { id: 'largest_contentful_paint', name: 'Largest Contentful Paint', format: value => formatTime(value) },
          { id: 'time_to_interactive', name: 'Time to Interactive', format: value => formatTime(value) },
          { id: 'cumulative_layout_shift', name: 'Cumulative Layout Shift', format: value => value?.toFixed(2) || 'N/A' }
        ]
      },
      {
        id: 'seo-results',
        metrics: [
          { id: 'meta_title', name: 'Meta Title' },
          { id: 'meta_description', name: 'Meta Description' },
          { id: 'headings_structure', name: 'Headings Structure' },
          { id: 'alt_tags', name: 'Alt Tags' },
          { id: 'url_structure', name: 'URL Structure' }
        ]
      },
      {
        id: 'security-results',
        metrics: [
          { id: 'ssl_grade', name: 'SSL Grade' },
          { id: 'headers_score', name: 'Security Headers' },
          { id: 'vulnerabilities', name: 'Vulnerabilities' }
        ]
      },
      {
        id: 'technical-results',
        metrics: [
          { id: 'html_validation', name: 'HTML Validation' },
          { id: 'css_validation', name: 'CSS Validation' },
          { id: 'technologies', name: 'Technologies' }
        ]
      }
    ];
    
    // Popola ogni tabella
    categoryTables.forEach(table => {
      const tableBody = document.getElementById(table.id);
      if (!tableBody) return;
      
      // Svuota la tabella
      tableBody.innerHTML = '';
      
      // Estrai categoria dal id della tabella
      const category = table.id.split('-')[0]; // "performance" da "performance-results"
      
      // Aggiungi righe per ogni metrica
      table.metrics.forEach(metric => {
        // Ottieni i valori per entrambi i siti utilizzando la funzione di accesso flessibile
        const site1Value = getNestedValue(results.site1, category, metric.id);
        const site2Value = getNestedValue(results.site2, category, metric.id);
        
        // Determina il vincitore
        const winner = compareMetricValues(site1Value, site2Value, metric.id);
        
        // Formatta i valori
        const formattedSite1Value = formatMetricValue(site1Value, metric);
        const formattedSite2Value = formatMetricValue(site2Value, metric);
        
        // Crea la riga
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${metric.name}</td>
          <td class="${winner === 'site1' ? 'text-site1 fw-bold' : ''}">${formattedSite1Value}</td>
          <td class="${winner === 'site2' ? 'text-site2 fw-bold' : ''}">${formattedSite2Value}</td>
          <td>${winner === 'site1' ? extractDomain(results.site1.url) : (winner === 'site2' ? extractDomain(results.site2.url) : 'Pareggio')}</td>
        `;
        
        tableBody.appendChild(row);
      });
    });
  }
  
  // Funzione per accedere ai valori annidati in modo flessibile
  function getNestedValue(siteData, category, metricId) {
    if (!siteData) return null;
    
    // Ottieni i possibili percorsi per questa metrica
    const possiblePaths = CONFIG.METRICS_PATHS[category]?.[metricId] || [];
    
    // Prova ogni percorso finché non trovi un valore
    for (const path of possiblePaths) {
      const value = getValueByPath(siteData, path);
      if (value !== undefined && value !== null) {
        return value;
      }
    }
    
    // Strategia 1: Cerca nella struttura metrics (nuova versione API)
    if (siteData.metrics) {
      // 1.1 Cerca in metrics.performance/seo/security.metricId
      if (siteData.metrics[category] && siteData.metrics[category][metricId] !== undefined) {
        return siteData.metrics[category][metricId];
      }
      
      // 1.2 Per il caso in cui metrics.performance contiene direttamente le metriche specifiche
      if (category === 'performance' && siteData.metrics.performance) {
        // Cerca in localMetrics che contiene timeToFirstByte (first_contentful_paint) e loadTime (largest_contentful_paint)
        if (metricId === 'first_contentful_paint' && siteData.metrics.performance.localMetrics?.timeToFirstByte) {
          return siteData.metrics.performance.localMetrics.timeToFirstByte;
        }
        
        if (metricId === 'largest_contentful_paint' && siteData.metrics.performance.localMetrics?.loadTime) {
          return siteData.metrics.performance.localMetrics.loadTime;
        }
        
        if (metricId === 'time_to_interactive' && siteData.metrics.performance.localMetrics?.loadTime) {
          return siteData.metrics.performance.localMetrics.loadTime;
        }
        
        if (metricId === 'cumulative_layout_shift' && 
            (siteData.metrics.performance.cumulative_layout_shift || 
             siteData.metrics.performance.cumulativeLayoutShift)) {
          return siteData.metrics.performance.cumulative_layout_shift || 
                 siteData.metrics.performance.cumulativeLayoutShift || 0.1;
        }
      }
      
      // 1.3 Per il caso in cui metrics.seo contiene metriche specifiche
      if (category === 'seo' && siteData.metrics.seo) {
        if (metricId === 'meta_title' && siteData.metrics.seo.metaTags) {
          return siteData.metrics.seo.metaTags.score || siteData.metrics.seo.metaTags.titleLength || 0;
        }
        
        if (metricId === 'meta_description' && siteData.metrics.seo.metaTags) {
          return siteData.metrics.seo.metaTags.score || 0;
        }
        
        if (metricId === 'headings_structure' && siteData.metrics.seo.headings) {
          return siteData.metrics.seo.headings.score || 0;
        }
        
        if (metricId === 'alt_tags' && siteData.metrics.seo.images) {
          return siteData.metrics.seo.images.altPercentage || siteData.metrics.seo.images.score || 0;
        }
        
        if (metricId === 'url_structure' && siteData.metrics.seo.url) {
          return siteData.metrics.seo.url.score || 0;
        }
      }
      
      // 1.4 Per il caso in cui metrics.security contiene metriche specifiche
      if (category === 'security' && siteData.metrics.security) {
        if (metricId === 'ssl_grade' && siteData.metrics.security.ssl) {
          return siteData.metrics.security.ssl.score || 
                 (siteData.metrics.security.ssl.certificate?.issuer ? 'A' : 'B');
        }
        
        if (metricId === 'headers_score' && siteData.metrics.security.securityHeaders) {
          return siteData.metrics.security.securityHeaders.score || 0;
        }
        
        if (metricId === 'vulnerabilities' && siteData.metrics.security.vulnerabilities) {
          return siteData.metrics.security.vulnerabilities.count || 0;
        }
      }
    }
    
    // Strategia 2: Cerca direttamente nella categoria (vecchia versione API)
    if (siteData[category]) {
      // 2.1 Cerca la metrica specifica nella categoria
      if (siteData[category][metricId] !== undefined) {
        return siteData[category][metricId];
      }
      
      // 2.2 Ricerca flessibile per chiavi specifiche basate sulla categoria
      if (category === 'performance') {
        if (metricId === 'first_contentful_paint' && siteData[category].performance_localMetrics?.timeToFirstByte) {
          return siteData[category].performance_localMetrics.timeToFirstByte;
        }
        if (metricId === 'largest_contentful_paint' && siteData[category].performance_localMetrics?.loadTime) {
          return siteData[category].performance_localMetrics.loadTime;
        }
      }
      
      if (category === 'seo') {
        if (metricId === 'meta_title' && siteData[category].seo_metaTags) {
          return siteData[category].seo_metaTags.score || siteData[category].seo_metaTags.titleLength || 0;
        }
      }
      
      if (category === 'security') {
        if (metricId === 'ssl_grade' && siteData[category].security_ssl) {
          return siteData[category].security_ssl.score || 
                 (siteData[category].security_ssl.certificate?.issuer ? 'A' : 'B');
        }
      }
      
      // 2.3 Ricerca flessibile per chiavi che contengono l'ID della metrica
      for (const key in siteData[category]) {
        if (key.includes(metricId) || (typeof key === 'string' && key.toLowerCase().includes(metricId.toLowerCase()))) {
          // Se abbiamo trovato un oggetto, cerca proprietà pertinenti
          if (typeof siteData[category][key] === 'object' && siteData[category][key] !== null) {
            if (siteData[category][key].score !== undefined) {
              return siteData[category][key].score;
            }
            
            // Per alt tags
            if (category === 'seo' && metricId === 'alt_tags' && siteData[category][key].altPercentage !== undefined) {
              return siteData[category][key].altPercentage;
            }
            
            // Per vulnerabilità
            if (category === 'security' && metricId === 'vulnerabilities' && siteData[category][key].count !== undefined) {
              return siteData[category][key].count;
            }
          }
          
          return siteData[category][key];
        }
      }
    }
    
    // Strategia 3: Cerca direttamente nel siteData
    // 3.1 Per nomi compositi come "seo_metaTags", prova direttamente
    const compositeKey = `${category}_${metricId}`;
    if (siteData[compositeKey] !== undefined) {
      return siteData[compositeKey];
    }
    
    // 3.2 Cerca in tutte le proprietà di primo livello
    for (const key in siteData) {
      // Ignora proprietà comuni che sicuramente non contengono la metrica
      if (['url', 'id', 'winner', 'timestamp'].includes(key)) continue;
      
      // Se la proprietà è un oggetto, cerca al suo interno
      if (typeof siteData[key] === 'object' && siteData[key] !== null) {
        // Cerca sia con metricId che con versioni alternative (con o senza underscore)
        const altMetricId = metricId.includes('_') ? metricId.replace('_', '') : metricId;
        
        if (siteData[key][metricId] !== undefined) {
          return siteData[key][metricId];
        }
        
        if (siteData[key][altMetricId] !== undefined) {
          return siteData[key][altMetricId];
        }
        
        // Cerca in livelli più profondi per metriche specifiche
        if (category === 'performance' && metricId === 'first_contentful_paint' && 
            siteData[key].localMetrics?.timeToFirstByte) {
          return siteData[key].localMetrics.timeToFirstByte;
        }
      }
    }
    
    // Strategia 4: Inferisci valori predefiniti per alcune metriche
    if (category === 'performance') {
      if (metricId === 'cumulative_layout_shift') {
        return 0.1; // Valore predefinito ragionevole
      }
    }
    
    // Non è stato trovato alcun valore
    console.log(`Nessun valore trovato per ${category}.${metricId}`);
    return null;
  }
  
  // Funzione per ottenere un valore seguendo un percorso espresso come stringa
  function getValueByPath(obj, path) {
    return path.split('.').reduce((acc, part) => {
      return acc && acc[part] !== undefined ? acc[part] : null;
    }, obj);
  }
  
  // Confronta i valori di due metriche per determinare il vincitore
  function compareMetricValues(value1, value2, metricId) {
    // Se uno dei valori è null, l'altro è il vincitore
    if (value1 === null && value2 !== null) return 'site2';
    if (value2 === null && value1 !== null) return 'site1';
    if (value1 === null && value2 === null) return 'tie';
    
    // Per alcune metriche, valori più bassi sono migliori
    const lowerIsBetter = CONFIG.LOWER_IS_BETTER_METRICS;
    
    if (lowerIsBetter.includes(metricId)) {
      if (value1 < value2) return 'site1';
      if (value2 < value1) return 'site2';
      return 'tie';
    }
    
    // Per il resto, valori più alti sono migliori
    if (value1 > value2) return 'site1';
    if (value2 > value1) return 'site2';
    return 'tie';
  }
  
  // Formatta il valore di una metrica per la visualizzazione
  function formatMetricValue(value, metric) {
    // Se il valore è null, mostra N/A
    if (value === null || value === undefined) return 'N/A';
    
    // Se è disponibile una funzione di formattazione personalizzata, utilizzala
    if (metric.format) {
      return metric.format(value);
    }
    
    // Formattazione predefinita
    if (typeof value === 'number') {
      return value.toFixed(1);
    }
    
    return value.toString();
  }
  
  // Formatta un tempo in millisecondi
  function formatTime(ms) {
    if (ms === null || ms === undefined) return 'N/A';
    
    // Converti in secondi
    const seconds = ms / 1000;
    
    return seconds.toFixed(2) + ' s';
  }
  
  // Crea i grafici per visualizzare i risultati
  function createCharts(results) {
    // Distruggi i grafici esistenti
    Object.values(charts).forEach(chart => {
      if (chart) {
        chart.destroy();
      }
    });
    
    // Estrai i nomi di dominio
    const site1Domain = extractDomain(results.site1.url);
    const site2Domain = extractDomain(results.site2.url);
    
    // Colori per i siti
    const site1Color = getComputedStyle(document.documentElement).getPropertyValue('--site1-color') || '#FF5733';
    const site2Color = getComputedStyle(document.documentElement).getPropertyValue('--site2-color') || '#33A8FF';
    
    // Crea grafico a barre per le categorie
    charts.categoryChart = createCategoryChart(results, site1Domain, site2Domain, site1Color, site2Color);
    
    // Crea grafico radar per il confronto
    charts.radarChart = createRadarChart(results, site1Domain, site2Domain, site1Color, site2Color);
    
    // Crea grafici specifici per categoria
    charts.performanceChart = createPerformanceChart(results, site1Domain, site2Domain, site1Color, site2Color);
    charts.seoChart = createSeoChart(results, site1Domain, site2Domain, site1Color, site2Color);
    charts.securityChart = createSecurityChart(results, site1Domain, site2Domain, site1Color, site2Color);
  }
  
  // Crea il grafico a barre per le categorie
  function createCategoryChart(results, site1Domain, site2Domain, site1Color, site2Color) {
    const ctx = document.getElementById('category-chart');
    if (!ctx) return null;
    
    // Ottieni i punteggi per ogni categoria
    const site1Performance = results.site1.performanceScore || 0;
    const site2Performance = results.site2.performanceScore || 0;
    
    const site1Seo = results.site1.seoScore || 0;
    const site2Seo = results.site2.seoScore || 0;
    
    const site1Security = results.site1.securityScore || 0;
    const site2Security = results.site2.securityScore || 0;
    
    const site1Technical = results.site1.technicalScore || 0;
    const site2Technical = results.site2.technicalScore || 0;
    
    // Aggiorna anche i valori dei dettagli del sito nella scheda principale
    updateSiteDetailsInMainCard(results, site1Domain, site2Domain);
    
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Performance', 'SEO', 'Sicurezza', 'Aspetti Tecnici'],
        datasets: [
          {
            label: site1Domain,
            data: [site1Performance, site1Seo, site1Security, site1Technical],
            backgroundColor: site1Color,
            borderColor: site1Color,
            borderWidth: 1
          },
          {
            label: site2Domain,
            data: [site2Performance, site2Seo, site2Security, site2Technical],
            backgroundColor: site2Color,
            borderColor: site2Color,
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        },
        plugins: {
          title: {
            display: true,
            text: 'Confronto per categoria'
          }
        }
      }
    });
  }
  
  // Funzione per aggiornare i dettagli dei siti nella scheda principale
  function updateSiteDetailsInMainCard(results, site1Domain, site2Domain) {
    // Aggiorna i dettagli per il sito 1
    const site1TotalScore = Math.round(results.site1.totalScore || 0);
    const site1PerformanceScore = Math.round(results.site1.performanceScore || 0);
    const site1SeoScore = Math.round(results.site1.seoScore || 0);
    const site1SecurityScore = Math.round(results.site1.securityScore || 0);
    const site1TechnicalScore = Math.round(results.site1.technicalScore || 0);
    
    // Aggiorna URL completo
    const site1FullUrlElement = document.getElementById('site1-full-url');
    if (site1FullUrlElement) {
      site1FullUrlElement.textContent = results.site1.url || results.url1 || '';
    }
    
    // Aggiorna punteggi
    setElementTextContent('site1-total-score', site1TotalScore);
    setElementTextContent('site1-performance-score', site1PerformanceScore);
    setElementTextContent('site1-seo-score', site1SeoScore);
    setElementTextContent('site1-security-score', site1SecurityScore);
    setElementTextContent('site1-technical-score', site1TechnicalScore);
    
    // Aggiorna barra di progresso
    const site1ScoreBar = document.getElementById('site1-score-bar');
    if (site1ScoreBar) {
      site1ScoreBar.style.width = `${site1TotalScore}%`;
      site1ScoreBar.classList.remove('bg-success', 'bg-warning', 'bg-danger');
      site1ScoreBar.classList.add(getScoreColorClass(site1TotalScore));
    }
    
    // Aggiorna i dettagli per il sito 2
    const site2TotalScore = Math.round(results.site2.totalScore || 0);
    const site2PerformanceScore = Math.round(results.site2.performanceScore || 0);
    const site2SeoScore = Math.round(results.site2.seoScore || 0);
    const site2SecurityScore = Math.round(results.site2.securityScore || 0);
    const site2TechnicalScore = Math.round(results.site2.technicalScore || 0);
    
    // Aggiorna URL completo
    const site2FullUrlElement = document.getElementById('site2-full-url');
    if (site2FullUrlElement) {
      site2FullUrlElement.textContent = results.site2.url || results.url2 || '';
    }
    
    // Aggiorna punteggi
    setElementTextContent('site2-total-score', site2TotalScore);
    setElementTextContent('site2-performance-score', site2PerformanceScore);
    setElementTextContent('site2-seo-score', site2SeoScore);
    setElementTextContent('site2-security-score', site2SecurityScore);
    setElementTextContent('site2-technical-score', site2TechnicalScore);
    
    // Aggiorna barra di progresso
    const site2ScoreBar = document.getElementById('site2-score-bar');
    if (site2ScoreBar) {
      site2ScoreBar.style.width = `${site2TotalScore}%`;
      site2ScoreBar.classList.remove('bg-success', 'bg-warning', 'bg-danger');
      site2ScoreBar.classList.add(getScoreColorClass(site2TotalScore));
    }
    
    // Aggiorna il livello di vittoria
    const victoryLevelElement = document.getElementById('victory-level');
    if (victoryLevelElement && results.victoryLevel) {
      victoryLevelElement.textContent = capitalizeFirstLetter(results.victoryLevel);
    }
    
    // Aggiorna l'aspetto dell'annuncio del vincitore
    const winnerAnnouncement = document.getElementById('winner-announcement');
    if (winnerAnnouncement) {
      // Rimuovi tutte le classi di colore esistenti
      winnerAnnouncement.classList.remove('alert-success', 'alert-primary', 'alert-warning', 'alert-info');
      
      // Aggiungi la classe appropriata in base al livello di vittoria
      if (results.victoryLevel) {
        const victoryLevel = results.victoryLevel.toLowerCase();
        if (victoryLevel.includes('schiacciante')) {
          winnerAnnouncement.classList.add('alert-success');
        } else if (victoryLevel.includes('netta')) {
          winnerAnnouncement.classList.add('alert-primary');
        } else if (victoryLevel.includes('leggera')) {
          winnerAnnouncement.classList.add('alert-info');
        } else {
          winnerAnnouncement.classList.add('alert-light');
        }
      }
    }
    
    // Aggiungi dati grezzi nella tab dei dettagli
    const rawDataJson = document.getElementById('raw-data-json');
    if (rawDataJson) {
      rawDataJson.textContent = JSON.stringify(results, null, 2);
    }
    
    // Popola la tabella dei metadati
    populateMetadataTable(results.metadata);
    
    // Popola i suggerimenti per performance se disponibili
    populatePerformanceTips(results);
    
    // Popola le informazioni di sicurezza
    populateSecurityInfo(results);
    
    // Popola le tecnologie rilevate
    populateTechnologies(results);
    
    // Popola i meta tag analizzati
    populateMetaTags(results);
  }
  
  // Funzione per impostare il testo di un elemento se esiste
  function setElementTextContent(elementId, text) {
    const element = document.getElementById(elementId);
    if (element) {
      element.textContent = text;
    }
  }
  
  // Funzione per ottenere la classe di colore in base al punteggio
  function getScoreColorClass(score) {
    if (score >= 70) return 'bg-success';
    if (score >= 50) return 'bg-warning';
    return 'bg-danger';
  }
  
  // Funzione per capitalizzare la prima lettera di una stringa
  function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
  }
  
  // Funzione per popolare la tabella dei metadati
  function populateMetadataTable(metadata) {
    const metadataTable = document.getElementById('metadata-table');
    if (!metadataTable || !metadata) return;
    
    // Pulisci la tabella
    metadataTable.innerHTML = '';
    
    // Aggiungi righe per ogni metadato
    Object.entries(metadata).forEach(([key, value]) => {
      const row = document.createElement('tr');
      
      // Formatta i timestamp
      if (key.toLowerCase().includes('time') || key.toLowerCase().includes('date')) {
        if (typeof value === 'number' && value > 1000000000) {
          // Probabilmente un timestamp Unix
          value = new Date(value * 1000).toLocaleString();
        } else if (typeof value === 'number') {
          // Probabilmente un timestamp in millisecondi
          value = new Date(value).toLocaleString();
        }
      }
      
      // Formatta gli array
      if (Array.isArray(value)) {
        value = value.join(', ');
      }
      
      // Formatta gli oggetti
      if (typeof value === 'object' && value !== null) {
        value = JSON.stringify(value);
      }
      
      row.innerHTML = `
        <th scope="row">${key}</th>
        <td>${value}</td>
      `;
      
      metadataTable.appendChild(row);
    });
  }
  
  // Funzione per popolare i suggerimenti di performance
  function populatePerformanceTips(results) {
    const site1Tips = document.getElementById('site1-perf-tips');
    const site2Tips = document.getElementById('site2-perf-tips');
    
    if (!site1Tips || !site2Tips) return;
    
    // Pulisci le liste
    site1Tips.innerHTML = '';
    site2Tips.innerHTML = '';
    
    // Suggerimenti per il sito 1
    let site1TipsData = [];
    if (results.site1.performance?.performance_pageSpeed?.opportunities) {
      site1TipsData = results.site1.performance.performance_pageSpeed.opportunities;
    } else if (results.site1.metrics?.performance?.opportunities) {
      site1TipsData = results.site1.metrics.performance.opportunities;
    }
    
    if (site1TipsData.length > 0) {
      site1TipsData.forEach(tip => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.innerHTML = `<strong>${tip.title || 'Suggerimento'}</strong>: ${tip.description || ''}`;
        site1Tips.appendChild(li);
      });
    } else {
      const li = document.createElement('li');
      li.className = 'list-group-item';
      li.innerHTML = 'Nessun suggerimento disponibile';
      site1Tips.appendChild(li);
    }
    
    // Suggerimenti per il sito 2
    let site2TipsData = [];
    if (results.site2.performance?.performance_pageSpeed?.opportunities) {
      site2TipsData = results.site2.performance.performance_pageSpeed.opportunities;
    } else if (results.site2.metrics?.performance?.opportunities) {
      site2TipsData = results.site2.metrics.performance.opportunities;
    }
    
    if (site2TipsData.length > 0) {
      site2TipsData.forEach(tip => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.innerHTML = `<strong>${tip.title || 'Suggerimento'}</strong>: ${tip.description || ''}`;
        site2Tips.appendChild(li);
      });
    } else {
      const li = document.createElement('li');
      li.className = 'list-group-item';
      li.innerHTML = 'Nessun suggerimento disponibile';
      site2Tips.appendChild(li);
    }
  }
  
  // Funzione per popolare le informazioni di sicurezza
  function populateSecurityInfo(results) {
    const site1Headers = document.getElementById('site1-security-headers');
    const site2Headers = document.getElementById('site2-security-headers');
    
    if (!site1Headers || !site2Headers) return;
    
    // Pulisci le liste
    site1Headers.innerHTML = '';
    site2Headers.innerHTML = '';
    
    // Headers per il sito 1
    let site1HeadersData = {};
    if (results.site1.security?.security_securityHeaders?.values) {
      site1HeadersData = results.site1.security.security_securityHeaders.values;
    } else if (results.site1.metrics?.security?.securityHeaders?.values) {
      site1HeadersData = results.site1.metrics.security.securityHeaders.values;
    }
    
    if (Object.keys(site1HeadersData).length > 0) {
      Object.entries(site1HeadersData).forEach(([header, value]) => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.innerHTML = `<strong>${header}</strong>: ${Array.isArray(value) ? value.join(', ') : value}`;
        site1Headers.appendChild(li);
      });
    } else {
      const li = document.createElement('li');
      li.className = 'list-group-item';
      li.innerHTML = 'Nessun header di sicurezza rilevato';
      site1Headers.appendChild(li);
    }
    
    // Headers per il sito 2
    let site2HeadersData = {};
    if (results.site2.security?.security_securityHeaders?.values) {
      site2HeadersData = results.site2.security.security_securityHeaders.values;
    } else if (results.site2.metrics?.security?.securityHeaders?.values) {
      site2HeadersData = results.site2.metrics.security.securityHeaders.values;
    }
    
    if (Object.keys(site2HeadersData).length > 0) {
      Object.entries(site2HeadersData).forEach(([header, value]) => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.innerHTML = `<strong>${header}</strong>: ${Array.isArray(value) ? value.join(', ') : value}`;
        site2Headers.appendChild(li);
      });
    } else {
      const li = document.createElement('li');
      li.className = 'list-group-item';
      li.innerHTML = 'Nessun header di sicurezza rilevato';
      site2Headers.appendChild(li);
    }
  }
  
  // Funzione per popolare le tecnologie rilevate
  function populateTechnologies(results) {
    const site1Tech = document.getElementById('site1-technologies');
    const site2Tech = document.getElementById('site2-technologies');
    
    if (!site1Tech || !site2Tech) return;
    
    // Pulisci le liste
    site1Tech.innerHTML = '';
    site2Tech.innerHTML = '';
    
    // Tecnologie per il sito 1
    let site1TechData = [];
    if (results.site1.technical?.technologies) {
      site1TechData = results.site1.technical.technologies;
    } else if (results.site1.metrics?.technical?.technologies) {
      site1TechData = results.site1.metrics.technical.technologies;
    }
    
    if (Array.isArray(site1TechData) && site1TechData.length > 0) {
      site1TechData.forEach(tech => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.innerHTML = typeof tech === 'string' ? tech : (tech.name || 'Tecnologia sconosciuta');
        site1Tech.appendChild(li);
      });
    } else {
      const li = document.createElement('li');
      li.className = 'list-group-item';
      li.innerHTML = 'Nessuna tecnologia rilevata';
      site1Tech.appendChild(li);
    }
    
    // Tecnologie per il sito 2
    let site2TechData = [];
    if (results.site2.technical?.technologies) {
      site2TechData = results.site2.technical.technologies;
    } else if (results.site2.metrics?.technical?.technologies) {
      site2TechData = results.site2.metrics.technical.technologies;
    }
    
    if (Array.isArray(site2TechData) && site2TechData.length > 0) {
      site2TechData.forEach(tech => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.innerHTML = typeof tech === 'string' ? tech : (tech.name || 'Tecnologia sconosciuta');
        site2Tech.appendChild(li);
      });
    } else {
      const li = document.createElement('li');
      li.className = 'list-group-item';
      li.innerHTML = 'Nessuna tecnologia rilevata';
      site2Tech.appendChild(li);
    }
  }
  
  // Funzione per popolare i meta tag analizzati
  function populateMetaTags(results) {
    const metaTagsTable = document.getElementById('metatags-results');
    if (!metaTagsTable) return;
    
    // Pulisci la tabella
    metaTagsTable.innerHTML = '';
    
    // Meta tag da visualizzare
    const metaTags = ['title', 'description', 'keywords', 'robots', 'viewport', 'canonical'];
    
    // Ottieni i dati dei meta tag per entrambi i siti
    let site1MetaTags = {};
    if (results.site1.seo?.seo_metaTags) {
      site1MetaTags = results.site1.seo.seo_metaTags;
    } else if (results.site1.metrics?.seo?.metaTags) {
      site1MetaTags = results.site1.metrics.seo.metaTags;
    }
    
    let site2MetaTags = {};
    if (results.site2.seo?.seo_metaTags) {
      site2MetaTags = results.site2.seo.seo_metaTags;
    } else if (results.site2.metrics?.seo?.metaTags) {
      site2MetaTags = results.site2.metrics.seo.metaTags;
    }
    
    // Aggiungi righe per ogni meta tag
    metaTags.forEach(tag => {
      const row = document.createElement('tr');
      const site1Value = site1MetaTags[tag] || 'Non trovato';
      const site2Value = site2MetaTags[tag] || 'Non trovato';
      
      row.innerHTML = `
        <th scope="row">${tag}</th>
        <td>${site1Value}</td>
        <td>${site2Value}</td>
      `;
      
      metaTagsTable.appendChild(row);
    });
    
    // Aggiungi anche Open Graph e Twitter se disponibili
    if (site1MetaTags.ogTags || site2MetaTags.ogTags) {
      const ogRow = document.createElement('tr');
      ogRow.innerHTML = `
        <th scope="row">Open Graph</th>
        <td>${site1MetaTags.ogTags ? 'Presente' : 'Non trovato'}</td>
        <td>${site2MetaTags.ogTags ? 'Presente' : 'Non trovato'}</td>
      `;
      metaTagsTable.appendChild(ogRow);
    }
    
    if (site1MetaTags.twitterTags || site2MetaTags.twitterTags) {
      const twitterRow = document.createElement('tr');
      twitterRow.innerHTML = `
        <th scope="row">Twitter Cards</th>
        <td>${site1MetaTags.twitterTags ? 'Presente' : 'Non trovato'}</td>
        <td>${site2MetaTags.twitterTags ? 'Presente' : 'Non trovato'}</td>
      `;
      metaTagsTable.appendChild(twitterRow);
    }
  }
  
  // Crea il grafico radar per il confronto
  function createRadarChart(results, site1Domain, site2Domain, site1Color, site2Color) {
    const ctx = document.getElementById('radar-chart');
    if (!ctx) return null;
    
    // Ottieni i punteggi per ogni categoria
    const site1Performance = results.site1.performanceScore || 0;
    const site2Performance = results.site2.performanceScore || 0;
    
    const site1Seo = results.site1.seoScore || 0;
    const site2Seo = results.site2.seoScore || 0;
    
    const site1Security = results.site1.securityScore || 0;
    const site2Security = results.site2.securityScore || 0;
    
    const site1Technical = results.site1.technicalScore || 0;
    const site2Technical = results.site2.technicalScore || 0;
    
    return new Chart(ctx, {
      type: 'radar',
      data: {
        labels: ['Performance', 'SEO', 'Sicurezza', 'Aspetti Tecnici'],
        datasets: [
          {
            label: site1Domain,
            data: [site1Performance, site1Seo, site1Security, site1Technical],
            backgroundColor: `${site1Color}33`,
            borderColor: site1Color,
            borderWidth: 2,
            pointBackgroundColor: site1Color,
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: site1Color
          },
          {
            label: site2Domain,
            data: [site2Performance, site2Seo, site2Security, site2Technical],
            backgroundColor: `${site2Color}33`,
            borderColor: site2Color,
            borderWidth: 2,
            pointBackgroundColor: site2Color,
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: site2Color
          }
        ]
      },
      options: {
        responsive: true,
        scales: {
          r: {
            min: 0,
            max: 100,
            beginAtZero: true,
            ticks: {
              stepSize: 20
            }
          }
        },
        plugins: {
          title: {
            display: true,
            text: 'Radar del confronto'
          }
        }
      }
    });
  }
  
  // Crea il grafico per le performance
  function createPerformanceChart(results, site1Domain, site2Domain, site1Color, site2Color) {
    const ctx = document.getElementById('performance-chart');
    if (!ctx) return null;
    
    // Ottieni i valori specifici per le performance, utilizzando i percorsi flessibili
    const site1FCP = getNestedValue(results.site1, 'performance', 'first_contentful_paint') || 0;
    const site2FCP = getNestedValue(results.site2, 'performance', 'first_contentful_paint') || 0;
    
    const site1LCP = getNestedValue(results.site1, 'performance', 'largest_contentful_paint') || 0;
    const site2LCP = getNestedValue(results.site2, 'performance', 'largest_contentful_paint') || 0;
    
    const site1TTI = getNestedValue(results.site1, 'performance', 'time_to_interactive') || 0;
    const site2TTI = getNestedValue(results.site2, 'performance', 'time_to_interactive') || 0;
    
    // Normalizza i valori (per tempi più bassi sono migliori)
    const maxFCP = Math.max(site1FCP, site2FCP) || 1;
    const maxLCP = Math.max(site1LCP, site2LCP) || 1;
    const maxTTI = Math.max(site1TTI, site2TTI) || 1;
    
    // Converti in punteggi (più alto è migliore)
    const site1FCPScore = site1FCP ? 100 - (site1FCP / maxFCP * 100) : 0;
    const site2FCPScore = site2FCP ? 100 - (site2FCP / maxFCP * 100) : 0;
    
    const site1LCPScore = site1LCP ? 100 - (site1LCP / maxLCP * 100) : 0;
    const site2LCPScore = site2LCP ? 100 - (site2LCP / maxLCP * 100) : 0;
    
    const site1TTIScore = site1TTI ? 100 - (site1TTI / maxTTI * 100) : 0;
    const site2TTIScore = site2TTI ? 100 - (site2TTI / maxTTI * 100) : 0;
    
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['First Contentful Paint', 'Largest Contentful Paint', 'Time to Interactive'],
        datasets: [
          {
            label: site1Domain,
            data: [site1FCPScore, site1LCPScore, site1TTIScore],
            backgroundColor: site1Color,
            borderColor: site1Color,
            borderWidth: 1
          },
          {
            label: site2Domain,
            data: [site2FCPScore, site2LCPScore, site2TTIScore],
            backgroundColor: site2Color,
            borderColor: site2Color,
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        },
        plugins: {
          title: {
            display: true,
            text: 'Confronto performance (punteggi normalizzati)'
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.dataset.label || '';
                const value = context.raw;
                
                // Mostra anche i valori originali
                if (context.dataIndex === 0) {
                  const originalValue = context.datasetIndex === 0 ? site1FCP : site2FCP;
                  return `${label}: ${value.toFixed(1)} (${(originalValue / 1000).toFixed(2)}s)`;
                } else if (context.dataIndex === 1) {
                  const originalValue = context.datasetIndex === 0 ? site1LCP : site2LCP;
                  return `${label}: ${value.toFixed(1)} (${(originalValue / 1000).toFixed(2)}s)`;
                } else if (context.dataIndex === 2) {
                  const originalValue = context.datasetIndex === 0 ? site1TTI : site2TTI;
                  return `${label}: ${value.toFixed(1)} (${(originalValue / 1000).toFixed(2)}s)`;
                }
                
                return `${label}: ${value.toFixed(1)}`;
              }
            }
          }
        }
      }
    });
  }
  
  // Crea il grafico per il SEO
  function createSeoChart(results, site1Domain, site2Domain, site1Color, site2Color) {
    const ctx = document.getElementById('seo-chart');
    if (!ctx) return null;
    
    // Ottieni i valori SEO utilizzando i percorsi flessibili
    const site1MetaTitle = getNestedValue(results.site1, 'seo', 'meta_title') || 0;
    const site2MetaTitle = getNestedValue(results.site2, 'seo', 'meta_title') || 0;
    
    const site1MetaDesc = getNestedValue(results.site1, 'seo', 'meta_description') || 0;
    const site2MetaDesc = getNestedValue(results.site2, 'seo', 'meta_description') || 0;
    
    const site1Headings = getNestedValue(results.site1, 'seo', 'headings_structure') || 0;
    const site2Headings = getNestedValue(results.site2, 'seo', 'headings_structure') || 0;
    
    const site1AltTags = getNestedValue(results.site1, 'seo', 'alt_tags') || 0;
    const site2AltTags = getNestedValue(results.site2, 'seo', 'alt_tags') || 0;
    
    const site1Url = getNestedValue(results.site1, 'seo', 'url_structure') || 0;
    const site2Url = getNestedValue(results.site2, 'seo', 'url_structure') || 0;
    
    return new Chart(ctx, {
      type: 'radar',
      data: {
        labels: ['Meta Title', 'Meta Description', 'Headings', 'Alt Tags', 'URL Structure'],
        datasets: [
          {
            label: site1Domain,
            data: [
              typeof site1MetaTitle === 'string' ? 80 : site1MetaTitle, 
              typeof site1MetaDesc === 'string' ? 80 : site1MetaDesc, 
              site1Headings, 
              site1AltTags, 
              site1Url
            ],
            backgroundColor: `${site1Color}33`,
            borderColor: site1Color,
            borderWidth: 2
          },
          {
            label: site2Domain,
            data: [
              typeof site2MetaTitle === 'string' ? 80 : site2MetaTitle, 
              typeof site2MetaDesc === 'string' ? 80 : site2MetaDesc, 
              site2Headings, 
              site2AltTags, 
              site2Url
            ],
            backgroundColor: `${site2Color}33`,
            borderColor: site2Color,
            borderWidth: 2
          }
        ]
      },
      options: {
        responsive: true,
        scales: {
          r: {
            min: 0,
            max: 100,
            beginAtZero: true
          }
        }
      }
    });
  }
  
  // Crea il grafico per la sicurezza
  function createSecurityChart(results, site1Domain, site2Domain, site1Color, site2Color) {
    const ctx = document.getElementById('security-chart');
    if (!ctx) return null;
    
    // Ottieni valori di sicurezza utilizzando i percorsi flessibili
    const site1Headers = getNestedValue(results.site1, 'security', 'headers_score') || 0;
    const site2Headers = getNestedValue(results.site2, 'security', 'headers_score') || 0;
    
    const site1SSL = getNestedValue(results.site1, 'security', 'ssl_grade');
    const site2SSL = getNestedValue(results.site2, 'security', 'ssl_grade');
    
    // Converte SSL grade in punteggio numerico
    const getSSLScore = grade => {
      if (!grade) return 0;
      if (typeof grade === 'number') return grade;
      
      const gradeMap = {
        'A+': 100, 'A': 95, 'A-': 90,
        'B+': 85, 'B': 80, 'B-': 75,
        'C+': 70, 'C': 65, 'C-': 60,
        'D+': 55, 'D': 50, 'D-': 45,
        'F': 30
      };
      
      return gradeMap[grade] || 50; // 50 come valore di default
    };
    
    const site1SSLScore = getSSLScore(site1SSL);
    const site2SSLScore = getSSLScore(site2SSL);
    
    // Ottieni vulnerabilità (0 è ottimale)
    const site1Vulns = getNestedValue(results.site1, 'security', 'vulnerabilities') || 0;
    const site2Vulns = getNestedValue(results.site2, 'security', 'vulnerabilities') || 0;
    
    // Converti in punteggio (100 - vulns*10, limitato a 0-100)
    const site1VulnScore = Math.max(0, 100 - site1Vulns * 10);
    const site2VulnScore = Math.max(0, 100 - site2Vulns * 10);
    
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['SSL/TLS', 'Security Headers', 'Vulnerabilità'],
        datasets: [
          {
            label: site1Domain,
            data: [site1SSLScore, site1Headers, site1VulnScore],
            backgroundColor: site1Color,
            borderColor: site1Color,
            borderWidth: 1
          },
          {
            label: site2Domain,
            data: [site2SSLScore, site2Headers, site2VulnScore],
            backgroundColor: site2Color,
            borderColor: site2Color,
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        },
        plugins: {
          title: {
            display: true,
            text: 'Confronto sicurezza'
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.dataset.label || '';
                const value = context.raw;
                
                // Mostra anche i valori originali
                if (context.dataIndex === 0) {
                  const originalValue = context.datasetIndex === 0 ? site1SSL : site2SSL;
                  if (typeof originalValue === 'string') {
                    return `${label}: ${value} (${originalValue})`;
                  }
                } else if (context.dataIndex === 2) {
                  const originalValue = context.datasetIndex === 0 ? site1Vulns : site2Vulns;
                  return `${label}: ${value} (${originalValue} problemi)`;
                }
                
                return `${label}: ${value}`;
              }
            }
          }
        }
      }
    });
  }
  
  // Funzione utility per estrarre il dominio da un URL
  function extractDomain(url) {
    if (!url) return '';
    
    // Rimuovi il protocollo e prendi solo l'hostname
    return url.replace(/^https?:\/\//, '').split('/')[0];
  }
  
  // Esponi l'API pubblica
  return {
    init,
    show,
    hide
  };
})();

// Modulo APIConnector per la comunicazione con il backend
SiteWar.APIConnector = (function() {
  // URL dell'API
  const API_URL = '/server/api';
  
  // Flag per attivare l'ottimizzazione
  const OPTIMIZATION_ENABLED = true;
  
  // Inizializzazione
  function init() {
    console.log('APIConnector initialized with optimizations');
  }
  
  /**
   * Verifica la validità degli URL prima dell'analisi
   * 
   * @param {string} site1 URL del primo sito
   * @param {string} site2 URL del secondo sito
   * @returns {Promise} Promise che si risolve con la risposta dell'API
   */
  async function validateUrls(site1, site2) {
    // Endpoint per la validazione
    const endpoint = `${API_URL}/validate`;
    
    // Dati da inviare
    const data = { site1, site2 };
    
    // Esegui la chiamata API
    const response = await apiCall(endpoint, 'POST', data);
    
    // Restituisci la risposta
    return response;
  }
  
  /**
   * Esegue l'analisi di due URL tramite l'API di Site War
   * 
   * @param {string} site1 URL del primo sito
   * @param {string} site2 URL del secondo sito
   * @param {string} analysisId ID di un'analisi precedente (opzionale)
   * @returns {Promise} Promise che si risolve con la risposta dell'API
   */
  async function analyzeUrls(site1, site2, analysisId) {
    // Determina l'endpoint in base ai parametri
    let endpoint = `${API_URL}/analyze`;
    let data = {};
    
    if (analysisId) {
      // Controllo dello stato di un'analisi esistente
      data = { analysisId };
    } else if (site1 && site2) {
      // Nuova analisi
      data = { site1, site2 };
    } else {
      throw new Error('Parametri mancanti: è necessario fornire site1 e site2 o analysisId');
    }
    
    // Esegui la chiamata API
    const response = await apiCall(endpoint, 'POST', data);
    
    // Logging per debug
    console.log('API call:', 'POST', endpoint, data);
    
    // Restituisci la risposta
    return response;
  }
  
  /**
   * Funzione generica per le chiamate API
   * 
   * @param {string} url URL dell'endpoint
   * @param {string} method Metodo HTTP (GET, POST, etc.)
   * @param {object} data Dati da inviare
   * @returns {Promise} Promise che si risolve con la risposta dell'API
   */
  async function apiCall(url, method, data) {
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    };
    
    // Aggiungi body per metodi POST, PUT, etc.
    if (method !== 'GET' && data) {
      options.body = JSON.stringify(data);
    }
    
    // Ottimizzazione: aggiungi un timestamp o parametro di cache busting
    if (OPTIMIZATION_ENABLED) {
      const timestamp = Date.now();
      url = url.includes('?') ? `${url}&_=${timestamp}` : `${url}?_=${timestamp}`;
    }
    
    // Log della richiesta
    console.log(`Fetching URL: ${url}`);
    
    try {
      // Esegui la richiesta
      const response = await fetch(url, options);
      
      // Controlla se la risposta è OK
      if (!response.ok) {
        throw new Error(`Error ${response.status}: ${response.statusText}`);
      }
      
      // Converti risposta in JSON
      const jsonResponse = await response.json();
      
      // Log della risposta per debugging
      console.log("===== API RESPONSE FROM " + url.split('/').pop() + " =====");
      console.log(JSON.stringify(jsonResponse));
      console.log("=======================================");
      
      // Parsing della struttura generale della risposta
      console.log("Parsed JSON response:", jsonResponse);
      
      // Restituisci il risultato
      return jsonResponse;
    } catch (error) {
      console.error('API call error:', error);
      throw error;
    }
  }
  
  // Esponi l'API pubblica
  return {
    init,
    analyzeUrls,
    validateUrls
  };
})();

// Modulo per l'ottimizzazione DOM
SiteWar.DOMOptimizer = (function() {
  // Inizializzazione
  function init() {
    // Ottimizza caricamento delle immagini
    optimizeImages();
    
    // Ottimizza eventi
    optimizeEvents();
    
    console.log('DOM Optimizer initialized');
  }
  
  // Ottimizza caricamento immagini
  function optimizeImages() {
    // Implementa il lazy loading per immagini che supportano l'attributo loading
    document.querySelectorAll('img:not([loading])').forEach(img => {
      if ('loading' in HTMLImageElement.prototype) {
        img.loading = 'lazy';
      }
    });
  }
  
  // Ottimizza gestione eventi
  function optimizeEvents() {
    // Usa delegazione eventi per ridurre il numero di listener
    document.addEventListener('click', e => {
      // Gestione click per vari elementi
    });
  }
  
  // Esponi l'API pubblica
  return {
    init
  };
})();

// Modulo per caratteristiche di sicurezza
SiteWar.Security = (function() {
  // Inizializzazione
  function init() {
    // Aggiungi protezioni XSS
    addXSSProtection();
    
    // Applica sanitizzazione input
    applySanitization();
    
    console.log('Security module initialized');
  }
  
  // Protezione XSS
  function addXSSProtection() {
    // Funzione utile per sanificare input
    window.sanitizeInput = function(input) {
      const div = document.createElement('div');
      div.textContent = input;
      return div.innerHTML;
    };
  }
  
  // Sanitizzazione input
  function applySanitization() {
    document.querySelectorAll('input[type="text"], input[type="url"]').forEach(input => {
      input.addEventListener('blur', function() {
        this.value = this.value.trim();
      });
    });
  }
  
  // Esponi l'API pubblica
  return {
    init
  };
})();

// Inizializzazione dell'applicazione al caricamento della pagina
document.addEventListener('DOMContentLoaded', function() {
  // Inizializza moduli di supporto
  SiteWar.Security.init();
  SiteWar.DOMOptimizer.init();
  
  // Inizializza l'applicazione principale
  SiteWar.App.init();
});