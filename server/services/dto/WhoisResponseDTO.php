<?php
/**
 * WhoisResponseDTO
 * 
 * DTO (Data Transfer Object) specializzato per le risposte dell'API WHOIS.
 * Gestisce diversi formati di risposta, mappandoli in una struttura standard.
 * 
 * Pattern implementati:
 * - DTO (Data Transfer Object)
 * - Adapter
 */

class WhoisResponseDTO {
    /**
     * @var string Data di creazione del dominio
     */
    private $creationDate;
    
    /**
     * @var string Data di scadenza del dominio
     */
    private $expirationDate;
    
    /**
     * @var string Nome del registrar
     */
    private $registrar;
    
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
     * @param array $response Risposta originale dell'API WHOIS
     * @param string $error Eventuale messaggio di errore
     */
    public function __construct($response = null, $error = null) {
        $this->creationDate = null;
        $this->expirationDate = null;
        $this->registrar = null;
        $this->error = $error;
        $this->rawResponse = $response;
        
        if ($response !== null && is_array($response)) {
            $this->parseResponse($response);
        }
    }
    
    /**
     * Analizza la risposta API e mappa i dati
     * 
     * @param array $response Risposta API WHOIS
     */
    private function parseResponse($response) {
        // Log della struttura per debug
        error_log("[WHOIS DTO] Analisi struttura risposta: " . json_encode(array_keys($response)));
        
        // Tipo 1: Struttura standard WhoisRecord
        if (isset($response['WhoisRecord'])) {
            $whois = $response['WhoisRecord'];
            
            // Log della struttura interna
            error_log("[WHOIS DTO] Struttura 'WhoisRecord': " . json_encode(array_keys($whois)));
            
            // Prova a ottenere i dati dal WhoisRecord principale
            $this->creationDate = $whois['createdDate'] ?? null;
            $this->expirationDate = $whois['expiresDate'] ?? null;
            $this->registrar = $whois['registrarName'] ?? null;
            
            // Se non è presente registryData, termina
            if (!isset($whois['registryData'])) {
                return;
            }
            
            // Se mancano dati, prova a ottenerli da registryData
            $registry = $whois['registryData'];
            
            // Log della sottostruttura
            error_log("[WHOIS DTO] Struttura 'registryData': " . json_encode(array_keys($registry)));
            
            // Usa i dati registryData solo se i valori principali sono null
            if ($this->creationDate === null) {
                $this->creationDate = $registry['createdDate'] ?? null;
            }
            
            if ($this->expirationDate === null) {
                $this->expirationDate = $registry['expiresDate'] ?? null;
            }
            
            if ($this->registrar === null) {
                $this->registrar = $registry['registrarName'] ?? null;
            }
            
            return;
        }
        
        // Tipo 2: Struttura diretta senza WhoisRecord
        $directFields = [
            'creationDate' => ['created', 'creation_date', 'createdDate', 'created_date', 'registered', 'registration_date'],
            'expirationDate' => ['expires', 'expiration_date', 'expiresDate', 'expiry_date', 'expiration'],
            'registrar' => ['registrar', 'registrarName', 'registrar_name']
        ];
        
        foreach ($directFields as $property => $possibleKeys) {
            foreach ($possibleKeys as $key) {
                if (isset($response[$key])) {
                    $this->$property = $response[$key];
                    error_log("[WHOIS DTO] Trovato {$property} in campo diretto: {$key}");
                    break;
                }
            }
        }
        
        // Se abbiamo trovato almeno un campo, termina
        if ($this->creationDate !== null || $this->expirationDate !== null || $this->registrar !== null) {
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
                // Cerca data di creazione
                if ((stripos($key, 'creat') !== false || 
                     stripos($key, 'registered') !== false) && 
                    $this->looksLikeDate($value)) {
                    $this->creationDate = $value;
                    error_log("[WHOIS DTO] Trovata data di creazione in: $path");
                }
                
                // Cerca data di scadenza
                if ((stripos($key, 'expir') !== false || 
                     stripos($key, 'expiry') !== false) && 
                    $this->looksLikeDate($value)) {
                    $this->expirationDate = $value;
                    error_log("[WHOIS DTO] Trovata data di scadenza in: $path");
                }
                
                // Cerca registrar
                if (stripos($key, 'registrar') !== false && is_string($value) && !empty($value)) {
                    $this->registrar = $value;
                    error_log("[WHOIS DTO] Trovato registrar in: $path");
                }
            }
        }
    }
    
    /**
     * Verifica se un valore sembra una data
     * 
     * @param mixed $value Valore da verificare
     * @return bool True se sembra una data
     */
    private function looksLikeDate($value) {
        if (!is_string($value)) {
            return false;
        }
        
        // Cerca pattern comuni nelle date
        return (
            preg_match('/\d{4}-\d{2}-\d{2}/', $value) || // YYYY-MM-DD
            preg_match('/\d{2}\/\d{2}\/\d{4}/', $value) || // DD/MM/YYYY o MM/DD/YYYY
            preg_match('/\d{2}\.\d{2}\.\d{4}/', $value) || // DD.MM.YYYY
            strtotime($value) !== false // Tenta di parsare come data
        );
    }
    
    /**
     * Converte il DTO in un array associativo
     * 
     * @return array Rappresentazione array del DTO
     */
    public function toArray() {
        return [
            'creation_date' => $this->creationDate,
            'expiration_date' => $this->expirationDate,
            'registrar' => $this->registrar,
            'error' => $this->error
        ];
    }
    
    /**
     * Verifica se i dati sono validi
     * 
     * @return bool True se i dati sono validi
     */
    public function isValid() {
        return $this->creationDate !== null || $this->expirationDate !== null || $this->registrar !== null;
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
    public function getCreationDate() { return $this->creationDate; }
    public function getExpirationDate() { return $this->expirationDate; }
    public function getRegistrar() { return $this->registrar; }
    public function getError() { return $this->error; }
}