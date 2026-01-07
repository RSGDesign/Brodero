<?php
/**
 * Admin Panel - Gestiune Referrals & Retrageri
 * Pagină administratorpentru monitorizare și procesare referrals + cereri retragere
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions_referral.php';

// Verificare admin
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

if (!isAdmin()) {
    redirect('/');
}

$db = getDB();

// Procesare actualizare setări
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $commissionPercentage = floatval($_POST['commission_percentage'] ?? 10);
    $minWithdrawal = floatval($_POST['min_withdrawal_amount'] ?? 100);
    
    // Validări
    if ($commissionPercentage < 0 || $commissionPercentage > 100) {
        setMessage("Procentul comision trebuie să fie între 0 și 100%", "danger");
    } elseif ($minWithdrawal < 0) {
        setMessage("Suma minimă retragere trebuie să fie pozitivă", "danger");
    } else {
        // Actualizare setări
        $stmt = $db->prepare("UPDATE referral_settings SET setting_value = ? WHERE setting_key = 'commission_percentage'");
        $stmt->bind_param("s", $commissionPercentage);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $db->prepare("UPDATE referral_settings SET setting_value = ? WHERE setting_key = 'min_withdrawal_amount'");
        $stmt->bind_param("s", $minWithdrawal);
        $stmt->execute();
        $stmt->close();
        
        setMessage("Setările au fost actualizate cu succes!", "success");
    }
    
    redirect('/admin/admin_referrals.php?tab=settings');
}

// Procesare aprobare/respingere retragere
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestId = intval($_POST['request_id'] ?? 0);
    $adminNote = cleanInput($_POST['admin_note'] ?? '');
    $adminId = $_SESSION['user_id'];
    
    if ($_POST['action'] === 'approve') {
        $result = approveWithdrawalRequest($requestId, $adminId, $adminNote);
        setMessage($result['message'], $result['success'] ? 'success' : 'danger');
    } elseif ($_POST['action'] === 'reject') {
        $result = rejectWithdrawalRequest($requestId, $adminId, $adminNote);
        setMessage($result['message'], $result['success'] ? 'success' : 'danger');
    }
    
    redirect('/admin/admin_referrals.php');
}

// Obține toate referrals cu earnings
$stmt = $db->prepare("
    SELECT 
        r.*,
        referrer.first_name as referrer_first_name,
        referrer.last_name as referrer_last_name,
        referrer.email as referrer_email,
        referred.first_name as referred_first_name,
        referred.last_name as referred_last_name,
        referred.email as referred_email,
        COUNT(DISTINCT re.order_id) as orders_count,
        COALESCE(SUM(re.commission_amount), 0) as total_commission
    FROM referrals r
    JOIN users referrer ON r.referrer_user_id = referrer.id
    JOIN users referred ON r.referred_user_id = referred.id
    LEFT JOIN referral_earnings re ON re.referral_id = r.id
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$allReferrals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obține toate earnings
$stmt = $db->prepare("
    SELECT 
        re.*,
        o.order_number,
        referrer.first_name as referrer_first_name,
        referrer.last_name as referrer_last_name,
        referred.first_name as referred_first_name,
        referred.last_name as referred_last_name
    FROM referral_earnings re
    JOIN referrals r ON re.referral_id = r.id
    JOIN orders o ON re.order_id = o.id
    JOIN users referrer ON r.referrer_user_id = referrer.id
    JOIN users referred ON r.referred_user_id = referred.id
    ORDER BY re.created_at DESC
    LIMIT 100
");
$stmt->execute();
$allEarnings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obține toate cererile de retragere
$stmt = $db->prepare("
    SELECT 
        w.*,
        u.first_name,
        u.last_name,
        u.email,
        admin.first_name as admin_first_name,
        admin.last_name as admin_last_name
    FROM withdrawal_requests w
    JOIN users u ON w.user_id = u.id
    LEFT JOIN users admin ON w.processed_by_admin_id = admin.id
    ORDER BY 
        CASE w.status 
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
        END,
        w.created_at DESC
");
$stmt->execute();
$allWithdrawals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Statistici generale
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_referrals,
        AVG(commission_percentage) as avg_commission_percentage
    FROM referrals
");
$stmt->execute();
$referralStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_earnings,
        COALESCE(SUM(commission_amount), 0) as total_commission_paid,
        COUNT(DISTINCT order_id) as orders_with_commission
    FROM referral_earnings
");
$stmt->execute();
$earningsStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_requests,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_requests,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) as approved_requests,
        COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) as rejected_requests,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as total_withdrawn
    FROM withdrawal_requests
");
$stmt->execute();
$withdrawalStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$pageTitle = "Gestiune Referrals";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-people-fill me-2"></i>Gestiune Referrals & Retrageri</h1>
</div>

<!-- Statistici Dashboard -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-people text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted small mb-1">Total Referrals</h6>
                        <h4 class="mb-0"><?php echo number_format($referralStats['total_referrals']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-cart-check text-info" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted small mb-1">Comenzi cu Comision</h6>
                        <h4 class="mb-0"><?php echo number_format($earningsStats['orders_with_commission']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-cash-coin text-success" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted small mb-1">Total Comisioane Plătite</h6>
                        <h4 class="mb-0"><?php echo number_format($earningsStats['total_commission_paid'], 2); ?> RON</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-bank text-danger" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted small mb-1">Total Retras</h6>
                        <h4 class="mb-0"><?php echo number_format($withdrawalStats['total_withdrawn'], 2); ?> RON</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" id="referralTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="referrals-tab" data-bs-toggle="tab" data-bs-target="#referrals" type="button" role="tab">
            <i class="bi bi-people me-2"></i>Toate Referrals (<?php echo count($allReferrals); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="earnings-tab" data-bs-toggle="tab" data-bs-target="#earnings" type="button" role="tab">
            <i class="bi bi-cash-stack me-2"></i>Comisioane (<?php echo count($allEarnings); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="withdrawals-tab" data-bs-toggle="tab" data-bs-target="#withdrawals" type="button" role="tab">
            <i class="bi bi-bank me-2"></i>Cereri Retragere (<?php echo $withdrawalStats['pending_requests']; ?> Pending)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
            <i class="bi bi-gear me-2"></i>Setări
        </button>
    </li>
</ul>

<!-- Tabs Content -->
<div class="tab-content" id="referralTabsContent">
    
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 1: TOATE REFERRALS -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade show active" id="referrals" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($allReferrals)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-3">Nu există referrals înregistrate încă.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Referrer (Cel care invită)</th>
                                    <th>Referred (Cel invitat)</th>
                                    <th>Data Creare</th>
                                    <th>Comision %</th>
                                    <th>Comenzi</th>
                                    <th>Total Comision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allReferrals as $ref): ?>
                                    <tr>
                                        <td><code>#<?php echo $ref['id']; ?></code></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ref['referrer_first_name'] . ' ' . $ref['referrer_last_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($ref['referrer_email']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ref['referred_first_name'] . ' ' . $ref['referred_last_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($ref['referred_email']); ?></small>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($ref['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format($ref['commission_percentage'], 0); ?>%</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $ref['orders_count']; ?> comenzi</span>
                                        </td>
                                        <td class="fw-bold text-success">
                                            <?php echo $ref['total_commission'] > 0 ? number_format($ref['total_commission'], 2) . ' RON' : '—'; ?>
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
    <!-- TAB 2: COMISIOANE (EARNINGS) -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="earnings" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($allEarnings)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-receipt text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-3">Nu există comisioane acordate încă.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Comandă</th>
                                    <th>Referrer</th>
                                    <th>Referred</th>
                                    <th>Valoare Comandă</th>
                                    <th>Comision Acordat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allEarnings as $earning): ?>
                                    <tr>
                                        <td><code>#<?php echo $earning['id']; ?></code></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($earning['created_at'])); ?></td>
                                        <td><code>#<?php echo htmlspecialchars($earning['order_number']); ?></code></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($earning['referrer_first_name'] . ' ' . $earning['referrer_last_name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($earning['referred_first_name'] . ' ' . $earning['referred_last_name']); ?>
                                        </td>
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
    </div>
    
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 3: CERERI RETRAGERE -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="withdrawals" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($allWithdrawals)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-3">Nu există cereri de retragere.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Utilizator</th>
                                    <th>Sumă</th>
                                    <th>IBAN</th>
                                    <th>Titular Cont</th>
                                    <th>Data Cerere</th>
                                    <th>Status</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allWithdrawals as $w): ?>
                                    <tr class="<?php echo $w['status'] === 'pending' ? 'table-warning' : ''; ?>">
                                        <td><code>#<?php echo $w['id']; ?></code></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($w['email']); ?></small>
                                        </td>
                                        <td class="fw-bold"><?php echo number_format($w['amount'], 2); ?> RON</td>
                                        <td><code><?php echo htmlspecialchars($w['bank_account_iban']); ?></code></td>
                                        <td><?php echo htmlspecialchars($w['bank_account_name']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($w['created_at'])); ?></td>
                                        <td>
                                            <?php if ($w['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php elseif ($w['status'] === 'approved'): ?>
                                                <span class="badge bg-success">Aprobat</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Respins</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($w['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $w['id']; ?>">
                                                    <i class="bi bi-check-circle"></i> Aprobă
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $w['id']; ?>">
                                                    <i class="bi bi-x-circle"></i> Respinge
                                                </button>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    Procesat de: <?php echo htmlspecialchars($w['admin_first_name'] . ' ' . $w['admin_last_name']); ?><br>
                                                    <?php echo date('d.m.Y', strtotime($w['processed_at'])); ?>
                                                    <?php if ($w['admin_note']): ?>
                                                        <br><em><?php echo htmlspecialchars($w['admin_note']); ?></em>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal Aprobare -->
                                    <div class="modal fade" id="approveModal<?php echo $w['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <input type="hidden" name="request_id" value="<?php echo $w['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <div class="modal-header bg-success text-white">
                                                        <h5 class="modal-title">Aprobă Retragere</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><strong>Confirmi aprobarea cererii de retragere?</strong></p>
                                                        <ul class="list-unstyled">
                                                            <li><strong>Utilizator:</strong> <?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name']); ?></li>
                                                            <li><strong>Sumă:</strong> <?php echo number_format($w['amount'], 2); ?> RON</li>
                                                            <li><strong>IBAN:</strong> <code><?php echo htmlspecialchars($w['bank_account_iban']); ?></code></li>
                                                            <li><strong>Titular:</strong> <?php echo htmlspecialchars($w['bank_account_name']); ?></li>
                                                        </ul>
                                                        <div class="mb-3">
                                                            <label for="admin_note_approve" class="form-label">Notă (Opțional - ex: Nr. tranzacție)</label>
                                                            <input type="text" class="form-control" name="admin_note" placeholder="Ex: Transfer ID12345">
                                                        </div>
                                                        <div class="alert alert-warning">
                                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                                            După aprobare, <?php echo number_format($w['amount'], 2); ?> RON vor fi scăzuți din creditul utilizatorului.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="bi bi-check-circle me-2"></i>Confirmă Aprobarea
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal Respingere -->
                                    <div class="modal fade" id="rejectModal<?php echo $w['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <input type="hidden" name="request_id" value="<?php echo $w['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Respinge Retragere</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><strong>Confirmi respingerea cererii?</strong></p>
                                                        <div class="mb-3">
                                                            <label for="admin_note_reject" class="form-label">Motiv Respingere *</label>
                                                            <textarea class="form-control" name="admin_note" rows="3" required placeholder="Ex: Date bancare incorecte"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="bi bi-x-circle me-2"></i>Confirmă Respingerea
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 3: SETĂRI -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="settings" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-4">Configurare Sistem Referral</h5>
                
                <?php
                // Obține setările curente
                $stmt = $db->prepare("SELECT * FROM referral_settings");
                $stmt->execute();
                $settingsData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                
                $settings = [];
                foreach ($settingsData as $row) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
                ?>
                
                <!-- Formular Editare Setări -->
                <form method="POST" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="commission_percentage" class="form-label">
                                <i class="bi bi-percent text-primary me-2"></i>Comision Procentual
                            </label>
                            <div class="input-group">
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="commission_percentage" 
                                    name="commission_percentage" 
                                    value="<?php echo number_format($settings['commission_percentage'] ?? 10, 2, '.', ''); ?>" 
                                    min="0" 
                                    max="100" 
                                    step="0.01" 
                                    required
                                >
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Procent acordat din fiecare comandă plătită (0-100%)</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="min_withdrawal_amount" class="form-label">
                                <i class="bi bi-cash-stack text-success me-2"></i>Sumă Minimă Retragere
                            </label>
                            <div class="input-group">
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="min_withdrawal_amount" 
                                    name="min_withdrawal_amount" 
                                    value="<?php echo number_format($settings['min_withdrawal_amount'] ?? 100, 2, '.', ''); ?>" 
                                    min="0" 
                                    step="0.01" 
                                    required
                                >
                                <span class="input-group-text">RON</span>
                            </div>
                            <small class="text-muted">Suma minimă pentru cerere de retragere bancară</small>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="update_settings" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Salvează Modificări
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Resetează
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <!-- Info Card -->
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="bi bi-info-circle me-2"></i>Informații Importante
                    </h6>
                    <ul class="mb-0 small">
                        <li><strong>Comisionul procentual</strong> se aplică la toate comenzile noi. Utilizatorii existenți păstrează procentul setat la momentul creării referral-ului.</li>
                        <li><strong>Suma minimă de retragere</strong> afectează toate cererile noi de retragere.</li>
                        <li>Modificările sunt aplicate imediat după salvare.</li>
                    </ul>
                </div>
                
                <!-- Statistici Sistem -->
                <h6 class="mt-4 mb-3">Statistici Sistem</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <tbody>
                            <tr>
                                <td width="30%"><strong>Comision Actual</strong></td>
                                <td width="20%">
                                    <span class="badge bg-primary"><?php echo number_format($settings['commission_percentage'] ?? 10, 2); ?>%</span>
                                </td>
                                <td><small class="text-muted">Procent aplicat pentru referral-uri noi</small></td>
                            </tr>
                            <tr>
                                <td><strong>Minim Retragere</strong></td>
                                <td>
                                    <span class="badge bg-success"><?php echo number_format($settings['min_withdrawal_amount'] ?? 100, 2); ?> RON</span>
                                </td>
                                <td><small class="text-muted">Suma minimă pentru retragere</small></td>
                            </tr>
                            <tr>
                                <td><strong>Sistem Activ</strong></td>
                                <td>
                                    <?php if (($settings['referral_enabled'] ?? '1') === '1'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>ACTIV</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>INACTIV</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted">Status general sistem referral</small></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
