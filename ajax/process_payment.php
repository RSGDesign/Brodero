<?php
/**
 * Create Stripe Checkout Session
 * Crează sesiune Checkout pentru plată
 */

header('Content-Type: application/json');
error_log("=== STRIPE CHECKOUT SESSION START ===");

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    
    // Încarcă Stripe SDK
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        throw new Exception('Stripe SDK nu este instalat');
    }

    // Verificare metoda POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metoda nu este permisă');
    }

    // Verificare chei Stripe
    if (!defined('STRIPE_SECRET_KEY') || empty(STRIPE_SECRET_KEY)) {
        throw new Exception('Stripe nu este configurat corect');
    }

    // Inițializare Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Obțineți utilizatorul și coșul
    $db = getDB();
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = $_SESSION['session_id'] ?? null;

    error_log("User ID: " . ($userId ?? 'null') . ", Session ID: " . ($sessionId ?? 'null'));

    if (!$userId && !$sessionId) {
        throw new Exception('Sesiune invalidă. Te rugăm să adaugi produse în coș.');
    }

    // Obțineți comenzile din coș
    if ($userId) {
        $stmt = $db->prepare("
            SELECT c.id as cart_id, c.quantity, c.product_id, p.name, p.price, p.sale_price, p.image
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $db->prepare("
            SELECT c.id as cart_id, c.quantity, c.product_id, p.name, p.price, p.sale_price, p.image
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ?
        ");
        $stmt->bind_param("s", $sessionId);
    }

    $stmt->execute();
    $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    error_log("Cart items count: " . count($cartItems));

    if (empty($cartItems)) {
        throw new Exception('Coșul este gol. Adaugă produse înainte de plată.');
    }

    // Construire line items pentru Stripe
    $lineItems = [];
    foreach ($cartItems as $item) {
        $price = ($item['sale_price'] > 0 ? $item['sale_price'] : $item['price']);
        $priceInCents = (int)($price * 100); // Convertire în cenți
        
        error_log("Product: {$item['name']}, Price: {$price} RON, Cents: {$priceInCents}");
        
        $lineItems[] = [
            'price_data' => [
                'currency' => 'ron',
                'product_data' => [
                    'name' => $item['name'],
                ],
                'unit_amount' => $priceInCents,
            ],
            'quantity' => (int)$item['quantity'],
        ];
    }

    error_log("Creating Stripe session with " . count($lineItems) . " items");

    // Creare Checkout Session
    $checkoutSession = \Stripe\Checkout\Session::create([
        'line_items' => $lineItems,
        'mode' => 'payment',
        'ui_mode' => 'embedded',
        'return_url' => SITE_URL . '/pages/checkout_return.php?session_id={CHECKOUT_SESSION_ID}',
    ]);

    error_log("Stripe session created: " . $checkoutSession->id);
    error_log("=== STRIPE CHECKOUT SESSION SUCCESS ===");

    echo json_encode([
        'clientSecret' => $checkoutSession->client_secret
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("Stripe API Error: " . $e->getMessage());
    error_log("=== STRIPE CHECKOUT SESSION FAILED ===");
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Eroare Stripe: ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    error_log("=== STRIPE CHECKOUT SESSION FAILED ===");
    
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
