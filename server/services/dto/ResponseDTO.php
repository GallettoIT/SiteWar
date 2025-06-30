<?php
/**
 * ResponseDTO.php
 * 
 * DTO base per standardizzare tutte le risposte dell'applicazione.
 * Implementa un'interfaccia comune per garantire coerenza tra backend e frontend.
 */

interface MetricsDTO {
    public function toArray();
    public function isValid();
}

/**
 * BaseMetricsDTO
 * 
 * Classe base astratta per tutti i DTO delle metriche di analisi.
 * Fornisce funzionalità comuni a tutti i tipi di metriche.
 */
abstract class BaseMetricsDTO implements MetricsDTO {
    protected $metrics = [];
    protected $error = null;
    
    /**
     * Verifica se i dati sono validi
     * 
     * @return bool
     */
    public function isValid() {
        return !empty($this->metrics) && $this->error === null;
    }
    
    /**
     * Imposta un messaggio di errore
     * 
     * @param string $message
     * @return $this
     */
    public function setError($message) {
        $this->error = $message;
        return $this;
    }
    
    /**
     * Ottiene il messaggio di errore
     * 
     * @return string|null
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Converte in array
     * 
     * @return array
     */
    public function toArray() {
        return array_merge($this->metrics, ['error' => $this->error]);
    }
    
    /**
     * Ottiene una metrica specifica con fallback
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetric($key, $default = null) {
        return isset($this->metrics[$key]) ? $this->metrics[$key] : $default;
    }
    
    /**
     * Ottiene tutte le metriche
     * 
     * @return array
     */
    public function getMetrics() {
        return $this->metrics;
    }
}

/**
 * MetricsDTOFactory
 * 
 * Factory per la creazione dei DTO appropriati
 */
class MetricsDTOFactory {
    /**
     * Crea un DTO per il tipo di metriche richiesto
     * 
     * @param string $type Tipo di metriche (performance, seo, security, technical)
     * @param array $data Dati grezzi 
     * @return MetricsDTO
     */
    public static function create($type, $data = []) {
        switch (strtolower($type)) {
            case 'performance':
                return new PerformanceMetricsDTO($data);
            case 'seo':
                return new SEOMetricsDTO($data);
            case 'security':
                return new SecurityMetricsDTO($data);
            case 'technical':
                return new TechnicalMetricsDTO($data);
            default:
                throw new InvalidArgumentException("Tipo di metriche non supportato: {$type}");
        }
    }
}

/**
 * AnalysisResultDTO
 * 
 * DTO per il risultato completo dell'analisi di un sito
 */
class AnalysisResultDTO {
    /**
     * @var string
     */
    private $url;
    
    /**
     * @var array
     */
    private $metrics = [];
    
    /**
     * @var array
     */
    private $categories = [];
    
    /**
     * @var float
     */
    private $totalScore = 0;
    
    /**
     * Costruttore
     * 
     * @param string $url
     * @param array $data
     */
    public function __construct($url, $data = []) {
        $this->url = $url;
        
        if (!empty($data)) {
            $this->populate($data);
        }
    }
    
    /**
     * Popola il DTO dai dati grezzi
     * 
     * @param array $data
     */
    public function populate($data) {
        // Estrai ed organizza le metriche per categoria
        $categoryTypes = ['performance', 'seo', 'security', 'technical'];
        
        foreach ($categoryTypes as $type) {
            // Cerca i dati delle metriche in posizioni diverse
            $metricsData = null;
            
            // 1. Cerca prima nella struttura metrics standard
            if (isset($data['metrics']) && isset($data['metrics'][$type])) {
                $metricsData = $data['metrics'][$type];
            } 
            // 2. Cerca nei dati di categoria diretti
            else if (isset($data[$type])) {
                $metricsData = $data[$type];
            }
            
            // Crea il DTO per questa categoria
            if ($metricsData !== null) {
                $this->metrics[$type] = MetricsDTOFactory::create($type, $metricsData);
            } else {
                // Crea un DTO vuoto se non ci sono dati
                $this->metrics[$type] = MetricsDTOFactory::create($type);
            }
            
            // Estrai il punteggio per la categoria
            $this->extractCategoryScore($type, $data);
        }
        
        // Estrai punteggio totale
        if (isset($data['totalScore'])) {
            $this->totalScore = $data['totalScore'];
        } else if (isset($data['score'])) {
            $this->totalScore = $data['score'];
        }
    }
    
    /**
     * Estrae il punteggio di una categoria dai dati grezzi
     * 
     * @param string $category
     * @param array $data
     */
    private function extractCategoryScore($category, $data) {
        // Cerca il punteggio in diverse posizioni possibili
        
        // 1. Nuova struttura standard
        if (isset($data['categories']) && isset($data['categories'][$category])) {
            $this->categories[$category] = $data['categories'][$category];
        } 
        // 2. Struttura vecchio stile categoryScore
        else if (isset($data[$category . 'Score'])) {
            $this->categories[$category] = $data[$category . 'Score'];
        }
        // 3. Dalla struttura di categoria diretta
        else if (isset($data[$category]) && isset($data[$category]['aggregatedScore'])) {
            $this->categories[$category] = $data[$category]['aggregatedScore'];
        }
        // 4. Dal totalScore nel DTO delle metriche
        else if (isset($this->metrics[$category])) {
            $score = $this->metrics[$category]->getTotalScore();
            if ($score > 0) {
                $this->categories[$category] = $score;
            } else {
                $this->categories[$category] = 50; // valore neutro
            }
        }
        // 5. Fallback
        else {
            $this->categories[$category] = 50; // valore neutro
        }
    }
    
    /**
     * Converte il DTO in array
     * 
     * @return array
     */
    public function toArray() {
        $result = [
            'url' => $this->url,
            'metrics' => [],
            'categories' => $this->categories,
            'totalScore' => $this->totalScore
        ];
        
        // Aggiungi le metriche di ogni categoria
        foreach ($this->metrics as $category => $dto) {
            $result['metrics'][$category] = $dto->getMetrics();
        }
        
        // Mantieni compatibilità con formato vecchio stile
        foreach ($this->categories as $category => $score) {
            $result[$category . 'Score'] = $score;
        }
        
        // Mantieni il totalScore anche come score per compatibilità
        $result['score'] = $this->totalScore;
        
        return $result;
    }
    
    /**
     * Ottiene il DTO delle metriche per una categoria
     * 
     * @param string $category
     * @return MetricsDTO|null
     */
    public function getMetrics($category) {
        return $this->metrics[$category] ?? null;
    }
    
    /**
     * Ottiene il punteggio di una categoria
     * 
     * @param string $category
     * @return float|null
     */
    public function getCategoryScore($category) {
        return $this->categories[$category] ?? null;
    }
    
    /**
     * Ottiene l'URL
     * 
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }
    
    /**
     * Ottiene il punteggio totale
     * 
     * @return float
     */
    public function getTotalScore() {
        return $this->totalScore;
    }
}