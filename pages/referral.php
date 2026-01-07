<?php
/**
 * Pagina Referral & Câștiguri
 * Dashboard complet pentru sistemul de referral
 */

$pageTitle = "Referral & Câștiguri";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_referral.php';

// Verificare autentificare
if (!isLoggedIn()) {
    setMessage("Trebuie să fii autentificat pentru a accesa această pagină.", "warning");
    redirect('/pages/login.php?redirect=/pages/referral.php');
}

$userId = $_SESSION['user_id'];
$db = getDB();

// Procesare cerere retragere
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_withdrawal'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    $iban = cleanInput($_POST['iban'] ?? '');
    $accountName = cleanInput($_POST['account_name'] ?? '');
    
    $result = createWithdrawalRequest($userId, $amount, $iban, $accountName);
    setMessage($result['message'], $result['success'] ? 'success' : 'danger');
    redirect('/pages/referral.php');
}

// Obține date
$referralCode = getUserReferralCode($userId);
$stats = getUserReferralStats($userId);
$referralsList = getUserReferralsList($userId);
$withdrawalRequests = getUserWithdrawalRequests($userId);
$earningsList = getUserReferralEarnings($userId);
$commissionPercentage = $stats['commission_percentage'];
$minWithdrawal = getMinWithdrawalAmount();

// Link complet referral
$referralLink = SITE_URL . '/?ref=' . $referralCode;
?>

<!-- Page Header -->
<section class="bg-gradient" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center text-white">
                <h1 class="display-5 fw-bold mb-3">
                    <i class="bi bi-people-fill me-2"></i>Referral & Câștiguri
                </h1>
                <p class="lead">Câștigă <?php echo number_format($commissionPercentage, 0); ?>% comision din fiecare comandă a prietenilor invitați</p>
            </div>
        </div>
    </div>
</section>

<div class="container my-5">
    
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- DASHBOARD CARDS - Statistici Rapide -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="row g-4 mb-5">
        <!-- Card: Sold Disponibil -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-wallet2 text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-muted text-uppercase small mb-2">Sold Disponibil</h6>
                    <h2 class="fw-bold mb-0 text-success"><?php echo number_format($stats['current_balance'], 2); ?> RON</h2>
                </div>
            </div>
        </div>
        
        <!-- Card: Total Câștigat -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-trophy text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-muted text-uppercase small mb-2">Total Câștigat</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_earned'], 2); ?> RON</h2>
                </div>
            </div>
        </div>
        
        <!-- Card: Referrals Reușite -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-people text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-muted text-uppercase small mb-2">Utilizatori Referați</h6>
                    <h2 class="fw-bold mb-0 text-primary"><?php echo $stats['total_referrals']; ?></h2>
                </div>
            </div>
        </div>
        
        <!-- Card: În Așteptare -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-cart-check text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-muted text-uppercase small mb-2">Comenzi cu Comision</h6>
                    <h2 class="fw-bold mb-0 text-info"><?php echo $stats['orders_with_commission']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- LINK REFERRAL PERSONAL -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">
                        <i class="bi bi-link-45deg text-primary me-2"></i>Link-ul Tău de Referral
                    </h4>
                    <p class="text-muted mb-4">Distribuie acest link prietenilor tăi. Câștigi <strong><?php echo number_format($commissionPercentage, 0); ?>%</strong> comision din fiecare comandă plătită!</p>
                    
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="referralLink" value="<?php echo htmlspecialchars($referralLink); ?>" readonly>
                        <button class="btn btn-primary" type="button" onclick="copyReferralLink()">
                            <i class="bi bi-clipboard me-1"></i>Copiază Link
                        </button>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($referralLink); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-facebook me-1"></i>Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($referralLink); ?>&text=Descoperă Brodero!" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-twitter me-1"></i>Twitter
                        </a>
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Descoperă Brodero! ' . $referralLink); ?>" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-whatsapp me-1"></i>WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- ACȚIUNI: Folosește Credit / Retrage Bani -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="row mb-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-cart-check text-success me-2"></i>Folosește Creditul la Checkout
                    </h5>
                    <p class="text-muted">Poți folosi soldul disponibil pentru a reduce prețul comenzilor tale.</p>
                    <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="btn btn-success w-100">
                        <i class="bi bi-arrow-right-circle me-2"></i>Mergi la Coș
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-bank text-primary me-2"></i>Solicită Retragere Bancară
                    </h5>
                    <p class="text-muted">Minim <?php echo number_format($minWithdrawal, 2); ?> RON. Banii vor fi transferați în contul tău.</p>
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#withdrawalModal" <?php echo $stats['current_balance'] < $minWithdrawal ? 'disabled' : ''; ?>>
                        <i class="bi bi-cash-stack me-2"></i>Solicită Retragere
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- LISTA REFERRALS -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow mb-5">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="bi bi-people text-primary me-2"></i>Persoanele Tale Invitate</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($referralsList)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3">Nu ai invitat încă pe nimeni. Distribuie link-ul tău de referral!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nume</th>
                                <th>Email</th>
                                <th>Înregistrat La</th>
                                <th>Comenzi</th>
                                <th>Total Comision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referralsList as $referral): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($referral['referred_first_name'] . ' ' . $referral['referred_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($referral['referred_email'], 0, 3) . '***'); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($referral['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $referral['orders_count']; ?> comenzi</span>
                                    </td>
                                    <td class="fw-bold <?php echo $referral['total_commission'] > 0 ? 'text-success' : 'text-muted'; ?>">
                                        <?php echo $referral['total_commission'] > 0 ? number_format($referral['total_commission'], 2) . ' RON' : '—'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- LISTA COMISIOANE (EARNINGS) -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow mb-5">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="bi bi-cash-stack text-success me-2"></i>Istoric Comisioane</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($earningsList)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-receipt text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3">Nu ai câștigat încă comisioane. Invită prieteni pentru a începe!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Comandă</th>
                                <th>De la</th>
                                <th>Valoare Comandă</th>
                                <th>Comision (<?php echo number_format($commissionPercentage, 0); ?>%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($earningsList as $earning): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($earning['created_at'])); ?></td>
                                    <td><code>#<?php echo htmlspecialchars($earning['order_number']); ?></code></td>
                                    <td><?php echo htmlspecialchars($earning['referred_first_name'] . ' ' . $earning['referred_last_name']); ?></td>
                                    <td><?php echo number_format($earning['order_total'], 2); ?> RON</td>
                                    <td class="fw-bold text-success">+<?php echo number_format($earning['commission_amount'], 2); ?> RON</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- LISTA CERERI RETRAGERE -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="bi bi-list-check text-primary me-2"></i>Cereri de Retragere</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($withdrawalRequests)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-text text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3">Nu ai cereri de retragere.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Sumă</th>
                                <th>IBAN</th>
                                <th>Status</th>
                                <th>Notă Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawalRequests as $request): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></td>
                                    <td class="fw-bold"><?php echo number_format($request['amount'], 2); ?> RON</td>
                                    <td><code><?php echo htmlspecialchars($request['bank_account_iban']); ?></code></td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark">În Procesare</span>
                                        <?php elseif ($request['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Aprobat</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Respins</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo $request['admin_note'] ? htmlspecialchars($request['admin_note']) : '—'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODAL: Cerere Retragere Bancară -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="withdrawalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Cerere Retragere Bancară</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Sold disponibil:</strong> <?php echo number_format($stats['current_balance'], 2); ?> RON<br>
                        <strong>Sumă minimă:</strong> <?php echo number_format($minWithdrawal, 2); ?> RON
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Sumă Solicitată (RON) *</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="<?php echo $minWithdrawal; ?>" max="<?php echo $stats['current_balance']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="iban" class="form-label">IBAN Cont Bancar *</label>
                        <input type="text" class="form-control" id="iban" name="iban" placeholder="RO49AAAA1B31007593840000" required>
                        <small class="form-text text-muted">Format: RO + 22 cifre</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="account_name" class="form-label">Nume Titular Cont *</label>
                        <input type="text" class="form-control" id="account_name" name="account_name" placeholder="Nume Prenume" required>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Cererea va fi procesată manual de către administrator. Transferul se efectuează în 3-5 zile lucrătoare.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" name="submit_withdrawal" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Trimite Cerere
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Copiază link referral
function copyReferralLink() {
    const input = document.getElementById('referralLink');
    input.select();
    input.setSelectionRange(0, 99999); // Pentru mobile
    
    navigator.clipboard.writeText(input.value).then(() => {
        // Toast success
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copiat!';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2000);
    }).catch(err => {
        alert('Eroare la copiere: ' + err);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
