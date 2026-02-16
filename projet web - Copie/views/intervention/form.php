<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($interventionData) ? 'Modifier' : 'Ajouter'; ?> Intervention - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message { color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; display: none; }
        .has-error .error-message { display: block; }
        .has-error input { border-color: #dc3545 !important; background-color: #fff8f8; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .alert-danger ul { margin: 10px 0 0 20px; padding: 0; }
        .alert-danger li { margin: 5px 0; }
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
                <li class="dropdown">
                    <a href="#" class="dropbtn active">Bloc opératoire ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=intervention">Interventions</a>
                        <a href="index.php?page=materiel">Matériel Médical</a>
                    </div>
                </li>
                <li><a href="securite.php">Remplacement</a></li>
                <li><a href="cas_usage.php">Rendez-vous</a></li>
                <li><a href="blog.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1><?php echo isset($interventionData) ? '✏️ Modifier l\'Intervention' : '➕ Ajouter une Intervention'; ?></h1>
            <a href="index.php?page=intervention" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if(isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Erreurs serveur :</strong>
                <ul>
                    <?php foreach($errors as $field => $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="index.php?page=intervention&action=<?php echo isset($interventionData) ? 'update&id=' . $interventionData['idIntervention'] : 'store'; ?>" 
                  method="POST" id="interventionForm" novalidate onsubmit="return validateForm(event)">
                
                <div class="form-group" id="group-typeIntervention">
                    <label>Type d'Intervention *</label>
                    <input type="text" id="typeIntervention" name="typeIntervention" 
                           value="<?php echo isset($oldData['typeIntervention']) ? htmlspecialchars($oldData['typeIntervention'] ?? '') : (isset($interventionData) ? htmlspecialchars($interventionData['typeIntervention'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-typeIntervention">Le type est obligatoire (min 3 caractères).</div>
                </div>

                <div class="form-group" id="group-niveauUrgence">
                    <label>Niveau d'urgence (1 = Faible, 5 = Max) *</label>
                    <input type="text" id="niveauUrgence" name="niveauUrgence" 
                           value="<?php echo isset($oldData['niveauUrgence']) ? htmlspecialchars($oldData['niveauUrgence'] ?? '') : (isset($interventionData) ? htmlspecialchars($interventionData['niveauUrgence'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-niveauUrgence">Veuillez entrer un nombre entre 1 et 5.</div>
                </div>

                <div class="form-group" id="group-dateHeureDebut">
                    <label>Date & Heure de début (Optionnel : format AAAA-MM-JJ HH:MM:SS)</label>
                    <input type="text" id="dateHeureDebut" name="dateHeureDebut" 
                           value="<?php echo isset($oldData['dateHeureDebut']) ? htmlspecialchars($oldData['dateHeureDebut'] ?? '') : (isset($interventionData) ? htmlspecialchars($interventionData['dateHeureDebut'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-dateHeureDebut">Format datetime invalide. Laissez vide ou utilisez AAAA-MM-JJ HH:MM:SS.</div>
                </div>

                <div class="form-group" id="group-dateHeureFinPrevu">
                    <label>Date & Heure de fin prévue (Optionnel)</label>
                    <input type="text" id="dateHeureFinPrevu" name="dateHeureFinPrevu" 
                           value="<?php echo isset($oldData['dateHeureFinPrevu']) ? htmlspecialchars($oldData['dateHeureFinPrevu'] ?? '') : (isset($interventionData) ? htmlspecialchars($interventionData['dateHeureFinPrevu'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-dateHeureFinPrevu">Format datetime invalide ou date de fin précédant le début.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo isset($interventionData) ? '💾 Mettre à jour' : '✅ Enregistrer'; ?>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('interventionForm').reset(); document.querySelectorAll('.form-group').forEach(e=>e.classList.remove('has-error'));">🔄 Réinitialiser</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function validateForm(event) {
            let isValid = true;
            document.querySelectorAll('.form-group').forEach(el => el.classList.remove('has-error'));

            let typeIntervention = document.getElementById('typeIntervention').value.trim();
            if (typeIntervention.length < 3) {
                document.getElementById('group-typeIntervention').classList.add('has-error');
                isValid = false;
            }

            let niveauUrgenceStr = document.getElementById('niveauUrgence').value.trim();
            let niv = parseInt(niveauUrgenceStr);
            if (isNaN(niv) || niv < 1 || niv > 5) {
                document.getElementById('group-niveauUrgence').classList.add('has-error');
                isValid = false;
            }

            let dateTimeRegex = /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/;
            let dDeb = document.getElementById('dateHeureDebut').value.trim();
            let dFin = document.getElementById('dateHeureFinPrevu').value.trim();

            if(dDeb !== '' && !dateTimeRegex.test(dDeb)) {
                document.getElementById('group-dateHeureDebut').classList.add('has-error');
                isValid = false;
            }

            if(dFin !== '') {
                if(!dateTimeRegex.test(dFin)) {
                    document.getElementById('err-dateHeureFinPrevu').innerText = "Format attendu: AAAA-MM-JJ HH:MM:SS";
                    document.getElementById('group-dateHeureFinPrevu').classList.add('has-error');
                    isValid = false;
                } else if(isValid && dDeb !== '' && new Date(dFin) < new Date(dDeb)) {
                    document.getElementById('err-dateHeureFinPrevu').innerText = "La date de fin ne peut pas précéder la date de début.";
                    document.getElementById('group-dateHeureFinPrevu').classList.add('has-error');
                    isValid = false;
                }
            }

            if(!isValid) event.preventDefault();
            return isValid;
        }
    </script>
</body>
</html>
