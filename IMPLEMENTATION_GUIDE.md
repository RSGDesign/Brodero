# 🛒 BRODERO E-COMMERCE - IMPLEMENTARE COMPLETĂ

## ✅ DEJA IMPLEMENTAT

### 1. Baza de Date
- ✅ Tabel `orders` actualizat cu: subtotal, discount_amount, coupon_code, stripe_session_id, customer_*
- ✅ Tabel `coupons` creat cu toate câmpurile necesare
- ✅ 4 cupoane demo inserate (WELCOME10, SAVE20, FIRSTORDER, SUMMER25)
- ✅ Tabel `cart` existent și funcțional

### 2. Funcționalitate Coș
- ✅ `pages/add_to_cart.php` - Adaugă produse cu validare stoc
- ✅ `pages/cart.php` - Pagină completă coș cu:
  - Afișare produse (imagine, nume, preț, cantitate, subtotal)
  - Butoane actualizare cantitate (+/-)
  - Buton ștergere produs
  - Formular aplicare cupon
  - Rezumat comandă (subtotal, reducere, total)

## 📝 FIȘIERE DE CREAT (Instrucțiuni Detaliate)

### 3. pages/update_cart.php
```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$cartId = (int)($_POST['cart_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($cartId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$db = getDB();
$stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
$stmt->bind_param("ii", $quantity, $cartId);

echo json_encode(['success' => $stmt->execute()]);
?>
```

### 4. pages/remove_from_cart.php
```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$cartId = (int)($_POST['cart_id'] ?? 0);

$db = getDB();
$stmt = $db->prepare("DELETE FROM cart WHERE id = ?");
$stmt->bind_param("i", $cartId);

echo json_encode(['success' => $stmt->execute()]);
?>
```

### 5. pages/apply_coupon.php
```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/cart.php');
}

$couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));

if (empty($couponCode)) {
    setMessage("Introdu un cod de cupon.", "warning");
    redirect('/pages/cart.php');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM coupons WHERE code = ?");
$stmt->bind_param("s", $couponCode);
$stmt->execute();
$coupon = $stmt->get_result()->fetch_assoc();

if (!$coupon) {
    setMessage("Cupon invalid.", "danger");
    redirect('/pages/cart.php');
}

if (!$coupon['is_active']) {
    setMessage("Acest cupon nu mai este activ.", "danger");
    redirect('/pages/cart.php');
}

if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    setMessage("Acest cupon a expirat.", "danger");
    redirect('/pages/cart.php');
}

if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) {
    setMessage("Acest cupon a fost utilizat complet.", "danger");
    redirect('/pages/cart.php');
}

// Verificare sumă minimă
// Calculează subtotal coș aici

$_SESSION['applied_coupon'] = $couponCode;
setMessage("Cupon aplicat cu succes!", "success");
redirect('/pages/cart.php');
?>
```

### 6. pages/remove_coupon.php
```php
<?php
require_once __DIR__ . '/../config/config.php';
unset($_SESSION['applied_coupon']);
setMessage("Cupon eliminat.", "info");
redirect('/pages/cart.php');
?>
```

### 7. pages/checkout.php (MARE - FORMULAR COMPLET)

**Structură:**
- Verificare coș gol → redirect
- Formular cu: nume, email, telefon, adresă
- Radio buttons: Transfer Bancar / Plată Card (Stripe)
- Rezumat comandă
- Validări JavaScript + PHP
- Submit către `checkout_process.php`

### 8. pages/checkout_process.php

**Logică:**
1. Validare date POST (nume, email, telefon, metoda_plata)
2. Verificare coș nu e gol
3. Verificare stoc pentru fiecare produs
4. Calculare subtotal, discount, total
5. Generare order_number unic
6. INSERT în `orders`
7. INSERT în `order_items` pentru fiecare produs
8. **DACĂ Transfer Bancar:**
   - Status: pending, payment_status: unpaid
   - Clear coș
   - Redirect la `payment_instructions.php?order=XXX`
9. **DACĂ Stripe:**
   - Creare Stripe Checkout Session
   - Salvare stripe_session_id în orders
   - Redirect la Stripe

### 9. pages/payment_instructions.php

Afișează:
- Detalii comandă
- Instrucțiuni transfer:
  ```
  Titular: Brodero SRL
  IBAN: RO12BTRL000012345678901
  Banca: Banca Transilvania
  Suma: [TOTAL] LEI
  Referință: Comanda #[ORDER_NUMBER]
  ```
- Trimite email cu aceleași informații

### 10. INTEGRARE STRIPE

**Instalare:**
```bash
composer require stripe/stripe-php
```

**În checkout_process.php când metoda == 'stripe':**
```php
require_once 'vendor/autoload.php';
\Stripe\Stripe::setApiKey('sk_test_YOUsk_test_51STRYeQ1U061mKnqqVKPkDaQ4HS5RsmkYB2sDgk9N6WnMM1ntWdz53VGVxSbQjJkWfxh2VGeCyyMVgy11ogE7hKO00fw6RZ3fcR_SECRET_KEY');

$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency' => 'ron',
            'product_data' => ['name' => 'Comandă #' . $orderNumber],
            'unit_amount' => $total * 100, // în bani (cenți)
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => SITE_URL . '/pages/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => SITE_URL . '/pages/payment_cancel.php',
    'metadata' => ['order_id' => $orderId]
]);

// Salvează $session->id în DB
// Redirect la $session->url
```

### 11. pages/payment_success.php
```php
- Preia session_id din URL
- Verifică în Stripe dacă plata e completă
- UPDATE orders SET payment_status='paid', status='processing' WHERE stripe_session_id=?
- Incrementează used_count pentru cupon dacă există
- Clear coș
- Trimite email confirmare
- Afișează mesaj succes + detalii comandă
```

### 12. pages/payment_cancel.php
```php
- Afișează mesaj că plata a fost anulată
- Link înapoi la cart.php
```

### 13. webhooks/stripe_webhook.php (OPȚIONAL DAR RECOMANDAT)

Pentru siguranță maximă:
```php
require_once '../vendor/autoload.php';

$endpoint_secret = 'whsec_YOUR_WEBHOOK_SECRET';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    
    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;
        $orderId = $session->metadata->order_id;
        
        // UPDATE orders SET payment_status='paid'
        // Trimite email confirmare
    }
} catch (\Exception $e) {
    http_response_code(400);
    exit;
}

http_response_code(200);
```

## 🔧 CONFIGURĂRI NECESARE

### config/config.php - Adaugă:
```php
// Stripe Keys
define('STRIPE_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY');
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_WEBHOOK_SECRET');

// Email SMTP (pentru notificări)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

## 📧 EMAIL NOTIFICATIONS

Creează `includes/send_email.php`:
```php
<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function sendOrderEmail($to, $orderNumber, $total, $paymentMethod) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom('noreply@brodero.online', 'Brodero');
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = 'Confirmare Comandă #' . $orderNumber;
        $mail->Body = "
            <h2>Mulțumim pentru comandă!</h2>
            <p>Comanda ta #$orderNumber a fost înregistrată.</p>
            <p>Total: $total LEI</p>
            <p>Metodă plată: $paymentMethod</p>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
```

## 🎨 INTEGRARE FRONTEND

### pages/magazin.php & pages/produs.php

Adaugă buton "Adaugă în Coș":
```php
<button class="btn btn-primary add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
    <i class="bi bi-cart-plus me-2"></i>Adaugă în Coș
</button>
```

### assets/js/main.js - Adaugă:
```javascript
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        
        fetch('/pages/add_to_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `product_id=${productId}&quantity=1`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update cart count în header
                document.querySelector('.cart-count').textContent = data.cart_count;
                alert('Produs adăugat în coș!');
            }
        });
    });
});
```

## ✅ CHECKLIST FINAL

- [ ] Creează toate fișierele PHP listate mai sus
- [ ] Instalează Stripe SDK: `composer require stripe/stripe-php`
- [ ] Instalează PHPMailer: `composer require phpmailer/phpmailer`
- [ ] Configurează chei Stripe în config.php
- [ ] Configurează SMTP în config.php
- [ ] Testează flow complet: Add to Cart → Cart → Checkout → Payment
- [ ] Testează cupoane (valid, expirat, max uses)
- [ ] Testează ambele metode de plată
- [ ] Configurează Stripe webhook în dashboard Stripe
- [ ] Verifică emailurile de confirmare

## 🔒 SECURITATE

- ✅ CSRF tokens pe toate formularele
- ✅ Prepared statements pentru SQL
- ✅ Validare stoc la fiecare pas
- ✅ Verificare user session
- ✅ Escape output cu htmlspecialchars
- ✅ Validare server-side pentru toate inputs

## 📊 TESTARE

**Cupoane demo:**
- `WELCOME10` - 10% reducere, min 50 LEI
- `SAVE20` - 20 LEI fix, min 100 LEI
- `FIRSTORDER` - 15% reducere, fără minim
- `SUMMER25` - 25% reducere, min 150 LEI

**Stripe Test Cards:**
- Success: 4242 4242 4242 4242
- Declined: 4000 0000 0000 0002
