<?php
require_once __DIR__ . '/../config/config.php';

// Elimină cuponul aplicat din sesiune
unset($_SESSION['applied_coupon']);

setMessage("Cupon eliminat.", "info");
redirect('/pages/cart.php');
?>
