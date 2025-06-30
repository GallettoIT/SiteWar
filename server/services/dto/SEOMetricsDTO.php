<?php
/**
 * SEOMetricsDTO
 * 
 * DTO specializzato per le metriche SEO.
 * Standardizza e normalizza i dati SEO da varie fonti.
 */

require_once __DIR__ . '/ResponseDTO.php';

class SEOMetricsDTO extends BaseMetricsDTO {
    /**
     * Costruttore
     * 
     * @param array $data Dati grezzi delle metriche
     */
    public function __construct($data = []) {
        // Definisce le metriche obbligatorie con valori predefiniti
        $this->metrics = [
            'meta_title' => null,
            'meta_description' => null,
            'headings_structure' => null,
            'alt_tags' => null,
            'url_structure' => null,
            'schema_markup' => null,
            'domain_authority' => null,
            'page_authority' => null,
            'backlinks' => null,
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
            'meta_title' => ['title', 'metaTitle', 'pageTitle'],
            'meta_description' => ['description', 'metaDescription', 'pageDescription'],
            'headings_structure' => ['headings', 'headers', 'headingsStructure'],
            'alt_tags' => ['alt', 'image_alt', 'imageAlt'],
            'url_structure' => ['urls', 'url', 'urlFormat'],
            'schema_markup' => ['schema', 'structured_data', 'structuredData']
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
        
        // Cerca in "metaTags"
        if (isset($data['metaTags']) && is_array($data['metaTags'])) {
            if (isset($data['metaTags']['title']) && $this->metrics['meta_title'] === null) {
                $this->metrics['meta_title'] = $data['metaTags']['title'];
            }
            
            if (isset($data['metaTags']['description']) && $this->metrics['meta_description'] === null) {
                $this->metrics['meta_description'] = $data['metaTags']['description'];
            }
        }
        
        // Cerca in "headings"
        if (isset($data['headings']) && is_array($data['headings']) && $this->metrics['headings_structure'] === null) {
            // Converti la struttura dei headings in un formato leggibile
            $this->metrics['headings_structure'] = $this->formatHeadingsData($data['headings']);
        }
        
        // Cerca in "images"
        if (isset($data['images']) && is_array($data['images']) && $this->metrics['alt_tags'] === null) {
            if (isset($data['images']['altPercentage'])) {
                $this->metrics['alt_tags'] = $data['images']['altPercentage'];
            } elseif (isset($data['images']['withAlt']) && isset($data['images']['total'])) {
                $total = $data['images']['total'];
                if ($total > 0) {
                    $this->metrics['alt_tags'] = round(($data['images']['withAlt'] / $total) * 100);
                }
            }
        }
        
        // Cerca in "url"
        if (isset($data['url']) && is_array($data['url']) && $this->metrics['url_structure'] === null) {
            if (isset($data['url']['seoFriendly'])) {
                $this->metrics['url_structure'] = $data['url']['seoFriendly'] ? 100 : 0;
            }
        }
        
        // Cerca in "structuredData"
        if (isset($data['structuredData']) && is_array($data['structuredData']) && $this->metrics['schema_markup'] === null) {
            if (isset($data['structuredData']['detected'])) {
                $this->metrics['schema_markup'] = $data['structuredData']['detected'] ? 100 : 0;
            }
        }
        
        // Estrai dati API esterne se presenti
        $this->extractExternalData($data);
        
        // Cerca con prefissi
        $prefixedSearch = ['seo_', 'moz_', 'whois_'];
        foreach ($prefixedSearch as $prefix) {
            $this->searchPrefixedData($data, $prefix);
        }
        
        // Imposta punteggio totale se non trovato
        if ($this->metrics['total_score'] == 0) {
            if (isset($data['totalScore'])) {
                $this->metrics['total_score'] = $data['totalScore'];
            } else if (isset($data['score'])) {
                $this->metrics['total_score'] = $data['score'];
            } else if (isset($data['seoScore'])) {
                $this->metrics['total_score'] = $data['seoScore'];
            }
        }
    }
    
    /**
     * Formatta i dati dei headings in un formato leggibile
     * 
     * @param array $headingsData
     * @return mixed
     */
    private function formatHeadingsData($headingsData) {
        // Se esiste già un punteggio, usalo
        if (isset($headingsData['score'])) {
            return $headingsData['score'];
        }
        
        // Se ci sono conteggi di h1, h2, etc, crea un riepilogo
        $summary = [];
        for ($i = 1; $i <= 6; $i++) {
            $key = "h{$i}Count";
            if (isset($headingsData[$key])) {
                $summary["h{$i}"] = $headingsData[$key];
            }
        }
        
        return !empty($summary) ? $summary : null;
    }
    
    /**
     * Estrae dati da API esterne
     * 
     * @param array $data
     */
    private function extractExternalData($data) {
        // Cerca dati esterni
        if (isset($data['external']) && is_array($data['external'])) {
            $external = $data['external'];
            
            // Dati Moz
            if (isset($external['domain_authority'])) {
                $this->metrics['domain_authority'] = $external['domain_authority'];
            }
            
            if (isset($external['page_authority'])) {
                $this->metrics['page_authority'] = $external['page_authority'];
            }
            
            if (isset($external['backlinks'])) {
                $this->metrics['backlinks'] = $external['backlinks'];
            }
        }
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
    
    // Getters specifici
    public function getMetaTitle() { return $this->getMetric('meta_title'); }
    public function getMetaDescription() { return $this->getMetric('meta_description'); }
    public function getHeadingsStructure() { return $this->getMetric('headings_structure'); }
    public function getAltTags() { return $this->getMetric('alt_tags'); }
    public function getUrlStructure() { return $this->getMetric('url_structure'); }
    public function getSchemaMarkup() { return $this->getMetric('schema_markup'); }
    public function getDomainAuthority() { return $this->getMetric('domain_authority'); }
    public function getPageAuthority() { return $this->getMetric('page_authority'); }
    public function getBacklinks() { return $this->getMetric('backlinks'); }
    public function getTotalScore() { return $this->getMetric('total_score'); }
}