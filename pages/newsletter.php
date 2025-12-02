<?php
/**
 * Newsletter Subscription Handler
 * Gestionare abonări la newsletter cu CSRF protection și reactivare
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare metodă POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
    exit;
}

// Verificare CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    setMessage("Sesiune invalidă. Te rugăm să încerci din nou.", "danger");
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
    exit;
}

// Preluare și validare email
$email = cleanInput($_POST['email'] ?? '');

if (empty($email)) {
    setMessage("Te rugăm să introduci o adresă de email.", "warning");
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setMessage("Adresa de email introdusă este invalidă.", "danger");
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
    exit;
}

$db = getDB();

try {
    // Verificare dacă emailul există deja în baza de date
    $stmt = $db->prepare("SELECT id, is_active FROM newsletter WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Email-ul există deja
        $subscriber = $result->fetch_assoc();
        
        if ($subscriber['is_active'] == 1) {
            // Deja abonat activ
            setMessage("Acest email este deja abonat la newsletter. Îți mulțumim!", "info");
        } else {
            // Reactivare abonat dezabonat
            $updateStmt = $db->prepare("UPDATE newsletter SET is_active = 1, subscribed_at = NOW() WHERE email = ?");
            $updateStmt->bind_param("s", $email);
            
            if ($updateStmt->execute()) {
                setMessage("Bine ai revenit! Abonamentul tău a fost reactivat cu succes!", "success");
            } else {
                setMessage("A apărut o eroare la reactivarea abonamentului. Te rugăm să încerci din nou.", "danger");
            }
            $updateStmt->close();
        }
    } else {
        // Email nou - inserare în baza de date
        $insertStmt = $db->prepare("INSERT INTO newsletter (email, is_active, subscribed_at) VALUES (?, 1, NOW())");
        $insertStmt->bind_param("s", $email);
        
        if ($insertStmt->execute()) {
            setMessage("🎉 Te-ai abonat cu succes la newsletter! Îți mulțumim că faci parte din comunitatea Brodero!", "success");
            
            // Opțional: Trimite email de confirmare
            // $subject = "Bun venit la Newsletter-ul Brodero!";
            // $message = "Mulțumim pentru abonare...";
            // mail($email, $subject, $message);
            
        } else {
            setMessage("A apărut o eroare la procesarea cererii. Te rugăm să încerci din nou.", "danger");
        }
        $insertStmt->close();
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    setMessage("A apărut o eroare tehnică. Te rugăm să încerci mai târziu.", "danger");
}

// Redirect înapoi la pagina anterioară
redirect($_SERVER['HTTP_REFERER'] ?? '/');
exit;
?>
