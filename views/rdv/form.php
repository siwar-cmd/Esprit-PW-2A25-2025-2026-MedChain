<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($rdvData) ? 'Modifier' : 'Ajouter'; ?> RDV - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message { color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; display: none; }
        .has-error .error-message { display: block; }
        .has-error input { border-color: #dc3545 !important; background-color: #fff8f8; }
        .alert-danger { background-color: #f8d7da; padding: 10px; margin-bottom: 20px; }
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
                    <a href="#" class="dropbtn">Traçabilité ⬇</a>
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
                <li><a href="loisir.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1><?php echo isset($rdvData) ? '✏️ Modifier RDV' : '➕ Nouveau RDV'; ?></h1>
            <a href="index.php?page=rdv" class="btn btn-secondary">← Retour</a>
        </div>
        <div class="form-container">
            <form action="index.php?page=rdv&action=<?php echo isset($rdvData) ? 'update&id='.$rdvData['idRDV'] : 'store'; ?>" 
                  method="POST" id="rdvForm" novalidate onsubmit="return validateForm(event)">
                
                <div class="form-group" id="group-dateHeureDebut">
                    <label>Date et Heure DU DEBUT * (AAAA-MM-JJ HH:MM:SS)</label>
                    <input type="text" id="dateHeureDebut" name="dateHeureDebut" 
                           value="<?php echo isset($oldData['dateHeureDebut']) ? htmlspecialchars($oldData['dateHeureDebut'] ?? '') : (isset($rdvData) ? htmlspecialchars($rdvData['dateHeureDebut'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-dateHeureDebut">Format attendu: AAAA-MM-JJ HH:MM:SS</div>
                    <?php if(isset($errors['dateHeureDebut'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['dateHeureDebut']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-dateHeureFin">
                    <label>Date et Heure DE FIN * (AAAA-MM-JJ HH:MM:SS)</label>
                    <input type="text" id="dateHeureFin" name="dateHeureFin" 
                           value="<?php echo isset($oldData['dateHeureFin']) ? htmlspecialchars($oldData['dateHeureFin'] ?? '') : (isset($rdvData) ? htmlspecialchars($rdvData['dateHeureFin'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-dateHeureFin">Date de fin invalide ou précède la date de début.</div>
                    <?php if(isset($errors['dateHeureFin'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['dateHeureFin']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-typeConsultation">
                    <label>Type de Consultation *</label>
                    <input type="text" id="typeConsultation" name="typeConsultation" 
                           value="<?php echo isset($oldData['typeConsultation']) ? htmlspecialchars($oldData['typeConsultation'] ?? '') : (isset($rdvData) ? htmlspecialchars($rdvData['typeConsultation'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-typeConsultation">Type de consultation obligatoire (minimum 3 caractères).</div>
                    <?php if(isset($errors['typeConsultation'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['typeConsultation']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-motif">
                    <label>Motif (Initial)</label>
                    <input type="text" id="motif" name="motif" 
                           value="<?php echo isset($oldData['motif']) ? htmlspecialchars($oldData['motif'] ?? '') : (isset($rdvData) ? htmlspecialchars($rdvData['motif'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-motif">Le motif ne peut pas être vide si renseigné.</div>
                </div>

                <?php if(isset($rdvData)): ?>
                <!-- Champ statut caché si edit pour ne pas forcer l'effacement par erreur -->
                <input type="hidden" name="statut" value="<?php echo htmlspecialchars($rdvData['statut']); ?>">
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">✅ Enregistrer</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('rdvForm').reset(); document.querySelectorAll('.form-group').forEach(e=>e.classList.remove('has-error'));">🔄 Réinitialiser</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function validateForm(e) {
            let isValid = true;
            document.querySelectorAll('.form-group').forEach(el => el.classList.remove('has-error'));
            
            let dtReg = /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/;
            let dd = document.getElementById('dateHeureDebut').value.trim();
            let df = document.getElementById('dateHeureFin').value.trim();

            if(!dtReg.test(dd)) { document.getElementById('group-dateHeureDebut').classList.add('has-error'); document.getElementById('err-dateHeureDebut').style.display='block'; isValid = false; }
            if(!dtReg.test(df)) { 
                document.getElementById('err-dateHeureFin').innerText = "Format attendu: AAAA-MM-JJ HH:MM:SS";
                document.getElementById('group-dateHeureFin').classList.add('has-error'); isValid = false; 
            } else if (new Date(df) <= new Date(dd)) {
                document.getElementById('err-dateHeureFin').innerText = "La fin doit être après le début.";
                document.getElementById('group-dateHeureFin').classList.add('has-error'); isValid = false;
            }

            let tps = document.getElementById('typeConsultation').value.trim();
            if(tps.length < 3) { document.getElementById('group-typeConsultation').classList.add('has-error'); document.getElementById('err-typeConsultation').style.display='block'; isValid = false; }

            if(!isValid) e.preventDefault();
            return isValid;
        }
    </script>
</body>
</html>
