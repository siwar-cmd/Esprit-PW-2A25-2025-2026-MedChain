// Validation JavaScript sans HTML5
const ValidationUtils = {
    // Vérifier si un champ est vide
    isEmpty: function(value) {
        return !value || value.trim() === '';
    },

    // Valider une chaîne de caractères non vide
    isValidText: function(value) {
        return !this.isEmpty(value);
    },

    // Valider un nombre positif
    isValidNumber: function(value) {
        const num = parseFloat(value);
        return !isNaN(num) && num > 0;
    },

    // Valider un nombre dans une plage
    isValidRange: function(value, min, max) {
        const num = parseFloat(value);
        return !isNaN(num) && num >= min && num <= max;
    },

    // Valider une date (format YYYY-MM-DD)
    isValidDate: function(value) {
        if (this.isEmpty(value)) return false;
        const regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(value)) return false;
        const date = new Date(value);
        return date instanceof Date && !isNaN(date);
    },

    // Valider une date future
    isValidFutureDate: function(value) {
        if (!this.isValidDate(value)) return false;
        const inputDate = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return inputDate >= today;
    },

    // Afficher message d'erreur
    showError: function(field, message) {
        field.classList.add('is-invalid');
        let errorDiv = field.parentElement.querySelector('.error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            field.parentElement.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    },

    // Masquer message d'erreur
    hideError: function(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    },

    // Valider formulaire d'intervention
    validateInterventionForm: function(form) {
        let isValid = true;
        const fields = {
            type: {
                validator: (val) => this.isValidText(val),
                message: 'Le type est obligatoire'
            },
            date_intervention: {
                validator: (val) => this.isValidDate(val),
                message: 'Veuillez entrer une date valide (YYYY-MM-DD)'
            },
            duree: {
                validator: (val) => this.isValidNumber(val),
                message: 'La durée doit être un nombre positif'
            },
            chirurgien: {
                validator: (val) => this.isValidText(val),
                message: 'Le chirurgien est obligatoire'
            },
            niveau_urgence: {
                validator: (val) => this.isValidRange(val, 1, 5),
                message: 'Le niveau d\'urgence doit être entre 1 et 5'
            }
        };

        for (let fieldName in fields) {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                if (!fields[fieldName].validator(field.value)) {
                    this.showError(field, fields[fieldName].message);
                    isValid = false;
                } else {
                    this.hideError(field);
                }
            }
        }

        return isValid;
    },

    // Valider formulaire d'intervention annulée
    validateInterventionAnnuleeForm: function(form) {
        let isValid = true;
        const fields = {
            idIntervention: {
                validator: (val) => !this.isEmpty(val),
                message: 'L\'intervention est obligatoire'
            },
            raison: {
                validator: (val) => this.isValidText(val),
                message: 'La raison est obligatoire'
            }
        };

        for (let fieldName in fields) {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                if (!fields[fieldName].validator(field.value)) {
                    this.showError(field, fields[fieldName].message);
                    isValid = false;
                } else {
                    this.hideError(field);
                }
            }
        }

        return isValid;
    },

    // Valider formulaire de matériel
    validateMaterielForm: function(form) {
        let isValid = true;
        const fields = {
            nom: {
                validator: (val) => this.isValidText(val),
                message: 'Le nom est obligatoire'
            },
            categorie: {
                validator: (val) => this.isValidText(val),
                message: 'La catégorie est obligatoire'
            },
            disponibilite: {
                validator: (val) => !this.isEmpty(val),
                message: 'La disponibilité est obligatoire'
            }
        };

        for (let fieldName in fields) {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                if (!fields[fieldName].validator(field.value)) {
                    this.showError(field, fields[fieldName].message);
                    isValid = false;
                } else {
                    this.hideError(field);
                }
            }
        }

        return isValid;
    },

    // Ajouter validation en temps réel sur les champs
    attachLiveValidation: function(form, validatorFunction) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                validatorFunction.call(this, form);
            });
            input.addEventListener('change', () => {
                if (input.classList.contains('is-invalid')) {
                    validatorFunction.call(this, form);
                }
            });
        });
    }
};

// Initialiser validation au chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    // Valider intervention
    const interventionForm = document.querySelector('form[data-form-type="intervention"]');
    if (interventionForm) {
        interventionForm.addEventListener('submit', function(e) {
            if (!ValidationUtils.validateInterventionForm(this)) {
                e.preventDefault();
            }
        });
        ValidationUtils.attachLiveValidation(interventionForm, ValidationUtils.validateInterventionForm);
    }

    // Valider intervention annulée
    const annuleeForm = document.querySelector('form[data-form-type="intervention-annulee"]');
    if (annuleeForm) {
        annuleeForm.addEventListener('submit', function(e) {
            if (!ValidationUtils.validateInterventionAnnuleeForm(this)) {
                e.preventDefault();
            }
        });
        ValidationUtils.attachLiveValidation(annuleeForm, ValidationUtils.validateInterventionAnnuleeForm);
    }

    // Valider matériel
    const materielForm = document.querySelector('form[data-form-type="materiel"]');
    if (materielForm) {
        materielForm.addEventListener('submit', function(e) {
            if (!ValidationUtils.validateMaterielForm(this)) {
                e.preventDefault();
            }
        });
        ValidationUtils.attachLiveValidation(materielForm, ValidationUtils.validateMaterielForm);
    }
});
