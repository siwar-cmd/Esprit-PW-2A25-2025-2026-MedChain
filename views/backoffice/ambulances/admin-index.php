<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/AmbulanceMissionController.php';
$ctrl = new AmbulanceMissionController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create')     $r = $ctrl->createAmbulance($_POST);
    elseif ($action === 'update') $r = $ctrl->updateAmbulance((int)$_POST['id'], $_POST);
    elseif ($action === 'delete') $r = $ctrl->deleteAmbulance((int)$_POST['id']);
    else $r = ['success'=>false,'message'=>'Action inconnue'];
    $_SESSION[$r['success']?'success_message':'error_message'] = $r['message'];
    header('Location: admin-index.php'); exit;
}

$search       = $_GET['search']    ?? '';
$filterDispo  = $_GET['disponible']?? '';
$filterStatut = $_GET['statut']    ?? '';
$filters      = array_filter(['search'=>$search,'disponible'=>$filterDispo,'statut'=>$filterStatut], fn($v)=>$v!=='');
$result       = $ctrl->getAllAmbulances($filters);
$ambulances   = $result['data'] ?? [];
$stats        = $ctrl->getAmbulanceStats();

$success = $_SESSION['success_message'] ?? null; unset($_SESSION['success_message']);
$error   = $_SESSION['error_message']   ?? null; unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion Ambulances – MedChain Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#ffffff;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--radius-md:12px;--radius-lg:20px;}
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
.table td{padding:14px 16px;border-bottom:1px solid var(--gray-200);vertical-align:middle;font-size:14px;color:var(--navy);}
.table tr:last-child td{border-bottom:none;}
.table tr:hover td{background:#F8FAFC;}
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;}
.badge-green{background:#DCFCE7;color:#16A34A;}
.badge-red{background:#FEF2F2;color:#EF4444;}
.badge-blue{background:#E0F2FE;color:#0284C7;}
.badge-gray{background:#F1F5F9;color:#64748B;}
.badge-orange{background:#FFF7ED;color:#EA580C;}
.actions-btns{display:flex;gap:6px;}
.alert{padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;}
.alert-success{background:#DCFCE7;border-left:4px solid #22C55E;color:#166534;}
.alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C;}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:#fff;border-radius:var(--radius-lg);padding:28px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.2);}
.modal h3{font-family:'Syne',sans-serif;font-size:20px;color:var(--navy);margin-bottom:20px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:var(--navy);margin-bottom:6px;}
.form-group input,.form-group select{width:100%;padding:10px 14px;border:1.5px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border .2s;}
.form-group input:focus,.form-group select:focus{border-color:var(--green);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.checkbox-group{display:flex;align-items:center;gap:10px;padding:10px 0;}
.checkbox-group input[type=checkbox]{width:18px;height:18px;accent-color:var(--green);}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--gray-200);}
.empty-state{text-align:center;padding:48px;color:var(--gray-500);}
.empty-state i{font-size:48px;opacity:.3;display:block;margin-bottom:12px;}
.mission-chip{display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;padding:3px 9px;border-radius:12px;}
@media(max-width:768px){.dashboard-container{grid-template-columns:1fr;}.dashboard-sidebar{display:none;}.dashboard-main{padding:20px;}}
@media print{.dashboard-sidebar,.card-header .btn,.actions-btns,.modal-overlay,.filter-row{display:none!important;}.dashboard-container{display:block;}.dashboard-main{padding:0;}}
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
      <a href="admin-index.php" class="dashboard-nav-item active nav-sub"><i class="bi bi-truck-front-fill"></i> Gestion Ambulances</a>
      <a href="../missions/admin-index.php" class="dashboard-nav-item nav-sub"><i class="bi bi-geo-alt-fill"></i> Registre Missions</a>
      <div class="nav-title">Compte</div>
      <a href="../../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
      <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" id="logoutLink"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </nav>
  </aside>

  <main class="dashboard-main">
    <div class="page-header">
      <div>
        <h1><i class="bi bi-truck-front-fill" style="color:var(--green);font-size:24px;"></i> Gestion Ambulances</h1>
        <p>Gérez la flotte d'ambulances – CRUD complet</p>
      </div>
      <div style="display:flex;gap:10px;">
        <button class="btn btn-pdf" onclick="window.print()"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
        <button class="btn btn-primary" onclick="openModal('createModal')"><i class="bi bi-plus-lg"></i> Nouvelle ambulance</button>
      </div>
    </div>

    <?php if($success): ?><div class="alert alert-success" id="flashMsg"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if($error):   ?><div class="alert alert-error"   id="flashMsg"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon primary"><i class="bi bi-truck-front"></i></div><div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total ambulances</p></div></div>
      <div class="stat-card"><div class="stat-icon success"><i class="bi bi-check-circle"></i></div><div class="stat-content"><h3><?= $stats['available'] ?></h3><p>Disponibles</p></div></div>
      <div class="stat-card"><div class="stat-icon info"><i class="bi bi-activity"></i></div><div class="stat-content"><h3><?= $stats['enService'] ?></h3><p>En service</p></div></div>
      <div class="stat-card"><div class="stat-icon warning"><i class="bi bi-exclamation-triangle"></i></div><div class="stat-content"><h3><?= $stats['total'] - $stats['available'] ?></h3><p>Indisponibles</p></div></div>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="card-body">
        <form method="GET" class="filter-row">
          <input type="text" name="search" placeholder="🔍  Rechercher immatriculation, modèle…" value="<?= htmlspecialchars($search) ?>">
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
          <a href="admin-index.php" class="btn btn-outline"><i class="bi bi-x"></i> Réinitialiser</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Table -->
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
            <thead><tr><th>#</th><th>Immatriculation</th><th>Modèle</th><th>Statut</th><th>Capacité</th><th>Disponibilité</th><th>Missions</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($ambulances as $a): ?>
            <tr>
              <td><strong>#<?= (int)$a['idAmbulance'] ?></strong></td>
              <td><strong><?= htmlspecialchars($a['immatriculation']) ?></strong></td>
              <td><?= htmlspecialchars($a['modele']) ?></td>
              <td>
                <?php $sc = match($a['statut']){ 'En service'=>'badge-green','Hors service'=>'badge-red',default=>'badge-gray' }; ?>
                <span class="badge <?= $sc ?>"><i class="bi bi-circle-fill" style="font-size:7px;"></i><?= htmlspecialchars($a['statut']) ?></span>
              </td>
              <td><?= (int)$a['capacite'] ?> pers.</td>
              <td><span class="badge <?= $a['estDisponible']?'badge-green':'badge-red' ?>"><?= $a['estDisponible']?'Disponible':'Indisponible' ?></span></td>
              <td>
                <!-- Joint column: mission counts from the JOIN -->
                <span class="mission-chip badge-blue"><i class="bi bi-geo-alt"></i><?= (int)($a['nb_missions']??0) ?> total</span>
                <?php if(($a['missions_en_cours']??0) > 0): ?>
                <span class="mission-chip badge-orange"><i class="bi bi-arrow-repeat"></i><?= (int)$a['missions_en_cours'] ?> en cours</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="actions-btns">
                  <button class="btn btn-edit btn-sm" onclick='openEdit(<?= json_encode($a) ?>)'><i class="bi bi-pencil"></i> Modifier</button>
                  <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= (int)$a['idAmbulance'] ?>, '<?= htmlspecialchars($a['immatriculation'], ENT_QUOTES) ?>', <?= (int)($a['nb_missions']??0) ?>)">
                    <i class="bi bi-trash"></i>
                  </button>
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
<form id="deleteForm" method="POST" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="deleteId">
</form>

<!-- CREATE MODAL -->
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <h3><i class="bi bi-plus-circle" style="color:var(--green);"></i> Nouvelle ambulance</h3>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="form-row">
        <div class="form-group"><label>Immatriculation *</label><input type="text" name="immatriculation" required placeholder="Ex: TU-123-456"></div>
        <div class="form-group"><label>Modèle *</label><input type="text" name="modele" required placeholder="Ex: Mercedes Sprinter"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Statut</label>
          <select name="statut"><option>En service</option><option>Hors service</option><option>En maintenance</option></select>
        </div>
        <div class="form-group"><label>Capacité (personnes)</label><input type="number" name="capacite" value="2" min="1" max="10"></div>
      </div>
      <div class="checkbox-group"><input type="checkbox" name="estDisponible" id="cd" checked><label for="cd">Disponible</label></div>
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
    <h3><i class="bi bi-pencil" style="color:var(--green);"></i> Modifier ambulance</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editId">
      <div class="form-row">
        <div class="form-group"><label>Immatriculation *</label><input type="text" name="immatriculation" id="editImmat" required></div>
        <div class="form-group"><label>Modèle *</label><input type="text" name="modele" id="editModele" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Statut</label>
          <select name="statut" id="editStatut"><option>En service</option><option>Hors service</option><option>En maintenance</option></select>
        </div>
        <div class="form-group"><label>Capacité</label><input type="number" name="capacite" id="editCapacite" min="1" max="10"></div>
      </div>
      <div class="checkbox-group"><input type="checkbox" name="estDisponible" id="editDispo"><label for="editDispo">Disponible</label></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Annuler</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Modals ── */
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function openEdit(a){
  document.getElementById('editId').value       = a.idAmbulance;
  document.getElementById('editImmat').value    = a.immatriculation;
  document.getElementById('editModele').value   = a.modele;
  document.getElementById('editStatut').value   = a.statut;
  document.getElementById('editCapacite').value = a.capacite;
  document.getElementById('editDispo').checked  = a.estDisponible == 1;
  openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));

/* ── SweetAlert2: delete confirmation ── */
function confirmDelete(id, immat, nbMissions){
  const extra = nbMissions > 0
    ? `<br><span style="color:#EF4444;font-size:13px;"><i class="bi bi-exclamation-triangle"></i> Cette ambulance possède <strong>${nbMissions}</strong> mission(s) qui seront également supprimées.</span>`
    : '';
  Swal.fire({
    title: 'Supprimer l\'ambulance ?',
    html: `Vous allez supprimer <strong>${immat}</strong>.${extra}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
    cancelButtonColor: '#6B7280',
    confirmButtonText: '<i class="bi bi-trash"></i> Oui, supprimer',
    cancelButtonText: 'Annuler',
    customClass: { popup: 'swal-popup' }
  }).then(result => {
    if (result.isConfirmed) {
      document.getElementById('deleteId').value = id;
      document.getElementById('deleteForm').submit();
    }
  });
}

/* ── SweetAlert2: logout confirmation ── */
document.getElementById('logoutLink').addEventListener('click', function(e){
  e.preventDefault();
  const href = this.href;
  Swal.fire({
    title: 'Déconnexion',
    text: 'Êtes-vous sûr de vouloir vous déconnecter ?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#1D9E75',
    cancelButtonColor: '#6B7280',
    confirmButtonText: 'Oui, déconnecter',
    cancelButtonText: 'Annuler'
  }).then(r => { if(r.isConfirmed) window.location.href = href; });
});

/* ── Auto-dismiss flash message ── */
setTimeout(()=>{
  const f = document.getElementById('flashMsg');
  if(f){ f.style.transition='opacity .5s'; f.style.opacity='0'; setTimeout(()=>f.remove(),500); }
}, 4000);
</script>
</body>
</html>
