<?php
/**
 * ResultViewer.php
 * 
 * Template per la visualizzazione dettagliata dei risultati di analisi.
 * Questo template viene utilizzato quando si vuole visualizzare un report
 * esistente senza eseguire una nuova analisi.
 * 
 * @param string $analysisId ID univoco dell'analisi
 */

$analysisId = $analysisId ?? $_GET['id'] ?? null;

if (!$analysisId) {
    // Carica template di errore
    loadTemplate('ErrorView', [
        'title' => 'Analisi non trovata',
        'message' => 'ID analisi non specificato',
        'code' => 404
    ]);
    exit;
}

// Prepara parametri per la chiamata API
$params = [
    'analysisId' => $analysisId
];

// In un ambiente reale, qui verrebbe chiamata l'API per ottenere i risultati
// Per ora, generiamo un URL per la chiamata API lato client
$apiUrl = '/server/api/report?' . http_build_query($params);
?>

<div class="report-viewer-container" data-analysis-id="<?php echo htmlspecialchars($analysisId); ?>">
    <!-- Loader in attesa del caricamento dei risultati -->
    <div id="report-loader" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Caricamento in corso...</span>
        </div>
        <p class="mt-3">Caricamento risultati...</p>
    </div>
    
    <!-- Contenitore per i risultati dell'analisi (verrà popolato via JavaScript) -->
    <div id="report-container" class="d-none">
        <!-- I risultati verranno inseriti qui dal JavaScript -->
    </div>
    
    <!-- Contenitore per messaggi di errore -->
    <div id="report-error" class="d-none">
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <span id="report-error-message">Errore durante il caricamento dei risultati</span>
        </div>
    </div>
</div>

<script type="text/javascript">
(function() {
    // Quando il documento è pronto
    document.addEventListener('DOMContentLoaded', function() {
        // Elementi del DOM
        const reportLoader = document.getElementById('report-loader');
        const reportContainer = document.getElementById('report-container');
        const reportError = document.getElementById('report-error');
        const reportErrorMessage = document.getElementById('report-error-message');
        
        // ID dell'analisi
        const analysisId = document.querySelector('.report-viewer-container').dataset.analysisId;
        
        // Funzione per mostrare un errore
        function showError(message) {
            reportLoader.classList.add('d-none');
            reportError.classList.remove('d-none');
            reportErrorMessage.textContent = message;
        }
        
        // Cerca di ottenere i risultati dell'analisi
        if (SiteWar && SiteWar.APIConnector) {
            // Usa l'API connector esistente per caricare i risultati
            fetch('<?php echo $apiUrl; ?>')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Errore durante il recupero dei risultati (HTTP ' + response.status + ')');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        // Nascondi il loader
                        reportLoader.classList.add('d-none');
                        
                        // Mostra il container dei risultati
                        reportContainer.classList.remove('d-none');
                        
                        // Passa i risultati al modulo ResultsUI per visualizzarli
                        if (SiteWar.ResultsUI) {
                            SiteWar.ResultsUI.show(data.data);
                        } else {
                            // Fallback: mostra i risultati in formato JSON
                            reportContainer.innerHTML = '<pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                        }
                    } else {
                        showError(data.message || 'Risultati non disponibili');
                    }
                })
                .catch(error => {
                    showError(error.message);
                    console.error('Error loading report:', error);
                });
        } else {
            showError('Modulo API non disponibile');
        }
    });
})();
</script>