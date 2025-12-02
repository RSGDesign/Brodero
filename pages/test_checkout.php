<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "TEST 1: Start<br>";

try {
    echo "TEST 2: Before config<br>";
    require_once __DIR__ . '/../config/config.php';
    echo "TEST 3: After config<br>";
    
    require_once __DIR__ . '/../config/database.php';
    echo "TEST 4: After database<br>";
    
    $db = getDB();
    echo "TEST 5: Got DB connection<br>";
    
    echo "TEST 6: Session ID = " . (isset($_SESSION['session_id']) ? $_SESSION['session_id'] : 'NOT SET') . "<br>";
    echo "TEST 7: User ID = " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "<br>";
    
    echo "<br><strong>TOT E OK! Problema e mai departe în checkout_process.php</strong>";
    
} catch (Throwable $e) {
    echo "EROARE: " . $e->getMessage() . "<br>";
    echo "Fișier: " . $e->getFile() . "<br>";
    echo "Linia: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
