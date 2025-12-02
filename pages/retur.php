<?php
/**
 * Politica de Retur
 */

$pageTitle = "Politica de Retur";

require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="fw-bold mb-4">Politica de Retur</h1>
                <p class="text-muted mb-4">Ultima actualizare: <?php echo date('d.m.Y'); ?></p>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">1. Produse Digitale - Politica Generală</h4>
                    <p>Datorită naturii digitale a produselor noastre (design-uri de broderie descărcabile), <strong>nu putem accepta returnări sau rambursări după ce fișierele au fost descărcate</strong>.</p>
                    <p>Această politică este în conformitate cu legislația privind protecția consumatorilor pentru conținut digital.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">2. Excepții - Probleme Tehnice</h4>
                    <p>Vom oferi rambursare sau înlocuire în următoarele situații:</p>
                    <ul>
                        <li><strong>Fișierul este corupt sau deteriorat</strong> și nu poate fi utilizat</li>
                        <li><strong>Formatul descărcat este incorect</strong> față de cel specificat</li>
                        <li><strong>Fișierul lipsește din pachetul descărcat</strong></li>
                        <li><strong>Eroare tehnică</strong> care împiedică descărcarea</li>
                    </ul>
                    <p class="text-danger"><strong>Important:</strong> Problemele tehnice trebuie raportate în termen de <strong>7 zile</strong> de la data achiziției.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">3. Procesul de Reclamație</h4>
                    <p>Pentru a raporta o problemă tehnică, urmați acești pași:</p>
                    <ol>
                        <li>Contactați-ne la <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></li>
                        <li>Includeți numărul comenzii și descrierea problemei</li>
                        <li>Atașați capturi de ecran ale erorii (dacă este cazul)</li>
                        <li>Specificați mașina de brodat și software-ul folosit</li>
                    </ol>
                    <p>Vom răspunde în termen de <strong>24-48 ore lucrătoare</strong>.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">4. Soluții Oferite</h4>
                    <p>În funcție de situație, vom oferi:</p>
                    <ul>
                        <li><strong>Re-trimiterea fișierelor</strong> în format corect</li>
                        <li><strong>Asistență tehnică</strong> pentru încărcarea în mașina de brodat</li>
                        <li><strong>Înlocuirea design-ului</strong> cu unul similar</li>
                        <li><strong>Rambursare completă</strong> în cazuri justificate</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">5. Situații în Care NU Oferim Rambursare</h4>
                    <ul>
                        <li>Schimbarea opiniei după descărcare</li>
                        <li>Dificultăți în utilizarea mașinii de brodat (nu legate de fișier)</li>
                        <li>Rezultate finale nesatisfăcătoare ale broderii (calitatea depinde de mașină, fire, material)</li>
                        <li>Pierderea fișierelor după descărcare</li>
                        <li>Incompatibilitate cu software-ul terț utilizat</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">6. Dreptul de Retractare</h4>
                    <p>Conform legislației privind comerțul electronic, <strong>dreptul de retractare de 14 zile nu se aplică produselor digitale</strong> după ce acestea au fost descărcate cu acordul expres al consumatorului.</p>
                    <p>Prin finalizarea comenzii, confirmați că înțelegeți și acceptați această condiție.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">7. Rambursări</h4>
                    <p>Dacă rambursarea este aprobată:</p>
                    <ul>
                        <li>Procesarea se face în <strong>5-10 zile lucrătoare</strong></li>
                        <li>Suma este returnată prin <strong>aceeași metodă de plată</strong> folosită la achiziție</li>
                        <li>Veți primi confirmare prin email</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">8. Contact</h4>
                    <p>Pentru întrebări despre politica de retur sau pentru a raporta o problemă:</p>
                    <ul>
                        <li>Email: <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></li>
                        <li>Telefon: <a href="tel:<?php echo SITE_PHONE; ?>"><?php echo SITE_PHONE; ?></a></li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Sfat:</strong> Înainte de achiziție, verificați cu atenție descrierea produsului, formatele disponibile și cerințele tehnice pentru a evita problemele.
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
