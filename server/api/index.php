<?php
/**
 * Site War API Entry Point
 * 
 * Questo file serve come punto di ingresso per tutte le richieste API
 * e si occupa di instradare le richieste al controller appropriato.
 * 
 * Implementa:
 * - Instradamento richieste
 * - Gestione errori
 * - Headers risposta
 */

// Percorso base dell'applicazione
define('BASE_PATH', dirname(dirname(__DIR__)));

// Flag per modalità debug
define('DEBUG_MODE', true);

// Cattura l'output per evitare che gli errori interferiscano con il JSON
ob_start();

// Abilita la visualizzazione degli errori in fase di sviluppo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Caricamento file di utilità e core
require_once BASE_PATH . '/server/core/APIController.php';

// Imposta error_log per maggiore visibilità
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/error.log');

// Abilitazione CORS per le richieste da client
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Gestione richieste OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inizializzazione e avvio controller API
try {
    $apiController = new APIController();
    $apiController->processRequest();
} catch (Exception $e) {
    // Log dell'errore
    error_log('API Error: ' . $e->getMessage());
    
    // Pulisci il buffer per eliminare eventuali errori HTML
    ob_clean();
    
    // Risposta di errore generica
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Si è verificato un errore interno. Riprova più tardi.',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}

// Rilascia l'output bufferizzato e termina
ob_end_flush();