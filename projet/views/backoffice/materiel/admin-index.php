<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header('Location: ../../frontoffice/auth/login.php'); exit; }
require_once __DIR__ . '/../../../controllers/MaterielController.php';
$ctrl = new MaterielController();
$materiels = $ctrl->getAllMateriels();
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matériel Chirurgical - MedChain</title>
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
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;flex-wrap:wrap;gap:16px}
        .page-header h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:var(--navy)}
        .page-header p{color:var(--gray-500);font-size:14px}
        .card{background:var(--white);border-radius:var(--radius-xl);border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:32px}
        .card-header{padding:20px 24px;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px}
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
        .btn{padding:8px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:600;cursor:pointer;transition:all .3s;border:none;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
        .btn-primary{background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;box-shadow:0 3px 12px rgba(29,158,117,.30)}
        .btn-primary:hover{transform:translateY(-2px)}
        .btn-warning{background:#F59E0B;color:white}.btn-warning:hover{background:#D97706}
        .btn-danger{background:#EF4444;color:white}.btn-danger:hover{background:#DC2626}
        .btn-sm{padding:6px 12px;font-size:12px}
        .actions-cell{display:flex;gap:6px}
        .dispo-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
        .dispo-disponible{background:#F0FDF4;color:#22C55E}
        .dispo-indisponible{background:#FEF2F2;color:#EF4444}
        .dispo-en_maintenance{background:#FEF9C3;color:#CA8A04}
        .progress-bar-wrap{width:100px;height:8px;background:#E5E7EB;border-radius:4px;overflow:hidden}
        .progress-bar-fill{height:100%;border-radius:4px;transition:width .3s}
        .alert{padding:14px 18px;border-radius:var(--radius-md);margin-bottom:24px;display:flex;align-items:center;gap:12px;animation:slideIn .3s}
        @keyframes slideIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        .alert-success{background:#F0FDF4;border-left:4px solid #22C55E;color:#166534}
        .alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C}
        .alert-close{margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;opacity:0.6}
        @media(max-width:1024px){.dashboard-container{grid-template-columns:1fr}.dashboard-sidebar{display:none}}
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
            <div class="nav-dropdown"><a href="#" class="dashboard-nav-item active"><i class="bi bi-hospital"></i> Bloc Opératoire <i class="bi bi-chevron-down" style="font-size:12px;margin-left:auto"></i></a><div class="nav-dropdown-menu"><a href="../intervention/admin-index.php" class="dashboard-nav-item"><i class="bi bi-heart-pulse"></i> Interventions</a><a href="admin-index.php" class="dashboard-nav-item active"><i class="bi bi-tools"></i> Matériel</a></div></div>
            <a href="../rendezvous/admin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
            <div class="dashboard-nav-title">Gestion</div>
            <a href="../../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <div class="page-header">
            <div><h1>Matériel Chirurgical</h1><p>Bloc Opératoire — Admin</p></div>
            <a href="admin-add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter</a>
        </div>
        <?php if($success_message):?><div class="alert alert-success"><i class="bi bi-check-circle-fill"></i><div><?=htmlspecialchars($success_message)?></div><button class="alert-close">&times;</button></div><?php endif;?>
        <?php if($error_message):?><div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i><div><?=htmlspecialchars($error_message)?></div><button class="alert-close">&times;</button></div><?php endif;?>
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-tools"></i> Liste du Matériel</h2>
                <div class="search-box"><i class="bi bi-search"></i><input type="text" id="searchInput" placeholder="Rechercher..."></div>
            </div>
            <div class="card-body">
                <?php if(empty($materiels)):?>
                    <div style="text-align:center;padding:48px"><i class="bi bi-tools" style="font-size:48px;opacity:0.3"></i><p style="margin-top:16px">Aucun matériel</p></div>
                <?php else:?>
                <div class="table-responsive">
                    <table class="table" id="tbl">
                        <thead><tr><th>ID</th><th>Nom</th><th>Catégorie</th><th>Disponibilité</th><th>Stérilisation</th><th>Utilisations</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach($materiels as $m):
                            $pct = $m['nombreUtilisationsMax']>0 ? round(($m['nombreUtilisationsActuelles']/$m['nombreUtilisationsMax'])*100) : 0;
                            $barColor = $pct > 80 ? '#EF4444' : ($pct > 50 ? '#F59E0B' : '#22C55E');
                            $dispoClass = strtolower(str_replace(' ','_',$m['disponibilite']??''));
                        ?>
                        <tr>
                            <td><?=$m['idMateriel']?></td>
                            <td><strong><?=htmlspecialchars($m['nom']??'')?></strong></td>
                            <td><?=htmlspecialchars($m['categorie']??'-')?></td>
                            <td><span class="dispo-badge dispo-<?=$dispoClass?>"><?=htmlspecialchars($m['disponibilite']??'-')?></span></td>
                            <td><?=htmlspecialchars($m['statutSterilisation']??'-')?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?=$pct?>%;background:<?=$barColor?>"></div></div>
                                    <span style="font-size:12px;color:var(--gray-500)"><?=$m['nombreUtilisationsActuelles']?>/<?=$m['nombreUtilisationsMax']?></span>
                                </div>
                            </td>
                            <td><div class="actions-cell">
                                <a href="admin-edit.php?id=<?=$m['idMateriel']?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                                <a href="admin-delete.php?id=<?=$m['idMateriel']?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></a>
                            </div></td>
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
document.querySelectorAll('.alert-close').forEach(b=>b.addEventListener('click',()=>b.closest('.alert').remove()));
setTimeout(()=>document.querySelectorAll('.alert').forEach(a=>{a.style.opacity='0';a.style.transition='all .3s';setTimeout(()=>a.remove(),300)}),5000);
</script>
</body>
</html>
