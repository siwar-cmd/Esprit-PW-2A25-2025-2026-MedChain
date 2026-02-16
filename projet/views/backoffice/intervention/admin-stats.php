<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header('Location: ../../frontoffice/auth/login.php'); exit; }
require_once __DIR__ . '/../../../controllers/InterventionController.php';
$ctrl = new InterventionController();
$stats = $ctrl->getStatistics();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Interventions - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#fff;--radius-md:12px;--radius-lg:20px;--radius-xl:28px;--shadow-sm:0 1px 3px rgba(0,0,0,.08)}
        body{font-family:'DM Sans',sans-serif;background:linear-gradient(145deg,#f0faf6,#e8f7f1,#ddf3ea);min-height:100vh}
        .dashboard-container{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
        .dashboard-sidebar{background:linear-gradient(180deg,var(--navy),#0F172A);position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto}
        .dashboard-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:20px}
        .dashboard-logo a{display:flex;align-items:center;gap:10px;text-decoration:none}
        .dashboard-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center}
        .dashboard-logo-icon i{font-size:18px;color:white}
        .dashboard-logo-text{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:white}
        .dashboard-logo-text span{color:var(--green)}
        .dashboard-nav{flex:1;display:flex;flex-direction:column;gap:4px;padding:0 12px}
        .dashboard-nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#94A3B8;text-decoration:none;border-radius:var(--radius-md);transition:all .3s;font-size:14px;font-weight:500}
        .dashboard-nav-item i{font-size:18px;width:24px}
        .dashboard-nav-item:hover{background:rgba(255,255,255,0.1);color:white}
        .dashboard-nav-item.active{background:rgba(29,158,117,0.2);color:var(--green)}
        .dashboard-nav-item.logout{margin-top:auto;margin-bottom:20px;color:#F87171}
        .dashboard-nav-title{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748B;padding:16px 16px 8px;font-weight:600}
        .nav-dropdown{position:relative}.nav-dropdown-menu{display:none;flex-direction:column;gap:2px;padding:4px 0 4px 20px}.nav-dropdown:hover .nav-dropdown-menu{display:flex}.nav-dropdown-menu a{font-size:13px;padding:8px 16px}
        .dashboard-main{padding:32px 40px;overflow-y:auto}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px}
        .page-header h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:var(--navy)}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px}
        .stat-card{background:var(--white);border-radius:var(--radius-lg);padding:24px;display:flex;align-items:center;gap:16px;border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm)}
        .stat-icon{width:52px;height:52px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:24px}
        .stat-icon.primary{background:rgba(29,158,117,0.1);color:var(--green)}
        .stat-icon.warning{background:rgba(245,158,11,0.1);color:#F59E0B}
        .stat-icon.danger{background:rgba(239,68,68,0.1);color:#EF4444}
        .stat-icon.info{background:rgba(59,130,246,0.1);color:#3B82F6}
        .stat-content h3{font-size:28px;font-weight:700;color:var(--navy)}
        .stat-content p{font-size:13px;color:var(--gray-500)}
        .charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px}
        .chart-card{background:var(--white);border-radius:var(--radius-lg);padding:24px;border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm)}
        .chart-card h3{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--navy);margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .chart-card h3 i{color:var(--green)}
        .btn{padding:10px 20px;border-radius:var(--radius-md);font-size:14px;font-weight:600;cursor:pointer;transition:all .3s;border:none;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .btn-outline{background:transparent;border:2px solid var(--gray-200);color:var(--navy)}
        .btn-outline:hover{border-color:var(--green);color:var(--green)}
        @media(max-width:1024px){.dashboard-container{grid-template-columns:1fr}.dashboard-sidebar{display:none}.charts-grid{grid-template-columns:1fr}}
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
            <div class="nav-dropdown"><a href="#" class="dashboard-nav-item active"><i class="bi bi-hospital"></i> Bloc Opératoire <i class="bi bi-chevron-down" style="font-size:12px;margin-left:auto"></i></a><div class="nav-dropdown-menu"><a href="admin-index.php" class="dashboard-nav-item active"><i class="bi bi-heart-pulse"></i> Interventions</a><a href="../materiel/admin-index.php" class="dashboard-nav-item"><i class="bi bi-tools"></i> Matériel</a></div></div>
            <a href="../rendezvous/admin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
            <div class="dashboard-nav-title">Gestion</div>
            <a href="../../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <div class="page-header">
            <div><h1><i class="bi bi-graph-up" style="color:var(--green)"></i> Statistiques Interventions</h1></div>
            <a href="admin-index.php" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Retour</a>
        </div>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon primary"><i class="bi bi-heart-pulse-fill"></i></div><div class="stat-content"><h3><?=$stats['total']??0?></h3><p>Total interventions</p></div></div>
            <div class="stat-card"><div class="stat-icon info"><i class="bi bi-clock-fill"></i></div><div class="stat-content"><h3><?=$stats['avg_duree']??0?> min</h3><p>Durée moyenne</p></div></div>
            <div class="stat-card"><div class="stat-icon danger"><i class="bi bi-x-circle-fill"></i></div><div class="stat-content"><h3><?=$stats['total_annulees']??0?></h3><p>Annulées</p></div></div>
            <div class="stat-card"><div class="stat-icon warning"><i class="bi bi-list-check"></i></div><div class="stat-content"><h3><?=count($stats['by_type']??[])?></h3><p>Types différents</p></div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card"><h3><i class="bi bi-pie-chart"></i> Par Type</h3><canvas id="chartType"></canvas></div>
            <div class="chart-card"><h3><i class="bi bi-bar-chart"></i> Par Urgence</h3><canvas id="chartUrgence"></canvas></div>
            <div class="chart-card" style="grid-column:span 2"><h3><i class="bi bi-graph-up-arrow"></i> Par Mois</h3><canvas id="chartMonth"></canvas></div>
        </div>
    </main>
</div>
<script>
const colors = ['#1D9E75','#0F6E56','#3B82F6','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6'];
// Chart by Type
new Chart(document.getElementById('chartType'),{type:'doughnut',data:{labels:<?=json_encode(array_column($stats['by_type']??[],'type'))?>,datasets:[{data:<?=json_encode(array_column($stats['by_type']??[],'count'))?>,backgroundColor:colors}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
// Chart by Urgence
const urgLabels = {1:'Faible',2:'Modéré',3:'Élevé',4:'Urgent',5:'Critique'};
const urgData = <?=json_encode($stats['by_urgence']??[])?>;
new Chart(document.getElementById('chartUrgence'),{type:'bar',data:{labels:urgData.map(u=>urgLabels[u.niveau_urgence]||u.niveau_urgence),datasets:[{label:'Interventions',data:urgData.map(u=>u.count),backgroundColor:['#22C55E','#F59E0B','#EA580C','#EF4444','#B91C1C']}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
// Chart by Month
const monthData = <?=json_encode($stats['by_month']??[])?>;
new Chart(document.getElementById('chartMonth'),{type:'line',data:{labels:monthData.map(m=>m.mois),datasets:[{label:'Interventions',data:monthData.map(m=>m.count),borderColor:'#1D9E75',backgroundColor:'rgba(29,158,117,0.1)',fill:true,tension:0.4}]},options:{responsive:true,scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
</script>
</body>
</html>
