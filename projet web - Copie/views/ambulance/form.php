<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($ambulance) ? 'Modifier' : 'Ajouter'; ?> une Ambulance - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
            display: none;
        }
        .has-error .error-message {
            display: block;
        }
        .has-error input, .has-error select {
            border-color: #dc3545 !important;
            background-color: #fff8f8;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        .alert-danger li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo"><a href="index.php"><img src="logo.PNG" alt="MedChain Logo"></a></div>
            <ul class="nav-links">
                <li class="dropdown">
                    <a href="#" class="dropbtn">Flotte & Missions ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=ambulance">Gestion Ambulances</a>
                        <a href="index.php?page=mission">Registre Missions</a>
                    </div>
                </li>
                <li><a href="#">Bloc Opératoire</a></li>
                <li><a href="#">Remplacement</a></li>
                <li><a href="#">Rendez-vous</a></li>
                <li><a href="#">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1><?php echo isset($ambulance) ? '✏️ Modifier l\'Ambulance' : '➕ Ajouter une Ambulance'; ?></h1>
            <a href="index.php?page=ambulance" class="btn btn-secondary">← Retour</a>
        </div>

        <!-- Affichage des erreurs globales PHP -->
        <?php if(isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Des erreurs serveur ont été détectées :</strong>
                <ul>
                    <?php foreach($errors as $field => $error): ?>
                        <?php if($field != 'general'): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="index.php?page=ambulance&action=<?php echo isset($ambulance) ? 'update&id=' . $ambulance['idAmbulance'] : 'store'; ?>" 
                  method="POST" class="ambulance-form" id="ambulanceForm" novalidate onsubmit="return validateForm(event)">
                
                <div class="form-group" id="group-immatriculation">
                    <label for="immatriculation">Immatriculation *</label>
                    <input type="text" id="immatriculation" name="immatriculation" 
                           value="<?php echo isset($oldData['immatriculation']) ? htmlspecialchars($oldData['immatriculation']) : (isset($ambulance) ? htmlspecialchars($ambulance['immatriculation']) : ''); ?>"
                           placeholder="Ex: AB-123-CD">
                    <div class="error-message" id="err-immatriculation">Format invalide (Ex: lettres majuscules, chiffres et tirets, min 5).</div>
                    <small style="display: block; margin-top: 5px; color: #666;">Format attendu: lettres, chiffres et tirets (minimum 5 caractères)</small>
                </div>

                <div class="form-group" id="group-modele">
                    <label for="modele">Modèle *</label>
                    <input type="text" id="modele" name="modele" 
                           value="<?php echo isset($oldData['modele']) ? htmlspecialchars($oldData['modele']) : (isset($ambulance) ? htmlspecialchars($ambulance['modele']) : ''); ?>"
                           placeholder="Ex: Renault Master">
                    <div class="error-message" id="err-modele">Le modèle doit contenir au moins 2 caractères.</div>
                </div>

                <div class="form-group" id="group-statut">
                    <label for="statut">Statut *</label>
                    <select id="statut" name="statut">
                        <option value="">Sélectionner un statut</option>
                        <option value="En service" <?php echo (isset($oldData['statut']) && $oldData['statut'] == 'En service') ? 'selected' : (isset($ambulance) && isset($ambulance['statut']) && $ambulance['statut'] == 'En service' ? 'selected' : ''); ?>>
                            🟢 En service
                        </option>
                        <option value="En maintenance" <?php echo (isset($oldData['statut']) && $oldData['statut'] == 'En maintenance') ? 'selected' : (isset($ambulance) && isset($ambulance['statut']) && $ambulance['statut'] == 'En maintenance' ? 'selected' : ''); ?>>
                            🟡 En maintenance
                        </option>
                        <option value="Hors service" <?php echo (isset($oldData['statut']) && $oldData['statut'] == 'Hors service') ? 'selected' : (isset($ambulance) && isset($ambulance['statut']) && $ambulance['statut'] == 'Hors service' ? 'selected' : ''); ?>>
                            🔴 Hors service
                        </option>
                    </select>
                    <div class="error-message" id="err-statut">Veuillez sélectionner un statut valide.</div>
                </div>

                <div class="form-group" id="group-capacite">
                    <label for="capacite">Capacité (places) *</label>
                    <!-- Changement type="number" à type="text" pour interdire la validation HTML5 -->
                    <input type="text" id="capacite" name="capacite" 
                           value="<?php echo isset($oldData['capacite']) ? htmlspecialchars($oldData['capacite']) : (isset($ambulance) ? htmlspecialchars($ambulance['capacite']) : ''); ?>">
                    <div class="error-message" id="err-capacite">La capacité doit être un nombre entre 1 et 20.</div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="estDisponible" value="1" 
                               <?php echo (isset($oldData['estDisponible']) && $oldData['estDisponible'] == 1) ? 'checked' : ((isset($ambulance) && ($ambulance['estDisponible'] ?? 1) == 1) || (!isset($ambulance) && !isset($oldData)) ? 'checked' : ''); ?>>
                        Disponible immédiatement
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo isset($ambulance) ? '💾 Mettre à jour' : '✅ Enregistrer'; ?>
                    </button>
                    <button type="reset" class="btn btn-secondary" onclick="resetForm()">🔄 Réinitialiser</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function resetForm() {
            document.querySelectorAll('.form-group').forEach(el => el.classList.remove('has-error'));
        }

        // Form Validation sans HTML5
        function validateForm(event) {
            let isValid = true;
            resetForm();

            // Immatriculation
            let immatriculation = document.getElementById('immatriculation').value.trim();
            const immatRegex = /^[A-Z0-9-]+$/i;
            if (immatriculation.length < 5 || immatriculation.length > 20 || !immatRegex.test(immatriculation)) {
                document.getElementById('group-immatriculation').classList.add('has-error');
                isValid = false;
            }

            // Modèle
            let modele = document.getElementById('modele').value.trim();
            const modeleRegex = /^[a-zA-Z0-9\s\-]+$/;
            if (modele.length < 2 || modele.length > 50 || !modeleRegex.test(modele)) {
                document.getElementById('group-modele').classList.add('has-error');
                isValid = false;
            }

            // Statut
            let statut = document.getElementById('statut').value;
            if (statut === '') {
                document.getElementById('group-statut').classList.add('has-error');
                isValid = false;
            }

            // Capacité
            let capaciteStr = document.getElementById('capacite').value.trim();
            const capRegex = /^\d+$/;
            if(!capRegex.test(capaciteStr)) {
                document.getElementById('group-capacite').classList.add('has-error');
                isValid = false;
            } else {
                let capacite = parseInt(capaciteStr, 10);
                if (isNaN(capacite) || capacite < 1 || capacite > 20) {
                    document.getElementById('group-capacite').classList.add('has-error');
                    isValid = false;
                }
            }

            if(!isValid) {
                event.preventDefault(); // Stop form submission
            }
            
            return isValid;
        }
    </script>
</body>
</html>