<?php
/**
 * Pagina Login
 * Autentificare utilizatori
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions_referral.php';

// Redirect dacă deja autentificat
if (isLoggedIn()) {
    redirect('/pages/cont.php');
}

// Procesare formular login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        setMessage("Te rugăm să completezi toate câmpurile.", "danger");
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];
                
                setMessage("Bun venit, " . $user['first_name'] . "!", "success");
                
                // Redirect către pagina anterioară sau contul utilizatorului
                $redirect_to = $_GET['redirect'] ?? '/pages/cont.php';
                redirect($redirect_to);
            } else {
                setMessage("Email sau parolă incorectă.", "danger");
            }
        } else {
            setMessage("Email sau parolă incorectă.", "danger");
        }
        $stmt->close();
    }
}

// Procesare formular register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstName = cleanInput($_POST['first_name'] ?? '');
    $lastName = cleanInput($_POST['last_name'] ?? '');
    $email = cleanInput($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($firstName) || empty($lastName)) {
        $errors[] = "Numele și prenumele sunt obligatorii.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email-ul este invalid.";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Parola trebuie să aibă minim 6 caractere.";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Parolele nu se potrivesc.";
    }
    
    // Verificare email duplicat
    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Acest email este deja înregistrat.";
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $username = strtolower($firstName . $lastName . rand(100, 999));
        
        // Generează cod referral unic pentru noul utilizator
        $referralCode = generateReferralCode();
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, referral_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $email, $hashedPassword, $firstName, $lastName, $referralCode);
        
        if ($stmt->execute()) {
            $newUserId = $stmt->insert_id;
            
            // ═══════════════════════════════════════════════════════════════════════════
            // REFERRAL PROCESSING - Verifică dacă utilizatorul a venit prin link referral
            // ═══════════════════════════════════════════════════════════════════════════
            $referralCodeFromCookie = getReferralCodeFromCookie();
            if ($referralCodeFromCookie) {
                $referrerId = getUserIdFromReferralCode($referralCodeFromCookie);
                if ($referrerId && $referrerId !== $newUserId) {
                    // Creează referral record (status: pending)
                    createReferral($referrerId, $newUserId);
                }
                // Șterge cookie-ul referral după procesare
                clearReferralCodeCookie();
            }
            
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            $_SESSION['user_role'] = 'user';
            
            setMessage("Cont creat cu succes! Bun venit la Brodero!", "success");
            redirect('/pages/cont.php');
        } else {
            $errors[] = "Eroare la crearea contului. Te rugăm să încerci din nou.";
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setMessage($error, "danger");
        }
    }
}

// Include header AFTER processing to allow redirects
$pageTitle = "Autentificare";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Login Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="row g-0 shadow-lg rounded-custom overflow-hidden">
                    <!-- Login Form -->
                    <div class="col-md-6 bg-white p-5">
                        <h3 class="fw-bold mb-4">Autentificare</h3>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Parolă</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">
                                    Ține-mă minte
                                </label>
                            </div>
                            
                            <button type="submit" name="login" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Autentifică-te
                            </button>
                            
                            <div class="text-center">
                                <a href="#" class="text-decoration-none">Ai uitat parola?</a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Register Form -->
                    <div class="col-md-6 bg-light p-5">
                        <h3 class="fw-bold mb-4">Înregistrare</h3>
                        
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="first_name" class="form-label">Prenume</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                
                                <div class="col-6">
                                    <label for="last_name" class="form-label">Nume</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                                
                                <div class="col-12">
                                    <label for="reg_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="reg_email" name="reg_email" required>
                                </div>
                                
                                <div class="col-12">
                                    <label for="reg_password" class="form-label">Parolă</label>
                                    <input type="password" class="form-control" id="reg_password" name="reg_password" required>
                                    <div class="form-text">Minim 6 caractere</div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="confirm_password" class="form-label">Confirmă Parola</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            Sunt de acord cu <a href="<?php echo SITE_URL; ?>/pages/termeni.php" target="_blank">Termenii și Condițiile</a>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" name="register" class="btn btn-primary w-100">
                                        <i class="bi bi-person-plus me-2"></i>Creează Cont
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
