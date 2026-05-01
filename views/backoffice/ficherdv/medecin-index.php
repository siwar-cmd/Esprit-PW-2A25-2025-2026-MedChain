<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';

$ficheController = new FicheRendezVousController();
$userId = $_SESSION['user_id'];

$search = $_GET['search'] ?? '';
$filters = ['search' => $search];

// Handle delete
if (isset($_POST['delete_id'])) {
    $ficheController->deleteFiche($_POST['delete_id']);
    $_SESSION['success_message'] = "Fiche supprimée avec succès";
    header("Location: medecin-index.php");
    exit;
}

$ficheData = $ficheController->getAllFiches($filters, 'medecin', $userId);
$fiches = $ficheData['success'] ? $ficheData['fiches'] : [];

$stats = $ficheController->getStats('medecin', $userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Fiches Médicales - Médecin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green: #1D9E75; --green-dark: #0F6E56; --green-pale: #F0FDF9;
            --navy: #1E3A52; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-green: 0 8px 30px rgba(29,158,117,.18);
            --radius-md: 12px; --radius-lg: 20px;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; min-height: 100vh; }
        .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        .dashboard-sidebar { background:linear-gradient(160deg,#fff 0%,#f0fdf9 60%,#e6faf3 100%); border-right:1px solid rgba(29,158,117,.15); height:100vh; position:sticky; top:0; display:flex; flex-direction:column; overflow-y:auto; box-shadow:4px 0 24px rgba(29,158,117,.08); }
        .sidebar-logo-zone { padding:26px 22px 20px; border-bottom:1px solid rgba(29,158,117,.12); }
        .sidebar-logo-link { display:flex; align-items:center; gap:12px; text-decoration:none; }
        .sidebar-logo-icon { width:42px; height:42px; background:linear-gradient(135deg,var(--green),var(--green-dark)); border-radius:13px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(29,158,117,.35); }
        .sidebar-logo-icon i { font-size:20px; color:white; }
        .sidebar-logo-text { font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--navy); }
        .sidebar-logo-text span { color:var(--green); }
        .sidebar-tagline { font-size:11px; color:var(--gray-500); margin-top:3px; }
        .sidebar-user-card { margin:18px 16px; background:linear-gradient(135deg,var(--green),var(--green-dark)); border-radius:var(--radius-lg); padding:18px 16px; box-shadow:var(--shadow-green); position:relative; overflow:hidden; }
        .sidebar-user-card::before { content:''; position:absolute; top:-20px; right:-20px; width:90px; height:90px; border-radius:50%; background:rgba(255,255,255,.1); }
        .sidebar-user-avatar { width:44px; height:44px; border-radius:50%; background:rgba(255,255,255,.25); border:2.5px solid rgba(255,255,255,.5); display:flex; align-items:center; justify-content:center; margin-bottom:12px; }
        .sidebar-user-avatar i { font-size:22px; color:white; }
        .sidebar-user-name { font-size:15px; font-weight:700; color:white; }
        .sidebar-user-role { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:rgba(255,255,255,.85); background:rgba(255,255,255,.18); padding:3px 10px; border-radius:20px; margin-top:4px; }
        .sidebar-stats-widget { margin:0 16px 6px; background:var(--white); border:1px solid rgba(29,158,117,.15); border-radius:var(--radius-md); padding:14px 16px; }
        .sidebar-stats-label { font-size:11px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:.08em; margin-bottom:10px; }
        .sidebar-stats-row { display:flex; justify-content:space-between; }
        .sidebar-stat-item { text-align:center; }
        .sidebar-stat-num { font-size:20px; font-weight:800; color:var(--green); line-height:1; }
        .sidebar-stat-lbl { font-size:10px; color:var(--gray-500); margin-top:2px; }
        .sidebar-nav { flex:1; display:flex; flex-direction:column; gap:3px; padding:12px 12px 0; }
        .sidebar-nav-section-label { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:#A0AEC0; padding:14px 12px 6px; }
        .sidebar-nav-item { display:flex; align-items:center; gap:13px; padding:11px 14px; color:var(--gray-500); text-decoration:none; border-radius:var(--radius-md); transition:all .25s; font-size:14px; font-weight:500; position:relative; }
        .sidebar-nav-item .nav-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; background:rgba(29,158,117,.08); color:var(--green); transition:all .25s; }
        .sidebar-nav-item:hover { background:rgba(29,158,117,.07); color:var(--green-dark); }
        .sidebar-nav-item:hover .nav-icon { background:rgba(29,158,117,.15); transform:scale(1.08); }
        .sidebar-nav-item.active { background:linear-gradient(90deg,rgba(29,158,117,.12),rgba(29,158,117,.04)); color:var(--green-dark); font-weight:600; }
        .sidebar-nav-item.active .nav-icon { background:linear-gradient(135deg,var(--green),var(--green-dark)); color:white; box-shadow:0 4px 12px rgba(29,158,117,.30); }
        .sidebar-nav-item.active::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:3px; border-radius:0 3px 3px 0; background:var(--green); }
        .sidebar-nav-item.logout { color:#E53E3E; margin:0 0 4px; }
        .sidebar-nav-item.logout .nav-icon { background:rgba(229,62,62,.08); color:#E53E3E; }
        .sidebar-nav-item.logout:hover { background:rgba(229,62,62,.07); }
        .sidebar-footer { padding:16px; border-top:1px solid rgba(29,158,117,.10); margin-top:auto; }
        .sidebar-footer-back { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:var(--radius-md); background:var(--green-pale); color:var(--green-dark); font-size:13px; font-weight:600; text-decoration:none; transition:all .2s; border:1px solid rgba(29,158,117,.2); }
        .sidebar-footer-back:hover { background:rgba(29,158,117,.15); transform:translateX(-3px); }
        .dashboard-main { padding: 32px 40px; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--navy); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--white); border-radius: var(--radius-lg); padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: var(--shadow-sm); border: 1px solid rgba(29,158,117,.15); }
        .stat-icon { width: 52px; height: 52px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 24px; background: rgba(29,158,117,0.1); color: var(--green); }
        .stat-content h3 { font-size: 28px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .stat-content p { font-size: 13px; color: var(--gray-500); }
        .card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); overflow: hidden; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 24px; }
        .table { width: 100%; border-collapse: collapse; text-align: left; }
        .table th { background: #F8FAFC; padding: 12px 16px; color: #64748B; border-bottom: 1px solid var(--gray-200); }
        .table td { padding: 16px; border-bottom: 1px solid var(--gray-200); }
        .search-form { display: flex; gap: 10px; }
        .search-input { padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 8px; }
        .btn { padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;}
        .btn-primary { background: var(--green); color: white; }
        .btn-secondary { background: var(--gray-500); color: white; }
        .btn-danger { background: #FEE2E2; color: #EF4444; border: 1px solid #EF4444; }
        .btn-outline { background: white; border: 1px solid var(--green); color: var(--green); }
        .alert-success { background: #DCFCE7; color: #16A34A; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        @media print { .dashboard-sidebar, .search-form, .btn, .actions-col { display: none !important; } .dashboard-container { display: block; } .dashboard-main { padding: 0; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
      <div class="sidebar-logo-zone">
        <a href="../../frontoffice/home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div>
            <div class="sidebar-logo-text">Med<span>Chain</span></div>
            <div class="sidebar-tagline">Espace Médecin</div>
          </div>
        </a>
      </div>
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-badge-fill"></i></div>
        <div class="sidebar-user-name">Dr. <?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?></div>
        <div class="sidebar-user-role"><i class="bi bi-heart-pulse-fill"></i> Médecin</div>
      </div>
      <div class="sidebar-stats-widget">
        <div class="sidebar-stats-label"><i class="bi bi-bar-chart-fill" style="color:var(--green);margin-right:5px;"></i>Mes statistiques</div>
        <div class="sidebar-stats-row">
          <div class="sidebar-stat-item"><div class="sidebar-stat-num"><?= $stats['total'] ?? 0 ?></div><div class="sidebar-stat-lbl">Fiches</div></div>
          <div class="sidebar-stat-item"><div class="sidebar-stat-num"><?= $stats['ce_mois'] ?? 0 ?></div><div class="sidebar-stat-lbl">Ce mois</div></div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Navigation</div>
        <a href="../../frontoffice/home/index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-house-door-fill"></i></span> Accueil</a>
        <a href="../../frontoffice/auth/profile.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-person-fill"></i></span> Mon Profil</a>
        <div class="sidebar-nav-section-label">Mes Consultations</div>
        <a href="../rendezvous/medecin-index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span> Rendez-vous</a>
        <a href="medecin-index.php" class="sidebar-nav-item active"><span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span> Fiches Médicales</a>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout" onclick="return confirm('Déconnexion ?')"><span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span> Déconnexion</a>
        <div style="margin-top:10px;"><a href="../../frontoffice/home/index.php" class="sidebar-footer-back"><i class="bi bi-arrow-left-circle-fill"></i> Retour au site</a></div>
      </div>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Mes Fiches Médicales</h1>
                <p>Vue Médecin</p>
            </div>
            <div>
                <a href="medecin-create.php" class="btn btn-primary me-2"><i class="bi bi-plus-lg"></i> Nouvelle Fiche</a>
                <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
            </div>
        </div>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-file-earmark-medical"></i></div>
                <div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total Fiches</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #E0F2FE; color: #0284C7;"><i class="bi bi-calendar-plus"></i></div>
                <div class="stat-content"><h3><?= $stats['ce_mois'] ?></h3><p>Fiches ce mois</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #FEF3C7; color: #D97706;"><i class="bi bi-envelope"></i></div>
                <div class="stat-content"><h3><?= $stats['emails_sent'] ?></h3><p>Emails envoyés</p></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Liste des Fiches</h2>
                <form class="search-form" method="GET">
                    <input type="text" name="search" id="searchInput" class="search-input" placeholder="Rechercher patient, motif..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable()">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body">
                <table class="table" id="ficheTable">
                    <thead>
                        <tr>
                            <th style="cursor:pointer" onclick="sortTable(0, 'ficheTable')">ID Fiche <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(1, 'ficheTable')">Date Création <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(2, 'ficheTable')">Date RDV <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(3, 'ficheTable')">Patient <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(4, 'ficheTable')">Tarif <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fiches as $fiche): ?>
                        <tr>
                            <td>#<?= $fiche['idFiche'] ?></td>
                            <td><?= date('d/m/Y', strtotime($fiche['dateGeneration'])) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($fiche['dateHeureDebut'])) ?></td>
                            <td><?= htmlspecialchars($fiche['patient_nom'] . ' ' . $fiche['patient_prenom']) ?></td>
                            <td><?= $fiche['tarifConsultation'] ? $fiche['tarifConsultation'] . ' TND' : '-' ?></td>
                            <td class="actions-col" style="display: flex; gap: 5px;">
                                <a href="medecin-edit.php?id=<?= $fiche['idFiche'] ?>" class="btn btn-outline"><i class="bi bi-pencil"></i></a>
                                <form method="POST" style="display:inline;" onsubmit="confirmSwal(event, this, '')">
                                    <input type="hidden" name="delete_id" value="<?= $fiche['idFiche'] ?>">
                                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($fiches)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Aucune fiche trouvée.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function filterTable() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("ficheTable");
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

