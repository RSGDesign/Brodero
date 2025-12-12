<?php
/**
 * Termeni și Condiții
 */

$pageTitle = "Termeni și Condiții";

require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="fw-bold mb-4">Termeni și Condiții</h1>
                <p class="text-muted mb-4">Ultima actualizare: <?php echo date('d.m.Y'); ?></p>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">1. Acceptarea Termenilor</h4>
                    <p>Prin accesarea și utilizarea site-ului Brodero, acceptați să respectați acești termeni și condiții. Dacă nu sunteți de acord cu acești termeni, vă rugăm să nu utilizați serviciile noastre.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">2. Descrierea Serviciilor</h4>
                    <p>Brodero oferă design-uri digitale de broderie pentru achiziție și descărcare. Toate produsele sunt digitale și nu există produse fizice expediate.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">3. Licență de Utilizare</h4>
                    <p>Prin achiziționarea unui design, primiți o licență non-exclusivă de utilizare personală sau comercială. Aveți dreptul să:</p>
                    <ul>
                        <li>Utilizați design-urile pentru brodare pe articole personale sau comerciale</li>
                        <li>Vindeți articolele brodate folosind design-urile noastre</li>
                    </ul>
                    <p>Nu aveți dreptul să:</p>
                    <ul>
                        <li>Redistribuiți, vindeți sau oferiți gratuit fișierele digitale</li>
                        <li>Pretindeți că design-urile sunt create de dvs.</li>
                        <li>Modificați și vindeți design-urile ca fiind noi design-uri digitale</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">4. Plăți și Facturare</h4>
                    <p>Toate prețurile sunt afișate în RON (lei românești) și includ TVA. Plata se procesează imediat la finalizarea comenzii. Acceptăm carduri bancare, transfer bancar și PayPal.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">5. Politica de Returnare</h4>
                    <p>Datorită naturii digitale a produselor, nu acceptăm returnări după ce fișierele au fost descărcate. Dacă există o problemă tehnică cu fișierul, vă rugăm să ne contactați în termen de 7 zile.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">6. Proprietate Intelectuală</h4>
                    <p>Toate design-urile și conținutul site-ului sunt protejate de drepturile de autor. Brodero deține toate drepturile asupra design-urilor vândute, cu excepția drepturilor de utilizare acordate prin licență.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">7. Limitarea Răspunderii</h4>
                    <p>Brodero nu este responsabil pentru:</p>
                    <ul>
                        <li>Probleme tehnice ale mașinilor de brodat</li>
                        <li>Rezultate finale ale broderii (calitatea depinde de mașină, fire, material)</li>
                        <li>Pierderea fișierelor după descărcare</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">8. Modificări ale Termenilor</h4>
                    <p>Ne rezervăm dreptul de a modifica acești termeni în orice moment. Modificările intră în vigoare imediat după publicare pe site.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">9. Contact</h4>
                    <p>Pentru întrebări despre acești termeni, contactați-ne la: <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
