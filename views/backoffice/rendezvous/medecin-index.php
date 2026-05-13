<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/RendezVousController.php';
$rdvController = new RendezVousController();
$userId = $_SESSION['user_id'];

$search = $_GET['search'] ?? '';
$filters = ['search' => $search];
$rdvData = $rdvController->getAllRendezVous($filters, 'medecin', $userId);
$rendezvous = $rdvData['success'] ? $rdvData['rdvs'] : [];
$stats = $rdvController->getStats('medecin', $userId);

// Pagination
$items_per_page = 5;
$total_items = count($rendezvous);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
$offset = ($current_page - 1) * $items_per_page;
$paginated_rdv = array_slice($rendezvous, $offset, $items_per_page);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mes Consultations – Espace Médecin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../components/medecin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:32px;}
    .stat-card{background:var(--white);border-radius:var(--radius-lg);padding:20px;display:flex;align-items:center;gap:16px;box-shadow:var(--shadow-sm);border:1px solid rgba(29,158,117,.15);}
    .stat-icon{width:52px;height:52px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:24px;background:rgba(29,158,117,0.1);color:var(--green);}
    .stat-content h3{font-size:28px;font-weight:700;color:var(--navy);margin-bottom:4px;}
    .stat-content p{font-size:13px;color:var(--gray-500);}
    .status-badge{padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;}
    .status-planifie{background:#E0F2FE;color:#0284C7;}
    .status-termine{background:#DCFCE7;color:#16A34A;}
    .status-annule{background:#FEF2F2;color:#EF4444;}
    .sort-select{padding:8px 12px;border-radius:8px;border:1px solid var(--gray-200);background:white;color:var(--navy);font-weight:600;font-size:13px;outline:none;cursor:pointer;}
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Mes Consultations</h1>
                <p>Vue Médecin</p>
            </div>
            <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-calendar"></i></div>
                <div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total Consultations</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #E0F2FE; color: #0284C7;"><i class="bi bi-calendar-plus"></i></div>
                <div class="stat-content"><h3><?= $stats['ce_mois'] ?></h3><p>Ce mois-ci</p></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Liste des rendez-vous</h2>
                <div style="display:flex; gap:10px; align-items:center;">
                    <select class="sort-select" onchange="handleSortChange(this, 'rdvTable')">
                        <option value="">Tri par...</option>
                        <option value="0">Date & Heure</option>
                        <option value="1">Patient</option>
                        <option value="2">Type</option>
                        <option value="4">Statut</option>
                    </select>
                    <form method="GET" style="display:flex;gap:8px;">
                        <input type="text" name="search" class="search-input" placeholder="Patient..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                    </form>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="table" id="rdvTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable(0, 'rdvTable')">Date & Heure</th>
                                <th onclick="sortTable(1, 'rdvTable')">Patient</th>
                                <th onclick="sortTable(2, 'rdvTable')">Type</th>
                                <th>Motif</th>
                                <th onclick="sortTable(4, 'rdvTable')">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($paginated_rdv as $rdv): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($rdv['dateHeureDebut'])) ?></td>
                                <td><?= htmlspecialchars($rdv['client_nom'] . ' ' . $rdv['client_prenom']) ?></td>
                                <td><?= htmlspecialchars($rdv['typeConsultation']) ?></td>
                                <td><?= htmlspecialchars(substr($rdv['motif'], 0, 30)) ?>...</td>
                                <td><span class="status-badge status-<?= $rdv['statut'] ?>"><?= ucfirst($rdv['statut']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($rendezvous)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:40px; color:var(--gray-500);">
                                        <i class="bi bi-calendar-x" style="font-size:24px; display:block; margin-bottom:10px;"></i>
                                        Aucun rendez-vous trouvé.
                                        <?php if(!$rdvData['success']): ?>
                                            <br><small style="color:#EF4444;">Erreur: <?= htmlspecialchars($rdvData['message'] ?? 'Inconnue') ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages > 1): ?>
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
        <div style="margin-top:20px; padding:10px; background:rgba(0,0,0,0.05); border-radius:8px; font-size:11px; color:var(--gray-500);">
            <i class="bi bi-info-circle"></i> Connecté en tant que: <strong><?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?></strong> (ID: <?= $_SESSION['user_id'] ?? '?' ?>) | Résultats: <?= count($rendezvous) ?>
        </div>
    </main>
</div>

<script>
function handleSortChange(select, tableId) {
    if (select.value !== "") sortTable(parseInt(select.value), tableId);
}
function sortTable(n, tableId) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById(tableId);
    switching = true; dir = "asc"; 
    while (switching) {
        switching = false; rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false; x = rows[i].getElementsByTagName("TD")[n]; y = rows[i+1].getElementsByTagName("TD")[n];
            if (dir == "asc") { if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) { shouldSwitch = true; break; }
            } else if (dir == "desc") { if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) { shouldSwitch = true; break; } }
        }
        if (shouldSwitch) { rows[i].parentNode.insertBefore(rows[i+1], rows[i]); switching = true; switchcount ++;
        } else { if (switchcount == 0 && dir == "asc") { dir = "desc"; switching = true; } }
    }
}
</script>
</body>
</html>
