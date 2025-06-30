<?php
/**
 * AnalyzeController
 * 
 * Controller responsabile della gestione delle richieste di analisi dei siti web.
 * Coordina il processo di analisi completo utilizzando i vari analizzatori disponibili.
 * 
 * Implementa:
 * - Inizializzazione dell'analisi di due siti
 * - Coordinamento degli analizzatori specifici
 * - Aggregazione dei risultati
 * - Calcolo dei punteggi finali e determinazione del vincitore
 * 
 * Pattern utilizzati:
 * - Command Pattern
 * - Strategy Pattern
 */

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/ServiceFactory.php';
require_once __DIR__ . '/../../services/AnalysisManager.php';
require_once __DIR__ . '/../../utils/Security.php';
require_once __DIR__ . '/../../utils/Cache.php';
require_once __DIR__ . '/../../services/dto/ResponseDTO.php';
require_once __DIR__ . '/../../services/dto/PerformanceMetricsDTO.php';
require_once __DIR__ . '/../../services/dto/SEOMetricsDTO.php';
require_once __DIR__ . '/../../services/dto/SecurityMetricsDTO.php';
require_once __DIR__ . '/../../services/dto/TechnicalMetricsDTO.php';

class AnalyzeController implements Controller {
    /**
     * @var ServiceFactory Factory per la creazione di servizi e analizzatori
     */
    private $serviceFactory;
    
    /**
     * @var Cache Sistema di cache
     */
    private $cache;
    
    /**
     * @var array Configurazione dei timeout per le analisi
     */
    private $timeoutConfig = [
        'total' => 180, // Timeout totale in secondi (3 minuti)
        'performance' => 60, // 1 minuto
        'seo' => 45,         // 45 secondi
        'security' => 45,    // 45 secondi
        'technology' => 30   // 30 secondi
    ];
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->serviceFactory = new ServiceFactory();
        $this->cache = new Cache();
    }
    
    /**
     * Gestisce una richiesta HTTP
     * 
     * @param string $method Il metodo HTTP (GET, POST, etc.)
     * @param array $params I parametri della richiesta
     * @return array La risposta da restituire al client
     * @throws Exception Se la richiesta non è valida o si verifica un errore
     */
    public function handleRequest($method, $params) {
        // Verifica il metodo HTTP
        if ($method !== 'POST') {
            throw new Exception('Metodo non supportato. Utilizzare POST.', 405);
        }
        
        // Verifica se è richiesto un completamento precedente
        $analysisId = $params['analysisId'] ?? null;
        
        // Se è stato fornito analysisId, recupera i risultati
        if ($analysisId) {
            error_log("Richiesta di recupero risultati per analisi ID: {$analysisId}");
            $analysisStatus = $this->cache->get("status_{$analysisId}");
            
            if (!$analysisStatus) {
                throw new Exception("Analisi ID non trovato: {$analysisId}", 404);
            }
            
            // Se l'analisi è completata, restituisci i risultati
            if ($analysisStatus['status'] === 'completed' || $analysisStatus['status'] === 'completed_partial') {
                return [
                    'status' => 'success',
                    'complete' => true,
                    'results' => $analysisStatus['results'] ?? null
                ];
            }
            
            // Se l'analisi è ancora in corso, restituisci lo stato
            return [
                'status' => 'success',
                'complete' => false,
                'progress' => $analysisStatus['progress'] ?? 0,
                'message' => $analysisStatus['message'] ?? 'Analisi in corso...'
            ];
        }
        
        // Altrimenti, avvia una nuova analisi
        // Verifica che siano stati forniti gli URL (supporta sia url1/url2 che site1/site2)
        $url1 = $params['url1'] ?? $params['site1'] ?? null;
        $url2 = $params['url2'] ?? $params['site2'] ?? null;
        
        if (empty($url1) || empty($url2)) {
            throw new Exception('Parametri mancanti. È necessario fornire url1/site1 e url2/site2.', 400);
        }
        
        // Sanitizza e valida gli URL
        $url1 = filter_var($url1, FILTER_SANITIZE_URL);
        $url2 = filter_var($url2, FILTER_SANITIZE_URL);
        
        if (!filter_var($url1, FILTER_VALIDATE_URL) || !filter_var($url2, FILTER_VALIDATE_URL)) {
            throw new Exception('URL non validi. Fornire URL completi (es. https://esempio.com).', 400);
        }
        
        // Verifica se i risultati sono già in cache
        $cacheKey = md5($url1 . '|' . $url2);
        $cachedResults = $this->cache->get($cacheKey);
        
        if ($cachedResults) {
            error_log("Trovati risultati in cache per {$url1} vs {$url2}");
            return [
                'status' => 'success',
                'complete' => true,
                'results' => $cachedResults
            ];
        } else {
            error_log("Nessun risultato in cache per {$url1} vs {$url2}");
        }
        
        // Crea un ID univoco per questa analisi
        $analysisId = uniqid('analysis_');
        
        // Inizializza lo stato dell'analisi
        $status = [
            'analysisId' => $analysisId,
            'url1' => $url1,
            'url2' => $url2,
            'status' => 'initiated',
            'progress' => 0,
            'message' => 'Analisi avviata, in attesa di risultati',
            'timestamp' => time()
        ];
        
        // Salva lo stato iniziale
        $this->cache->set("status_{$analysisId}", $status, 3600);
        
        // Inizializza il manager dell'analisi
        $analysisManager = new AnalysisManager($url1, $url2, [
            'timeouts' => $this->timeoutConfig,
            'serviceFactory' => $this->serviceFactory
        ]);
        
        try {
            error_log("Avvio analisi per ID {$analysisId}: {$url1} vs {$url2}");
            
            // In modalità di debug, l'analisi è sincrona ma lenta
            if (DEBUG_MODE) {
                // Aggiorna subito lo stato a "in_progress"
                $status['status'] = 'in_progress';
                $status['progress'] = 10;
                $status['message'] = 'Analisi in corso...';
                $this->cache->set("status_{$analysisId}", $status, 3600);
                
                // Esegui l'analisi in modo sincrono in background
                ignore_user_abort(true);
                set_time_limit(120); // 2 minuti
                
                register_shutdown_function(function() use ($analysisManager, $analysisId, $cacheKey) {
                    try {
                        // Esegui l'analisi
                        $results = $analysisManager->analyzeDirectly();
                        
                        // Salva i risultati in cache
                        $this->cache->set($cacheKey, $results, 3600);
                        
                        // Standardizza i risultati utilizzando i DTO
                        $standardizedResults = $this->standardizeResults($results);
                        
                        // Aggiorna lo stato
                        $status = [
                            'analysisId' => $analysisId,
                            'status' => 'completed',
                            'progress' => 100,
                            'message' => 'Analisi completata con successo',
                            'results' => $standardizedResults,
                            'timestamp' => time()
                        ];
                        
                        $this->cache->set("status_{$analysisId}", $status, 3600);
                        error_log("Analisi completata per ID {$analysisId}");
                    } catch (Exception $e) {
                        error_log("Errore analisi: " . $e->getMessage());
                        
                        // Aggiorna lo stato con l'errore
                        $status = $this->cache->get("status_{$analysisId}") ?: [];
                        $status['status'] = 'failed';
                        $status['message'] = 'Errore durante l\'analisi: ' . $e->getMessage();
                        $this->cache->set("status_{$analysisId}", $status, 3600);
                    }
                });
            }
            
            // Restituisci lo stato iniziale e l'ID dell'analisi
            return [
                'status' => 'success',
                'complete' => false,
                'analysisId' => $analysisId,
                'message' => 'Analisi avviata, utilizzare l\'ID per controllare lo stato'
            ];
        } catch (Exception $e) {
            error_log("Errore avvio analisi: " . $e->getMessage());
            throw new Exception('Errore durante l\'avvio dell\'analisi: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Completa l'analisi e memorizza i risultati in cache
     * 
     * @param string $analysisId ID dell'analisi
     * @param array $results Risultati dell'analisi
     */
    private function completeAnalysis($analysisId, $results) {
        // Standardizza i risultati utilizzando i DTO
        $standardizedResults = $this->standardizeResults($results);
        
        // Memorizza i risultati in cache
        $cacheKey = md5($results['url1'] . '|' . $results['url2']);
        $this->cache->set($cacheKey, $standardizedResults, 3600); // Cache per 1 ora
        
        // Aggiorna lo stato dell'analisi
        $this->cache->set("status_{$analysisId}", [
            'status' => 'completed',
            'results' => $standardizedResults
        ], 1800); // Cache per 30 minuti
    }
    
    /**
     * Standardizza i risultati utilizzando i DTO
     * 
     * @param array $rawResults
     * @return array
     */
    private function standardizeResults($rawResults) {
        // Crea DTO per entrambi i siti
        $site1DTO = new AnalysisResultDTO($rawResults['url1'] ?? '', $rawResults['site1']);
        $site2DTO = new AnalysisResultDTO($rawResults['url2'] ?? '', $rawResults['site2']);
        
        // Ottieni array standardizzati
        $standardSite1 = $site1DTO->toArray();
        $standardSite2 = $site2DTO->toArray();
        
        // Costruisci il risultato finale standardizzato
        $standardResults = [
            'url1' => $rawResults['url1'] ?? $standardSite1['url'],
            'url2' => $rawResults['url2'] ?? $standardSite2['url'],
            'site1' => $standardSite1,
            'site2' => $standardSite2,
            'winner' => $rawResults['winner'] ?? null,
            'advantage' => $rawResults['advantage'] ?? 0,
            'victoryLevel' => $rawResults['victoryLevel'] ?? 'pareggio'
        ];
        
        // Mantieni le informazioni di confronto se presenti
        if (isset($rawResults['comparison'])) {
            $standardResults['comparison'] = $rawResults['comparison'];
        }
        
        // Aggiungi metadata e altre informazioni
        if (isset($rawResults['metadata'])) {
            $standardResults['metadata'] = $rawResults['metadata'];
            $standardResults['metadata']['version'] = '2.0.0'; // Aggiorna la versione per riflettere il nuovo formato
        }
        
        // Mantieni l'analisi dei punti di forza
        if (isset($rawResults['strengthAnalysis'])) {
            $standardResults['strengthAnalysis'] = $rawResults['strengthAnalysis'];
        }
        
        return $standardResults;
    }
}