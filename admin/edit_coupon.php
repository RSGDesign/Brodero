<?php
/**
 * Admin - Editare Cupon
 * Formular editare cupon cu statistici utilizare
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare acces admin
if (!isAdmin()) {
    redirect('/pages/login.php');
}

$pageTitle = "Editare Cupon";
// Fallback la header standard dacă admin_header lipsește
if (file_exists(__DIR__ . '/../includes/admin_header.php')) {
    require_once __DIR__ . '/../includes/admin_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

$db = getDB();
$couponId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Preia cuponul
$stmt = $db->prepare("SELECT * FROM coupons WHERE id = ?");
$stmt->bind_param("i", $couponId);
$stmt->execute();
$coupon = $stmt->get_result()->fetch_assoc();

if (!$coupon) {
    setMessage("Cupon nu a fost găsit.", "danger");
    redirect('/admin/admin_coupons.php');
}

// Statistici utilizare
$stmt = $db->prepare("SELECT COUNT(*) as total_orders, SUM(discount_amount) as total_discount FROM orders WHERE coupon_code = ?");
$stmt->bind_param("s", $coupon['code']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Comenzi recente cu acest cupon
$stmt = $db->prepare("
    SELECT order_number, customer_name, total_amount, discount_amount, created_at 
    FROM orders 
    WHERE coupon_code = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->bind_param("s", $coupon['code']);
$stmt->execute();
$recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discountType = $_POST['discount_type'] ?? 'percent';
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $minOrderAmount = (float)($_POST['min_order_amount'] ?? 0);
    $maxUses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    
    // Validări
    if (!in_array($discountType, ['percent', 'fixed'])) {
        $errors[] = "Tip discount invalid.";
    }
    
    if ($discountValue <= 0) {
        $errors[] = "Valoarea discount-ului trebuie să fie mai mare de 0.";
    }
    
    if ($discountType === 'percent' && $discountValue > 100) {
        $errors[] = "Discount-ul procentual nu poate fi mai mare de 100%.";
    }
    
    if ($minOrderAmount < 0) {
        $errors[] = "Suma minimă comandă nu poate fi negativă.";
    }
    
    if ($maxUses !== null && $maxUses <= 0) {
        $errors[] = "Numărul maxim de utilizări trebuie să fie mai mare de 0.";
    }
    
    if ($expiresAt && strtotime($expiresAt) < time()) {
        $errors[] = "Data expirării nu poate fi în trecut.";
    }
    
    // Update în DB
    if (empty($errors)) {
        $stmt = $db->prepare("
            UPDATE coupons 
            SET discount_type = ?, discount_value = ?, min_order_amount = ?, max_uses = ?, expires_at = ?, is_active = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param("sddisdi", $discountType, $discountValue, $minOrderAmount, $maxUses, $expiresAt, $isActive, $couponId);
        
        if ($stmt->execute()) {
            setMessage("Cupon actualizat cu succes!", "success");
            redirect('/admin/admin_coupons.php');
        } else {
            $errors[] = "Eroare la actualizare în baza de date.";
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-pencil me-2"></i>Editare Cupon: <?php echo htmlspecialchars($coupon['code']); ?>
                </h1>
                <a href="/admin/admin_coupons.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Înapoi
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <h6 class="alert-heading">Erori de validare:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Cod Cupon</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($coupon['code']); ?>" disabled>
                            <div class="form-text">Codul nu poate fi modificat după creare.</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="discount_type" class="form-label">Tip Discount *</label>
                                <select class="form-select" id="discount_type" name="discount_type" required>
                                    <option value="percent" <?php echo $coupon['discount_type'] === 'percent' ? 'selected' : ''; ?>>
                                        Procent (%)
                                    </option>
                                    <option value="fixed" <?php echo $coupon['discount_type'] === 'fixed' ? 'selected' : ''; ?>>
                                        Fix (LEI)
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="discount_value" class="form-label">Valoare Discount *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="discount_value" name="discount_value" 
                                           value="<?php echo $coupon['discount_value']; ?>" 
                                           step="0.01" min="0.01" required>
                                    <span class="input-group-text discount-unit">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="min_order_amount" class="form-label">Sumă Minimă Comandă (LEI)</label>
                                <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" 
                                       value="<?php echo $coupon['min_order_amount']; ?>" 
                                       step="0.01" min="0">
                            </div>
                            <div class="col-md-6">
                                <label for="max_uses" class="form-label">Număr Maxim Utilizări</label>
                                <input type="number" class="form-control" id="max_uses" name="max_uses" 
                                       value="<?php echo $coupon['max_uses']; ?>" 
                                       min="1">
                                <div class="form-text">
                                    Folosit: <?php echo $coupon['used_count']; ?> 
                                    <?php if ($coupon['max_uses']): ?>
                                        / <?php echo $coupon['max_uses']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="expires_at" class="form-label">Data Expirare</label>
                            <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" 
                                   value="<?php echo $coupon['expires_at'] ? date('Y-m-d\TH:i', strtotime($coupon['expires_at'])) : ''; ?>">
                            <?php if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()): ?>
                                <div class="form-text text-danger">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Acest cupon a expirat!
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       <?php echo $coupon['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    <strong>Activ</strong> (cuponul poate fi folosit)
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Salvează Modificări
                            </button>
                            <a href="/admin/admin_coupons.php" class="btn btn-secondary">
                                Anulează
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Statistici -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Statistici Utilizare</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Total Comenzi</small>
                        <h4 class="mb-0"><?php echo $stats['total_orders'] ?? 0; ?></h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Reducere Totală Acordată</small>
                        <h4 class="mb-0 text-success">
                            <?php echo number_format($stats['total_discount'] ?? 0, 2); ?> LEI
                        </h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Utilizări Rămase</small>
                        <h4 class="mb-0">
                            <?php 
                            if ($coupon['max_uses']) {
                                $remaining = $coupon['max_uses'] - $coupon['used_count'];
                                echo $remaining > 0 ? $remaining : '<span class="text-danger">0</span>';
                            } else {
                                echo '<i class="bi bi-infinity"></i>';
                            }
                            ?>
                        </h4>
                    </div>
                    <div>
                        <small class="text-muted">Status</small>
                        <div class="mt-1">
                            <?php if (!$coupon['is_active']): ?>
                                <span class="badge bg-secondary">Inactiv</span>
                            <?php elseif ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()): ?>
                                <span class="badge bg-danger">Expirat</span>
                            <?php else: ?>
                                <span class="badge bg-success">Activ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comenzi Recente -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Comenzi Recente</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentOrders)): ?>
                        <div class="p-3 text-center text-muted">
                            <small>Nicio comandă încă</small>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <small class="text-muted">#<?php echo htmlspecialchars($order['order_number']); ?></small>
                                            <div class="fw-bold"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-success fw-bold">
                                                -<?php echo number_format($order['discount_amount'], 2); ?> LEI
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y', strtotime($order['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Schimbă unitatea în funcție de tip discount
document.getElementById('discount_type').addEventListener('change', function() {
    const unit = document.querySelector('.discount-unit');
    const valueInput = document.getElementById('discount_value');
    
    if (this.value === 'percent') {
        unit.textContent = '%';
        valueInput.max = '100';
    } else {
        unit.textContent = 'LEI';
        valueInput.removeAttribute('max');
    }
});

// Trigger change pentru inițializare
document.getElementById('discount_type').dispatchEvent(new Event('change'));

// Bootstrap validation
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php 
if (file_exists(__DIR__ . '/../includes/admin_footer.php')) {
    require_once __DIR__ . '/../includes/admin_footer.php';
} else {
    require_once __DIR__ . '/../includes/footer.php';
}
?>
