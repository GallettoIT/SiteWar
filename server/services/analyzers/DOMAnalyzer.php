<?php
/**
 * DOMAnalyzer
 * 
 * Analizzatore specializzato per la struttura HTML di un sito web.
 * Valuta la qualità del DOM, la semantica HTML, l'accessibilità
 * e altri aspetti strutturali della pagina.
 * 
 * Pattern implementati:
 * - Strategy
 * - Template Method
 * - Visitor (per l'analisi del DOM)
 */

require_once __DIR__ . '/BaseAnalyzer.php';

class DOMAnalyzer extends BaseAnalyzer {
    /**
     * @var array Mappatura dei tag semantici HTML5
     */
    private $semanticTags = [
        'header', 'footer', 'main', 'article', 'section', 'nav', 
        'aside', 'figure', 'figcaption', 'mark', 'time', 
        'details', 'summary', 'menu'
    ];
    
    /**
     * @var array Tag deprecati o obsoleti in HTML5
     */
    private $deprecatedTags = [
        'applet', 'basefont', 'big', 'center', 'dir', 'font', 
        'frame', 'frameset', 'noframes', 'strike', 'tt'
    ];
    
    /**
     * @var array Attributi ARIA per l'accessibilità
     */
    private $ariaAttributes = [
        'aria-label', 'aria-labelledby', 'aria-describedby', 'aria-hidden', 
        'aria-live', 'aria-atomic', 'aria-relevant', 'role'
    ];
    
    /**
     * Esegue l'analisi DOM specifica
     */
    protected function doAnalyze() {
        // Analizza la struttura generale del DOM
        $this->analyzeDOMStructure();
        
        // Analizza la semantica HTML
        $this->analyzeSemanticHTML();
        
        // Analizza l'accessibilità
        $this->analyzeAccessibility();
        
        // Analizza la validità HTML
        $this->analyzeHTMLValidity();
        
        // Analizza le relazioni tra elementi
        $this->analyzeElementRelationships();
        
        // Analizza i meta data strutturati
        $this->analyzeStructuredData();
        
        // Analizza l'organizzazione responsiva
        $this->analyzeResponsiveness();
        
        // Calcola i punteggi finali
        $this->calculateScores();
    }
    
    /**
     * Analizza la struttura generale del DOM
     */
    private function analyzeDOMStructure() {
        // Inizializza i risultati per la struttura del DOM
        $this->results['domStructure'] = [
            'elementCount' => 0,
            'depth' => 0,
            'averageDepth' => 0,
            'maxChildren' => 0,
            'depthDistribution' => [],
            'score' => 0
        ];
        
        // Conta elementi e valuta profondità
        $elementCount = 0;
        $maxDepth = 0;
        $depthSum = 0;
        $depthDistribution = [];
        $maxChildren = 0;
        
        // Funzione ricorsiva per analizzare il DOM
        $analyzeNode = function($node, $depth = 0) use (&$elementCount, &$maxDepth, &$depthSum, &$depthDistribution, &$maxChildren, &$analyzeNode) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                return;
            }
            
            $elementCount++;
            $maxDepth = max($maxDepth, $depth);
            $depthSum += $depth;
            
            if (!isset($depthDistribution[$depth])) {
                $depthDistribution[$depth] = 0;
            }
            $depthDistribution[$depth]++;
            
            // Conta i figli di tipo element
            $childElementCount = 0;
            foreach ($node->childNodes as $childNode) {
                if ($childNode->nodeType === XML_ELEMENT_NODE) {
                    $childElementCount++;
                }
            }
            
            $maxChildren = max($maxChildren, $childElementCount);
            
            // Analizza ricorsivamente i nodi figli
            foreach ($node->childNodes as $childNode) {
                $analyzeNode($childNode, $depth + 1);
            }
        };
        
        // Inizia l'analisi dal documento
        $analyzeNode($this->dom->documentElement);
        
        // Calcola la profondità media
        $averageDepth = $elementCount > 0 ? $depthSum / $elementCount : 0;
        
        // Memorizza i risultati
        $this->results['domStructure']['elementCount'] = $elementCount;
        $this->results['domStructure']['depth'] = $maxDepth;
        $this->results['domStructure']['averageDepth'] = round($averageDepth, 2);
        $this->results['domStructure']['maxChildren'] = $maxChildren;
        $this->results['domStructure']['depthDistribution'] = $depthDistribution;
        
        // Valuta la qualità della struttura DOM
        $this->evaluateDOMStructure();
    }
    
    /**
     * Valuta la qualità della struttura DOM
     */
    private function evaluateDOMStructure() {
        $score = 100; // Punteggio iniziale massimo
        
        // Penalità per DOM troppo grande
        $elementCount = $this->results['domStructure']['elementCount'];
        if ($elementCount > 5000) {
            $score -= 50;
        } elseif ($elementCount > 3000) {
            $score -= 30;
        } elseif ($elementCount > 1500) {
            $score -= 15;
        } elseif ($elementCount > 1000) {
            $score -= 5;
        }
        
        // Penalità per DOM troppo profondo
        $maxDepth = $this->results['domStructure']['depth'];
        if ($maxDepth > 20) {
            $score -= 30;
        } elseif ($maxDepth > 15) {
            $score -= 20;
        } elseif ($maxDepth > 10) {
            $score -= 10;
        }
        
        // Penalità per eccessivo numero di figli
        $maxChildren = $this->results['domStructure']['maxChildren'];
        if ($maxChildren > 100) {
            $score -= 25;
        } elseif ($maxChildren > 50) {
            $score -= 15;
        } elseif ($maxChildren > 30) {
            $score -= 5;
        }
        
        // Memorizza il punteggio
        $this->results['domStructure']['score'] = max(0, $score);
    }
    
    /**
     * Analizza la semantica HTML
     */
    private function analyzeSemanticHTML() {
        // Inizializza i risultati per la semantica HTML
        $this->results['semanticHTML'] = [
            'semanticTagsCount' => [],
            'deprecatedTagsCount' => [],
            'semanticScore' => 0,
            'hasDoctype' => false,
            'hasHtmlLang' => false,
            'hasPageTitle' => false,
            'hasMainContent' => false,
            'score' => 0
        ];
        
        // Controlla il doctype
        $this->results['semanticHTML']['hasDoctype'] = $this->dom->doctype !== null;
        
        // Controlla l'attributo lang
        $htmlElement = $this->dom->getElementsByTagName('html')->item(0);
        $this->results['semanticHTML']['hasHtmlLang'] = $htmlElement && $htmlElement->hasAttribute('lang');
        
        // Controlla il titolo della pagina
        $titleElements = $this->dom->getElementsByTagName('title');
        $this->results['semanticHTML']['hasPageTitle'] = $titleElements->length > 0 && !empty(trim($titleElements->item(0)->nodeValue));
        
        // Controlla la presenza di contenuto principale
        $mainElements = $this->dom->getElementsByTagName('main');
        $this->results['semanticHTML']['hasMainContent'] = $mainElements->length > 0;
        
        // Conta tag semantici
        foreach ($this->semanticTags as $tag) {
            $elements = $this->dom->getElementsByTagName($tag);
            $this->results['semanticHTML']['semanticTagsCount'][$tag] = $elements->length;
        }
        
        // Conta tag deprecati
        foreach ($this->deprecatedTags as $tag) {
            $elements = $this->dom->getElementsByTagName($tag);
            if ($elements->length > 0) {
                $this->results['semanticHTML']['deprecatedTagsCount'][$tag] = $elements->length;
            }
        }
        
        // Calcola il punteggio semantico
        $this->evaluateSemanticHTML();
    }
    
    /**
     * Valuta la qualità della semantica HTML
     */
    private function evaluateSemanticHTML() {
        $score = 0;
        
        // Valuta la presenza di elementi fondamentali
        if ($this->results['semanticHTML']['hasDoctype']) {
            $score += 15;
        }
        
        if ($this->results['semanticHTML']['hasHtmlLang']) {
            $score += 10;
        }
        
        if ($this->results['semanticHTML']['hasPageTitle']) {
            $score += 15;
        }
        
        if ($this->results['semanticHTML']['hasMainContent']) {
            $score += 15;
        }
        
        // Valuta l'utilizzo di tag semantici
        $semanticTagsUsed = 0;
        foreach ($this->results['semanticHTML']['semanticTagsCount'] as $tag => $count) {
            if ($count > 0) {
                $semanticTagsUsed++;
            }
        }
        
        // Bonus per l'utilizzo di tag semantici (max 30 punti)
        $semanticBonus = min(30, $semanticTagsUsed * 3);
        $score += $semanticBonus;
        
        // Penalità per l'utilizzo di tag deprecati
        $deprecatedTagsCount = array_sum($this->results['semanticHTML']['deprecatedTagsCount']);
        $deprecatedPenalty = min(30, $deprecatedTagsCount * 5);
        $score -= $deprecatedPenalty;
        
        // Memorizza il punteggio
        $this->results['semanticHTML']['semanticScore'] = $semanticTagsUsed;
        $this->results['semanticHTML']['score'] = max(0, min(100, $score + 15)); // +15 bonus iniziale
    }
    
    /**
     * Analizza l'accessibilità della pagina
     */
    private function analyzeAccessibility() {
        // Inizializza i risultati per l'accessibilità
        $this->results['accessibility'] = [
            'imgAltCount' => 0,
            'imgNoAltCount' => 0,
            'ariaAttributesCount' => 0,
            'formLabelsCount' => 0,
            'formNoLabelsCount' => 0,
            'tableHeadersCount' => 0,
            'tabIndexCount' => 0,
            'colorContrastIssues' => 0, // Stimato
            'score' => 0
        ];
        
        // Analizza le immagini
        $imgElements = $this->dom->getElementsByTagName('img');
        foreach ($imgElements as $img) {
            if ($img->hasAttribute('alt')) {
                $this->results['accessibility']['imgAltCount']++;
            } else {
                $this->results['accessibility']['imgNoAltCount']++;
            }
        }
        
        // Analizza gli attributi ARIA
        $ariaCount = 0;
        $xpath = new DOMXPath($this->dom);
        
        foreach ($this->ariaAttributes as $attr) {
            $elements = $xpath->query("//*[@$attr]");
            $ariaCount += $elements->length;
        }
        
        $this->results['accessibility']['ariaAttributesCount'] = $ariaCount;
        
        // Analizza i form e label
        $inputElements = $this->dom->getElementsByTagName('input');
        $selectElements = $this->dom->getElementsByTagName('select');
        $textareaElements = $this->dom->getElementsByTagName('textarea');
        $formElements = [];
        
        // Raccoglie tutti gli elementi di form
        foreach ($inputElements as $input) {
            if ($input->getAttribute('type') !== 'hidden') {
                $formElements[] = $input;
            }
        }
        
        foreach ($selectElements as $select) {
            $formElements[] = $select;
        }
        
        foreach ($textareaElements as $textarea) {
            $formElements[] = $textarea;
        }
        
        // Verifica la presenza di label
        $labeledCount = 0;
        $unlabeledCount = 0;
        
        foreach ($formElements as $element) {
            $hasLabel = false;
            
            // Controllo diretto con attributo id
            if ($element->hasAttribute('id')) {
                $id = $element->getAttribute('id');
                $labels = $xpath->query("//label[@for='$id']");
                if ($labels->length > 0) {
                    $hasLabel = true;
                }
            }
            
            // Controllo se l'elemento è avvolto in un tag label
            if (!$hasLabel) {
                $parent = $element->parentNode;
                while ($parent && $parent->nodeType === XML_ELEMENT_NODE) {
                    if ($parent->nodeName === 'label') {
                        $hasLabel = true;
                        break;
                    }
                    $parent = $parent->parentNode;
                }
            }
            
            // Controllo attributo aria-label o aria-labelledby
            if (!$hasLabel && ($element->hasAttribute('aria-label') || $element->hasAttribute('aria-labelledby'))) {
                $hasLabel = true;
            }
            
            if ($hasLabel) {
                $labeledCount++;
            } else {
                $unlabeledCount++;
            }
        }
        
        $this->results['accessibility']['formLabelsCount'] = $labeledCount;
        $this->results['accessibility']['formNoLabelsCount'] = $unlabeledCount;
        
        // Analizza tabelle
        $tableElements = $this->dom->getElementsByTagName('table');
        $tableHeadersCount = 0;
        
        foreach ($tableElements as $table) {
            $thElements = $table->getElementsByTagName('th');
            $tableHeadersCount += $thElements->length;
        }
        
        $this->results['accessibility']['tableHeadersCount'] = $tableHeadersCount;
        
        // Analizza tabindex
        $tabIndexElements = $xpath->query("//*[@tabindex]");
        $this->results['accessibility']['tabIndexCount'] = $tabIndexElements->length;
        
        // Stima problemi di contrasto (analisi esatta richiederebbe rendering)
        $this->results['accessibility']['colorContrastIssues'] = 0; // Valore di default
        
        // Valuta l'accessibilità complessiva
        $this->evaluateAccessibility();
    }
    
    /**
     * Valuta la qualità dell'accessibilità
     */
    private function evaluateAccessibility() {
        $score = 0;
        
        // Valuta alt text per immagini
        $imgTotal = $this->results['accessibility']['imgAltCount'] + $this->results['accessibility']['imgNoAltCount'];
        if ($imgTotal > 0) {
            $imgAltRatio = $this->results['accessibility']['imgAltCount'] / $imgTotal;
            $score += $imgAltRatio * 25; // Max 25 punti
        } else {
            $score += 5; // Bonus se non ci sono immagini
        }
        
        // Valuta label per form
        $formTotal = $this->results['accessibility']['formLabelsCount'] + $this->results['accessibility']['formNoLabelsCount'];
        if ($formTotal > 0) {
            $formLabelRatio = $this->results['accessibility']['formLabelsCount'] / $formTotal;
            $score += $formLabelRatio * 25; // Max 25 punti
        } else {
            $score += 5; // Bonus se non ci sono form
        }
        
        // Valuta utilizzo ARIA
        $elementCount = $this->results['domStructure']['elementCount'];
        $ariaRatio = $elementCount > 0 ? 
                    $this->results['accessibility']['ariaAttributesCount'] / $elementCount : 0;
        
        // Bonus per utilizzo ARIA (idealmente 5-15% degli elementi dovrebbero avere attributi ARIA)
        if ($ariaRatio >= 0.05 && $ariaRatio <= 0.15) {
            $score += 25;
        } else if ($ariaRatio > 0.15) {
            $score += 20; // Leggera penalità per overuse
        } else if ($ariaRatio > 0) {
            $score += $ariaRatio * 200; // Bonus proporzionale
        }
        
        // Bonus per header nelle tabelle
        if ($this->results['accessibility']['tableHeadersCount'] > 0) {
            $score += 10;
        }
        
        // Bonus/malus per tabindex
        if ($this->results['accessibility']['tabIndexCount'] > 0 && $this->results['accessibility']['tabIndexCount'] <= 10) {
            $score += 5; // Utilizzo moderato è positivo
        } else if ($this->results['accessibility']['tabIndexCount'] > 10) {
            $score -= 5; // Eccesso può indicare una cattiva struttura semantica
        }
        
        // Malus per i problemi di contrasto (stimati)
        $score -= $this->results['accessibility']['colorContrastIssues'] * 2;
        
        // Memorizza il punteggio
        $this->results['accessibility']['score'] = max(0, min(100, $score + 10)); // +10 bonus iniziale
    }
    
    /**
     * Analizza la validità HTML (stima approssimativa)
     */
    private function analyzeHTMLValidity() {
        // Inizializza i risultati per la validità HTML
        $this->results['htmlValidity'] = [
            'errors' => [],
            'warningCount' => 0,
            'errorCount' => 0,
            'score' => 0
        ];
        
        // Recupera gli errori di parsing libxml
        $errors = libxml_get_errors();
        libxml_clear_errors();
        
        $errorCount = 0;
        $warningCount = 0;
        $errorDetails = [];
        
        foreach ($errors as $error) {
            if ($error->level === LIBXML_ERR_WARNING) {
                $warningCount++;
            } else {
                $errorCount++;
                
                // Memorizza i dettagli dell'errore (limitati a 10)
                if (count($errorDetails) < 10) {
                    $errorDetails[] = [
                        'code' => $error->code,
                        'message' => trim($error->message),
                        'line' => $error->line,
                        'column' => $error->column
                    ];
                }
            }
        }
        
        $this->results['htmlValidity']['errorCount'] = $errorCount;
        $this->results['htmlValidity']['warningCount'] = $warningCount;
        $this->results['htmlValidity']['errors'] = $errorDetails;
        
        // Controlla anche problemi strutturali comuni
        $this->checkCommonStructuralIssues();
        
        // Valuta la validità HTML
        $this->evaluateHTMLValidity();
    }
    
    /**
     * Controlla problemi strutturali HTML comuni
     */
    private function checkCommonStructuralIssues() {
        $issues = [];
        
        // Verifica ID duplicati
        $idElements = [];
        $duplicateIds = [];
        $xpath = new DOMXPath($this->dom);
        $elements = $xpath->query("//*[@id]");
        
        foreach ($elements as $element) {
            $id = $element->getAttribute('id');
            if (isset($idElements[$id])) {
                $duplicateIds[$id] = true;
            }
            $idElements[$id] = true;
        }
        
        if (count($duplicateIds) > 0) {
            $issues[] = [
                'type' => 'duplicate_ids',
                'message' => 'La pagina contiene ID duplicati: ' . implode(', ', array_keys($duplicateIds)),
                'count' => count($duplicateIds)
            ];
        }
        
        // Verifica elementi nidificati impropriamente
        $improperNesting = 0;
        
        // Esempi di nidificazione impropria (p dentro p, div dentro span, etc.)
        $problematicPatterns = [
            '//p//p',
            '//a//a',
            '//button//button',
            '//span//div',
            '//ul//ul[not(parent::li)]',
            '//ol//ol[not(parent::li)]'
        ];
        
        foreach ($problematicPatterns as $pattern) {
            $result = $xpath->query($pattern);
            $improperNesting += $result->length;
        }
        
        if ($improperNesting > 0) {
            $issues[] = [
                'type' => 'improper_nesting',
                'message' => 'La pagina contiene elementi nidificati in modo improprio',
                'count' => $improperNesting
            ];
        }
        
        // Verifica balancing dei tag
        $unbalancedTags = array_sum(libxml_get_errors() > 0 ? 1 : 0);
        
        if ($unbalancedTags > 0) {
            $issues[] = [
                'type' => 'unbalanced_tags',
                'message' => 'La pagina potrebbe contenere tag non bilanciati',
                'count' => $unbalancedTags
            ];
        }
        
        $this->results['htmlValidity']['structuralIssues'] = $issues;
    }
    
    /**
     * Valuta la validità HTML
     */
    private function evaluateHTMLValidity() {
        $score = 100; // Punteggio iniziale massimo
        
        // Penalità per errori
        $errorPenalty = min(70, $this->results['htmlValidity']['errorCount'] * 2);
        $score -= $errorPenalty;
        
        // Penalità per warning
        $warningPenalty = min(20, $this->results['htmlValidity']['warningCount'] * 0.5);
        $score -= $warningPenalty;
        
        // Penalità per problemi strutturali
        $structuralIssueCount = 0;
        foreach ($this->results['htmlValidity']['structuralIssues'] as $issue) {
            $structuralIssueCount += $issue['count'];
        }
        
        $structuralPenalty = min(30, $structuralIssueCount * 3);
        $score -= $structuralPenalty;
        
        // Memorizza il punteggio
        $this->results['htmlValidity']['score'] = max(0, $score);
    }
    
    /**
     * Analizza le relazioni tra elementi
     */
    private function analyzeElementRelationships() {
        // Inizializza i risultati per le relazioni tra elementi
        $this->results['elementRelationships'] = [
            'headingHierarchy' => [],
            'headingStructureValid' => false,
            'listNestedCount' => 0,
            'sectionsWithHeadings' => 0,
            'sectionsWithoutHeadings' => 0,
            'score' => 0
        ];
        
        // Analizza la gerarchia degli heading
        $this->analyzeHeadingHierarchy();
        
        // Analizza la struttura delle liste
        $this->analyzeListStructure();
        
        // Analizza le sezioni e i loro heading
        $this->analyzeSectionHeadings();
        
        // Valuta le relazioni tra elementi
        $this->evaluateElementRelationships();
    }
    
    /**
     * Analizza la gerarchia degli heading
     */
    private function analyzeHeadingHierarchy() {
        $headings = [];
        $hierarchy = [];
        
        // Raccoglie tutti gli heading
        for ($i = 1; $i <= 6; $i++) {
            $elements = $this->dom->getElementsByTagName('h' . $i);
            $headings['h' . $i] = $elements->length;
            
            foreach ($elements as $element) {
                $hierarchy[] = [
                    'level' => $i,
                    'text' => trim($element->textContent)
                ];
            }
        }
        
        // Verifica se la gerarchia è valida (no h1->h3 senza h2, etc.)
        $valid = true;
        $currentLevel = 0;
        
        foreach ($hierarchy as $heading) {
            if ($heading['level'] > $currentLevel + 1 && $currentLevel > 0) {
                $valid = false;
                break;
            }
            $currentLevel = $heading['level'];
        }
        
        $this->results['elementRelationships']['headingHierarchy'] = $headings;
        $this->results['elementRelationships']['headingStructureValid'] = $valid;
    }
    
    /**
     * Analizza la struttura delle liste
     */
    private function analyzeListStructure() {
        $nestedListCount = 0;
        $xpath = new DOMXPath($this->dom);
        
        // Conta liste annidate
        $nestedUl = $xpath->query('//ul//ul');
        $nestedOl = $xpath->query('//ol//ol');
        $nestedListCount = $nestedUl->length + $nestedOl->length;
        
        $this->results['elementRelationships']['listNestedCount'] = $nestedListCount;
    }
    
    /**
     * Analizza le sezioni e i loro heading
     */
    private function analyzeSectionHeadings() {
        $sectionsWithHeadings = 0;
        $sectionsWithoutHeadings = 0;
        $xpath = new DOMXPath($this->dom);
        
        // Controlla section con heading
        $sections = $this->dom->getElementsByTagName('section');
        
        foreach ($sections as $section) {
            $hasHeading = false;
            
            // Cerca heading diretto
            for ($i = 1; $i <= 6; $i++) {
                $headings = $section->getElementsByTagName('h' . $i);
                if ($headings->length > 0) {
                    $hasHeading = true;
                    break;
                }
            }
            
            if ($hasHeading) {
                $sectionsWithHeadings++;
            } else {
                $sectionsWithoutHeadings++;
            }
        }
        
        $this->results['elementRelationships']['sectionsWithHeadings'] = $sectionsWithHeadings;
        $this->results['elementRelationships']['sectionsWithoutHeadings'] = $sectionsWithoutHeadings;
    }
    
    /**
     * Valuta le relazioni tra elementi
     */
    private function evaluateElementRelationships() {
        $score = 0;
        
        // Valuta la gerarchia degli heading
        if ($this->results['elementRelationships']['headingStructureValid']) {
            $score += 30;
        }
        
        // Bonus per presenza di heading di primo livello
        if (isset($this->results['elementRelationships']['headingHierarchy']['h1']) && 
            $this->results['elementRelationships']['headingHierarchy']['h1'] > 0) {
            $score += 10;
            
            // Penalità per troppi h1
            if ($this->results['elementRelationships']['headingHierarchy']['h1'] > 1) {
                $score -= 5;
            }
        }
        
        // Bonus per liste annidate (indicano buona struttura)
        if ($this->results['elementRelationships']['listNestedCount'] > 0) {
            $score += min(10, $this->results['elementRelationships']['listNestedCount'] * 2);
        }
        
        // Valuta sections con heading
        $totalSections = $this->results['elementRelationships']['sectionsWithHeadings'] + 
                        $this->results['elementRelationships']['sectionsWithoutHeadings'];
        
        if ($totalSections > 0) {
            $sectionHeadingRatio = $this->results['elementRelationships']['sectionsWithHeadings'] / $totalSections;
            $score += $sectionHeadingRatio * 30;
        }
        
        // Memorizza il punteggio
        $this->results['elementRelationships']['score'] = max(0, min(100, $score + 20)); // +20 bonus iniziale
    }
    
    /**
     * Analizza i metadati strutturati
     */
    private function analyzeStructuredData() {
        // Inizializza i risultati per i dati strutturati
        $this->results['structuredData'] = [
            'microdata' => false,
            'rdfa' => false,
            'jsonld' => false,
            'types' => [],
            'score' => 0
        ];
        
        $xpath = new DOMXPath($this->dom);
        
        // Rileva microdata
        $microdataElements = $xpath->query('//*[@itemscope]');
        $this->results['structuredData']['microdata'] = $microdataElements->length > 0;
        
        if ($this->results['structuredData']['microdata']) {
            $itemtypes = $xpath->query('//*[@itemtype]');
            foreach ($itemtypes as $element) {
                $type = $element->getAttribute('itemtype');
                if (strpos($type, 'schema.org/') !== false) {
                    $schemaType = substr($type, strrpos($type, '/') + 1);
                    $this->results['structuredData']['types'][] = 'schema:' . $schemaType;
                }
            }
        }
        
        // Rileva RDFa
        $rdfaElements = $xpath->query('//*[@typeof]');
        $this->results['structuredData']['rdfa'] = $rdfaElements->length > 0;
        
        if ($this->results['structuredData']['rdfa']) {
            foreach ($rdfaElements as $element) {
                $type = $element->getAttribute('typeof');
                if (!empty($type)) {
                    $this->results['structuredData']['types'][] = 'rdfa:' . $type;
                }
            }
        }
        
        // Rileva JSON-LD
        $jsonldScripts = $xpath->query('//script[@type="application/ld+json"]');
        $this->results['structuredData']['jsonld'] = $jsonldScripts->length > 0;
        
        if ($this->results['structuredData']['jsonld']) {
            foreach ($jsonldScripts as $script) {
                $jsonContent = $script->nodeValue;
                $jsonData = json_decode($jsonContent, true);
                
                if ($jsonData && isset($jsonData['@type'])) {
                    $this->results['structuredData']['types'][] = 'jsonld:' . $jsonData['@type'];
                }
            }
        }
        
        // Rimuovi duplicati
        $this->results['structuredData']['types'] = array_unique($this->results['structuredData']['types']);
        
        // Valuta i dati strutturati
        $this->evaluateStructuredData();
    }
    
    /**
     * Valuta i dati strutturati
     */
    private function evaluateStructuredData() {
        $score = 0;
        
        // Bonus per presenza di dati strutturati
        if ($this->results['structuredData']['microdata'] || 
            $this->results['structuredData']['rdfa'] || 
            $this->results['structuredData']['jsonld']) {
            
            $score += 50; // Bonus base per avere dati strutturati
            
            // Bonus per ogni formato utilizzato
            if ($this->results['structuredData']['microdata']) $score += 10;
            if ($this->results['structuredData']['rdfa']) $score += 10;
            if ($this->results['structuredData']['jsonld']) $score += 15; // Bonus maggiore per JSON-LD (forma preferita)
            
            // Bonus per diversità di tipi (max 15 punti)
            $typeCount = count($this->results['structuredData']['types']);
            $score += min(15, $typeCount * 3);
        }
        
        // Memorizza il punteggio
        $this->results['structuredData']['score'] = max(0, min(100, $score));
    }
    
    /**
     * Analizza la struttura responsiva della pagina
     */
    private function analyzeResponsiveness() {
        // Inizializza i risultati per la responsiveness
        $this->results['responsiveness'] = [
            'hasViewport' => false,
            'hasMediaQueries' => false,
            'hasPictureElement' => false,
            'responsiveImagesCount' => 0,
            'fluidLayoutClasses' => 0,
            'viewportContent' => '',
            'score' => 0
        ];
        
        // Controlla meta viewport
        $metaElements = $this->dom->getElementsByTagName('meta');
        
        foreach ($metaElements as $meta) {
            if ($meta->getAttribute('name') === 'viewport') {
                $this->results['responsiveness']['hasViewport'] = true;
                $this->results['responsiveness']['viewportContent'] = $meta->getAttribute('content');
                break;
            }
        }
        
        // Controlla la presenza di media queries nei tag style
        $styleElements = $this->dom->getElementsByTagName('style');
        $hasMediaQueries = false;
        
        foreach ($styleElements as $style) {
            if (strpos($style->textContent, '@media') !== false) {
                $hasMediaQueries = true;
                break;
            }
        }
        
        $this->results['responsiveness']['hasMediaQueries'] = $hasMediaQueries;
        
        // Controlla la presenza dell'elemento picture
        $pictureElements = $this->dom->getElementsByTagName('picture');
        $this->results['responsiveness']['hasPictureElement'] = $pictureElements->length > 0;
        
        // Controlla immagini responsive (srcset, sizes)
        $imgElements = $this->dom->getElementsByTagName('img');
        $responsiveImagesCount = 0;
        
        foreach ($imgElements as $img) {
            if ($img->hasAttribute('srcset') || $img->hasAttribute('sizes')) {
                $responsiveImagesCount++;
            }
        }
        
        $this->results['responsiveness']['responsiveImagesCount'] = $responsiveImagesCount;
        
        // Controlla classi che indicano layout fluido
        $xpath = new DOMXPath($this->dom);
        $responsiveClassPatterns = [
            'container', 'row', 'col', 'flex', 'grid', 'sm-', 'md-', 'lg-', 'xl-', 
            'w-', 'h-', 'fluid', 'mobile', 'tablet', 'desktop'
        ];
        
        $fluidLayoutClasses = 0;
        
        foreach ($responsiveClassPatterns as $pattern) {
            $elements = $xpath->query("//*[contains(@class, '$pattern')]");
            $fluidLayoutClasses += $elements->length;
        }
        
        $this->results['responsiveness']['fluidLayoutClasses'] = $fluidLayoutClasses;
        
        // Valuta la responsiveness
        $this->evaluateResponsiveness();
    }
    
    /**
     * Valuta la responsiveness
     */
    private function evaluateResponsiveness() {
        $score = 0;
        
        // Valuta meta viewport
        if ($this->results['responsiveness']['hasViewport']) {
            $score += 25;
            
            // Bonus per configurazione corretta del viewport
            $viewportContent = $this->results['responsiveness']['viewportContent'];
            if (strpos($viewportContent, 'width=device-width') !== false && 
                strpos($viewportContent, 'initial-scale=1') !== false) {
                $score += 10;
            }
        }
        
        // Valuta media queries
        if ($this->results['responsiveness']['hasMediaQueries']) {
            $score += 20;
        }
        
        // Valuta immagini responsive
        if ($this->results['responsiveness']['hasPictureElement']) {
            $score += 15;
        }
        
        $imgElements = $this->dom->getElementsByTagName('img');
        if ($imgElements->length > 0) {
            $responsiveImgRatio = $this->results['responsiveness']['responsiveImagesCount'] / $imgElements->length;
            $score += $responsiveImgRatio * 15;
        }
        
        // Valuta classi per layout fluido
        if ($this->results['responsiveness']['fluidLayoutClasses'] > 10) {
            $score += 15;
        } else if ($this->results['responsiveness']['fluidLayoutClasses'] > 0) {
            $score += $this->results['responsiveness']['fluidLayoutClasses'] * 1.5;
        }
        
        // Memorizza il punteggio
        $this->results['responsiveness']['score'] = max(0, min(100, $score));
    }
    
    /**
     * Calcola i punteggi finali
     */
    private function calculateScores() {
        // Pesi per le diverse categorie
        $weights = [
            'domStructure' => 0.15,
            'semanticHTML' => 0.20,
            'accessibility' => 0.25,
            'htmlValidity' => 0.15,
            'elementRelationships' => 0.10,
            'structuredData' => 0.05,
            'responsiveness' => 0.10
        ];
        
        // Calcola il punteggio totale
        $totalScore = 0;
        
        foreach ($weights as $category => $weight) {
            if (isset($this->results[$category]['score'])) {
                $totalScore += $this->results[$category]['score'] * $weight;
            }
        }
        
        // Arrotonda il punteggio
        $this->results['totalScore'] = round($totalScore, 2);
        
        // Aggiungi consigli per miglioramenti
        $this->addRecommendations();
    }
    
    /**
     * Aggiunge consigli per miglioramenti
     */
    private function addRecommendations() {
        $this->results['recommendations'] = [];
        
        // Consigli per la struttura del DOM
        if ($this->results['domStructure']['score'] < 70) {
            if ($this->results['domStructure']['elementCount'] > 1500) {
                $this->results['recommendations'][] = 'Ridurre la complessità del DOM rimuovendo elementi non necessari';
            }
            
            if ($this->results['domStructure']['depth'] > 15) {
                $this->results['recommendations'][] = 'Ridurre la profondità della struttura DOM, semplificando la nidificazione';
            }
        }
        
        // Consigli per la semantica HTML
        if ($this->results['semanticHTML']['score'] < 70) {
            if (!$this->results['semanticHTML']['hasDoctype']) {
                $this->results['recommendations'][] = 'Aggiungere il doctype HTML5 (<!DOCTYPE html>)';
            }
            
            if (!$this->results['semanticHTML']['hasHtmlLang']) {
                $this->results['recommendations'][] = 'Aggiungere l\'attributo lang all\'elemento html';
            }
            
            if (!$this->results['semanticHTML']['hasMainContent']) {
                $this->results['recommendations'][] = 'Utilizzare l\'elemento <main> per il contenuto principale';
            }
            
            if (count($this->results['semanticHTML']['semanticTagsCount']) < 3) {
                $this->results['recommendations'][] = 'Utilizzare più tag semantici HTML5 (header, footer, nav, article, section, etc.)';
            }
            
            if (!empty($this->results['semanticHTML']['deprecatedTagsCount'])) {
                $this->results['recommendations'][] = 'Sostituire i tag deprecati con alternative HTML5 valide';
            }
        }
        
        // Consigli per l'accessibilità
        if ($this->results['accessibility']['score'] < 70) {
            if ($this->results['accessibility']['imgNoAltCount'] > 0) {
                $this->results['recommendations'][] = 'Aggiungere attributi alt a tutte le immagini';
            }
            
            if ($this->results['accessibility']['formNoLabelsCount'] > 0) {
                $this->results['recommendations'][] = 'Associare label a tutti gli elementi di form';
            }
            
            if ($this->results['accessibility']['ariaAttributesCount'] < 5) {
                $this->results['recommendations'][] = 'Migliorare l\'accessibilità aggiungendo attributi ARIA appropriati';
            }
        }
        
        // Consigli per la validità HTML
        if ($this->results['htmlValidity']['score'] < 70) {
            if ($this->results['htmlValidity']['errorCount'] > 0) {
                $this->results['recommendations'][] = 'Correggere gli errori di validazione HTML';
            }
            
            if (!empty($this->results['htmlValidity']['structuralIssues'])) {
                foreach ($this->results['htmlValidity']['structuralIssues'] as $issue) {
                    if ($issue['type'] === 'duplicate_ids') {
                        $this->results['recommendations'][] = 'Rimuovere gli ID duplicati nel documento';
                    } else if ($issue['type'] === 'improper_nesting') {
                        $this->results['recommendations'][] = 'Correggere la nidificazione impropria degli elementi';
                    }
                }
            }
        }
        
        // Consigli per le relazioni tra elementi
        if ($this->results['elementRelationships']['score'] < 70) {
            if (!$this->results['elementRelationships']['headingStructureValid']) {
                $this->results['recommendations'][] = 'Migliorare la gerarchia degli heading (evitare salti come da h1 a h3)';
            }
            
            if ($this->results['elementRelationships']['sectionsWithoutHeadings'] > 0) {
                $this->results['recommendations'][] = 'Aggiungere heading a tutte le sezioni';
            }
        }
        
        // Consigli per i dati strutturati
        if ($this->results['structuredData']['score'] < 50) {
            $this->results['recommendations'][] = 'Aggiungere dati strutturati (JSON-LD, Microdata o RDFa) per migliorare la comprensione del contenuto';
        }
        
        // Consigli per la responsiveness
        if ($this->results['responsiveness']['score'] < 70) {
            if (!$this->results['responsiveness']['hasViewport']) {
                $this->results['recommendations'][] = 'Aggiungere meta viewport per il corretto rendering mobile';
            }
            
            if (!$this->results['responsiveness']['hasMediaQueries']) {
                $this->results['recommendations'][] = 'Utilizzare media queries per adattare il layout a diverse dimensioni di schermo';
            }
            
            if ($this->results['responsiveness']['responsiveImagesCount'] === 0 && $this->dom->getElementsByTagName('img')->length > 0) {
                $this->results['recommendations'][] = 'Utilizzare immagini responsive con srcset o picture element';
            }
        }
    }
    
    /**
     * Implementa una strategia di fallback in caso di errore
     * 
     * @return bool True se il fallback ha avuto successo
     */
    protected function implementFallback() {
        // In caso di errore, crea un risultato base
        $this->results = [
            'domStructure' => ['score' => 50],
            'semanticHTML' => ['score' => 50],
            'accessibility' => ['score' => 50],
            'htmlValidity' => ['score' => 50],
            'elementRelationships' => ['score' => 50],
            'structuredData' => ['score' => 50],
            'responsiveness' => ['score' => 50],
            'totalScore' => 50,
            'error' => $this->errorMessage,
            'fallbackUsed' => true,
            'recommendations' => ['Impossibile analizzare il DOM a causa di un errore: ' . $this->errorMessage]
        ];
        
        return true;
    }
}