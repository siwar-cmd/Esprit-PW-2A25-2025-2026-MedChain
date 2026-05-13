<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'],['admin','medecin'])) {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/AmbulanceMissionController.php';
$ctrl = new AmbulanceMissionController();

$page         = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit        = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$search       = $_GET['search']    ?? '';
$filterDispo  = $_GET['disponible']?? '';
$filterStatut = $_GET['statut']    ?? '';
$filters      = array_filter(['search'=>$search,'disponible'=>$filterDispo,'statut'=>$filterStatut, 'page'=>$page, 'limit'=>$limit],fn($v)=>$v!=='');
$result       = $ctrl->getAllAmbulances($filters);
$ambulances   = $result['data'] ?? [];
$totalPages   = $result['pages'] ?? 1;
$stats        = $ctrl->getAmbulanceStats();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Ambulances – Espace Médecin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../components/medecin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .readonly-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(29,158,117,.1);color:var(--green);border:1px solid rgba(29,158,117,.2);padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;}
    .charts-grid{display:grid;grid-template-columns:1fr;gap:20px;margin-bottom:24px;}
    .chart-container{background:var(--white);border-radius:24px;padding:24px;border:1px solid rgba(29,158,117,.1);box-shadow:0 4px 20px rgba(0,0,0,0.03);display:flex;flex-direction:column;align-items:center;}
    .chart-container h4{font-family:'Syne',sans-serif;font-size:15px;margin-bottom:20px;color:var(--navy);align-self:flex-start;display:flex;align-items:center;gap:8px;}
    .chart-container h4 i{color:var(--green);}
    .chart-wrapper{width:100%;max-width:300px;position:relative;}
    .filter-bar{display:flex;gap:12px;padding:16px 24px;background:#F8FAFC;border-bottom:1px solid var(--gray-200);align-items:center;flex-wrap:wrap;}
    .filter-bar input,.filter-bar select{padding:8px 12px;border:1.5px solid var(--gray-200);border-radius:10px;font-size:13.5px;font-family:'DM Sans',sans-serif;outline:none;transition:all .2s;background:#fff;}
    .filter-bar input:focus,.filter-bar select:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(29,158,117,0.1);}
    .filter-bar .search-input{flex:1;min-width:240px;}
    .filter-bar .btn-filter{height:38px;padding:0 16px;border-radius:10px;font-size:13px;}
    .btn-pdf{background:rgba(59,130,246,.1);color:#3B82F6;border:1px solid rgba(59,130,246,.2);}
    .btn-pdf:hover{background:#3B82F6;color:#fff;}
    .btn-edit{background:rgba(29,158,117,.1);color:var(--green);border:1px solid rgba(29,158,117,.2);}
    .btn-edit:hover{background:var(--green);color:#fff;}
    .btn-sm{padding:6px 12px;font-size:12px;}
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;">
            <div>
                <h1><i class="bi bi-truck-front-fill" style="color:var(--green);font-size:24px;"></i> Flotte d'Ambulances</h1>
                <p>Vue médecin – consultation uniquement &nbsp;<span class="readonly-badge"><i class="bi bi-eye"></i> Lecture seule</span></p>
            </div>
            <button class="btn btn-pdf" onclick="window.print()"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
        </div>

        <div class="charts-grid">
            <div class="chart-container">
                <h4><i class="bi bi-pie-chart-fill"></i> Disponibilité Flotte</h4>
                <div class="chart-wrapper"><canvas id="flotteChart"></canvas></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-list-ul"></i> Liste des ambulances <span style="background:#DCFCE7;color:#16A34A;padding:3px 10px;border-radius:20px;font-size:13px;"><?= count($ambulances) ?></span></h2>
            </div>

            <form method="GET" class="filter-bar">
                <input type="text" name="search" class="search-input" placeholder="🔍  Rechercher…" value="<?= htmlspecialchars($search) ?>">
                <select name="statut">
                    <option value="">Tous les statuts</option>
                    <?php foreach(['En service','Hors service','En maintenance'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filterStatut===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="disponible">
                    <option value="">Disponibilité</option>
                    <option value="1" <?= $filterDispo==='1'?'selected':'' ?>>Disponible</option>
                    <option value="0" <?= $filterDispo==='0'?'selected':'' ?>>Indisponible</option>
                </select>
                <button type="submit" class="btn btn-primary btn-filter"><i class="bi bi-funnel"></i> Filtrer</button>
                <?php if($search||$filterStatut||$filterDispo!==''): ?>
                <a href="medecin-index.php" class="btn btn-outline btn-filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </form>

            <div class="card-body" style="padding:0;">
                <?php if(empty($ambulances)): ?>
                <div class="empty-state"><i class="bi bi-truck-front"></i><p>Aucune ambulance trouvée</p></div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead><tr><th>#</th><th>Immatriculation</th><th>Modèle</th><th>Statut</th><th>Capacité</th><th>Disponibilité</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach($ambulances as $a): ?>
                        <tr>
                            <td><strong>#<?= (int)$a['idAmbulance'] ?></strong></td>
                            <td><strong><?= htmlspecialchars($a['immatriculation']) ?></strong></td>
                            <td><?= htmlspecialchars($a['modele']) ?></td>
                            <td><?php $sc=match($a['statut']){'En service'=>'badge-green','Hors service'=>'badge-red',default=>'badge-gray'}; ?><span class="badge <?= $sc ?>"><?= htmlspecialchars($a['statut']) ?></span></td>
                            <td><?= (int)$a['capacite'] ?> pers.</td>
                            <td><span class="badge <?= $a['estDisponible']?'badge-green':'badge-red' ?>"><?= $a['estDisponible']?'Disponible':'Indisponible' ?></span></td>
                            <td>
                                <button class="btn btn-edit btn-sm" onclick='showAmbulanceDetails(<?= json_encode($a) ?>)'>
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?= max(1, $page-1) ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($filterStatut) ?>&disponible=<?= $filterDispo ?>" 
                       class="btn btn-outline btn-sm <?= $page<=1?'disabled':'' ?>"><i class="bi bi-chevron-left"></i></a>
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($filterStatut) ?>&disponible=<?= $filterDispo ?>" 
                           class="btn btn-sm <?= $i==$page?'btn-primary':'btn-outline' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= min($totalPages, $page+1) ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($filterStatut) ?>&disponible=<?= $filterDispo ?>" 
                       class="btn btn-outline btn-sm <?= $page>=$totalPages?'disabled':'' ?>"><i class="bi bi-chevron-right"></i></a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
const ctxF = document.getElementById('flotteChart').getContext('2d');
new Chart(ctxF, {
    type: 'doughnut',
    data: {
        labels: ['Disponibles', 'Indisponibles'],
        datasets: [{
            data: [<?= $stats['available'] ?>, <?= $stats['total'] - $stats['available'] ?>],
            backgroundColor: ['#1D9E75', '#F43F5E'],
            borderWidth: 0
        }]
    },
    options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
});

function showAmbulanceDetails(a) {
    Swal.fire({
        title: '<span style="font-family:Syne;font-weight:700;">Détails Ambulance</span>',
        html: `
            <div style="text-align:left; font-family:DM Sans; font-size:14px; line-height:1.6;">
                <p><strong>Immatriculation:</strong> ${a.immatriculation}</p>
                <p><strong>Modèle:</strong> ${a.modele}</p>
                <p><strong>Statut:</strong> ${a.statut}</p>
                <p><strong>Capacité:</strong> ${a.capacite} personne(s)</p>
                <p><strong>Disponibilité:</strong> ${a.estDisponible == 1 ? '<span style="color:#16A34A;">Disponible</span>' : '<span style="color:#EF4444;">Indisponible</span>'}</p>
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
