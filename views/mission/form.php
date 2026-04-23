<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($mission) ? 'Modifier' : 'Ajouter'; ?> une Mission - MedChain</title>
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
            <h1><?php echo isset($mission) ? '✏️ Modifier la Mission' : '➕ Ajouter une Mission'; ?></h1>
            <a href="index.php?page=mission" class="btn btn-secondary">← Retour</a>
        </div>
        <div class="form-container">
            <form action="index.php?page=mission&action=<?php echo isset($mission) ? 'update&id=' . $mission['idMission'] : 'store'; ?>" 
                  method="POST" class="mission-form" id="missionForm" novalidate onsubmit="return validateForm(event)">
                
                <div class="form-group" id="group-idAmbulance">
                    <label for="idAmbulance">Ambulance ciblée *</label>
                    <select id="idAmbulance" name="idAmbulance">
                        <option value="">Sélectionner une ambulance</option>
                        <?php foreach($ambulances as $amb): ?>
                            <option value="<?php echo $amb['idAmbulance']; ?>" 
                                <?php echo (isset($oldData['idAmbulance']) && $oldData['idAmbulance'] == $amb['idAmbulance']) || (isset($mission) && $mission['idAmbulance'] == $amb['idAmbulance']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($amb['immatriculation'] . ' (' . $amb['modele'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="error-message" id="err-idAmbulance">L'ambulance est obligatoire.</div>
                    <?php if(isset($errors['idAmbulance'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['idAmbulance']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-typeMission">
                    <label for="typeMission">Type de Mission *</label>
                    <select id="typeMission" name="typeMission">
                        <option value="">Sélectionner un type</option>
                        <?php 
                            $types = ['Urgence', 'Transfert inter-hospitalier', 'Rapatriement', 'Consultation', 'Autre'];
                            $currentType = isset($oldData['typeMission']) ? $oldData['typeMission'] : (isset($mission) ? $mission['typeMission'] : '');
                            foreach($types as $t) {
                                $selected = ($currentType == $t) ? 'selected' : '';
                                echo "<option value=\"$t\" $selected>$t</option>";
                            }
                        ?>
                    </select>
                    <div class="error-message" id="err-typeMission">Veuillez choisir un type de mission.</div>
                    <?php if(isset($errors['typeMission'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['typeMission']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-dateDebut">
                    <label for="dateDebut">Date de début *</label>
                    <!-- Type text utilisé pour éviter la validation HTML5 sur certains navigateurs tout en gardant l'aspect calendrier via JS (généralement géré via des lib, mais restons avec 'date' mais form HTML5 disabled par 'novalidate') -->
                    <input type="text" id="dateDebut" name="dateDebut" placeholder="AAAA-MM-JJ"
                           value="<?php echo isset($oldData['dateDebut']) ? htmlspecialchars($oldData['dateDebut']) : (isset($mission) ? htmlspecialchars($mission['dateDebut']) : ''); ?>">
                    <div class="error-message" id="err-dateDebut">La date de début est obligatoire (Format AAAA-MM-JJ).</div>
                    <?php if(isset($errors['dateDebut'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['dateDebut']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-dateFin">
                    <label for="dateFin">Date de fin</label>
                    <input type="text" id="dateFin" name="dateFin" placeholder="AAAA-MM-JJ"
                           value="<?php echo isset($oldData['dateFin']) ? htmlspecialchars($oldData['dateFin']) : (isset($mission) ? htmlspecialchars($mission['dateFin']) : ''); ?>">
                    <div class="error-message" id="err-dateFin">La date de fin ne peut pas précéder la date de début.</div>
                    <?php if(isset($errors['dateFin'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['dateFin']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-lieuDepart">
                    <label for="lieuDepart">Lieu de départ *</label>
                    <input type="text" id="lieuDepart" name="lieuDepart" 
                           value="<?php echo isset($oldData['lieuDepart']) ? htmlspecialchars($oldData['lieuDepart']) : (isset($mission) ? htmlspecialchars($mission['lieuDepart']) : ''); ?>">
                    <div class="error-message" id="err-lieuDepart">Le lieu de départ est obligatoire (minimum 3 caractères).</div>
                    <?php if(isset($errors['lieuDepart'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['lieuDepart']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-lieuArrivee">
                    <label for="lieuArrivee">Lieu d'arrivée *</label>
                    <input type="text" id="lieuArrivee" name="lieuArrivee" 
                           value="<?php echo isset($oldData['lieuArrivee']) ? htmlspecialchars($oldData['lieuArrivee']) : (isset($mission) ? htmlspecialchars($mission['lieuArrivee']) : ''); ?>">
                    <div class="error-message" id="err-lieuArrivee">Le lieu d'arrivée est obligatoire (minimum 3 caractères).</div>
                    <?php if(isset($errors['lieuArrivee'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['lieuArrivee']); ?></p><?php endif; ?>
                </div>

                <div class="form-group" id="group-equipe">
                    <label for="equipe">Équipe médicale</label>
                    <input type="text" id="equipe" name="equipe" placeholder="Noms des médecins/infirmiers"
                           value="<?php echo isset($oldData['equipe']) ? htmlspecialchars($oldData['equipe']) : (isset($mission) ? htmlspecialchars($mission['equipe']) : ''); ?>">
                    <div class="error-message" id="err-equipe">Ce champ contient des caractères non autorisés.</div>
                    <?php if(isset($errors['equipe'])): ?><p class="error-message" style="display:block;">⚠️ <?php echo htmlspecialchars($errors['equipe']); ?></p><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="estTerminee" value="1" 
                               <?php echo (isset($oldData['estTerminee']) && $oldData['estTerminee'] == 1) ? 'checked' : ((isset($mission) && $mission['estTerminee']) ? 'checked' : ''); ?>>
                        Marquer la mission comme Terminée
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo isset($mission) ? '💾 Mettre à jour' : '✅ Enregistrer'; ?>
                    </button>
                    <button type="reset" class="btn btn-secondary">🔄 Réinitialiser</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Form Validation without HTML5
        function validateForm(event) {
            let isValid = true;

            // Clear previous errors
            document.querySelectorAll('.form-group').forEach(el => el.classList.remove('has-error'));

            // Validation idAmbulance
            let idAmbulance = document.getElementById('idAmbulance').value;
            if (idAmbulance.trim() === '') {
                document.getElementById('group-idAmbulance').classList.add('has-error');
                isValid = false;
            }

            // Validation typeMission
            let typeMission = document.getElementById('typeMission').value;
            if (typeMission.trim() === '') {
                document.getElementById('group-typeMission').classList.add('has-error');
                isValid = false;
            }

            // Validation dateDebut (Format basique AAAA-MM-JJ)
            let dateDebut = document.getElementById('dateDebut').value;
            let dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!dateRegex.test(dateDebut.trim())) {
                document.getElementById('group-dateDebut').classList.add('has-error');
                isValid = false;
            }

            // Validation dateFin (Si remplie, doit être >= dateDebut)
            let dateFin = document.getElementById('dateFin').value;
            if (dateFin.trim() !== '') {
                if(!dateRegex.test(dateFin.trim())) {
                    document.getElementById('err-dateFin').innerText = "Format attendu: AAAA-MM-JJ";
                    document.getElementById('group-dateFin').classList.add('has-error');
                    isValid = false;
                } else if(isValid && new Date(dateFin) < new Date(dateDebut)) {
                    document.getElementById('err-dateFin').innerText = "La date de fin ne peut pas précéder la date de début.";
                    document.getElementById('group-dateFin').classList.add('has-error');
                    isValid = false;
                }
            }

            // Validation lieux
            let lieuDepart = document.getElementById('lieuDepart').value;
            if (lieuDepart.trim().length < 3) {
                document.getElementById('group-lieuDepart').classList.add('has-error');
                isValid = false;
            }

            let lieuArrivee = document.getElementById('lieuArrivee').value;
            if (lieuArrivee.trim().length < 3) {
                document.getElementById('group-lieuArrivee').classList.add('has-error');
                isValid = false;
            }

            if(!isValid) {
                event.preventDefault(); // Stop form submission
            }
            
            return isValid;
        }
    </script>
</body>
</html>
