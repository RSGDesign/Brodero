<?php
/**
 * Checkout Page
 * Pagină finalizare comandă cu formular date client și selectare metodă plată
 */

$pageTitle = "Finalizare Comandă";
require_once __DIR__ . '/../includes/header.php';

// Asigură funcțiile de mesaje există (fallback minimal)
if (!function_exists('hasMessage')) {
    function hasMessage() {
        return !empty($_SESSION['flash_message']);
    }
}
if (!function_exists('displayMessage')) {
    function displayMessage() {
        if (!empty($_SESSION['flash_message'])) {
            $msg = $_SESSION['flash_message'];
            echo '<div class="alert alert-' . htmlspecialchars($msg['type'] ?? 'info') . ' alert-dismissible fade show">'
                . htmlspecialchars($msg['text'] ?? '') .
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' .
                '</div>';
            unset($_SESSION['flash_message']);
        }
    }
}
if (!function_exists('setMessage')) {
    function setMessage($text, $type = 'info') {
        $_SESSION['flash_message'] = ['text' => $text, 'type' => $type];
    }
}

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$db = getDB();
$userId = isLoggedIn() ? $_SESSION['user_id'] : null;
$sessionId = $_SESSION['session_id'];

// Obține produse din coș
if ($userId) {
    $stmt = $db->prepare("\n        SELECT c.id as cart_id, c.quantity, p.id, p.name, p.slug, p.price, p.sale_price, p.image\n        FROM cart c\n        JOIN products p ON c.product_id = p.id\n        WHERE c.user_id = ?\n    ");
    $stmt->bind_param("i", $userId);
} else {
    $stmt = $db->prepare("\n        SELECT c.id as cart_id, c.quantity, p.id, p.name, p.slug, p.price, p.sale_price, p.image\n        FROM cart c\n        JOIN products p ON c.product_id = p.id\n        WHERE c.session_id = ?\n    ");
    $stmt->bind_param("s", $sessionId);
}

$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Verificare coș gol
if (empty($cartItems)) {
    setMessage("Coșul tău este gol.", "warning");
    redirect('/pages/cart.php');
}

// Produse digitale: fără verificări stoc/cantitate

// Calcul subtotal
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
    $subtotal += $price; // cantitate implicită 1
}

// Verificare cupon aplicat
$discount = 0;
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;

if ($appliedCoupon) {
    if ($appliedCoupon['discount_type'] === 'percent') {
        $discount = ($subtotal * $appliedCoupon['discount_value']) / 100;
    } else {
        $discount = $appliedCoupon['discount_value'];
    }
}

$total = $subtotal - $discount;

// Preia date utilizator dacă e logat
$userData = [];
if ($userId) {
    $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-fill me-2"></i>Detalii Client</h5>
                </div>
                <div class="card-body">
                    <?php if (hasMessage()): ?>
                        <?php displayMessage(); ?>
                    <?php endif; ?>

                    <form action="<?php echo SITE_URL; ?>/pages/checkout_process.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="customer_name" class="form-label">Nume Complet *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                       value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Introdu numele complet.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="customer_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                       value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Introdu o adresă de email validă.
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="customer_phone" class="form-label">Telefon *</label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                       pattern="[0-9]{10}" placeholder="07XXXXXXXX" required>
                                <div class="invalid-feedback">
                                    Introdu un număr de telefon valid (10 cifre).
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Adresă Livrare *</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                      rows="3" placeholder="Strada, număr, bloc, scară, etaj, apartament, oraș, județ, cod poștal" required></textarea>
                            <div class="invalid-feedback">
                                Introdu adresa completă de livrare.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Metodă Plată *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_bank" value="bank_transfer" checked>
                                <label class="form-check-label" for="payment_bank">
                                    <i class="bi bi-bank me-2"></i>Transfer Bancar
                                    <small class="text-muted d-block">Vei primi instrucțiunile de plată pe email</small>
                                </label>
                            </div>
                            <?php 
                            // Afișează opțiunea Stripe doar dacă SDK-ul e instalat și configurat
                            if (file_exists(__DIR__ . '/../vendor/autoload.php') && defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY)): 
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_card" value="stripe">
                                <label class="form-check-label" for="payment_card">
                                    <i class="bi bi-credit-card me-2"></i>Plată Online cu Card (Stripe)
                                    <small class="text-muted d-block">Plată securizată prin Stripe</small>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notițe Comandă (opțional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Instrucțiuni speciale pentru livrare..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-check-circle me-2"></i>Finalizează Comanda
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 100px;">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-cart-check me-2"></i>Rezumat Comandă</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-3">Produse (<?php echo count($cartItems); ?>)</h6>
                        <?php foreach ($cartItems as $item): ?>
                            <?php
                            $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
                            $itemTotal = $price * $item['quantity'];
                            ?>
                            <div class="d-flex justify-content-between mb-2">
                                <small>
                                    <?php echo htmlspecialchars($item['name']); ?> 
                                    <span class="text-muted">x<?php echo $item['quantity']; ?></span>
                                </small>
                                <small class="fw-bold"><?php echo number_format($itemTotal, 2); ?> LEI</small>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong><?php echo number_format($subtotal, 2); ?> LEI</strong>
                    </div>

                    <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between text-success mb-2">
                            <span>
                                Reducere (<?php echo htmlspecialchars($appliedCoupon['code']); ?>):
                            </span>
                            <strong>-<?php echo number_format($discount, 2); ?> LEI</strong>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <h5>Total:</h5>
                        <h5 class="text-primary"><?php echo number_format($total, 2); ?> LEI</h5>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Produsele digitale vor fi trimise pe email după confirmarea plății.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Phone validation
document.getElementById('customer_phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 10);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
