/**
 * Lazy Loading Implementation for Images
 * Improves LCP and reduces initial page load
 */

// Intersection Observer for lazy loading
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                
                // Load the image
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                }
                if (img.dataset.srcset) {
                    img.srcset = img.dataset.srcset;
                }
                
                // Add loaded class for fade-in effect
                img.classList.add('loaded');
                
                // Stop observing this image
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px 0px', // Start loading 50px before image enters viewport
        threshold: 0.01
    });

    // Observe all lazy images
    document.addEventListener('DOMContentLoaded', () => {
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    });
} else {
    // Fallback for browsers without IntersectionObserver
    document.addEventListener('DOMContentLoaded', () => {
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        lazyImages.forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
            }
            if (img.dataset.srcset) {
                img.srcset = img.dataset.srcset;
            }
            img.classList.add('loaded');
        });
    });
}

// Native lazy loading support check and enhancement
if ('loading' in HTMLImageElement.prototype) {
    // Browser supports native lazy loading
    const images = document.querySelectorAll('img[loading="lazy"]');
    images.forEach(img => {
        img.classList.add('loaded');
    });
}
