<?php
/**
 * Newsletter subscription
 * Gestionare abonări la newsletter
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setMessage("Email-ul introdus este invalid.", "danger");
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
    
    $db = getDB();
    
    // Verificare dacă emailul există deja
    $stmt = $db->prepare("SELECT id FROM newsletter WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        setMessage("Acest email este deja abonat la newsletter.", "info");
    } else {
        $stmt = $db->prepare("INSERT INTO newsletter (email) VALUES (?)");
        $stmt->bind_param("s", $email);
        
        if ($stmt->execute()) {
            setMessage("Te-ai abonat cu succes la newsletter! Îți mulțumim!", "success");
        } else {
            setMessage("Eroare la abonare. Te rugăm să încerci din nou.", "danger");
        }
    }
    $stmt->close();
}

redirect($_SERVER['HTTP_REFERER'] ?? '/');
?>
