<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php'); exit;
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
    header("Location: medecin-index.php"); exit;
}

$ficheData = $ficheController->getAllFiches($filters, 'medecin', $userId);
$fiches = $ficheData['success'] ? $ficheData['fiches'] : [];
$stats = $ficheController->getStats('medecin', $userId);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mes Fiches Médicales – Espace Médecin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../components/medecin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .dashboard-main { padding: 30px; }
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:32px;}
    .stat-card{background:var(--white);border-radius:var(--radius-lg);padding:20px;display:flex;align-items:center;gap:16px;box-shadow:var(--shadow-sm);border:1px solid rgba(29,158,117,.15);position:relative;overflow:hidden;}
    .stat-card::after{content:'';position:absolute;top:0;right:0;width:100px;height:100%;background:linear-gradient(90deg,transparent,rgba(29,158,117,0.03));}
    .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;background:rgba(29,158,117,0.1);color:var(--green);flex-shrink:0;}
    .stat-content h3{font-size:24px;font-weight:700;color:var(--navy);margin-bottom:2px;}
    .stat-content p{font-size:12px;color:var(--gray-500);margin:0;font-weight:500;}
    
    .chart-container{background:white;border-radius:var(--radius-lg);padding:25px;box-shadow:var(--shadow-sm);border:1px solid rgba(29,158,117,.15);margin-bottom:32px;}
    
    .card{background:white;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid rgba(29,158,117,.1);overflow:hidden;}
    .card-header{padding:20px 25px;border-bottom:1px solid rgba(29,158,117,.1);display:flex;justify-content:space-between;align-items:center;background:#fafdfc;}
    .card-header h2{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--navy);margin:0;}
    
    .search-group{position:relative;width:300px;}
    .search-group i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-500);font-size:14px;}
    .search-input{width:100%;padding:10px 15px 10px 38px;border-radius:10px;border:1px solid var(--gray-200);outline:none;font-size:14px;transition:all 0.2s;}
    .search-input:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(29,158,117,0.1);}
    
    .table{width:100%;border-collapse:separate;border-spacing:0;}
    .table th{background:#f8fafc;padding:15px 20px;font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid var(--gray-200);cursor:pointer;transition:background 0.2s;}
    .table th:hover{background:#f1f5f9;}
    .table td{padding:16px 20px;font-size:14px;color:var(--navy);border-bottom:1px solid #f1f5f9;vertical-align:middle;}
    .table tr:hover td{background:#fafdfc;}
    
    .action-icons{display:flex;gap:10px;justify-content:center;}
    .action-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:all 0.25s;border:none;cursor:pointer;}
    .btn-view{background:rgba(29,158,117,0.1);color:var(--green);}
    .btn-view:hover{background:var(--green);color:white;transform:translateY(-2px);box-shadow:0 4px 12px rgba(29,158,117,0.25);}
    .btn-edit{background:rgba(2,132,199,0.1);color:#0284C7;}
    .btn-edit:hover{background:#0284C7;color:white;transform:translateY(-2px);box-shadow:0 4px 12px rgba(2,132,199,0.25);}
    .btn-delete{background:rgba(239,68,68,0.1);color:#EF4444;}
    .btn-delete:hover{background:#EF4444;color:white;transform:translateY(-2px);box-shadow:0 4px 12px rgba(239,68,68,0.25);}
    
    .badge-consultation{background:rgba(2,132,199,0.1);color:#0284C7;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;}
    
    .sort-select{appearance:none; padding:10px 35px 10px 15px; border-radius:10px; border:1px solid var(--gray-200); background:#f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat calc(100% - 12px) center; color:var(--navy); font-weight:600; font-size:14px; outline:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 4px rgba(0,0,0,0.02);}
    .sort-select:hover{border-color:var(--green); background-color:white; box-shadow:0 2px 8px rgba(29,158,117,0.1);}
    
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .modal-content { background: white; border-radius: var(--radius-lg); padding: 30px; width: 90%; max-width: 800px; position: relative; transform: translateY(-20px); transition: transform 0.3s; box-shadow: var(--shadow-lg); }
    .modal-overlay.active .modal-content { transform: translateY(0); }
    .modal-close { position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: var(--gray-500); }
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Mes Fiches Médicales</h1>
                <p>Historique complet</p>
            </div>
            <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
        </div>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div style="background:#DCFCE7;color:#16A34A;padding:15px;border-radius:8px;margin-bottom:20px;"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-file-earmark-medical"></i></div>
                <div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total Fiches</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(2,132,199,0.1); color: #0284C7;"><i class="bi bi-calendar-check"></i></div>
                <div class="stat-content"><h3><?= $stats['ce_mois'] ?></h3><p>Fiches ce mois</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(217,119,6,0.1); color: #D97706;"><i class="bi bi-send-check"></i></div>
                <div class="stat-content"><h3><?= $stats['emails_sent'] ?></h3><p>Emails envoyés</p></div>
            </div>
        </div>

        <button onclick="openStatsModal()" class="btn btn-primary" style="margin-bottom: 20px; background: var(--green); border: none; padding: 12px 24px; border-radius: var(--radius-md); color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-bar-chart-fill"></i> Afficher les Statistiques
        </button>

        <div class="card">
            <div class="card-header">
                <h2>Répertoire des Fiches Médicales</h2>
                <div style="display:flex; gap:15px; align-items:center;">
                    <select class="sort-select" onchange="if(this.value !== '') sortTable(parseInt(this.value))" style="height: 42px;">
                        <option value="">Trier par...</option>
                        <option value="0">ID</option>
                        <option value="1">Type RDV</option>
                        <option value="2">Date RDV</option>
                        <option value="3">Patient</option>
                        <option value="4">Tarif</option>
                    </select>
                    <div class="search-group">
                        <i class="bi bi-search"></i>
                        <input type="text" id="dynamicSearch" class="search-input" placeholder="Rechercher un patient ou un motif..." onkeyup="filterTable()">
                    </div>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="table" id="ficheTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable(0)">ID <i class="bi bi-sort-alpha-down ms-1"></i></th>
                                <th onclick="sortTable(1)">Type RDV <i class="bi bi-sort-alpha-down ms-1"></i></th>
                                <th onclick="sortTable(2)">Date RDV <i class="bi bi-sort-alpha-down ms-1"></i></th>
                                <th onclick="sortTable(3)">Patient <i class="bi bi-sort-alpha-down ms-1"></i></th>
                                <th onclick="sortTable(4)">Tarif <i class="bi bi-sort-alpha-down ms-1"></i></th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                            <?php foreach($fiches as $fiche): ?>
                            <tr class="fiche-row">
                                <td class="fw-bold text-success" data-sort="<?= $fiche['idFiche'] ?>">#<?= $fiche['idFiche'] ?></td>
                                <td><span class="badge-consultation"><?= htmlspecialchars($fiche['typeConsultation']) ?></span></td>
                                <td data-sort="<?= strtotime($fiche['dateHeureDebut']) ?>"><?= date('d/m/Y H:i', strtotime($fiche['dateHeureDebut'])) ?></td>
                                <td><?= htmlspecialchars($fiche['patient_nom'] . ' ' . $fiche['patient_prenom']) ?></td>
                                <td data-sort="<?= $fiche['tarifConsultation'] ?: 0 ?>"><?= $fiche['tarifConsultation'] ? $fiche['tarifConsultation'] . ' TND' : '0 TND' ?></td>
                                <td>
                                    <div class="action-icons">
                                        <a href="medecin-view.php?id=<?= $fiche['idFiche'] ?>" class="action-btn btn-view" title="Afficher">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <a href="medecin-edit.php?id=<?= $fiche['idFiche'] ?>" class="action-btn btn-edit" title="Modifier">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirmSwal(event, this, 'Supprimer ?', 'Supprimer définitivement cette fiche ?')">
                                            <input type="hidden" name="delete_id" value="<?= $fiche['idFiche'] ?>">
                                            <button type="submit" class="action-btn btn-delete" title="Supprimer">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </form>
                                        <?php if(isset($fiche['modeConsultation']) && $fiche['modeConsultation'] === 'Téléconsultation'): ?>
                                            <a href="https://meet.jit.si/MedChain_Consultation_<?= $fiche['idFiche'] ?>" target="_blank" class="action-btn" style="background:rgba(29, 158, 117, 0.15); color:var(--green-dark);" title="Rejoindre la téléconsultation">
                                                <i class="bi bi-camera-video-fill"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($fiches)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding:40px; color:var(--gray-500);">
                                    Aucune fiche trouvée.
                                    <?php if(!$ficheData['success']): ?>
                                        <br><small style="color:#EF4444;">Erreur: <?= htmlspecialchars($ficheData['message'] ?? 'Inconnue') ?></small>
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
            <i class="bi bi-info-circle"></i> Connecté en tant que: <strong><?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?></strong> (ID: <?= $_SESSION['user_id'] ?? '?' ?>) | Résultats: <?= count($fiches) ?>
        </div>
    </main>
</div>

<div class="modal-overlay" id="statsModal" onclick="if(event.target===this) closeStatsModal()">
    <div class="modal-content">
        <button class="modal-close" onclick="closeStatsModal()">&times;</button>
        <h2 style="font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--navy);margin-bottom:20px;"><i class="bi bi-graph-up text-success me-2"></i>Activité des Fiches (6 derniers mois)</h2>
        <div style="height: 300px;">
            <canvas id="ficheChart"></canvas>
        </div>
    </div>
</div>

<script>
function openStatsModal() {
    document.getElementById('statsModal').classList.add('active');
    if (window.ficheChartInstance) window.ficheChartInstance.resize();
}
function closeStatsModal() {
    document.getElementById('statsModal').classList.remove('active');
}

// Chart.js implementation
const ctx = document.getElementById('ficheChart').getContext('2d');
window.ficheChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php foreach($stats['monthly'] as $m) echo "'".$m['month']."',"; ?>],
        datasets: [{
            label: 'Nombre de fiches',
            data: [<?php foreach($stats['monthly'] as $m) echo $m['count'].","; ?>],
            borderColor: '#1D9E75',
            backgroundColor: 'rgba(29, 158, 117, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#1D9E75',
            pointBorderColor: '#fff',
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// Client-Side Pagination, Search & Sort
const rowsPerPage = 5;
let currentPage = 1;

function updateView() {
    const table = document.getElementById("ficheTable");
    const tbody = table.querySelector("tbody");
    const allRows = Array.from(tbody.querySelectorAll("tr.fiche-row"));
    
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
    const table = document.getElementById("ficheTable");
    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr.fiche-row"));
    
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
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
