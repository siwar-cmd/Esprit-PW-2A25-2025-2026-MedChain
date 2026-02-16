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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'idRDV' => $_POST['idRDV'] ?? '',
        'dateGeneration' => $_POST['dateGeneration'] ?? date('Y-m-d'),
        'piecesAApporter' => $_POST['piecesAApporter'] ?? '',
        'consignesAvantConsultation' => $_POST['consignesAvantConsultation'] ?? '',
        'tarifConsultation' => $_POST['tarifConsultation'] ?? 0,
        'modeRemboursement' => $_POST['modeRemboursement'] ?? '',
        'emailEnvoye' => isset($_POST['emailEnvoye']) ? 1 : 0,
        'calendrierAjoute' => isset($_POST['calendrierAjoute']) ? 1 : 0
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
        
        .card { background: var(--white); border-radius: var(--radius-md); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); padding: 24px; max-width: 800px;}
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--navy); font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 15px; font-family: 'DM Sans', sans-serif; }
        .form-control:focus { outline: none; border-color: var(--green); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 16px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;}
        .btn-primary { background: var(--green); color: white; }
        .btn-secondary { background: var(--gray-500); color: white; }
        
        .alert-error { background: #FEF2F2; color: #EF4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #FCA5A5; }
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
            <p>Remplissez les informations pour une consultation</p>
        </div>

        <?php if($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Rendez-vous concerné <span style="color:red">*</span></label>
                    <select name="idRDV" class="form-control" required>
                        <option value="">-- Sélectionner un rendez-vous --</option>
                        <?php foreach($rdvs_disponibles as $rdv): ?>
                            <option value="<?= $rdv['idRDV'] ?>">
                                <?= date('d/m/Y H:i', strtotime($rdv['dateHeureDebut'])) ?> - <?= htmlspecialchars($rdv['nom'] . ' ' . $rdv['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if(empty($rdvs_disponibles)): ?>
                        <small style="color: orange; display: block; margin-top: 5px;">Tous vos rendez-vous ont déjà une fiche associée.</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Date de génération</label>
                    <input type="date" name="dateGeneration" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label>Consignes avant consultation</label>
                    <textarea name="consignesAvantConsultation" class="form-control" placeholder="A jeun, boire de l'eau, etc..."></textarea>
                </div>

                <div class="form-group">
                    <label>Pièces à apporter</label>
                    <textarea name="piecesAApporter" class="form-control" placeholder="Analyses, imageries précédentes..."></textarea>
                </div>

                <div class="form-group">
                    <label>Tarif de consultation (TND)</label>
                    <input type="number" step="0.5" name="tarifConsultation" class="form-control" placeholder="ex: 50.0">
                </div>

                <div class="form-group">
                    <label>Mode de remboursement</label>
                    <input type="text" name="modeRemboursement" class="form-control" placeholder="ex: CNAM, Mutuelle...">
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="emailEnvoye" id="emailEnvoye" value="1">
                    <label for="emailEnvoye" style="margin-bottom:0;">Envoyer par email au patient</label>
                </div>

                <div class="checkbox-group" style="margin-bottom: 20px;">
                    <input type="checkbox" name="calendrierAjoute" id="calendrierAjoute" value="1">
                    <label for="calendrierAjoute" style="margin-bottom:0;">Ajouter au calendrier</label>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer la fiche</button>
                    <a href="medecin-index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
