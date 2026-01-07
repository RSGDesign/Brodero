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
    global $conn;
    
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
    global $conn;
    
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
    global $conn;
    
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
 * Status: pending (până la prima comandă plătită)
 * 
 * @param int $referrerId ID user care a trimis invitația
 * @param int $referredId ID user nou înregistrat
 * @return bool Succes
 */
function createReferral($referrerId, $referredId) {
    global $conn;
    
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
    
    // Creează referral cu status pending
    $stmt = $conn->prepare("
        INSERT INTO referrals (referrer_user_id, referred_user_id, status) 
        VALUES (?, ?, 'pending')
    ");
    $stmt->bind_param("ii", $referrerId, $referredId);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        error_log("REFERRAL SUCCESS: User $referrerId referred user $referredId");
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
    global $conn;
    
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
// 4. ACTIVARE REFERRAL & RECOMPENSĂ (La prima plată)
// ───────────────────────────────────────────────────────────────────────────

/**
 * Activează referral-ul după prima comandă plătită
 * Schimbă status: pending → completed
 * Acordă recompensă în credit_balance al referrer-ului
 * 
 * @param int $referredUserId ID utilizator care a făcut prima plată
 * @return bool Succes
 */
function activateReferralReward($referredUserId) {
    global $conn;
    
    // Verifică dacă există un referral pending pentru acest user
    $stmt = $conn->prepare("
        SELECT id, referrer_user_id 
        FROM referrals 
        WHERE referred_user_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("i", $referredUserId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$result) {
        return false; // Nu există referral pending
    }
    
    $referralId = $result['id'];
    $referrerId = $result['referrer_user_id'];
    
    // Obține suma recompensei din setări
    $rewardAmount = getReferralRewardAmount();
    
    // Începe tranzacție
    $conn->begin_transaction();
    
    try {
        // 1. Actualizează status referral → completed
        $stmt = $conn->prepare("
            UPDATE referrals 
            SET status = 'completed', 
                reward_amount = ?, 
                completed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("di", $rewardAmount, $referralId);
        $stmt->execute();
        $stmt->close();
        
        // 2. Adaugă recompensa în credit_balance al referrer-ului
        $stmt = $conn->prepare("
            UPDATE users 
            SET credit_balance = credit_balance + ? 
            WHERE id = ?
        ");
        $stmt->bind_param("di", $rewardAmount, $referrerId);
        $stmt->execute();
        $stmt->close();
        
        // Commit tranzacție
        $conn->commit();
        
        error_log("REFERRAL REWARD: User $referrerId earned $rewardAmount lei from user $referredUserId");
        
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("REFERRAL ERROR: Failed to activate reward - " . $e->getMessage());
        return false;
    }
}

/**
 * Obține suma recompensei din setări
 * 
 * @return float Suma recompensă (default: 50 lei)
 */
function getReferralRewardAmount() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM referral_settings WHERE setting_key = 'reward_amount'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return isset($result['setting_value']) ? floatval($result['setting_value']) : 50.00;
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
    global $conn;
    
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
    global $conn;
    
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
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_referrals,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_referrals,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_referrals,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN reward_amount ELSE 0 END), 0) as total_earned
        FROM referrals 
        WHERE referrer_user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return [
        'total_referrals' => intval($result['total_referrals']),
        'completed_referrals' => intval($result['completed_referrals']),
        'pending_referrals' => intval($result['pending_referrals']),
        'total_earned' => floatval($result['total_earned']),
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
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            u.name as referred_name,
            u.email as referred_email,
            u.created_at as referred_joined_at
        FROM referrals r
        JOIN users u ON r.referred_user_id = u.id
        WHERE r.referrer_user_id = ?
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
    global $conn;
    
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
    global $conn;
    
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
    global $conn;
    
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
    global $conn;
    
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
    global $conn;
    
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
