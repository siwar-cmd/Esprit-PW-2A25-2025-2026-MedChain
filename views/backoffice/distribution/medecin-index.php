<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/DistributionController.php';
$distController = new DistributionController();

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $result = $distController->deleteDistribution($_POST['id_distribution']);
    if ($result['success']) $_SESSION['success_message'] = "Distribution supprimée.";
    else $_SESSION['error_message'] = "Erreur lors de la suppression.";
    header('Location: medecin-index.php'); exit;
}

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'date';
$dir = $_GET['dir'] ?? 'DESC';
$filters = ['search' => $search, 'sort' => $sort, 'dir' => $dir];
$distData = $distController->getAllDistributions($filters);
$distributions = $distData['success'] ? $distData['distributions'] : [];

// Pagination
$items_per_page = 5;
$total_items = count($distributions);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
$offset = ($current_page - 1) * $items_per_page;
$paginated_dist = array_slice($distributions, $offset, $items_per_page);

if (isset($_GET['ajax'])) { ob_start(); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Distributions – Espace Médecin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../components/medecin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Distributions</h1>
                <p>Gestion des distributions aux patients</p>
            </div>
            <div style="display:flex; gap:12px;">
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouvelle</a>
                <a href="medecin-stats.php" class="btn btn-primary" style="background:#0284c7;"><i class="bi bi-bar-chart-fill"></i> Statistiques</a>
                <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> PDF</button>
            </div>
        </div>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div style="background:#DCFCE7;color:#16A34A;padding:15px;border-radius:8px;margin-bottom:20px;"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Liste des distributions</h2>
                <form class="search-form" method="GET" style="display:flex; gap:10px;">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                    <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
                    <input type="text" name="search" id="searchInput" class="search-input" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable()">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div id="dynamic-content">
                <div class="card-body" style="padding:0;">
                    <table class="table" id="dataTable">
                        <thead>
                            <?php
                            function sortLink($field, $label, $currentSort, $currentDir, $search) {
                                $nextDir = ($currentSort === $field && $currentDir === 'ASC') ? 'DESC' : 'ASC';
                                $icon = ($currentSort === $field) ? ($currentDir === 'ASC' ? ' <i class="bi bi-sort-up"></i>' : ' <i class="bi bi-sort-down"></i>') : '';
                                return "<a href='?sort=$field&dir=$nextDir&search=" . urlencode($search) . "' style='text-decoration:none; color:inherit;'>$label$icon</a>";
                            }
                            ?>
                            <tr>
                                <th><?= sortLink('date', 'Date', $sort, $dir, $search) ?></th>
                                <th><?= sortLink('medicament', 'Médicament', $sort, $dir, $search) ?></th>
                                <th><?= sortLink('quantite', 'Qté', $sort, $dir, $search) ?></th>
                                <th><?= sortLink('patient', 'Patient', $sort, $dir, $search) ?></th>
                                <th><?= sortLink('statut', 'Statut', $sort, $dir, $search) ?></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($paginated_dist as $dist): 
                                $badgeClass = ($dist['statut'] === 'Accepte') ? 'bg-success' : (($dist['statut'] === 'Rejete') ? 'bg-danger' : 'bg-warning');
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($dist['date_distribution'])) ?></td>
                                <td><?= htmlspecialchars($dist['nom_medicament']) ?></td>
                                <td><strong><?= htmlspecialchars($dist['quantite_distribuee']) ?></strong></td>
                                <td><?= htmlspecialchars($dist['patient']) ?></td>
                                <td><span class="badge <?= $badgeClass ?> text-white"><?= htmlspecialchars($dist['statut']) ?></span></td>
                                <td>
                                    <?php if ($dist['statut'] === 'En attente'): ?>
                                        <a href="edit.php?id=<?= $dist['id_distribution'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Annuler cette distribution ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_distribution" value="<?= $dist['id_distribution'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="background:#FEF2F2;color:#EF4444;border:1px solid #EF4444;"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:11px; color:var(--gray-500); font-style:italic;">Verrouillé</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&dir=<?= $dir ?>" class="page-link <?= $current_page <= 1 ? 'disabled' : '' ?>"><i class="bi bi-chevron-left"></i></a>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&dir=<?= $dir ?>" class="page-link <?= $current_page == $i ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&dir=<?= $dir ?>" class="page-link <?= $current_page >= $total_pages ? 'disabled' : '' ?>"><i class="bi bi-chevron-right"></i></a>
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
    const searchValue = document.getElementById('searchInput').value;
    searchTimeout = setTimeout(() => {
        const url = new URL(window.location.href);
        url.searchParams.set('search', searchValue);
        url.searchParams.set('ajax', '1');
        url.searchParams.set('page', '1');
        fetch(url).then(r => r.text()).then(html => {
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
<?php if (isset($_GET['ajax'])) { echo ob_get_clean(); exit; } ?>
</body>
</html>
