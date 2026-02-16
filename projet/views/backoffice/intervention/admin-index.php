<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}
require_once __DIR__ . '/../../../controllers/InterventionController.php';
$ctrl = new InterventionController();

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

if (!empty($search)) {
    $interventions = $ctrl->searchInterventions($search);
} else {
    $interventions = $ctrl->getAllSorted($sort, $order);
}

$stats = $ctrl->getStatistics();
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interventions - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--green:#1D9E75;--green-dark:#0F6E56;--green-deep:#094D3C;--green-light:#E8F7F2;--navy:#1E3A52;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#fff;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 16px rgba(0,0,0,.08);--shadow-lg:0 12px 40px rgba(0,0,0,.10);--shadow-green:0 8px 30px rgba(29,158,117,.22);--radius-md:12px;--radius-lg:20px;--radius-xl:28px}
        body{font-family:'DM Sans',sans-serif;background:linear-gradient(145deg,#f0faf6 0%,#e8f7f1 50%,#ddf3ea 100%);min-height:100vh;overflow-x:hidden}
        .dashboard-container{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
        .dashboard-sidebar{background:linear-gradient(180deg,var(--navy) 0%,#0F172A 100%);position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto}
        .dashboard-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:20px}
        .dashboard-logo a{display:flex;align-items:center;gap:10px;text-decoration:none}
        .dashboard-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center}
        .dashboard-logo-icon i{font-size:18px;color:white}
        .dashboard-logo-text{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:white}
        .dashboard-logo-text span{color:var(--green)}
        .dashboard-nav{flex:1;display:flex;flex-direction:column;gap:4px;padding:0 12px}
        .dashboard-nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#94A3B8;text-decoration:none;border-radius:var(--radius-md);transition:all 0.3s;font-size:14px;font-weight:500}
        .dashboard-nav-item i{font-size:18px;width:24px}
        .dashboard-nav-item:hover{background:rgba(255,255,255,0.1);color:white}
        .dashboard-nav-item.active{background:rgba(29,158,117,0.2);color:var(--green)}
        .dashboard-nav-item.logout{margin-top:auto;margin-bottom:20px;color:#F87171}
        .dashboard-nav-item.logout:hover{background:rgba(248,113,113,0.1)}
        .dashboard-nav-title{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748B;padding:16px 16px 8px;font-weight:600}
        .nav-dropdown{position:relative}
        .nav-dropdown-menu{display:none;flex-direction:column;gap:2px;padding:4px 0 4px 20px}
        .nav-dropdown:hover .nav-dropdown-menu{display:flex}
        .nav-dropdown-menu a{font-size:13px;padding:8px 16px}
        .dashboard-main{padding:32px 40px;overflow-y:auto}
        .dashboard-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;flex-wrap:wrap;gap:16px}
        .dashboard-header h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:var(--navy)}
        .dashboard-header p{color:var(--gray-500);font-size:14px}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:32px}
        .stat-card{background:var(--white);border-radius:var(--radius-lg);padding:20px;display:flex;align-items:center;gap:16px;border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);transition:all 0.3s}
        .stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
        .stat-icon{width:48px;height:48px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:22px}
        .stat-icon.primary{background:rgba(29,158,117,0.1);color:var(--green)}
        .stat-icon.warning{background:rgba(245,158,11,0.1);color:#F59E0B}
        .stat-icon.danger{background:rgba(239,68,68,0.1);color:#EF4444}
        .stat-icon.info{background:rgba(59,130,246,0.1);color:#3B82F6}
        .stat-content h3{font-size:24px;font-weight:700;color:var(--navy)}
        .stat-content p{font-size:12px;color:var(--gray-500)}
        .card{background:var(--white);border-radius:var(--radius-xl);border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:32px}
        .card-header{padding:20px 24px;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px}
        .card-header h2{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:10px}
        .card-header h2 i{color:var(--green)}
        .card-body{padding:24px}
        .toolbar{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
        .search-box{position:relative}
        .search-box input{padding:10px 16px 10px 40px;border:2px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;width:280px;transition:border-color .3s;font-family:'DM Sans',sans-serif}
        .search-box input:focus{outline:none;border-color:var(--green)}
        .search-box i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--gray-500)}
        .table-responsive{overflow-x:auto}
        .table{width:100%;border-collapse:collapse}
        .table th{background:#F8FAFC;padding:12px 16px;text-align:left;font-weight:600;color:#64748B;border-bottom:1px solid var(--gray-200);cursor:pointer;white-space:nowrap}
        .table th:hover{color:var(--green)}
        .table th a{color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px}
        .table td{padding:14px 16px;border-bottom:1px solid var(--gray-200);vertical-align:middle}
        .table tr:hover{background:#F8FAFC}
        .btn{padding:8px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:600;cursor:pointer;transition:all 0.3s;border:none;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
        .btn-primary{background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;box-shadow:0 3px 12px rgba(29,158,117,.30)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(29,158,117,.40)}
        .btn-warning{background:#F59E0B;color:white}
        .btn-warning:hover{background:#D97706}
        .btn-danger{background:#EF4444;color:white}
        .btn-danger:hover{background:#DC2626}
        .btn-outline{background:transparent;border:2px solid var(--gray-200);color:var(--navy)}
        .btn-outline:hover{border-color:var(--green);color:var(--green)}
        .btn-info{background:#3B82F6;color:white}
        .btn-info:hover{background:#2563EB}
        .btn-sm{padding:6px 12px;font-size:12px}
        .actions-cell{display:flex;gap:6px}
        .urgence-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
        .urgence-1{background:#F0FDF4;color:#22C55E}
        .urgence-2{background:#FEF9C3;color:#CA8A04}
        .urgence-3{background:#FFF7ED;color:#EA580C}
        .urgence-4{background:#FEF2F2;color:#EF4444}
        .urgence-5{background:#FEE2E2;color:#B91C1C}
        .alert{padding:14px 18px;border-radius:var(--radius-md);margin-bottom:24px;display:flex;align-items:center;gap:12px;animation:slideIn 0.3s ease}
        @keyframes slideIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        .alert-success{background:#F0FDF4;border-left:4px solid #22C55E;color:#166534}
        .alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C}
        .alert-close{margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;opacity:0.6}
        .charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px}
        .chart-card{background:var(--white);border-radius:var(--radius-lg);padding:24px;border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm)}
        .chart-card h3{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--navy);margin-bottom:16px}
        @media(max-width:1024px){.dashboard-container{grid-template-columns:1fr}.dashboard-sidebar{display:none}.charts-grid{grid-template-columns:1fr}}
        @media(max-width:768px){.dashboard-main{padding:20px}.toolbar{flex-direction:column}.search-box input{width:100%}}
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="dashboard-logo"><a href="../admin-dashboard.php"><div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div><div class="dashboard-logo-text">Med<span>Chain</span></div></a></div>
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="../admin-dashboard.php" class="dashboard-nav-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="../admin-users.php" class="dashboard-nav-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
            <div class="nav-dropdown">
                <a href="#" class="dashboard-nav-item active"><i class="bi bi-hospital"></i> Bloc Opératoire <i class="bi bi-chevron-down" style="font-size:12px;margin-left:auto"></i></a>
                <div class="nav-dropdown-menu">
                    <a href="admin-index.php" class="dashboard-nav-item active"><i class="bi bi-heart-pulse"></i> Interventions</a>
                    <a href="../materiel/admin-index.php" class="dashboard-nav-item"><i class="bi bi-tools"></i> Matériel</a>
                </div>
            </div>
            <a href="../rendezvous/admin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
            <a href="../ficherdv/admin-index.php" class="dashboard-nav-item"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
            <div class="dashboard-nav-title">Gestion</div>
            <a href="../../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div><h1>Gestion des Interventions</h1><p>Bloc Opératoire — Admin</p></div>
            <div style="display:flex;gap:10px">
                <a href="admin-export-pdf.php" class="btn btn-outline"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
                <a href="admin-stats.php" class="btn btn-info"><i class="bi bi-graph-up"></i> Statistiques</a>
                <a href="admin-add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter</a>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success_message) ?></div><button class="alert-close">&times;</button></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i><div><?= htmlspecialchars($error_message) ?></div><button class="alert-close">&times;</button></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon primary"><i class="bi bi-heart-pulse-fill"></i></div><div class="stat-content"><h3><?= $stats['total'] ?? 0 ?></h3><p>Total interventions</p></div></div>
            <div class="stat-card"><div class="stat-icon info"><i class="bi bi-clock-fill"></i></div><div class="stat-content"><h3><?= $stats['avg_duree'] ?? 0 ?> min</h3><p>Durée moyenne</p></div></div>
            <div class="stat-card"><div class="stat-icon danger"><i class="bi bi-x-circle-fill"></i></div><div class="stat-content"><h3><?= $stats['total_annulees'] ?? 0 ?></h3><p>Annulées</p></div></div>
            <div class="stat-card"><div class="stat-icon warning"><i class="bi bi-exclamation-triangle-fill"></i></div><div class="stat-content"><h3><?= count($stats['by_type'] ?? []) ?></h3><p>Types différents</p></div></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-table"></i> Liste des Interventions</h2>
                <div class="toolbar">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($interventions)): ?>
                    <div style="text-align:center;padding:48px"><i class="bi bi-heart-pulse" style="font-size:48px;opacity:0.3"></i><p style="margin-top:16px">Aucune intervention trouvée</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="interventionsTable">
                        <thead><tr>
                            <th><a href="?sort=id&order=<?= $sort==='id'&&$order==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search) ?>">ID <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?sort=type&order=<?= $sort==='type'&&$order==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search) ?>">Type <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?sort=date_intervention&order=<?= $sort==='date_intervention'&&$order==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search) ?>">Date <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?sort=duree&order=<?= $sort==='duree'&&$order==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search) ?>">Durée <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?sort=niveau_urgence&order=<?= $sort==='niveau_urgence'&&$order==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search) ?>">Urgence <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?sort=chirurgien&order=<?= $sort==='chirurgien'&&$order==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search) ?>">Chirurgien <i class="bi bi-arrow-down-up"></i></a></th>
                            <th>Salle</th>
                            <th>Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($interventions as $interv): ?>
                            <tr>
                                <td><?= $interv['id'] ?></td>
                                <td><strong><?= htmlspecialchars($interv['type']) ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($interv['date_intervention'])) ?></td>
                                <td><?= $interv['duree'] ?> min</td>
                                <td><span class="urgence-badge urgence-<?= $interv['niveau_urgence'] ?>"><?php
                                    $labels = [1=>'Faible',2=>'Modéré',3=>'Élevé',4=>'Urgent',5=>'Critique'];
                                    echo $labels[$interv['niveau_urgence']] ?? $interv['niveau_urgence'];
                                ?></span></td>
                                <td><?= htmlspecialchars($interv['chirurgien']) ?></td>
                                <td><?= htmlspecialchars($interv['salle'] ?? '-') ?></td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="admin-edit.php?id=<?= $interv['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                                        <a href="admin-delete.php?id=<?= $interv['id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script>
document.getElementById('searchInput').addEventListener('input', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#interventionsTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});
document.querySelectorAll('.alert-close').forEach(btn => btn.addEventListener('click', () => btn.closest('.alert').remove()));
setTimeout(() => document.querySelectorAll('.alert').forEach(a => {a.style.opacity='0';a.style.transition='all .3s';setTimeout(()=>a.remove(),300)}), 5000);
</script>
</body>
</html>
