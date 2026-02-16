<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') { header('Location: ../../frontoffice/auth/login.php'); exit; }
require_once __DIR__ . '/../../../controllers/InterventionController.php';
$ctrl = new InterventionController();
$interventions = $ctrl->getInterventionsWithAnnulations();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interventions - Médecin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#fff;--radius-md:12px;--radius-lg:20px;--radius-xl:28px;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 16px rgba(0,0,0,.08)}
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
        .page-header{margin-bottom:32px}
        .page-header h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:var(--navy)}
        .page-header p{color:var(--gray-500);font-size:14px}
        .card{background:var(--white);border-radius:var(--radius-xl);border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:32px}
        .card-header{padding:20px 24px;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center}
        .card-header h2{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:10px}
        .card-header h2 i{color:var(--green)}
        .card-body{padding:24px}
        .search-box{position:relative}
        .search-box input{padding:10px 16px 10px 40px;border:2px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;width:280px;transition:border-color .3s;font-family:'DM Sans',sans-serif}
        .search-box input:focus{outline:none;border-color:var(--green)}
        .search-box i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--gray-500)}
        .table-responsive{overflow-x:auto}
        .table{width:100%;border-collapse:collapse}
        .table th{background:#F8FAFC;padding:12px 16px;text-align:left;font-weight:600;color:#64748B;border-bottom:1px solid var(--gray-200)}
        .table td{padding:14px 16px;border-bottom:1px solid var(--gray-200);vertical-align:middle}
        .table tr:hover{background:#F8FAFC}
        .urgence-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
        .urgence-1{background:#F0FDF4;color:#22C55E}.urgence-2{background:#FEF9C3;color:#CA8A04}.urgence-3{background:#FFF7ED;color:#EA580C}.urgence-4{background:#FEF2F2;color:#EF4444}.urgence-5{background:#FEE2E2;color:#B91C1C}
        .status-annulee{color:#EF4444;font-weight:600;font-size:12px}
        .info-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#F0FDF4;color:#22C55E}
        @media(max-width:1024px){.dashboard-container{grid-template-columns:1fr}.dashboard-sidebar{display:none}}
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="dashboard-logo"><a href="../rendezvous/medecin-index.php"><div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div><div class="dashboard-logo-text">Med<span>Chain</span></div></a></div>
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="../rendezvous/medecin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
            <a href="../ficherdv/medecin-index.php" class="dashboard-nav-item"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
            <div class="nav-dropdown">
                <a href="#" class="dashboard-nav-item active"><i class="bi bi-hospital"></i> Bloc Opératoire <i class="bi bi-chevron-down" style="font-size:12px;margin-left:auto"></i></a>
                <div class="nav-dropdown-menu">
                    <a href="medecin-index.php" class="dashboard-nav-item active"><i class="bi bi-heart-pulse"></i> Interventions</a>
                    <a href="../materiel/medecin-index.php" class="dashboard-nav-item"><i class="bi bi-tools"></i> Matériel</a>
                </div>
            </div>
            <div class="dashboard-nav-title">Gestion</div>
            <a href="../../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <div class="page-header"><h1>Interventions</h1><p>Bloc Opératoire — Consultation (Médecin)</p></div>
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-table"></i> Liste des Interventions</h2>
                <div class="search-box"><i class="bi bi-search"></i><input type="text" id="searchInput" placeholder="Rechercher..."></div>
            </div>
            <div class="card-body">
                <?php if(empty($interventions)):?>
                    <div style="text-align:center;padding:48px"><i class="bi bi-heart-pulse" style="font-size:48px;opacity:0.3"></i><p style="margin-top:16px">Aucune intervention</p></div>
                <?php else:?>
                <div class="table-responsive">
                    <table class="table" id="tbl">
                        <thead><tr><th>ID</th><th>Type</th><th>Date</th><th>Durée</th><th>Urgence</th><th>Chirurgien</th><th>Salle</th><th>Statut</th></tr></thead>
                        <tbody>
                        <?php foreach($interventions as $i):?>
                        <tr>
                            <td><?=$i['id']?></td>
                            <td><strong><?=htmlspecialchars($i['type'])?></strong></td>
                            <td><?=date('d/m/Y',strtotime($i['date_intervention']))?></td>
                            <td><?=$i['duree']?> min</td>
                            <td><span class="urgence-badge urgence-<?=$i['niveau_urgence']?>"><?php $l=[1=>'Faible',2=>'Modéré',3=>'Élevé',4=>'Urgent',5=>'Critique'];echo $l[$i['niveau_urgence']]??$i['niveau_urgence'];?></span></td>
                            <td><?=htmlspecialchars($i['chirurgien'])?></td>
                            <td><?=htmlspecialchars($i['salle']??'-')?></td>
                            <td><?php if($i['annulation_raison']):?><span class="status-annulee"><i class="bi bi-x-circle"></i> Annulée</span><?php else:?><span class="info-badge">Active</span><?php endif;?></td>
                        </tr>
                        <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
                <?php endif;?>
            </div>
        </div>
    </main>
</div>
<script>
document.getElementById('searchInput').addEventListener('input',function(){const v=this.value.toLowerCase();document.querySelectorAll('#tbl tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(v)?'':'none'})});
</script>
</body>
</html>
