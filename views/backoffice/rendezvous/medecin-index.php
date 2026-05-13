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
    .sort-select{appearance:none; padding:10px 35px 10px 15px; border-radius:10px; border:1px solid var(--gray-200); background:#f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat calc(100% - 12px) center; color:var(--navy); font-weight:600; font-size:14px; outline:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 4px rgba(0,0,0,0.02);}
    .sort-select:hover{border-color:var(--green); background-color:white; box-shadow:0 2px 8px rgba(29,158,117,0.1);}
    .btn-action{padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s;}
    .btn-create-fiche{background:var(--green-light);color:var(--green-dark);border:1px solid rgba(29,158,117,0.2);}
    .btn-create-fiche:hover{background:var(--green);color:white;transform:translateY(-1px);}
    .btn-view-fiche{background:#E0F2FE;color:#0284C7;border:1px solid rgba(2,132,199,0.2);}
    .btn-view-fiche:hover{background:#0284C7;color:white;transform:translateY(-1px);}
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

        <a href="medecin-stats.php" class="btn btn-primary" style="margin-bottom: 20px; background: var(--green); border: none; padding: 12px 24px; border-radius: var(--radius-md); color: white; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
            <i class="bi bi-pie-chart-fill"></i> Afficher les Statistiques
        </a>

        <div class="card">
            <div class="card-header">
                <h2>Liste des rendez-vous</h2>
                <div style="display:flex; gap:15px; align-items:center; position:relative;">
                    <select class="sort-select" onchange="if(this.value !== '') sortTable(parseInt(this.value))" style="height: 42px;">
                        <option value="">Trier par...</option>
                        <option value="0">Date & Heure</option>
                        <option value="1">Patient</option>
                        <option value="2">Type</option>
                    </select>
                    <div style="position:relative;">
                        <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--gray-500); font-size:14px;"></i>
                        <input type="text" id="dynamicSearch" class="search-input" placeholder="Rechercher un patient ou motif..." onkeyup="filterTable()" style="width:250px; padding:10px 15px 10px 38px; border-radius:10px; border:1px solid var(--gray-200); outline:none; font-size:14px; transition:all 0.2s;">
                    </div>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="table" id="rdvTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable(0)" style="cursor:pointer;">Date & Heure <i class="bi bi-sort-alpha-down ms-1"></i></th>
                                <th onclick="sortTable(1)" style="cursor:pointer;">Patient <i class="bi bi-sort-alpha-down ms-1"></i></th>
                                <th onclick="sortTable(2)" style="cursor:pointer;">Type <i class="bi bi-sort-alpha-down ms-1"></i></th>
                                <th>Motif</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rendezvous as $rdv): ?>
                            <tr class="rdv-row">
                                <td data-sort="<?= strtotime($rdv['dateHeureDebut']) ?>"><?= date('d/m/Y H:i', strtotime($rdv['dateHeureDebut'])) ?></td>
                                <td><?= htmlspecialchars($rdv['client_nom'] . ' ' . $rdv['client_prenom']) ?></td>
                                <td><?= htmlspecialchars($rdv['typeConsultation']) ?></td>
                                <td><?= htmlspecialchars(substr($rdv['motif'], 0, 30)) ?>...</td>
                                <td>
                                    <?php if(empty($rdv['idFiche'])): ?>
                                        <a href="../ficherdv/medecin-create.php?idRDV=<?= $rdv['idRDV'] ?>" class="btn-action btn-create-fiche">
                                            <i class="bi bi-file-earmark-plus"></i> Créer Fiche
                                        </a>
                                    <?php else: ?>
                                        <a href="../ficherdv/medecin-view.php?id=<?= $rdv['idFiche'] ?>" class="btn-action btn-view-fiche">
                                            <i class="bi bi-eye"></i> Consulter Fiche
                                        </a>
                                    <?php endif; ?>
                                </td>
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
                <div id="pagination-container" class="pagination" style="display:flex; justify-content:center; gap:5px; padding:20px 0;"></div>
            </div>
        </div>
        <div style="margin-top:20px; padding:10px; background:rgba(0,0,0,0.05); border-radius:8px; font-size:11px; color:var(--gray-500);">
            <i class="bi bi-info-circle"></i> Connecté en tant que: <strong><?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?></strong> (ID: <?= $_SESSION['user_id'] ?? '?' ?>) | Résultats: <?= count($rendezvous) ?>
        </div>
    </main>
</div>

<script>
// Client-Side Pagination, Search & Sort
const rowsPerPage = 5;
let currentPage = 1;

function updateView() {
    const table = document.getElementById("rdvTable");
    const tbody = table.querySelector("tbody");
    const allRows = Array.from(tbody.querySelectorAll("tr.rdv-row"));
    
    const input = document.getElementById('dynamicSearch');
    const filter = input.value.toLowerCase();
    
    let visibleRows = [];
    allRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(filter)) {
            visibleRows.push(row);
        }
    });
    
    const totalPages = Math.ceil(visibleRows.length / rowsPerPage) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    
    allRows.forEach(row => row.style.display = 'none');
    visibleRows.forEach((row, index) => {
        if (index >= startIndex && index < endIndex) {
            row.style.display = '';
        }
    });
    
    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const container = document.getElementById('pagination-container');
    container.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    const prev = document.createElement('a');
    prev.innerHTML = '<i class="bi bi-chevron-left"></i>';
    prev.className = `page-link ${currentPage === 1 ? 'disabled' : ''}`;
    prev.style.cursor = 'pointer';
    prev.onclick = (e) => { e.preventDefault(); if(currentPage > 1) { currentPage--; updateView(); } };
    container.appendChild(prev);
    
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('a');
        btn.innerText = i;
        btn.className = `page-link ${currentPage === i ? 'active' : ''}`;
        btn.style.cursor = 'pointer';
        btn.onclick = (e) => { e.preventDefault(); currentPage = i; updateView(); };
        container.appendChild(btn);
    }
    
    const next = document.createElement('a');
    next.innerHTML = '<i class="bi bi-chevron-right"></i>';
    next.className = `page-link ${currentPage === totalPages ? 'disabled' : ''}`;
    next.style.cursor = 'pointer';
    next.onclick = (e) => { e.preventDefault(); if(currentPage < totalPages) { currentPage++; updateView(); } };
    container.appendChild(next);
}

function filterTable() {
    currentPage = 1;
    updateView();
}

let sortDirections = {};
function sortTable(n) {
    const table = document.getElementById("rdvTable");
    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr.rdv-row"));
    
    if (!sortDirections[n]) sortDirections[n] = 1;
    else sortDirections[n] *= -1;
    
    const dir = sortDirections[n];
    
    rows.sort((a, b) => {
        let x = a.cells[n].getAttribute('data-sort');
        let y = b.cells[n].getAttribute('data-sort');
        
        if (x === null || x === undefined) x = a.cells[n].textContent.trim();
        if (y === null || y === undefined) y = b.cells[n].textContent.trim();
        
        let numX = parseFloat(x);
        let numY = parseFloat(y);
        
        if (!isNaN(numX) && !isNaN(numY)) {
            return (numX - numY) * dir;
        }
        
        return x.toLowerCase().localeCompare(y.toLowerCase()) * dir;
    });
    
    rows.forEach(row => tbody.appendChild(row));
    updateView();
}

document.addEventListener('DOMContentLoaded', () => {
    updateView();
});
</script>
</body>
</html>
