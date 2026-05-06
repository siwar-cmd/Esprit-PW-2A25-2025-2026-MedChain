<?php
session_start();
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../controllers/PatientController.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$role = $user->getRole();
$userId = $user->getId();

// Seul un patient peut voir son dossier médical (ou un médecin via une page dédiée, mais ici on simplifie)
if ($role !== 'patient') {
    echo "<p>Seuls les patients peuvent accéder à cette section.</p>";
    exit;
}

$controller = new PatientController();
$consultations = $controller->getRecentConsultations($userId, 50);
$ordonnances = $controller->getOrdonnances($userId);
$analyses = $controller->getAnalyses($userId);

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon dossier médical - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'DM Sans',sans-serif;background:#f0faf6;padding:40px 20px;}
        .container{max-width:1200px;margin:0 auto;background:#fff;border-radius:28px;padding:32px;box-shadow:0 12px 40px rgba(0,0,0,.1);}
        h1{font-family:'Syne',sans-serif;font-size:28px;color:#1E3A52;margin-bottom:24px;}
        h2{font-size:20px;margin:24px 0 16px;color:#1E3A52;border-left:4px solid #1D9E75;padding-left:12px;}
        table{width:100%;border-collapse:collapse;margin-bottom:20px;}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #E5E7EB;}
        th{background:#F8FAFC;color:#1E3A52;}
        .badge{background:#E8F7F2;color:#1D9E75;padding:4px 10px;border-radius:20px;font-size:12px;}
    </style>
</head>
<body>
<div class="container">
    <h1><i class="bi bi-file-medical-fill"></i> Mon dossier médical</h1>

    <h2>📋 Consultations</h2>
    <?php if (empty($consultations)): ?>
        <p>Aucune consultation enregistrée.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Date</th><th>Médecin</th><th>Diagnostic</th><th>Traitement</th></tr></thead>
            <tbody>
                <?php foreach ($consultations as $c): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($c['date_consultation'])) ?></td>
                    <td>Dr. <?= e($c['medecin_prenom']) ?> <?= e($c['medecin_nom']) ?></td>
                    <td><?= e($c['diagnostic']) ?></td>
                    <td><?= e($c['traitement'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>💊 Ordonnances</h2>
    <?php if (empty($ordonnances)): ?>
        <p>Aucune ordonnance disponible.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Date</th><th>Médecin</th><th>Titre</th><th>Contenu</th></tr></thead>
            <tbody>
                <?php foreach ($ordonnances as $o): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($o['date_ordonnance'])) ?></td>
                    <td>Dr. <?= e($o['medecin_prenom']) ?> <?= e($o['medecin_nom']) ?></td>
                    <td><?= e($o['titre']) ?></td>
                    <td><?= nl2br(e($o['contenu'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>🔬 Analyses / Examens</h2>
    <?php if (empty($analyses)): ?>
        <p>Aucune analyse enregistrée.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Date</th><th>Médecin prescripteur</th><th>Type d'analyse</th><th>Résultat</th></tr></thead>
            <tbody>
                <?php foreach ($analyses as $a): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($a['date_analyse'])) ?></td>
                    <td>Dr. <?= e($a['medecin_prenom']) ?> <?= e($a['medecin_nom']) ?></td>
                    <td><?= e($a['type_analyse']) ?></td>
                    <td><?= e($a['resultat'] ?? 'En attente') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>