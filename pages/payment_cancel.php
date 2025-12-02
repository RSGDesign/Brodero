<?php
/**
 * Payment Cancel Page
 * Plată anulată de utilizator
 */

$pageTitle = "Plată Anulată";
require_once __DIR__ . '/../includes/header.php';

$orderNumber = $_GET['order'] ?? '';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark text-center">
                    <h4 class="mb-0">
                        <i class="bi bi-x-circle-fill me-2"></i>
                        Plată Anulată
                    </h4>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 5rem;"></i>
                    </div>

                    <h5 class="mb-3">Plata a fost anulată</h5>
                    
                    <p class="text-muted">
                        Ai anulat procesul de plată. 
                        <?php if ($orderNumber): ?>
                            Comanda <strong>#<?php echo htmlspecialchars($orderNumber); ?></strong> 
                            a fost salvată ca <span class="badge bg-warning">Neplatită</span>.
                        <?php endif; ?>
                    </p>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Poți încerca din nou oricând dorești.
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <a href="/pages/cart.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-cart-fill me-2"></i>Înapoi la Coș
                        </a>
                        <a href="/" class="btn btn-outline-secondary">
                            <i class="bi bi-house-fill me-2"></i>Înapoi la Magazin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
