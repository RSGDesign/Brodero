<?php
/**
 * Shopping Cart Page
 * Pagină coș de cumpărături cu actualizare cantități, ștergere, aplicare cupoane
 */

$pageTitle = "Coșul Meu";
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$db = getDB();
$userId = isLoggedIn() ? $_SESSION['user_id'] : null;
$sessionId = $_SESSION['session_id'];

// Obține produse din coș
if ($userId) {
    $stmt = $db->prepare("
        SELECT c.id as cart_id, c.quantity, p.id, p.name, p.slug, p.price, p.sale_price, p.image
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
} else {
    $stmt = $db->prepare("
        SELECT c.id as cart_id, c.quantity, p.id, p.name, p.slug, p.price, p.sale_price, p.image
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.session_id = ?
    ");
    $stmt->bind_param("s", $sessionId);
}

$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcul subtotal
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = $item['sale_price'] ?? $item['price'];
    // Produse digitale: cantitate implicită 1
    $subtotal += $price;
}

// Verificare cupon aplicat
$discount = 0;
$couponCode = null;

if (isset($_SESSION['applied_coupon'])) {
    // applied_coupon poate fi string sau array
    $appliedCoupon = $_SESSION['applied_coupon'];
    $couponCode = is_array($appliedCoupon) ? ($appliedCoupon['code'] ?? null) : $appliedCoupon;
}

if ($couponCode) {
    $couponStmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $couponStmt->bind_param("s", $couponCode);
    $couponStmt->execute();
    $coupon = $couponStmt->get_result()->fetch_assoc();
    
    if ($coupon) {
        if ($coupon['discount_type'] === 'percent') {
            $discount = ($subtotal * $coupon['discount_value']) / 100;
        } else {
            $discount = $coupon['discount_value'];
        }
    }
}

$total = $subtotal - $discount;
?>

<section class="bg-light py-4 border-bottom">
    <div class="container">
        <h1 class="h2 fw-bold text-dark"><i class="bi bi-cart3 me-2"></i>Coșul Meu</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Acasă</a></li>
                <li class="breadcrumb-item active">Coș</li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (empty($cartItems)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <h3 class="mt-4">Coșul tău este gol</h3>
            <p class="text-muted">Adaugă produse pentru a continua cumpărăturile</p>
            <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="btn btn-primary mt-3">
                <i class="bi bi-shop me-2"></i>Mergi la Magazin
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Produse (<?php echo count($cartItems); ?>)</h5>
                        
                        <?php foreach ($cartItems as $item): ?>
                        <?php $price = $item['sale_price'] ?? $item['price']; ?>
                        <div class="row g-3 align-items-center mb-3 pb-3 border-bottom" data-cart-id="<?php echo $item['cart_id']; ?>">
                            <div class="col-3 col-md-2">
                                <img src="<?php echo SITE_URL . '/uploads/' . ($item['image'] ?? 'placeholder.jpg'); ?>" 
                                     class="img-fluid rounded" alt="">
                            </div>
                            <div class="col-7 col-md-5">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted d-block"><?php echo number_format($price, 2); ?> RON</small>
                            </div>
                            <div class="col-12 col-md-5 d-flex align-items-center justify-content-between">
                                <div class="me-3">
                                    <span class="badge bg-secondary">x1</span>
                                </div>
                                <div class="text-end flex-grow-1">
                                    <strong><?php echo number_format($price, 2); ?> RON</strong>
                                </div>
                                <div class="ms-3 text-end">
                                    <button class="btn btn-sm btn-outline-danger remove-item" title="Șterge">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Cod Cupon</h5>
                        <form method="POST" action="apply_coupon.php" id="couponForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="input-group mb-2">
                                <input type="text" name="coupon_code" class="form-control" placeholder="Introdu codul" 
                                       value="<?php echo htmlspecialchars($couponCode ?? ''); ?>">
                                <button type="submit" class="btn btn-primary">Aplică</button>
                            </div>
                            <?php if ($couponCode): ?>
                            <small class="text-success"><i class="bi bi-check-circle me-1"></i>Cupon aplicat: <?php echo $couponCode; ?></small>
                            <a href="<?php echo SITE_URL; ?>/pages/remove_coupon.php" class="btn btn-link btn-sm p-0 text-danger">Elimină</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Rezumat Comandă</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <strong><?php echo number_format($subtotal, 2); ?> RON</strong>
                        </div>
                        <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Reducere:</span>
                            <strong>-<?php echo number_format($discount, 2); ?> RON</strong>
                        </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="h5 mb-0">Total:</span>
                            <strong class="h5 mb-0 text-primary"><?php echo number_format($total, 2); ?> RON</strong>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="btn btn-primary w-100">
                            <i class="bi bi-credit-card me-2"></i>Finalizează Comanda
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Remove item from cart (digital products: no quantity updates)
document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Sigur vrei să ștergi acest produs?')) {
            const row = this.closest('[data-cart-id]');
            removeFromCart(row.dataset.cartId);
        }
    });
});

function removeFromCart(cartId) {
    fetch('remove_from_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cart_id=${cartId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
