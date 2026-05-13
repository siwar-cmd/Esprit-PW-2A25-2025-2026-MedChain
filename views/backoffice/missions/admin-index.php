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

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$filters = ['search'=>$search, 'page'=>$page, 'limit'=>10];
$result = $ctrl->getAllMissions($filters);
$missions = $result['data'] ?? [];
$totalPages = $result['pages'] ?? 1;
$stats = $ctrl->getMissionStats();
$ambulances = $ctrl->getAmbulancesForSelect();

$success = $_SESSION['success_message'] ?? null; unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);
$today = date('l d F Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Missions – Admin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="../components/admin.css">
<style>
    .card { overflow: hidden; margin-bottom: 24px; }
    .table th { background: #F8FAFC !important; color: #64748B !important; }
</style>

</head>
<body>
<div class="dashboard-container">
  <?php include '../components/sidebar-admin.php'; ?>

  <main class="dashboard-main">
    <div class="page-header">
      <div><h1>Registre des Missions</h1><p><?= $today ?></p></div>
      <button class="btn btn-primary" onclick="openCreateModal()"><i class="bi bi-plus-lg"></i> Nouvelle Mission</button>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon total"><i class="bi bi-geo-alt"></i></div>
        <div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total Missions</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon ongoing"><i class="bi bi-clock-history"></i></div>
        <div class="stat-content"><h3><?= $stats['ongoing'] ?></h3><p>En cours</p></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2>Toutes les missions</h2>
        <form method="GET"><input type="text" name="search" class="search-input" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>"></form>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="table">
          <thead><tr><th>Type</th><th>Ambulance</th><th>Itinéraire</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach($missions as $m): ?>
            <tr>
              <td><strong><?= htmlspecialchars($m['typeMission']) ?></strong></td>
              <td><?= htmlspecialchars($m['amb_immatriculation']??'—') ?></td>
              <td><?= htmlspecialchars($m['lieuDepart']) ?> <i class="bi bi-arrow-right"></i> <?= htmlspecialchars($m['lieuArrivee']) ?></td>
              <td><?= $m['dateDebut'] ? date('d/m/Y',strtotime($m['dateDebut'])) : '—' ?></td>
              <td><span class="badge <?= $m['estTerminee']?'bg-success':'bg-warning' ?>"><?= $m['estTerminee']?'Terminée':'En cours' ?></span></td>
              <td>
                <button class="btn btn-primary btn-sm" onclick='editMission(<?= json_encode($m) ?>)'><i class="bi bi-pencil"></i></button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $m['idMission'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script>
function openCreateModal(){
  Swal.fire({
    title: 'Nouvelle Mission',
    html: `<form id='f-c' method='POST'><input type='hidden' name='action' value='create'><select name='typeMission' class='swal2-select'><option>Urgence</option><option>Transport</option></select><select name='idAmbulance' class='swal2-select'><?php foreach($ambulances as $a): ?><option value='<?= $a['idAmbulance'] ?>'><?= $a['immatriculation'] ?></option><?php endforeach; ?></select><input name='lieuDepart' placeholder='Départ' class='swal2-input'><input name='lieuArrivee' placeholder='Arrivée' class='swal2-input'><input type='datetime-local' name='dateDebut' class='swal2-input'></form>`,
    preConfirm: ()=>document.getElementById('f-c').submit()
  });
}
function editMission(m){
  Swal.fire({
    title: 'Modifier Mission',
    html: `<form id='f-e' method='POST'><input type='hidden' name='action' value='update'><input type='hidden' name='id' value='${m.idMission}'><select name='typeMission' class='swal2-select'><option ${m.typeMission==='Urgence'?'selected':''}>Urgence</option><option ${m.typeMission==='Transport'?'selected':''}>Transport</option></select><input name='lieuDepart' value='${m.lieuDepart}' class='swal2-input'><input name='lieuArrivee' value='${m.lieuArrivee}' class='swal2-input'></form>`,
    preConfirm: ()=>document.getElementById('f-e').submit()
  });
}
</script>
</body>
</html>
