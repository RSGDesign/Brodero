<?php
/**
 * Payment Instructions Page
 * Instrucțiuni plată transfer bancar
 */

$pageTitle = "Instrucțiuni Plată";

$orderNumber = $_GET['order'] ?? '';

if (empty($orderNumber)) {
    setMessage("Comandă invalidă.", "danger");
    redirect('/');
}

$db = getDB();
$stmt = $db->prepare("
    SELECT * FROM orders 
    WHERE order_number = ? AND payment_method = 'bank_transfer'
");
$stmt->bind_param("s", $orderNumber);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Dacă totalul este 0, marchează comanda ca plătită și finalizată automat
if (!$order) {
    setMessage("Comandă nu a fost găsită.", "danger");
    redirect('/');
}

// Pentru comenzi cu total 0: finalizează automat și redirect înainte de orice output
if (isset($order['total_amount']) && (float)$order['total_amount'] <= 0) {
    $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid', status = 'completed' WHERE order_number = ?");
    if ($stmt) {
        $stmt->bind_param("s", $orderNumber);
        $stmt->execute();
    }
    setMessage("Comanda cu total 0 a fost finalizată automat.", "success");
    redirect('/pages/payment_success.php?order=' . urlencode($orderNumber));
}

// Abia acum includem header-ul, după ce validările/redirect-urile au avut loc
require_once __DIR__ . '/../includes/header.php';
// Trimite email cu instrucțiuni (opțional - necesită configurare SMTP)
// sendBankTransferEmail($order['customer_email'], $orderNumber, $order['total_amount']);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-check-circle-fill me-2"></i>Comandă Înregistrată</h4>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-success mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        Comanda ta <strong>#<?php echo htmlspecialchars($orderNumber); ?></strong> a fost înregistrată cu succes!
                    </div>

                    <h5 class="mb-3"><i class="bi bi-bank me-2"></i>Instrucțiuni Plată Transfer Bancar</h5>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                    <?php if (isset($order['total_amount']) && (float)$order['total_amount'] <= 0): ?>
                        <?php
                        $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid', status = 'completed' WHERE order_number = ?");
                        if ($stmt) {
                            $stmt->bind_param("s", $orderNumber);
                            $stmt->execute();
                        }
                        setMessage("Comanda cu total 0 a fost finalizată automat.", "success");
                        redirect('/pages/payment_success.php?order=' . urlencode($orderNumber));
                        ?>
                    <?php endif; ?>
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="fw-bold" style="width: 150px;">Beneficiar:</td>
                                    <td>Brodero SRL</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">IBAN:</td>
                                    <td class="text-primary">
                                        <strong>RO12 BTRL 0000 1234 5678 901</strong>
                                        <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyIBAN()">
                                            <i class="bi bi-clipboard"></i> Copiază
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Banca:</td>
                                    <td>Banca Transilvania</td>
                                </tr>
                            <td><?php echo htmlspecialchars($order['customer_name'] ?? '—'); ?></td>
                                    <td class="fw-bold">Sumă:</td>
                                    <td class="text-danger fs-5">
                                        <strong><?php echo number_format($order['total_amount'], 2); ?> LEI</strong>
                            <td><?php echo htmlspecialchars($order['customer_email'] ?? '—'); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Referință:</td>
                            <td><?php echo htmlspecialchars($order['customer_phone'] ?? '—'); ?></td>
                                        <strong>Comanda #<?php echo htmlspecialchars($orderNumber); ?></strong>
                                        <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyReference()">
                                            <i class="bi bi-clipboard"></i> Copiază
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                            <td>-<?php echo number_format((float)($order['discount_amount'] ?? 0), 2); ?> LEI</td>

                    <div class="alert alert-warning">
                        <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Important!</h6>
                        <ul class="mb-0">
                            <li>Menționează <strong>numărul comenzii</strong> în detaliile transferului</li>
                            <li>Comanda va fi procesată după confirmarea plății (1-3 zile lucrătoare)</li>
                            <li>Vei primi un email de confirmare cu link-urile de download</li>
                        </ul>
                    </div>

                    <h6 class="mb-3">Detalii Comandă</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Număr Comandă:</td>
                            <td class="fw-bold"><?php echo htmlspecialchars($orderNumber); ?></td>
                        </tr>
                        <tr>
                            <td>Client:</td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        </tr>
                        <tr>
                            <td>Email:</td>
                            <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                        </tr>
                        <tr>
                            <td>Telefon:</td>
                            <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                        </tr>
                        <?php if ($order['subtotal'] != $order['total_amount']): ?>
                        <tr>
                            <td>Subtotal:</td>
                            <td><?php echo number_format($order['subtotal'], 2); ?> LEI</td>
                        </tr>
                        <tr class="text-success">
                            <td>Reducere:</td>
                            <td>-<?php echo number_format($order['discount_amount'], 2); ?> LEI</td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-active">
                            <td class="fw-bold">Total:</td>
                            <td class="fw-bold text-primary"><?php echo number_format($order['total_amount'], 2); ?> LEI</td>
                        </tr>
                    </table>

                    <div class="d-flex gap-2 mt-4">
                        <a href="/" class="btn btn-primary flex-fill">
                            <i class="bi bi-house-fill me-2"></i>Înapoi la Magazin
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="bi bi-printer me-2"></i>Printează
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyIBAN() {
    navigator.clipboard.writeText('RO12BTRL0000123456789901');
    alert('IBAN copiat în clipboard!');
}

function copyReference() {
    navigator.clipboard.writeText('Comanda #<?php echo htmlspecialchars($orderNumber); ?>');
    alert('Referință copiată în clipboard!');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
