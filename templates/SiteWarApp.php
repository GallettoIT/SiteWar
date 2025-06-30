<?php
/**
 * SiteWarApp.php
 * 
 * Template principale dell'applicazione che fornisce la configurazione di base
 * e il collegamento tra frontend e backend.
 */

// Carica le configurazioni
$apiBaseUrl = '/server/api';
$apiEndpoints = [
    'validate' => '/validate',
    'analyze' => '/analyze',
    'progress' => '/progress',
    'report' => '/report'
];

// Genera la configurazione JavaScript per il frontend
$jsConfig = [
    'apiBaseUrl' => $apiBaseUrl,
    'endpoints' => $apiEndpoints,
    'debug' => DEBUG_MODE,
    'maxTimeout' => 25000 // timeout massimo per l'analisi (ms)
];
?>

<script type="text/javascript">
// Configurazione globale dell'applicazione
window.SiteWarConfig = <?php echo json_encode($jsConfig, JSON_PRETTY_PRINT); ?>;

// Qui potranno essere inserite ulteriori configurazioni specifiche per le API
</script>