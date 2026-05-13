<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/AmbulanceMissionController.php';
$ctrl = new AmbulanceMissionController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = (int)$_POST['idDemande'];
    if ($action === 'accept') {
        $idAmb = (int)$_POST['idAmbulance'];
        $r = $ctrl->updateDemandeStatus($id, 'acceptee', $idAmb);
    } elseif ($action === 'refuse') {
        $r = $ctrl->updateDemandeStatus($id, 'refusee');
    }
    $_SESSION[$r['success'] ? 'success_message' : 'error_message'] = $r['message'];
    header('Location: admin-demandes.php'); exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';
$filters = ['page' => $page, 'limit' => 10, 'search' => $search, 'statut' => $statut];
$result = $ctrl->getAllDemandes($filters);
$demandes = $result['data'] ?? [];
$totalPages = $result['pages'] ?? 1;
$stats = $ctrl->getDemandeStats();
$ambulances = $ctrl->getAmbulancesForSelect();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Demandes – Admin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="../components/admin.css">
<style>.card{overflow:hidden;margin-bottom:24px;}</style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-admin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Demandes d'Ambulances</h1>
        </div>
        <div class="card">
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead><tr><th>Médecin</th><th>Type</th><th>Itinéraire</th><th>Statut</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($demandes as $d): ?>
                        <tr>
                            <td>Dr. <?= htmlspecialchars($d['med_nom'].' '.$d['med_prenom']) ?></td>
                            <td><?= htmlspecialchars($d['typeMission']) ?></td>
                            <td><?= htmlspecialchars($d['lieuDepart']) ?> → <?= htmlspecialchars($d['lieuArrivee']) ?></td>
                            <td>
                                <?php if($d['statut'] === 'en attente'): ?>
                                    <span class="badge" style="background:#FEF3C7;color:#D97706;">En attente</span>
                                <?php elseif($d['statut'] === 'acceptee'): ?>
                                    <span class="badge" style="background:#D1FAE5;color:#059669;">Acceptée</span>
                                <?php elseif($d['statut'] === 'refusee'): ?>
                                    <span class="badge" style="background:#FEE2E2;color:#DC2626;">Refusée</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#F1F5F9;color:#64748B;"><?= ucfirst($d['statut']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <?php if($d['statut'] === 'en attente'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="acceptDemande(<?= $d['idDemande'] ?>)">
                                            <i class="bi bi-check-lg"></i> Accepter
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="refuseDemande(<?= $d['idDemande'] ?>)">
                                            <i class="bi bi-x-lg"></i> Refuser
                                        </button>
                                    <?php else: ?>
                                        <span style="color:#64748B; font-size: 13px; font-weight: 500;">
                                            <i class="bi bi-check-circle-fill"></i> Traité
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script>
function acceptDemande(id){
  Swal.fire({
    title: 'Assigner une ambulance',
    text: 'Veuillez choisir une ambulance pour cette mission',
    icon: 'question',
    html: `
        <form id='f-a' method='POST'>
            <input type='hidden' name='action' value='accept'>
            <input type='hidden' name='idDemande' value='${id}'>
            <div class="swal2-content" style="margin-top: 15px;">
                <select name='idAmbulance' class='swal2-select' style="width: 100%; border-radius: 12px; border: 1px solid #E2E8F0; padding: 12px;">
                    <option value="" disabled selected>Sélectionner une ambulance...</option>
                    <?php foreach($ambulances as $a): ?>
                        <option value='<?= $a['idAmbulance'] ?>'><?= $a['immatriculation'] ?> - <?= $a['modele'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>`,
    showCancelButton: true,
    confirmButtonColor: '#1D9E75',
    cancelButtonColor: '#6B7280',
    confirmButtonText: 'Confirmer',
    cancelButtonText: 'Annuler',
    preConfirm: () => {
        const select = document.querySelector('select[name="idAmbulance"]');
        if (!select.value) {
            Swal.showValidationMessage('Veuillez sélectionner une ambulance');
            return false;
        }
        document.getElementById('f-a').submit();
    }
  });
}

function refuseDemande(id){
    Swal.fire({
        title: 'Refuser la demande ?',
        text: "Cette action est irréversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Oui, refuser',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="refuse">
                <input type="hidden" name="idDemande" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
</body>
</html>
