<?php
/**
 * Pagina FAQ - Întrebări Frecvente
 */

$pageTitle = "Întrebări Frecvente (FAQ)";
$pageDescription = "Găsește răspunsuri la cele mai frecvente întrebări despre Brodero și produsele noastre.";

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<section class="bg-light py-4">
    <div class="container">
        <h1 class="h2 fw-bold mb-0">Întrebări Frecvente</h1>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="accordion" id="faqAccordion">
                    <!-- Despre Produse -->
                    <h4 class="fw-bold mb-3">Despre Produse</h4>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Ce formate de fișiere oferiți pentru design-urile de broderie?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Design-urile noastre sunt disponibile în formatele DST, PES, JEF, VP3, EXP și HUS. Acestea sunt compatibile cu majoritatea mașinilor de brodat disponibile pe piață.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Pot modifica design-urile după ce le cumpăr?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Da, poți modifica dimensiunile și culorile design-urilor folosind software de editare pentru broderie. Totuși, modificările extensive pot afecta calitatea finală.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comenzi și Plăți -->
                    <h4 class="fw-bold mb-3 mt-5">Comenzi și Plăți</h4>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Când pot descărca fișierele după achiziție?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Fișierele sunt disponibile imediat după finalizarea plății. Vei primi un email cu link-ul de descărcare și vei putea accesa fișierele din secțiunea "Contul Meu".
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Ce metode de plată acceptați?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Acceptăm plata cu card bancar (Visa, Mastercard), transfer bancar și PayPal. Toate tranzacțiile sunt securizate.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Suport Tehnic -->
                    <h4 class="fw-bold mb-3 mt-5">Suport Tehnic</h4>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Design-ul nu se încarcă corect în mașina mea. Ce pot face?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Verifică mai întâi dacă ai selectat formatul corect pentru mașina ta. Dacă problema persistă, contactează-ne la <?php echo SITE_EMAIL; ?> cu detalii despre mașina și formatul folosit.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                Oferiți asistență pentru brodare?
                            </button>
                        </h2>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Da! Echipa noastră poate oferi sfaturi despre setările optime, tipurile de fire și materiale recomandate pentru fiecare design. Contactează-ne pentru asistență personalizată.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Returnări și Rambursări -->
                    <h4 class="fw-bold mb-3 mt-5">Returnări și Rambursări</h4>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                Pot returna un design digital?
                            </button>
                        </h2>
                        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Datorită naturii digitale a produselor, nu putem accepta returnări după descărcarea fișierelor. Totuși, dacă există o problemă tehnică cu fișierul, vom lucra cu tine pentru a o rezolva.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Licențiere -->
                    <h4 class="fw-bold mb-3 mt-5">Licențiere și Utilizare</h4>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                                Pot vinde articolele brodate cu design-urile voastre?
                            </button>
                        </h2>
                        <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Da, licența de utilizare permite brodierea și vânzarea articolelor finite. Nu poți redistribui sau revinde fișierele digitale în sine.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                                Câte copii pot descărca?
                            </button>
                        </h2>
                        <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Poți descărca fișierele de maximum 5 ori. Recomandăm să salvezi o copie de siguranță imediat după prima descărcare.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Card -->
                <div class="card bg-light border-0 mt-5">
                    <div class="card-body p-4 text-center">
                        <h5 class="fw-bold mb-3">Nu ai găsit răspunsul?</h5>
                        <p class="text-muted mb-4">Echipa noastră este aici pentru a te ajuta!</p>
                        <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="btn btn-primary">
                            <i class="bi bi-envelope me-2"></i>Contactează-ne
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
