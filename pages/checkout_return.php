<?php
/**
 * Checkout Return Page - Stripe Embedded Checkout
 * Verifică status-ul sesiunii de checkout și confirmă comanda
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions_orders.php';
require_once __DIR__ . '/../includes/functions_referral.php';

// Încarcă Stripe SDK
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    die('Stripe SDK nu este instalat');
}

$pageTitle = "Confirmare Plată";
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);

// Obține session_id din URL
$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    // CRITICAL: Save session before redirect
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Location: ' . SITE_URL . '/pages/checkout.php');
    exit;
}

try {
    // Retrieve Checkout Session
    $session = $stripe->checkout->sessions->retrieve($sessionId);

    if ($session->status === 'complete') {
        // Plată reușită - Creare comandă
        $userId = $_SESSION['user_id'] ?? null;
        $sessionCartId = $_SESSION['session_id'] ?? null;

        // Obține iteme din coș
        if ($userId) {
            $stmt = $db->prepare("
                SELECT c.id as cart_id, c.quantity, c.product_id, p.name, p.price, p.sale_price
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $db->prepare("
                SELECT c.id as cart_id, c.quantity, c.product_id, p.name, p.price, p.sale_price
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.session_id = ?
            ");
            $stmt->bind_param("s", $sessionCartId);
        }

        $stmt->execute();
        $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Calcul total
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $price = $item['sale_price'] ?? $item['price'];
            $totalAmount += $price;
        }

        // Creare comandă
        $orderNumber = 'ORD-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
        $status = 'completed';
        $paymentStatus = 'paid';
        $paymentMethod = 'stripe';

        $stmt = $db->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, status, payment_status, payment_method, stripe_payment_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param("issssss", $userId, $orderNumber, $totalAmount, $status, $paymentStatus, $paymentMethod, $sessionId);

        if (!$stmt->execute()) {
            throw new Exception('Eroare la crearea comenzii');
        }

        $orderId = $stmt->insert_id;
        $stmt->close();

        // Adăugare iteme în comandă
        foreach ($cartItems as $item) {
            $price = $item['sale_price'] ?? $item['price'];
            $stmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $price);
            $stmt->execute();
            $stmt->close();
        }

        // ✅ ACTIVARE DESCĂRCĂRI - Plată Stripe confirmată
        enableOrderDownloads($orderId);
        
        // ✅ APLICARE CREDIT DIN REFERRALS (dacă a fost folosit)
        if ($userId && isset($_SESSION['credit_to_use']) && $_SESSION['credit_to_use'] > 0) {
            $creditUsed = floatval($_SESSION['credit_to_use']);
            $result = applyCreditToOrder($userId, $orderId, $creditUsed);
            
            if ($result['success']) {
                error_log("Credit aplicat cu succes: {$creditUsed} RON pentru comanda #{$orderId}");
            } else {
                error_log("Eroare la aplicarea creditului: " . $result['message']);
            }
            
            // Curățare credit din sesiune
            unset($_SESSION['credit_to_use']);
        }

        // Curățare coș
        if ($userId) {
            $db->query("DELETE FROM cart WHERE user_id = $userId");
        } else {
            $db->query("DELETE FROM cart WHERE session_id = '$sessionCartId'");
        }

        // Curățare cupon aplicat
        unset($_SESSION['applied_coupon']);
        ?>

        <!-- Confirmare Succes -->
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <div class="card border-0 shadow-sm p-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle text-success" style="font-size: 5rem;"></i>
                        </div>
                        <h1 class="fw-bold mb-3">Plată Confirmată!</h1>
                        <p class="text-muted mb-4">Comanda ta a fost procesată cu succes.</p>
                        
                        <div class="bg-light p-4 rounded mb-4">
                            <h5 class="fw-bold mb-3">Detalii Comandă</h5>
                            <p class="mb-2"><strong>Număr Comandă:</strong> <?php echo $orderNumber; ?></p>
                            <p class="mb-2"><strong>Total:</strong> <?php echo number_format($totalAmount, 2); ?> RON</p>
                            <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">Plătită</span></p>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="<?php echo SITE_URL; ?>/pages/cont.php?tab=fisiere" class="btn btn-success">
                                <i class="bi bi-download me-2"></i>Descarcă Fișierele
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/cont.php?tab=comenzi" class="btn btn-primary">
                                <i class="bi bi-list-ul me-2"></i>Vezi Comenzile Mele
                            </a>
                            <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-house me-2"></i>Înapoi la Magazin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
    } elseif ($session->status === 'open') {
        // Plată în curs
        ?>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <div class="card border-0 shadow-sm p-5">
                        <div class="mb-4">
                            <div class="spinner-border text-warning" style="width: 5rem; height: 5rem;" role="status">
                                <span class="visually-hidden">Procesare...</span>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-3">Plată în Procesare</h2>
                        <p class="text-muted">Sesiunea de plată este încă deschisă. Te rugăm să finalizezi plata.</p>
                        <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="btn btn-primary mt-3">
                            Înapoi la Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {
        // Plată eșuată
        ?>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <div class="card border-0 shadow-sm p-5">
                        <div class="mb-4">
                            <i class="bi bi-x-circle text-danger" style="font-size: 5rem;"></i>
                        </div>
                        <h2 class="fw-bold mb-3">Plată Eșuată</h2>
                        <p class="text-muted mb-4">Sesiunea de plată a expirat sau a fost anulată.</p>
                        <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="btn btn-primary">
                            Încearcă Din Nou
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

} catch (\Stripe\Exception\ApiErrorException $e) {
    ?>
    <div class="container my-5">
        <div class="alert alert-danger">
            <strong>Eroare Stripe:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
        </div>
        <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="btn btn-primary">Înapoi la Checkout</a>
    </div>
    <?php
} catch (Exception $e) {
    ?>
    <div class="container my-5">
        <div class="alert alert-danger">
            <strong>Eroare:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
        </div>
        <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="btn btn-primary">Înapoi la Checkout</a>
    </div>
    <?php
}

require_once __DIR__ . '/../includes/footer.php';
?>
