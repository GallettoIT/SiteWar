<?php
/**
 * ErrorView.php
 * 
 * Template per la visualizzazione degli errori dell'applicazione.
 * 
 * @param string $title Titolo dell'errore
 * @param string $message Messaggio di errore
 * @param int $code Codice di errore (opzionale)
 * @param bool $showBackButton Mostra il pulsante per tornare indietro (default: true)
 */

$title = $title ?? 'Errore';
$message = $message ?? 'Si è verificato un errore imprevisto.';
$code = $code ?? 500;
$showBackButton = $showBackButton ?? true;
?>

<div class="error-container py-5">
    <div class="text-center">
        <div class="error-icon mb-4">
            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
        </div>
        
        <h2 class="error-title mb-3"><?php echo htmlspecialchars($title); ?></h2>
        
        <?php if ($code): ?>
        <div class="error-code mb-3">
            <span class="badge bg-secondary"><?php echo htmlspecialchars($code); ?></span>
        </div>
        <?php endif; ?>
        
        <p class="error-message lead mb-4">
            <?php echo htmlspecialchars($message); ?>
        </p>
        
        <?php if ($showBackButton): ?>
        <div class="error-actions">
            <button onclick="history.back()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Torna indietro
            </button>
            
            <a href="<?php echo htmlspecialchars(BASE_URL ?? '/'); ?>" class="btn btn-primary ms-2">
                <i class="bi bi-house"></i> Homepage
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>