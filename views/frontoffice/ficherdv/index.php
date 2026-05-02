<?php
session_start();
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';

$authController = new AuthController();
if (!$authController->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$currentUser = $authController->getCurrentUser();
if ($currentUser->getRole() !== 'patient') {
    header('Location: ../../../views/backoffice/admin-dashboard.php');
    exit;
}

$ficheController = new FicheRendezVousController();

$search = $_GET['search'] ?? '';
$filters = ['search' => $search];
$ficheData = $ficheController->getAllFiches($filters, 'patient', $currentUser->getId());
$fiches = $ficheData['success'] ? $ficheData['fiches'] : [];

$stats = $ficheController->getStats('patient', $currentUser->getId());

// Pagination Logic
$items_per_page = 5;
$total_items = count($fiches);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

$offset = ($current_page - 1) * $items_per_page;
$paginated_fiches = array_slice($fiches, $offset, $items_per_page);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Fiches Médicales - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F0FDF9;
            --navy: #1E3A52; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-green: 0 8px 30px rgba(29,158,117,.18);
            --radius-md: 12px; --radius-lg: 20px;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; min-height: 100vh; display: flex; flex-direction: column; }

        /* ════ FRONTOFFICE SIDEBAR ════ */
        .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        .dashboard-sidebar { background: linear-gradient(160deg,#fff 0%,#f0fdf9 60%,#e6faf3 100%); border-right:1px solid rgba(29,158,117,.15); position:sticky; top:0; height:100vh; display:flex; flex-direction:column; overflow-y:auto; box-shadow:4px 0 24px rgba(29,158,117,.08); }
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
        .sidebar-health-widget { margin:0 16px 6px; background:var(--white); border:1px solid rgba(29,158,117,.15); border-radius:var(--radius-md); padding:14px 16px; }
        .sidebar-health-label { font-size:11px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
        .sidebar-health-bar-wrap { background:var(--gray-200); border-radius:6px; height:6px; overflow:hidden; }
        .sidebar-health-bar { height:100%; border-radius:6px; background:linear-gradient(90deg,var(--green),#34D399); animation:health-grow 1.2s ease-out forwards; }
        @keyframes health-grow { from{width:0} to{width:78%} }
        .sidebar-health-stats { display:flex; justify-content:space-between; margin-top:8px; }
        .sidebar-health-stat { font-size:12px; color:var(--gray-500); }
        .sidebar-health-stat strong { color:var(--green); font-weight:700; }
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
        .dashboard-main { padding:32px 40px; overflow-y:auto; width:100%; }
        @media (max-width:768px) { .dashboard-container{grid-template-columns:1fr} .dashboard-sidebar{position:fixed;left:-290px;top:0;bottom:0;width:280px;z-index:1000;transition:left .3s} .dashboard-sidebar.open{left:0} }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; flex: 1; width: 100%; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h1 { font-family: 'Syne', sans-serif; font-size: 32px; color: var(--navy); }
        .page-title p { color: var(--gray-500); margin-top: 5px; }
        
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-primary { background: var(--green); color: white; }
        .btn-primary:hover { background: var(--green-dark); }
        .btn-secondary { background: var(--white); color: var(--gray-500); border: 1px solid var(--gray-200); }
        .btn-stats { background: linear-gradient(135deg, var(--green), var(--navy)); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-stats:hover { background: linear-gradient(135deg, var(--green-dark), var(--navy)); color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(29, 158, 117, 0.3); }
        .sort-select { padding: 9px 15px; border-radius: 8px; border: 1px solid var(--gray-200); background: white; color: var(--navy); font-weight: 500; font-size: 14px; outline: none; cursor: pointer; transition: all 0.3s; }
        .sort-select:hover { border-color: var(--green); }
        
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: var(--white); padding: 24px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 20px; border: 1px solid rgba(29,158,117,.1); }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
        .stat-icon.primary { background: rgba(29,158,117,0.1); color: var(--green); }
        .stat-icon.secondary { background: #E0F2FE; color: #0284C7; }
        .stat-info h3 { font-size: 32px; color: var(--navy); font-weight: 700; line-height: 1; margin-bottom: 4px; }
        .stat-info p { color: var(--gray-500); font-size: 14px; font-weight: 500; margin: 0; }
        
        .card { background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; border: 1px solid rgba(29,158,117,.1); }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; }
        .card-header h2 { font-size: 18px; color: var(--navy); font-weight: 600; }
        
        .search-box { display: flex; gap: 10px; }
        .search-input { padding: 10px 16px; border: 1px solid var(--gray-200); border-radius: 8px; width: 250px; font-family: inherit; }
        .search-input:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 16px 24px; border-bottom: 1px solid var(--gray-200); }
        th { color: var(--gray-500); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { color: var(--navy); font-size: 15px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #F8FAFC; }
        
        .empty-state { padding: 60px 20px; text-align: center; color: var(--gray-500); }
        .empty-state i { font-size: 48px; color: var(--gray-200); margin-bottom: 16px; display: block; }
        
        @media print {
            .dashboard-sidebar, .search-box, .btn { display: none !important; }
            .dashboard-container { display: block; }
            .container { margin: 0; padding: 0; max-width: none; }
            .card { box-shadow: none; border: none; }
        }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; padding-bottom: 20px; }
        .page-link { padding: 8px 16px; border-radius: 8px; background: white; border: 1px solid var(--gray-200); color: var(--navy); text-decoration: none; transition: 0.3s; font-weight: 500; font-size: 13px; }
        .page-link:hover { border-color: var(--green); color: var(--green); }
        .page-link.active { background: var(--green); color: white; border-color: var(--green); }
        .page-link.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar FrontOffice Moderne -->
    <aside class="dashboard-sidebar" id="sidebar">
      <div class="sidebar-logo-zone">
        <a href="../home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div>
            <div class="sidebar-logo-text">Med<span>Chain</span></div>
            <div class="sidebar-tagline">Espace Patient</div>
          </div>
        </a>
      </div>
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
        <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_prenom'] ?? 'Patient') ?> <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></div>
        <div class="sidebar-user-role"><i class="bi bi-heart-pulse-fill"></i> Patient</div>
      </div>
      <div class="sidebar-health-widget">
        <div class="sidebar-health-label"><i class="bi bi-activity" style="color:var(--green);margin-right:5px;"></i>Suivi de santé</div>
        <div class="sidebar-health-bar-wrap"><div class="sidebar-health-bar"></div></div>
        <div class="sidebar-health-stats">
          <span class="sidebar-health-stat">Profil <strong>78%</strong> complet</span>
          <span class="sidebar-health-stat" style="color:var(--green);"><i class="bi bi-shield-check"></i> Actif</span>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Navigation</div>
        <a href="../home/index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-house-door-fill"></i></span> Accueil</a>
        <a href="../auth/profile.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-person-fill"></i></span> Mon Profil</a>
        <div class="sidebar-nav-section-label">Mes Services</div>
        <a href="../rendezvous/index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span> Mes Rendez-vous</a>
        <a href="index.php" class="sidebar-nav-item active"><span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span> Mes Fiches Médicales</a>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout" onclick="confirmSwal(event, this, '')"><span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span> Déconnexion</a>
        <div style="margin-top:10px;"><a href="../home/index.php" class="sidebar-footer-back"><i class="bi bi-arrow-left-circle-fill"></i> Retour au site</a></div>
      </div>
    </aside>

    <main class="dashboard-main">
        <div class="container">
    <div class="page-header" data-aos="fade-down">
        <div class="page-title">
            <h1>Mes Fiches Médicales</h1>
            <p>Retrouvez toutes vos fiches et consignes de consultations</p>
        </div>
        <div class="page-actions" style="display:flex; align-items:center; gap:10px;">
            <select class="sort-select" onchange="handleSortChange(this, 'ficheTable')">
                <option value="">Tri par...</option>
                <option value="0">Date Consultation</option>
                <option value="1">Médecin</option>
                <option value="4">Tarif</option>
            </select>
            <a href="stats.php" class="btn btn-stats"><i class="bi bi-bar-chart-line-fill"></i> Statistiques</a>
            <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> PDF</button>
        </div>
    </div>
    
    <div class="stats-container" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-file-earmark-text"></i></div>
            <div class="stat-info">
                <h3><?= $stats['total'] ?></h3>
                <p>Total Fiches</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon secondary"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-info">
                <h3><?= $stats['ce_mois'] ?></h3>
                <p>Générées ce mois</p>
            </div>
        </div>
    </div>
    
    <div class="card" data-aos="fade-up" data-aos-delay="200">
        <div class="card-header">
            <h2>Historique de mes fiches</h2>
            <form class="search-box" method="GET">
                <input type="text" name="search" id="searchInput" class="search-input" placeholder="Rechercher par médecin, consigne..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable()">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
            </form>
        </div>
        
        <div class="table-responsive">
            <table id="ficheTable">
                <thead>
                    <tr>
                        <th style="cursor:pointer" onclick="sortTable(0, 'ficheTable')">Date Consultation <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                        <th style="cursor:pointer" onclick="sortTable(1, 'ficheTable')">Médecin <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                        <th>Pièces à apporter</th>
                        <th style="cursor:pointer" onclick="sortTable(4, 'ficheTable')">Tarif & Remboursement <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fiches)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="bi bi-file-earmark-x"></i>
                                    <h3>Aucune fiche médicale trouvée</h3>
                                    <p>Vos médecins n'ont pas encore généré de fiches pour vos consultations.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginated_fiches as $fiche): ?>
                            <tr>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($fiche['dateHeureDebut'])) ?></strong><br>
                                    <span style="color: var(--gray-500); font-size: 13px;"><?= date('H:i', strtotime($fiche['dateHeureDebut'])) ?></span>
                                </td>
                                <td>Dr. <?= htmlspecialchars($fiche['medecin_nom'] . ' ' . $fiche['medecin_prenom']) ?></td>
                                <td><?= nl2br(htmlspecialchars($fiche['piecesAApporter'] ?: '-')) ?></td>
                                <td>
                                    <?php if ($fiche['tarifConsultation']): ?>
                                        <strong><?= $fiche['tarifConsultation'] ?> TND</strong><br>
                                        <span style="color: var(--gray-500); font-size: 13px;"><?= htmlspecialchars($fiche['modeRemboursement'] ?: 'Non spécifié') ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view.php?id=<?= $fiche['idFiche'] ?>" class="btn btn-secondary" style="padding: 5px 12px; font-size: 13px;">
                                        <i class="bi bi-eye"></i> Voir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages >= 1): ?>
        <div class="pagination">
            <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>" 
               class="page-link <?= $current_page <= 1 ? 'disabled' : '' ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                   class="page-link <?= $current_page == $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <a href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>" 
               class="page-link <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
</main>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 50 });

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
                if (td[j]) {
                    txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        break;
                    }
                }
            }
        }
    }

    function handleSortChange(select, tableId) {
        if (select.value !== "") {
            sortTable(parseInt(select.value), tableId);
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

