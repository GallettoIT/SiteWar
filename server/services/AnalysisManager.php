<?php
/**
 * AnalysisManager
 * 
 * Responsabile del coordinamento dell'intero processo di analisi.
 * Gestisce l'inizializzazione degli analizzatori, l'esecuzione delle analisi
 * per entrambi i siti e il confronto dei risultati.
 * 
 * Pattern implementati:
 * - Facade
 * - Command
 * - Observer
 */

require_once __DIR__ . '/../core/ServiceFactory.php';
require_once __DIR__ . '/../utils/Cache.php';

class AnalysisManager {
    /**
     * @var string URL del primo sito
     */
    private $url1;
    
    /**
     * @var string URL del secondo sito
     */
    private $url2;
    
    /**
     * @var ServiceFactory Factory per la creazione di servizi e analizzatori
     */
    private $serviceFactory;
    
    /**
     * @var Cache Sistema di cache
     */
    private $cache;
    
    /**
     * @var array Configurazione dei timeout
     */
    private $timeoutConfig;
    
    /**
     * @var array Pesi per il calcolo del punteggio finale
     */
    private $weights = [
        'performance' => 0.30,
        'seo' => 0.25,
        'security' => 0.25,
        'technical' => 0.20
    ];
    
    /**
     * Costruttore
     * 
     * @param string $url1 URL del primo sito
     * @param string $url2 URL del secondo sito
     * @param array $config Configurazione opzionale
     */
    public function __construct($url1, $url2, $config = []) {
        $this->url1 = $url1;
        $this->url2 = $url2;
        $this->serviceFactory = $config['serviceFactory'] ?? new ServiceFactory();
        $this->cache = new Cache();
        $this->timeoutConfig = $config['timeouts'] ?? [
            'total' => 180,         // Aumentato a 3 minuti
            'performance' => 60,    // Aumentato a 1 minuto
            'seo' => 45,            // Aumentato a 45 secondi
            'security' => 45,       // Aumentato a 45 secondi
            'technology' => 30      // Aumentato a 30 secondi
        ];
    }
    
    /**
     * Analizza direttamente i siti e restituisce i risultati
     * 
     * @return array I risultati dell'analisi
     * @throws Exception Se si verifica un errore durante l'analisi
     */
    public function analyzeDirectly() {
        error_log("[ANALYSIS] Esecuzione analisi diretta per i siti {$this->url1} e {$this->url2}");
        
        try {
            // Ottiene i tipi di analizzatori disponibili
            $analyzerTypes = $this->serviceFactory->getAvailableAnalyzers();
            error_log("[ANALYSIS] Analizzatori disponibili: " . implode(', ', $analyzerTypes));
            
            // Risultati per entrambi i siti
            $results1 = [];
            $results2 = [];
            
            // Crea tutti gli analizzatori in anticipo
            $analyzers = [
                'site1' => [],
                'site2' => []
            ];
            
            foreach ($analyzerTypes as $type) {
                error_log("[ANALYSIS] Creazione analizzatore '{$type}' per entrambi i siti");
                
                $analyzers['site1'][$type] = $this->serviceFactory->createAnalyzer($type, $this->url1, [
                    'timeout' => $this->timeoutConfig[$type] ?? 10
                ]);
                
                $analyzers['site2'][$type] = $this->serviceFactory->createAnalyzer($type, $this->url2, [
                    'timeout' => $this->timeoutConfig[$type] ?? 10
                ]);
            }
            
            // Esegui le analisi per ogni sito
            foreach ($analyzerTypes as $type) {
                error_log("[ANALYSIS] Esecuzione analisi tipo '{$type}'");
                
                // Analisi sito 1
                try {
                    error_log("[ANALYSIS] Analisi '{$type}' per sito 1: {$this->url1}");
                    $startTime = microtime(true);
                    $success = $analyzers['site1'][$type]->analyze();
                    $duration = round((microtime(true) - $startTime) * 1000);
                    
                    if ($success) {
                        $results1[$type] = $analyzers['site1'][$type]->getResults();
                        error_log("[ANALYSIS] Completata analisi '{$type}' per sito 1 in {$duration}ms");
                        
                        // Dettaglio risultati per debug
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            if (isset($results1[$type]['totalScore'])) {
                                error_log("[ANALYSIS RESULT] Sito 1 '{$type}' score: " . $results1[$type]['totalScore']);
                            }
                            
                            // Verifica se ci sono risultati da API esterne
                            if (isset($results1[$type]['external'])) {
                                error_log("[ANALYSIS RESULT] Sito 1 '{$type}' ha dati da API esterne");
                                
                                // Log dettagliato dei dati API per debug
                                if ($type == 'seo' && isset($results1[$type]['external']['domain_authority'])) {
                                    error_log("[API DATA] Sito 1 SEO Moz domain_authority: " . $results1[$type]['external']['domain_authority']);
                                    error_log("[API DATA] Sito 1 SEO Moz page_authority: " . $results1[$type]['external']['page_authority']);
                                    error_log("[API DATA] Sito 1 SEO Moz backlinks: " . $results1[$type]['external']['backlinks']);
                                }
                                
                                if ($type == 'seo' && isset($results1[$type]['external']['creation_date'])) {
                                    error_log("[API DATA] Sito 1 SEO WHOIS data creazione: " . $results1[$type]['external']['creation_date']);
                                    error_log("[API DATA] Sito 1 SEO WHOIS data scadenza: " . $results1[$type]['external']['expiration_date']);
                                    error_log("[API DATA] Sito 1 SEO WHOIS registrar: " . $results1[$type]['external']['registrar']);
                                }
                            }
                            
                            // Verifica se è stato usato fallback
                            if (isset($results1[$type]['fallback']) && $results1[$type]['fallback']) {
                                error_log("[ANALYSIS RESULT] Sito 1 '{$type}' ha usato fallback");
                            }
                        }
                    } else {
                        throw new Exception($analyzers['site1'][$type]->getErrorMessage() ?: 'Errore non specificato');
                    }
                } catch (Exception $e) {
                    error_log("[ERROR] Errore analisi sito 1 '{$type}': " . $e->getMessage());
                    $results1[$type] = [
                        'totalScore' => 50,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'fallback' => true
                    ];
                }
                
                // Analisi sito 2
                try {
                    error_log("[ANALYSIS] Analisi '{$type}' per sito 2: {$this->url2}");
                    $startTime = microtime(true);
                    $success = $analyzers['site2'][$type]->analyze();
                    $duration = round((microtime(true) - $startTime) * 1000);
                    
                    if ($success) {
                        $results2[$type] = $analyzers['site2'][$type]->getResults();
                        error_log("[ANALYSIS] Completata analisi '{$type}' per sito 2 in {$duration}ms");
                        
                        // Dettaglio risultati per debug
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            if (isset($results2[$type]['totalScore'])) {
                                error_log("[ANALYSIS RESULT] Sito 2 '{$type}' score: " . $results2[$type]['totalScore']);
                            }
                            
                            // Verifica se ci sono risultati da API esterne
                            if (isset($results2[$type]['external'])) {
                                error_log("[ANALYSIS RESULT] Sito 2 '{$type}' ha dati da API esterne");
                                
                                // Log dettagliato dei dati API per debug
                                if ($type == 'seo' && isset($results2[$type]['external']['domain_authority'])) {
                                    error_log("[API DATA] Sito 2 SEO Moz domain_authority: " . $results2[$type]['external']['domain_authority']);
                                    error_log("[API DATA] Sito 2 SEO Moz page_authority: " . $results2[$type]['external']['page_authority']);
                                    error_log("[API DATA] Sito 2 SEO Moz backlinks: " . $results2[$type]['external']['backlinks']);
                                }
                                
                                if ($type == 'seo' && isset($results2[$type]['external']['creation_date'])) {
                                    error_log("[API DATA] Sito 2 SEO WHOIS data creazione: " . $results2[$type]['external']['creation_date']);
                                    error_log("[API DATA] Sito 2 SEO WHOIS data scadenza: " . $results2[$type]['external']['expiration_date']);
                                    error_log("[API DATA] Sito 2 SEO WHOIS registrar: " . $results2[$type]['external']['registrar']);
                                }
                            }
                            
                            // Verifica se è stato usato fallback
                            if (isset($results2[$type]['fallback']) && $results2[$type]['fallback']) {
                                error_log("[ANALYSIS RESULT] Sito 2 '{$type}' ha usato fallback");
                            }
                        }
                    } else {
                        throw new Exception($analyzers['site2'][$type]->getErrorMessage() ?: 'Errore non specificato');
                    }
                } catch (Exception $e) {
                    error_log("[ERROR] Errore analisi sito 2 '{$type}': " . $e->getMessage());
                    $results2[$type] = [
                        'totalScore' => 50,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'fallback' => true
                    ];
                }
            }
            
            // Elabora i risultati
            error_log("[ANALYSIS] Elaborazione e confronto dei risultati");
            $processedResults = $this->processResults($results1, $results2);
            
            // Calcola i punteggi finali
            error_log("[ANALYSIS] Calcolo punteggi finali");
            $finalResults = $this->calculateFinalScores($processedResults);
            
            // Dettaglio punteggi finali
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[ANALYSIS RESULT] Punteggio finale sito 1: " . $finalResults['site1']['totalScore']);
                error_log("[ANALYSIS RESULT] Punteggio finale sito 2: " . $finalResults['site2']['totalScore']);
                error_log("[ANALYSIS RESULT] Vincitore: " . $finalResults['winner']);
                error_log("[ANALYSIS RESULT] Livello vittoria: " . $finalResults['victoryLevel']);
            }
            
            // Log della struttura dei risultati per debug
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[ANALYSIS RESULT STRUCTURE] Struttura dati risultato finale: " . print_r(array_keys($finalResults), true));
                error_log("[ANALYSIS RESULT STRUCTURE] Struttura site1: " . print_r(array_keys($finalResults['site1']), true));
                error_log("[ANALYSIS RESULT STRUCTURE] Struttura site2: " . print_r(array_keys($finalResults['site2']), true));
                
                // Log di debug delle API usate
                $apiResults = [];
                foreach ($analyzerTypes as $type) {
                    if (isset($results1[$type]['external'])) {
                        $apiResults[$type] = true;
                        error_log("[ANALYSIS API USAGE] {$type} ha dati da API esterne per il sito 1");
                    }
                    if (isset($results2[$type]['external'])) {
                        $apiResults[$type] = true;
                        error_log("[ANALYSIS API USAGE] {$type} ha dati da API esterne per il sito 2");
                    }
                }
                
                if (!empty($apiResults)) {
                    error_log("[ANALYSIS] Analisi completata con successo. API utilizzate: " . implode(", ", array_keys($apiResults)));
                } else {
                    error_log("[ANALYSIS] Analisi completata con successo. Nessuna API esterna utilizzata.");
                }
            } else {
                error_log("[ANALYSIS] Analisi completata con successo");
            }
            
            return $finalResults;
        } catch (Exception $e) {
            error_log("[ERROR] Errore analisi: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Esegue l'analisi in modo asincrono con supporto per esecuzione parallela
     * 
     * In un ambiente reale, questo metodo verrebbe eseguito in un processo
     * separato o tramite un job in background, con supporto per thread multipli
     * o worker paralleli.
     * 
     * @param string $analysisId ID univoco dell'analisi
     */
    private function performAnalysisAsync($analysisId) {
        // In modalità DEBUG, aggiungiamo un ritardo simulato per ogni step per vedere il progresso
        $debugDelayPerStep = 1; // secondi
        
        // Aggiorna lo stato a "in corso"
        $this->updateAnalysisStatus($analysisId, 'in_progress', 5);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[ANALYSIS] Inizio analisi asincrona per ID: {$analysisId}");
            error_log("[ANALYSIS] Analisi di {$this->url1} vs {$this->url2}");
        }
        
        // In modalità debug, aggiungiamo un ritardo per simulare lavorazione
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            sleep($debugDelayPerStep);
        }
        
        try {
            // Ottiene i tipi di analizzatori disponibili
            $analyzerTypes = $this->serviceFactory->getAvailableAnalyzers();
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[ANALYSIS] Analizzatori disponibili: " . implode(', ', $analyzerTypes));
            }
            
            // Risultati per entrambi i siti
            $results1 = [];
            $results2 = [];
            
            // Esegue l'analisi con ogni tipo di analizzatore in modalità pseudo-parallela
            $progress = 5;
            $progressIncrement = 70 / (count($analyzerTypes) * 2);
            
            // Memorizza il tempo di inizio dell'analisi
            $startTime = microtime(true);
            $timeoutReached = false;
            
            // Crea tutti gli analizzatori in anticipo
            $analyzers = [
                'site1' => [],
                'site2' => []
            ];
            
            foreach ($analyzerTypes as $type) {
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("[ANALYSIS] Creazione analizzatore '{$type}' per entrambi i siti");
                }
                
                $analyzers['site1'][$type] = $this->serviceFactory->createAnalyzer($type, $this->url1, [
                    'timeout' => $this->timeoutConfig[$type] ?? $this->timeoutConfig['total'] / count($analyzerTypes)
                ]);
                
                $analyzers['site2'][$type] = $this->serviceFactory->createAnalyzer($type, $this->url2, [
                    'timeout' => $this->timeoutConfig[$type] ?? $this->timeoutConfig['total'] / count($analyzerTypes)
                ]);
            }
            
            // Prima strategia di parallelizzazione: esegui in parallelo l'analisi per entrambi i siti
            // Nota: In PHP puro questo è simulato, ma il codice è strutturato per 
            // supportare una vera parallelizzazione in ambienti che lo consentono
            foreach ($analyzerTypes as $type) {
                // Aggiorna lo stato indicando quale analizzatore sta per essere utilizzato
                $this->updateAnalysisStatus($analysisId, 'in_progress', $progress, "Analisi {$type} in corso...");
                
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("[ANALYSIS] Avvio analisi di tipo '{$type}' per entrambi i siti (progresso: {$progress}%)");
                }
                
                // In modalità debug, aggiungiamo un ritardo per simulare lavorazione
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    sleep($debugDelayPerStep);
                }
                
                // Verifica se il timeout totale è stato raggiunto
                $elapsedTime = microtime(true) - $startTime;
                if ($elapsedTime >= $this->timeoutConfig['total']) {
                    $timeoutReached = true;
                    break;
                }
                
                // Esecuzione "parallela" degli analizzatori per entrambi i siti
                // In un ambiente multi-thread, questi verrebbero eseguiti in thread separati
                $analysisTasks = [
                    'site1' => function() use ($analyzers, $type) {
                        $analyzers['site1'][$type]->analyze();
                        return $analyzers['site1'][$type]->getResults();
                    },
                    'site2' => function() use ($analyzers, $type) {
                        $analyzers['site2'][$type]->analyze();
                        return $analyzers['site2'][$type]->getResults();
                    }
                ];
                
                // Esegui le analisi con gestione degli errori
                try {
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        error_log("[ANALYSIS] Esecuzione analisi '{$type}' per il sito 1: {$this->url1}");
                    }
                    
                    $startTime = microtime(true);
                    $results1[$type] = $analysisTasks['site1']();
                    $duration = round((microtime(true) - $startTime) * 1000);
                    
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        error_log("[ANALYSIS] Completata analisi '{$type}' per il sito 1 in {$duration}ms");
                        
                        // Dettaglio risultati per debug
                        if (isset($results1[$type]['totalScore'])) {
                            error_log("[ANALYSIS RESULT] Sito 1 '{$type}' score: " . $results1[$type]['totalScore']);
                        }
                        
                        // Verifica se ci sono risultati da API esterne
                        if (isset($results1[$type]['external'])) {
                            error_log("[ANALYSIS RESULT] Sito 1 '{$type}' ha dati da API esterne");
                            
                            // Aggiungi dettagli specifici per ogni tipo di API
                            if ($type == 'security' && isset($results1[$type]['external']['grade'])) {
                                error_log("[ANALYSIS RESULT] Sito 1 Security Headers grade: " . $results1[$type]['external']['grade']);
                            }
                            
                            if ($type == 'seo' && isset($results1[$type]['external']['domain_authority'])) {
                                error_log("[ANALYSIS RESULT] Sito 1 Moz domain authority: " . $results1[$type]['external']['domain_authority']);
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("[ERROR] Errore durante l'analisi del sito 1 ({$type}): " . $e->getMessage());
                    $results1[$type] = [
                        'totalScore' => 50,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'fallback' => true
                    ];
                }
                
                try {
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        error_log("[ANALYSIS] Esecuzione analisi '{$type}' per il sito 2: {$this->url2}");
                    }
                    
                    $startTime = microtime(true);
                    $results2[$type] = $analysisTasks['site2']();
                    $duration = round((microtime(true) - $startTime) * 1000);
                    
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        error_log("[ANALYSIS] Completata analisi '{$type}' per il sito 2 in {$duration}ms");
                        
                        // Dettaglio risultati per debug
                        if (isset($results2[$type]['totalScore'])) {
                            error_log("[ANALYSIS RESULT] Sito 2 '{$type}' score: " . $results2[$type]['totalScore']);
                        }
                        
                        // Verifica se ci sono risultati da API esterne
                        if (isset($results2[$type]['external'])) {
                            error_log("[ANALYSIS RESULT] Sito 2 '{$type}' ha dati da API esterne");
                            
                            // Aggiungi dettagli specifici per ogni tipo di API
                            if ($type == 'security' && isset($results2[$type]['external']['grade'])) {
                                error_log("[ANALYSIS RESULT] Sito 2 Security Headers grade: " . $results2[$type]['external']['grade']);
                            }
                            
                            if ($type == 'seo' && isset($results2[$type]['external']['domain_authority'])) {
                                error_log("[ANALYSIS RESULT] Sito 2 Moz domain authority: " . $results2[$type]['external']['domain_authority']);
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("[ERROR] Errore durante l'analisi del sito 2 ({$type}): " . $e->getMessage());
                    $results2[$type] = [
                        'totalScore' => 50,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'fallback' => true
                    ];
                }
                
                // Aggiorna il progresso
                $progress += $progressIncrement * 2; // Incremento doppio perché eseguiamo entrambi i siti
                $this->updateAnalysisStatus($analysisId, 'in_progress', $progress);
                
                // Piccola pausa per rendere visibile il progresso (solo in modalità debug)
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    sleep(1);
                }
            }
            
            // Gestione del timeout
            if ($timeoutReached) {
                // Informa l'utente che l'analisi è stata interrotta per timeout
                $this->updateAnalysisStatus($analysisId, 'in_progress', $progress, 
                    "Analisi interrotta per timeout. Elaborazione risultati parziali...");
            }
            
            // Aggiorna lo stato
            $this->updateAnalysisStatus($analysisId, 'in_progress', 80, "Elaborazione risultati...");
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[ANALYSIS] Elaborazione risultati (progresso: 80%)");
                
                // Riassunto dei risultati raccolti per sito 1
                error_log("[ANALYSIS] Sito 1 analizzatori completati: " . implode(', ', array_keys($results1)));
                foreach ($results1 as $type => $data) {
                    if (isset($data['totalScore'])) {
                        error_log("[ANALYSIS] Sito 1 {$type} score: " . $data['totalScore']);
                    }
                    if (isset($data['fallback']) && $data['fallback']) {
                        error_log("[ANALYSIS] Sito 1 {$type} usa fallback");
                    }
                }
                
                // Riassunto dei risultati raccolti per sito 2
                error_log("[ANALYSIS] Sito 2 analizzatori completati: " . implode(', ', array_keys($results2)));
                foreach ($results2 as $type => $data) {
                    if (isset($data['totalScore'])) {
                        error_log("[ANALYSIS] Sito 2 {$type} score: " . $data['totalScore']);
                    }
                    if (isset($data['fallback']) && $data['fallback']) {
                        error_log("[ANALYSIS] Sito 2 {$type} usa fallback");
                    }
                }
            }
            
            // In modalità debug, aggiungiamo un ritardo per simulare lavorazione
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                sleep($debugDelayPerStep);
            }
            
            // Elabora i risultati (anche se parziali in caso di timeout)
            error_log("[ANALYSIS] Elaborazione e confronto dei risultati");
            $processedResults = $this->processResults($results1, $results2);
            
            // Aggiorna lo stato
            $this->updateAnalysisStatus($analysisId, 'in_progress', 90, "Calcolo punteggi...");
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[ANALYSIS] Calcolo punteggi finali (progresso: 90%)");
            }
            
            // Calcola i punteggi finali
            error_log("[ANALYSIS] Calcolo punteggi finali");
            $finalResults = $this->calculateFinalScores($processedResults);
            
            // Aggiunge informazioni sul timeout, se applicabile
            if ($timeoutReached) {
                $finalResults['timeoutReached'] = true;
                $finalResults['completedAnalyzers'] = array_keys($results1);
                $finalResults['missingAnalyzers'] = array_diff($analyzerTypes, array_keys($results1));
                error_log("[ANALYSIS] Timeout raggiunto. Analizzatori completati: " . implode(', ', $finalResults['completedAnalyzers']));
                error_log("[ANALYSIS] Analizzatori mancanti: " . implode(', ', $finalResults['missingAnalyzers']));
            }
            
            // Aggiorna lo stato a "completato" con i risultati finali
            $status = $timeoutReached ? 'completed_partial' : 'completed';
            $message = $timeoutReached ? "Analisi completata parzialmente (timeout)" : "Analisi completata";
            $this->updateAnalysisStatus($analysisId, $status, 100, $message, $finalResults);
            
            // Riepilogo finale dettagliato
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $winner = $finalResults['winner'] ?? 'Nessun vincitore determinato';
                $victoryLevel = $finalResults['victoryLevel'] ?? 'non determinato';
                error_log("[ANALYSIS] COMPLETATA (ID: {$analysisId})");
                error_log("[ANALYSIS RESULT] Vincitore: {$winner}");
                error_log("[ANALYSIS RESULT] Livello vittoria: {$victoryLevel}");
                error_log("[ANALYSIS RESULT] Punteggio finale sito 1: " . $finalResults['site1']['totalScore']);
                error_log("[ANALYSIS RESULT] Punteggio finale sito 2: " . $finalResults['site2']['totalScore']);
                error_log("[ANALYSIS RESULT] Status: {$status}");
                
                // Log dei punteggi per categoria
                foreach ($this->weights as $category => $weight) {
                    if (isset($finalResults['site1']["{$category}Score"])) {
                        $score1 = $finalResults['site1']["{$category}Score"];
                        $score2 = $finalResults['site2']["{$category}Score"];
                        error_log("[ANALYSIS RESULT] Categoria {$category}: Sito 1 = {$score1}, Sito 2 = {$score2}");
                    }
                }
                
                // Log della struttura dei risultati per debug
                error_log("[ANALYSIS RESULT STRUCTURE] Struttura dati risultato finale (asincrono): " . print_r(array_keys($finalResults), true));
                error_log("[ANALYSIS RESULT STRUCTURE] Struttura site1 (asincrono): " . print_r(array_keys($finalResults['site1']), true));
                error_log("[ANALYSIS RESULT STRUCTURE] Struttura site2 (asincrono): " . print_r(array_keys($finalResults['site2']), true));
                
                // Log di debug delle API usate (asincrono)
                $apiResults = [];
                foreach ($analyzerTypes as $type) {
                    if (isset($results1[$type]['external'])) {
                        $apiResults[$type] = true;
                        error_log("[ANALYSIS API USAGE] {$type} ha dati da API esterne per il sito 1 (asincrono)");
                    }
                    if (isset($results2[$type]['external'])) {
                        $apiResults[$type] = true;
                        error_log("[ANALYSIS API USAGE] {$type} ha dati da API esterne per il sito 2 (asincrono)");
                    }
                }
                
                if (!empty($apiResults)) {
                    error_log("[ANALYSIS] Analisi asincrona completata. API utilizzate: " . implode(", ", array_keys($apiResults)));
                } else {
                    error_log("[ANALYSIS] Analisi asincrona completata. Nessuna API esterna utilizzata.");
                }
            }
            
        } catch (Exception $e) {
            // In caso di errore, aggiorna lo stato a "fallito"
            $this->updateAnalysisStatus($analysisId, 'failed', $progress, $e->getMessage());
            error_log("[ERROR] Analisi fallita (ID: {$analysisId}) - " . $e->getMessage());
        }
    }
    
    /**
     * Aggiorna lo stato dell'analisi nella cache
     * 
     * @param string $analysisId ID univoco dell'analisi
     * @param string $status Stato dell'analisi (initiated, in_progress, completed, failed)
     * @param int $progress Percentuale di avanzamento (0-100)
     * @param string $message Messaggio opzionale
     * @param array $results Risultati dell'analisi, se disponibili
     */
    private function updateAnalysisStatus($analysisId, $status, $progress, $message = '', $results = null) {
        $statusData = [
            'analysisId' => $analysisId,
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'timestamp' => time()
        ];
        
        if ($results) {
            $statusData['results'] = $results;
        }
        
        // Per debugging, visualizza l'ID dell'analisi quando viene aggiornato lo stato
        if (DEBUG_MODE) {
            error_log("Aggiornato stato analisi: {$analysisId} - Status: {$status} - Progress: {$progress}");
        }
        
        // Salva nel formato richiesto da ReportController
        $cacheKey = "status_{$analysisId}";
        $success = $this->cache->set($cacheKey, $statusData, 3600); // Cache per 1 ora
        
        // Forza il flush della cache su disco
        $this->cache->flush();
        
        // Verifica che lo stato sia stato effettivamente salvato
        if (DEBUG_MODE) {
            $savedStatus = $this->cache->get($cacheKey);
            if (!$savedStatus) {
                error_log("[ERROR] Stato analisi non salvato correttamente: {$analysisId}");
            } else {
                error_log("[DEBUG] Stato analisi verificato: {$analysisId} - Status: {$savedStatus['status']} - Progress: {$savedStatus['progress']}");
            }
        }
        
        return $success;
    }
    
    /**
     * Elabora i risultati grezzi dell'analisi
     * 
     * @param array $results1 Risultati per il primo sito
     * @param array $results2 Risultati per il secondo sito
     * @return array Risultati elaborati
     */
    private function processResults($results1, $results2) {
        // Mappatura degli analizzatori alle categorie principali
        $categoryMap = [
            'performance' => 'performance',
            'seo' => 'seo',
            'security' => 'security',
            'technology' => 'technical', // 'technology' diventa 'technical'
            'dom' => 'technical' // Il DOM analyzer contribuisce alla categoria technical
        ];
        
        // Inizializza le categorie per entrambi i siti
        $site1Results = [
            'metrics' => [
                'performance' => [],
                'seo' => [],
                'security' => [],
                'technical' => []
            ],
            'categories' => [
                'performance' => 0,
                'seo' => 0,
                'security' => 0,
                'technical' => 0
            ],
            'performance' => [],
            'seo' => [],
            'security' => [],
            'technical' => []
        ];
        
        $site2Results = [
            'metrics' => [
                'performance' => [],
                'seo' => [],
                'security' => [],
                'technical' => []
            ],
            'categories' => [
                'performance' => 0,
                'seo' => 0,
                'security' => 0,
                'technical' => 0
            ],
            'performance' => [],
            'seo' => [],
            'security' => [],
            'technical' => []
        ];
        
        // Redistribuisci i risultati nelle categorie appropriate
        foreach ($results1 as $analyzerType => $analyzerResults) {
            $category = $categoryMap[$analyzerType] ?? $analyzerType;
            
            // Aggiungi i risultati alla categoria appropriata
            if (isset($site1Results[$category])) {
                // Prefissa le chiavi per evitare conflitti
                $prefix = $analyzerType . '_';
                foreach ($analyzerResults as $key => $value) {
                    if ($key === 'totalScore') {
                        // I punteggi totali vengono gestiti separatamente
                        $site1Results[$category][$analyzerType . 'Score'] = $value;
                        // Aggiungi anche alle categorie per compatibilità
                        $site1Results['categories'][$category] = $value;
                    } else if ($key !== 'external' && $key !== 'fallback' && $key !== 'status') {
                        // Tutti gli altri dati vengono prefissati nel formato originale
                        $site1Results[$category][$prefix . $key] = $value;
                        
                        // E vengono aggiunti anche nella nuova struttura metrics senza prefisso
                        $site1Results['metrics'][$category][$key] = $value;
                    }
                }
                
                // Estrai dati da API esterne se presenti
                if (isset($analyzerResults['external'])) {
                    $this->extractExternalApiData($site1Results, $category, $analyzerResults['external']);
                }
            }
        }
        
        foreach ($results2 as $analyzerType => $analyzerResults) {
            $category = $categoryMap[$analyzerType] ?? $analyzerType;
            
            // Aggiungi i risultati alla categoria appropriata
            if (isset($site2Results[$category])) {
                // Prefissa le chiavi per evitare conflitti
                $prefix = $analyzerType . '_';
                foreach ($analyzerResults as $key => $value) {
                    if ($key === 'totalScore') {
                        // I punteggi totali vengono gestiti separatamente
                        $site2Results[$category][$analyzerType . 'Score'] = $value;
                        // Aggiungi anche alle categorie per compatibilità
                        $site2Results['categories'][$category] = $value;
                    } else if ($key !== 'external' && $key !== 'fallback' && $key !== 'status') {
                        // Tutti gli altri dati vengono prefissati nel formato originale
                        $site2Results[$category][$prefix . $key] = $value;
                        
                        // E vengono aggiunti anche nella nuova struttura metrics senza prefisso
                        $site2Results['metrics'][$category][$key] = $value;
                    }
                }
                
                // Estrai dati da API esterne se presenti
                if (isset($analyzerResults['external'])) {
                    $this->extractExternalApiData($site2Results, $category, $analyzerResults['external']);
                }
            }
        }
        
        // Assicura che tutte le metriche richieste dal frontend siano presenti
        $this->ensureRequiredMetrics($site1Results);
        $this->ensureRequiredMetrics($site2Results);
        
        // Aggiungi metadati sui risultati analizzati
        $metadata = [
            'analyzers' => array_keys($results1),
            'categories' => array_keys($site1Results['metrics']),
            'timeStamp' => time(),
            'analysisVersion' => '2.0.0' // Versione dell'algoritmo di analisi
        ];
        
        // Calcola punteggi aggregati per ogni categoria
        $this->calculateAggregatedScores($site1Results);
        $this->calculateAggregatedScores($site2Results);
        
        // Confronta i risultati
        $comparison = $this->compareResults($site1Results, $site2Results);
        
        return [
            'url1' => $this->url1,
            'url2' => $this->url2,
            'site1' => $site1Results,
            'site2' => $site2Results,
            'comparison' => $comparison,
            'metadata' => $metadata
        ];
    }
    
    /**
     * Estrae dati da API esterne e li aggiunge alle metriche
     * 
     * @param array &$siteResults Risultati del sito
     * @param string $category Categoria
     * @param array $externalData Dati esterni
     */
    private function extractExternalApiData(&$siteResults, $category, $externalData) {
        if ($category === 'seo') {
            // Estrai dati SEO esterni (Moz, WHOIS, ecc.)
            if (isset($externalData['domain_authority'])) {
                $siteResults['metrics']['seo']['domain_authority'] = $externalData['domain_authority'];
            }
            if (isset($externalData['page_authority'])) {
                $siteResults['metrics']['seo']['page_authority'] = $externalData['page_authority'];
            }
            if (isset($externalData['backlinks'])) {
                $siteResults['metrics']['seo']['backlinks'] = $externalData['backlinks'];
            }
        } elseif ($category === 'security') {
            // Estrai dati di sicurezza esterni
            if (isset($externalData['grade'])) {
                $siteResults['metrics']['security']['ssl_grade'] = $externalData['grade'];
            }
        }
    }
    
    /**
     * Assicura che tutte le metriche richieste dal frontend siano presenti
     * 
     * @param array &$siteResults Risultati del sito
     */
    private function ensureRequiredMetrics(&$siteResults) {
        $this->ensurePerformanceMetrics($siteResults);
        $this->ensureSeoMetrics($siteResults);
        $this->ensureSecurityMetrics($siteResults);
        $this->ensureTechnicalMetrics($siteResults);
    }
    
    /**
     * Calcola i punteggi aggregati per ogni categoria
     * 
     * @param array &$siteResults Risultati del sito da elaborare (passato per riferimento)
     */
    private function calculateAggregatedScores(&$siteResults) {
        // Pesi per gli analizzatori all'interno di ogni categoria
        $analyzerWeights = [
            'performance' => [
                'performance' => 1.0
            ],
            'seo' => [
                'seo' => 1.0
            ],
            'security' => [
                'security' => 1.0
            ],
            'technical' => [
                'technology' => 0.7,
                'dom' => 0.3
            ]
        ];
        
        // Calcola il punteggio aggregato per ogni categoria
        foreach ($analyzerWeights as $category => $weights) {
            // Se la categoria ha già un punteggio nelle categories, usalo
            if (isset($siteResults['categories'][$category]) && $siteResults['categories'][$category] > 0) {
                // Usa il punteggio già calcolato
                $aggregatedScore = $siteResults['categories'][$category];
            } else {
                // Altrimenti calcola il punteggio aggregato
                $totalScore = 0;
                $weightSum = 0;
                
                foreach ($weights as $analyzer => $weight) {
                    if (isset($siteResults[$category][$analyzer . 'Score'])) {
                        $totalScore += $siteResults[$category][$analyzer . 'Score'] * $weight;
                        $weightSum += $weight;
                    }
                }
                
                // Imposta il punteggio aggregato solo se ci sono dati
                if ($weightSum > 0) {
                    $aggregatedScore = round($totalScore / $weightSum, 2);
                } else {
                    // Se non ci sono dati, usa un valore di default neutro
                    $aggregatedScore = 50;
                }
                
                // Salva il punteggio nelle categories per compatibilità
                $siteResults['categories'][$category] = $aggregatedScore;
            }
            
            // Salva il punteggio aggregato sia nella struttura originale che nella nuova
            $siteResults[$category]['aggregatedScore'] = $aggregatedScore;
            
            // Aggiungi anche i valori specifici necessari per la visualizzazione
            if ($category === 'performance') {
                $this->ensurePerformanceMetrics($siteResults);
            } else if ($category === 'seo') {
                $this->ensureSeoMetrics($siteResults);
            } else if ($category === 'security') {
                $this->ensureSecurityMetrics($siteResults);
            } else if ($category === 'technical') {
                $this->ensureTechnicalMetrics($siteResults);
            }
        }
    }
    
    /**
     * Assicura che le metriche principali per la performance siano disponibili
     * 
     * @param array &$siteResults Risultati del sito (passato per riferimento)
     */
    private function ensurePerformanceMetrics(&$siteResults) {
        $requiredMetrics = [
            'first_contentful_paint',
            'largest_contentful_paint',
            'time_to_interactive',
            'cumulative_layout_shift'
        ];
        
        foreach ($requiredMetrics as $metric) {
            // Se la metrica è mancante, cercala in altre fonti
            if (!isset($siteResults['metrics']['performance'][$metric])) {
                $value = $this->findPerformanceMetricValue($siteResults, $metric);
                $siteResults['metrics']['performance'][$metric] = $value;
            }
        }
    }
    
    /**
     * Trova il valore di una metrica di performance da fonti alternative
     * 
     * @param array $siteResults Risultati del sito
     * @param string $metric Nome della metrica
     * @return mixed Valore della metrica o null
     */
    private function findPerformanceMetricValue($siteResults, $metric) {
        // Verifica nella struttura performance_localMetrics
        if (isset($siteResults['performance']['performance_localMetrics'])) {
            $local = $siteResults['performance']['performance_localMetrics'];
            
            switch ($metric) {
                case 'first_contentful_paint':
                    return $local['timeToFirstByte'] ?? null;
                    
                case 'largest_contentful_paint':
                    return $local['loadTime'] ?? null;
                    
                case 'time_to_interactive':
                    return $local['loadTime'] ?? null;
                    
                case 'cumulative_layout_shift':
                    // Valore approssimativo basato su analytics
                    return isset($local['score']) ? 0.1 : null;
            }
        }
        
        // Verifica in performance_pageSpeed
        if (isset($siteResults['performance']['performance_pageSpeed']) && 
            isset($siteResults['performance']['performance_pageSpeed']['metrics'])) {
            $metrics = $siteResults['performance']['performance_pageSpeed']['metrics'];
            if (isset($metrics[$metric])) {
                return $metrics[$metric];
            }
        }
        
        // Non trovato
        return null;
    }
    
    /**
     * Assicura che le metriche principali per SEO siano disponibili
     * 
     * @param array &$siteResults Risultati del sito (passato per riferimento)
     */
    private function ensureSeoMetrics(&$siteResults) {
        $requiredMetrics = [
            'meta_title',
            'meta_description',
            'headings_structure',
            'alt_tags',
            'url_structure'
        ];
        
        foreach ($requiredMetrics as $metric) {
            // Se la metrica è mancante, cercala in altre fonti
            if (!isset($siteResults['metrics']['seo'][$metric])) {
                $value = $this->findSeoMetricValue($siteResults, $metric);
                $siteResults['metrics']['seo'][$metric] = $value;
            }
        }
    }
    
    /**
     * Trova il valore di una metrica SEO da fonti alternative
     * 
     * @param array $siteResults Risultati del sito
     * @param string $metric Nome della metrica
     * @return mixed Valore della metrica o null
     */
    private function findSeoMetricValue($siteResults, $metric) {
        // Verifica nelle specifiche sottostrutture SEO
        if (isset($siteResults['seo'])) {
            switch ($metric) {
                case 'meta_title':
                    if (isset($siteResults['seo']['seo_metaTags']['title'])) {
                        return $siteResults['seo']['seo_metaTags']['title'];
                    }
                    break;
                    
                case 'meta_description':
                    if (isset($siteResults['seo']['seo_metaTags']['description'])) {
                        return $siteResults['seo']['seo_metaTags']['description'];
                    }
                    break;
                    
                case 'headings_structure':
                    if (isset($siteResults['seo']['seo_headings']['score'])) {
                        return $siteResults['seo']['seo_headings']['score'];
                    }
                    break;
                    
                case 'alt_tags':
                    if (isset($siteResults['seo']['seo_images']['altPercentage'])) {
                        return $siteResults['seo']['seo_images']['altPercentage'];
                    }
                    break;
                    
                case 'url_structure':
                    if (isset($siteResults['seo']['seo_url']['score'])) {
                        return $siteResults['seo']['seo_url']['score'];
                    } else if (isset($siteResults['seo']['seo_url']['seoFriendly'])) {
                        return $siteResults['seo']['seo_url']['seoFriendly'] ? 100 : 0;
                    }
                    break;
            }
        }
        
        // Non trovato
        return null;
    }
    
    /**
     * Assicura che le metriche principali per la sicurezza siano disponibili
     * 
     * @param array &$siteResults Risultati del sito (passato per riferimento)
     */
    private function ensureSecurityMetrics(&$siteResults) {
        $requiredMetrics = [
            'ssl_grade',
            'headers_score',
            'vulnerabilities'
        ];
        
        foreach ($requiredMetrics as $metric) {
            // Se la metrica è mancante, cercala in altre fonti
            if (!isset($siteResults['metrics']['security'][$metric])) {
                $value = $this->findSecurityMetricValue($siteResults, $metric);
                $siteResults['metrics']['security'][$metric] = $value;
            }
        }
    }
    
    /**
     * Trova il valore di una metrica di sicurezza da fonti alternative
     * 
     * @param array $siteResults Risultati del sito
     * @param string $metric Nome della metrica
     * @return mixed Valore della metrica o null
     */
    private function findSecurityMetricValue($siteResults, $metric) {
        // Verifica nelle specifiche sottostrutture security
        if (isset($siteResults['security'])) {
            switch ($metric) {
                case 'ssl_grade':
                    if (isset($siteResults['security']['security_ssl'])) {
                        $ssl = $siteResults['security']['security_ssl'];
                        if (isset($ssl['grade'])) {
                            return $ssl['grade'];
                        } else if (isset($ssl['score'])) {
                            return $ssl['score'];
                        } else if (isset($ssl['certificate']['issuer'])) {
                            return $ssl['certificate']['issuer'];
                        }
                    }
                    break;
                    
                case 'headers_score':
                    if (isset($siteResults['security']['security_securityHeaders']['score'])) {
                        return $siteResults['security']['security_securityHeaders']['score'];
                    }
                    break;
                    
                case 'vulnerabilities':
                    if (isset($siteResults['security']['security_vulnerabilities'])) {
                        $vulns = $siteResults['security']['security_vulnerabilities'];
                        if (isset($vulns['count'])) {
                            return $vulns['count'];
                        } else if (isset($vulns['score'])) {
                            return $vulns['score'];
                        }
                    }
                    break;
            }
        }
        
        // Non trovato
        return null;
    }
    
    /**
     * Assicura che le metriche principali per gli aspetti tecnici siano disponibili
     * 
     * @param array &$siteResults Risultati del sito (passato per riferimento)
     */
    private function ensureTechnicalMetrics(&$siteResults) {
        $requiredMetrics = [
            'html_validation',
            'css_validation',
            'technologies'
        ];
        
        foreach ($requiredMetrics as $metric) {
            // Se la metrica è mancante, cercala in altre fonti
            if (!isset($siteResults['metrics']['technical'][$metric])) {
                $value = $this->findTechnicalMetricValue($siteResults, $metric);
                $siteResults['metrics']['technical'][$metric] = $value;
            }
        }
    }
    
    /**
     * Trova il valore di una metrica tecnica da fonti alternative
     * 
     * @param array $siteResults Risultati del sito
     * @param string $metric Nome della metrica
     * @return mixed Valore della metrica o null
     */
    private function findTechnicalMetricValue($siteResults, $metric) {
        // Verifica nelle specifiche sottostrutture technical
        if (isset($siteResults['technical']) || isset($siteResults['technology'])) {
            // Supporta sia 'technical' che 'technology' per compatibilità
            $tech = $siteResults['technical'] ?? $siteResults['technology'];
            
            switch ($metric) {
                case 'html_validation':
                    // Cerca in technology_validation o altri campi pertinenti
                    if (isset($tech['technology_validation']['html'])) {
                        return $tech['technology_validation']['html'];
                    }
                    break;
                    
                case 'css_validation':
                    // Cerca in technology_validation o altri campi pertinenti
                    if (isset($tech['technology_validation']['css'])) {
                        return $tech['technology_validation']['css'];
                    }
                    break;
                    
                case 'technologies':
                    // Cerca in technology_detected o altri campi pertinenti
                    if (isset($tech['technology_detected'])) {
                        return $tech['technology_detected'];
                    } else if (isset($tech['detected'])) {
                        return $tech['detected'];
                    }
                    break;
            }
        }
        
        // Non trovato
        return null;
    }
    
    /**
     * Confronta i risultati tra i due siti
     * 
     * @param array $site1Results Risultati del primo sito
     * @param array $site2Results Risultati del secondo sito
     * @return array Risultati del confronto
     */
    private function compareResults($site1Results, $site2Results) {
        $comparison = [];
        $site1 = parse_url($this->url1, PHP_URL_HOST);
        $site2 = parse_url($this->url2, PHP_URL_HOST);
        
        // Lista di metriche per cui un valore più basso è migliore
        $lowerIsBetterMetrics = [
            'loadTime', 'ttfb', 'requestCount', 'pageSize', 
            'vulnerabilityCount', 'errorCount', 'performance_localMetrics_timeToFirstByte',
            'performance_localMetrics_loadTime', 'performance_localMetrics_resourceCount',
            'dom_domStructure_elementCount', 'dom_domStructure_depth', 'dom_htmlValidity_errorCount',
            'dom_htmlValidity_warningCount', 'security_vulnerabilities_count',
            'technology_renderBlocking_total'
        ];
        
        // Confronta ogni categoria
        foreach ($site1Results as $category => $metrics) {
            $comparison[$category] = [];
            
            // Confronta aggregatedScore come metrica principale per ogni categoria
            if (isset($metrics['aggregatedScore']) && isset($site2Results[$category]['aggregatedScore'])) {
                $value1 = $metrics['aggregatedScore'];
                $value2 = $site2Results[$category]['aggregatedScore'];
                
                // Determina il vincitore a livello di categoria
                if ($value1 == $value2) {
                    $winner = 'Pareggio';
                    $advantage = 0;
                } else {
                    if ($value1 > $value2) {
                        $winner = $site1;
                        $advantage = ($value1 / max(1, $value2)) - 1;
                    } else {
                        $winner = $site2;
                        $advantage = ($value2 / max(1, $value1)) - 1;
                    }
                    
                    // Limita il vantaggio a un massimo del 200%
                    $advantage = min($advantage, 2);
                }
                
                // Memorizza il risultato del confronto per la categoria
                $comparison[$category]['aggregatedScore'] = [
                    'winner' => $winner,
                    'advantage' => $advantage,
                    'score1' => $value1,
                    'score2' => $value2
                ];
            }
            
            // Confronta le metriche specifiche all'interno della categoria
            foreach ($metrics as $metric => $value1) {
                // Salta la metrica aggregata che abbiamo già elaborato
                if ($metric === 'aggregatedScore') {
                    continue;
                }
                
                $value2 = $site2Results[$category][$metric] ?? null;
                
                // Se entrambi i valori sono disponibili, determina il vincitore
                if ($value1 !== null && $value2 !== null && is_numeric($value1) && is_numeric($value2)) {
                    // Per la maggior parte delle metriche, un valore più alto è migliore
                    $higherIsBetter = true;
                    
                    // Eccezioni: per alcune metriche un valore più basso è migliore
                    if (in_array($metric, $lowerIsBetterMetrics) || preg_match('/(time|size|count|depth|error)/i', $metric)) {
                        $higherIsBetter = false;
                    }
                    
                    // Determina il vincitore
                    if ($value1 == $value2) {
                        $winner = 'Pareggio';
                        $advantage = 0;
                    } else {
                        if (($higherIsBetter && $value1 > $value2) || (!$higherIsBetter && $value1 < $value2)) {
                            $winner = $site1;
                            $advantage = $higherIsBetter ? 
                                ($value1 / max(1, $value2)) - 1 : 
                                ($value2 / max(1, $value1)) - 1;
                        } else {
                            $winner = $site2;
                            $advantage = $higherIsBetter ? 
                                ($value2 / max(1, $value1)) - 1 : 
                                ($value1 / max(1, $value2)) - 1;
                        }
                        
                        // Limita il vantaggio a un massimo del 200%
                        $advantage = min($advantage, 2);
                    }
                    
                    // Memorizza i risultati del confronto
                    $comparison[$category][$metric] = [
                        'winner' => $winner,
                        'advantage' => $advantage,
                        'higherIsBetter' => $higherIsBetter,
                        'value1' => $value1,
                        'value2' => $value2
                    ];
                }
            }
        }
        
        // Calcola statistiche sul confronto
        $this->calculateComparisonStatistics($comparison, $site1, $site2);
        
        return $comparison;
    }
    
    /**
     * Calcola statistiche sul confronto complessivo
     * 
     * @param array &$comparison Risultati del confronto (passato per riferimento)
     * @param string $site1 Nome del primo sito
     * @param string $site2 Nome del secondo sito
     */
    private function calculateComparisonStatistics(&$comparison, $site1, $site2) {
        // Inizializza statistiche di confronto
        $statistics = [
            'winsByCategory' => [
                $site1 => 0,
                $site2 => 0,
                'Pareggio' => 0
            ],
            'winsByMetric' => [
                $site1 => 0,
                $site2 => 0,
                'Pareggio' => 0
            ],
            'advantagesByCategory' => [
                $site1 => 0,
                $site2 => 0
            ],
            'significantAdvantages' => [
                $site1 => 0,
                $site2 => 0
            ]
        ];
        
        // Calcola le statistiche
        foreach ($comparison as $category => $metrics) {
            // Conteggio vittorie per categoria (basato su aggregatedScore)
            if (isset($metrics['aggregatedScore'])) {
                $winner = $metrics['aggregatedScore']['winner'];
                $statistics['winsByCategory'][$winner]++;
                
                // Somma dei vantaggi per categoria
                if ($winner !== 'Pareggio') {
                    $statistics['advantagesByCategory'][$winner] += $metrics['aggregatedScore']['advantage'];
                    
                    // Conteggio vantaggi significativi (>20%)
                    if ($metrics['aggregatedScore']['advantage'] > 0.2) {
                        $statistics['significantAdvantages'][$winner]++;
                    }
                }
            }
            
            // Conteggio vittorie per singola metrica
            foreach ($metrics as $metric => $result) {
                if ($metric !== 'aggregatedScore' && isset($result['winner'])) {
                    $statistics['winsByMetric'][$result['winner']]++;
                }
            }
        }
        
        // Aggiungi le statistiche al confronto
        $comparison['statistics'] = $statistics;
    }
    
    /**
     * Calcola i punteggi finali e determina il vincitore
     * 
     * @param array $results Risultati elaborati
     * @return array Risultati finali con punteggi e vincitore
     */
    private function calculateFinalScores($results) {
        // Nomi dei siti
        $site1 = parse_url($this->url1, PHP_URL_HOST);
        $site2 = parse_url($this->url2, PHP_URL_HOST);
        
        // Punteggi per categoria
        $categoryScores1 = [];
        $categoryScores2 = [];
        
        // Calcola i punteggi per ogni categoria
        foreach ($this->weights as $category => $weight) {
            // Se abbiamo punteggi aggregati, usali direttamente
            if (isset($results['site1'][$category]['aggregatedScore'])) {
                $categoryScores1[$category] = $results['site1'][$category]['aggregatedScore'];
                $categoryScores2[$category] = $results['site2'][$category]['aggregatedScore'];
            }
            // Altrimenti, calcoliamo punteggi relativi basati sul confronto
            else if (isset($results['comparison'][$category])) {
                // Inizializza i punteggi a 50 (neutro)
                $categoryScores1[$category] = 50;
                $categoryScores2[$category] = 50;
                
                $metrics = $results['comparison'][$category];
                $metricCount = count($metrics);
                
                if ($metricCount > 0) {
                    $totalAdvantage1 = 0;
                    $totalAdvantage2 = 0;
                    
                    // Somma i vantaggi per ogni metrica
                    foreach ($metrics as $metric => $comparison) {
                        if ($comparison['winner'] === $site1) {
                            $totalAdvantage1 += $comparison['advantage'];
                        } elseif ($comparison['winner'] === $site2) {
                            $totalAdvantage2 += $comparison['advantage'];
                        }
                    }
                    
                    // Calcola il punteggio normalizzato (0-100)
                    $baseScore = 50;
                    $maxAdvantage = $metricCount * 2; // Massimo vantaggio possibile (2 per metrica)
                    
                    $advantageFactor1 = $totalAdvantage1 / $maxAdvantage;
                    $advantageFactor2 = $totalAdvantage2 / $maxAdvantage;
                    
                    // Applica una curva sigmoide per evitare punteggi estremi
                    $categoryScores1[$category] = $baseScore + (50 * $advantageFactor1);
                    $categoryScores2[$category] = $baseScore + (50 * $advantageFactor2);
                }
                
                // Normalizza i punteggi a somma 100
                $totalCategoryScore = $categoryScores1[$category] + $categoryScores2[$category];
                if ($totalCategoryScore > 0) {
                    $categoryScores1[$category] = ($categoryScores1[$category] / $totalCategoryScore) * 100;
                    $categoryScores2[$category] = ($categoryScores2[$category] / $totalCategoryScore) * 100;
                }
            }
            // Fallback per categorie senza confronto
            else {
                $categoryScores1[$category] = 50;
                $categoryScores2[$category] = 50;
            }
            
            // Aggiungi i punteggi di categoria ai risultati
            $results['site1'][$category . 'Score'] = round($categoryScores1[$category], 2);
            $results['site2'][$category . 'Score'] = round($categoryScores2[$category], 2);
        }
        
        // Calcola il punteggio totale ponderato
        $totalScore1 = 0;
        $totalScore2 = 0;
        $appliedWeightSum = 0;
        
        foreach ($this->weights as $category => $weight) {
            // Se la categoria è stata effettivamente analizzata
            if (isset($results['comparison'][$category])) {
                $totalScore1 += $categoryScores1[$category] * $weight;
                $totalScore2 += $categoryScores2[$category] * $weight;
                $appliedWeightSum += $weight;
            }
        }
        
        // Normalizza in base ai pesi effettivamente applicati
        if ($appliedWeightSum > 0) {
            $factor = 1 / $appliedWeightSum;
            $totalScore1 *= $factor;
            $totalScore2 *= $factor;
        }
        
        // Arrotonda i punteggi
        $totalScore1 = round($totalScore1, 2);
        $totalScore2 = round($totalScore2, 2);
        
        // Determina il vincitore
        if ($totalScore1 > $totalScore2) {
            $winner = $site1;
            $advantage = $totalScore1 - $totalScore2;
            $winMargin = ($totalScore1 / (($totalScore1 + $totalScore2) / 2)) - 1;
        } elseif ($totalScore2 > $totalScore1) {
            $winner = $site2;
            $advantage = $totalScore2 - $totalScore1;
            $winMargin = ($totalScore2 / (($totalScore1 + $totalScore2) / 2)) - 1;
        } else {
            $winner = 'Pareggio';
            $advantage = 0;
            $winMargin = 0;
        }
        
        // Determina l'intensità della vittoria
        $victoryLevel = 'pareggio';
        if ($winner !== 'Pareggio') {
            if ($winMargin < 0.05) {
                $victoryLevel = 'minima';
            } else if ($winMargin < 0.15) {
                $victoryLevel = 'leggera';
            } else if ($winMargin < 0.30) {
                $victoryLevel = 'chiara';
            } else if ($winMargin < 0.50) {
                $victoryLevel = 'netta';
            } else {
                $victoryLevel = 'schiacciante';
            }
        }
        
        // Aggiungi i punteggi totali e il vincitore ai risultati
        $results['site1']['totalScore'] = $totalScore1;
        $results['site2']['totalScore'] = $totalScore2;
        $results['winner'] = $winner;
        $results['advantage'] = round($advantage, 2);
        $results['winMargin'] = round($winMargin * 100, 2); // Percentuale di vantaggio
        $results['victoryLevel'] = $victoryLevel;
        
        // Aggiungi analisi punti di forza per ciascun sito
        $results['strengthAnalysis'] = $this->analyzeStrengths($results);
        
        return $results;
    }
    
    /**
     * Analizza i punti di forza relativi di ciascun sito
     * 
     * @param array $results Risultati elaborati
     * @return array Analisi dei punti di forza
     */
    private function analyzeStrengths($results) {
        $site1 = parse_url($this->url1, PHP_URL_HOST);
        $site2 = parse_url($this->url2, PHP_URL_HOST);
        
        $strengths = [
            $site1 => [
                'categories' => [],
                'metrics' => []
            ],
            $site2 => [
                'categories' => [],
                'metrics' => []
            ]
        ];
        
        // Analizza le categorie vinte
        foreach ($this->weights as $category => $weight) {
            if (isset($results['comparison'][$category]['aggregatedScore'])) {
                $comparison = $results['comparison'][$category]['aggregatedScore'];
                
                if ($comparison['winner'] !== 'Pareggio') {
                    // Registra solo se il vantaggio è significativo (>10%)
                    if ($comparison['advantage'] > 0.1) {
                        $strengths[$comparison['winner']]['categories'][] = [
                            'category' => $category,
                            'advantage' => $comparison['advantage'],
                            'score' => $comparison['winner'] === $site1 ? 
                                      $comparison['score1'] : $comparison['score2']
                        ];
                    }
                }
            }
        }
        
        // Analizza le metriche specifiche con il maggior vantaggio
        $significantMetrics = [];
        
        foreach ($results['comparison'] as $category => $metrics) {
            foreach ($metrics as $metric => $comparison) {
                // Salta aggregatedScore e non-numeric
                if ($metric === 'aggregatedScore' || $metric === 'statistics') {
                    continue;
                }
                
                // Considera solo metriche con vantaggio significativo (>30%)
                if (isset($comparison['winner']) && $comparison['winner'] !== 'Pareggio' && $comparison['advantage'] > 0.3) {
                    $significantMetrics[] = [
                        'category' => $category,
                        'metric' => $metric,
                        'winner' => $comparison['winner'],
                        'advantage' => $comparison['advantage'],
                        'higherIsBetter' => $comparison['higherIsBetter'] ?? true
                    ];
                }
            }
        }
        
        // Ordina per vantaggio decrescente
        usort($significantMetrics, function($a, $b) {
            return $b['advantage'] <=> $a['advantage'];
        });
        
        // Prendi le top 5 metriche per ogni sito
        $topMetricsPerSite = [
            $site1 => [],
            $site2 => []
        ];
        
        foreach ($significantMetrics as $metric) {
            $winner = $metric['winner'];
            if (count($topMetricsPerSite[$winner]) < 5) {
                $topMetricsPerSite[$winner][] = $metric;
            }
        }
        
        $strengths[$site1]['metrics'] = $topMetricsPerSite[$site1];
        $strengths[$site2]['metrics'] = $topMetricsPerSite[$site2];
        
        // Ordina le categorie per vantaggio
        usort($strengths[$site1]['categories'], function($a, $b) {
            return $b['advantage'] <=> $a['advantage'];
        });
        
        usort($strengths[$site2]['categories'], function($a, $b) {
            return $b['advantage'] <=> $a['advantage'];
        });
        
        return $strengths;
    }
}