<?php
/**
 * Politica de Confidențialitate
 */

$pageTitle = "Politica de Confidențialitate";

require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="fw-bold mb-4">Politica de Confidențialitate</h1>
                <p class="text-muted mb-4">Ultima actualizare: <?php echo date('d.m.Y'); ?></p>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">1. Introducere</h4>
                    <p>Brodero respectă confidențialitatea datelor personale ale utilizatorilor. Această politică descrie modul în care colectăm, utilizăm și protejăm informațiile dvs.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">2. Informații Colectate</h4>
                    <p>Colectăm următoarele tipuri de informații:</p>
                    <ul>
                        <li><strong>Informații de cont:</strong> nume, prenume, email, telefon</li>
                        <li><strong>Informații de plată:</strong> detalii despre tranzacții (nu stocăm date cardului)</li>
                        <li><strong>Informații de utilizare:</strong> pagini vizitate, produse vizualizate, IP</li>
                        <li><strong>Cookie-uri:</strong> pentru funcționalitate și analiză</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">3. Utilizarea Informațiilor</h4>
                    <p>Utilizăm informațiile dvs. pentru:</p>
                    <ul>
                        <li>Procesarea comenzilor și livrarea produselor digitale</li>
                        <li>Comunicarea cu dvs. despre comenzi și produse</li>
                        <li>Îmbunătățirea serviciilor noastre</li>
                        <li>Trimiterea de newsletter-e (doar dacă v-ați abonat)</li>
                        <li>Prevenirea fraudei și securizarea site-ului</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">4. Partajarea Informațiilor</h4>
                    <p>Nu vindem, închiriem sau partajăm informațiile dvs. personale cu terțe părți, cu excepția:</p>
                    <ul>
                        <li>Procesatorilor de plăți (pentru finalizarea tranzacțiilor)</li>
                        <li>Furnizorilor de servicii de email (pentru comunicare)</li>
                        <li>Autorităților, când este necesar legal</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">5. Securitatea Datelor</h4>
                    <p>Implementăm măsuri de securitate pentru protejarea datelor dvs.:</p>
                    <ul>
                        <li>Criptare SSL pentru toate tranzacțiile</li>
                        <li>Parole criptate în baza de date</li>
                        <li>Acces restricționat la informații personale</li>
                        <li>Backup-uri regulate ale datelor</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">6. Drepturile Dvs.</h4>
                    <p>Conform GDPR, aveți următoarele drepturi:</p>
                    <ul>
                        <li>Dreptul de acces la datele personale</li>
                        <li>Dreptul de rectificare a datelor incorecte</li>
                        <li>Dreptul de ștergere a datelor ("dreptul de a fi uitat")</li>
                        <li>Dreptul de a restricționa prelucrarea</li>
                        <li>Dreptul la portabilitatea datelor</li>
                        <li>Dreptul de a vă opune prelucrării</li>
                    </ul>
                    <p>Pentru exercitarea acestor drepturi, contactați-ne la <?php echo SITE_EMAIL; ?></p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">7. Cookie-uri</h4>
                    <p>Utilizăm cookie-uri pentru:</p>
                    <ul>
                        <li>Menținerea sesiunii de autentificare</li>
                        <li>Salvarea preferințelor utilizatorului</li>
                        <li>Analizarea traficului pe site</li>
                    </ul>
                    <p>Puteți dezactiva cookie-urile din setările browser-ului, dar acest lucru poate afecta funcționalitatea site-ului.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">8. Modificări ale Politicii</h4>
                    <p>Ne rezervăm dreptul de a actualiza această politică. Modificările vor fi publicate pe această pagină cu data actualizării.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">9. Contact</h4>
                    <p>Pentru întrebări despre această politică, contactați-ne:</p>
                    <ul>
                        <li>Email: <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></li>
                        <li>Telefon: <a href="tel:<?php echo SITE_PHONE; ?>"><?php echo SITE_PHONE; ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
