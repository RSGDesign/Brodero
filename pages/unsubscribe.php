<?php
/**
 * Unsubscribe from Newsletter
 * Pagină de dezabonare din newsletter
 */

$pageTitle = "Dezabonare Newsletter";

require_once __DIR__ . '/../includes/header.php';

$db = getDB();

$success = false;
$email = isset($_GET['email']) ? urldecode($_GET['email']) : '';

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailToUnsubscribe = trim($_POST['email']);
    
    if (empty($emailToUnsubscribe) || !filter_var($emailToUnsubscribe, FILTER_VALIDATE_EMAIL)) {
        setMessage("Email invalid.", "danger");
    } else {
        // Verificare dacă emailul există în baza de date
        $stmt = $db->prepare("SELECT id, is_active FROM newsletter WHERE email = ?");
        $stmt->bind_param("s", $emailToUnsubscribe);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $subscriber = $result->fetch_assoc();
            
            if ($subscriber['is_active'] == 0) {
                setMessage("Ești deja dezabonat de la newsletter.", "info");
            } else {
                // Dezabonare
                $stmt = $db->prepare("UPDATE newsletter SET is_active = 0 WHERE email = ?");
                $stmt->bind_param("s", $emailToUnsubscribe);
                
                if ($stmt->execute()) {
                    $success = true;
                    setMessage("Ai fost dezabonat cu succes de la newsletter.", "success");
                } else {
                    setMessage("A apărut o eroare. Te rugăm să încerci din nou.", "danger");
                }
            }
        } else {
            setMessage("Email-ul nu a fost găsit în lista de abonați.", "warning");
        }
    }
}
?>

<!-- Page Header -->
<section class="bg-gradient" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 80px 0;">
    <div class="container text-center text-white">
        <i class="bi bi-envelope-x display-1 mb-4"></i>
        <h1 class="display-4 fw-bold mb-3">Dezabonare Newsletter</h1>
        <p class="lead mb-0">Ne pare rău că vrei să pleci. Ne-ar plăcea să te avem în continuare alături!</p>
    </div>
</section>

<!-- Unsubscribe Form -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <?php if ($success): ?>
                <!-- Success Message -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h3 class="fw-bold mb-3">Dezabonare Reușită</h3>
                        <p class="text-muted mb-4">
                            Ai fost dezabonat cu succes de la newsletter-ul Brodero. 
                            Nu vei mai primi emailuri promoționale de la noi.
                        </p>
                        <p class="text-muted mb-4">
                            <small>
                                Dacă te răzgândești, poți oricând să te reabonezi din footer-ul site-ului sau din contul tău.
                            </small>
                        </p>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                            <i class="bi bi-house-fill me-2"></i>Înapoi la Pagina Principală
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <!-- Unsubscribe Form -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-envelope-x text-warning" style="font-size: 2rem;"></i>
                            </div>
                            <h3 class="fw-bold">Confirmă Dezabonarea</h3>
                            <p class="text-muted mb-0">
                                Introdu adresa ta de email pentru a te dezabona de la newsletter
                            </p>
                        </div>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Adresa Email</label>
                                <input type="email" name="email" class="form-control form-control-lg" 
                                       value="<?php echo htmlspecialchars($email); ?>" 
                                       placeholder="exemplu@email.com" required>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Ce se întâmplă după dezabonare?</strong>
                                <ul class="mb-0 mt-2 small">
                                    <li>Nu vei mai primi emailuri promoționale</li>
                                    <li>Nu vei mai fi notificat despre oferte speciale</li>
                                    <li>Poți oricând să te reabonezi</li>
                                    <li>Vei primi în continuare emailuri legate de comenzi</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-envelope-x me-2"></i>Confirmă Dezabonarea
                                </button>
                                <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Renunță, vreau să rămân abonat
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Alternative Actions -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">
                            <i class="bi bi-lightbulb me-2"></i>În loc de dezabonare, ai putea:
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Să îți ajustezi preferințele de newsletter (în curând)
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Să ne trimiți feedback despre ce tipuri de emailuri preferi
                            </li>
                            <li class="mb-0">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Să ne urmărești pe social media pentru mai puține emailuri
                            </li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Why Stay Section -->
<?php if (!$success): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-4">De ce să rămâi abonat?</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4 text-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                    <i class="bi bi-percent text-primary fs-3"></i>
                                </div>
                                <h5 class="fw-bold">Oferte Exclusive</h5>
                                <p class="text-muted mb-0">Reduceri speciale doar pentru abonați</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4 text-center">
                                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                    <i class="bi bi-stars text-success fs-3"></i>
                                </div>
                                <h5 class="fw-bold">Produse Noi</h5>
                                <p class="text-muted mb-0">Fii primul care află despre lansări</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4 text-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                    <i class="bi bi-gift text-warning fs-3"></i>
                                </div>
                                <h5 class="fw-bold">Concursuri</h5>
                                <p class="text-muted mb-0">Participă la tombole și premii</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
