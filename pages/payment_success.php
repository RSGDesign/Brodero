<?php
/**
 * Payment Success Page
 * Confirmare plată reușită prin Stripe
 */

$pageTitle = "Plată Reușită";

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions_referral.php';

$sessionId = $_GET['session_id'] ?? '';
$orderNumber = $_GET['order'] ?? '';

if (empty($sessionId) && empty($orderNumber)) {
    setMessage("Sesiune invalidă.", "danger");
    redirect('/');
}

$db = getDB();
$order = null;

// Cazul 1: Plată prin Stripe
if (!empty($sessionId)) {
    // Verificăm dacă avem Stripe SDK
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        if (defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY)) {
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

            try {
                // Verifică sesiunea Stripe
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                
                if ($session->payment_status !== 'paid') {
                    setMessage("Plata nu a fost confirmată.", "warning");
                    redirect('/pages/cart.php');
                }
                
                // Actualizare status comandă
                $stmt = $db->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid', status = 'processing', updated_at = NOW()
                    WHERE stripe_session_id = ? AND payment_status = 'unpaid'
                ");
                $stmt->bind_param("s", $sessionId);
                $stmt->execute();
                
                // Preia comanda
                $stmt = $db->prepare("SELECT id FROM orders WHERE stripe_session_id = ?");
                $stmt->bind_param("s", $sessionId);
                $stmt->execute();
                $order = $stmt->get_result()->fetch_assoc();
                
                // ═══════════════════════════════════════════════════════════════════════════
                // REFERRAL COMMISSION - Acordă comision pentru comandă plătită
                // ═══════════════════════════════════════════════════════════════════════════
                if ($order && $order['id']) {
                    calculateAndAwardCommission($order['id']);
                }
                
            } catch (\Stripe\Exception\ApiErrorException $e) {
                error_log("Stripe Error: " . $e->getMessage());
                setMessage("Eroare la verificarea plății.", "danger");
                redirect('/');
            }
        }
    }
} 
// Cazul 2: Comandă directă (ex: total 0 sau redirect manual)
elseif (!empty($orderNumber)) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->bind_param("s", $orderNumber);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}

if (!$order) {
    setMessage("Comanda nu a fost găsită.", "danger");
    redirect('/');
}

// Include header DUPĂ toate validările
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white text-center">
                    <h3 class="mb-0">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Plată Confirmată!
                    </h3>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>

                    <h4 class="mb-3">Mulțumim pentru comandă!</h4>
                    
                    <div class="alert alert-success">
                        <p class="mb-0">
                            Comanda ta <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong> 
                            a fost plătită cu succes.
                        </p>
                    </div>

                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="text-start">Număr Comandă:</td>
                                    <td class="text-end fw-bold"><?php echo htmlspecialchars($order['order_number'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Email:</td>
                                    <td class="text-end"><?php echo htmlspecialchars($order['customer_email'] ?? '-'); ?></td>
                                </tr>
                                <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                <tr>
                                    <td class="text-start">Subtotal:</td>
                                    <td class="text-end"><?php echo number_format($order['subtotal'] ?? $order['total_amount'], 2); ?> RON</td>
                                </tr>
                                <tr class="text-success">
                                    <td class="text-start">Reducere:</td>
                                    <td class="text-end">-<?php echo number_format($order['discount_amount'], 2); ?> RON</td>
                                </tr>
                                <?php endif; ?>
                                <tr class="table-active">
                                    <td class="text-start fw-bold">Total Plătit:</td>
                                    <td class="text-end fw-bold text-primary fs-5">
                                        <?php echo number_format($order['total_amount'], 2); ?> RON
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-envelope-fill me-2"></i>
                        Un email de confirmare cu link-urile de download a fost trimis la 
                        <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="/" class="btn btn-primary btn-lg">
                            <i class="bi bi-house-fill me-2"></i>Înapoi la Magazin
                        </a>
                        <?php if (isLoggedIn()): ?>
                        <a href="/pages/cont.php?tab=comenzi" class="btn btn-outline-secondary">
                            <i class="bi bi-list-ul me-2"></i>Vezi Comenzile Mele
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
