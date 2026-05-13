<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';
require_once __DIR__ . '/../../../controllers/RendezVousController.php';
require_once __DIR__ . '/../../../config.php';

$ficheController = new FicheRendezVousController();
$rdvController = new RendezVousController();
$userId = $_SESSION['user_id'];
$pdo = config::getConnexion();

$errors = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idRDV = $_POST['idRDV'] ?? '';
    $motifPrincipal = $_POST['motifPrincipal'] ?? '';

    if (empty($idRDV)) {
        $errors['idRDV'] = "Le rendez-vous concerné est obligatoire.";
    }
    if (empty(trim($motifPrincipal))) {
        $errors['motifPrincipal'] = "Le motif principal est obligatoire.";
    }

    $prochainRDV = $_POST['prochainRDV'] ?? '';
    if (!empty($prochainRDV)) {
        $currentRDVDate = date('Y-m-d', strtotime($rdv_info['dateHeureDebut']));
        if ($prochainRDV <= $currentRDVDate) {
            $errors['prochainRDV'] = "La date du prochain rendez-vous doit être ultérieure à celle d'aujourd'hui.";
        }
    }

    if (empty($errors)) {
        $data = [
            'idRDV' => $idRDV,
            'dateGeneration' => date('Y-m-d'),
            'piecesAApporter' => $_POST['piecesAApporter'] ?? '',
            'tarifConsultation' => $_POST['tarifConsultation'] ?? 0,
            'modeRemboursement' => '', 
            'emailEnvoye' => 0,
            'calendrierAjoute' => 0,
            'antecedents' => $_POST['antecedents'] ?? '',
            'allergies' => $_POST['allergies'] ?? '',
            'motifPrincipal' => $motifPrincipal,
            'modeConsultation' => $_POST['modeConsultation'] ?? 'Présentiel',
            'statutPaiement' => $_POST['statutPaiement'] ?? 'En attente',
            'tensionArterielle' => $_POST['tensionArterielle'] ?? '',
            'poids' => $_POST['poids'] !== '' ? (float)$_POST['poids'] : null,
            'taille' => $_POST['taille'] !== '' ? (int)$_POST['taille'] : null,
            'temperature' => $_POST['temperature'] !== '' ? (float)$_POST['temperature'] : null,
            'prescription' => $_POST['prescription'] ?? '',
            'examensComplementaires' => $_POST['examensComplementaires'] ?? '',
            'observations' => $_POST['observations'] ?? '',
            'prochainRDV' => $_POST['prochainRDV'] !== '' ? $_POST['prochainRDV'] : null
        ];

        $result = $ficheController->createFiche($data);

        if ($result['success']) {
            $_SESSION['success_message'] = "Fiche créée avec succès";
            header("Location: medecin-index.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Récupérer l'ID du rendez-vous depuis l'URL
$idRDV_get = $_GET['idRDV'] ?? null;

if (!$idRDV_get) {
    header('Location: ../rendezvous/medecin-index.php');
    exit;
}

// Vérifier que le RDV appartient au médecin et n'a pas encore de fiche
$stmt = $pdo->prepare("
    SELECT r.idRDV, r.dateHeureDebut, u.nom, u.prenom 
    FROM rendezvous r 
    JOIN utilisateur u ON r.idClient = u.id_utilisateur 
    LEFT JOIN ficherendezvous f ON r.idRDV = f.idRDV 
    WHERE r.idRDV = ? AND r.idMedecin = ? AND f.idFiche IS NULL
");
$stmt->execute([$idRDV_get, $userId]);
$rdv_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rdv_info) {
    // Soit le RDV n'existe pas, soit il n'appartient pas au médecin, soit il a déjà une fiche
    $_SESSION['error_message'] = "Rendez-vous invalide ou fiche déjà existante.";
    header('Location: ../rendezvous/medecin-index.php');
    exit;
}

$stats = $rdvController->getStats('medecin', $userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Fiche - Médecin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../components/medecin.css">
    <style>
        :root {
            --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F0FDF9;
            --navy: #1E3A52; --gray-700: #374151; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --shadow-green: 0 8px 30px rgba(29,158,117,.18);
            --radius-sm: 8px; --radius-md: 12px; --radius-lg: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; color: var(--gray-700); min-height: 100vh; }
        
        .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        
        .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        
        .dashboard-main { padding: 32px 40px; }
        .dashboard-header { margin-bottom: 32px; }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--navy); }
        
        .card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); padding: 32px; max-width: 900px;}
        
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--navy); font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 15px; transition: all 0.2s; }
        .form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }
        
        .checkbox-container { display: flex; gap: 24px; align-items: center; flex-wrap: wrap; padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid var(--gray-200); }
        .checkbox-item { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; }
        .radio-container { display: flex; gap: 20px; flex-wrap: wrap; }
        .radio-item { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; }

        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.25s; }
        .btn-primary { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; box-shadow: 0 4px 12px rgba(29,158,117,0.2); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(29,158,117,0.3); }
        .btn-secondary { background: #f1f5f9; color: var(--navy); }
        
        .alert-error { background: #FEF2F2; color: #EF4444; padding: 15px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #EF4444; display: flex; align-items: center; gap: 10px; }
        .field-error { color: #EF4444; font-size: 12px; margin-top: 6px; font-weight: 500; }
        .is-invalid { border-color: #EF4444 !important; }

        /* BMI & Alert Styles */
        .bmi-container { margin-top: 15px; padding: 12px; border-radius: 10px; display: none; align-items: center; gap: 12px; transition: all 0.3s ease; border: 1px solid rgba(0,0,0,0.05); }
        .bmi-badge { padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 12px; text-transform: uppercase; color: white; }
        .bmi-value { font-weight: 800; font-size: 18px; color: var(--navy); }
        .bmi-label { font-size: 13px; font-weight: 500; }
        
        .bmi-maigreur { background: #eff6ff; border-color: #bfdbfe; }
        .bmi-maigreur .bmi-badge { background: #3b82f6; }
        .bmi-normal { background: #f0fdf4; border-color: #bbf7d0; }
        .bmi-normal .bmi-badge { background: #22c55e; }
        .bmi-surpoids { background: #fffbeb; border-color: #fef3c7; }
        .bmi-surpoids .bmi-badge { background: #f59e0b; }
        .bmi-obesite { background: #fef2f2; border-color: #fecaca; }
        .bmi-obesite .bmi-badge { background: #ef4444; }

        .critical-alert { margin-top: 8px; color: #ef4444; font-size: 11px; font-weight: 700; display: none; align-items: center; gap: 4px; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
        .input-critical { border-color: #ef4444 !important; background-color: #fff1f2 !important; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Créer une Fiche Médicale</h1>
            <p>Remplissez les informations de la consultation</p>
        </div>

        <?php if($error): ?>
            <div class="alert-error"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Rendez-vous concerné</label>
                    <div style="background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--gray-200); color: var(--navy); font-weight: 600;">
                        <i class="bi bi-calendar-event" style="margin-right: 8px; color: var(--green);"></i>
                        <?= date('d/m/Y H:i', strtotime($rdv_info['dateHeureDebut'])) ?> - <?= htmlspecialchars($rdv_info['nom'] . ' ' . $rdv_info['prenom']) ?>
                    </div>
                    <input type="hidden" name="idRDV" value="<?= $rdv_info['idRDV'] ?>">
                </div>

                <div class="form-group">
                    <label>Motif principal <span style="color:red">*</span></label>
                    <input type="text" name="motifPrincipal" class="form-control <?= isset($errors['motifPrincipal']) ? 'is-invalid' : '' ?>" placeholder="ex: Douleur abdominale" value="<?= htmlspecialchars($_POST['motifPrincipal'] ?? '') ?>">
                    <?php if(isset($errors['motifPrincipal'])): ?><span class="field-error"><?= $errors['motifPrincipal'] ?></span><?php endif; ?>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                  <div class="form-group"><label>Antécédents</label><input type="text" name="antecedents" class="form-control" placeholder="ex: Diabète type 2"></div>
                  <div class="form-group"><label>Allergies</label><input type="text" name="allergies" class="form-control" placeholder="ex: Pénicilline"></div>
                </div>

                <div style="background: var(--green-pale); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(29,158,117,0.1);">
                    <h3 style="font-size: 16px; color: var(--green-dark); margin-bottom: 15px; font-family: 'Syne', sans-serif;"><i class="bi bi-activity"></i> Constantes Vitales</h3>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Tension Artérielle</label>
                            <input type="text" id="tensionArterielle" name="tensionArterielle" class="form-control" placeholder="ex: 12/8">
                            <div id="tension-alert" class="critical-alert"><i class="bi bi-exclamation-octagon-fill"></i> Tension trop élevée !</div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Poids (kg)</label>
                            <input type="number" step="0.1" id="poids" name="poids" class="form-control" placeholder="ex: 75.5">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Taille (cm)</label>
                            <input type="number" id="taille" name="taille" class="form-control" placeholder="ex: 175">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Température (°C)</label>
                            <input type="number" step="0.1" id="temperature" name="temperature" class="form-control" placeholder="ex: 37.2">
                            <div id="temp-alert" class="critical-alert"><i class="bi bi-thermometer-high"></i> Fièvre détectée !</div>
                        </div>
                    </div>
                    
                    <div id="bmi-display" class="bmi-container">
                        <span class="bmi-badge" id="bmi-status">Normal</span>
                        <span class="bmi-value" id="bmi-val">--.-</span>
                        <span class="bmi-label" id="bmi-text">Complétez le poids et la taille pour calculer l'IMC.</span>
                    </div>
                </div>

                <div class="ai-assistant-card" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(34,197,94,0.1);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                        <h3 style="color: var(--green-dark); font-size: 16px; margin:0; font-family: 'Syne', sans-serif;"><i class="bi bi-robot" style="font-size: 18px; margin-right: 5px;"></i> Assistant Médical IA</h3>
                        <span style="font-size: 12px; background: white; padding: 4px 8px; border-radius: 20px; color: var(--green); font-weight: 700; border: 1px solid #bbf7d0;">BETA</span>
                    </div>
                    <p style="font-size: 13px; color: var(--green-dark); margin-bottom: 12px;">Décrivez rapidement le diagnostic ou les symptômes pour pré-remplir les champs ci-dessous.</p>
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="ai-prompt" class="form-control" style="background: white; border-color: #bbf7d0;" placeholder="ex: 'Patient souffrant d'une angine sévère, fièvre à 39'">
                        <button type="button" id="ai-btn" class="btn btn-primary" onclick="askChatbot()"><i class="bi bi-magic"></i> Générer</button>
                    </div>
                    <div id="ai-loading" style="display:none; margin-top: 15px; font-size: 13px; color: var(--green-dark); text-align: center;">
                        <i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite; display: inline-block;"></i> Analyse IA en cours...
                        <style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
                    </div>
                    <div id="ai-response-container" style="display:none; margin-top: 15px; background: white; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0;">
                        <p id="ai-response-text" style="font-size: 14px; margin-bottom: 10px; font-weight: 500; color: var(--navy);"></p>
                        <button type="button" class="btn btn-secondary" onclick="applyAiSuggestions()" style="font-size: 13px; padding: 8px 12px; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;"><i class="bi bi-clipboard-check"></i> Appliquer aux champs de la fiche</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Prescription / Traitement</label>
                    <textarea name="prescription" class="form-control" rows="3" placeholder="Médicaments, doses, durée..."></textarea>
                </div>

                <div class="form-group">
                    <label>Examens complémentaires demandés</label>
                    <input type="text" name="examensComplementaires" class="form-control" placeholder="ex: Radio thorax, Bilan sanguin complet">
                </div>

                <div class="form-group">
                    <label>Observations & Notes</label>
                    <textarea name="observations" class="form-control" rows="2" placeholder="Remarques additionnelles..."></textarea>
                </div>

                <div class="form-group">
                    <label>Prochain rendez-vous recommandé</label>
                    <input type="date" name="prochainRDV" class="form-control <?= isset($errors['prochainRDV']) ? 'is-invalid' : '' ?>" 
                           min="<?= date('Y-m-d', strtotime($rdv_info['dateHeureDebut'] . ' +1 day')) ?>"
                           value="<?= htmlspecialchars($_POST['prochainRDV'] ?? '') ?>">
                    <?php if(isset($errors['prochainRDV'])): ?><span class="field-error"><?= $errors['prochainRDV'] ?></span><?php endif; ?>
                </div>


                <div class="form-group">
                    <label>Pièces à apporter</label>
                    <input type="text" name="piecesAApporter" class="form-control" placeholder="ex: Analyse sanguine, Échographie">
                </div>

                <div style="display:grid; grid-template-columns: 150px 1fr; gap:30px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--gray-200); margin-bottom:24px;">
                    <div class="form-group" style="margin-bottom:0;"><label>Tarif (TND)</label><input type="number" step="0.5" name="tarifConsultation" class="form-control" placeholder="80.0"></div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Paiement</label>
                        <div class="radio-container">
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="En attente" checked> En attente</label>
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="Payé partiellement"> Payé partiellement</label>
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="Total"> Total</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mode de consultation</label>
                    <div class="radio-container">
                        <label class="radio-item"><input type="radio" name="modeConsultation" value="Présentiel" checked> Présentiel</label>
                        <label class="radio-item"><input type="radio" name="modeConsultation" value="Téléconsultation"> Téléconsultation</label>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Créer la fiche</button>
                    <a href="../rendezvous/medecin-index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </main>
</div>
<script>
    const autreConsigne = document.getElementById('autre_consigne');
    if (autreConsigne) {
        autreConsigne.addEventListener('input', function() {
            const checkAutre = document.getElementById('check_autre');
            if (checkAutre) checkAutre.checked = (this.value.trim() !== '');
        });
    }

    // --- Analyse Intelligente des Constantes ---
    const inputPoids = document.getElementById('poids');
    const inputTaille = document.getElementById('taille');
    const inputTemp = document.getElementById('temperature');
    const inputTension = document.getElementById('tensionArterielle');

    const bmiDisplay = document.getElementById('bmi-display');
    const bmiVal = document.getElementById('bmi-val');
    const bmiStatus = document.getElementById('bmi-status');
    const bmiText = document.getElementById('bmi-text');

    const tempAlert = document.getElementById('temp-alert');
    const tensionAlert = document.getElementById('tension-alert');

    function calculateBMI() {
        if (!inputPoids || !inputTaille || !bmiDisplay) return;
        
        const poids = parseFloat(inputPoids.value);
        const taille = parseFloat(inputTaille.value) / 100; // cm to m

        if (poids > 0 && taille > 0) {
            const imc = (poids / (taille * taille)).toFixed(1);
            bmiVal.innerText = imc;
            bmiDisplay.style.display = 'flex';
            
            bmiDisplay.classList.remove('bmi-maigreur', 'bmi-normal', 'bmi-surpoids', 'bmi-obesite');
            
            if (imc < 18.5) {
                bmiStatus.innerText = "Maigreur";
                bmiText.innerText = "Le patient est en insuffisance pondérale.";
                bmiDisplay.classList.add('bmi-maigreur');
            } else if (imc < 25) {
                bmiStatus.innerText = "Normal";
                bmiText.innerText = "L'indice de masse corporelle est normal.";
                bmiDisplay.classList.add('bmi-normal');
            } else if (imc < 30) {
                bmiStatus.innerText = "Surpoids";
                bmiText.innerText = "Le patient est en surpoids.";
                bmiDisplay.classList.add('bmi-surpoids');
            } else {
                bmiStatus.innerText = "Obésité";
                bmiText.innerText = "Le patient est en état d'obésité.";
                bmiDisplay.classList.add('bmi-obesite');
            }
        } else {
            bmiDisplay.style.display = 'none';
        }
    }

    function checkThresholds() {
        if (!inputTemp || !inputTension) return;

        // Température
        const temp = parseFloat(inputTemp.value);
        if (temp >= 39) {
            inputTemp.classList.add('input-critical');
            if (tempAlert) tempAlert.style.display = 'flex';
        } else {
            inputTemp.classList.remove('input-critical');
            if (tempAlert) tempAlert.style.display = 'none';
        }

        // Tension
        const tensionValue = inputTension.value.trim();
        const tensionMatch = tensionValue.match(/^(\d+)[/](\d+)$/);
        if (tensionMatch) {
            let systolique = parseInt(tensionMatch[1]);
            let diastolique = parseInt(tensionMatch[2]);
            if (systolique < 30) systolique *= 10;
            if (diastolique < 20) diastolique *= 10;

            if (systolique >= 140 || diastolique >= 90) {
                inputTension.classList.add('input-critical');
                if (tensionAlert) tensionAlert.style.display = 'flex';
            } else {
                inputTension.classList.remove('input-critical');
                if (tensionAlert) tensionAlert.style.display = 'none';
            }
        } else {
            inputTension.classList.remove('input-critical');
            if (tensionAlert) tensionAlert.style.display = 'none';
        }
    }

    if (inputPoids) inputPoids.addEventListener('input', calculateBMI);
    if (inputTaille) inputTaille.addEventListener('input', calculateBMI);
    if (inputTemp) inputTemp.addEventListener('input', checkThresholds);
    if (inputTension) inputTension.addEventListener('input', checkThresholds);

    // Initialisation
    calculateBMI();
    checkThresholds();

    // --- Assistant IA ---
    let aiSuggestions = {};

    async function askChatbot() {
        const prompt = document.getElementById('ai-prompt').value;
        if (!prompt.trim()) {
            Swal.fire('Attention', 'Veuillez décrire les symptômes ou le diagnostic.', 'warning');
            return;
        }

        const btn = document.getElementById('ai-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        document.getElementById('ai-loading').style.display = 'block';
        document.getElementById('ai-response-container').style.display = 'none';

        try {
            const response = await fetch('/projet/controllers/ChatbotController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: prompt })
            });
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('ai-response-text').innerText = data.response;
                aiSuggestions = data.fields || {};
                document.getElementById('ai-response-container').style.display = 'block';
            } else {
                Swal.fire('Erreur', data.message || 'Erreur lors de la génération.', 'error');
            }
        } catch (e) {
            Swal.fire('Erreur', 'Impossible de se connecter à l\'Assistant IA.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-magic"></i> Générer';
            document.getElementById('ai-loading').style.display = 'none';
        }
    }

    function applyAiSuggestions() {
        let appliedCount = 0;
        
        const fieldsMapping = {
            'prescription': 'prescription',
            'examensComplementaires': 'examensComplementaires',
            'observations': 'observations',
            'piecesAApporter': 'piecesAApporter'
        };

        for (const [aiKey, formName] of Object.entries(fieldsMapping)) {
            if (aiSuggestions[aiKey]) {
                const el = document.querySelector(`[name="${formName}"]`);
                if (el) {
                    el.value = aiSuggestions[aiKey];
                    el.style.transition = 'background-color 0.4s ease';
                    el.style.backgroundColor = '#dcfce7';
                    setTimeout(() => el.style.backgroundColor = '', 2000);
                    appliedCount++;
                }
            }
        }
        
        if (appliedCount > 0) {
            Swal.fire({
                title: 'Succès',
                text: appliedCount + ' champ(s) pré-rempli(s) par l\'IA.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            Swal.fire('Info', 'Aucun champ correspondant n\'a pu être rempli.', 'info');
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
