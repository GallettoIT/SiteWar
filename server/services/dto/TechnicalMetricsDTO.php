<?php
/**
 * TechnicalMetricsDTO
 * 
 * DTO specializzato per le metriche tecniche.
 * Standardizza e normalizza i dati tecnici da varie fonti.
 */

require_once __DIR__ . '/ResponseDTO.php';

class TechnicalMetricsDTO extends BaseMetricsDTO {
    /**
     * Costruttore
     * 
     * @param array $data Dati grezzi delle metriche
     */
    public function __construct($data = []) {
        // Definisce le metriche obbligatorie con valori predefiniti
        $this->metrics = [
            'html_validation' => null,
            'css_validation' => null,
            'technologies' => null,
            'responsive' => null,
            'dom_depth' => null,
            'dom_size' => null,
            'js_errors' => null,
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
        // Mappatura standard
        foreach ($this->metrics as $key => $defaultValue) {
            if (isset($data[$key])) {
                $this->metrics[$key] = $data[$key];
            }
        }
        
        // Gestione compatibilità per i vecchi nomi di campi
        $alternatives = [
            'html_validation' => ['htmlValidation', 'validHtml', 'html_valid'],
            'css_validation' => ['cssValidation', 'validCss', 'css_valid'],
            'technologies' => ['techs', 'tech_stack', 'tech', 'stack'],
            'responsive' => ['isResponsive', 'mobile_friendly', 'mobileFriendly'],
            'dom_depth' => ['domDepth', 'depth', 'tree_depth'],
            'dom_size' => ['domSize', 'elementCount', 'dom_elements'],
            'js_errors' => ['jsErrors', 'errors', 'console_errors']
        ];
        
        foreach ($alternatives as $standardKey => $altKeys) {
            if ($this->metrics[$standardKey] !== null) continue;
            
            foreach ($altKeys as $altKey) {
                if (isset($data[$altKey])) {
                    $this->metrics[$standardKey] = $data[$altKey];
                    break;
                }
            }
        }
        
        // Estrai dati da 'dom' se presente
        if (isset($data['dom']) && is_array($data['dom'])) {
            $dom = $data['dom'];
            
            if (isset($dom['depth']) && $this->metrics['dom_depth'] === null) {
                $this->metrics['dom_depth'] = $dom['depth'];
            }
            
            if (isset($dom['elementCount']) && $this->metrics['dom_size'] === null) {
                $this->metrics['dom_size'] = $dom['elementCount'];
            }
        }
        
        // Estrai dati da 'validation' se presente
        if (isset($data['validation']) && is_array($data['validation'])) {
            $validation = $data['validation'];
            
            if (isset($validation['html']) && $this->metrics['html_validation'] === null) {
                if (is_array($validation['html']) && isset($validation['html']['score'])) {
                    $this->metrics['html_validation'] = $validation['html']['score'];
                } else {
                    $this->metrics['html_validation'] = $validation['html'];
                }
            }
            
            if (isset($validation['css']) && $this->metrics['css_validation'] === null) {
                if (is_array($validation['css']) && isset($validation['css']['score'])) {
                    $this->metrics['css_validation'] = $validation['css']['score'];
                } else {
                    $this->metrics['css_validation'] = $validation['css'];
                }
            }
        }
        
        // Estrai dati da 'technology' se presente
        if (isset($data['technology']) && is_array($data['technology']) && $this->metrics['technologies'] === null) {
            if (isset($data['technology']['list'])) {
                $this->metrics['technologies'] = $data['technology']['list'];
            } elseif (isset($data['technology']['detected'])) {
                $this->metrics['technologies'] = $data['technology']['detected'];
            }
        }
        
        // Cerca con prefissi
        $prefixedSearch = [
            'technical_', 
            'tech_', 
            'technology_', 
            'dom_'
        ];
        
        foreach ($prefixedSearch as $prefix) {
            $this->searchPrefixedData($data, $prefix);
        }
        
        // Imposta punteggio totale
        if (isset($data['totalScore'])) {
            $this->metrics['total_score'] = $data['totalScore'];
        } else if (isset($data['score'])) {
            $this->metrics['total_score'] = $data['score'];
        } else if (isset($data['technicalScore'])) {
            $this->metrics['total_score'] = $data['technicalScore'];
        }
        
        // Normalizza i valori
        $this->normalizeValues();
    }
    
    /**
     * Cerca dati con prefisso specifico
     * 
     * @param array $data
     * @param string $prefix
     */
    private function searchPrefixedData($data, $prefix) {
        foreach ($this->metrics as $key => $value) {
            // Salta se abbiamo già un valore
            if ($value !== null && $key !== 'total_score') continue;
            
            $prefixedKey = $prefix . $key;
            if (isset($data[$prefixedKey])) {
                $this->metrics[$key] = $data[$prefixedKey];
            }
        }
    }
    
    /**
     * Normalizza i valori per garantire consistenza
     */
    private function normalizeValues() {
        // Converti boolean in numeri
        if (is_bool($this->metrics['responsive'])) {
            $this->metrics['responsive'] = $this->metrics['responsive'] ? 100 : 0;
        }
        
        // Normalizza DOM depth (minore è meglio)
        if (is_numeric($this->metrics['dom_depth']) && $this->metrics['dom_depth'] > 0) {
            // Idealmente la profondità non dovrebbe superare 10-12 livelli
            // Converti in punteggio: meno di 10 = ottimo (90-100), oltre 20 = pessimo (<50)
            $depth = intval($this->metrics['dom_depth']);
            $this->metrics['dom_depth'] = max(0, 100 - ($depth * 5));
        }
        
        // Normalizza dimensione DOM (minore è meglio)
        if (is_numeric($this->metrics['dom_size']) && $this->metrics['dom_size'] > 0) {
            // Converti in punteggio: meno di 500 elementi = ottimo (90-100), oltre 2000 = pessimo (<50)
            $size = intval($this->metrics['dom_size']);
            if ($size < 500) {
                $this->metrics['dom_size'] = 90 + min(10, (500 - $size) / 50);
            } else if ($size < 2000) {
                $this->metrics['dom_size'] = 50 + ((2000 - $size) / 1500) * 40;
            } else {
                $this->metrics['dom_size'] = max(0, 50 - (($size - 2000) / 1000) * 25);
            }
        }
        
        // Normalizza errori JS (0 = meglio)
        if (is_numeric($this->metrics['js_errors']) && $this->metrics['js_errors'] >= 0) {
            $errors = intval($this->metrics['js_errors']);
            $this->metrics['js_errors'] = max(0, 100 - ($errors * 20)); // ogni errore -20 punti
        }
        
        // Normalizza le tecnologie
        if (is_array($this->metrics['technologies'])) {
            $this->metrics['technologies'] = implode(', ', $this->metrics['technologies']);
        }
    }
    
    // Getters specifici
    public function getHtmlValidation() { return $this->getMetric('html_validation'); }
    public function getCssValidation() { return $this->getMetric('css_validation'); }
    public function getTechnologies() { return $this->getMetric('technologies'); }
    public function getResponsive() { return $this->getMetric('responsive'); }
    public function getDomDepth() { return $this->getMetric('dom_depth'); }
    public function getDomSize() { return $this->getMetric('dom_size'); }
    public function getJsErrors() { return $this->getMetric('js_errors'); }
    public function getTotalScore() { return $this->getMetric('total_score'); }
}