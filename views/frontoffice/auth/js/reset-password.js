// views/frontoffice/auth/js/reset-password.js

class ResetPasswordAnimations {
    constructor() {
        this.init();
    }

    init() {
        this.setupFormAnimations();
        this.setupPasswordAnimations();
        this.setupButtonAnimations();
        this.setupPageAnimations();
        this.setupValidation();
        this.setupPasswordStrength();
        this.setupAutoFocus();
    }

    setupFormAnimations() {
        const form = document.getElementById('resetPasswordForm');
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

    setupPasswordAnimations() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (newPassword) {
            // Animation au focus
            newPassword.addEventListener('focus', () => {
                this.animateInputFocus(newPassword);
            });
            
            // Animation de la force du mot de passe
            newPassword.addEventListener('input', () => {
                this.updatePasswordStrength(newPassword.value);
                this.validatePasswordRequirements();
                this.checkPasswordMatch();
            });
            
            // Animation du toggle
            this.setupPasswordToggle(newPassword, 'toggleNewPassword');
        }
        
        if (confirmPassword) {
            // Animation au focus
            confirmPassword.addEventListener('focus', () => {
                this.animateInputFocus(confirmPassword);
            });
            
            // Animation de correspondance
            confirmPassword.addEventListener('input', () => {
                this.checkPasswordMatch();
                this.validatePasswordRequirements();
            });
            
            // Animation du toggle
            this.setupPasswordToggle(confirmPassword, 'toggleConfirmPassword');
        }
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
            submitBtn.style.boxShadow = '0 10px 25px rgba(40, 167, 69, 0.4)';
        });

        submitBtn.addEventListener('mouseleave', () => {
            submitBtn.style.transform = 'translateY(0) scale(1)';
            submitBtn.style.boxShadow = '';
        });

        // Préparer le contenu du bouton pour le chargement
        submitBtn.innerHTML = `
            <span class="btn-content">
                <i class="fas fa-sync-alt mr-2"></i> Réinitialiser le mot de passe
            </span>
            <span class="btn-loading" style="display: none;">
                <span class="loading-dots">
                    <div></div><div></div><div></div><div></div>
                </span>
                <span class="ml-2">Réinitialisation en cours...</span>
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
            icon.classList.add('animate-rotate-in');
            setTimeout(() => {
                icon.classList.add('animate-float');
            }, 1000);
        }

        // Animation des instructions
        const instructions = document.querySelector('.auth-instructions');
        if (instructions) {
            instructions.classList.add('animate-fade-in-left');
        }

        // Animation des exigences de sécurité
        const requirements = document.querySelector('.auth-password-requirements');
        if (requirements) {
            requirements.style.animationDelay = '0.3s';
            requirements.classList.add('animate-fade-in-right');
        }

        // Animation des liens
        const links = document.querySelectorAll('.auth-back-to-login a');
        links.forEach((link, index) => {
            link.style.animationDelay = `${0.6 + (index * 0.1)}s`;
            link.classList.add('animate-fade-in');
        });

        // Animation du footer
        const footer = document.querySelector('.auth-form-footer');
        if (footer) {
            footer.style.animationDelay = '0.9s';
            footer.classList.add('animate-fade-in');
        }
    }

    setupValidation() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (newPassword) {
            newPassword.addEventListener('blur', () => {
                this.validatePasswordOnBlur(newPassword);
            });
        }
        
        if (confirmPassword) {
            confirmPassword.addEventListener('blur', () => {
                this.validateConfirmPasswordOnBlur(confirmPassword);
            });
        }
    }

    setupPasswordStrength() {
        const newPassword = document.getElementById('new_password');
        if (!newPassword) return;
        
        // Initialiser la barre de force
        this.updatePasswordStrength(newPassword.value);
    }

    setupAutoFocus() {
        // Focus automatique sur le premier champ
        setTimeout(() => {
            const newPassword = document.getElementById('new_password');
            if (newPassword) {
                newPassword.focus();
                this.animateInputFocus(newPassword);
            }
        }, 800);
    }

    setupPasswordToggle(input, toggleId) {
        const toggleBtn = document.getElementById(toggleId);
        if (!toggleBtn) return;
        
        toggleBtn.addEventListener('click', () => {
            this.togglePasswordVisibility(input, toggleBtn);
        });
        
        // Animation au survol du bouton toggle
        toggleBtn.addEventListener('mouseenter', () => {
            toggleBtn.style.transform = 'scale(1.2)';
            toggleBtn.style.transition = 'transform 0.3s ease';
        });
        
        toggleBtn.addEventListener('mouseleave', () => {
            toggleBtn.style.transform = 'scale(1)';
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

    togglePasswordVisibility(input, toggleBtn) {
        const icon = toggleBtn.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            
            // Animation de l'icône
            icon.classList.add('animate-rotate-in');
            setTimeout(() => {
                icon.classList.remove('animate-rotate-in');
            }, 800);
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            
            // Animation de l'icône
            icon.classList.add('animate-rotate-in');
            setTimeout(() => {
                icon.classList.remove('animate-rotate-in');
            }, 800);
        }
    }

    updatePasswordStrength(password) {
        const strengthBar = document.getElementById('strengthBar');
        if (!strengthBar) return;
        
        const strength = this.calculatePasswordStrength(password);
        
        // Réinitialiser les classes
        strengthBar.className = 'auth-strength-bar';
        
        // Définir la classe et la largeur en fonction de la force
        let width = 0;
        let className = '';
        
        if (strength <= 2) {
            width = 25;
            className = 'auth-strength-weak';
        } else if (strength <= 3) {
            width = 50;
            className = 'auth-strength-fair';
        } else if (strength <= 4) {
            width = 75;
            className = 'auth-strength-good';
        } else {
            width = 100;
            className = 'auth-strength-strong';
        }
        
        // Animation de la barre
        strengthBar.style.width = '0';
        setTimeout(() => {
            strengthBar.style.transition = 'width 0.5s ease, background-color 0.5s ease';
            strengthBar.style.width = `${width}%`;
            strengthBar.classList.add(className);
        }, 10);
        
        // Animation de couleur
        strengthBar.classList.add('progress-animate');
    }

    calculatePasswordStrength(password) {
        let strength = 0;
        
        // Longueur
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Lettres minuscules
        if (/[a-z]/.test(password)) strength++;
        
        // Lettres majuscules
        if (/[A-Z]/.test(password)) strength++;
        
        // Chiffres
        if (/[0-9]/.test(password)) strength++;
        
        // Caractères spéciaux
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        return strength;
    }

    validatePasswordRequirements() {
        const password = document.getElementById('new_password')?.value || '';
        const confirmPassword = document.getElementById('confirm_password')?.value || '';
        
        // Longueur
        this.updateRequirement('reqLength', 'iconLength', password.length >= 8);
        
        // Lettre
        this.updateRequirement('reqLetter', 'iconLetter', /[A-Za-z]/.test(password));
        
        // Chiffre
        this.updateRequirement('reqNumber', 'iconNumber', /\d/.test(password));
        
        // Correspondance
        this.updateRequirement('reqMatch', 'iconMatch', password === confirmPassword && password !== '');
    }

    updateRequirement(reqId, iconId, isMet) {
        const reqElement = document.getElementById(reqId);
        const iconElement = document.getElementById(iconId);
        
        if (!reqElement || !iconElement) return;
        
        if (isMet) {
            reqElement.classList.remove('auth-requirement', 'unmet');
            reqElement.classList.add('auth-requirement', 'met');
            
            iconElement.classList.remove('fas', 'fa-circle');
            iconElement.classList.add('fas', 'fa-check-circle', 'text-success');
            
            // Animation de l'icône
            iconElement.classList.add('animate-pulse');
            setTimeout(() => {
                iconElement.classList.remove('animate-pulse');
            }, 300);
        } else {
            reqElement.classList.remove('auth-requirement', 'met');
            reqElement.classList.add('auth-requirement', 'unmet');
            
            iconElement.classList.remove('fas', 'fa-check-circle', 'text-success');
            iconElement.classList.add('fas', 'fa-circle');
        }
    }

    checkPasswordMatch() {
        const password = document.getElementById('new_password')?.value || '';
        const confirmPassword = document.getElementById('confirm_password')?.value || '';
        const matchCheck = document.getElementById('passwordMatchCheck');
        
        if (!matchCheck) return;
        
        if (password === '' || confirmPassword === '') {
            matchCheck.style.display = 'none';
            return;
        }
        
        if (password === confirmPassword) {
            matchCheck.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Les mots de passe correspondent';
            matchCheck.className = 'password-match-check valid animate-fade-in';
            
            // Animation de succès
            matchCheck.classList.add('animate-pulse');
            setTimeout(() => {
                matchCheck.classList.remove('animate-pulse');
            }, 300);
        } else {
            matchCheck.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Les mots de passe ne correspondent pas';
            matchCheck.className = 'password-match-check invalid animate-fade-in animate-shake';
        }
    }

    validatePasswordOnBlur(input) {
        const value = input.value;
        
        if (value === '') {
            this.removeFieldError(input);
            return;
        }
        
        if (value.length < 8) {
            this.showFieldError(input, 'Le mot de passe doit contenir au moins 8 caractères');
        } else if (!/[A-Za-z]/.test(value)) {
            this.showFieldError(input, 'Le mot de passe doit contenir au moins une lettre');
        } else if (!/\d/.test(value)) {
            this.showFieldError(input, 'Le mot de passe doit contenir au moins un chiffre');
        } else {
            this.removeFieldError(input);
            this.showFieldSuccess(input);
        }
    }

    validateConfirmPasswordOnBlur(input) {
        const password = document.getElementById('new_password')?.value || '';
        const confirmPassword = input.value;
        
        if (confirmPassword === '') {
            this.removeFieldError(input);
            return;
        }
        
        if (password !== confirmPassword) {
            this.showFieldError(input, 'Les mots de passe ne correspondent pas');
        } else {
            this.removeFieldError(input);
            this.showFieldSuccess(input);
        }
    }

    validateForm() {
        const password = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        let isValid = true;
        
        // Validation du mot de passe
        if (!password || password.value.length < 8) {
            this.showFieldError(password, 'Minimum 8 caractères requis');
            this.shakeElement(password);
            isValid = false;
        } else if (!/[A-Za-z]/.test(password.value)) {
            this.showFieldError(password, 'Au moins une lettre requise');
            this.shakeElement(password);
            isValid = false;
        } else if (!/\d/.test(password.value)) {
            this.showFieldError(password, 'Au moins un chiffre requis');
            this.shakeElement(password);
            isValid = false;
        }
        
        // Validation de la confirmation
        if (!confirmPassword || password.value !== confirmPassword.value) {
            this.showFieldError(confirmPassword, 'Les mots de passe ne correspondent pas');
            this.shakeElement(confirmPassword);
            isValid = false;
        }
        
        if (!isValid) {
            // Scroll vers la première erreur
            const firstError = document.querySelector('.auth-form-control.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        return isValid;
    }

    showFieldError(field, message) {
        if (!field) return;
        
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
        if (!field) return;
        
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
        if (!field) return;
        
        field.classList.remove('error');
        field.classList.add('success');
        
        // Animation de succès
        field.classList.add('animate-pulse');
        setTimeout(() => {
            field.classList.remove('animate-pulse');
        }, 500);
    }

    shakeElement(element) {
        element.classList.add('animate-shake');
        setTimeout(() => {
            element.classList.remove('animate-shake');
        }, 500);
    }

    animateSubmit(e) {
        const form = document.getElementById('resetPasswordForm');
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
    window.resetPasswordAnimations = new ResetPasswordAnimations();
});