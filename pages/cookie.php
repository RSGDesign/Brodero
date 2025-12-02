<?php
/**
 * Politica Cookie
 */

$pageTitle = "Politica Cookie";

require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="fw-bold mb-4">Politica Cookie</h1>
                <p class="text-muted mb-4">Ultima actualizare: <?php echo date('d.m.Y'); ?></p>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">Ce sunt Cookie-urile?</h4>
                    <p>Cookie-urile sunt fișiere text mici stocate pe dispozitivul dvs. de către browser-ul web atunci când vizitați un site. Acestea ajută site-ul să funcționeze mai eficient și să ofere o experiență personalizată.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">Cum Utilizăm Cookie-urile</h4>
                    <p>Brodero utilizează cookie-uri pentru:</p>
                    <ul>
                        <li><strong>Funcționalitate:</strong> Menținerea sesiunii de autentificare, salvarea preferințelor</li>
                        <li><strong>Performanță:</strong> Înțelegerea modului în care utilizați site-ul pentru îmbunătățiri</li>
                        <li><strong>Analiză:</strong> Colectarea de statistici anonime despre vizitatori</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">Tipuri de Cookie-uri Utilizate</h4>
                    
                    <h5 class="fw-bold mb-2">1. Cookie-uri Esențiale</h5>
                    <p>Necesare pentru funcționarea de bază a site-ului (autentificare, coș de cumpărături). Acestea nu pot fi dezactivate.</p>
                    
                    <h5 class="fw-bold mb-2 mt-4">2. Cookie-uri de Performanță</h5>
                    <p>Colectează informații despre modul în care utilizați site-ul pentru a ne ajuta să îl îmbunătățim.</p>
                    
                    <h5 class="fw-bold mb-2 mt-4">3. Cookie-uri de Funcționalitate</h5>
                    <p>Permit site-ului să rețină alegerile dvs. (limba, preferințe).</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">Gestionarea Cookie-urilor</h4>
                    <p>Puteți controla și/sau șterge cookie-urile după preferințe. Pentru detalii, vizitați <a href="https://www.aboutcookies.org" target="_blank">aboutcookies.org</a>.</p>
                    <p>Notă: Dezactivarea cookie-urilor poate afecta funcționalitatea site-ului.</p>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">Cookie-uri Terțe Părți</h4>
                    <p>Site-ul poate utiliza cookie-uri de la terțe părți pentru servicii precum:</p>
                    <ul>
                        <li>Google Analytics (analiză trafic)</li>
                        <li>Procesatori de plăți</li>
                        <li>Platforme de social media</li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h4 class="fw-bold mb-3">Contact</h4>
                    <p>Pentru întrebări despre utilizarea cookie-urilor, contactați-ne la <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
