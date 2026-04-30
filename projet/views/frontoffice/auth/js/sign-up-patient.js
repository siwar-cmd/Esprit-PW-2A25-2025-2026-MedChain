// sign-up-patient.js - Validation pour l'inscription patient
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('patientForm');
    const submitBtn = document.getElementById('submitBtn');
    const loading = document.getElementById('loading');
    
    // Champs à valider
    const fields = {
        'prenom': {
            required: true,
            pattern: /^[A-Za-zÀ-ÿ\s\-']+$/,
            message: 'Le prénom ne doit contenir que des lettres',
            minLength: 2,
            minMessage: 'Le prénom doit contenir au moins 2 caractères'
        },
        'nom': {
            required: true,
            pattern: /^[A-Za-zÀ-ÿ\s\-']+$/,
            message: 'Le nom ne doit contenir que des lettres',
            minLength: 2,
            minMessage: 'Le nom doit contenir au moins 2 caractères'
        },
        'email': {
            required: true,
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            message: 'Veuillez entrer une adresse email valide'
        },
        'mot_de_passe': {
            required: true,
            minLength: 6,
            message: 'Le mot de passe doit contenir au moins 6 caractères'
        },
        'dateNaissance': {
            required: false,
            validate: function(value) {
                if (!value) return { valid: true };
                
                const birthDate = new Date(value);
                const today = new Date();
                const minDate = new Date();
                minDate.setFullYear(today.getFullYear() - 120);
                
                if (birthDate > today) {
                    return { 
                        valid: false, 
                        message: 'La date de naissance ne peut pas être dans le futur' 
                    };
                }
                
                if (birthDate < minDate) {
                    return { 
                        valid: false, 
                        message: 'L\'âge maximum est de 120 ans' 
                    };
                }
                
                return { valid: true };
            }
        }
    };
    
    // Initialisation des messages d'erreur
    Object.keys(fields).forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field) {
            const errorElement = document.getElementById(fieldName + '-error');
            if (!errorElement) {
                const div = document.createElement('div');
                div.id = fieldName + '-error';
                div.className = 'validation-message';
                field.parentNode.appendChild(div);
            }
        }
    });
    
    // Validation en temps réel
    Object.keys(fields).forEach(fieldName => {
        const field = document.getElementById(fieldName);
        const config = fields[fieldName];
        
        if (field) {
            field.addEventListener('blur', function() {
                validateField(field, config);
            });
            
            field.addEventListener('input', function() {
                if (field.classList.contains('invalid')) {
                    validateField(field, config);
                }
            });
        }
    });
    
    // Validation au submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        const firstErrorField = [];
        
        // Valider tous les champs
        Object.keys(fields).forEach(fieldName => {
            const field = document.getElementById(fieldName);
            const config = fields[fieldName];
            
            if (field && !validateField(field, config)) {
                isValid = false;
                if (firstErrorField.length === 0) {
                    firstErrorField.push(field);
                }
            }
        });
        
        if (isValid) {
            // Afficher le loading
            submitBtn.disabled = true;
            loading.style.display = 'block';
            
            // Soumettre le formulaire
            form.submit();
        } else {
            // Scroll vers la première erreur
            if (firstErrorField.length > 0) {
                firstErrorField[0].scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }
        }
    });
    
    // Fonction de validation
    function validateField(field, config) {
        const value = field.value.trim();
        const errorElement = document.getElementById(field.id + '-error');
        
        // Validation requise
        if (config.required && !value) {
            showError(field.id, 'Ce champ est obligatoire');
            return false;
        }
        
        // Si le champ n'est pas requis et vide, on le valide
        if (!config.required && !value) {
            clearError(field.id);
            return true;
        }
        
        // Validation longueur minimale
        if (config.minLength && value.length < config.minLength) {
            showError(field.id, config.minMessage || `Minimum ${config.minLength} caractères`);
            return false;
        }
        
        // Validation pattern
        if (config.pattern && !config.pattern.test(value)) {
            showError(field.id, config.message);
            return false;
        }
        
        // Validation personnalisée
        if (config.validate) {
            const result = config.validate(value);
            if (!result.valid) {
                showError(field.id, result.message);
                return false;
            }
        }
        
        // Validation spécifique pour l'email
        if (field.id === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                showError(field.id, 'Veuillez entrer une adresse email valide');
                return false;
            }
        }
        
        // Validation spécifique pour la date
        if (field.id === 'dateNaissance' && value) {
            const birthDate = new Date(value);
            const today = new Date();
            
            // S'assurer que la date est valide
            if (isNaN(birthDate.getTime())) {
                showError(field.id, 'Date invalide');
                return false;
            }
        }
        
        // Validation réussie
        clearError(field.id);
        return true;
    }
    
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + '-error');
        
        if (field) {
            field.classList.add('invalid');
            field.classList.remove('valid');
        }
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.className = 'validation-message error';
        }
    }
    
    function clearError(fieldId) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + '-error');
        
        if (field) {
            field.classList.remove('invalid');
            field.classList.add('valid');
        }
        
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.className = 'validation-message';
        }
    }
    
    // Validation de la date de naissance en temps réel
    const dateField = document.getElementById('dateNaissance');
    if (dateField) {
        // Définir la date max (aujourd'hui)
        const today = new Date();
        const maxDate = today.toISOString().split('T')[0];
        dateField.max = maxDate;
        
        // Définir la date min (il y a 120 ans)
        const minDate = new Date();
        minDate.setFullYear(today.getFullYear() - 120);
        dateField.min = minDate.toISOString().split('T')[0];
    }
    
    // Amélioration de l'UX : validation au focus out
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = 'var(--patient-blue)';
        });
        
        input.addEventListener('blur', function() {
            if (!this.classList.contains('invalid')) {
                this.style.borderColor = '';
            }
        });
    });
    
    // Afficher/masquer les exigences du mot de passe
    const passwordField = document.getElementById('mot_de_passe');
    if (passwordField) {
        passwordField.addEventListener('focus', function() {
            const requirements = this.parentNode.querySelector('.password-requirements');
            if (requirements) {
                requirements.style.opacity = '1';
                requirements.style.transform = 'translateY(0)';
            }
        });
        
        passwordField.addEventListener('blur', function() {
            const requirements = this.parentNode.querySelector('.password-requirements');
            if (requirements && !this.value) {
                requirements.style.opacity = '0.7';
                requirements.style.transform = 'translateY(-5px)';
            }
        });
    }
});