<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/BlocOperationController.php';
$ctrl = new BlocOperationController();
$interventionsData = $ctrl->getInterventionList();
$interventions = $interventionsData['success'] ? $interventionsData['data'] : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Registre des Interventions – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="../components/admin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.badge{padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;}
</style>
</head>
<body>
<div class="dashboard-container">
    <?php 
    if ($_SESSION['user_role'] === 'admin') {
        include '../components/sidebar-admin.php';
    } else {
        include '../components/sidebar-medecin.php';
    }
    ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Registre des Interventions</h1>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <div class="header-actions">
                    <a href="intervention-create.php" class="btn-primary" style="background:var(--green);color:#fff;padding:8px 16px;border-radius:8px;border:none;text-decoration:none;display:inline-block;margin-right:10px;">+ Nouvelle Intervention</a>
                    <button class="btn-primary" style="background:var(--navy);color:#fff;padding:8px 16px;border-radius:8px;border:none;">Exporter PDF</button>
                </div>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead><tr><th>Type d'Intervention</th><th>Date / Heure</th><th>Chirurgien</th><th>Salle</th><th>Niveau d'Urgence</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($interventions as $i): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($i['type']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($i['date_intervention'])) ?></td>
                            <td>Dr. <?= htmlspecialchars($i['chirurgien']) ?></td>
                            <td><?= htmlspecialchars($i['salle'] ?? 'N/A') ?></td>
                            <td>
                                <?php $niv = (int)($i['niveau_urgence'] ?? 1); ?>
                                <span class="badge" style="background:<?= $niv >= 4 ? '#FEE2E2' : '#E0F2FE' ?>;color:<?= $niv >= 4 ? '#DC2626' : '#0284C7' ?>;">
                                    Urgence <?= $niv ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <a href="intervention-edit.php?id=<?= $i['id'] ?>" class="btn-edit" style="color:var(--navy);"><i class="bi bi-pencil-square"></i></a>
                                <?php else: ?>
                                    <span style="color:var(--gray-500);"><i class="bi bi-lock-fill"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
