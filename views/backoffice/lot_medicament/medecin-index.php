<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/LotMedicamentController.php';

$lotController = new LotMedicamentController();

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'expiration';
$dir = $_GET['dir'] ?? 'ASC';
$filters = ['search' => $search, 'sort' => $sort, 'dir' => $dir];
$lotData = $lotController->getAllLotMedicaments($filters);
$lots = $lotData['success'] ? $lotData['lots'] : [];

// Detection AJAX pour recherche dynamique
if (isset($_GET['ajax'])) {
    ob_start();
}

// Pagination Logic
$items_per_page = 5;
$total_items = count($lots);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

$offset = ($current_page - 1) * $items_per_page;
$paginated_lots = array_slice($lots, $offset, $items_per_page);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lots Médicaments - Médecin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F0FDF9;
            --navy: #1E3A52; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-green: 0 8px 30px rgba(29,158,117,.18);
            --radius-md: 12px; --radius-lg: 20px;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; min-height: 100vh; }

        /* ════ MEDECIN SIDEBAR ════ */
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
        .sidebar-footer { padding:16px; border-top:1px solid rgba(29,158,117,.10); margin-top:auto; }
        
        .dashboard-main { padding: 32px 40px; overflow-y: auto; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--navy); }
        .card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); overflow: hidden; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 24px; }
        .table { width: 100%; border-collapse: collapse; text-align: left; }
        .table th { background: #F8FAFC; padding: 12px 16px; color: #64748B; border-bottom: 1px solid var(--gray-200); }
        .table td { padding: 16px; border-bottom: 1px solid var(--gray-200); vertical-align: middle; }
        .search-form { display: flex; gap: 10px; }
        .search-input { padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 8px; }
        .btn { padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;}
        .btn-primary { background: var(--green); color: white; }
        .btn-secondary { background: var(--gray-500); color: white; }

        .pdf-header { display: none; }

        @media print { .dashboard-sidebar, .search-form, .btn { display: none !important; } .dashboard-container { display: block; } .dashboard-main { padding: 0; } }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; padding: 0 20px 20px; }
        .page-link { padding: 8px 16px; border-radius: 8px; background: white; border: 1px solid var(--gray-200); color: var(--navy); text-decoration: none; transition: 0.3s; font-weight: 500; font-size: 13px; }
        .page-link:hover { border-color: var(--green); color: var(--green); }
        .page-link.active { background: var(--green); color: white; border-color: var(--green); }
        .page-link.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
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
      <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Mes Consultations</div>
        <a href="../rendezvous/medecin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-calendar-check"></i></span> Rendez-vous
        </a>
        <a href="../ficherdv/medecin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span> Fiches Médicales
        </a>
        
        <div class="sidebar-nav-section-label">Gestion Stock</div>
        <a href="medecin-index.php" class="sidebar-nav-item active">
          <span class="nav-icon"><i class="bi bi-box-seam"></i></span> Lots Médicaments
        </a>
        <a href="../distribution/medecin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-truck"></i></span> Distributions
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout">
          <span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span> Déconnexion
        </a>
      </div>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Lots de Médicaments</h1>
                <p>Consultation du stock disponible (Vue Médecin)</p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
            </div>
        </div>

        <div class="pdf-header">
            <div class="pdf-logo">
                <div class="pdf-logo-icon"><i class="fas fa-hospital-alt"></i></div>
                <div class="pdf-title">MedChain</div>
            </div>
            <div class="pdf-meta">
                Date: <?= date('d/m/Y') ?><br>
                Généré par MedChain System<br>
                Inventaire des Lots
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Inventaire des Lots</h2>
                <div style="display:flex; gap:10px; align-items:center;">
                    <form class="search-form" method="GET" style="display:flex; gap:10px;">
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
                        <input type="text" name="search" id="searchInput" class="search-input" placeholder="Rechercher médicament..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable()">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                    </form>
                </div>
            </div>
            <div id="dynamic-content">
            <div class="card-body">
                <table class="table" id="dataTable">
                    <thead>
                        <?php
                        function sortLink($field, $label, $currentSort, $currentDir, $search) {
                            $nextDir = ($currentSort === $field && $currentDir === 'ASC') ? 'DESC' : 'ASC';
                            $icon = '';
                            if ($currentSort === $field) {
                                $icon = $currentDir === 'ASC' ? ' <i class="bi bi-sort-up"></i>' : ' <i class="bi bi-sort-down"></i>';
                            }
                            return "<a href='?sort=$field&dir=$nextDir&search=" . urlencode($search) . "' style='text-decoration:none; color:inherit;'>$label$icon</a>";
                        }
                        ?>
                        <tr>
                            <th><?= sortLink('nom', 'Médicament', $sort, $dir, $search) ?></th>
                            <th><?= sortLink('type', 'Type', $sort, $dir, $search) ?></th>
                            <th><?= sortLink('expiration', "Date d'expiration", $sort, $dir, $search) ?></th>
                            <th><?= sortLink('restante', 'Quantité Restante', $sort, $dir, $search) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($paginated_lots as $lot): ?>
                        <tr>
                            <td><?= htmlspecialchars($lot['nom_medicament']) ?> (Lot #<?= $lot['id_lot'] ?>)</td>
                            <td><?= htmlspecialchars($lot['type_medicament']) ?></td>
                            <td><?= date('d/m/Y', strtotime($lot['date_expiration'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($lot['quantite_restante']) ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($paginated_lots)): ?>
                        <tr><td colspan="4" style="text-align:center;">Aucun lot de médicament trouvé.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages >= 1): ?>
            <div class="pagination">
                <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>" class="page-link <?= $current_page <= 1 ? 'disabled' : '' ?>"><i class="bi bi-chevron-left"></i></a>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-link <?= $current_page == $i ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>" class="page-link <?= $current_page >= $total_pages ? 'disabled' : '' ?>"><i class="bi bi-chevron-right"></i></a>
            </div>
            <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
let searchTimeout;
function filterTable() {
    clearTimeout(searchTimeout);
    const searchInput = document.getElementById('searchInput');
    const searchValue = searchInput.value;
    
    var filter = searchValue.toUpperCase();
    var table = document.getElementById("dataTable");
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        tr[i].style.display = "none";
        var td = tr[i].getElementsByTagName("td");
        for (var j = 0; j < td.length; j++) {
            if (td[j]) {
                if ((td[j].textContent || td[j].innerText).toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                    break;
                }
            }
        }
    }

    searchTimeout = setTimeout(() => {
        const url = new URL(window.location.href);
        url.searchParams.set('search', searchValue);
        url.searchParams.set('ajax', '1');
        url.searchParams.set('page', '1');

        fetch(url)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('dynamic-content');
                if (newContent) {
                    document.getElementById('dynamic-content').innerHTML = newContent.innerHTML;
                    const pushUrl = new URL(window.location.href);
                    pushUrl.searchParams.set('search', searchValue);
                    pushUrl.searchParams.set('page', '1');
                    window.history.pushState({}, '', pushUrl);
                }
            });
    }, 500);
}
</script>
<?php
if (isset($_GET['ajax'])) {
    echo ob_get_clean();
    exit;
}
?>
</body>
</html>
