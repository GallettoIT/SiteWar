<?php
/**
 * ServiceFactory
 * 
 * Implementa il pattern Factory Method per creare istanze di servizi e analizzatori.
 * Centralizza la creazione di oggetti e facilita l'estensibilità del sistema.
 * 
 * Pattern implementati:
 * - Factory Method
 */

// Include le classi necessarie
require_once __DIR__ . '/../services/BaseService.php';
require_once __DIR__ . '/../services/ProxyService.php';
require_once __DIR__ . '/../services/AIService.php';
require_once __DIR__ . '/../services/analyzers/BaseAnalyzer.php';
require_once __DIR__ . '/../services/analyzers/SEOAnalyzer.php';
require_once __DIR__ . '/../services/analyzers/SecurityAnalyzer.php';
require_once __DIR__ . '/../services/analyzers/PerformanceAnalyzer.php';
require_once __DIR__ . '/../services/analyzers/TechnologyAnalyzer.php';

class ServiceFactory {
    /**
     * Crea un'istanza di servizio in base al tipo richiesto
     * 
     * @param string $type Il tipo di servizio da creare
     * @param array $config La configurazione per il servizio
     * @return BaseService Un'istanza del servizio richiesto
     * @throws Exception Se il tipo di servizio non è supportato
     */
    public function createService($type, $config = []) {
        switch ($type) {
            case 'proxy':
                return new ProxyService($config);
            case 'ai':
                return new AIService($config);
            default:
                throw new Exception("Tipo di servizio non supportato: {$type}");
        }
    }
    
    /**
     * Crea un'istanza di analizzatore in base al tipo richiesto
     * 
     * @param string $type Il tipo di analizzatore da creare
     * @param string $url L'URL del sito da analizzare
     * @param array $config La configurazione per l'analizzatore
     * @return BaseAnalyzer Un'istanza dell'analizzatore richiesto
     * @throws Exception Se il tipo di analizzatore non è supportato
     */
    public function createAnalyzer($type, $url, $config = []) {
        switch ($type) {
            case 'seo':
                return new SEOAnalyzer($url, $config);
            case 'security':
                return new SecurityAnalyzer($url, $config);
            case 'performance':
                return new PerformanceAnalyzer($url, $config);
            case 'technology':
                return new TechnologyAnalyzer($url, $config);
            default:
                throw new Exception("Tipo di analizzatore non supportato: {$type}");
        }
    }
    
    /**
     * Restituisce un array con i tipi di analizzatori disponibili
     * 
     * @return array I tipi di analizzatori disponibili
     */
    public function getAvailableAnalyzers() {
        return [
            'seo',
            'security',
            'performance'
        ];
    }
}