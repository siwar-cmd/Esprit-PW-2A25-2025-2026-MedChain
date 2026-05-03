<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/DistributionController.php';

$distController = new DistributionController();

// Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $result = $distController->updateStatus($_POST['id_distribution'], $_POST['statut']);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header('Location: admin-index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'date';
$dir = $_GET['dir'] ?? 'DESC';
$filters = ['search' => $search, 'sort' => $sort, 'dir' => $dir];
$distData = $distController->getAllDistributions($filters);
$distributions = $distData['success'] ? $distData['distributions'] : [];

$stats = $distController->getStats();

// Detection AJAX pour recherche dynamique
if (isset($_GET['ajax'])) {
    ob_start();
}

// Pagination Logic
$items_per_page = 5;
$total_items = count($distributions);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

$offset = ($current_page - 1) * $items_per_page;
$paginated_dist = array_slice($distributions, $offset, $items_per_page);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distributions - Admin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green: #1D9E75;
            --navy: #1E3A52;
            --gray-500: #6B7280;
            --gray-200: #E5E7EB;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --radius-md: 12px;
            --radius-lg: 20px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        .dashboard-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
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

        .badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-waiting { background: #FEF3C7; color: #92400E; }
        .badge-success { background: #DCFCE7; color: #16A34A; }
        .badge-danger { background: #FEF2F2; color: #EF4444; }

        .alert-success { background: #DCFCE7; color: #16A34A; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEF2F2; color: #EF4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #FEE2E2; }

        @media print {
            .dashboard-sidebar, .search-form, .btn, .actions-col { display: none !important; }
            .dashboard-container { display: block; }
            .dashboard-main { padding: 0; background: white; }
            .card { border: none; box-shadow: none; }
            .pdf-header { 
                display: flex; justify-content: space-between; align-items: center; 
                margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid var(--green);
            }
            .pdf-logo { display: flex; align-items: center; gap: 15px; }
            .pdf-logo-icon { width: 50px; height: 50px; background: var(--green); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
            .pdf-title { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; color: var(--navy); }
            .pdf-meta { text-align: right; font-size: 12px; color: var(--gray-500); line-height: 1.6; }
            .table th { background: var(--green) !important; color: white !important; -webkit-print-color-adjust: exact; }
        }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; padding: 0 20px 20px; }
        .page-link { padding: 8px 16px; border-radius: 8px; background: white; border: 1px solid var(--gray-200); color: var(--navy); text-decoration: none; transition: 0.3s; font-weight: 500; font-size: 13px; }
        .page-link:hover { border-color: var(--green); color: var(--green); }
        .page-link.active { background: var(--green); color: white; border-color: var(--green); }
        .page-link.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
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
            <a href="../rendezvous/admin-index.php" class="dashboard-nav-item">
                <i class="fas fa-calendar-check"></i> Rendez-vous
            </a>
            <a href="../ficherdv/admin-index.php" class="dashboard-nav-item">
                <i class="fas fa-file-medical-alt"></i> Fiches Médicales
            </a>
            <a href="../lot_medicament/admin-index.php" class="dashboard-nav-item">
                <i class="fas fa-pills"></i> Lots Médicaments
            </a>
            <a href="admin-index.php" class="dashboard-nav-item active">
                <i class="fas fa-truck"></i> Distributions
            </a>
            <a href="../admin-reports-statistics.php" class="dashboard-nav-item">
                <i class="fas fa-chart-pie"></i> Statistiques
            </a>
            
            <div class="dashboard-nav-title">Personnel</div>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Toutes les Distributions</h1>
                <p>Validation et gestion des stocks</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
            </div>
        </div>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert-success"><i class="bi bi-check-circle-fill"></i> <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert-error"><i class="bi bi-exclamation-triangle-fill"></i> <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 30px;">
            <div class="card-body" style="display:flex; justify-content:space-around;">
                <div style="text-align:center;">
                    <h3 style="font-size:24px; color:var(--navy);"><?= $stats['total'] ?></h3>
                    <p style="color:var(--gray-500); font-size:14px;">Total Distributions</p>
                </div>
                <div style="text-align:center;">
                    <h3 style="font-size:24px; color:var(--green);"><?= $stats['sum_distribuee'] ?></h3>
                    <p style="color:var(--gray-500); font-size:14px;">Quantité Totale Distribuée</p>
                </div>
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
                Rapport des Distributions
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Liste globale</h2>
                <div style="display:flex; gap:10px; align-items:center;">
                    <form class="search-form" method="GET" style="display:flex; gap:10px;">
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
                        <input type="text" name="search" id="searchInput" class="search-input" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable()">
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
                            <th><?= sortLink('date', 'Date', $sort, $dir, $search) ?></th>
                            <th><?= sortLink('medicament', 'Lot de Médicament', $sort, $dir, $search) ?></th>
                            <th><?= sortLink('quantite', 'Qté', $sort, $dir, $search) ?></th>
                            <th><?= sortLink('patient', 'Patient', $sort, $dir, $search) ?></th>
                            <th><?= sortLink('responsable', 'Responsable', $sort, $dir, $search) ?></th>
                            <th><?= sortLink('statut', 'Statut', $sort, $dir, $search) ?></th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($paginated_dist as $dist): 
                            $badgeClass = 'badge-waiting';
                            if ($dist['statut'] === 'Accepte') $badgeClass = 'badge-success';
                            if ($dist['statut'] === 'Rejete') $badgeClass = 'badge-danger';
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($dist['date_distribution'])) ?></td>
                            <td><?= htmlspecialchars($dist['nom_medicament']) ?></td>
                            <td><strong><?= htmlspecialchars($dist['quantite_distribuee']) ?></strong></td>
                            <td><?= htmlspecialchars($dist['patient']) ?></td>
                            <td><?= htmlspecialchars($dist['responsable']) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($dist['statut']) ?></span></td>
                            <td class="actions-col">
                                <?php if ($dist['statut'] === 'En attente'): ?>
                                    <div style="display:flex; gap:5px;">
                                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete(this);">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_distribution" value="<?= $dist['id_distribution'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer"><i class="bi bi-trash"></i></button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id_distribution" value="<?= $dist['id_distribution'] ?>">
                                            <input type="hidden" name="statut" value="Accepte">
                                            <button type="submit" class="btn btn-primary btn-sm" title="Accepter"><i class="bi bi-check-lg"></i></button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id_distribution" value="<?= $dist['id_distribution'] ?>">
                                            <input type="hidden" name="statut" value="Rejete">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Rejeter"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size:11px; color:var(--gray-500); font-style:italic;">Traité</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($paginated_dist)): ?>
                        <tr><td colspan="5" style="text-align:center;">Aucune distribution trouvée.</td></tr>
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
    
    // On garde le filtrage JS immédiat pour le feedback visuel rapide sur la page actuelle
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

    // Recherche AJAX debouncée pour le reste de la DB
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

function confirmDelete(form) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action est irréversible !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1D9E75',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer !',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
    return false;
}

<?php if (isset($_SESSION['success_message'])): ?>
    Swal.fire('Succès', '<?= addslashes($_SESSION['success_message']) ?>', 'success');
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    Swal.fire('Erreur', '<?= addslashes($_SESSION['error_message']) ?>', 'error');
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>
</script>
<?php
if (isset($_GET['ajax'])) {
    $content = ob_get_clean();
    // Extraire uniquement le contenu de #dynamic-content pour éviter les doublons de structure si mal géré
    // Mais le plus simple est de renvoyer tout ce qui a été bufférisé depuis le début de la partie dynamique
    echo $content;
    exit;
}
?>
</body>
</html>
