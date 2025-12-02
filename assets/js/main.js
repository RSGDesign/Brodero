/**
 * Brodero - JavaScript Principal
 * Funcționalități interactive pentru site
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-hide alerts după 5 secunde
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Smooth scroll pentru link-uri anchor
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Active navigation link
    const currentLocation = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentLocation) {
            link.classList.add('active');
        }
    });
    
    // Validare formular contact înainte de submit
    const contactForm = document.querySelector('.contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            const fileInput = this.querySelector('input[type="file"]');
            if (fileInput && fileInput.files.length > 0) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const file = fileInput.files[0];
                
                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('Fișierul este prea mare! Dimensiunea maximă este 5MB.');
                    return false;
                }
            }
        });
    }
    
    // Validare formular newsletter
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value.trim();
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('Te rugăm să introduci o adresă de email validă.');
                return false;
            }
        });
    }
    
    // Funcție validare email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Confirmare pentru ștergere
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Ești sigur că vrei să ștergi acest element?')) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Preview imagine înainte de upload
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('#image-preview');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Lazy loading pentru imagini
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
    
    // Tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Animații la scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const animatedElements = document.querySelectorAll('.card, .feature-icon, .section-title');
    animatedElements.forEach(el => observer.observe(el));
    
});

// Funcție pentru adăugare în coș (va fi folosită în pagina magazin)
function addToCart(productId) {
    fetch('/pages/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizare număr coș
            const cartCount = document.querySelector('.badge.rounded-pill');
            if (cartCount) {
                cartCount.textContent = data.cartCount;
            }
            
            // Afișare mesaj succes
            showNotification('Produs adăugat în coș!', 'success');
        } else {
            showNotification(data.message || 'Eroare la adăugarea în coș!', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Eroare la adăugarea în coș!', 'danger');
    });
}

// Funcție pentru afișare notificări
function showNotification(message, type = 'success') {
    const notificationHTML = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
             style="z-index: 9999; min-width: 300px;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('afterbegin', notificationHTML);
    
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 3000);
}
