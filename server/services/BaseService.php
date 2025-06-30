<?php
/**
 * Base Service Class
 * 
 * Classe base astratta per tutti i servizi specifici.
 * Definisce l'interfaccia comune e implementa funzionalità condivise.
 */

abstract class BaseService {
    /**
     * @var array Configurazione del servizio
     */
    protected $config;
    
    /**
     * @var mixed Risultato dell'esecuzione del servizio
     */
    protected $result;
    
    /**
     * @var string Messaggio di errore, se presente
     */
    protected $errorMessage;
    
    /**
     * Costruttore
     * 
     * @param array $config Configurazione del servizio
     */
    public function __construct($config = []) {
        $this->config = $config;
    }
    
    /**
     * Esegue il servizio
     * 
     * @return mixed Il risultato dell'esecuzione del servizio
     */
    abstract public function execute();
    
    /**
     * Ottiene il risultato dell'esecuzione del servizio
     * 
     * @return mixed Il risultato dell'esecuzione del servizio
     */
    public function getResult() {
        return $this->result;
    }
    
    /**
     * Verifica se si è verificato un errore durante l'esecuzione del servizio
     * 
     * @return bool True se si è verificato un errore
     */
    public function hasError() {
        return !empty($this->errorMessage);
    }
    
    /**
     * Ottiene il messaggio di errore, se presente
     * 
     * @return string|null Il messaggio di errore o null se non presente
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    /**
     * Imposta il messaggio di errore
     * 
     * @param string $message Il messaggio di errore
     */
    protected function setError($message) {
        $this->errorMessage = $message;
    }
    
    /**
     * Implementa una strategia di fallback in caso di errore
     * 
     * @return mixed Il risultato della strategia di fallback
     */
    abstract protected function implementFallback();
}