<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';
require_once __DIR__ . '/../../../config.php';

$ficheController = new FicheRendezVousController();
$userId = $_SESSION['user_id'];
$pdo = config::getConnexion();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idRDV = $_POST['idRDV'] ?? '';
    $motifPrincipal = $_POST['motifPrincipal'] ?? '';

    if (empty($idRDV)) {
        $errors['idRDV'] = "Le rendez-vous concerné est obligatoire.";
    }
    if (empty(trim($motifPrincipal))) {
        $errors['motifPrincipal'] = "Le motif principal est obligatoire.";
    }

    if (empty($errors)) {
        $consignes = $_POST['consignes'] ?? [];
        if (!empty($_POST['autre_consigne'])) {
            $consignes[] = "Autre : " . $_POST['autre_consigne'];
        }
        $consignes_str = implode(', ', $consignes);

        $data = [
            'idRDV' => $idRDV,
            'dateGeneration' => date('Y-m-d'),
            'piecesAApporter' => $_POST['piecesAApporter'] ?? '',
            'consignesAvantConsultation' => $consignes_str,
            'tarifConsultation' => $_POST['tarifConsultation'] ?? 0,
            'modeRemboursement' => '', 
            'emailEnvoye' => 0,
            'calendrierAjoute' => 0,
            'antecedents' => $_POST['antecedents'] ?? '',
            'allergies' => $_POST['allergies'] ?? '',
            'motifPrincipal' => $motifPrincipal,
            'modeConsultation' => $_POST['modeConsultation'] ?? 'Présentiel',
            'statutPaiement' => $_POST['statutPaiement'] ?? 'En attente'
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

// Récupérer les RDVs du médecin qui n'ont pas encore de fiche
$stmt = $pdo->prepare("
    SELECT r.idRDV, r.dateHeureDebut, u.nom, u.prenom 
    FROM rendezvous r 
    JOIN utilisateur u ON r.idClient = u.id_utilisateur 
    LEFT JOIN ficherendezvous f ON r.idRDV = f.idRDV 
    WHERE r.idMedecin = ? AND f.idFiche IS NULL 
    ORDER BY r.dateHeureDebut DESC
");
$stmt->execute([$userId]);
$rdvs_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Fiche - Médecin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green: #1D9E75; --navy: #1E3A52; --gray-500: #6B7280; --gray-200: #E5E7EB;
            --white: #ffffff; --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --radius-md: 12px;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; min-height: 100vh; }
        .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .dashboard-sidebar { background: #0F172A; height: 100vh; position: sticky; top: 0; display: flex; flex-direction: column; }
        .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; text-decoration: none;}
        .dashboard-logo-text span { color: var(--green); }
        .dashboard-nav { padding: 20px 12px; display: flex; flex-direction: column; gap: 5px; }
        .dashboard-nav-item { padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .dashboard-nav-item:hover, .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }
        
        .dashboard-main { padding: 32px 40px; }
        .dashboard-header { margin-bottom: 32px; }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--navy); }
        
        .card { background: var(--white); border-radius: var(--radius-md); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); padding: 32px; max-width: 900px;}
        
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 250px; margin-bottom: 20px; }
        .form-group.full-width { flex: 100%; margin-bottom: 20px;}
        .form-group label { display: block; margin-bottom: 8px; color: var(--navy); font-weight: 600; font-size: 14px; }
        .form-group label span.required { color: red; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 15px; font-family: 'DM Sans', sans-serif; transition: border-color 0.2s ease; }
        .form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }
        
        .checkbox-container { display: flex; gap: 24px; align-items: center; flex-wrap: wrap; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid var(--gray-200); }
        .checkbox-item { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .checkbox-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--green); cursor: pointer; }
        
        .radio-container { display: flex; gap: 24px; align-items: center; }
        .radio-item { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .radio-item input[type="radio"] { width: 18px; height: 18px; accent-color: var(--green); cursor: pointer; }

        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 16px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.2s ease;}
        .btn-primary { background: var(--green); color: white; box-shadow: 0 4px 6px rgba(29,158,117,0.2); }
        .btn-primary:hover { background: #168260; transform: translateY(-1px); box-shadow: 0 6px 12px rgba(29,158,117,0.3); }
        .btn-secondary { background: #f1f5f9; color: var(--navy); border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; }
        
        .alert-error { background: #FEF2F2; color: #EF4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #FCA5A5; }
        .field-error { color: #EF4444; font-size: 13px; margin-top: 6px; display: block; font-weight: 500;}
        .form-control.is-invalid { border-color: #EF4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
        .input-group { display: flex; align-items: center; gap: 10px; }
        .input-group input[type="text"] { flex: 1; }
        
        .section-title { font-size: 18px; color: var(--navy); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--gray-200); font-family: 'Syne', sans-serif;}
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="dashboard-logo">
            <a href="#" class="dashboard-logo-text">Med<span>Chain</span></a>
        </div>
        <nav class="dashboard-nav">
            <a href="../rendezvous/medecin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
            <a href="medecin-index.php" class="dashboard-nav-item active"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item" style="color: #F87171; margin-top: auto;"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>

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
                
                <div class="form-group full-width">
                    <label>Rendez-vous concerné <span class="required">*</span></label>
                    <select name="idRDV" class="form-control <?= isset($errors['idRDV']) ? 'is-invalid' : '' ?>">
                        <option value="">-- Sélectionner un rendez-vous --</option>
                        <?php foreach($rdvs_disponibles as $rdv): ?>
                            <option value="<?= $rdv['idRDV'] ?>" <?= (isset($_POST['idRDV']) && $_POST['idRDV'] == $rdv['idRDV']) ? 'selected' : '' ?>>
                                <?= date('d/m/Y H:i', strtotime($rdv['dateHeureDebut'])) ?> - <?= htmlspecialchars($rdv['nom'] . ' ' . $rdv['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if(isset($errors['idRDV'])): ?>
                        <span class="field-error"><i class="bi bi-exclamation-circle"></i> <?= $errors['idRDV'] ?></span>
                    <?php endif; ?>
                    <?php if(empty($rdvs_disponibles)): ?>
                        <small style="color: orange; display: block; margin-top: 5px;"><i class="bi bi-info-circle"></i> Tous vos rendez-vous ont déjà une fiche associée.</small>
                    <?php endif; ?>
                </div>

                <div class="form-group full-width">
                    <label>Motif principal <span class="required">*</span></label>
                    <input type="text" name="motifPrincipal" class="form-control <?= isset($errors['motifPrincipal']) ? 'is-invalid' : '' ?>" placeholder="ex: Douleur abdominale" value="<?= htmlspecialchars($_POST['motifPrincipal'] ?? '') ?>">
                    <?php if(isset($errors['motifPrincipal'])): ?>
                        <span class="field-error"><i class="bi bi-exclamation-circle"></i> <?= $errors['motifPrincipal'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Antécédents</label>
                        <input type="text" name="antecedents" class="form-control" placeholder="ex: Diabète type 2">
                    </div>
                    <div class="form-group">
                        <label>Allergies</label>
                        <input type="text" name="allergies" class="form-control" placeholder="ex: Pénicilline">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Consignes avant consultation (cochez)</label>
                    <div class="checkbox-container">
                        <label class="checkbox-item">
                            <input type="checkbox" name="consignes[]" value="A jeun"> A jeun
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="consignes[]" value="Boire eau"> Boire eau
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="consignes[]" value="Arrêter aspirine"> Arrêter aspirine
                        </label>
                        <div class="input-group" style="flex:1; min-width: 200px;">
                            <label class="checkbox-item">
                                <input type="checkbox" id="check_autre" onchange="document.getElementById('autre_consigne').focus()"> Autre :
                            </label>
                            <input type="text" name="autre_consigne" id="autre_consigne" class="form-control" placeholder="Précisez..." style="padding: 8px 12px; height: 38px;">
                        </div>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Pièces à apporter</label>
                    <input type="text" name="piecesAApporter" class="form-control" placeholder="ex: Analyse sanguine 05/05/26, Échographie">
                </div>

                <div class="form-row" style="align-items: center; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid var(--gray-200); margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Tarif (TND)</label>
                        <input type="number" step="0.5" name="tarifConsultation" class="form-control" placeholder="80.0" style="max-width: 150px;">
                    </div>
                    
                    <div style="width: 1px; height: 50px; background: #e2e8f0; margin: 0 20px;"></div>
                    
                    <div class="form-group" style="margin-bottom:0; flex:2;">
                        <label>Paiement</label>
                        <div class="radio-container">
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="En attente" checked> En attente</label>
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="Payé partiellement"> Payé partiellement</label>
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="Total"> Total</label>
                        </div>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Mode de consultation</label>
                    <div class="radio-container">
                        <label class="radio-item"><input type="radio" name="modeConsultation" value="Présentiel" checked> Présentiel</label>
                        <label class="radio-item"><input type="radio" name="modeConsultation" value="Téléconsultation"> Téléconsultation</label>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 40px; border-top: 1px solid var(--gray-200); padding-top: 24px;">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle" style="font-size: 1.2rem;"></i> Enregistrer la fiche</button>
                    <a href="medecin-index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </main>
</div>
<script>
    // Link "Autre" input to its checkbox
    document.getElementById('autre_consigne').addEventListener('input', function() {
        if(this.value.trim() !== '') {
            document.getElementById('check_autre').checked = true;
        } else {
            document.getElementById('check_autre').checked = false;
        }
    });
</script>
</body>
</html>
