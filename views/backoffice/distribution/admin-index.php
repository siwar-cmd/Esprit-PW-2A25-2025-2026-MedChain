<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/DistributionController.php';
require_once __DIR__ . '/../../../controllers/LotMedicamentController.php';
$ctrl = new DistributionController();
$lotCtrl = new LotMedicamentController();

$search = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        $action = $_POST['action'];
        
        if ($action === 'accepter') {
            $res = $ctrl->updateStatus($id, 'Accepte');
            if ($res['success']) $_SESSION['success_message'] = "Distribution acceptée";
            else $_SESSION['error_message'] = $res['message'];
        } elseif ($action === 'refuser') {
            $res = $ctrl->updateStatus($id, 'Rejete');
            if ($res['success']) $_SESSION['success_message'] = "Distribution rejetée";
            else $_SESSION['error_message'] = $res['message'];
        }
    }
    header('Location: admin-index.php'); exit;
}

$result = $ctrl->getAllDistributions(['search' => $search]);
$distributions = $result['distributions'] ?? [];
$stats = $ctrl->getStats();

// Fetch lots for the dropdown
$lotsResult = $lotCtrl->getAllLotMedicaments();
$allLots = $lotsResult['lots'] ?? [];

$success = $_SESSION['success_message'] ?? null; unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Distributions Médicales – MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="../components/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .badge { padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-accepted { background: #dcfce7; color: #16a34a; }
        .badge-rejected { background: #fee2e2; color: #ef4444; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: white; padding: 24px; border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); display: flex; align-items: center; gap: 16px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-icon.purple { background: #f3e8ff; color: #7e22ce; }
        .stat-icon.blue { background: #e0f2fe; color: #0369a1; }
        .stat-icon.green { background: #dcfce7; color: #16a34a; }
        
        .search-bar { display: flex; gap: 12px; margin-bottom: 24px; }
        .search-input { flex: 1; padding: 10px 16px; border: 1px solid var(--gray-200); border-radius: 12px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include '../components/sidebar-admin.php'; ?>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Distributions Médicales</h1>
                <p>Suivi des sorties de stock et livraisons aux patients</p>
            </div>
            <div style="display:flex; gap:12px;" class="no-print">
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
                </button>
            </div>
        </div>

        <style>
            @media print {
                .no-print, .dashboard-sidebar { display: none !important; }
                .dashboard-main { margin-left: 0 !important; padding: 0 !important; }
                .card { border: none !important; box-shadow: none !important; }
            }
        </style>

        <?php if($success): ?>
            <script>Swal.fire({ title: 'Succès', text: <?= json_encode($success) ?>, icon: 'success', confirmButtonColor: '#1D9E75' });</script>
        <?php endif; ?>
        <?php if($error): ?>
            <script>Swal.fire({ title: 'Erreur', text: <?= json_encode($error) ?>, icon: 'error', confirmButtonColor: '#EF4444' });</script>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="bi bi-truck"></i></div>
                <div><h3 style="font-family:Syne;"><?= $stats['total'] ?></h3><p style="font-size:12px;color:var(--gray-500);">Total Livraisons</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-calendar-event"></i></div>
                <div><h3 style="font-family:Syne;"><?= $stats['ce_mois'] ?></h3><p style="font-size:12px;color:var(--gray-500);">Ce mois-ci</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check2-circle"></i></div>
                <div><h3 style="font-family:Syne;"><?= $stats['sum_distribuee'] ?></h3><p style="font-size:12px;color:var(--gray-500);">Unités Livrées</p></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Registre des Distributions</h2>
                <form method="GET" class="search-bar" style="margin-bottom:0;">
                    <input type="text" name="search" class="search-input" placeholder="Patient, médicament ou responsable..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient / Destinataire</th>
                            <th>Médicament</th>
                            <th>Quantité</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($distributions)): ?>
                            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray-500);">Aucune distribution trouvée.</td></tr>
                        <?php else: foreach ($distributions as $d): 
                            $statutClass = match($d['statut']) { 'En attente'=>'badge-pending', 'Accepte'=>'badge-accepted', 'Rejete'=>'badge-rejected', default=>'' };
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($d['patient']) ?></strong><br>
                                    <small class="text-muted">Par: <?= htmlspecialchars($d['responsable']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($d['nom_medicament']) ?></td>
                                <td><span style="font-weight:600;"><?= $d['quantite_distribuee'] ?></span></td>
                                <td><?= date('d/m/Y', strtotime($d['date_distribution'])) ?></td>
                                <td><span class="badge <?= $statutClass ?>"><?= $d['statut'] ?></span></td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <button class="btn btn-sm btn-primary" style="padding:4px 8px;" title="Voir"><i class="bi bi-eye"></i></button>
                                        <?php if($d['statut'] === 'En attente'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $d['id_distribution'] ?>">
                                                <input type="hidden" name="action" value="accepter">
                                                <button type="submit" class="btn btn-sm btn-success" style="padding:4px 8px; background:#DCFCE7; color:#16A34A; border:1px solid #16A34A;" title="Accepter"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $d['id_distribution'] ?>">
                                                <input type="hidden" name="action" value="refuser">
                                                <button type="submit" class="btn btn-sm btn-danger" style="padding:4px 8px; background:#FEF2F2; color:#EF4444; border:1px solid #EF4444;" title="Refuser"><i class="bi bi-x-lg"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
// Logic for view/edit distribution could go here
</script>

</body>
</html>
