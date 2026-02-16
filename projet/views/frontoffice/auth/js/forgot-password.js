// views/frontoffice/auth/js/forgot-password.js

class ForgotPasswordAnimations {
    constructor() {
        this.init();
    }

    init() {
        this.setupFormAnimations();
        this.setupInputAnimations();
        this.setupButtonAnimations();
        this.setupPageAnimations();
        this.setupValidation();
        this.setupAutoFocus();
        this.setupAlerts();
    }

    setupFormAnimations() {
        const form = document.getElementById('forgotPasswordForm');
        if (!form) return;

        // Animation de soumission
        form.addEventListener('submit', (e) => {
            this.animateSubmit(e);
        });

        // Animation d'entrée
        form.classList.add('animate-fade-in-up');
        
        // Animation au survol
        form.addEventListener('mouseenter', () => {
            form.style.transform = 'translateY(-3px)';
            form.style.transition = 'transform 0.3s ease';
        });

        form.addEventListener('mouseleave', () => {
            form.style.transform = 'translateY(0)';
        });
    }

    setupInputAnimations() {
        const emailInput = document.getElementById('email');
        if (!emailInput) return;

        // Animation au focus
        emailInput.addEventListener('focus', () => {
            this.animateInputFocus(emailInput);
        });

        // Animation à la saisie
        emailInput.addEventListener('input', () => {
            this.validateEmailInput(emailInput);
        });

        // Animation de validation au blur
        emailInput.addEventListener('blur', () => {
            this.validateEmailOnBlur(emailInput);
        });

        // Effet de placeholder animé
        this.setupPlaceholderAnimation(emailInput);
    }

    setupButtonAnimations() {
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn) return;

        // Effet de clic avec ripple
        submitBtn.addEventListener('click', (e) => {
            this.createRippleEffect(e, submitBtn);
        });

        // Animation au survol
        submitBtn.addEventListener('mouseenter', () => {
            submitBtn.style.transform = 'translateY(-3px) scale(1.05)';
            submitBtn.style.boxShadow = '0 10px 25px rgba(74, 144, 226, 0.4)';
        });

        submitBtn.addEventListener('mouseleave', () => {
            submitBtn.style.transform = 'translateY(0) scale(1)';
            submitBtn.style.boxShadow = '';
        });

        // Préparer le contenu du bouton pour le chargement
        submitBtn.innerHTML = `
            <span class="btn-content">
                <i class="fas fa-paper-plane mr-2"></i> Envoyer le lien de réinitialisation
            </span>
            <span class="btn-loading" style="display: none;">
                <span class="loading-dots">
                    <div></div><div></div><div></div><div></div>
                </span>
                <span class="ml-2">Envoi en cours...</span>
            </span>
        `;
    }

    setupPageAnimations() {
        // Animation du header
        const header = document.querySelector('.auth-header');
        if (header) {
            header.classList.add('animate-fade-in-down');
        }

        // Animation de l'icône
        const icon = document.querySelector('.auth-icon');
        if (icon) {
            icon.classList.add('animate-bounce-in');
            setTimeout(() => {
                icon.classList.add('animate-float');
            }, 1000);
        }

        // Animation des instructions
        const instructions = document.querySelector('.auth-instructions');
        if (instructions) {
            instructions.classList.add('animate-fade-in-left');
        }

        // Animation des liens
        const links = document.querySelectorAll('.auth-back-to-login a');
        links.forEach((link, index) => {
            link.style.animationDelay = `${0.5 + (index * 0.1)}s`;
            link.classList.add('animate-fade-in');
        });

        // Animation du footer
        const footer = document.querySelector('.auth-form-footer');
        if (footer) {
            footer.style.animationDelay = '0.8s';
            footer.classList.add('animate-fade-in');
        }
    }

    setupValidation() {
        const emailInput = document.getElementById('email');
        if (!emailInput) return;

        const emailError = document.getElementById('emailError');
        if (!emailError) return;

        // Validation en temps réel
        emailInput.addEventListener('input', () => {
            this.validateEmailInput(emailInput);
        });

        // Validation au submit
        const form = document.getElementById('forgotPasswordForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                }
            });
        }
    }

    setupAutoFocus() {
        // Focus automatique sur le champ email
        setTimeout(() => {
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.focus();
                this.animateInputFocus(emailInput);
            }
        }, 800);
    }

    setupAlerts() {
        // Animation pour les alertes
        const alerts = document.querySelectorAll('.auth-alert');
        alerts.forEach(alert => {
            alert.classList.add('notification');
            
            // Auto-dismiss avec animation
            if (alert.classList.contains('alert-dismissible')) {
                setTimeout(() => {
                    alert.classList.add('exit');
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }, 5000);
            }
        });
    }

    // Méthodes d'animation
    animateInputFocus(input) {
        input.parentElement.classList.add('input-focused');
        
        // Animation de l'icône
        const icon = input.parentElement.querySelector('.auth-input-icon i');
        if (icon) {
            icon.classList.add('icon-pulse');
            setTimeout(() => {
                icon.classList.remove('icon-pulse');
            }, 500);
        }
    }

    validateEmailInput(input) {
        const value = input.value.trim();
        const emailError = document.getElementById('emailError');
        
        // Supprimer les messages d'erreur précédents
        this.removeFieldError(input);

        if (value === '') {
            input.classList.remove('is-valid', 'is-invalid');
            return false;
        }

        const isValid = this.isValidEmail(value);
        
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            this.showFieldSuccess(input);
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            this.showFieldError(input, 'Adresse email invalide');
        }
        
        return isValid;
    }

    validateEmailOnBlur(input) {
        const value = input.value.trim();
        if (value === '') return;
        
        if (!this.isValidEmail(value)) {
            this.showFieldError(input, 'Veuillez entrer une adresse email valide');
        }
    }

    validateForm() {
        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('emailError');
        
        let isValid = true;
        
        // Validation de l'email
        const emailValue = emailInput.value.trim();
        if (!emailValue) {
            this.showFieldError(emailInput, 'L\'adresse email est obligatoire');
            this.shakeElement(emailInput);
            isValid = false;
        } else if (!this.isValidEmail(emailValue)) {
            this.showFieldError(emailInput, 'Format d\'email invalide');
            this.shakeElement(emailInput);
            isValid = false;
        }
        
        if (!isValid) {
            // Scroll vers l'erreur
            emailInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        return isValid;
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email) && email.length <= 255;
    }

    showFieldError(field, message) {
        field.classList.add('error');
        field.classList.remove('success');
        
        // Créer ou mettre à jour le message d'erreur
        let errorDiv = field.parentElement.querySelector('.field-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'field-error auth-error-message animate-fade-in';
            field.parentElement.appendChild(errorDiv);
        }
        
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-1"></i> ${message}`;
        errorDiv.style.display = 'block';
        
        // Animation de secousse
        this.shakeElement(field);
    }

    removeFieldError(field) {
        const errorDiv = field.parentElement.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.classList.remove('animate-fade-in');
            errorDiv.classList.add('animate-fade-out');
            setTimeout(() => {
                if (errorDiv.parentElement) {
                    errorDiv.parentElement.removeChild(errorDiv);
                }
            }, 300);
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
        
        // Effet de validation visuelle
        const icon = field.parentElement.querySelector('.auth-input-icon i');
        if (icon) {
            icon.classList.remove('fa-envelope');
            icon.classList.add('fa-check-circle', 'text-success');
            setTimeout(() => {
                icon.classList.remove('fa-check-circle', 'text-success');
                icon.classList.add('fa-envelope');
            }, 1500);
        }
    }

    shakeElement(element) {
        element.classList.add('animate-shake');
        setTimeout(() => {
            element.classList.remove('animate-shake');
        }, 500);
    }

    animateSubmit(e) {
        const form = document.getElementById('forgotPasswordForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (!form || !submitBtn) return;
        
        // Valider le formulaire
        if (!this.validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // Animation de chargement
        const btnContent = submitBtn.querySelector('.btn-content');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        
        if (btnContent && btnLoading) {
            // Désactiver le bouton
            submitBtn.disabled = true;
            
            // Animation de rétrécissement
            submitBtn.style.transform = 'scale(0.95)';
            submitBtn.style.transition = 'transform 0.3s ease';
            
            // Afficher le loader
            btnContent.style.display = 'none';
            btnLoading.style.display = 'flex';
            btnLoading.style.alignItems = 'center';
            btnLoading.style.justifyContent = 'center';
            
            // Animation du spinner
            submitBtn.classList.add('btn-loading-active');
        }
        
        return true;
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
            animation: ripple-animation 0.6s linear;
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

    setupPlaceholderAnimation(input) {
        const placeholder = input.getAttribute('placeholder');
        if (!placeholder) return;
        
        // Animation de type machine à écrire
        let index = 0;
        const originalPlaceholder = placeholder;
        
        const typeWriter = () => {
            if (document.activeElement === input || input.value) return;
            
            if (index < originalPlaceholder.length) {
                input.setAttribute('placeholder', originalPlaceholder.substring(0, index + 1) + '|');
                index++;
                setTimeout(typeWriter, 100);
            } else {
                // Effacer et recommencer
                setTimeout(() => {
                    index = 0;
                    input.setAttribute('placeholder', '');
                    setTimeout(typeWriter, 500);
                }, 2000);
            }
        };
        
        // Démarrer l'animation après un délai
        setTimeout(typeWriter, 1000);
        
        // Arrêter l'animation au focus
        input.addEventListener('focus', () => {
            input.setAttribute('placeholder', originalPlaceholder);
        });
        
        // Reprendre l'animation si vide après le blur
        input.addEventListener('blur', () => {
            if (!input.value) {
                setTimeout(() => {
                    index = 0;
                    input.setAttribute('placeholder', '');
                    setTimeout(typeWriter, 500);
                }, 300);
            }
        });
    }

    resetForm() {
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.transform = '';
            
            const btnContent = submitBtn.querySelector('.btn-content');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            if (btnContent && btnLoading) {
                btnContent.style.display = 'inline';
                btnLoading.style.display = 'none';
            }
            
            submitBtn.classList.remove('btn-loading-active');
        }
    }
}

// Initialiser quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    window.forgotPasswordAnimations = new ForgotPasswordAnimations();
});