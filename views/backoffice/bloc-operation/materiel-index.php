<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/BlocOperationController.php';
$ctrl = new BlocOperationController();
$materielsData = $ctrl->getMaterielList();
$materiels = $materielsData['success'] ? $materielsData['data'] : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Matériel Chirurgical – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="../components/admin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>.card{overflow:hidden;margin-bottom:24px;}</style>
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
            <h1>Matériel Chirurgical</h1>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <div class="header-actions">
                    <a href="materiel-create.php" class="btn-primary" style="background:var(--green);color:#fff;padding:8px 16px;border-radius:8px;border:none;text-decoration:none;display:inline-block;margin-right:10px;">+ Ajouter Matériel</a>
                    <button class="btn-primary" style="background:var(--navy);color:#fff;padding:8px 16px;border-radius:8px;border:none;">Exporter PDF</button>
                </div>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead><tr><th>ID Matériel</th><th>Bloc / Salle</th><th>Catégorie</th><th>Stérilisation</th><th>Intervention</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($materiels as $m): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['id_materiel']) ?></strong></td>
                            <td><?= htmlspecialchars($m['bloc']) ?></td>
                            <td><span class="badge" style="background:#E0F2FE;color:#0284C7;padding:4px 8px;border-radius:6px;font-size:12px;"><?= htmlspecialchars($m['categorie']) ?></span></td>
                            <td>
                                <span style="color:<?= $m['statutSterilisation']==='sterilise'?'#16A34A':'#EF4444' ?>;">
                                    <i class="bi <?= $m['statutSterilisation']==='sterilise'?'bi-check-circle-fill':'bi-exclamation-triangle-fill' ?>"></i>
                                    <?= $m['statutSterilisation'] === 'sterilise' ? 'Stérilisé' : 'Non stérilisé' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($m['id_intervention']): ?>
                                    <small><?= htmlspecialchars($m['intervention_type']) ?><br>Dr. <?= htmlspecialchars($m['intervention_chirurgien']) ?></small>
                                <?php else: ?>
                                    <span style="color:var(--gray-500);">Aucune</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <a href="materiel-edit.php?id=<?= $m['idMateriel'] ?>" class="btn-edit" style="color:var(--navy);"><i class="bi bi-pencil-square"></i></a>
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
