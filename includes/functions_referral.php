<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * REFERRAL SYSTEM MVP - Helper Functions
 * Data: 7 ianuarie 2026
 * Descriere: Funcții core pentru sistemul de referral
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// ───────────────────────────────────────────────────────────────────────────
// 1. GENERARE & GESTIUNE CODURI REFERRAL
// ───────────────────────────────────────────────────────────────────────────

/**
 * Generează un cod referral unic
 * Format: REF + 10 caractere alfanumerice uppercase
 * 
 * @return string Cod referral unic
 */
function generateReferralCode() {
    $conn = getDB();
    
    do {
        // Generează cod unic: REF + hash MD5 (primele 10 caractere)
        $code = 'REF' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        
        // Verifică dacă codul există deja
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE referral_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $exists = $result['count'] > 0;
        $stmt->close();
        
    } while ($exists); // Repetă până găsești un cod unic
    
    return $code;
}

/**
 * Obține codul referral al unui utilizator
 * 
 * @param int $userId ID utilizator
 * @return string|null Codul referral sau null
 */
function getUserReferralCode($userId) {
    $conn = getDB();
    
    $stmt = $conn->prepare("SELECT referral_code FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['referral_code'] ?? null;
}

/**
 * Obține ID utilizator din codul referral
 * 
 * @param string $referralCode Cod referral
 * @return int|null ID utilizator sau null
 */
function getUserIdFromReferralCode($referralCode) {
    $conn = getDB();
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
    $stmt->bind_param("s", $referralCode);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['id'] ?? null;
}

// ───────────────────────────────────────────────────────────────────────────
// 2. TRACKING REFERRAL (Cookie/Session)
// ───────────────────────────────────────────────────────────────────────────

/**
 * Salvează codul referral în cookie (când vizitator accesează link cu ?ref=)
 * Cookie valid 30 zile
 * 
 * @param string $referralCode Cod referral
 * @return bool Succes
 */
function saveReferralCodeToCookie($referralCode) {
    // Validează că există un user cu acest cod
    if (!getUserIdFromReferralCode($referralCode)) {
        return false;
    }
    
    // Salvează în cookie (30 zile)
    $expire = time() + (30 * 24 * 60 * 60); // 30 zile
    setcookie('referral_code', $referralCode, $expire, '/', '', true, true);
    
    // Salvează și în sesiune pentru access imediat
    $_SESSION['pending_referral_code'] = $referralCode;
    
    return true;
}

/**
 * Obține codul referral din cookie/session
 * 
 * @return string|null Cod referral sau null
 */
function getReferralCodeFromCookie() {
    // Încearcă mai întâi din sesiune
    if (isset($_SESSION['pending_referral_code'])) {
        return $_SESSION['pending_referral_code'];
    }
    
    // Apoi din cookie
    if (isset($_COOKIE['referral_code'])) {
        return $_COOKIE['referral_code'];
    }
    
    return null;
}

/**
 * Șterge codul referral din cookie/session (după înregistrare reușită)
 */
function clearReferralCodeCookie() {
    // Șterge cookie
    if (isset($_COOKIE['referral_code'])) {
        setcookie('referral_code', '', time() - 3600, '/');
        unset($_COOKIE['referral_code']);
    }
    
    // Șterge din sesiune
    if (isset($_SESSION['pending_referral_code'])) {
        unset($_SESSION['pending_referral_code']);
    }
}

// ───────────────────────────────────────────────────────────────────────────
// 3. CREARE & GESTIUNE REFERRALS
// ───────────────────────────────────────────────────────────────────────────

/**
 * Creează un referral nou (la înregistrare user)
 * Relația este permanentă (fără status)
 * 
 * @param int $referrerId ID user care a trimis invitația
 * @param int $referredId ID user nou înregistrat
 * @param float|null $commissionPercentage Procent comision (default din setări)
 * @return bool Succes
 */
function createReferral($referrerId, $referredId, $commissionPercentage = null) {
    $conn = getDB();
    
    // Validări anti-abuz
    if ($referrerId === $referredId) {
        error_log("REFERRAL ERROR: Self-referral attempt (user $referrerId)");
        return false; // Nu poți face self-referral
    }
    
    // Verifică dacă utilizatorul a fost deja referit
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM referrals WHERE referred_user_id = ?");
    $stmt->bind_param("i", $referredId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result['count'] > 0) {
        error_log("REFERRAL ERROR: User $referredId already referred");
        return false; // User poate fi referit o singură dată
    }
    
    // Obține procent comision din setări dacă nu e specificat
    if ($commissionPercentage === null) {
        $commissionPercentage = getCommissionPercentage();
    }
    
    // Creează referral permanent (fără status)
    $stmt = $conn->prepare("
        INSERT INTO referrals (referrer_user_id, referred_user_id, commission_percentage) 
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iid", $referrerId, $referredId, $commissionPercentage);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        error_log("REFERRAL SUCCESS: User $referrerId referred user $referredId (commission: {$commissionPercentage}%)");
    }
    
    return $success;
}

/**
 * Verifică dacă un utilizator a fost referit
 * 
 * @param int $userId ID utilizator
 * @return array|null Datele referral-ului sau null
 */
function getUserReferralInfo($userId) {
    $conn = getDB();
    
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            referrer.name as referrer_name,
            referrer.email as referrer_email
        FROM referrals r
        JOIN users referrer ON r.referrer_user_id = referrer.id
        WHERE r.referred_user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

// ───────────────────────────────────────────────────────────────────────────
// 4. CALCUL & ACORDARE COMISION (La fiecare comandă plătită)
// ───────────────────────────────────────────────────────────────────────────

/**
 * Calculează și acordă comision pentru o comandă plătită
 * Apelat automat când o comandă primește status 'paid'
 * 
 * @param int $orderId ID comandă
 * @return bool Succes
 */
function calculateAndAwardCommission($orderId) {
    $conn = getDB();
    
    // Obține detalii comandă
    $stmt = $conn->prepare("
        SELECT user_id, total_amount 
        FROM orders 
        WHERE id = ? AND payment_status = 'paid'
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        error_log("COMMISSION ERROR: Order $orderId not found or not paid");
        return false; // Comanda nu există sau nu e plătită
    }
    
    $userId = $order['user_id'];
    $orderTotal = floatval($order['total_amount']);
    
    // Verifică dacă userul are referrer
    $stmt = $conn->prepare("
        SELECT id, referrer_user_id, commission_percentage 
        FROM referrals 
        WHERE referred_user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $referral = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$referral) {
        return false; // User nu a fost referit
    }
    
    $referralId = $referral['id'];
    $referrerId = $referral['referrer_user_id'];
    $commissionPercentage = floatval($referral['commission_percentage']);
    
    // Verifică dacă comisionul a fost deja acordat pentru această comandă
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM referral_earnings WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result['count'] > 0) {
        error_log("COMMISSION WARNING: Commission already awarded for order $orderId");
        return false; // Comision deja acordat
    }
    
    // Calculează comision
    $commissionAmount = ($orderTotal * $commissionPercentage) / 100;
    
    // Începe tranzacție
    $conn->begin_transaction();
    
    try {
        // 1. Creează record în referral_earnings
        $stmt = $conn->prepare("
            INSERT INTO referral_earnings (referral_id, order_id, order_total, commission_amount) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iidd", $referralId, $orderId, $orderTotal, $commissionAmount);
        $stmt->execute();
        $stmt->close();
        
        // 2. Adaugă comision în credit_balance al referrer-ului
        $stmt = $conn->prepare("
            UPDATE users 
            SET credit_balance = credit_balance + ? 
            WHERE id = ?
        ");
        $stmt->bind_param("di", $commissionAmount, $referrerId);
        $stmt->execute();
        $stmt->close();
        
        // Commit tranzacție
        $conn->commit();
        
        error_log("COMMISSION SUCCESS: User $referrerId earned {$commissionAmount} lei ({$commissionPercentage}%) from order $orderId (total: {$orderTotal} lei)");
        
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("COMMISSION ERROR: Failed to award commission - " . $e->getMessage());
        return false;
    }
}

/**
 * Obține procentul comision din setări
 * 
 * @return float Procent comision (default: 10%)
 */
function getCommissionPercentage() {
    $conn = getDB();
    
    $stmt = $conn->prepare("SELECT setting_value FROM referral_settings WHERE setting_key = 'commission_percentage'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return isset($result['setting_value']) ? floatval($result['setting_value']) : 10.00;
}

// ───────────────────────────────────────────────────────────────────────────
// 5. GESTIUNE CREDIT BALANCE
// ───────────────────────────────────────────────────────────────────────────

/**
 * Obține soldul de credit al unui utilizator
 * 
 * @param int $userId ID utilizator
 * @return float Sold credit
 */
function getUserCreditBalance($userId) {
    $conn = getDB();
    
    $stmt = $conn->prepare("SELECT credit_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return isset($result['credit_balance']) ? floatval($result['credit_balance']) : 0.00;
}

/**
 * Aplică credit la checkout (scade din credit_balance)
 * 
 * @param int $userId ID utilizator
 * @param float $amount Suma de scăzut
 * @return bool Succes
 */
function applyCreditToOrder($userId, $amount) {
    $conn = getDB();
    
    // Validare
    $currentBalance = getUserCreditBalance($userId);
    if ($amount > $currentBalance || $amount <= 0) {
        return false; // Sumă invalidă
    }
    
    $stmt = $conn->prepare("
        UPDATE users 
        SET credit_balance = credit_balance - ? 
        WHERE id = ? AND credit_balance >= ?
    ");
    $stmt->bind_param("did", $amount, $userId, $amount);
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    if ($success && $affected > 0) {
        error_log("CREDIT APPLIED: User $userId used $amount lei credit");
        return true;
    }
    
    return false;
}

// ───────────────────────────────────────────────────────────────────────────
// 6. STATISTICI REFERRAL (Pentru dashboard utilizator)
// ───────────────────────────────────────────────────────────────────────────

/**
 * Obține statistici referral pentru un utilizator
 * 
 * @param int $userId ID utilizator
 * @return array Statistici
 */
function getUserReferralStats($userId) {
    $conn = getDB();
    
    // Statistici referrals
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_referrals,
            commission_percentage
        FROM referrals 
        WHERE referrer_user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $totalReferrals = intval($result['total_referrals'] ?? 0);
    $commissionPercentage = floatval($result['commission_percentage'] ?? getCommissionPercentage());
    
    // Total câștigat din comisioane
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(re.commission_amount), 0) as total_earned
        FROM referral_earnings re
        JOIN referrals r ON re.referral_id = r.id
        WHERE r.referrer_user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $earningsResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Număr comenzi care au generat comision
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT re.order_id) as orders_with_commission
        FROM referral_earnings re
        JOIN referrals r ON re.referral_id = r.id
        WHERE r.referrer_user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $ordersResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return [
        'total_referrals' => $totalReferrals,
        'commission_percentage' => $commissionPercentage,
        'total_earned' => floatval($earningsResult['total_earned']),
        'orders_with_commission' => intval($ordersResult['orders_with_commission']),
        'current_balance' => getUserCreditBalance($userId)
    ];
}

/**
 * Obține lista referrals a unui utilizator
 * 
 * @param int $userId ID utilizator
 * @return array Lista referrals
 */
function getUserReferralsList($userId) {
    $conn = getDB();
    
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            u.first_name as referred_first_name,
            u.last_name as referred_last_name,
            u.email as referred_email,
            u.created_at as referred_joined_at,
            COUNT(DISTINCT re.order_id) as orders_count,
            COALESCE(SUM(re.commission_amount), 0) as total_commission
        FROM referrals r
        JOIN users u ON r.referred_user_id = u.id
        LEFT JOIN referral_earnings re ON re.referral_id = r.id
        WHERE r.referrer_user_id = ?
        GROUP BY r.id
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $referrals = [];
    while ($row = $result->fetch_assoc()) {
        $referrals[] = $row;
    }
    
    $stmt->close();
    
    return $referrals;
}

/**
 * Obține lista de earnings (comisioane) pentru un utilizator
 * 
 * @param int $userId ID utilizator referrer
 * @return array Lista earnings
 */
function getUserReferralEarnings($userId) {
    $conn = getDB();
    
    $stmt = $conn->prepare("
        SELECT 
            re.*,
            o.order_number,
            o.created_at as order_date,
            u.first_name as referred_first_name,
            u.last_name as referred_last_name,
            u.email as referred_email
        FROM referral_earnings re
        JOIN referrals r ON re.referral_id = r.id
        JOIN orders o ON re.order_id = o.id
        JOIN users u ON r.referred_user_id = u.id
        WHERE r.referrer_user_id = ?
        ORDER BY re.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $earnings = [];
    while ($row = $result->fetch_assoc()) {
        $earnings[] = $row;
    }
    
    $stmt->close();
    
    return $earnings;
}

// ───────────────────────────────────────────────────────────────────────────
// 7. CERERI RETRAGERE BANCARĂ
// ───────────────────────────────────────────────────────────────────────────

/**
 * Creează o cerere de retragere bancară
 * 
 * @param int $userId ID utilizator
 * @param float $amount Suma solicitată
 * @param string $iban IBAN cont beneficiar
 * @param string $accountName Nume titular cont
 * @return array ['success' => bool, 'message' => string, 'request_id' => int|null]
 */
function createWithdrawalRequest($userId, $amount, $iban, $accountName) {
    $conn = getDB();
    
    // Validări
    $currentBalance = getUserCreditBalance($userId);
    $minAmount = getMinWithdrawalAmount();
    
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Suma trebuie să fie pozitivă'];
    }
    
    if ($amount < $minAmount) {
        return ['success' => false, 'message' => "Suma minimă pentru retragere este $minAmount lei"];
    }
    
    if ($amount > $currentBalance) {
        return ['success' => false, 'message' => 'Sold insuficient'];
    }
    
    if (empty($iban) || strlen($iban) < 15) {
        return ['success' => false, 'message' => 'IBAN invalid'];
    }
    
    if (empty($accountName)) {
        return ['success' => false, 'message' => 'Nume titular obligatoriu'];
    }
    
    // Creează cerere
    $stmt = $conn->prepare("
        INSERT INTO withdrawal_requests (user_id, amount, bank_account_iban, bank_account_name, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("idss", $userId, $amount, $iban, $accountName);
    $success = $stmt->execute();
    $requestId = $success ? $stmt->insert_id : null;
    $stmt->close();
    
    if ($success) {
        error_log("WITHDRAWAL REQUEST: User $userId requested $amount lei");
        return [
            'success' => true, 
            'message' => 'Cererea de retragere a fost trimisă cu succes', 
            'request_id' => $requestId
        ];
    }
    
    return ['success' => false, 'message' => 'Eroare la salvarea cererii'];
}

/**
 * Obține suma minimă pentru retragere din setări
 * 
 * @return float Suma minimă (default: 100 lei)
 */
function getMinWithdrawalAmount() {
    $conn = getDB();
    
    $stmt = $conn->prepare("SELECT setting_value FROM referral_settings WHERE setting_key = 'min_withdrawal_amount'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return isset($result['setting_value']) ? floatval($result['setting_value']) : 100.00;
}

/**
 * Obține cererile de retragere ale unui utilizator
 * 
 * @param int $userId ID utilizator
 * @return array Lista cereri
 */
function getUserWithdrawalRequests($userId) {
    $conn = getDB();
    
    $stmt = $conn->prepare("
        SELECT * FROM withdrawal_requests 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    $stmt->close();
    
    return $requests;
}

/**
 * Aprobă cerere de retragere (doar admin)
 * Scade suma din credit_balance
 * 
 * @param int $requestId ID cerere
 * @param int $adminId ID admin care procesează
 * @param string $adminNote Notă (ex: nr. tranzacție)
 * @return array ['success' => bool, 'message' => string]
 */
function approveWithdrawalRequest($requestId, $adminId, $adminNote = '') {
    $conn = getDB();
    
    // Obține cererea
    $stmt = $conn->prepare("
        SELECT * FROM withdrawal_requests 
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$request) {
        return ['success' => false, 'message' => 'Cerere inexistentă sau deja procesată'];
    }
    
    // Verifică sold
    $currentBalance = getUserCreditBalance($request['user_id']);
    if ($request['amount'] > $currentBalance) {
        return ['success' => false, 'message' => 'Utilizatorul nu mai are sold suficient'];
    }
    
    // Începe tranzacție
    $conn->begin_transaction();
    
    try {
        // 1. Scade suma din credit_balance
        $stmt = $conn->prepare("
            UPDATE users 
            SET credit_balance = credit_balance - ? 
            WHERE id = ?
        ");
        $stmt->bind_param("di", $request['amount'], $request['user_id']);
        $stmt->execute();
        $stmt->close();
        
        // 2. Actualizează status cerere → approved
        $stmt = $conn->prepare("
            UPDATE withdrawal_requests 
            SET status = 'approved', 
                processed_by_admin_id = ?, 
                admin_note = ?, 
                processed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $adminId, $adminNote, $requestId);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        error_log("WITHDRAWAL APPROVED: Request $requestId ({$request['amount']} lei) approved by admin $adminId");
        
        return ['success' => true, 'message' => 'Cerere aprobată cu succes'];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("WITHDRAWAL ERROR: " . $e->getMessage());
        return ['success' => false, 'message' => 'Eroare la procesare'];
    }
}

/**
 * Respinge cerere de retragere (doar admin)
 * 
 * @param int $requestId ID cerere
 * @param int $adminId ID admin care procesează
 * @param string $adminNote Motiv respingere
 * @return array ['success' => bool, 'message' => string]
 */
function rejectWithdrawalRequest($requestId, $adminId, $adminNote = '') {
    $conn = getDB();
    
    $stmt = $conn->prepare("
        UPDATE withdrawal_requests 
        SET status = 'rejected', 
            processed_by_admin_id = ?, 
            admin_note = ?, 
            processed_at = NOW() 
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->bind_param("isi", $adminId, $adminNote, $requestId);
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    if ($success && $affected > 0) {
        error_log("WITHDRAWAL REJECTED: Request $requestId rejected by admin $adminId");
        return ['success' => true, 'message' => 'Cerere respinsă'];
    }
    
    return ['success' => false, 'message' => 'Cerere inexistentă sau deja procesată'];
}

// ═══════════════════════════════════════════════════════════════════════════
// FIN FUNCTIONS HELPER
// ═══════════════════════════════════════════════════════════════════════════
