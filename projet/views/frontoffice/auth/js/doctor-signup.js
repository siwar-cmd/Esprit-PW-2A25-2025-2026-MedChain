// doctor-signup.js - Animation et validation pour l'inscription médecin
class DoctorSignupAnimation {
    constructor() {
        this.form = document.getElementById('doctorForm');
        this.submitBtn = document.getElementById('submitBtn');
        this.diplomeInput = document.getElementById('diplome');
        this.previewContainer = document.getElementById('previewContainer');
        this.previewImage = document.getElementById('previewImage');
        
        this.init();
    }

    init() {
        this.setupDelayedAnimations();
        this.setupFormAnimations();
        this.setupInputAnimations();
        this.setupButtonAnimation();
        this.setupFilePreview();
        this.setupAutoFocus();
        this.setupAutoDismiss();
    }

    setupDelayedAnimations() {
        // Appliquer les délais d'animation depuis les data-attributs
        document.querySelectorAll('[data-delay]').forEach(element => {
            const delay = element.getAttribute('data-delay');
            element.style.setProperty('--delay', delay);
        });
    }

    setupFormAnimations() {
        if (!this.form) return;

        // Animation au survol
        this.form.addEventListener('mouseenter', () => {
            this.form.style.transform = 'translateY(-2px)';
            this.form.style.transition = 'transform 0.3s ease';
        });

        this.form.addEventListener('mouseleave', () => {
            this.form.style.transform = 'translateY(0)';
        });

        // Validation à la soumission
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.animateSubmitError();
            } else {
                this.animateSubmit();
            }
        });
    }

    setupInputAnimations() {
        const inputs = document.querySelectorAll('.form-control');
        
        inputs.forEach(input => {
            // Animation au focus
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('input-focused');
                
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
                
                // Validation en temps réel
                this.validateField(input);
            });

            // Animation de validation
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });
    }

    setupButtonAnimation() {
        if (!this.submitBtn) return;

        // Effet de clic
        this.submitBtn.addEventListener('click', (e) => {
            this.createRippleEffect(e, this.submitBtn);
        });

        // Animation au survol
        this.submitBtn.addEventListener('mouseenter', () => {
            this.submitBtn.style.transform = 'translateY(-3px) scale(1.02)';
        });

        this.submitBtn.addEventListener('mouseleave', () => {
            this.submitBtn.style.transform = 'translateY(0) scale(1)';
        });
    }

    setupFilePreview() {
        if (!this.diplomeInput || !this.previewContainer || !this.previewImage) return;

        this.diplomeInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewImage.src = e.target.result;
                    this.previewContainer.classList.add('show');
                };
                reader.readAsDataURL(file);
            } else {
                this.previewContainer.classList.remove('show');
            }
            
            // Validation du fichier
            this.validateFile();
        });
    }

    setupAutoFocus() {
        // Focus automatique sur le premier champ après 500ms
        setTimeout(() => {
            const firstInput = document.getElementById('prenom');
            if (firstInput) {
                firstInput.focus();
                
                firstInput.parentElement.classList.add('field-focused');
                setTimeout(() => {
                    firstInput.parentElement.classList.remove('field-focused');
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

    validateForm() {
        let isValid = true;
        const errors = [];
        
        // Valider les champs requis
        const requiredFields = this.form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                const fieldName = field.previousElementSibling?.textContent || field.name;
                errors.push(`"${fieldName}" est obligatoire`);
                this.showFieldError(field, 'Ce champ est obligatoire');
            } else {
                this.removeFieldError(field);
            }
        });

        // Validation spécifique
        const emailField = document.getElementById('email');
        if (emailField && emailField.value) {
            if (!this.validateEmail(emailField.value)) {
                isValid = false;
                errors.push('Format d\'email invalide');
                this.showFieldError(emailField, 'Format d\'email invalide');
            }
        }

        const passwordField = document.getElementById('mot_de_passe');
        if (passwordField && passwordField.value) {
            if (passwordField.value.length < 6) {
                isValid = false;
                errors.push('Le mot de passe doit contenir au moins 6 caractères');
                this.showFieldError(passwordField, 'Minimum 6 caractères');
            }
        }

        // Validation du fichier
        if (this.diplomeInput) {
            const fileError = this.validateFile();
            if (fileError) {
                isValid = false;
                errors.push(fileError);
            }
        }

        if (!isValid && errors.length > 0) {
            this.showGlobalError(errors);
        }

        return isValid;
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name || field.id;
        
        this.removeFieldError(field);

        if (!value && field.required) {
            this.showFieldError(field, 'Ce champ est obligatoire');
            return false;
        }

        if (value) {
            switch(fieldName) {
                case 'email':
                    if (!this.validateEmail(value)) {
                        this.showFieldError(field, 'Format d\'email invalide');
                        return false;
                    }
                    break;
                    
                case 'mot_de_passe':
                    if (value.length < 6) {
                        this.showFieldError(field, 'Minimum 6 caractères');
                        return false;
                    }
                    break;
                    
                case 'prenom':
                case 'nom':
                    if (value.length < 2) {
                        this.showFieldError(field, 'Minimum 2 caractères');
                        return false;
                    }
                    break;
            }
            
            this.showFieldSuccess(field);
            return true;
        }
        
        return true;
    }

    validateFile() {
        if (!this.diplomeInput || this.diplomeInput.files.length === 0) {
            this.showFieldError(this.diplomeInput, 'Veuillez sélectionner un fichier');
            return 'Veuillez télécharger votre diplôme';
        }

        const file = this.diplomeInput.files[0];
        const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        const maxSize = 2 * 1024 * 1024; // 2MB

        if (!allowedExtensions.includes(fileExtension)) {
            this.showFieldError(this.diplomeInput, 'Format non accepté. Utilisez PDF, JPG, JPEG ou PNG.');
            return 'Format de fichier non accepté';
        }

        if (file.size > maxSize) {
            this.showFieldError(this.diplomeInput, 'Le fichier est trop volumineux (max 2MB)');
            return 'Le fichier est trop volumineux. Taille maximum: 2MB';
        }

        this.removeFieldError(this.diplomeInput);
        return null;
    }

    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email) && email.length <= 255;
    }

    showFieldError(field, message) {
        this.removeFieldError(field);
        
        field.classList.add('error');
        field.classList.remove('success');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error animate-fade-in';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        
        field.parentElement.appendChild(errorDiv);
        
        field.classList.add('animate-shake');
        setTimeout(() => {
            field.classList.remove('animate-shake');
        }, 500);
    }

    removeFieldError(field) {
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        field.classList.remove('error');
    }

    showFieldSuccess(field) {
        field.classList.remove('error');
        field.classList.add('success');
        
        field.classList.add('animate-pulse');
        setTimeout(() => {
            field.classList.remove('animate-pulse');
        }, 500);
    }

    animateSubmit() {
        if (!this.submitBtn) return;

        const btnContent = this.submitBtn.querySelector('.btn-content');
        const btnLoading = this.submitBtn.querySelector('.btn-loading');

        if (btnContent && btnLoading) {
            this.submitBtn.disabled = true;
            this.submitBtn.style.transform = 'scale(0.95)';
            this.submitBtn.style.transition = 'transform 0.3s ease';

            btnContent.style.display = 'none';
            btnLoading.style.display = 'flex';
            this.submitBtn.classList.add('btn-loading-active');
        }
    }

    animateSubmitError() {
        if (!this.form) return;

        this.form.classList.add('animate-shake');
        setTimeout(() => {
            this.form.classList.remove('animate-shake');
        }, 500);
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
        const existingError = document.querySelector('.global-error-alert');
        if (existingError) {
            this.fadeOutElement(existingError);
        }

        const errorDiv = document.createElement('div');
        errorDiv.className = 'global-error-alert alert-error animate-fade-in animate-shake';
        errorDiv.style.cssText = 'margin: 20px; border-radius: 8px;';
        
        let errorHtml = '<div class="d-flex align-items-center"><i class="fas fa-exclamation-circle mr-2"></i>';
        errorHtml += '<strong>Veuillez corriger les erreurs suivantes :</strong></div>';
        errorHtml += '<ul style="margin: 0.5rem 0 0 1.5rem;">';
        messages.forEach(msg => {
            errorHtml += `<li>${msg}</li>`;
        });
        errorHtml += '</ul>';
        
        errorDiv.innerHTML = errorHtml;

        const formContainer = document.querySelector('.doctor-form-section');
        if (formContainer) {
            formContainer.parentNode.insertBefore(errorDiv, formContainer);
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        setTimeout(() => {
            this.fadeOutElement(errorDiv);
        }, 8000);
    }

    resetForm() {
        if (this.submitBtn) {
            this.submitBtn.disabled = false;
            this.submitBtn.style.transform = '';
            
            const btnContent = this.submitBtn.querySelector('.btn-content');
            const btnLoading = this.submitBtn.querySelector('.btn-loading');
            
            if (btnContent && btnLoading) {
                btnContent.style.display = 'flex';
                btnLoading.style.display = 'none';
            }
            
            this.submitBtn.classList.remove('btn-loading-active');
        }
    }
}

// Initialiser quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    window.doctorSignupAnimations = new DoctorSignupAnimation();
});