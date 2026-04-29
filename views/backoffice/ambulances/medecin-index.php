<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'],['admin','medecin'])) {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/AmbulanceMissionController.php';
$ctrl = new AmbulanceMissionController();

$search      = $_GET['search']    ?? '';
$filterDispo = $_GET['disponible']?? '';
$filterStatut= $_GET['statut']    ?? '';
$filters     = array_filter(['search'=>$search,'disponible'=>$filterDispo,'statut'=>$filterStatut],fn($v)=>$v!=='');
$result      = $ctrl->getAllAmbulances($filters);
$ambulances  = $result['data'] ?? [];
$stats       = $ctrl->getAmbulanceStats();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Ambulances – Espace Médecin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#fff;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--radius-md:12px;--radius-lg:20px;}
body{font-family:'DM Sans',sans-serif;background:#f0faf6;min-height:100vh;}
.dashboard-container{display:grid;grid-template-columns:260px 1fr;min-height:100vh;}
.dashboard-sidebar{background:#0F172A;height:100vh;position:sticky;top:0;display:flex;flex-direction:column;overflow-y:auto;}
.dashboard-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.1);}
.dashboard-logo a{text-decoration:none;font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#fff;}
.dashboard-logo a span{color:var(--green);}
.dashboard-nav{padding:16px 12px;display:flex;flex-direction:column;gap:4px;flex:1;}
.nav-title{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748B;padding:12px 16px 6px;font-weight:600;}
.dashboard-nav-item{padding:11px 16px;color:#94A3B8;text-decoration:none;border-radius:var(--radius-md);display:flex;align-items:center;gap:12px;font-weight:500;font-size:14px;transition:all .2s;}
.dashboard-nav-item:hover{background:rgba(255,255,255,.07);color:#fff;}
.dashboard-nav-item.active{background:rgba(29,158,117,.2);color:var(--green);}
.dashboard-nav-item.logout{color:#F87171;}
.dashboard-nav-item.logout:hover{background:rgba(248,113,113,.1);}
.nav-sub{padding-left:28px;}
.dashboard-main{padding:32px 40px;overflow-y:auto;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:16px;}
.page-header h1{font-family:'Syne',sans-serif;font-size:26px;font-weight:700;color:var(--navy);}
.page-header p{color:var(--gray-500);font-size:14px;}
.readonly-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(29,158,117,.1);color:var(--green);border:1px solid rgba(29,158,117,.2);padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:var(--white);border-radius:var(--radius-lg);padding:18px 20px;display:flex;align-items:center;gap:14px;border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);transition:all .2s;}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.08);}
.stat-icon{width:48px;height:48px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:22px;}
.stat-icon.primary{background:rgba(29,158,117,.1);color:var(--green);}
.stat-icon.success{background:rgba(34,197,94,.1);color:#22C55E;}
.stat-icon.warning{background:rgba(245,158,11,.1);color:#F59E0B;}
.stat-content h3{font-size:26px;font-weight:700;color:var(--navy);}
.stat-content p{font-size:12px;color:var(--gray-500);}
.card{background:var(--white);border-radius:var(--radius-lg);border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:24px;}
.card-header{padding:18px 24px;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;}
.card-header h2{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:8px;}
.card-header h2 i{color:var(--green);}
.card-body{padding:20px 24px;}
.filter-row{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;}
.filter-row input,.filter-row select{padding:9px 13px;border:1px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border .2s;}
.filter-row input:focus,.filter-row select:focus{border-color:var(--green);}
.filter-row input{flex:1;min-width:200px;}
.btn{padding:9px 18px;border-radius:var(--radius-md);font-size:14px;font-weight:600;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:7px;text-decoration:none;transition:all .2s;font-family:'DM Sans',sans-serif;}
.btn-primary{background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff;box-shadow:0 3px 10px rgba(29,158,117,.25);}
.btn-primary:hover{transform:translateY(-1px);}
.btn-outline{background:transparent;border:1.5px solid var(--gray-200);color:var(--gray-500);}
.btn-outline:hover{border-color:var(--green);color:var(--green);}
.btn-pdf{background:rgba(59,130,246,.1);color:#3B82F6;border:1px solid rgba(59,130,246,.2);}
.btn-pdf:hover{background:#3B82F6;color:#fff;}
.table{width:100%;border-collapse:collapse;}
.table th{background:#F8FAFC;padding:11px 16px;text-align:left;font-weight:600;color:#64748B;border-bottom:1px solid var(--gray-200);font-size:13px;}
.table td{padding:14px 16px;border-bottom:1px solid var(--gray-200);vertical-align:middle;font-size:14px;color:var(--navy);}
.table tr:last-child td{border-bottom:none;}
.table tr:hover td{background:#F8FAFC;}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;}
.badge-green{background:#DCFCE7;color:#16A34A;}
.badge-red{background:#FEF2F2;color:#EF4444;}
.badge-gray{background:#F1F5F9;color:#64748B;}
.empty-state{text-align:center;padding:48px;color:var(--gray-500);}
.empty-state i{font-size:48px;opacity:.3;display:block;margin-bottom:12px;}
@media(max-width:768px){.dashboard-container{grid-template-columns:1fr;}.dashboard-sidebar{display:none;}.dashboard-main{padding:20px;}}
@media print{.dashboard-sidebar,.filter-row,.btn-pdf{display:none!important;}.dashboard-container{display:block;}.dashboard-main{padding:0;}}
</style>
</head>
<body>
<div class="dashboard-container">
  <aside class="dashboard-sidebar">
    <div class="dashboard-logo"><a href="#">Med<span>Chain</span></a></div>
    <nav class="dashboard-nav">
      <div class="nav-title">Navigation</div>
      <a href="../medecin-dashboard.php" class="dashboard-nav-item"><i class="bi bi-speedometer2"></i> Tableau de bord</a>
      <a href="../rendezvous/medecin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Mes Consultations</a>
      <a href="../ficherdv/medecin-index.php" class="dashboard-nav-item"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
      <div class="nav-title">Flotte &amp; Missions</div>
      <a href="medecin-index.php" class="dashboard-nav-item active nav-sub"><i class="bi bi-truck-front-fill"></i> Ambulances</a>
      <a href="../missions/medecin-index.php" class="dashboard-nav-item nav-sub"><i class="bi bi-geo-alt-fill"></i> Missions</a>
      <div class="nav-title">Compte</div>
      <a href="../../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
      <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Déconnecter ?')"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </nav>
  </aside>

  <main class="dashboard-main">
    <div class="page-header">
      <div>
        <h1><i class="bi bi-truck-front-fill" style="color:var(--green);font-size:24px;"></i> Flotte d'Ambulances</h1>
        <p>Vue médecin – consultation uniquement &nbsp;<span class="readonly-badge"><i class="bi bi-eye"></i> Lecture seule</span></p>
      </div>
      <button class="btn btn-pdf" onclick="window.print()"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
    </div>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon primary"><i class="bi bi-truck-front"></i></div><div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total ambulances</p></div></div>
      <div class="stat-card"><div class="stat-icon success"><i class="bi bi-check-circle"></i></div><div class="stat-content"><h3><?= $stats['available'] ?></h3><p>Disponibles</p></div></div>
      <div class="stat-card"><div class="stat-icon warning"><i class="bi bi-exclamation-triangle"></i></div><div class="stat-content"><h3><?= $stats['total']-$stats['available'] ?></h3><p>Indisponibles</p></div></div>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="GET" class="filter-row">
          <input type="text" name="search" placeholder="🔍  Rechercher…" value="<?= htmlspecialchars($search) ?>">
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
          <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
          <?php if($search||$filterStatut||$filterDispo!==''): ?>
          <a href="medecin-index.php" class="btn btn-outline"><i class="bi bi-x"></i> Réinitialiser</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2><i class="bi bi-list-ul"></i> Liste des ambulances <span style="background:#DCFCE7;color:#16A34A;padding:3px 10px;border-radius:20px;font-size:13px;font-family:'DM Sans',sans-serif;"><?= count($ambulances) ?></span></h2>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if(empty($ambulances)): ?>
        <div class="empty-state"><i class="bi bi-truck-front"></i><p>Aucune ambulance trouvée</p></div>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="table">
            <thead><tr><th>#</th><th>Immatriculation</th><th>Modèle</th><th>Statut</th><th>Capacité</th><th>Disponibilité</th></tr></thead>
            <tbody>
            <?php foreach($ambulances as $a): ?>
            <tr>
              <td><strong>#<?= (int)$a['idAmbulance'] ?></strong></td>
              <td><strong><?= htmlspecialchars($a['immatriculation']) ?></strong></td>
              <td><?= htmlspecialchars($a['modele']) ?></td>
              <td><?php $sc=match($a['statut']){'En service'=>'badge-green','Hors service'=>'badge-red',default=>'badge-gray'}; ?><span class="badge <?= $sc ?>"><?= htmlspecialchars($a['statut']) ?></span></td>
              <td><?= (int)$a['capacite'] ?> pers.</td>
              <td><span class="badge <?= $a['estDisponible']?'badge-green':'badge-red' ?>"><?= $a['estDisponible']?'Disponible':'Indisponible' ?></span></td>
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
</body>
</html>
