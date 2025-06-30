<?php
/**
 * SecurityMetricsDTO
 * 
 * DTO specializzato per le metriche di sicurezza.
 * Standardizza e normalizza i dati di sicurezza da varie fonti.
 */

require_once __DIR__ . '/ResponseDTO.php';

class SecurityMetricsDTO extends BaseMetricsDTO {
    /**
     * Costruttore
     * 
     * @param array $data Dati grezzi delle metriche
     */
    public function __construct($data = []) {
        // Definisce le metriche obbligatorie con valori predefiniti
        $this->metrics = [
            'ssl_grade' => null,
            'headers_score' => null,
            'vulnerabilities' => null,
            'hsts' => null,
            'xss_protection' => null,
            'content_security_policy' => null,
            'https_redirect' => null,
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
            'ssl_grade' => ['ssl', 'sslGrade', 'tls_grade', 'tlsGrade'],
            'headers_score' => ['headers', 'securityHeaders', 'headerScore'],
            'vulnerabilities' => ['vulns', 'vulnerabilityCount', 'issues'],
            'hsts' => ['strictTransportSecurity', 'HSTS'],
            'xss_protection' => ['xssProtection', 'XSS'],
            'content_security_policy' => ['csp', 'CSP'],
            'https_redirect' => ['httpsRedirect', 'forceHttps', 'httpsForce']
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
        
        // Estrai dati da securityHeaders se presente
        if (isset($data['securityHeaders']) && is_array($data['securityHeaders'])) {
            $headers = $data['securityHeaders'];
            
            // Cerca specifici header di sicurezza
            if (isset($headers['hsts']) && $this->metrics['hsts'] === null) {
                $this->metrics['hsts'] = $headers['hsts'];
            }
            
            if (isset($headers['xss']) && $this->metrics['xss_protection'] === null) {
                $this->metrics['xss_protection'] = $headers['xss'];
            }
            
            if (isset($headers['csp']) && $this->metrics['content_security_policy'] === null) {
                $this->metrics['content_security_policy'] = $headers['csp'];
            }
            
            // Se c'è un punteggio complessivo per gli header
            if (isset($headers['score']) && $this->metrics['headers_score'] === null) {
                $this->metrics['headers_score'] = $headers['score'];
            }
        }
        
        // Estrai dati SSL se presente
        if (isset($data['ssl']) && is_array($data['ssl'])) {
            $ssl = $data['ssl'];
            
            if (isset($ssl['grade']) && $this->metrics['ssl_grade'] === null) {
                $this->metrics['ssl_grade'] = $ssl['grade'];
            }
        }
        
        // Estrai vulnerabilità se presenti
        if (isset($data['vulnerabilities'])) {
            if (is_array($data['vulnerabilities'])) {
                if (isset($data['vulnerabilities']['count'])) {
                    $this->metrics['vulnerabilities'] = $data['vulnerabilities']['count'];
                } else {
                    $this->metrics['vulnerabilities'] = count($data['vulnerabilities']);
                }
            } else if (is_numeric($data['vulnerabilities'])) {
                $this->metrics['vulnerabilities'] = $data['vulnerabilities'];
            }
        }
        
        // Cerca in API esterne
        if (isset($data['external']) && is_array($data['external'])) {
            $external = $data['external'];
            
            // Dati SecurityHeaders.com
            if (isset($external['grade']) && $this->metrics['headers_score'] === null) {
                $this->metrics['headers_score'] = $this->convertGradeToScore($external['grade']);
            }
            
            // Dati SSL Labs
            if (isset($external['ssl_grade']) && $this->metrics['ssl_grade'] === null) {
                $this->metrics['ssl_grade'] = $external['ssl_grade'];
            }
        }
        
        // Cerca con prefissi
        $prefixedSearch = ['security_', 'sec_', 'ssl_'];
        foreach ($prefixedSearch as $prefix) {
            $this->searchPrefixedData($data, $prefix);
        }
        
        // Imposta punteggio totale
        if (isset($data['totalScore'])) {
            $this->metrics['total_score'] = $data['totalScore'];
        } else if (isset($data['score'])) {
            $this->metrics['total_score'] = $data['score'];
        } else if (isset($data['securityScore'])) {
            $this->metrics['total_score'] = $data['securityScore'];
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
     * Converte un grade letterale (A, B, C...) in punteggio numerico
     * 
     * @param string $grade
     * @return int
     */
    private function convertGradeToScore($grade) {
        $gradeMap = [
            'A+' => 100,
            'A' => 95,
            'A-' => 90,
            'B+' => 85,
            'B' => 80,
            'B-' => 75,
            'C+' => 70,
            'C' => 65,
            'C-' => 60,
            'D+' => 55,
            'D' => 50,
            'D-' => 45,
            'E+' => 40,
            'E' => 35,
            'E-' => 30,
            'F+' => 25,
            'F' => 20,
            'F-' => 15,
            'T' => 0   // T = Trusted (SSL non valido ma affidabile)
        ];
        
        return $gradeMap[$grade] ?? 50; // 50 come valore neutro default
    }
    
    /**
     * Normalizza i valori per garantire consistenza
     */
    private function normalizeValues() {
        // Converte grade letterale in punteggio numerico
        if (is_string($this->metrics['ssl_grade']) && !is_numeric($this->metrics['ssl_grade'])) {
            $this->metrics['ssl_grade'] = $this->convertGradeToScore($this->metrics['ssl_grade']);
        }
        
        // Converte boolean in numeri
        $booleanFields = ['hsts', 'xss_protection', 'content_security_policy', 'https_redirect'];
        foreach ($booleanFields as $field) {
            if (is_bool($this->metrics[$field])) {
                $this->metrics[$field] = $this->metrics[$field] ? 100 : 0;
            }
        }
        
        // Normalizza vulnerabilities (0 = meglio, più vulnerabilità = peggio)
        if (is_numeric($this->metrics['vulnerabilities']) && $this->metrics['vulnerabilities'] > 0) {
            // Converte in un punteggio inverso (0 vulnerabilità = 100 punti, 10+ = 0 punti)
            $vulnCount = intval($this->metrics['vulnerabilities']);
            $vulnScore = max(0, 100 - ($vulnCount * 10));
            $this->metrics['vulnerabilities'] = $vulnScore;
        }
    }
    
    // Getters specifici
    public function getSslGrade() { return $this->getMetric('ssl_grade'); }
    public function getHeadersScore() { return $this->getMetric('headers_score'); }
    public function getVulnerabilities() { return $this->getMetric('vulnerabilities'); }
    public function getHsts() { return $this->getMetric('hsts'); }
    public function getXssProtection() { return $this->getMetric('xss_protection'); }
    public function getContentSecurityPolicy() { return $this->getMetric('content_security_policy'); }
    public function getHttpsRedirect() { return $this->getMetric('https_redirect'); }
    public function getTotalScore() { return $this->getMetric('total_score'); }
}