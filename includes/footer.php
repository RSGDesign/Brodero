    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white pt-5 pb-3 mt-5">
        <div class="container">
            <div class="row g-4">
                <!-- Coloana 1: Despre & Social Media -->
                <div class="col-lg-3 col-md-6">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-flower3 text-primary"></i> Brodero
                    </h5>
                    <p class="text-white-50 small">
                        Brodero este destinația ta pentru design-uri de broderie unice și creative. 
                        Transformăm ideile în artă brodată.
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="<?php echo FACEBOOK_URL; ?>" target="_blank" class="text-white-50 fs-5" title="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="<?php echo INSTAGRAM_URL; ?>" target="_blank" class="text-white-50 fs-5" title="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        
                    </div>
                </div>
                
                <!-- Coloana 2: Link-uri Legale -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold mb-3">Informații Legale</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/pages/termeni.php" class="text-white-50 text-decoration-none">
                                Termeni și Condiții
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/pages/confidentialitate.php" class="text-white-50 text-decoration-none">
                                Politica de Confidențialitate
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/pages/cookie.php" class="text-white-50 text-decoration-none">
                                Politica Cookie
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/pages/retur.php" class="text-white-50 text-decoration-none">
                                Politica de Retur
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/pages/faq.php" class="text-white-50 text-decoration-none">
                                Întrebări Frecvente (FAQ)
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Coloana 3: Link-uri Site -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold mb-3">Navigare Rapidă</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>" class="text-white-50 text-decoration-none">
                                <i class="bi bi-house-door me-2"></i>Acasă
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/pages/despre.php" class="text-white-50 text-decoration-none">
                                <i class="bi bi-info-circle me-2"></i>Despre Noi
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="text-white-50 text-decoration-none">
                                <i class="bi bi-shop me-2"></i>Magazin
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="text-white-50 text-decoration-none">
                                <i class="bi bi-envelope me-2"></i>Contact
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Coloana 4: Newsletter -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold mb-3">Newsletter</h6>
                    <p class="text-white-50 small mb-3">
                        Abonează-te pentru a primi noutăți și oferte exclusive!
                    </p>
                    <form action="<?php echo SITE_URL; ?>/pages/newsletter.php" method="POST" class="newsletter-form needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="input-group mb-2">
                            <input type="email" 
                                   name="email" 
                                   class="form-control" 
                                   placeholder="Email-ul tău" 
                                   required 
                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                   title="Introducă o adresă de email validă">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i>
                            </button>
                            <div class="invalid-feedback">
                                Te rugăm să introduci un email valid.
                            </div>
                        </div>
                        <small class="text-white-50">
                            Nu vom împărtăși niciodată email-ul tău.
                        </small>
                    </form>
                </div>
            </div>
            
            <!-- Copyright -->
            <hr class="border-secondary my-4">
            <div class="text-center text-white-50 small">
                <p class="mb-0">
                    © 2022 - <?php echo date('Y'); ?> Toate drepturile rezervate. Brodero
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle (async pentru performanță) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    
    <!-- Custom JS (async) -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js" defer></script>
</body>
</html>
