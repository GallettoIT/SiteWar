<?php
/**
 * PerformanceMetricsDTO
 * 
 * DTO specializzato per le metriche di performance.
 * Standardizza e normalizza i dati di performance da varie fonti.
 */

require_once __DIR__ . '/ResponseDTO.php';

class PerformanceMetricsDTO extends BaseMetricsDTO {
    /**
     * Costruttore
     * 
     * @param array $data Dati grezzi delle metriche
     */
    public function __construct($data = []) {
        // Definisce le metriche obbligatorie con valori predefiniti
        $this->metrics = [
            'first_contentful_paint' => null,
            'largest_contentful_paint' => null,
            'time_to_interactive' => null,
            'cumulative_layout_shift' => null,
            'page_size' => null,
            'total_score' => 0
        ];
        
        // Mappa i dati ricevuti
        if (!empty($data)) {
            $this->mapFromRawData($data);
        }
    }
    
    /**
     * Mappa i dati grezzi nelle metriche standardizzate
     * 
     * @param array $data
     * @return void
     */
    private function mapFromRawData($data) {
        // Mappatura diretta se i nomi corrispondono
        foreach ($this->metrics as $key => $defaultValue) {
            if (isset($data[$key])) {
                $this->metrics[$key] = $data[$key];
            }
        }
        
        // Gestione nomi alternativi per compatibilità
        $alternatives = [
            'first_contentful_paint' => ['firstPaint', 'fcp', 'first_paint'],
            'largest_contentful_paint' => ['largestPaint', 'lcp'],
            'time_to_interactive' => ['interactive', 'tti'],
            'cumulative_layout_shift' => ['layoutShift', 'cls'],
            'page_size' => ['size', 'totalBytes']
        ];
        
        foreach ($alternatives as $standardKey => $altKeys) {
            // Se la metrica standard è già definita, salta
            if ($this->metrics[$standardKey] !== null) continue;
            
            // Cerca alternative
            foreach ($altKeys as $altKey) {
                if (isset($data[$altKey])) {
                    $this->metrics[$standardKey] = $data[$altKey];
                    break;
                }
            }
        }
        
        // Cerca in strutture annidiate con prefissi
        $prefixedSearch = [
            'performance_',
            'pagespeed_',
            'lighthouse_'
        ];
        
        foreach ($prefixedSearch as $prefix) {
            if ($this->searchPrefixedData($data, $prefix)) {
                break; // Usciamo se abbiamo trovato corrispondenze
            }
        }
        
        // Imposta il punteggio totale
        if (isset($data['totalScore'])) {
            $this->metrics['total_score'] = $data['totalScore'];
        } else if (isset($data['score'])) {
            $this->metrics['total_score'] = $data['score'];
        } else if (isset($data['performanceScore'])) {
            $this->metrics['total_score'] = $data['performanceScore'];
        }
    }
    
    /**
     * Cerca dati con un prefisso specifico
     *
     * @param array $data
     * @param string $prefix
     * @return bool True se sono state trovate corrispondenze
     */
    private function searchPrefixedData($data, $prefix) {
        $found = false;
        foreach ($this->metrics as $key => $value) {
            // Salta se abbiamo già un valore
            if ($value !== null && $key !== 'total_score') continue;
            
            $prefixedKey = $prefix . $key;
            if (isset($data[$prefixedKey])) {
                $this->metrics[$key] = $data[$prefixedKey];
                $found = true;
            }
        }
        
        return $found;
    }
    
    // Getters specifici
    public function getFirstContentfulPaint() { return $this->getMetric('first_contentful_paint'); }
    public function getLargestContentfulPaint() { return $this->getMetric('largest_contentful_paint'); }
    public function getTimeToInteractive() { return $this->getMetric('time_to_interactive'); }
    public function getCumulativeLayoutShift() { return $this->getMetric('cumulative_layout_shift'); }
    public function getPageSize() { return $this->getMetric('page_size'); }
    public function getTotalScore() { return $this->getMetric('total_score'); }
}