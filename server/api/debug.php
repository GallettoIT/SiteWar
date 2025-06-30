<?php
/**
 * Debug script per testare le API
 */

// Percorso base dell'applicazione
define('BASE_PATH', dirname(dirname(__DIR__)));

// Abilita modalità debug
define('DEBUG_MODE', true);

// Cattura buffer
ob_start();

// Imposta header JSON
header('Content-Type: application/json');

// Carica il controller ValidateController
require_once __DIR__ . '/controllers/ValidateController.php';

// Testa la validazione degli URL
function testValidation() {
    $controller = new ValidateController();
    
    $params = [
        'site1' => 'https://www.unipr.it/',
        'site2' => 'https://www.univr.it/it/'
    ];
    
    try {
        $result = $controller->handleRequest('POST', $params);
        return [
            'status' => 'success',
            'data' => $result
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// Esegui il test di validazione
$result = testValidation();

// Pulisci il buffer output
ob_clean();

// Mostra il risultato come JSON
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);