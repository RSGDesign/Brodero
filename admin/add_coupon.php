<?php
/**
 * Admin - Adăugare Cupon Nou
 * Formular creare cupon cu validări
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare acces admin (ÎNAINTE de orice output)
if (!isAdmin()) {
    header("Location: " . SITE_URL . "/pages/login.php");
    exit();
}

$db = getDB();

// Handle POST (ÎNAINTE de header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discountType = $_POST['discount_type'] ?? 'percent';
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $minOrderAmount = (float)($_POST['min_order_amount'] ?? 0);
    $maxUses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    
    // Validări
    if (empty($code)) {
        $errors[] = "Codul cuponului este obligatoriu.";
    } elseif (!preg_match('/^[A-Z0-9]+$/', $code)) {
        $errors[] = "Codul poate conține doar litere mari și cifre.";
    } else {
        // Verifică duplicat
        $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Acest cod de cupon există deja.";
        }
    }
    
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
    
    // Inserare în DB
    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_uses, expires_at, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("ssddisd", $code, $discountType, $discountValue, $minOrderAmount, $maxUses, $expiresAt, $isActive);
        
        if ($stmt->execute()) {
            setMessage("Cupon adăugat cu succes!", "success");
            redirect('/admin/admin_coupons.php');
        } else {
            $errors[] = "Eroare la salvare în baza de date.";
        }
    }
}

// Include header DUPĂ procesarea POST
$pageTitle = "Adaugă Cupon";
if (file_exists(__DIR__ . '/../includes/admin_header.php')) {
    require_once __DIR__ . '/../includes/admin_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-plus-circle me-2"></i>Adaugă Cupon Nou
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
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="code" class="form-label">Cod Cupon *</label>
                                <input type="text" class="form-control text-uppercase" id="code" name="code" 
                                       value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>" 
                                       pattern="[A-Z0-9]+" maxlength="20" required>
                                <div class="form-text">Doar litere mari și cifre (ex: WELCOME10)</div>
                                <div class="invalid-feedback">Introdu un cod valid.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="discount_type" class="form-label">Tip Discount *</label>
                                <select class="form-select" id="discount_type" name="discount_type" required>
                                    <option value="percent" <?php echo ($_POST['discount_type'] ?? '') === 'percent' ? 'selected' : ''; ?>>
                                        Procent (%)
                                    </option>
                                    <option value="fixed" <?php echo ($_POST['discount_type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>
                                        Fix (LEI)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="discount_value" class="form-label">Valoare Discount *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="discount_value" name="discount_value" 
                                           value="<?php echo htmlspecialchars($_POST['discount_value'] ?? ''); ?>" 
                                           step="0.01" min="0.01" required>
                                    <span class="input-group-text discount-unit">%</span>
                                </div>
                                <div class="form-text">Pentru procent: 1-100. Pentru fix: sumă în LEI</div>
                                <div class="invalid-feedback">Introdu o valoare validă.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="min_order_amount" class="form-label">Sumă Minimă Comandă (LEI)</label>
                                <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" 
                                       value="<?php echo htmlspecialchars($_POST['min_order_amount'] ?? '0'); ?>" 
                                       step="0.01" min="0">
                                <div class="form-text">0 = fără restricție</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="max_uses" class="form-label">Număr Maxim Utilizări</label>
                                <input type="number" class="form-control" id="max_uses" name="max_uses" 
                                       value="<?php echo htmlspecialchars($_POST['max_uses'] ?? ''); ?>" 
                                       min="1">
                                <div class="form-text">Lasă gol pentru utilizări nelimitate</div>
                            </div>
                            <div class="col-md-6">
                                <label for="expires_at" class="form-label">Data Expirare</label>
                                <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" 
                                       value="<?php echo htmlspecialchars($_POST['expires_at'] ?? ''); ?>">
                                <div class="form-text">Lasă gol pentru fără expirare</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       <?php echo isset($_POST['is_active']) || !isset($_POST['code']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    <strong>Activ</strong> (cuponul poate fi folosit)
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Exemple:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>WELCOME10:</strong> 10% reducere pentru comenzi peste 50 LEI</li>
                                <li><strong>SAVE20:</strong> 20 LEI reducere fix pentru comenzi peste 100 LEI</li>
                                <li><strong>FIRSTORDER:</strong> 15% reducere, fără restricții</li>
                            </ul>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Salvează Cupon
                            </button>
                            <a href="/admin/admin_coupons.php" class="btn btn-secondary">
                                Anulează
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-uppercase pentru cod
document.getElementById('code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Schimbă unitatea în funcție de tip discount
document.getElementById('discount_type').addEventListener('change', function() {
    const unit = document.querySelector('.discount-unit');
    const valueInput = document.getElementById('discount_value');
    
    if (this.value === 'percent') {
        unit.textContent = '%';
        valueInput.max = '100';
        valueInput.placeholder = '1-100';
    } else {
        unit.textContent = 'LEI';
        valueInput.removeAttribute('max');
        valueInput.placeholder = 'Sumă în LEI';
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
