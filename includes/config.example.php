<?php
/**
 * Config Example - Template pentru configurarea cheilor secrete
 * 
 * INSTRUCȚIUNI:
 * 1. Copiază acest fișier ca "config.local.php" în același folder
 * 2. Completează valorile reale în config.local.php
 * 3. NU edita acest fișier cu valori reale!
 * 
 * config.local.php este în .gitignore și NU va fi versionat
 */

return [
    // ═══════════════════════════════════════════════════════════════════════════
    // BAZĂ DE DATE
    // ═══════════════════════════════════════════════════════════════════════════
    'database' => [
        'host'     => 'localhost',
        'user'     => 'your_database_user',
        'password' => 'your_database_password',
        'name'     => 'your_database_name',
    ],
    
    // ═══════════════════════════════════════════════════════════════════════════
    // STRIPE PAYMENT
    // ═══════════════════════════════════════════════════════════════════════════
    'stripe' => [
        'secret_key'      => 'sk_test_XXXXXXXXXXXXXXXXXXXXXX', // Stripe Secret Key
        'publishable_key' => 'pk_test_XXXXXXXXXXXXXXXXXXXXXX', // Stripe Publishable Key
    ],
    
    // ═══════════════════════════════════════════════════════════════════════════
    // GOOGLE ANALYTICS 4
    // ═══════════════════════════════════════════════════════════════════════════
    'analytics' => [
        'ga4_measurement_id' => 'G-XXXXXXXXXX', // GA4 Measurement ID
    ],
    
    // ═══════════════════════════════════════════════════════════════════════════
    // EMAIL (opțional - pentru SMTP)
    // ═══════════════════════════════════════════════════════════════════════════
    'email' => [
        'smtp_host'     => 'smtp.example.com',
        'smtp_port'     => 587,
        'smtp_user'     => 'your_email@example.com',
        'smtp_password' => 'your_smtp_password',
        'from_email'    => 'noreply@brodero.online',
        'from_name'     => 'Brodero',
    ],
    
    // ═══════════════════════════════════════════════════════════════════════════
    // ENVIRONMENT
    // ═══════════════════════════════════════════════════════════════════════════
    'environment' => [
        'debug_mode'    => false,        // true pentru debugging, false pentru producție
        'display_errors' => false,       // true pentru debugging, false pentru producție
    ],
];
