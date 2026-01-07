<?php
/**
 * Google Analytics 4 (GA4) Integration - MVP
 * Brodero
 * 
 * GDPR Compliant - Only loads if cookie consent is granted
 */

// Configuration - CHANGE THIS!
define('GA4_MEASUREMENT_ID', 'G-XXXXXXXXXX'); // Replace with your actual GA4 Measurement ID

/**
 * Check if user has given consent for analytics cookies
 */
function hasAnalyticsConsent() {
    return isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === 'granted';
}

/**
 * Output GA4 tracking code
 * Only loads if consent is granted
 */
function renderGA4Code() {
    $measurementId = GA4_MEASUREMENT_ID;
    
    // Don't load if consent not granted
    if (!hasAnalyticsConsent()) {
        // Set default consent to denied
        echo "\n<!-- Google Analytics - Waiting for consent -->\n";
        echo "<script>\n";
        echo "  window.dataLayer = window.dataLayer || [];\n";
        echo "  function gtag(){dataLayer.push(arguments);}\n";
        echo "  gtag('consent', 'default', {\n";
        echo "    'analytics_storage': 'denied'\n";
        echo "  });\n";
        echo "</script>\n";
        return;
    }
    
    // User has consented - load GA4
    ?>
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $measurementId; ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  
  // Set consent to granted
  gtag('consent', 'default', {
    'analytics_storage': 'granted'
  });
  
  gtag('js', new Date());
  gtag('config', '<?php echo $measurementId; ?>', {
    'anonymize_ip': true,  // Anonymize IP for GDPR
    'cookie_flags': 'SameSite=None;Secure'
  });
</script>
<?php
}

/**
 * Helper function to track custom events from PHP
 * Outputs JavaScript to trigger event
 * 
 * @param string $eventName - GA4 event name
 * @param array $eventParams - Event parameters (optional)
 */
function trackEvent($eventName, $eventParams = []) {
    if (!hasAnalyticsConsent()) {
        return; // Don't track if no consent
    }
    
    echo "<script>\n";
    echo "if (typeof gtag === 'function') {\n";
    
    if (empty($eventParams)) {
        echo "  gtag('event', '" . htmlspecialchars($eventName, ENT_QUOTES) . "');\n";
    } else {
        $paramsJson = json_encode($eventParams, JSON_HEX_TAG | JSON_HEX_AMP);
        echo "  gtag('event', '" . htmlspecialchars($eventName, ENT_QUOTES) . "', " . $paramsJson . ");\n";
    }
    
    echo "}\n";
    echo "</script>\n";
}

/**
 * Track purchase event
 * 
 * @param float $value - Order total value
 * @param string $currency - Currency code (default: RON)
 * @param string $transactionId - Order ID
 */
function trackPurchase($value, $currency = 'RON', $transactionId = '') {
    $params = [
        'value' => floatval($value),
        'currency' => $currency
    ];
    
    if (!empty($transactionId)) {
        $params['transaction_id'] = $transactionId;
    }
    
    trackEvent('purchase', $params);
}

/**
 * Track begin checkout event
 * 
 * @param float $value - Cart value (optional)
 */
function trackBeginCheckout($value = null) {
    $params = [];
    
    if ($value !== null) {
        $params['value'] = floatval($value);
        $params['currency'] = 'RON';
    }
    
    trackEvent('begin_checkout', $params);
}

/**
 * Track referral click event (optional)
 * 
 * @param string $referralCode
 */
function trackReferralClick($referralCode) {
    trackEvent('referral_click', [
        'referral_code' => $referralCode
    ]);
}
