<?php
/**
 * APIController
 * 
 * Controller principale per gestire tutte le richieste API.
 * Implementa un meccanismo di routing semplice che indirizza le richieste
 * al controller specifico in base all'endpoint richiesto.
 * 
 * Pattern implementati:
 * - Front Controller
 * - Dependency Injection
 */

// Caricamento delle classi necessarie
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../api/controllers/AnalyzeController.php';
require_once __DIR__ . '/../api/controllers/ValidateController.php';
require_once __DIR__ . '/../api/controllers/ReportController.php';
require_once __DIR__ . '/../utils/Security.php';

class APIController {
    /**
     * @var array Mappa dei controller disponibili
     */
    private $controllers;
    
    /**
     * Costruttore
     * 
     * Inizializza la mappa dei controller disponibili
     */
    public function __construct() {
        // Inizializzazione dei controller
        $this->controllers = [
            'analyze' => new AnalyzeController(),
            'validate' => new ValidateController(),
            'progress' => new ReportController()
        ];
    }
    
    /**
     * Processa la richiesta API
     * 
     * Analizza l'URL richiesto, identifica il controller appropriato
     * e delega la gestione della richiesta a quel controller
     */
    public function processRequest() {
        // Ottieni il percorso richiesto
        $requestPath = $this->getRequestPath();
        
        // Analizza il metodo HTTP
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Ottieni i parametri di richiesta
        $params = $this->getRequestParams($method);
        
        // Sanitizza i parametri di input
        $params = Security::sanitizeInput($params);
        
        // Determina il controller da utilizzare
        $controllerName = $this->getControllerFromPath($requestPath);
        
        // Verifica se il controller esiste
        if (!isset($this->controllers[$controllerName])) {
            $this->sendResponse(404, [
                'status' => 'error',
                'message' => 'Endpoint API non trovato'
            ]);
            return;
        }
        
        try {
            // Ottieni il controller e gestisci la richiesta
            $controller = $this->controllers[$controllerName];
            $result = $controller->handleRequest($method, $params);
            
            // Invia la risposta
            $this->sendResponse(200, [
                'status' => 'success',
                'data' => $result
            ]);
        } catch (Exception $e) {
            // Gestione degli errori
            $statusCode = $e->getCode() ?: 500;
            $this->sendResponse($statusCode, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Ottiene il percorso della richiesta dall'URL
     * 
     * @return string Il percorso richiesto
     */
    private function getRequestPath() {
        // Priorità al parametro 'endpoint' che viene impostato dal RewriteRule
        if (isset($_GET['endpoint'])) {
            return trim($_GET['endpoint'], '/');
        }
        
        $path = $_SERVER['REQUEST_URI'] ?? '';
        
        // Rimuove query string se presente
        $path = parse_url($path, PHP_URL_PATH);
        
        // Rimuove il prefisso '/api/' se presente
        if (strpos($path, '/api/') === 0) {
            $path = substr($path, 5);
        }
        
        // Rimuove il prefisso '/server/api/' se presente
        if (strpos($path, '/server/api/') === 0) {
            $path = substr($path, 12);
        }
        
        return trim($path, '/');
    }
    
    /**
     * Determina il nome del controller dal percorso
     * 
     * @param string $path Il percorso richiesto
     * @return string Il nome del controller
     */
    private function getControllerFromPath($path) {
        // Se il percorso è vuoto, usa il controller predefinito
        if (empty($path)) {
            return 'analyze';
        }
        
        // Estrae il primo segmento del percorso come nome del controller
        $segments = explode('/', $path);
        return $segments[0];
    }
    
    /**
     * Ottiene i parametri della richiesta in base al metodo HTTP
     * 
     * @param string $method Il metodo HTTP
     * @return array I parametri della richiesta
     */
    private function getRequestParams($method) {
        $params = [];
        
        switch ($method) {
            case 'GET':
                $params = $_GET;
                break;
            case 'POST':
                // Controlla se è stato inviato JSON
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (strpos($contentType, 'application/json') !== false) {
                    $jsonData = file_get_contents('php://input');
                    $params = json_decode($jsonData, true) ?: [];
                } else {
                    $params = $_POST;
                }
                break;
            default:
                // Per altri metodi (PUT, DELETE, ecc.)
                parse_str(file_get_contents('php://input'), $params);
                break;
        }
        
        return $params;
    }
    
    /**
     * Invia una risposta HTTP con il codice di stato e i dati JSON
     * 
     * @param int $statusCode Il codice di stato HTTP
     * @param array $data I dati da inviare come JSON
     */
    private function sendResponse($statusCode, $data) {
        // Pulisce tutto l'output precedente per evitare contaminazione del JSON
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Imposta l'header HTTP
        http_response_code($statusCode);
        
        // Invia il JSON
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}