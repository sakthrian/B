document.addEventListener('DOMContentLoaded', () => {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetSelector = this.getAttribute('href');
            const targetElement = document.querySelector(targetSelector);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Always keep navbar white with subtle shadow
    const navbar = document.querySelector('.navbar');
    navbar.style.background = 'rgba(255, 255, 255, 0.9)';
    navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.05)';
});

// Card animations
const cards = document.querySelectorAll('.card');
cards.forEach(card => {
    card.style.opacity = '1';
    card.style.transform = 'translateY(0)';
});