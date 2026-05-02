<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';

$ficheController = new FicheRendezVousController();

$search = $_GET['search'] ?? '';
$filters = ['search' => $search];

$ficheData = $ficheController->getAllFiches($filters, 'admin', null);
$fiches = $ficheData['success'] ? $ficheData['fiches'] : [];

$stats = $ficheController->getStats('admin', null);

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
    <title>Gestion des Fiches - Admin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
            --green-deep: #094D3C;
            --green-light: #E8F7F2;
            --navy: #1E3A52;
            --gray-500: #6B7280;
            --gray-200: #E5E7EB;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --shadow-lg: 0 12px 40px rgba(0,0,0,.10);
            --shadow-green: 0 8px 30px rgba(29,158,117,.22);
            --radius-md: 12px;
            --radius-lg: 20px;
            --radius-xl: 28px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
            position: relative;
            z-index: 2;
        }

        .dashboard-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .dashboard-logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .dashboard-logo-icon { width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
        .dashboard-logo-icon i { font-size: 18px; color: white; }
        .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; }
        .dashboard-logo-text span { color: #3b82f6; }

        .dashboard-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
        .dashboard-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .dashboard-nav-item i { font-size: 18px; width: 24px; }
        .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .dashboard-nav-item.active { background: rgba(59,130,246,0.2); color: #3b82f6; }
        .dashboard-nav-item.logout { margin-top: auto; margin-bottom: 20px; color: #F87171; }
        .dashboard-nav-item.logout:hover { background: rgba(248,113,113,0.1); }
        .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }

        .dashboard-main { padding: 32px 40px; overflow-y: auto; }
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

        @media print {
            .dashboard-sidebar, .search-form, .btn { display: none !important; }
            .dashboard-container { display: block; }
            .dashboard-main { padding: 0; }
        }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; padding: 0 20px 20px; }
        .page-link { padding: 8px 16px; border-radius: 8px; background: white; border: 1px solid var(--gray-200); color: var(--navy); text-decoration: none; transition: 0.3s; font-weight: 500; font-size: 13px; }
        .page-link:hover { border-color: var(--green); color: var(--green); }
        .page-link.active { background: var(--green); color: white; border-color: var(--green); }
        .page-link.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar" id="sidebar">
        <div class="dashboard-logo">
            <a href="../admin-dashboard.php">
                <div class="dashboard-logo-icon"><i class="fas fa-hospital-alt"></i></div>
                <div class="dashboard-logo-text">Med<span>Chain</span></div>
            </a>
        </div>
        
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="../admin-dashboard.php" class="dashboard-nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="../admin-users.php" class="dashboard-nav-item">
                <i class="fas fa-users"></i> Utilisateurs
            </a>
            <a href="../admin-create-user.php" class="dashboard-nav-item">
                <i class="fas fa-user-plus"></i> Nouvel utilisateur
            </a>
            <a href="../rendezvous/admin-index.php" class="dashboard-nav-item">
                <i class="fas fa-calendar-check"></i> Rendez-vous
            </a>
            <a href="admin-index.php" class="dashboard-nav-item active">
                <i class="fas fa-file-medical-alt"></i> Fiches Médicales
            </a>
            <a href="../admin-reports-statistics.php" class="dashboard-nav-item">
                <i class="fas fa-chart-pie"></i> Statistiques
            </a>
            
            <div class="dashboard-nav-title">Personnel</div>
            <a href="../../frontoffice/auth/profile.php" class="dashboard-nav-item">
                <i class="fas fa-user-circle"></i> Mon profil
            </a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" onclick="confirmSwal(event, this, 'Déconnexion ?', 'Voulez-vous vraiment vous déconnecter ?')">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Supervision des Fiches</h1>
                <p>Vue Administrateur Globale</p>
            </div>
            <div class="header-actions" style="display: flex; gap: 12px;">
                <a href="medecin-stats.php" class="btn btn-primary" style="background: var(--blue-600, #2563eb);"><i class="fas fa-chart-bar"></i> Statistiques</a>
                <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-files"></i></div>
                <div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total Fiches Système</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #E0F2FE; color: #0284C7;"><i class="bi bi-calendar-plus"></i></div>
                <div class="stat-content"><h3><?= $stats['ce_mois'] ?></h3><p>Nouvelles ce mois</p></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Toutes les Fiches Médicales</h2>
                <form class="search-form" method="GET">
                    <input type="text" name="search" id="searchInput" class="search-input" placeholder="Rechercher patient, médecin..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable()">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body">
                <table class="table" id="ficheTable">
                    <thead>
                        <tr>
                            <th style="cursor:pointer" onclick="sortTable(0, 'ficheTable')">ID Fiche <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(1, 'ficheTable')">Type RDV <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(2, 'ficheTable')">Médecin <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(3, 'ficheTable')">Patient <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(4, 'ficheTable')">Date RDV <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(5, 'ficheTable')">Statut RDV <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($paginated_fiches as $fiche): ?>
                        <tr>
                            <td>#<?= $fiche['idFiche'] ?></td>
                            <td><span class="badge bg-light text-dark" style="border: 1px solid var(--gray-200); font-weight: 500;"><?= htmlspecialchars($fiche['typeConsultation']) ?></span></td>
                            <td>Dr. <?= htmlspecialchars($fiche['medecin_nom'] . ' ' . $fiche['medecin_prenom']) ?></td>
                            <td><?= htmlspecialchars($fiche['patient_nom'] . ' ' . $fiche['patient_prenom']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($fiche['dateHeureDebut'])) ?></td>
                            <td><span class="status-badge" style="font-weight:600;"><?= ucfirst($fiche['statut']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($fiches)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Aucune fiche système trouvée.</td>
                        </tr>
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
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
