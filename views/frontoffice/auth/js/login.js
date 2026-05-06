// views/frontoffice/auth/js/login.js

class LoginAnimation {
    constructor() {
        this.init();
    }

    init() {
        this.setupFormAnimations();
        this.setupInputAnimations();
        this.setupButtonAnimation();
        this.setupPageAnimations();
        this.setupAutoFocus();
        this.setupAutoDismiss();
    }

    setupFormAnimations() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        // Animation de soumission
        form.addEventListener('submit', (e) => {
            this.animateSubmit();
        });

        // Animation d'entrée pour chaque champ
        const inputs = form.querySelectorAll('input');
        inputs.forEach((input, index) => {
            input.style.animationDelay = `${index * 0.1}s`;
            input.classList.add('animate-fade-in-up');
        });

        // Animation au survol
        form.addEventListener('mouseenter', () => {
            form.style.transform = 'translateY(-2px)';
            form.style.transition = 'transform 0.3s ease';
        });

        form.addEventListener('mouseleave', () => {
            form.style.transform = 'translateY(0)';
        });
    }

    setupInputAnimations() {
        const inputs = document.querySelectorAll('.form-control');
        
        inputs.forEach(input => {
            // Animation au focus
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('input-focused');
                
                // Animation de l'icône si présente
                const icon = input.parentElement.querySelector('i');
                if (icon) {
                    icon.classList.add('icon-focus');
                    setTimeout(() => {
                        icon.classList.remove('icon-focus');
                    }, 300);
                }
            });

            // Animation à la saisie
            input.addEventListener('input', () => {
                if (input.value.length > 0) {
                    input.classList.add('has-value');
                } else {
                    input.classList.remove('has-value');
                }
            });

            // Animation de validation
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });
    }

    setupButtonAnimation() {
        const button = document.getElementById('submitBtn');
        if (!button) return;

        // Effet de clic
        button.addEventListener('click', (e) => {
            this.createRippleEffect(e, button);
        });

        // Animation au survol
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'translateY(-3px) scale(1.02)';
        });

        button.addEventListener('mouseleave', () => {
            button.style.transform = 'translateY(0) scale(1)';
        });

        // Animation de chargement
        button.innerHTML = `
            <span class="btn-content">
                Se connecter <i class="ti-arrow-right ml-2"></i>
            </span>
            <span class="btn-loading" style="display: none;">
                <span class="loading-spinner"></span>Connexion en cours...
            </span>
        `;
    }

    setupPageAnimations() {
        // Animation du header
        const header = document.querySelector('.banner_content');
        if (header) {
            header.classList.add('animate-fade-in-up');
        }

        // Animation du formulaire
        const formContainer = document.querySelector('.appointment-form');
        if (formContainer) {
            formContainer.classList.add('animate-fade-in');
        }

        // Animation des liens
        const links = document.querySelectorAll('.login-links a');
        links.forEach((link, index) => {
            link.style.animationDelay = `${0.6 + (index * 0.1)}s`;
            link.classList.add('animate-fade-in');
        });

        // Animation du footer
        const footer = document.querySelector('.footer-area');
        if (footer) {
            footer.classList.add('animate-fade-in-up');
        }
    }

    setupAutoFocus() {
        // Focus automatique sur le champ email après 500ms
        setTimeout(() => {
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.focus();
                
                // Animation d'entrée
                emailInput.parentElement.classList.add('field-focused');
                setTimeout(() => {
                    emailInput.parentElement.classList.remove('field-focused');
                }, 1000);
            }
        }, 500);
    }

    setupAutoDismiss() {
        // Auto-dismiss des messages d'erreur après 5 secondes
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-error:not(.global-error-alert)');
            alerts.forEach(alert => {
                this.fadeOutElement(alert);
            });
        }, 5000);
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        let isValid = true;
        let message = '';

        // Supprimer les messages d'erreur existants
        this.removeFieldError(field);

        // Validation selon le type de champ
        if (fieldName === 'email') {
            isValid = this.validateEmail(value);
            if (!isValid) {
                message = value ? 'Format d\'email invalide' : 'L\'email est obligatoire';
            }
        } else if (fieldName === 'mot_de_passe') {
            isValid = this.validatePassword(value);
            if (!isValid) {
                message = value ? 'Minimum 6 caractères' : 'Le mot de passe est obligatoire';
            }
        }

        // Afficher le résultat
        if (!isValid) {
            this.showFieldError(field, message);
        } else if (value) {
            this.showFieldSuccess(field);
        }

        return isValid;
    }

    validateEmail(email) {
        if (!email) return false;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email) && email.length <= 255;
    }

    validatePassword(password) {
        if (!password) return false;
        return password.length >= 6 && password.length <= 255;
    }

    showFieldError(field, message) {
        field.classList.add('error');
        field.classList.remove('success');
        
        // Créer le message d'erreur
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error animate-fade-in';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        
        field.parentElement.appendChild(errorDiv);
        
        // Animation de secousse
        field.classList.add('animate-shake');
        setTimeout(() => {
            field.classList.remove('animate-shake');
        }, 500);
    }

    removeFieldError(field) {
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            this.fadeOutElement(existingError);
        }
        field.classList.remove('error');
    }

    showFieldSuccess(field) {
        field.classList.remove('error');
        field.classList.add('success');
        
        // Animation de succès
        field.classList.add('animate-pulse');
        setTimeout(() => {
            field.classList.remove('animate-pulse');
        }, 500);
    }

    animateSubmit() {
        const button = document.getElementById('submitBtn');
        if (!button) return;

        const btnContent = button.querySelector('.btn-content');
        const btnLoading = button.querySelector('.btn-loading');

        if (btnContent && btnLoading) {
            // Désactiver le bouton
            button.disabled = true;
            
            // Animation de rétrécissement
            button.style.transform = 'scale(0.95)';
            button.style.transition = 'transform 0.3s ease';

            // Afficher le loader
            btnContent.style.display = 'none';
            btnLoading.style.display = 'inline';

            // Animation du spinner
            button.classList.add('btn-loading-active');
        }
    }

    createRippleEffect(event, element) {
        const ripple = document.createElement('span');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

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
        `;

        element.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    fadeOutElement(element, duration = 500) {
        element.style.transition = `opacity ${duration}ms ease`;
        element.style.opacity = '0';
        
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, duration);
    }

    showGlobalError(messages) {
        // Supprimer les anciennes erreurs globales
        const existingError = document.querySelector('.global-error-alert');
        if (existingError) {
            this.fadeOutElement(existingError);
        }

        // Créer la nouvelle erreur globale
        const errorDiv = document.createElement('div');
        errorDiv.className = 'global-error-alert alert-error animate-fade-in animate-shake';
        
        let errorHtml = '<div class="d-flex align-items-center"><i class="fas fa-exclamation-circle mr-2"></i>';
        errorHtml += '<strong>Veuillez corriger les erreurs suivantes :</strong></div>';
        errorHtml += '<ul style="margin: 0.5rem 0 0 1.5rem;">';
        messages.forEach(msg => {
            errorHtml += `<li>${msg}</li>`;
        });
        errorHtml += '</ul>';
        
        errorDiv.innerHTML = errorHtml;

        // Insérer avant le formulaire
        const form = document.querySelector('form');
        if (form) {
            form.parentNode.insertBefore(errorDiv, form);
            
            // Scroll vers les erreurs
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Auto-dismiss après 8 secondes
        setTimeout(() => {
            this.fadeOutElement(errorDiv);
        }, 8000);
    }

    resetForm() {
        const button = document.getElementById('submitBtn');
        if (button) {
            button.disabled = false;
            button.style.transform = '';
            
            const btnContent = button.querySelector('.btn-content');
            const btnLoading = button.querySelector('.btn-loading');
            
            if (btnContent && btnLoading) {
                btnContent.style.display = 'inline';
                btnLoading.style.display = 'none';
            }
            
            button.classList.remove('btn-loading-active');
        }
    }
}

// Initialiser quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    window.loginAnimations = new LoginAnimation();
});