<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/RendezVousController.php';
require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';

$rdvController = new RendezVousController();
$ficheController = new FicheRendezVousController();
$userId = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header('Location: medecin-index.php');
    exit;
}

$idRDV = $_GET['id'];
$rdv = $rdvController->getRendezVousById($idRDV);

// Verify that this rdv belongs to this medecin
if (!$rdv || $rdv['idMedecin'] != $userId) {
    header('Location: medecin-index.php');
    exit;
}

$fiche = $ficheController->getFicheByRdvId($idRDV);

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Update RDV Status
    $dataRdv = ['statut' => $_POST['statut']];
    $resRdv = $rdvController->updateRendezVous($idRDV, $dataRdv);

    if (!$resRdv['success']) {
        $error = $resRdv['message'];
    } else {
        // 2. Update or Create Fiche
        $dataFiche = [
            'idRDV' => $idRDV,
            'piecesAApporter' => $_POST['piecesAApporter'] ?? '',
            'consignesAvantConsultation' => $_POST['consignesAvantConsultation'] ?? '',
            'tarifConsultation' => floatval($_POST['tarifConsultation'] ?? 0),
            'modeRemboursement' => $_POST['modeRemboursement'] ?? '',
            'emailEnvoye' => isset($_POST['emailEnvoye']) ? 1 : 0,
            'calendrierAjoute' => isset($_POST['calendrierAjoute']) ? 1 : 0
        ];

        if ($fiche) {
            $ficheController->updateFiche($fiche['idFiche'], $dataFiche);
        } else {
            $ficheController->createFiche($dataFiche);
        }

        $_SESSION['success_message'] = "Consultation mise à jour avec succès.";
        header('Location: medecin-index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer la Consultation - Médecin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <style>
        :root { --green: #1D9E75; --navy: #1E3A52; --gray-200: #E5E7EB; --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --radius-md: 12px; }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; padding: 40px 20px; }
        .card-mc { max-width: 800px; margin: 0 auto; background: white; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-sm); }
        .page-title { font-family: 'Syne', sans-serif; font-weight: 700; color: var(--navy); margin-bottom: 20px; }
        .btn-mc { background: var(--green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 500; }
        .btn-mc:hover { background: #0F6E56; color: white; }
    </style>
</head>
<body>

<div class="card-mc">
    <a href="medecin-index.php" class="btn btn-outline-secondary mb-4">&larr; Retour</a>
    <h1 class="page-title">Gérer la Consultation</h1>
    
    <div class="alert alert-info">
        <strong>Patient :</strong> <?= htmlspecialchars($rdv['client_nom'] . ' ' . $rdv['client_prenom']) ?><br>
        <strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($rdv['dateHeureDebut'])) ?><br>
        <strong>Motif :</strong> <?= htmlspecialchars($rdv['motif']) ?>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <h4 class="mt-4 mb-3" style="color: var(--navy); font-family: 'Syne';">Informations du Rendez-vous</h4>
        <div class="mb-3">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select">
                <option value="planifie" <?= $rdv['statut'] == 'planifie' ? 'selected' : '' ?>>Planifié</option>
                <option value="termine" <?= $rdv['statut'] == 'termine' ? 'selected' : '' ?>>Terminé</option>
                <option value="annule" <?= $rdv['statut'] == 'annule' ? 'selected' : '' ?>>Annulé</option>
            </select>
        </div>

        <h4 class="mt-5 mb-3" style="color: var(--navy); font-family: 'Syne';">Fiche Rendez-vous</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Pièces à apporter</label>
                <textarea name="piecesAApporter" class="form-control" rows="3"><?= htmlspecialchars($fiche['piecesAApporter'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Consignes avant consultation</label>
                <textarea name="consignesAvantConsultation" class="form-control" rows="3"><?= htmlspecialchars($fiche['consignesAvantConsultation'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tarif de consultation (DT)</label>
                <input type="number" step="0.5" name="tarifConsultation" class="form-control" value="<?= htmlspecialchars($fiche['tarifConsultation'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Mode de remboursement</label>
                <select name="modeRemboursement" class="form-select">
                    <option value="">Sélectionner...</option>
                    <option value="CNAM" <?= ($fiche['modeRemboursement']??'') == 'CNAM' ? 'selected' : '' ?>>CNAM</option>
                    <option value="Assurance Privée" <?= ($fiche['modeRemboursement']??'') == 'Assurance Privée' ? 'selected' : '' ?>>Assurance Privée</option>
                    <option value="Non Remboursable" <?= ($fiche['modeRemboursement']??'') == 'Non Remboursable' ? 'selected' : '' ?>>Non Remboursable</option>
                </select>
            </div>
        </div>

        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="emailEnvoye" id="emailEnvoye" <?= !empty($fiche['emailEnvoye']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="emailEnvoye">Email de confirmation envoyé</label>
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="calendrierAjoute" id="calendrierAjoute" <?= !empty($fiche['calendrierAjoute']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="calendrierAjoute">Ajouté au calendrier</label>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-mc">Enregistrer les modifications</button>
        </div>
    </form>
</div>

</body>
</html>
