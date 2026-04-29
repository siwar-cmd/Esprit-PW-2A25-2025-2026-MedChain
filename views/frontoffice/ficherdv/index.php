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
            --green: #1D9E75; --green-dark: #0F6E56; --navy: #1E3A52;
            --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --radius-lg: 20px;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f9fafb; min-height: 100vh; display: flex; flex-direction: column; }
        
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

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; flex: 1; width: 100%; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h1 { font-family: 'Syne', sans-serif; font-size: 32px; color: var(--navy); }
        .page-title p { color: var(--gray-500); margin-top: 5px; }
        
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-primary { background: var(--green); color: white; }
        .btn-primary:hover { background: var(--green-dark); }
        .btn-secondary { background: var(--white); color: var(--gray-500); border: 1px solid var(--gray-200); }
        
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
            <a href="../rendezvous/index.php" class="dashboard-nav-item">
                <i class="bi bi-calendar-check"></i> Mes Rendez-vous
            </a>
            <a href="index.php" class="dashboard-nav-item active">
                <i class="bi bi-file-medical"></i> Mes Fiches
            </a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" onclick="confirmSwal(event, this, '')">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="container">
    <div class="page-header" data-aos="fade-down">
        <div class="page-title">
            <h1>Mes Fiches Médicales</h1>
            <p>Retrouvez toutes vos fiches et consignes de consultations</p>
        </div>
        <button onclick="window.print()" class="btn btn-secondary me-2"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
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
                        <th>Consignes</th>
                        <th>Pièces à apporter</th>
                        <th style="cursor:pointer" onclick="sortTable(4, 'ficheTable')">Tarif & Remboursement <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
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
                        <?php foreach ($fiches as $fiche): ?>
                            <tr>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($fiche['dateHeureDebut'])) ?></strong><br>
                                    <span style="color: var(--gray-500); font-size: 13px;"><?= date('H:i', strtotime($fiche['dateHeureDebut'])) ?></span>
                                </td>
                                <td>Dr. <?= htmlspecialchars($fiche['medecin_nom'] . ' ' . $fiche['medecin_prenom']) ?></td>
                                <td><?= nl2br(htmlspecialchars($fiche['consignesAvantConsultation'] ?: '-')) ?></td>
                                <td><?= nl2br(htmlspecialchars($fiche['piecesAApporter'] ?: '-')) ?></td>
                                <td>
                                    <?php if ($fiche['tarifConsultation']): ?>
                                        <strong><?= $fiche['tarifConsultation'] ?> TND</strong><br>
                                        <span style="color: var(--gray-500); font-size: 13px;"><?= htmlspecialchars($fiche['modeRemboursement'] ?: 'Non spécifié') ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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

