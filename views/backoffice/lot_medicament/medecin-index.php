<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/LotMedicamentController.php';
$lotController = new LotMedicamentController();

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'expiration';
$dir = $_GET['dir'] ?? 'ASC';
$filters = ['search' => $search, 'sort' => $sort, 'dir' => $dir];
$lotData = $lotController->getAllLotMedicaments($filters);
$lots = $lotData['success'] ? $lotData['lots'] : [];

// Pagination
$items_per_page = 5;
$total_items = count($lots);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
$offset = ($current_page - 1) * $items_per_page;
$paginated_lots = array_slice($lots, $offset, $items_per_page);

if (isset($_GET['ajax'])) {
    ob_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Lots Médicaments – Espace Médecin – MedChain</title>
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
                <h1>Lots de Médicaments</h1>
                <p>Consultation du stock disponible</p>
            </div>
            <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Inventaire des Lots</h2>
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
                                <th><?= sortLink('nom', 'Médicament', $sort, $dir, $search) ?></th>
                                <th><?= sortLink('type', 'Type', $sort, $dir, $search) ?></th>
                                <th><?= sortLink('expiration', "Date d'expiration", $sort, $dir, $search) ?></th>
                                <th><?= sortLink('restante', 'Quantité', $sort, $dir, $search) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($paginated_lots as $lot): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($lot['nom_medicament']) ?></strong> (Lot #<?= $lot['id_lot'] ?>)</td>
                                <td><?= htmlspecialchars($lot['type_medicament']) ?></td>
                                <td><?= date('d/m/Y', strtotime($lot['date_expiration'])) ?></td>
                                <td><strong><?= htmlspecialchars($lot['quantite_restante']) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($paginated_lots)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:40px;">Aucun lot trouvé.</td></tr>
                            <?php endif; ?>
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
