<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($distData) ? 'Modifier' : 'Ajouter'; ?> Distribution - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message { color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; display: none; }
        .has-error .error-message { display: block; }
        .has-error input, .has-error select { border-color: #dc3545 !important; background-color: #fff8f8; }
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
            <h1><?php echo isset($distData) ? '✏️ Rectifier' : '➕ Enregistrer'; ?> une Distribution</h1>
            <a href="index.php?page=distribution" class="btn btn-secondary">← Annuler</a>
        </div>
        <div class="form-container">
            <form action="index.php?page=distribution&action=<?php echo isset($distData) ? 'update&id='.$distData['idDistribution'] : 'store'; ?>" 
                  method="POST" id="distForm" novalidate onsubmit="return validateForm(event)">
                
                <div class="form-group" id="group-idLot">
                    <label>Lot Source *</label>
                    <select id="idLot" name="idLot">
                        <option value="">-- Sélectionnez un Lot (Stock actuel) --</option>
                        <?php 
                            $selectedLot = isset($oldData['idLot']) ? $oldData['idLot'] : (isset($distData) ? $distData['idLot'] : '');
                            foreach($lots as $l): ?>
                            <option value="<?php echo $l['idLot']; ?>" <?php echo $selectedLot == $l['idLot'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($l['nomMedicament']); ?> (Lot: <?php echo htmlspecialchars($l['numeroLot']); ?>) - Reste: <?php echo $l['quantite']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="error-message" id="err-idLot">Veuillez rattacher cette distribution à un Lot existant.</div>
                    <?php if(isset($errors['idLot'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['idLot']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-quantite">
                    <label>Quantité à distribuer (qui sera soustraite) *</label>
                    <input type="text" id="quantite" name="quantite" 
                           value="<?php echo isset($oldData['quantite']) ? htmlspecialchars($oldData['quantite'] ?? '') : (isset($distData) ? htmlspecialchars($distData['quantite'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-qte">Doit être un entier positif supérieur à 0.</div>
                    <?php if(isset($errors['quantite'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['quantite']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-dateDistribution">
                    <label>Date et Heure * (AAAA-MM-JJ HH:MM:SS)</label>
                    <input type="text" id="dateDistribution" name="dateDistribution" 
                           value="<?php echo isset($oldData['dateDistribution']) ? htmlspecialchars($oldData['dateDistribution'] ?? '') : (isset($distData) ? htmlspecialchars($distData['dateDistribution'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-dateDistribution">Format attendu: AAAA-MM-JJ HH:MM:SS.</div>
                    <?php if(isset($errors['dateDistribution'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['dateDistribution']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-destinataire">
                    <label>Destinataire / Service *</label>
                    <input type="text" id="destinataire" name="destinataire" 
                           value="<?php echo isset($oldData['destinataire']) ? htmlspecialchars($oldData['destinataire'] ?? '') : (isset($distData) ? htmlspecialchars($distData['destinataire'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-destinataire">Veuillez indiquer le destinataire (ex: Bloc A, Dr Dupont, etc.).</div>
                    <?php if(isset($errors['destinataire'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['destinataire']); ?></p><?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">✅ Confirmer la Distribution</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('distForm').reset(); document.querySelectorAll('.form-group').forEach(e=>e.classList.remove('has-error'));">🔄 Réinitialiser</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function validateForm(e) {
            let isValid = true;
            document.querySelectorAll('.form-group').forEach(el => el.classList.remove('has-error'));
            
            let idL = document.getElementById('idLot').value;
            if(idL === '') { document.getElementById('group-idLot').classList.add('has-error'); isValid = false; }

            let qte = document.getElementById('quantite').value.trim();
            if(!/^\d+$/.test(qte) || parseInt(qte) <= 0) {
                document.getElementById('group-quantite').classList.add('has-error'); isValid = false;
            }

            let dtReg = /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/;
            let dd = document.getElementById('dateDistribution').value.trim();
            if(!dtReg.test(dd)) { document.getElementById('group-dateDistribution').classList.add('has-error'); isValid = false; }

            let dest = document.getElementById('destinataire').value.trim();
            if(dest.length < 2) { document.getElementById('group-destinataire').classList.add('has-error'); isValid = false; }

            if(!isValid) e.preventDefault();
            return isValid;
        }
    </script>
</body>
</html>
