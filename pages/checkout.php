<?php
/**
 * Checkout Page - Plată cu Stripe
 * Pagină finalizare comandă cu formular date client și plată prin Stripe
 */

$pageTitle = "Finalizare Comandă";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_referral.php';

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

// Obține credit disponibil dacă e logat
$availableCredit = 0;
$creditToUse = 0;

if ($userId) {
    $availableCredit = getUserCreditBalance($userId);
    
    // Verifică dacă utilizatorul vrea să folosească creditul
    if (isset($_POST['use_credit']) && $_POST['use_credit'] === '1') {
        $requestedCredit = floatval($_POST['credit_amount'] ?? 0);
        $creditToUse = min($requestedCredit, $availableCredit);
        $_SESSION['credit_to_use'] = $creditToUse;
    } elseif (isset($_SESSION['credit_to_use'])) {
        $creditToUse = min($_SESSION['credit_to_use'], $availableCredit);
    }
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

// Calculare total cu credit
$totalAfterDiscount = $subtotal - $discount;
$creditToUse = min($creditToUse, $totalAfterDiscount); // Creditul nu poate fi mai mare decât totalul
$total = $totalAfterDiscount - $creditToUse;

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

<?php
// Track begin_checkout event (GA4)
require_once __DIR__ . '/../includes/analytics.php';
trackBeginCheckout($total);
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
                                <!-- Nume Complet -->
                                <div class="col-12">
                                    <label for="customerName" class="form-label">Nume Complet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="customerName" name="customer_name" 
                                           value="<?php echo htmlspecialchars(trim($firstName . ' ' . $lastName)); ?>" 
                                           placeholder="Ex: Ion Popescu"
                                           required>
                                    <small class="text-muted">Prenume și nume de familie</small>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6">
                                    <label for="customerEmail" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="customerEmail" name="customer_email" 
                                           value="<?php echo htmlspecialchars($email); ?>" 
                                           placeholder="exemplu@email.com"
                                           required>
                                </div>

                                <!-- Telefon -->
                                <div class="col-md-6">
                                    <label for="customerPhone" class="form-label">Telefon <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="customerPhone" name="customer_phone" 
                                           value="<?php echo htmlspecialchars($phone); ?>" 
                                           placeholder="0712345678"
                                           required>
                                    <small class="text-muted">Minim 10 cifre</small>
                                </div>

                                <!-- Adresă Completă -->
                                <div class="col-12">
                                    <label for="shippingAddress" class="form-label">Adresă Completă de Livrare <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="shippingAddress" name="shipping_address" 
                                              rows="3" 
                                              placeholder="Strada, număr, bloc, scară, etaj, apartament, oraș, județ, cod poștal&#10;Ex: Str. Exemplu Nr. 10, Bl. A, Sc. 1, Et. 2, Ap. 5, București, Sector 1, 010101" 
                                              required></textarea>
                                    <small class="text-muted">Include oraș, județ și cod poștal</small>
                                </div>

                                <!-- Notițe Opționale -->
                                <div class="col-12">
                                    <label for="orderNotes" class="form-label">Notițe Comandă <small class="text-muted">(opțional)</small></label>
                                    <textarea class="form-control" id="orderNotes" name="notes" 
                                              rows="2" 
                                              placeholder="Informații suplimentare despre comandă..."></textarea>
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

                            <!-- Avertizare AdBlocker -->
                            <div id="adblocker-warning" class="alert alert-warning mt-3" style="display: none;">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Avertisment:</strong> Te rugăm să dezactivezi AdBlocker pentru acest site pentru a procesa plata cu cardul.
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
                                    ?> RON
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 pt-4 border-top">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <strong><?php echo number_format($subtotal, 2); ?> RON</strong>
                            </div>

                            <?php if ($discount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span><i class="bi bi-tag me-1"></i>Reducere Cupon:</span>
                                <strong>-<?php echo number_format($discount, 2); ?> RON</strong>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($userId && $availableCredit > 0): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">
                                        <i class="bi bi-wallet2 me-1"></i>Credit Disponibil:
                                    </span>
                                    <strong class="text-info"><?php echo number_format($availableCredit, 2); ?> RON</strong>
                                </div>
                                
                                <form method="POST" id="creditForm" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="useCreditCheckbox" 
                                               name="use_credit" value="1" 
                                               <?php echo ($creditToUse > 0) ? 'checked' : ''; ?>
                                               onchange="toggleCreditInput()">
                                        <label class="form-check-label" for="useCreditCheckbox">
                                            <small>Folosește creditul acumulat din referrals</small>
                                        </label>
                                    </div>
                                    
                                    <div id="creditAmountSection" style="<?php echo ($creditToUse > 0) ? '' : 'display:none;'; ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="number" 
                                                   class="form-control form-control-sm" 
                                                   id="creditAmount" 
                                                   name="credit_amount" 
                                                   min="0" 
                                                   max="<?php echo min($availableCredit, $totalAfterDiscount); ?>" 
                                                   step="0.01" 
                                                   value="<?php echo $creditToUse > 0 ? number_format($creditToUse, 2, '.', '') : ''; ?>"
                                                   placeholder="Sumă credit">
                                            <span class="input-group-text">RON</span>
                                            <button type="submit" class="btn btn-sm btn-outline-primary" name="apply_credit">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Maxim: <?php echo number_format(min($availableCredit, $totalAfterDiscount), 2); ?> RON</small>
                                    </div>
                                </form>
                            </div>
                            
                            <?php if ($creditToUse > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-info">
                                <span><i class="bi bi-wallet2 me-1"></i>Credit Folosit:</span>
                                <strong>-<?php echo number_format($creditToUse, 2); ?> RON</strong>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                                <h5 class="fw-bold mb-0">Total de Plată:</h5>
                                <h5 class="fw-bold mb-0 text-primary"><?php echo number_format($total, 2); ?> RON</h5>
                            </div>
                        </div>
                        
                        <script>
                        function toggleCreditInput() {
                            const checkbox = document.getElementById('useCreditCheckbox');
                            const section = document.getElementById('creditAmountSection');
                            const amountInput = document.getElementById('creditAmount');
                            
                            if (checkbox.checked) {
                                section.style.display = 'block';
                                // Setează automat maximul disponibil
                                if (!amountInput.value) {
                                    amountInput.value = amountInput.max;
                                }
                            } else {
                                section.style.display = 'none';
                                amountInput.value = '';
                                // Trimite formular pentru a reseta creditul
                                document.getElementById('creditForm').submit();
                            }
                        }
                        </script>
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
                try {
                    const fetchClientSecret = async () => {
                        const response = await fetch("<?php echo SITE_URL; ?>/ajax/process_payment.php", {
                            method: "POST",
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        });
                        
                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.error || 'Eroare la crearea sesiunii de plată');
                        }
                        
                        const data = await response.json();
                        console.log('Client Secret received:', data.clientSecret ? 'Yes' : 'No');
                        
                        if (!data.clientSecret) {
                            throw new Error('Client secret lipsește din răspuns');
                        }
                        
                        return data.clientSecret;
                    };

                    console.log('Initializing Stripe Embedded Checkout...');
                    checkout = await stripe.initEmbeddedCheckout({
                        fetchClientSecret,
                    });

                    console.log('Mounting checkout...');
                    checkout.mount('#checkout');
                    console.log('Checkout mounted successfully');
                    
                } catch (error) {
                    console.error('Stripe Checkout Error:', error);
                    
                    // Verifică dacă e eroare de AdBlocker
                    if (error.message && (error.message.includes('Failed to fetch') || error.message.includes('ERR_BLOCKED_BY_CLIENT'))) {
                        document.getElementById('adblocker-warning').style.display = 'block';
                        alert('AdBlocker detectat! Te rugăm să dezactivezi AdBlocker pentru acest site și să încerci din nou.');
                    } else {
                        alert('Eroare la inițializarea plății: ' + error.message);
                    }
                    
                    stripeSection.style.display = 'none';
                    document.getElementById('payment_bank').checked = true;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Finalizează Comanda';
                    return;
                }
            }
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-lock me-2"></i>Plătește <?php echo number_format($total, 2); ?> RON';
        } else {
            stripeSection.style.display = 'none';
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Finalizează Comanda';
        }
    });
});

const checkoutForm = document.getElementById('checkoutForm');

checkoutForm.addEventListener('submit', (e) => {
    e.preventDefault(); // Previne submit automat
    
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    
    if (!paymentMethod) {
        alert('Te rugăm să selectezi o metodă de plată.');
        return false;
    }

    // Validare câmpuri înainte de submit
    const customerName = document.getElementById('customerName').value.trim();
    const customerEmail = document.getElementById('customerEmail').value.trim();
    const customerPhone = document.getElementById('customerPhone').value.trim();
    const shippingAddress = document.getElementById('shippingAddress').value.trim();

    if (!customerName || !customerEmail || !customerPhone || !shippingAddress) {
        alert('Te rugăm să completezi toate câmpurile obligatorii marcate cu *');
        return false;
    }

    // Validare email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(customerEmail)) {
        alert('Te rugăm să introduci o adresă de email validă.');
        document.getElementById('customerEmail').focus();
        return false;
    }

    // Validare telefon (minimum 10 cifre)
    const phoneDigits = customerPhone.replace(/\D/g, '');
    if (phoneDigits.length < 10) {
        alert('Numărul de telefon trebuie să conțină cel puțin 10 cifre.');
        document.getElementById('customerPhone').focus();
        return false;
    }

    if (paymentMethod.value === 'bank_transfer') {
        // Trimite formular clasic pentru transfer bancar
        checkoutForm.action = '<?php echo SITE_URL; ?>/pages/checkout_process.php';
        checkoutForm.method = 'POST';
        
        // Dezactivează butonul pentru a preveni submit-uri multiple
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Se procesează...';
        
        checkoutForm.submit();
    } else {
        // Pentru Stripe, Embedded Checkout gestionează automat plata
        // Nu facem nimic, checkout-ul Stripe se ocupă
        console.log('Stripe checkout - handled by embedded form');
    }
});

// Salvare date în localStorage pentru persistență la erori
const formInputs = ['customerName', 'customerEmail', 'customerPhone', 'shippingAddress', 'orderNotes'];
formInputs.forEach(inputId => {
    const input = document.getElementById(inputId);
    if (input && input.value === '') {
        // Restaurează valori salvate anterior (la eroare)
        const savedValue = localStorage.getItem('checkout_' + inputId);
        if (savedValue) {
            input.value = savedValue;
        }
    }
    
    // Salvează la modificare
    if (input) {
        input.addEventListener('input', function() {
            localStorage.setItem('checkout_' + inputId, this.value);
        });
    }
});

// Curăță localStorage după submit reușit (detection la întoarcere success)
if (window.location.search.includes('success')) {
    formInputs.forEach(inputId => {
        localStorage.removeItem('checkout_' + inputId);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
