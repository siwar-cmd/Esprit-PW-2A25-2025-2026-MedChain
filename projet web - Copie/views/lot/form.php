<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($lotData) ? 'Modifier' : 'Ajouter'; ?> Lot - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message { color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; display: none; }
        .has-error .error-message { display: block; }
        .has-error input { border-color: #dc3545 !important; background-color: #fff8f8; }
        .alert-danger { background-color: #f8d7da; padding: 10px; margin-bottom: 20px; color: red;}
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
                    <a href="#" class="dropbtn">Bloc opératoire ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=intervention">Interventions</a>
                        <a href="index.php?page=materiel">Matériel Médical</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn active">Traçabilité ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=lot">Lots Médicaments</a>
                        <a href="index.php?page=distribution">Distributions</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Rendez-vous ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=rdv">Agenda RDV</a>
                        <a href="index.php?page=ficherdv">Fiches de RDV</a>
                    </div>
                </li>
                <li><a href="blog.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1><?php echo isset($lotData) ? '✏️ Modifier le Lot' : '➕ Ajouter un Nouveau Lot'; ?></h1>
            <a href="index.php?page=lot" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if(isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error) echo "- $error<br>"; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="index.php?page=lot&action=<?php echo isset($lotData) ? 'update&id='.$lotData['idLot'] : 'store'; ?>" 
                  method="POST" id="lotForm" novalidate onsubmit="return validateForm(event)">
                
                <div class="form-group" id="group-nomMedicament">
                    <label>Nom du Médicament *</label>
                    <input type="text" id="nomMedicament" name="nomMedicament" 
                           value="<?php echo isset($oldData['nomMedicament']) ? htmlspecialchars($oldData['nomMedicament'] ?? '') : (isset($lotData) ? htmlspecialchars($lotData['nomMedicament'] ?? '') : ''); ?>">
                    <div class="error-message">Le nom est obligatoire.</div>
                </div>

                <div class="form-group" id="group-numeroLot">
                    <label>Numéro de Lot (Référence) *</label>
                    <input type="text" id="numeroLot" name="numeroLot" 
                           value="<?php echo isset($oldData['numeroLot']) ? htmlspecialchars($oldData['numeroLot'] ?? '') : (isset($lotData) ? htmlspecialchars($lotData['numeroLot'] ?? '') : ''); ?>">
                    <div class="error-message">Référence obligatoire.</div>
                </div>

                <div class="form-group" id="group-quantite">
                    <label>Quantité Initiale (Stock Mère) *</label>
                    <input type="text" id="quantite" name="quantite" 
                           value="<?php echo isset($oldData['quantite']) ? htmlspecialchars($oldData['quantite'] ?? '') : (isset($lotData) ? htmlspecialchars($lotData['quantite'] ?? '') : ''); ?>">
                    <div class="error-message">Veuillez entrer un nombre entier positif.</div>
                </div>

                <div class="form-group" id="group-datePeremption">
                    <label>Date de Péremption * (AAAA-MM-JJ)</label>
                    <input type="text" id="datePeremption" name="datePeremption" 
                           value="<?php echo isset($oldData['datePeremption']) ? htmlspecialchars($oldData['datePeremption'] ?? '') : (isset($lotData) ? htmlspecialchars($lotData['datePeremption'] ?? '') : ''); ?>">
                    <div class="error-message">Format attendu: AAAA-MM-JJ.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">✅ Enregistrer</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('lotForm').reset(); document.querySelectorAll('.form-group').forEach(e=>e.classList.remove('has-error'));">🔄 Réinitialiser</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function validateForm(e) {
            let isValid = true;
            document.querySelectorAll('.form-group').forEach(el => el.classList.remove('has-error'));
            
            let nom = document.getElementById('nomMedicament').value.trim();
            if(nom.length < 2) { document.getElementById('group-nomMedicament').classList.add('has-error'); isValid = false; }

            let ref = document.getElementById('numeroLot').value.trim();
            if(ref.length < 2) { document.getElementById('group-numeroLot').classList.add('has-error'); isValid = false; }

            let qte = document.getElementById('quantite').value.trim();
            if(!/^\d+$/.test(qte) || parseInt(qte) < 0) {
                document.getElementById('group-quantite').classList.add('has-error'); isValid = false;
            }

            let dtReg = /^\d{4}-\d{2}-\d{2}$/;
            let dP = document.getElementById('datePeremption').value.trim();
            if(!dtReg.test(dP)) { document.getElementById('group-datePeremption').classList.add('has-error'); isValid = false; }

            if(!isValid) e.preventDefault();
            return isValid;
        }
    </script>
</body>
</html>
