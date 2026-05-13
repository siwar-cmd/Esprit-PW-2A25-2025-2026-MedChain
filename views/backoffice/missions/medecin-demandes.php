<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/AmbulanceMissionController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

$auth = new AuthController();
$user = $auth->getCurrentUser();
if (!$user) { header('Location: ../../../frontoffice/auth/login.php'); exit; }

$ctrl = new AmbulanceMissionController();
$userId = $user->getId();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $_POST['idMedecin'] = $userId;
        $r = $ctrl->createDemande($_POST);
        $_SESSION[$r['success'] ? 'success_message' : 'error_message'] = $r['message'];
        header('Location: medecin-demandes.php'); exit;
    }
}

// Filters and Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';

$filters = [
    'idMedecin' => $userId,
    'page' => $page,
    'limit' => $limit,
    'search' => $search,
    'statut' => $statut
];

$result = $ctrl->getAllDemandes($filters);
$demandes = $result['data'] ?? [];
$totalPages = $result['pages'] ?? 1;

$success = $_SESSION['success_message'] ?? null; unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);

$userName = $user->getPrenom().' '.$user->getNom();
$today = date('l d F Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Mes Demandes d'Ambulance – MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../components/medecin.css">
    <style>
        .badge { padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 600; }
        .badge-pending { background: #FEF3C7; color: #D97706; }
        .badge-accepted { background: #DCFCE7; color: #15803D; }
        .badge-refused { background: #FEE2E2; color: #B91C1C; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid var(--gray-200); border-radius: 12px; font-size: 14px; outline: none; transition: border-color .2s; }
        .form-control:focus { border-color: var(--green); }
        
        .charts-grid { display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 32px; }
        .chart-container { background: white; padding: 24px; border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); }
        .chart-wrapper { height: 250px; position: relative; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Mes Demandes d'Ambulance</h1>
                <p>Gérez vos requêtes d'intervention prioritaires</p>
            </div>
            <span style="font-size:12px;color:var(--gray-500);"><?= $today ?></span>
        </div>

        <?php if($success): ?>
            <script>Swal.fire('Succès', '<?= $success ?>', 'success');</script>
        <?php endif; ?>
        <?php if($error): ?>
            <script>Swal.fire('Erreur', '<?= $error ?>', 'error');</script>
        <?php endif; ?>

        <div class="charts-grid">
            <div class="chart-container">
                <h4 style="margin-bottom:20px; font-family:Syne; color:var(--navy);"><i class="bi bi-graph-up-arrow" style="color:var(--green);"></i> État de mes requêtes</h4>
                <div class="chart-wrapper"><canvas id="myDemandesChart"></canvas></div>
            </div>
        </div>

        <!-- NEW REQUEST FORM -->
        <div class="card" style="margin-bottom: 32px;">
            <div class="card-header">
                <h2><i class="bi bi-plus-circle-fill"></i> Nouvelle Demande</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Type de mission *</label>
                            <select name="typeMission" class="form-control" required>
                                <option value="Urgence">Urgence</option>
                                <option value="Transport">Transport / Transfert</option>
                                <option value="Rapatriement">Rapatriement</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date et Heure *</label>
                            <input type="datetime-local" name="dateHeure" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Lieu de départ *</label>
                            <input type="text" name="lieuDepart" class="form-control" placeholder="Point de départ..." required>
                        </div>
                        <div class="form-group">
                            <label>Lieu d'arrivée *</label>
                            <input type="text" name="lieuArrivee" class="form-control" placeholder="Destination..." required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Remarques (optionnel)</label>
                        <textarea name="remarques" class="form-control" rows="2" placeholder="Précisez l'état du patient ou besoins spécifiques..."></textarea>
                    </div>
                    <div style="text-align:right; margin-top: 10px;">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill"></i> Envoyer la demande</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- HISTORY TABLE -->
        <div class="card">
            <div class="card-header">
                <h2>Historique de mes demandes</h2>
                <div style="display:flex; gap:10px;">
                    <form method="GET">
                        <select name="statut" class="form-control" style="width: auto; height: 38px; padding: 0 10px;" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="en attente" <?= $statut==='en attente'?'selected':'' ?>>En attente</option>
                            <option value="acceptee" <?= $statut==='acceptee'?'selected':'' ?>>Acceptée</option>
                            <option value="refusee" <?= $statut==='refusee'?'selected':'' ?>>Refusée</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Créé le</th>
                                <th>Type</th>
                                <th>Date/Heure prévue</th>
                                <th>Itinéraire</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($demandes)): ?>
                                <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--gray-500);">Aucune demande enregistrée.</td></tr>
                            <?php else: foreach($demandes as $d): 
                                $stClass = match($d['statut']) { 'en attente'=>'badge-pending', 'acceptee'=>'badge-accepted', 'refusee'=>'badge-refused', default=>'' };
                            ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($d['dateCreation'])) ?></td>
                                    <td><strong><?= htmlspecialchars($d['typeMission']) ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($d['dateHeure'])) ?></td>
                                    <td>
                                        <div style="font-size:12px;">
                                            <span class="text-muted">De:</span> <?= htmlspecialchars($d['lieuDepart']) ?><br>
                                            <span class="text-muted">À:</span> <?= htmlspecialchars($d['lieuArrivee']) ?>
                                        </div>
                                    </td>
                                    <td><span class="badge <?= $stClass ?>"><?= ucfirst($d['statut']) ?></span></td>
                                    <td>
                                        <?php if($d['statut'] === 'acceptee'): ?>
                                            <button class="btn btn-secondary btn-sm" onclick="Swal.fire('Détails', 'Mission #<?= $d['idMission'] ?> assignée. Ambulance en route.', 'info')">
                                                <i class="bi bi-info-circle"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?= max(1, $page-1) ?>&statut=<?= $statut ?>" class="page-link <?= $page<=1?'disabled':'' ?>"><i class="bi bi-chevron-left"></i></a>
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&statut=<?= $statut ?>" class="page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= min($totalPages, $page+1) ?>&statut=<?= $statut ?>" class="page-link <?= $page>=$totalPages?'disabled':'' ?>"><i class="bi bi-chevron-right"></i></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
// --- Chart ---
<?php $myStats = $ctrl->getDemandeStats($userId); ?>
const ctxMy = document.getElementById('myDemandesChart').getContext('2d');
new Chart(ctxMy, {
    type: 'doughnut',
    data: {
        labels: ['En attente', 'Acceptées', 'Refusées'],
        datasets: [{
            data: [<?= $myStats['pending'] ?? 0 ?>, <?= $myStats['accepted'] ?? 0 ?>, <?= $myStats['refused'] ?? 0 ?>],
            backgroundColor: ['#F59E0B', '#1D9E75', '#EF4444'],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right', labels: { usePointStyle: true, font: { size: 12, family: 'DM Sans' } } }
        }
    }
});
</script>

</body>
</html>
