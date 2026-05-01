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
      --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F0FDF9;
      --navy: #1E3A52; --gray-700: #374151; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-md: 0 4px 16px rgba(0,0,0,.08);
      --shadow-green: 0 8px 30px rgba(29,158,117,.18);
      --radius-sm: 8px; --radius-md: 12px; --radius-lg: 20px;
    }
    body { font-family: 'DM Sans', sans-serif; background: #f0faf6; color: var(--gray-700); }
    .mc-container { max-width: 1200px; margin: 0 auto; }
    .page-title { font-family: 'Syne', sans-serif; font-weight: 700; color: var(--navy); margin-bottom: 20px; }
    .stat-card { background: var(--white); border-radius: var(--radius-md); padding: 20px; box-shadow: var(--shadow-sm); border-left: 4px solid var(--green); }
    .stat-card h3 { font-size: 24px; color: var(--navy); margin: 0; font-family: 'Syne', sans-serif; }
    .stat-card p { margin: 0; color: var(--gray-500); font-size: 14px; }
    .action-bar { display: flex; justify-content: space-between; align-items: center; margin: 30px 0 20px; flex-wrap: wrap; gap: 15px; }
    .search-box { display: flex; gap: 10px; }
    .btn-mc { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; border: none; padding: 9px 18px; border-radius: var(--radius-sm); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 12px rgba(29,158,117,.30); transition: all .25s; }
    .btn-mc:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(29,158,117,.40); color: white; }
    .table-custom { background: white; border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); }
    .table-custom th { background: #f3f4f6; color: var(--navy); font-weight: 600; padding: 15px; }
    .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid var(--gray-200); }
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-planifie { background: #E0F2FE; color: #0284C7; }
    .status-termine { background: #DCFCE7; color: #16A34A; }
    .status-annule { background: #FEE2E2; color: #DC2626; }

    /* ═══════════════════════════════════════════════
       FRONTOFFICE SIDEBAR — Design moderne & distinct
    ═══════════════════════════════════════════════ */
    .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }

    .dashboard-sidebar {
      background: linear-gradient(160deg, #ffffff 0%, #f0fdf9 60%, #e6faf3 100%);
      border-right: 1px solid rgba(29,158,117,.15);
      position: sticky; top: 0; height: 100vh;
      display: flex; flex-direction: column; overflow-y: auto;
      box-shadow: 4px 0 24px rgba(29,158,117,.08);
    }

    /* Logo zone */
    .sidebar-logo-zone {
      padding: 26px 22px 20px;
      border-bottom: 1px solid rgba(29,158,117,.12);
    }
    .sidebar-logo-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .sidebar-logo-icon {
      width: 42px; height: 42px;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      border-radius: 13px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 14px rgba(29,158,117,.35);
    }
    .sidebar-logo-icon i { font-size: 20px; color: white; }
    .sidebar-logo-text { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--navy); letter-spacing: -.3px; }
    .sidebar-logo-text span { color: var(--green); }
    .sidebar-tagline { font-size: 11px; color: var(--gray-500); margin-top: 3px; letter-spacing: .03em; }

    /* User card */
    .sidebar-user-card {
      margin: 18px 16px;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      border-radius: var(--radius-lg);
      padding: 18px 16px;
      box-shadow: var(--shadow-green);
      position: relative; overflow: hidden;
    }
    .sidebar-user-card::before {
      content: '';
      position: absolute; top: -20px; right: -20px;
      width: 90px; height: 90px; border-radius: 50%;
      background: rgba(255,255,255,.1);
    }
    .sidebar-user-card::after {
      content: '';
      position: absolute; bottom: -15px; left: -15px;
      width: 60px; height: 60px; border-radius: 50%;
      background: rgba(255,255,255,.06);
    }
    .sidebar-user-avatar {
      width: 44px; height: 44px; border-radius: 50%;
      background: rgba(255,255,255,.25);
      border: 2.5px solid rgba(255,255,255,.5);
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 12px;
    }
    .sidebar-user-avatar i { font-size: 22px; color: white; }
    .sidebar-user-name { font-size: 15px; font-weight: 700; color: white; margin-bottom: 2px; }
    .sidebar-user-role {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11px; color: rgba(255,255,255,.85);
      background: rgba(255,255,255,.18);
      padding: 3px 10px; border-radius: 20px; margin-top: 4px;
    }
    .sidebar-user-role i { font-size: 10px; }

    /* Health pulse widget */
    .sidebar-health-widget {
      margin: 0 16px 6px;
      background: var(--white);
      border: 1px solid rgba(29,158,117,.15);
      border-radius: var(--radius-md);
      padding: 14px 16px;
    }
    .sidebar-health-label { font-size: 11px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
    .sidebar-health-bar-wrap { background: var(--gray-200); border-radius: 6px; height: 6px; overflow: hidden; }
    .sidebar-health-bar {
      height: 100%; border-radius: 6px; width: 78%;
      background: linear-gradient(90deg, var(--green), #34D399);
      animation: health-grow 1.2s ease-out forwards;
    }
    @keyframes health-grow { from { width: 0; } to { width: 78%; } }
    .sidebar-health-stats { display: flex; justify-content: space-between; margin-top: 8px; }
    .sidebar-health-stat { font-size: 12px; color: var(--gray-500); }
    .sidebar-health-stat strong { color: var(--green); font-weight: 700; }

    /* Nav */
    .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 3px; padding: 12px 12px 0; }
    .sidebar-nav-section-label {
      font-size: 10.5px; font-weight: 700; text-transform: uppercase;
      letter-spacing: .12em; color: #A0AEC0;
      padding: 14px 12px 6px;
    }
    .sidebar-nav-item {
      display: flex; align-items: center; gap: 13px;
      padding: 11px 14px; color: var(--gray-500);
      text-decoration: none; border-radius: var(--radius-md);
      transition: all 0.25s; font-size: 14px; font-weight: 500;
      position: relative;
    }
    .sidebar-nav-item .nav-icon {
      width: 36px; height: 36px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; flex-shrink: 0;
      background: rgba(29,158,117,.08);
      color: var(--green);
      transition: all 0.25s;
    }
    .sidebar-nav-item:hover {
      background: rgba(29,158,117,.07);
      color: var(--green-dark);
    }
    .sidebar-nav-item:hover .nav-icon {
      background: rgba(29,158,117,.15);
      transform: scale(1.08);
    }
    .sidebar-nav-item.active {
      background: linear-gradient(90deg, rgba(29,158,117,.12), rgba(29,158,117,.04));
      color: var(--green-dark);
      font-weight: 600;
    }
    .sidebar-nav-item.active .nav-icon {
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      color: white;
      box-shadow: 0 4px 12px rgba(29,158,117,.30);
    }
    .sidebar-nav-item.active::before {
      content: '';
      position: absolute; left: 0; top: 20%; bottom: 20%;
      width: 3px; border-radius: 0 3px 3px 0;
      background: var(--green);
    }
    .sidebar-nav-item.logout {
      color: #E53E3E;
      margin: 0 0 4px;
    }
    .sidebar-nav-item.logout .nav-icon {
      background: rgba(229,62,62,.08);
      color: #E53E3E;
    }
    .sidebar-nav-item.logout:hover {
      background: rgba(229,62,62,.07);
    }
    .sidebar-nav-item.logout:hover .nav-icon {
      background: rgba(229,62,62,.15);
    }

    /* Footer sidebar */
    .sidebar-footer {
      padding: 16px;
      border-top: 1px solid rgba(29,158,117,.10);
      margin-top: auto;
    }
    .sidebar-footer-back {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; border-radius: var(--radius-md);
      background: var(--green-pale);
      color: var(--green-dark); font-size: 13px; font-weight: 600;
      text-decoration: none; transition: all .2s;
      border: 1px solid rgba(29,158,117,.2);
    }
    .sidebar-footer-back:hover {
      background: rgba(29,158,117,.15);
      transform: translateX(-3px);
    }

    .dashboard-main { padding: 32px 40px; overflow-y: auto; width: 100%; }

    @media (max-width: 1024px) { .dashboard-container { grid-template-columns: 260px 1fr; } }
    @media (max-width: 768px) {
      .dashboard-container { grid-template-columns: 1fr; }
      .dashboard-sidebar { position: fixed; left: -290px; top: 0; bottom: 0; width: 280px; z-index: 1000; transition: left 0.3s; }
      .dashboard-sidebar.open { left: 0; }
      .dashboard-main { padding: 20px; }
    }
    @media print {
        body * { visibility: hidden; }
        .dashboard-sidebar { display: none !important; }
        .dashboard-container { display: block; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .action-bar, .actions-col { display: none !important; }
    }
    .btn-mc { background-color: var(--green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-mc:hover { background-color: var(--green-dark); color: white; transform: translateY(-2px); }
    .btn-stats { background: linear-gradient(135deg, var(--green), var(--navy)); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-stats:hover { background: linear-gradient(135deg, var(--green-dark), var(--navy)); color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(29, 158, 117, 0.3); }
  </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar FrontOffice Moderne -->
    <aside class="dashboard-sidebar" id="sidebar">

      <!-- Logo -->
      <div class="sidebar-logo-zone">
        <a href="../home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div>
            <div class="sidebar-logo-text">Med<span>Chain</span></div>
            <div class="sidebar-tagline">Espace Patient</div>
          </div>
        </a>
      </div>

      <!-- User Card -->
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
        <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_prenom'] ?? 'Patient') ?> <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></div>
        <div class="sidebar-user-role"><i class="bi bi-heart-pulse-fill"></i> Patient</div>
      </div>

      <!-- Health Widget -->
      <div class="sidebar-health-widget">
        <div class="sidebar-health-label"><i class="bi bi-activity" style="color:var(--green);margin-right:5px;"></i>Suivi de santé</div>
        <div class="sidebar-health-bar-wrap"><div class="sidebar-health-bar"></div></div>
        <div class="sidebar-health-stats">
          <span class="sidebar-health-stat">Profil <strong>78%</strong> complet</span>
          <span class="sidebar-health-stat" style="color:var(--green);"><i class="bi bi-shield-check"></i> Actif</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Navigation</div>

        <a href="../home/index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-house-door-fill"></i></span>
          Accueil
        </a>
        <a href="../auth/profile.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-person-fill"></i></span>
          Mon Profil
        </a>

        <div class="sidebar-nav-section-label">Mes Services</div>

        <a href="index.php" class="sidebar-nav-item active">
          <span class="nav-icon"><i class="bi bi-calendar-check"></i></span>
          Mes Rendez-vous
        </a>
        <a href="../ficherdv/index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span>
          Mes Fiches Médicales
        </a>
      </nav>

      <!-- Footer Sidebar -->
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout"
           onclick="confirmSwal(event, this, 'Êtes-vous sûr de vouloir vous déconnecter ?')">
          <span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span>
          Déconnexion
        </a>
        <div style="margin-top:10px;">
          <a href="../home/index.php" class="sidebar-footer-back">
            <i class="bi bi-arrow-left-circle-fill"></i> Retour au site
          </a>
        </div>
      </div>

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
            <a href="stats.php" class="btn btn-stats me-2"><i class="bi bi-bar-chart-line-fill"></i> Statistiques</a>
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
