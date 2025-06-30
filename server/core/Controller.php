<?php
/**
 * Controller Interface
 * 
 * Interfaccia che definisce il contratto per tutti i controller specifici.
 * Ogni controller è responsabile della gestione di un tipo specifico di richiesta
 * e dell'implementazione della logica di business corrispondente.
 */

interface Controller {
    /**
     * Gestisce una richiesta HTTP
     * 
     * @param string $method Il metodo HTTP (GET, POST, etc.)
     * @param array $params I parametri della richiesta
     * @return array La risposta da restituire al client
     */
    public function handleRequest($method, $params);
}