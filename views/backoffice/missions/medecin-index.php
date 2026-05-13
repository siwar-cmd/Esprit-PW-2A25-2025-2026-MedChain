<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'],['admin','medecin'])) {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/AmbulanceMissionController.php';
$ctrl = new AmbulanceMissionController();

$page           = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit          = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search         = $_GET['search']      ?? '';
$filterTerminee = $_GET['estTerminee'] ?? '';
$filterType     = $_GET['typeMission'] ?? '';
$filters        = array_filter(['search'=>$search,'estTerminee'=>$filterTerminee,'typeMission'=>$filterType, 'page'=>$page, 'limit'=>$limit],fn($v)=>$v!=='');
$result         = $ctrl->getAllMissions($filters);
$missions       = $result['data'] ?? [];
$totalPages     = $result['pages'] ?? 1;
$stats          = $ctrl->getMissionStats();
$types          = ['Urgence','Transport','Rapatriement','Autre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre des Missions – MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../components/medecin.css">
    <style>
        .badge { padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 600; }
        .badge-red { background: #fee2e2; color: #ef4444; }
        .badge-blue { background: #e0f2fe; color: #0284c7; }
        .badge-orange { background: #ffedd5; color: #f97316; }
        .badge-gray { background: #f1f5f9; color: #64748b; }
        .badge-green { background: #dcfce7; color: #16a34a; }
        
        .filter-bar { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        .filter-bar input, .filter-bar select { padding: 10px 16px; border: 1px solid var(--gray-200); border-radius: 12px; font-size: 14px; background: white; }
        .filter-bar .search-input { flex: 1; min-width: 200px; }
        
        .charts-grid { display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 32px; }
        .chart-container { background: white; padding: 24px; border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); }
        .chart-wrapper { height: 250px; position: relative; }
        
        .readonly-badge { font-size: 11px; background: rgba(29,158,117,0.1); color: var(--green); padding: 4px 10px; border-radius: 20px; font-weight: 600; margin-left: 10px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Registre des Missions</h1>
                <p>Consultation des interventions en temps réel <span class="readonly-badge"><i class="bi bi-eye"></i> Lecture seule</span></p>
            </div>
            <button class="btn btn-secondary" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
            </button>
        </div>

        <div class="charts-grid">
            <div class="chart-container">
                <h4 style="margin-bottom:20px; font-family:Syne; color:var(--navy);"><i class="bi bi-bar-chart-fill" style="color:var(--green);"></i> Performance des Missions</h4>
                <div class="chart-wrapper"><canvas id="missionsChart"></canvas></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Liste des missions <span class="badge badge-green" style="margin-left:10px;"><?= count($missions) ?></span></h2>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-bar">
                    <input type="text" name="search" class="search-input" placeholder="🔍 Rechercher type, lieu, équipe…" value="<?= htmlspecialchars($search) ?>">
                    <select name="typeMission">
                        <option value="">Tous les types</option>
                        <?php foreach($types as $t): ?><option value="<?= $t ?>" <?= $filterType===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?>
                    </select>
                    <select name="estTerminee">
                        <option value="">Tous les statuts</option>
                        <option value="0" <?= $filterTerminee==='0'?'selected':'' ?>>En cours</option>
                        <option value="1" <?= $filterTerminee==='1'?'selected':'' ?>>Terminée</option>
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
                    <?php if($search || $filterType || $filterTerminee !== ''): ?>
                        <a href="medecin-index.php" class="btn btn-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </form>

                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Ambulance</th>
                                <th>Itinéraire</th>
                                <th>Météo</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($missions as $m): 
                                $typeBadge = match($m['typeMission']){'Urgence'=>'badge-red','Transport'=>'badge-blue','Rapatriement'=>'badge-orange',default=>'badge-gray'};
                            ?>
                            <tr>
                                <td><span class="badge <?= $typeBadge ?>"><?= htmlspecialchars($m['typeMission']) ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($m['amb_immatriculation']??'—') ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($m['amb_modele']??'') ?></small>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span><?= htmlspecialchars($m['lieuDepart']) ?></span>
                                        <i class="bi bi-arrow-right text-success"></i>
                                        <span><?= htmlspecialchars($m['lieuArrivee']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if(!empty($m['meteo'])): 
                                        $w = explode('|', $m['meteo']); 
                                    ?>
                                        <div style="display:flex; align-items:center; gap:4px;">
                                            <img src="https://openweathermap.org/img/wn/<?= $w[2] ?>.png" width="24" alt="weather">
                                            <span style="font-size:12px;"><?= $w[0] ?>°C</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $m['dateDebut'] ? date('d/m/Y', strtotime($m['dateDebut'])) : '—' ?></td>
                                <td>
                                    <span class="badge <?= $m['estTerminee'] ? 'badge-green' : 'badge-orange' ?>">
                                        <?= $m['estTerminee'] ? 'Terminée' : 'En cours' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-secondary btn-sm" onclick='showMissionDetails(<?= json_encode($m) ?>)'>
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?= max(1, $page-1) ?>&search=<?= urlencode($search) ?>&typeMission=<?= urlencode($filterType) ?>&estTerminee=<?= $filterTerminee ?>" class="page-link <?= $page<=1?'disabled':'' ?>"><i class="bi bi-chevron-left"></i></a>
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&typeMission=<?= urlencode($filterType) ?>&estTerminee=<?= $filterTerminee ?>" class="page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= min($totalPages, $page+1) ?>&search=<?= urlencode($search) ?>&typeMission=<?= urlencode($filterType) ?>&estTerminee=<?= $filterTerminee ?>" class="page-link <?= $page>=$totalPages?'disabled':'' ?>"><i class="bi bi-chevron-right"></i></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
// --- Chart ---
const ctxM = document.getElementById('missionsChart').getContext('2d');
new Chart(ctxM, {
    type: 'bar',
    data: {
        labels: ['Missions'],
        datasets: [
            { label: 'Terminées', data: [<?= $stats['completed'] ?>], backgroundColor: '#1D9E75', borderRadius: 8 },
            { label: 'En cours', data: [<?= $stats['ongoing'] ?>], backgroundColor: '#F59E0B', borderRadius: 8 }
        ]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
    }
});

function showMissionDetails(m) {
    Swal.fire({
        title: '<span style="font-family:Syne;font-weight:700;">Détails de la Mission</span>',
        html: `
            <div style="text-align:left; font-family:DM Sans; font-size:14px; line-height:1.8; padding: 10px;">
                <div style="margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #eee;">
                    <strong style="color:var(--green);">IDENTIFICATION</strong><br>
                    #ID: ${m.idMission}<br>
                    Type: ${m.typeMission}
                </div>
                <div style="margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #eee;">
                    <strong style="color:var(--green);">LOGISTIQUE</strong><br>
                    Ambulance: ${m.amb_immatriculation || '—'} (${m.amb_modele || ''})<br>
                    Équipe: ${m.equipe || '—'}
                </div>
                <div>
                    <strong style="color:var(--green);">TRAJET</strong><br>
                    Départ: ${m.lieuDepart}<br>
                    Arrivée: ${m.lieuArrivee}<br>
                    Statut: ${m.estTerminee == 1 ? 'Terminée' : 'En cours'}
                </div>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#1D9E75',
        confirmButtonText: 'Fermer'
    });
}
</script>

</body>
</html>
