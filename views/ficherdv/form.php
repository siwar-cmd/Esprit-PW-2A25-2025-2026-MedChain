<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($ficheData) ? 'Modifier' : 'Ajouter'; ?> Fiche RDV - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message { color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; display: none; }
        .has-error .error-message { display: block; }
        .has-error input, .has-error select, .has-error textarea { border-color: #dc3545 !important; background-color: #fff8f8; }
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
            <h1><?php echo isset($ficheData) ? '✏️ Modifier Fiche RDV' : '➕ Nouvelle Fiche RDV'; ?></h1>
            <a href="index.php?page=ficherdv" class="btn btn-secondary">← Retour</a>
        </div>
        <div class="form-container">
            <form action="index.php?page=ficherdv&action=<?php echo isset($ficheData) ? 'update&id='.$ficheData['idFiche'] : 'store'; ?>" 
                  method="POST" id="ficheForm" novalidate onsubmit="return validateForm(event)">
                
                <div class="form-group" id="group-idRDV">
                    <label>Rendez-Vous Lié *</label>
                    <select id="idRDV" name="idRDV">
                        <option value="">-- Sélectionnez un RDV --</option>
                        <?php 
                            $selectedRdv = isset($oldData['idRDV']) ? $oldData['idRDV'] : (isset($ficheData) ? $ficheData['idRDV'] : '');
                            foreach($rdvs as $r): 
                        ?>
                            <option value="<?php echo $r['idRDV']; ?>" <?php echo $selectedRdv == $r['idRDV'] ? 'selected' : ''; ?>>
                                #<?php echo $r['idRDV']; ?> - <?php echo date('d/m/Y H:i', strtotime($r['dateHeureDebut'])); ?> (<?php echo htmlspecialchars($r['typeConsultation']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="error-message" id="err-idRDV">Veuillez rattacher cette fiche à un Rendez-Vous existant.</div>
                    <?php if(isset($errors['idRDV'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['idRDV']); ?></p><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Pièces à apporter</label>
                    <textarea name="piecesAApporter" rows="3"><?php echo isset($oldData['piecesAApporter']) ? htmlspecialchars($oldData['piecesAApporter'] ?? '') : (isset($ficheData) ? htmlspecialchars($ficheData['piecesAApporter'] ?? '') : ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Consignes Avant Consultation</label>
                    <textarea name="consignesAvantConsultation" rows="3"><?php echo isset($oldData['consignesAvantConsultation']) ? htmlspecialchars($oldData['consignesAvantConsultation'] ?? '') : (isset($ficheData) ? htmlspecialchars($ficheData['consignesAvantConsultation'] ?? '') : ''); ?></textarea>
                </div>

                <div class="form-group" id="group-tarifConsultation">
                    <label>Tarif de la consultation (€) *</label>
                    <input type="text" id="tarifConsultation" name="tarifConsultation" 
                           value="<?php echo isset($oldData['tarifConsultation']) ? htmlspecialchars($oldData['tarifConsultation'] ?? '') : (isset($ficheData) ? htmlspecialchars($ficheData['tarifConsultation'] ?? '') : ''); ?>">
                    <div class="error-message" id="err-tarifConsultation">Le tarif doit être un nombre valide (ex: 50 ou 50.50).</div>
                    <?php if(isset($errors['tarifConsultation'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['tarifConsultation']); ?></p><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Mode de Remboursement</label>
                    <input type="text" name="modeRemboursement" 
                           value="<?php echo isset($oldData['modeRemboursement']) ? htmlspecialchars($oldData['modeRemboursement'] ?? '') : (isset($ficheData) ? htmlspecialchars($ficheData['modeRemboursement'] ?? '') : ''); ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">✅ Enregistrer</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('ficheForm').reset(); document.querySelectorAll('.form-group').forEach(e=>e.classList.remove('has-error'));">🔄 Réinitialiser</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function validateForm(e) {
            let isValid = true;
            document.querySelectorAll('.form-group').forEach(el => el.classList.remove('has-error'));
            
            let rdv = document.getElementById('idRDV').value;
            if(rdv === '') { document.getElementById('group-idRDV').classList.add('has-error'); document.getElementById('err-idRDV').style.display='block'; isValid = false; }

            let regexTarif = /^\d+(\.\d{1,2})?$/;
            let tarif = document.getElementById('tarifConsultation').value.trim();
            if(!regexTarif.test(tarif)) {
                document.getElementById('group-tarifConsultation').classList.add('has-error'); document.getElementById('err-tarifConsultation').style.display='block'; isValid = false;
            }

            if(!isValid) e.preventDefault();
            return isValid;
        }
    </script>
</body>
</html>
