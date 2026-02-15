<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($materielData) ? 'Modifier' : 'Ajouter'; ?> Matériel - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message { color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; display: none; }
        .has-error .error-message { display: block; }
        .has-error input, .has-error select { border-color: #dc3545 !important; background-color: #fff8f8; }
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
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1><?php echo isset($materielData) ? '✏️ Modifier Matériel' : '➕ Ajouter Matériel'; ?></h1>
            <a href="index.php?page=materiel" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if(isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Erreurs PHP :</strong>
                <ul>
                    <?php foreach($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="index.php?page=materiel&action=<?php echo isset($materielData) ? 'update&id=' . $materielData['idMateriel'] : 'store'; ?>" 
                  method="POST" id="matForm" novalidate onsubmit="return validateForm(event)">
                
                <div class="form-group" id="group-nom">
                    <label>Nom du Matériel *</label>
                    <input type="text" id="nom" name="nom" 
                           value="<?php echo isset($oldData['nom']) ? htmlspecialchars($oldData['nom']) : (isset($materielData) ? htmlspecialchars($materielData['nom']) : ''); ?>">
                    <div class="error-message" id="err-nom">Le nom est obligatoire (min 2 caractères).</div>
                </div>

                <div class="form-group" id="group-categorie">
                    <label>Catégorie *</label>
                    <input type="text" id="categorie" name="categorie" 
                           value="<?php echo isset($oldData['categorie']) ? htmlspecialchars($oldData['categorie']) : (isset($materielData) ? htmlspecialchars($materielData['categorie']) : ''); ?>">
                    <div class="error-message" id="err-categorie">La catégorie est obligatoire.</div>
                </div>

                <div class="form-group" id="group-disponibilite">
                    <label>Disponibilité *</label>
                    <select id="disponibilite" name="disponibilite">
                        <?php $dispo = isset($oldData['disponibilite']) ? $oldData['disponibilite'] : (isset($materielData) ? $materielData['disponibilite'] : 'disponible'); ?>
                        <option value="disponible" <?php echo $dispo == 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                        <option value="indisponible" <?php echo $dispo == 'indisponible' ? 'selected' : ''; ?>>Indisponible</option>
                    </select>
                </div>

                <div class="form-group" id="group-statutSterilisation">
                    <label>Statut Stérilisation *</label>
                    <select id="statutSterilisation" name="statutSterilisation">
                        <?php $steril = isset($oldData['statutSterilisation']) ? $oldData['statutSterilisation'] : (isset($materielData) ? $materielData['statutSterilisation'] : 'sterilise'); ?>
                        <option value="sterilise" <?php echo $steril == 'sterilise' ? 'selected' : ''; ?>>Stérilisé</option>
                        <option value="non_sterilise" <?php echo $steril == 'non_sterilise' ? 'selected' : ''; ?>>Non Stérilisé</option>
                    </select>
                </div>

                <div class="form-group" id="group-nombreUtilisationsMax">
                    <label>Nombre d'utilisations Max *</label>
                    <input type="text" id="nombreUtilisationsMax" name="nombreUtilisationsMax" 
                           value="<?php echo isset($oldData['nombreUtilisationsMax']) ? htmlspecialchars($oldData['nombreUtilisationsMax']) : (isset($materielData) ? htmlspecialchars($materielData['nombreUtilisationsMax']) : ''); ?>">
                    <div class="error-message">Veuillez entrer un nombre entier positif.</div>
                </div>

                <div class="form-group" id="group-nombreUtilisationsActuelles">
                    <label>Nombre d'utilisations actuelles</label>
                    <input type="text" id="nombreUtilisationsActuelles" name="nombreUtilisationsActuelles" 
                           value="<?php echo isset($oldData['nombreUtilisationsActuelles']) ? htmlspecialchars($oldData['nombreUtilisationsActuelles']) : (isset($materielData) ? htmlspecialchars($materielData['nombreUtilisationsActuelles']) : '0'); ?>">
                    <div class="error-message">Veuillez entrer un nombre entier positif ou zéro.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">✅ Enregistrer</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('matForm').reset(); document.querySelectorAll('.form-group').forEach(e=>e.classList.remove('has-error'));">🔄 Réinitialiser</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function validateForm(event) {
            let isValid = true;
            document.querySelectorAll('.form-group').forEach(el => el.classList.remove('has-error'));

            let nom = document.getElementById('nom').value.trim();
            if (nom.length < 2) { document.getElementById('group-nom').classList.add('has-error'); isValid = false; }

            let cat = document.getElementById('categorie').value.trim();
            if (cat.length < 2) { document.getElementById('group-categorie').classList.add('has-error'); isValid = false; }

            let numRegex = /^\d+$/;
            let max = document.getElementById('nombreUtilisationsMax').value.trim();
            if(!numRegex.test(max) || parseInt(max) < 1) {
                document.getElementById('group-nombreUtilisationsMax').classList.add('has-error'); isValid = false;
            }

            let act = document.getElementById('nombreUtilisationsActuelles').value.trim();
            if(act !== '' && (!numRegex.test(act) || parseInt(act) < 0)) {
                document.getElementById('group-nombreUtilisationsActuelles').classList.add('has-error'); isValid = false;
            }

            if(!isValid) event.preventDefault();
            return isValid;
        }
    </script>
</body>
</html>
