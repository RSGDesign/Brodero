<!-- Cookie Consent Banner - MVP Minimal -->
<style>
#cookieConsentBanner {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.95);
    color: white;
    padding: 20px;
    z-index: 9999;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
}

#cookieConsentBanner.show {
    display: block;
}

.cookie-consent-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.cookie-consent-text {
    flex: 1;
    min-width: 250px;
}

.cookie-consent-text p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
}

.cookie-consent-buttons {
    display: flex;
    gap: 10px;
}

.cookie-consent-btn {
    padding: 10px 30px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
}

.cookie-consent-btn.accept {
    background: #28a745;
    color: white;
}

.cookie-consent-btn.accept:hover {
    background: #218838;
}

.cookie-consent-btn.deny {
    background: #6c757d;
    color: white;
}

.cookie-consent-btn.deny:hover {
    background: #5a6268;
}

@media (max-width: 768px) {
    .cookie-consent-content {
        flex-direction: column;
        text-align: center;
    }
    
    .cookie-consent-buttons {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div id="cookieConsentBanner">
    <div class="cookie-consent-content">
        <div class="cookie-consent-text">
            <p>
                🍪 Folosim cookies pentru a îmbunătăți experiența ta pe site și pentru a analiza traficul. 
                Datele sunt anonimizate și procesate conform GDPR.
            </p>
        </div>
        <div class="cookie-consent-buttons">
            <button class="cookie-consent-btn deny" onclick="cookieConsent.deny()">Refuză</button>
            <button class="cookie-consent-btn accept" onclick="cookieConsent.accept()">Accept</button>
        </div>
    </div>
</div>

<script>
/**
 * Cookie Consent Manager - MVP
 */
const cookieConsent = {
    cookieName: 'cookie_consent',
    cookieExpireDays: 365,
    
    /**
     * Check if consent has been given
     */
    hasConsent: function() {
        return this.getCookie(this.cookieName) !== null;
    },
    
    /**
     * Get consent status
     */
    getConsentStatus: function() {
        return this.getCookie(this.cookieName);
    },
    
    /**
     * Show banner if no consent yet
     */
    init: function() {
        if (!this.hasConsent()) {
            this.showBanner();
        } else if (this.getConsentStatus() === 'granted') {
            // Update GA4 consent if already granted
            this.updateGAConsent('granted');
        }
    },
    
    /**
     * Show consent banner
     */
    showBanner: function() {
        const banner = document.getElementById('cookieConsentBanner');
        if (banner) {
            banner.classList.add('show');
        }
    },
    
    /**
     * Hide consent banner
     */
    hideBanner: function() {
        const banner = document.getElementById('cookieConsentBanner');
        if (banner) {
            banner.classList.remove('show');
        }
    },
    
    /**
     * User accepts cookies
     */
    accept: function() {
        this.setCookie(this.cookieName, 'granted', this.cookieExpireDays);
        this.hideBanner();
        this.updateGAConsent('granted');
        
        // Reload page to load GA4
        window.location.reload();
    },
    
    /**
     * User denies cookies
     */
    deny: function() {
        this.setCookie(this.cookieName, 'denied', this.cookieExpireDays);
        this.hideBanner();
        this.updateGAConsent('denied');
    },
    
    /**
     * Update GA4 consent status
     */
    updateGAConsent: function(status) {
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'analytics_storage': status
            });
        }
    },
    
    /**
     * Set cookie
     */
    setCookie: function(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
    },
    
    /**
     * Get cookie value
     */
    getCookie: function(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    cookieConsent.init();
});
</script>
