<?php
session_start();
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../controllers/RendezVousController.php';

$authController = new AuthController();
$isLoggedIn = $authController->isLoggedIn();

if (!$isLoggedIn) {
    header('Location: ../auth/login.php');
    exit;
}

$currentUser = $authController->getCurrentUser();
if ($currentUser->getRole() !== 'patient') {
    // If admin or medecin, redirect to backoffice
    header('Location: ../../../views/backoffice/admin-dashboard.php');
    exit;
}

$rdvController = new RendezVousController();

// Traitement suppression
if (isset($_POST['delete_id'])) {
    $rdvController->deleteRendezVous($_POST['delete_id']);
    $_SESSION['success_message'] = "Rendez-vous annulé avec succès.";
    header('Location: index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$filters = ['search' => $search];
$rdvData = $rdvController->getAllRendezVous($filters, 'patient', $currentUser->getId());
$rendezvous = $rdvData['success'] ? $rdvData['rdvs'] : [];

$stats = $rdvController->getStats('patient', $currentUser->getId());
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mes Rendez-vous — MedChain</title>
  
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <style>
    :root {
      --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --navy: #1E3A52;
      --gray-700: #374151; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-md: 0 4px 16px rgba(0,0,0,.08);
      --radius-sm: 8px; --radius-md: 12px;
    }
    body { font-family: 'DM Sans', sans-serif; background: #f9fafb; color: var(--gray-700); }
    .mc-container { max-width: 1200px; margin: 0 auto; }
    .page-title { font-family: 'Syne', sans-serif; font-weight: 700; color: var(--navy); margin-bottom: 20px; }
    .stat-card { background: var(--white); border-radius: var(--radius-md); padding: 20px; box-shadow: var(--shadow-sm); border-left: 4px solid var(--green); }
    .stat-card h3 { font-size: 24px; color: var(--navy); margin: 0; font-family: 'Syne', sans-serif; }
    .stat-card p { margin: 0; color: var(--gray-500); font-size: 14px; }
    .action-bar { display: flex; justify-content: space-between; align-items: center; margin: 30px 0 20px; flex-wrap: wrap; gap: 15px; }
    .search-box { display: flex; gap: 10px; }
    .btn-mc { background: var(--green); color: white; border: none; padding: 8px 16px; border-radius: var(--radius-sm); font-weight: 500; text-decoration: none; display: inline-block; }
    .btn-mc:hover { background: var(--green-dark); color: white; }
    .table-custom { background: white; border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); }
    .table-custom th { background: #f3f4f6; color: var(--navy); font-weight: 600; padding: 15px; }
    .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid var(--gray-200); }
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-planifie { background: #E0F2FE; color: #0284C7; }
    .status-termine { background: #DCFCE7; color: #16A34A; }
    .status-annule { background: #FEE2E2; color: #DC2626; }
    .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
    .dashboard-sidebar { background: linear-gradient(180deg, var(--navy) 0%, #0F172A 100%); position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; overflow-y: auto; }
    .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
    .dashboard-logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .dashboard-logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
    .dashboard-logo-icon i { font-size: 18px; color: white; }
    .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; }
    .dashboard-logo-text span { color: var(--green); }
    .dashboard-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
    .dashboard-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); transition: all 0.3s; font-size: 14px; font-weight: 500; }
    .dashboard-nav-item i { font-size: 18px; width: 24px; }
    .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
    .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }
    .dashboard-nav-item.logout { margin-top: auto; margin-bottom: 20px; color: #F87171; }
    .dashboard-nav-item.logout:hover { background: rgba(248,113,113,0.1); color: #F87171; }
    .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }
    .dashboard-main { padding: 32px 40px; overflow-y: auto; width: 100%; }

    @media print {
        body * { visibility: hidden; }
        .dashboard-sidebar { display: none !important; }
        .dashboard-container { display: block; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .action-bar, .actions-col { display: none !important; }
    }
  </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar" id="sidebar">
        <div class="dashboard-logo">
            <a href="../home/index.php">
                <div class="dashboard-logo-icon">
                    <i class="bi bi-plus-square-fill"></i>
                </div>
                <div class="dashboard-logo-text">Med<span>Chain</span></div>
            </a>
        </div>
        
        <nav class="dashboard-nav">
            <a href="/projet/views/frontoffice/home/index.php" class="dashboard-nav-item"><i class="bi bi-house"></i> Accueil</a>
            <div class="dashboard-nav-title">Navigation</div>
            <a href="../home/index.php" class="dashboard-nav-item">
                <i class="bi bi-house-door-fill"></i> Accueil
            </a>
            <a href="../auth/profile.php" class="dashboard-nav-item">
                <i class="bi bi-person-fill"></i> Mon Profil
            </a>
            <a href="index.php" class="dashboard-nav-item active">
                <i class="bi bi-calendar-check"></i> Mes Rendez-vous
            </a>
            <a href="../ficherdv/index.php" class="dashboard-nav-item">
                <i class="bi bi-file-medical"></i> Mes Fiches
            </a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" onclick="confirmSwal(event, this, 'Êtes-vous sûr de vouloir vous déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="mc-container" id="print-area">
    <h1 class="page-title">Mes Rendez-vous</h1>

    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <h3><?= $stats['total'] ?></h3>
                <p>Total Rendez-vous</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #0284C7;">
                <h3><?= $stats['ce_mois'] ?></h3>
                <p>Ce mois-ci</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #16A34A;">
                <?php 
                $term = 0;
                foreach($stats['by_status'] as $s) if($s['statut'] == 'termine') $term = $s['count'];
                ?>
                <h3><?= $term ?></h3>
                <p>Terminés</p>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <form method="GET" class="search-box">
            <input type="text" name="search" id="searchInput" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable()">
            <button type="submit" class="btn btn-mc"><i class="bi bi-search"></i></button>
        </form>
        <div>
            <button onclick="window.print()" class="btn btn-secondary me-2"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
            <a href="create.php" class="btn btn-mc"><i class="bi bi-plus-lg"></i> Nouveau Rendez-vous</a>
        </div>
    </div>

    <div class="table-responsive table-custom">
        <table class="table mb-0" id="rdvTable">
            <thead>
                <tr>
                    <th style="cursor:pointer" onclick="sortTable(0, 'rdvTable')">Date & Heure <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                    <th style="cursor:pointer" onclick="sortTable(1, 'rdvTable')">Médecin <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                    <th style="cursor:pointer" onclick="sortTable(2, 'rdvTable')">Type <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                    <th>Motif</th>
                    <th style="cursor:pointer" onclick="sortTable(4, 'rdvTable')">Statut <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                    <th class="actions-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($rendezvous)): ?>
                <tr><td colspan="6" class="text-center py-4">Aucun rendez-vous trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach($rendezvous as $rdv): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($rdv['dateHeureDebut'])) ?></td>
                        <td>Dr. <?= htmlspecialchars($rdv['medecin_nom'] . ' ' . $rdv['medecin_prenom']) ?></td>
                        <td><?= htmlspecialchars($rdv['typeConsultation']) ?></td>
                        <td><?= htmlspecialchars(substr($rdv['motif'], 0, 30)) ?>...</td>
                        <td>
                            <span class="status-badge status-<?= $rdv['statut'] ?>">
                                <?= ucfirst($rdv['statut']) ?>
                            </span>
                        </td>
                        <td class="actions-col">
                            <a href="edit.php?id=<?= $rdv['idRDV'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" style="display:inline;" onsubmit="confirmSwal(event, this, 'Voulez-vous vraiment annuler ce rendez-vous ?')">
                                <input type="hidden" name="delete_id" value="<?= $rdv['idRDV'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
</div>

<script>
// Recherche dynamique côté client en plus de la recherche serveur
function filterTable() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("rdvTable");
    tr = table.getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) {
        tr[i].style.display = "none";
        td = tr[i].getElementsByTagName("td");
        for (var j = 0; j < td.length; j++) {
            if (td[j] && !td[j].classList.contains('actions-col')) {
                txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                    break;
                }
            }
        }
    }
}

function sortTable(n, tableId) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById(tableId);
    switching = true;
    dir = "asc"; 
    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];
            if (dir == "asc") {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == "desc") {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount ++;
        } else {
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
