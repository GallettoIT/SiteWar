<?php
/**
 * ReportController
 * 
 * Controller responsabile della gestione delle richieste di stato
 * e report delle analisi in corso.
 * 
 * Implementa:
 * - Verifica stato avanzamento analisi
 * - Recupero risultati completi o parziali
 * - Generazione report in diversi formati
 * 
 * Pattern utilizzati:
 * - Command Pattern
 * - Observer Pattern
 */

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../utils/Cache.php';

class ReportController implements Controller {
    /**
     * @var Cache Sistema di cache
     */
    private $cache;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->cache = new Cache();
    }
    
    /**
     * Ottiene l'ID dell'ultima analisi dalla cache
     * 
     * @return string|null L'ID dell'ultima analisi o null se non trovato
     */
    private function getLatestAnalysisId() {
        // Cerca i file di cache che iniziano con 'status_analysis_'
        $cacheDir = BASE_PATH . '/server/cache/data';
        $latestTime = 0;
        $latestFile = null;
        
        if (is_dir($cacheDir)) {
            $files = scandir($cacheDir);
            foreach ($files as $file) {
                if (strpos($file, 'status_analysis_') === 0) {
                    $filePath = $cacheDir . '/' . $file;
                    $mtime = filemtime($filePath);
                    if ($mtime > $latestTime) {
                        $latestTime = $mtime;
                        $latestFile = $file;
                    }
                }
            }
        }
        
        if ($latestFile) {
            // Estrae l'ID dall'nome del file cache (formato: 'status_analysis_ID.cache')
            return str_replace(['status_', '.cache'], '', $latestFile);
        }
        
        return null;
    }
    
    /**
     * Gestisce una richiesta HTTP
     * 
     * @param string $method Il metodo HTTP (GET, POST, etc.)
     * @param array $params I parametri della richiesta
     * @return array La risposta da restituire al client
     * @throws Exception Se la richiesta non è valida o si verifica un errore
     */
    public function handleRequest($method, $params) {
        // Per GET e POST sono ammessi
        if ($method !== 'GET' && $method !== 'POST') {
            throw new Exception('Metodo non supportato. Utilizzare GET o POST.', 405);
        }
        
        // Risposta rapida per retrocompatibilità - l'analisi è ora sincrona
        return [
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Analisi completata',
            'timestamp' => time()
        ];
    }
    
    /**
     * Recupera lo stato di un'analisi dalla cache
     * 
     * @param string $analysisId ID dell'analisi
     * @return array|null Lo stato dell'analisi o null se non trovato
     */
    private function getAnalysisStatus($analysisId) {
        // Recupera lo stato dalla cache
        $status = $this->cache->get("status_{$analysisId}");
        
        if (!$status) {
            return null;
        }
        
        return $status;
    }
    
    /**
     * Genera un report in un formato specifico
     * 
     * @param array $results Risultati dell'analisi
     * @param string $format Formato del report (csv, html, json)
     * @return array Il report generato
     * @throws Exception Se il formato non è supportato
     */
    private function generateReport($results, $format) {
        switch ($format) {
            case 'csv':
                return $this->generateCSVReport($results);
            case 'html':
                return $this->generateHTMLReport($results);
            case 'json':
                return $results;
            default:
                throw new Exception("Formato non supportato: {$format}", 400);
        }
    }
    
    /**
     * Genera un report in formato CSV
     * 
     * @param array $results Risultati dell'analisi
     * @return array Il report in formato CSV
     */
    private function generateCSVReport($results) {
        // Prepara l'intestazione CSV
        $csvHeader = "Categoria,Metrica,Sito 1,Sito 2,Vincitore\n";
        $csvContent = $csvHeader;
        
        // Categorie da includere nel report
        $categories = ['performance', 'seo', 'security', 'technical'];
        
        // Nomi dei siti
        $site1 = parse_url($results['url1'], PHP_URL_HOST);
        $site2 = parse_url($results['url2'], PHP_URL_HOST);
        
        // Genera le righe del CSV per ogni categoria e metrica
        foreach ($categories as $category) {
            if (isset($results['comparison'][$category]) && is_array($results['comparison'][$category])) {
                foreach ($results['comparison'][$category] as $metric => $comparison) {
                    $value1 = $results['site1'][$category][$metric] ?? 'N/A';
                    $value2 = $results['site2'][$category][$metric] ?? 'N/A';
                    $winner = $comparison['winner'] ?? 'Pareggio';
                    
                    // Formatta i valori per il CSV
                    if (is_numeric($value1)) {
                        $value1 = round($value1, 2);
                    }
                    if (is_numeric($value2)) {
                        $value2 = round($value2, 2);
                    }
                    
                    // Aggiunge la riga al CSV
                    $csvContent .= "{$category},{$metric},{$value1},{$value2},{$winner}\n";
                }
            }
        }
        
        // Aggiunge il punteggio finale
        $totalScore1 = $results['site1']['totalScore'] ?? 0;
        $totalScore2 = $results['site2']['totalScore'] ?? 0;
        $finalWinner = $results['winner'] ?? 'Pareggio';
        
        $csvContent .= "Totale,Punteggio finale,{$totalScore1},{$totalScore2},{$finalWinner}\n";
        
        // Restituisce il report CSV
        return [
            'format' => 'csv',
            'content' => $csvContent,
            'filename' => "site_war_report_{$site1}_vs_{$site2}.csv"
        ];
    }
    
    /**
     * Genera un report in formato HTML
     * 
     * @param array $results Risultati dell'analisi
     * @return array Il report in formato HTML
     */
    private function generateHTMLReport($results) {
        // Implementazione base di un report HTML
        $site1 = parse_url($results['url1'], PHP_URL_HOST);
        $site2 = parse_url($results['url2'], PHP_URL_HOST);
        
        $html = "<!DOCTYPE html>
<html lang=\"it\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Site War Report: {$site1} vs {$site2}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .winner { font-weight: bold; color: green; }
        .loser { color: red; }
        .draw { color: orange; }
        .result-box { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Site War Report</h1>
    <h2>{$site1} vs {$site2}</h2>
    
    <div class=\"result-box\">
        <h3>Risultato Finale</h3>
        <p>Vincitore: <strong>{$results['winner']}</strong></p>
        <p>Punteggio {$site1}: {$results['site1']['totalScore']}</p>
        <p>Punteggio {$site2}: {$results['site2']['totalScore']}</p>
    </div>";
        
        // Categorie da includere nel report
        $categories = ['performance', 'seo', 'security', 'technical'];
        
        // Genera tabelle per ogni categoria
        foreach ($categories as $category) {
            $categoryTitle = ucfirst($category);
            
            $html .= "
    <h3>Categoria: {$categoryTitle}</h3>
    <table>
        <tr>
            <th>Metrica</th>
            <th>{$site1}</th>
            <th>{$site2}</th>
            <th>Vincitore</th>
        </tr>";
            
            if (isset($results['comparison'][$category]) && is_array($results['comparison'][$category])) {
                foreach ($results['comparison'][$category] as $metric => $comparison) {
                    $value1 = $results['site1'][$category][$metric] ?? 'N/A';
                    $value2 = $results['site2'][$category][$metric] ?? 'N/A';
                    $winner = $comparison['winner'] ?? 'Pareggio';
                    
                    // Formatta i valori
                    if (is_numeric($value1)) {
                        $value1 = round($value1, 2);
                    }
                    if (is_numeric($value2)) {
                        $value2 = round($value2, 2);
                    }
                    
                    // Determina le classi CSS per evidenziare il vincitore
                    $class1 = ($winner === $site1) ? 'winner' : (($winner === 'Pareggio') ? 'draw' : 'loser');
                    $class2 = ($winner === $site2) ? 'winner' : (($winner === 'Pareggio') ? 'draw' : 'loser');
                    
                    $html .= "
        <tr>
            <td>{$metric}</td>
            <td class=\"{$class1}\">{$value1}</td>
            <td class=\"{$class2}\">{$value2}</td>
            <td>{$winner}</td>
        </tr>";
                }
            }
            
            $html .= "
    </table>";
        }
        
        $html .= "
</body>
</html>";
        
        // Restituisce il report HTML
        return [
            'format' => 'html',
            'content' => $html,
            'filename' => "site_war_report_{$site1}_vs_{$site2}.html"
        ];
    }
}