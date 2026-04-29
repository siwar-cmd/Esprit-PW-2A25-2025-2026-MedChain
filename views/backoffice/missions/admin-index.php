<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/AmbulanceMissionController.php';
$ctrl = new AmbulanceMissionController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create')      $r = $ctrl->createMission($_POST);
    elseif ($action === 'update')  $r = $ctrl->updateMission((int)$_POST['id'], $_POST);
    elseif ($action === 'delete')  $r = $ctrl->deleteMission((int)$_POST['id']);
    else $r = ['success'=>false,'message'=>'Action inconnue'];
    $_SESSION[$r['success']?'success_message':'error_message'] = $r['message'];
    header('Location: admin-index.php'); exit;
}

$search = $_GET['search'] ?? '';
$filterTerminee = $_GET['estTerminee'] ?? '';
$filterType     = $_GET['typeMission'] ?? '';
$filters = array_filter(['search'=>$search,'estTerminee'=>$filterTerminee,'typeMission'=>$filterType], fn($v)=>$v!=='');
$result    = $ctrl->getAllMissions($filters);
$missions  = $result['data'] ?? [];
$stats     = $ctrl->getMissionStats();
$ambStats  = $ctrl->getAmbulanceStats();
$ambulances= $ctrl->getAmbulancesForSelect();

$success = $_SESSION['success_message'] ?? null; unset($_SESSION['success_message']);
$error   = $_SESSION['error_message']   ?? null; unset($_SESSION['error_message']);

$types = ['Urgence','Transport','Rapatriement','Autre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Registre Missions – MedChain Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:var(--white);border-radius:var(--radius-lg);padding:18px 20px;display:flex;align-items:center;gap:14px;border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);transition:all .2s;}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.08);}
.stat-icon{width:48px;height:48px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:22px;}
.stat-icon.primary{background:rgba(29,158,117,.1);color:var(--green);}
.stat-icon.success{background:rgba(34,197,94,.1);color:#22C55E;}
.stat-icon.warning{background:rgba(245,158,11,.1);color:#F59E0B;}
.stat-icon.info{background:rgba(59,130,246,.1);color:#3B82F6;}
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
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(29,158,117,.35);}
.btn-outline{background:transparent;border:1.5px solid var(--gray-200);color:var(--gray-500);}
.btn-outline:hover{border-color:var(--green);color:var(--green);}
.btn-danger{background:#FEF2F2;color:#EF4444;border:1px solid #FECACA;}
.btn-danger:hover{background:#EF4444;color:#fff;}
.btn-edit{background:rgba(29,158,117,.1);color:var(--green);border:1px solid rgba(29,158,117,.2);}
.btn-edit:hover{background:var(--green);color:#fff;}
.btn-sm{padding:6px 12px;font-size:12px;}
.btn-pdf{background:rgba(59,130,246,.1);color:#3B82F6;border:1px solid rgba(59,130,246,.2);}
.btn-pdf:hover{background:#3B82F6;color:#fff;}
.table{width:100%;border-collapse:collapse;}
.table th{background:#F8FAFC;padding:11px 16px;text-align:left;font-weight:600;color:#64748B;border-bottom:1px solid var(--gray-200);font-size:13px;}
.table td{padding:13px 16px;border-bottom:1px solid var(--gray-200);vertical-align:middle;font-size:14px;color:var(--navy);}
.table tr:last-child td{border-bottom:none;}
.table tr:hover td{background:#F8FAFC;}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;}
.badge-green{background:#DCFCE7;color:#16A34A;}
.badge-red{background:#FEF2F2;color:#EF4444;}
.badge-blue{background:#E0F2FE;color:#0284C7;}
.badge-orange{background:#FFF7ED;color:#EA580C;}
.badge-gray{background:#F1F5F9;color:#64748B;}
.actions-btns{display:flex;gap:6px;}
.alert{padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;}
.alert-success{background:#DCFCE7;border-left:4px solid #22C55E;color:#166534;}
.alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C;}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{background:#fff;border-radius:var(--radius-lg);padding:28px;width:100%;max-width:580px;box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto;}
.modal h3{font-family:'Syne',sans-serif;font-size:20px;color:var(--navy);margin-bottom:20px;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:var(--navy);margin-bottom:5px;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;border:1.5px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border .2s;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--green);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.checkbox-group{display:flex;align-items:center;gap:10px;padding:8px 0;}
.checkbox-group input[type=checkbox]{width:18px;height:18px;accent-color:var(--green);}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:18px;padding-top:14px;border-top:1px solid var(--gray-200);}
.empty-state{text-align:center;padding:48px;color:var(--gray-500);}
.empty-state i{font-size:48px;opacity:.3;display:block;margin-bottom:12px;}
@media(max-width:768px){.dashboard-container{grid-template-columns:1fr;}.dashboard-sidebar{display:none;}.dashboard-main{padding:20px;}.form-row{grid-template-columns:1fr;}}
@media print{.dashboard-sidebar,.card-header .btn,.actions-btns,.modal-overlay,.filter-row,.btn-pdf{display:none!important;}.dashboard-container{display:block;}.dashboard-main{padding:0;}}
</style>
</head>
<body>
<div class="dashboard-container">
  <aside class="dashboard-sidebar">
    <div class="dashboard-logo"><a href="../admin-dashboard.php">Med<span>Chain</span></a></div>
    <nav class="dashboard-nav">
      <div class="nav-title">Navigation</div>
      <a href="../admin-dashboard.php" class="dashboard-nav-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="../admin-users.php" class="dashboard-nav-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
      <a href="../admin-reports-statistics.php" class="dashboard-nav-item"><i class="bi bi-graph-up"></i> Statistiques</a>
      <a href="../rendezvous/admin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
      <a href="../ficherdv/admin-index.php" class="dashboard-nav-item"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
      <div class="nav-title">Flotte &amp; Missions</div>
      <a href="../ambulances/admin-index.php" class="dashboard-nav-item nav-sub"><i class="bi bi-truck-front-fill"></i> Gestion Ambulances</a>
      <a href="admin-index.php" class="dashboard-nav-item active nav-sub"><i class="bi bi-geo-alt-fill"></i> Registre Missions</a>
      <div class="nav-title">Compte</div>
      <a href="../../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
      <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Déconnecter ?')"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </nav>
  </aside>

  <main class="dashboard-main">
    <div class="page-header">
      <div>
        <h1><i class="bi bi-geo-alt-fill" style="color:var(--green);font-size:24px;"></i> Registre des Missions</h1>
        <p>Gérez toutes les missions médicales – CRUD complet</p>
      </div>
      <div style="display:flex;gap:10px;">
        <button class="btn btn-pdf" onclick="window.print()"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
        <button class="btn btn-primary" onclick="openModal('createModal')"><i class="bi bi-plus-lg"></i> Nouvelle mission</button>
      </div>
    </div>

    <?php if($success): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if($error):   ?><div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon primary"><i class="bi bi-geo-alt"></i></div><div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total missions</p></div></div>
      <div class="stat-card"><div class="stat-icon warning"><i class="bi bi-arrow-repeat"></i></div><div class="stat-content"><h3><?= $stats['ongoing'] ?></h3><p>En cours</p></div></div>
      <div class="stat-card"><div class="stat-icon success"><i class="bi bi-check-circle"></i></div><div class="stat-content"><h3><?= $stats['completed'] ?></h3><p>Terminées</p></div></div>
      <div class="stat-card"><div class="stat-icon info"><i class="bi bi-truck-front"></i></div><div class="stat-content"><h3><?= $ambStats['available'] ?></h3><p>Ambulances dispo.</p></div></div>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="GET" class="filter-row">
          <input type="text" name="search" placeholder="🔍  Rechercher type, lieu, équipe…" value="<?= htmlspecialchars($search) ?>">
          <select name="typeMission">
            <option value="">Tous les types</option>
            <?php foreach($types as $t): ?><option value="<?= $t ?>" <?= $filterType===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?>
          </select>
          <select name="estTerminee">
            <option value="">Statut mission</option>
            <option value="0" <?= $filterTerminee==='0'?'selected':'' ?>>En cours</option>
            <option value="1" <?= $filterTerminee==='1'?'selected':'' ?>>Terminée</option>
          </select>
          <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
          <?php if($search||$filterType||$filterTerminee!==''): ?>
          <a href="admin-index.php" class="btn btn-outline"><i class="bi bi-x"></i> Réinitialiser</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2><i class="bi bi-list-ul"></i> Liste des missions <span style="background:#DCFCE7;color:#16A34A;padding:3px 10px;border-radius:20px;font-size:13px;font-family:'DM Sans',sans-serif;"><?= count($missions) ?></span></h2>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if(empty($missions)): ?>
        <div class="empty-state"><i class="bi bi-geo-alt"></i><p>Aucune mission trouvée</p></div>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="table">
            <thead><tr><th>#</th><th>Type</th><th>Ambulance</th><th>Départ → Arrivée</th><th>Date début</th><th>Durée</th><th>Équipe</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($missions as $m):
              $mObj = new Mission($m['idMission'],$m['dateDebut'],$m['dateFin']??'',$m['typeMission'],$m['lieuDepart'],$m['lieuArrivee'],$m['equipe'],(bool)$m['estTerminee'],$m['idAmbulance']);
              $duree = $mObj->calculerDuree();
              $typeBadge = match($m['typeMission']){ 'Urgence'=>'badge-red','Transport'=>'badge-blue','Rapatriement'=>'badge-orange',default=>'badge-gray' };
            ?>
            <tr>
              <td><strong>#<?= (int)$m['idMission'] ?></strong></td>
              <td><span class="badge <?= $typeBadge ?>"><?= htmlspecialchars($m['typeMission']) ?></span></td>
              <td><strong><?= htmlspecialchars($m['amb_immatriculation']??'—') ?></strong><br><small style="color:var(--gray-500);"><?= htmlspecialchars($m['amb_modele']??'') ?></small></td>
              <td><?= htmlspecialchars($m['lieuDepart']) ?> <i class="bi bi-arrow-right" style="color:var(--green);"></i> <?= htmlspecialchars($m['lieuArrivee']) ?></td>
              <td><?= $m['dateDebut'] ? date('d/m/Y',strtotime($m['dateDebut'])) : '—' ?></td>
              <td><?= $duree ?? '—' ?></td>
              <td><?= htmlspecialchars($m['equipe']) ?></td>
              <td><span class="badge <?= $m['estTerminee']?'badge-green':'badge-orange' ?>"><?= $m['estTerminee']?'Terminée':'En cours' ?></span></td>
              <td>
                <div class="actions-btns">
                  <button class="btn btn-edit btn-sm" onclick='openEditMission(<?= json_encode($m) ?>)'><i class="bi bi-pencil"></i></button>
                  <button class="btn btn-danger btn-sm" onclick="confirmDeleteMission(<?= (int)$m['idMission'] ?>, '<?= htmlspecialchars($m['typeMission'],ENT_QUOTES) ?>')"><i class="bi bi-trash"></i></button>
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

<!-- Hidden delete form (submitted via SweetAlert2) -->
<form id="deleteMissionForm" method="POST" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="deleteMissionId">
</form>

<!-- CREATE MODAL -->
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <h3><i class="bi bi-plus-circle" style="color:var(--green);"></i> Nouvelle mission</h3>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="form-row">
        <div class="form-group"><label>Type de mission *</label>
          <select name="typeMission" required>
            <?php foreach($types as $t): ?><option><?= $t ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Ambulance *</label>
          <select name="idAmbulance" required>
            <option value="">-- Choisir --</option>
            <?php foreach($ambulances as $a): ?>
            <option value="<?= $a['idAmbulance'] ?>"><?= htmlspecialchars($a['immatriculation'].' – '.$a['modele']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Lieu de départ</label><input type="text" name="lieuDepart" placeholder="Ex: Tunis Centre"></div>
        <div class="form-group"><label>Lieu d'arrivée</label><input type="text" name="lieuArrivee" placeholder="Ex: Hôpital Mongi Slim"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Date début *</label><input type="datetime-local" name="dateDebut" required></div>
        <div class="form-group"><label>Date fin</label><input type="datetime-local" name="dateFin"></div>
      </div>
      <div class="form-group"><label>Équipe</label><input type="text" name="equipe" placeholder="Ex: Dr. Ben Ali, Inf. Saidi"></div>
      <div class="checkbox-group"><input type="checkbox" name="estTerminee" id="cTerminee"><label for="cTerminee">Mission terminée</label></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('createModal')">Annuler</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Créer</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <h3><i class="bi bi-pencil" style="color:var(--green);"></i> Modifier mission</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="eId">
      <div class="form-row">
        <div class="form-group"><label>Type de mission *</label>
          <select name="typeMission" id="eType">
            <?php foreach($types as $t): ?><option><?= $t ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Ambulance *</label>
          <select name="idAmbulance" id="eAmb">
            <?php foreach($ambulances as $a): ?>
            <option value="<?= $a['idAmbulance'] ?>"><?= htmlspecialchars($a['immatriculation'].' – '.$a['modele']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Lieu de départ</label><input type="text" name="lieuDepart" id="eDepart"></div>
        <div class="form-group"><label>Lieu d'arrivée</label><input type="text" name="lieuArrivee" id="eArrivee"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Date début *</label><input type="datetime-local" name="dateDebut" id="eDebut" required></div>
        <div class="form-group"><label>Date fin</label><input type="datetime-local" name="dateFin" id="eFin"></div>
      </div>
      <div class="form-group"><label>Équipe</label><input type="text" name="equipe" id="eEquipe"></div>
      <div class="checkbox-group"><input type="checkbox" name="estTerminee" id="eTerminee"><label for="eTerminee">Mission terminée</label></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Annuler</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function toLocal(dt){if(!dt)return'';try{return new Date(dt).toISOString().slice(0,16);}catch(e){return '';}}
function openEditMission(m){
  document.getElementById('eId').value     = m.idMission;
  document.getElementById('eType').value   = m.typeMission;
  document.getElementById('eAmb').value    = m.idAmbulance;
  document.getElementById('eDepart').value = m.lieuDepart;
  document.getElementById('eArrivee').value= m.lieuArrivee;
  document.getElementById('eDebut').value  = toLocal(m.dateDebut);
  document.getElementById('eFin').value    = toLocal(m.dateFin);
  document.getElementById('eEquipe').value = m.equipe;
  document.getElementById('eTerminee').checked = m.estTerminee==1;
  openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));

/* ── SweetAlert2: delete mission ── */
function confirmDeleteMission(id, type){
  Swal.fire({
    title: 'Supprimer la mission ?',
    html: `Vous allez supprimer la mission de type <strong>${type}</strong>. Cette action est irréversible.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
    cancelButtonColor: '#6B7280',
    confirmButtonText: '<i class="bi bi-trash"></i> Oui, supprimer',
    cancelButtonText: 'Annuler'
  }).then(result => {
    if (result.isConfirmed) {
      document.getElementById('deleteMissionId').value = id;
      document.getElementById('deleteMissionForm').submit();
    }
  });
}

/* ── SweetAlert2: logout ── */
document.querySelectorAll('a[href*="logout"]').forEach(link=>{
  link.addEventListener('click',function(e){
    e.preventDefault(); const href=this.href;
    Swal.fire({title:'Déconnexion',text:'Êtes-vous sûr de vouloir vous déconnecter ?',icon:'question',showCancelButton:true,confirmButtonColor:'#1D9E75',cancelButtonColor:'#6B7280',confirmButtonText:'Oui, déconnecter',cancelButtonText:'Annuler'})
    .then(r=>{ if(r.isConfirmed) window.location.href=href; });
  });
});

/* ── Auto-dismiss flash ── */
setTimeout(()=>{
  document.querySelectorAll('.alert').forEach(a=>{ a.style.transition='opacity .5s'; a.style.opacity='0'; setTimeout(()=>a.remove(),500); });
},4000);
</script>
</body>
</html>
