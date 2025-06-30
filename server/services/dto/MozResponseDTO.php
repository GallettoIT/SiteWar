<?php
/**
 * MozResponseDTO
 * 
 * DTO (Data Transfer Object) specializzato per le risposte dell'API Moz.
 * Gestisce diversi formati di risposta, mappandoli in una struttura standard.
 * 
 * Pattern implementati:
 * - DTO (Data Transfer Object)
 * - Adapter
 */

class MozResponseDTO {
    /**
     * @var float Punteggio di autorità del dominio (da 0 a 100)
     */
    private $domainAuthority;
    
    /**
     * @var float Punteggio di autorità della pagina (da 0 a 100)
     */
    private $pageAuthority;
    
    /**
     * @var int Numero di link esterni al dominio
     */
    private $backlinks;
    
    /**
     * @var string Messaggio di errore se presente
     */
    private $error;
    
    /**
     * @var mixed Risposta API originale per accesso avanzato
     */
    private $rawResponse;
    
    /**
     * Costruttore
     * 
     * @param array $response Risposta originale dell'API Moz
     * @param string $error Eventuale messaggio di errore
     */
    public function __construct($response = null, $error = null) {
        $this->domainAuthority = null;
        $this->pageAuthority = null;
        $this->backlinks = 0;
        $this->error = $error;
        $this->rawResponse = $response;
        
        if ($response !== null && is_array($response)) {
            $this->parseResponse($response);
        }
    }
    
    /**
     * Analizza la risposta API e mappa i dati
     * 
     * @param array $response Risposta API Moz
     */
    private function parseResponse($response) {
        // Log della struttura per debug
        error_log("[MOZ DTO] Analisi struttura risposta: " . json_encode(array_keys($response)));
        
        // Tipo 1: Struttura con 'results' (Moz API v2)
        if (isset($response['results']) && is_array($response['results']) && !empty($response['results'])) {
            $data = $response['results'][0] ?? null;
            
            if ($data) {
                // Log della struttura interna
                error_log("[MOZ DTO] Struttura 'results[0]': " . json_encode(array_keys($data)));
                
                $this->domainAuthority = $data['domain_authority'] ?? null;
                $this->pageAuthority = $data['page_authority'] ?? null;
                $this->backlinks = $data['external_links'] ?? $data['linking_domains'] ?? 0;
                return;
            }
        }
        
        // Tipo 2: Struttura diretta con 'domain_authority' o 'page_authority'
        if (isset($response['domain_authority']) || isset($response['page_authority'])) {
            $this->domainAuthority = $response['domain_authority'] ?? null;
            $this->pageAuthority = $response['page_authority'] ?? null;
            $this->backlinks = $response['external_links'] ?? $response['linking_domains'] ?? 0;
            return;
        }
        
        // Tipo 3: Struttura sconosciuta, ricerca ricorsiva dei campi chiave
        $this->findValuesRecursively($response);
    }
    
    /**
     * Cerca ricorsivamente i valori chiave nella risposta
     * 
     * @param array $data Array da cercare
     * @param string $prefix Prefisso del percorso corrente (per debug)
     */
    private function findValuesRecursively($data, $prefix = '') {
        if (!is_array($data)) {
            return;
        }
        
        foreach ($data as $key => $value) {
            $path = $prefix ? "$prefix.$key" : $key;
            
            if (is_array($value)) {
                $this->findValuesRecursively($value, $path);
            } else {
                // Cerca attributi di autorità del dominio
                if (stripos($key, 'domain_authority') !== false || 
                    stripos($key, 'domain_rank') !== false ||
                    stripos($key, 'domain_score') !== false) {
                    $this->domainAuthority = $value;
                    error_log("[MOZ DTO] Trovato domain authority in: $path");
                }
                
                // Cerca attributi di autorità della pagina
                if (stripos($key, 'page_authority') !== false || 
                    stripos($key, 'page_rank') !== false ||
                    stripos($key, 'url_score') !== false) {
                    $this->pageAuthority = $value;
                    error_log("[MOZ DTO] Trovato page authority in: $path");
                }
                
                // Cerca attributi di backlinks
                if (stripos($key, 'backlinks') !== false || 
                    stripos($key, 'external_links') !== false ||
                    stripos($key, 'links') !== false ||
                    stripos($key, 'linking_domains') !== false) {
                    $this->backlinks = $value;
                    error_log("[MOZ DTO] Trovato backlinks in: $path");
                }
            }
        }
    }
    
    /**
     * Converte il DTO in un array associativo
     * 
     * @return array Rappresentazione array del DTO
     */
    public function toArray() {
        return [
            'domain_authority' => $this->domainAuthority,
            'page_authority' => $this->pageAuthority,
            'backlinks' => $this->backlinks,
            'error' => $this->error
        ];
    }
    
    /**
     * Verifica se i dati sono validi
     * 
     * @return bool True se i dati sono validi
     */
    public function isValid() {
        return $this->domainAuthority !== null || $this->pageAuthority !== null || $this->backlinks > 0;
    }
    
    /**
     * Imposta il messaggio di errore
     * 
     * @param string $error Messaggio di errore
     * @return $this
     */
    public function setError($error) {
        $this->error = $error;
        return $this;
    }
    
    /**
     * Ottiene la risposta API originale
     * 
     * @return mixed Risposta API originale
     */
    public function getRawResponse() {
        return $this->rawResponse;
    }
    
    // Getters
    public function getDomainAuthority() { return $this->domainAuthority; }
    public function getPageAuthority() { return $this->pageAuthority; }
    public function getBacklinks() { return $this->backlinks; }
    public function getError() { return $this->error; }
}