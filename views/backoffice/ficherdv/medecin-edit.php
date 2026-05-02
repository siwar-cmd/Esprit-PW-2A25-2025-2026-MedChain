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
$pdo = config::getConnexion();
$error = null;
$userId = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: medecin-index.php");
    exit;
}
$idFiche = $_GET['id'];

// Get existing fiche data
$stmt = $pdo->prepare("SELECT f.*, r.idClient, r.dateHeureDebut, u.nom, u.prenom 
                       FROM ficherendezvous f 
                       JOIN rendezvous r ON f.idRDV = r.idRDV 
                       JOIN utilisateur u ON r.idClient = u.id_utilisateur 
                       WHERE f.idFiche = ? AND r.idMedecin = ?");
$stmt->execute([$idFiche, $userId]);
$fiche = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fiche) {
    header("Location: medecin-index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motifPrincipal = $_POST['motifPrincipal'] ?? '';

    if (empty(trim($motifPrincipal))) {
        $errors['motifPrincipal'] = "Le motif principal est obligatoire.";
    }

    $prochainRDV_input = $_POST['prochainRDV'] ?? '';
    if (!empty($prochainRDV_input)) {
        $currentRDVDate = date('Y-m-d', strtotime($fiche['dateHeureDebut']));
        if ($prochainRDV_input <= $currentRDVDate) {
            $errors['prochainRDV'] = "La date du prochain rendez-vous doit être ultérieure à celle du rendez-vous actuel.";
        }
    }

    if (empty($errors)) {
        $data = [
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

        $result = $ficheController->updateFiche($idFiche, $data);

        if ($result['success']) {
            $_SESSION['success_message'] = "Fiche mise à jour avec succès";
            header("Location: medecin-index.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$stats = $rdvController->getStats('medecin', $userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la Fiche - Médecin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
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
        
        /* Sidebar Premium */
        .dashboard-sidebar { background: linear-gradient(160deg, #ffffff 0%, #f0fdf9 60%, #e6faf3 100%); border-right: 1px solid rgba(29,158,117,.15); position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; box-shadow: 4px 0 24px rgba(29,158,117,.08); }
        .sidebar-logo-zone { padding: 26px 22px 20px; border-bottom: 1px solid rgba(29,158,117,.12); }
        .sidebar-logo-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar-logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(29,158,117,.35); color: white; }
        .sidebar-logo-text { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--navy); letter-spacing: -.3px; }
        .sidebar-logo-text span { color: var(--green); }
        .sidebar-tagline { font-size: 11px; color: var(--gray-500); margin-top: 3px; letter-spacing: .03em; }
        
        .sidebar-user-card { margin: 18px 16px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-lg); padding: 18px 16px; box-shadow: var(--shadow-green); color: white; }
        .sidebar-user-avatar { width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,.25); border: 2.5px solid rgba(255,255,255,.5); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .sidebar-user-name { font-size: 15px; font-weight: 700; }
        .sidebar-user-role { font-size: 11px; opacity: 0.9; margin-top: 4px; display: flex; align-items: center; gap: 5px; }

        .sidebar-stats-widget { margin: 0 16px 12px; padding: 12px; background: white; border-radius: 12px; border: 1px solid rgba(29,158,117,0.1); }
        .sidebar-stats-label { font-size: 11px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; margin-bottom: 8px; }
        .sidebar-stats-row { display: flex; justify-content: space-between; }
        .sidebar-stat-num { font-size: 16px; font-weight: 700; color: var(--green); }
        .sidebar-stat-lbl { font-size: 10px; color: var(--gray-500); }

        .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 3px; padding: 12px; }
        .sidebar-nav-item { display: flex; align-items: center; gap: 13px; padding: 11px 14px; color: var(--gray-500); text-decoration: none; border-radius: 12px; transition: all 0.25s; font-size: 14px; font-weight: 500; }
        .sidebar-nav-item i { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(29,158,117,.08); color: var(--green); }
        .sidebar-nav-item:hover, .sidebar-nav-item.active { background: rgba(29,158,117,.07); color: var(--green-dark); }
        .sidebar-nav-item.active i { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; }
        
        .sidebar-footer { padding: 16px; border-top: 1px solid rgba(29,158,117,.10); margin-top: auto; }
        .sidebar-footer-back { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 12px; background: var(--green-pale); color: var(--green-dark); font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid rgba(29,158,117,.2); }

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
    <aside class="dashboard-sidebar">
      <div class="sidebar-logo-zone">
        <a href="../../frontoffice/home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div>
            <div class="sidebar-logo-text">Med<span>Chain</span></div>
            <div class="sidebar-tagline">Espace Médecin</div>
          </div>
        </a>
      </div>
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-badge-fill"></i></div>
        <div class="sidebar-user-name">Dr. <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></div>
        <div class="sidebar-user-role"><i class="bi bi-heart-pulse-fill"></i> Médecin</div>
      </div>
      <div class="sidebar-stats-widget">
        <div class="sidebar-stats-label">Mes statistiques</div>
        <div class="sidebar-stats-row">
          <div><div class="sidebar-stat-num"><?= $stats['total'] ?? 0 ?></div><div class="sidebar-stat-lbl">Consultations</div></div>
          <div><div class="sidebar-stat-num"><?= $stats['ce_mois'] ?? 0 ?></div><div class="sidebar-stat-lbl">Ce mois</div></div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <a href="../rendezvous/medecin-index.php" class="sidebar-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
        <a href="medecin-index.php" class="sidebar-nav-item active"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout" onclick="confirmSwal(event, this, 'Déconnexion ?', 'Voulez-vous vraiment vous déconnecter ?')"><i class="bi bi-box-arrow-left"></i> Déconnexion</a>
        <div style="margin-top:10px;"><a href="../../frontoffice/home/index.php" class="sidebar-footer-back"><i class="bi bi-arrow-left"></i> Retour au site</a></div>
      </div>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Modifier la Fiche #<?= htmlspecialchars($fiche['idFiche']) ?></h1>
            <p>Consultation avec <?= htmlspecialchars($fiche['nom'] . ' ' . $fiche['prenom']) ?> le <?= date('d/m/Y H:i', strtotime($fiche['dateHeureDebut'])) ?></p>
        </div>

        <?php if($error): ?>
            <div class="alert-error"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Rendez-vous concerné</label>
                    <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($fiche['dateHeureDebut'])) ?> - <?= htmlspecialchars($fiche['nom'] . ' ' . $fiche['prenom']) ?>" readonly style="background: #f8fafc;">
                </div>

                <div class="form-group">
                    <label>Motif principal <span style="color:red">*</span></label>
                    <input type="text" name="motifPrincipal" class="form-control <?= isset($errors['motifPrincipal']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['motifPrincipal'] ?? $fiche['motifPrincipal'] ?? '') ?>">
                    <?php if(isset($errors['motifPrincipal'])): ?><span class="field-error"><?= $errors['motifPrincipal'] ?></span><?php endif; ?>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                  <div class="form-group"><label>Antécédents</label><input type="text" name="antecedents" class="form-control" value="<?= htmlspecialchars($fiche['antecedents'] ?? '') ?>"></div>
                  <div class="form-group"><label>Allergies</label><input type="text" name="allergies" class="form-control" value="<?= htmlspecialchars($fiche['allergies'] ?? '') ?>"></div>
                </div>

                <div style="background: var(--green-pale); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(29,158,117,0.1);">
                    <h3 style="font-size: 16px; color: var(--green-dark); margin-bottom: 15px; font-family: 'Syne', sans-serif;"><i class="bi bi-activity"></i> Constantes Vitales</h3>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Tension Artérielle</label>
                            <input type="text" id="tensionArterielle" name="tensionArterielle" class="form-control" value="<?= htmlspecialchars($fiche['tensionArterielle'] ?? '') ?>">
                            <div id="tension-alert" class="critical-alert"><i class="bi bi-exclamation-octagon-fill"></i> Tension trop élevée !</div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Poids (kg)</label>
                            <input type="number" step="0.1" id="poids" name="poids" class="form-control" value="<?= htmlspecialchars($fiche['poids'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Taille (cm)</label>
                            <input type="number" id="taille" name="taille" class="form-control" value="<?= htmlspecialchars($fiche['taille'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Température (°C)</label>
                            <input type="number" step="0.1" id="temperature" name="temperature" class="form-control" value="<?= htmlspecialchars($fiche['temperature'] ?? '') ?>">
                            <div id="temp-alert" class="critical-alert"><i class="bi bi-thermometer-high"></i> Fièvre détectée !</div>
                        </div>
                    </div>
                    
                    <div id="bmi-display" class="bmi-container">
                        <span class="bmi-badge" id="bmi-status">Normal</span>
                        <span class="bmi-value" id="bmi-val">--.-</span>
                        <span class="bmi-label" id="bmi-text">Complétez le poids et la taille pour calculer l'IMC.</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Prescription / Traitement</label>
                    <textarea name="prescription" class="form-control" rows="3"><?= htmlspecialchars($fiche['prescription'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Examens complémentaires demandés</label>
                    <input type="text" name="examensComplementaires" class="form-control" value="<?= htmlspecialchars($fiche['examensComplementaires'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Observations & Notes</label>
                    <textarea name="observations" class="form-control" rows="2"><?= htmlspecialchars($fiche['observations'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Prochain rendez-vous recommandé</label>
                    <input type="date" name="prochainRDV" class="form-control <?= isset($errors['prochainRDV']) ? 'is-invalid' : '' ?>" 
                           min="<?= date('Y-m-d', strtotime($fiche['dateHeureDebut'] . ' +1 day')) ?>"
                           value="<?= htmlspecialchars($_POST['prochainRDV'] ?? $fiche['prochainRDV'] ?? '') ?>">
                    <?php if(isset($errors['prochainRDV'])): ?><span class="field-error"><?= $errors['prochainRDV'] ?></span><?php endif; ?>
                </div>


                <div class="form-group">
                    <label>Pièces à apporter</label>
                    <input type="text" name="piecesAApporter" class="form-control" value="<?= htmlspecialchars($fiche['piecesAApporter'] ?? '') ?>">
                </div>

                <div style="display:grid; grid-template-columns: 150px 1fr; gap:30px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--gray-200); margin-bottom:24px;">
                    <div class="form-group" style="margin-bottom:0;"><label>Tarif (TND)</label><input type="number" step="0.5" name="tarifConsultation" class="form-control" value="<?= htmlspecialchars($fiche['tarifConsultation'] ?? '0') ?>"></div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Paiement</label>
                        <div class="radio-container">
                            <?php $sp = $fiche['statutPaiement'] ?? 'En attente'; ?>
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="En attente" <?= $sp == 'En attente' ? 'checked' : '' ?>> En attente</label>
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="Payé partiellement" <?= $sp == 'Payé partiellement' ? 'checked' : '' ?>> Payé partiellement</label>
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="Total" <?= $sp == 'Total' ? 'checked' : '' ?>> Total</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mode de consultation</label>
                    <div class="radio-container">
                        <?php $mc = $fiche['modeConsultation'] ?? 'Présentiel'; ?>
                        <label class="radio-item"><input type="radio" name="modeConsultation" value="Présentiel" <?= $mc == 'Présentiel' ? 'checked' : '' ?>> Présentiel</label>
                        <label class="radio-item"><input type="radio" name="modeConsultation" value="Téléconsultation" <?= $mc == 'Téléconsultation' ? 'checked' : '' ?>> Téléconsultation</label>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Mettre à jour</button>
                    <a href="medecin-index.php" class="btn btn-secondary">Annuler</a>
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

    // Initialisation au chargement
    calculateBMI();
    checkThresholds();
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
