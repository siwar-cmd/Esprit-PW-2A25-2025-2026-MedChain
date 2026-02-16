// choice-script.js - Script pour la page de choix de profil
document.addEventListener('DOMContentLoaded', function() {
    // Animation au survol des cartes
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Animation des boutons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.05)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
        
        // Effet de ripple sur les boutons
        btn.addEventListener('click', function(e) {
            // Si c'est un lien, on laisse le navigateur suivre le lien
            if (this.tagName === 'A') return;
            
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.7);
                transform: scale(0);
                animation: ripple 0.6s linear;
                width: ${size}px;
                height: ${size}px;
                top: ${y}px;
                left: ${x}px;
                pointer-events: none;
                z-index: 1;
            `;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Animation des icônes au survol
    document.querySelectorAll('.icon-wrapper').forEach(iconWrapper => {
        iconWrapper.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) rotate(10deg)';
        });
        
        iconWrapper.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) rotate(0)';
        });
    });
    
    // Animation des éléments de feature au survol de la carte
    document.querySelectorAll('.card').forEach(card => {
        const features = card.querySelectorAll('.feature');
        
        card.addEventListener('mouseenter', function() {
            features.forEach((feature, index) => {
                setTimeout(() => {
                    feature.style.transform = 'translateX(5px)';
                }, index * 50);
            });
        });
        
        card.addEventListener('mouseleave', function() {
            features.forEach((feature, index) => {
                setTimeout(() => {
                    feature.style.transform = 'translateX(0)';
                }, index * 30);
            });
        });
    });
    
    // Ajout d'un effet de pulse sur le logo
    const logoIcon = document.querySelector('.logo-icon');
    if (logoIcon) {
        setInterval(() => {
            logoIcon.style.animation = 'none';
            setTimeout(() => {
                logoIcon.style.animation = 'pulse 2s infinite';
            }, 10);
        }, 10000); // Relancer l'animation toutes les 10 secondes
    }
    
    // Animation des bulles d'eau
    const bubbles = document.querySelectorAll('.water-bubble');
    bubbles.forEach((bubble, index) => {
        // Décaler les animations
        bubble.style.animationDelay = `${index * 2}s`;
        
        // Ajouter un mouvement horizontal léger
        const moveBubble = () => {
            const currentLeft = parseFloat(bubble.style.left || getComputedStyle(bubble).left);
            const newLeft = currentLeft + (Math.random() * 2 - 1);
            
            // Limiter le mouvement
            if (newLeft > -50 && newLeft < window.innerWidth) {
                bubble.style.left = `${newLeft}px`;
            }
        };
        
        // Animer légèrement la position horizontale
        setInterval(moveBubble, 3000);
    });
    
    // Effet de parallaxe sur les vagues
    const waves = document.querySelectorAll('.wave');
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        waves.forEach((wave, index) => {
            const rate = scrolled * (index + 1) * 0.01;
            wave.style.transform = `translateY(${rate}px)`;
        });
    });
    
    // Confirmation de déconnexion si l'utilisateur vient d'une session
    const logoutLinks = document.querySelectorAll('a[href*="logout"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                e.preventDefault();
            }
        });
    });
    
    // Amélioration de l'accessibilité
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                this.click();
            }
        });
        
        btn.setAttribute('role', 'button');
        btn.setAttribute('tabindex', '0');
    });
});