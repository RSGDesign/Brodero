<?php
/**
 * Logout
 * Deconectare utilizator
 */

require_once __DIR__ . '/../config/config.php';

// Distruge sesiunea
session_unset();
session_destroy();

// Redirecționează către pagina principală
setMessage("Ai fost deconectat cu succes.", "success");
redirect('/');
?>
