<?php
/**
 * Checkout Page - Plată cu Stripe
 * Pagină finalizare comandă cu formular date client și plată prin Stripe
 */

$pageTitle = "Finalizare Comandă";
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
$stmt->close();

// Redirect dacă coșul este gol
if (empty($cartItems)) {
    setMessage("Coșul tău este gol!", "warning");
    redirect('/pages/cart.php');
}

// Calcul subtotal
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
    $subtotal += $price;
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
$firstName = '';
$lastName = '';
$email = '';
$phone = '';

if ($userId) {
    $stmt = $db->prepare("SELECT username, email, phone, first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($userData) {
        $firstName = $userData['first_name'] ?? '';
        $lastName = $userData['last_name'] ?? '';
        $email = $userData['email'] ?? '';
        $phone = $userData['phone'] ?? '';
    }
}
?>

<!-- Page Header -->
<section class="bg-light py-4 border-bottom">
    <div class="container">
        <h1 class="h2 fw-bold text-dark"><i class="bi bi-credit-card me-2"></i>Finalizare Comandă</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Acasă</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/pages/cart.php">Coș</a></li>
                <li class="breadcrumb-item active">Checkout</li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Formular Checkout -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-4">Informații de Livrare</h3>

                        <form id="checkoutForm" class="checkout-form">
                            <div class="row g-3">
                                <!-- Prenume -->
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">Prenume <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstName" name="first_name" 
                                           value="<?php echo htmlspecialchars($firstName); ?>" required>
                                </div>

                                <!-- Nume -->
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Nume <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastName" name="last_name" 
                                           value="<?php echo htmlspecialchars($lastName); ?>" required>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>

                                <!-- Telefon -->
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Telefon <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($phone); ?>" required>
                                </div>

                                <!-- Adresă -->
                                <div class="col-12">
                                    <label for="address" class="form-label">Adresă <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="address" name="address" rows="2" placeholder="Strada, număr, bloc, scară, etaj, apartament" required></textarea>
                                </div>

                                <!-- Oraș -->
                                <div class="col-md-6">
                                    <label for="city" class="form-label">Oraș <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>

                                <!-- Cod Poștal -->
                                <div class="col-md-6">
                                    <label for="zipCode" class="form-label">Cod Poștal <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="zipCode" name="zip_code" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h3 class="fw-bold mb-4">Metodă de Plată</h3>

                            <div class="payment-methods mb-4">
                                <!-- Transfer Bancar -->
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_bank" value="bank_transfer" checked>
                                    <label class="form-check-label" for="payment_bank">
                                        <i class="bi bi-bank me-2"></i><strong>Transfer Bancar</strong>
                                        <small class="text-muted d-block">Vei primi instrucțiunile de plată pe email</small>
                                    </label>
                                </div>

                                <!-- Stripe Card Payment -->
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_stripe" value="stripe">
                                    <label class="form-check-label" for="payment_stripe">
                                        <i class="bi bi-credit-card me-2"></i><strong>Card Bancar (Stripe)</strong>
                                        <small class="text-muted d-block">Plată securizată prin Stripe</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Stripe Embedded Checkout (ascuns inițial) -->
                            <div id="stripe-payment-section" style="display: none;">
                                <div id="checkout" class="mb-4"></div>
                            </div>

                            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" id="cart_id" name="cart_id" value="1">

                            <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                                <i class="bi bi-check-circle me-2"></i>Finalizează Comanda
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sumar Comandă -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Sumar Comandă</h4>

                        <div class="order-items">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">Cantitate: <?php echo $item['quantity']; ?></small>
                                </div>
                                <div class="text-end">
                                    <?php
                                    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
                                    echo number_format($price, 2);
                                    ?> LEI
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 pt-4 border-top">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <strong><?php echo number_format($subtotal, 2); ?> LEI</strong>
                            </div>

                            <?php if ($discount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Reducere:</span>
                                <strong>-<?php echo number_format($discount, 2); ?> LEI</strong>
                            </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                                <h5 class="fw-bold mb-0">Total:</h5>
                                <h5 class="fw-bold mb-0 text-primary"><?php echo number_format($total, 2); ?> LEI</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stripe Scripts -->
<script src="https://js.stripe.com/v3/"></script>
<script>
// Inițializare Stripe
const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
let checkout = null;

// Gestionare schimbare metodă plată
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', async function() {
        const stripeSection = document.getElementById('stripe-payment-section');
        const submitBtn = document.getElementById('submitBtn');
        
        if (this.value === 'stripe') {
            stripeSection.style.display = 'block';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Încărcare formular...';
            
            // Inițializare Stripe Embedded Checkout dacă nu există
            if (!checkout) {
                const fetchClientSecret = async () => {
                    const response = await fetch("<?php echo SITE_URL; ?>/ajax/process_payment.php", {
                        method: "POST",
                    });
                    const { clientSecret } = await response.json();
                    return clientSecret;
                };

                checkout = await stripe.initEmbeddedCheckout({
                    fetchClientSecret,
                });

                checkout.mount('#checkout');
            }
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-lock me-2"></i>Plătește <?php echo number_format($total, 2); ?> LEI';
        } else {
            stripeSection.style.display = 'none';
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Finalizează Comanda';
        }
    });
});

const checkoutForm = document.getElementById('checkoutForm');

checkoutForm.addEventListener('submit', (e) => {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

    if (paymentMethod === 'bank_transfer') {
        // Trimite formular clasic pentru transfer bancar
        checkoutForm.action = '<?php echo SITE_URL; ?>/pages/checkout_process.php';
        checkoutForm.method = 'POST';
        checkoutForm.submit();
    } else {
        // Pentru Stripe, Embedded Checkout gestionează automat plata
        e.preventDefault();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
