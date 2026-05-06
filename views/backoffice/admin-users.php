<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();
$usersResult = $adminController->getAllUsers();
$allUsers = $usersResult['success'] ? $usersResult['users'] : [];

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$statut_filter = $_GET['statut'] ?? '';

$users = $allUsers;

if ($search || $role_filter || $statut_filter) {
    $users = array_filter($allUsers, function($user) use ($search, $role_filter, $statut_filter) {
        $match_search = true; $match_role = true; $match_statut = true;
        if ($search) {
            $s = strtolower(trim($search));
            $match_search = strpos(strtolower($user['nom']??''), $s) !== false
                         || strpos(strtolower($user['prenom']??''), $s) !== false
                         || strpos(strtolower($user['email']??''), $s) !== false;
        }
        if ($role_filter)   $match_role   = ($user['role']   ?? '') === $role_filter;
        if ($statut_filter) $match_statut = ($user['statut'] ?? '') === $statut_filter;
        return $match_search && $match_role && $match_statut;
    });
    $users = array_values($users);
}

function escape_data($data) { return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des utilisateurs - MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;
  --gray-700:#374151;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#fff;
  --shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 16px rgba(0,0,0,.08);
  --shadow-lg:0 12px 40px rgba(0,0,0,.10);--shadow-green:0 8px 30px rgba(29,158,117,.22);
  --radius-md:12px;--radius-lg:20px;--radius-xl:28px;
}
body{font-family:'DM Sans',sans-serif;background:linear-gradient(145deg,#f0faf6,#e8f7f1,#ddf3ea);min-height:100vh;overflow-x:hidden}
.dashboard-container{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
/* SIDEBAR */
.dashboard-sidebar{background:linear-gradient(180deg,var(--navy) 0%,#0F172A 100%);position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto}
.dashboard-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:20px}
.dashboard-logo a{display:flex;align-items:center;gap:10px;text-decoration:none}
.dashboard-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center}
.dashboard-logo-icon i{font-size:18px;color:#fff}
.dashboard-logo-text{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#fff}
.dashboard-logo-text span{color:var(--green)}
.dashboard-nav{flex:1;display:flex;flex-direction:column;gap:4px;padding:0 12px}
.dashboard-nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#94A3B8;text-decoration:none;border-radius:var(--radius-md);transition:all .3s;font-size:14px;font-weight:500}
.dashboard-nav-item i{font-size:18px;width:24px}
.dashboard-nav-item:hover{background:rgba(255,255,255,.1);color:#fff}
.dashboard-nav-item.active{background:rgba(29,158,117,.2);color:var(--green)}
.dashboard-nav-item.logout{margin-top:auto;margin-bottom:20px;color:#F87171}
.dashboard-nav-item.logout:hover{background:rgba(248,113,113,.1)}
.dashboard-nav-title{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748B;padding:16px 16px 8px;font-weight:600}
/* MAIN */
.dashboard-main{padding:32px 40px;overflow-y:auto}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:16px}
.page-header h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:var(--navy)}
/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:20px;margin-bottom:28px}
.stat-card{background:var(--white);border-radius:var(--radius-lg);padding:20px;border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);transition:all .3s}
.stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.stat-card h3{font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:var(--navy)}
.stat-card p{color:var(--gray-500);font-size:13px;margin-top:4px}
/* FILTER */
.filter-form{background:var(--white);border-radius:var(--radius-lg);padding:20px 24px;margin-bottom:28px;border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm)}
.filter-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;align-items:end}
.form-group{margin-bottom:0}
label{display:block;margin-bottom:8px;color:var(--navy);font-weight:600;font-size:13px}
.form-control{width:100%;padding:10px 12px;border:2px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:'DM Sans',sans-serif;transition:all .3s;background:#fff}
.form-control:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px rgba(29,158,117,.15)}
/* ALERTS */
.alert{padding:14px 18px;border-radius:var(--radius-md);margin-bottom:24px;display:flex;align-items:center;gap:12px;animation:slideIn .3s ease}
@keyframes slideIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.alert-success{background:#F0FDF4;border-left:4px solid #22C55E;color:#166534}
.alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C}
.alert-close{margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;opacity:.6}
/* TABLE */
.table-card{background:var(--white);border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);border:1px solid rgba(29,158,117,.15);overflow:hidden}
.table-responsive{overflow-x:auto}
.table{width:100%;border-collapse:collapse}
.table th{background:#F8FAFC;padding:14px 16px;text-align:left;font-weight:600;color:#64748B;border-bottom:1px solid var(--gray-200);font-size:13px}
.table td{padding:16px;border-bottom:1px solid var(--gray-200);vertical-align:middle}
.table tr:last-child td{border-bottom:none}
.table tr:hover td{background:#F8FAFC}
/* BADGES */
.role-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
.role-badge.admin{background:#FEF2F2;color:#EF4444}
.role-badge.user{background:#F0FDF4;color:#22C55E}
.status-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
.status-badge.actif{background:#F0FDF4;color:#22C55E}
.status-badge.inactif{background:#FEF2F2;color:#EF4444}
/* BUTTONS */
.btn{padding:8px 16px;border-radius:var(--radius-md);font-size:13px;font-weight:600;cursor:pointer;transition:all .3s;border:none;display:inline-flex;align-items:center;gap:6px;text-decoration:none;font-family:'DM Sans',sans-serif}
.btn-lg{padding:12px 24px;font-size:14px}
.btn-primary{background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff;box-shadow:0 3px 12px rgba(29,158,117,.30)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(29,158,117,.40)}
.btn-outline{background:transparent;border:2px solid var(--gray-200);color:var(--gray-700)}
.btn-outline:hover{border-color:var(--green);color:var(--green)}
/* RESPONSIVE */
@media(max-width:900px){.dashboard-container{grid-template-columns:1fr}.dashboard-sidebar{display:none}.dashboard-main{padding:20px 16px}}
@media(max-width:640px){.stats-grid{grid-template-columns:repeat(2,1fr)}.filter-row{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="dashboard-container">

  <!-- SIDEBAR -->
  <aside class="dashboard-sidebar">
    <div class="dashboard-logo">
      <a href="admin-dashboard.php">
        <div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
        <div class="dashboard-logo-text">Med<span>Chain</span></div>
      </a>
    </div>
    <nav class="dashboard-nav">
      <div class="dashboard-nav-title">Navigation</div>
      <a href="admin-dashboard.php" class="dashboard-nav-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="admin-users.php"     class="dashboard-nav-item active"><i class="bi bi-people-fill"></i> Utilisateurs</a>
      <a href="admin-create-user.php" class="dashboard-nav-item"><i class="bi bi-person-plus-fill"></i> Nouvel utilisateur</a>
      <a href="admin-reports-statistics.php" class="dashboard-nav-item"><i class="bi bi-graph-up"></i> Statistiques</a>
      
      <div class="dashboard-nav-title">Médical</div>
      <a href="admin-medecin.php" class="dashboard-nav-item"><i class="bi bi-heart-pulse-fill"></i> Médecins</a>
      <a href="admin-patient.php" class="dashboard-nav-item"><i class="bi bi-person-lines-fill"></i> Patients</a>
      <a href="admin-medical-dashboard.php" class="dashboard-nav-item"><i class="bi bi-activity"></i> Suivi santé avancé</a>
      
      <div class="dashboard-nav-title">Gestion</div>
      <a href="admin-chatbot.php" class="dashboard-nav-item"><i class="bi bi-robot"></i> Assistant IA</a>
      <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
      <a href="../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="dashboard-main">

    <div class="page-header">
      <h1><i class="bi bi-people-fill"></i> Gestion des utilisateurs</h1>
      <a href="admin-create-user.php" class="btn btn-primary btn-lg"><i class="bi bi-person-plus-fill"></i> Nouvel utilisateur</a>
    </div>

    <?php if ($success_message): ?>
      <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success_message) ?></div><button class="alert-close">&times;</button></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
      <div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i><div><?= htmlspecialchars($error_message) ?></div><button class="alert-close">&times;</button></div>
    <?php endif; ?>

    <div class="stats-grid">
      <div class="stat-card"><h3><?= count($allUsers) ?></h3><p>Total utilisateurs</p></div>
      <div class="stat-card"><h3><?= count(array_filter($allUsers, fn($u) => $u['statut'] === 'actif')) ?></h3><p>Utilisateurs actifs</p></div>
      <div class="stat-card"><h3><?= count(array_filter($allUsers, fn($u) => $u['statut'] === 'inactif')) ?></h3><p>Utilisateurs inactifs</p></div>
      <div class="stat-card"><h3><?= count(array_filter($allUsers, fn($u) => $u['role'] === 'admin')) ?></h3><p>Administrateurs</p></div>
    </div>

    <div class="filter-form">
      <form method="GET" action="">
        <div class="filter-row">
          <div class="form-group">
            <label><i class="bi bi-search"></i> Recherche</label>
            <input type="text" name="search" class="form-control" value="<?= escape_data($search) ?>" placeholder="Nom, prénom ou email">
          </div>
          <div class="form-group">
            <label><i class="bi bi-person-badge"></i> Rôle</label>
            <select name="role" class="form-control">
              <option value="">Tous</option>
              <option value="user"  <?= $role_filter==='user'  ? 'selected':'' ?>>Utilisateur</option>
              <option value="admin" <?= $role_filter==='admin' ? 'selected':'' ?>>Administrateur</option>
            </select>
          </div>
          <div class="form-group">
            <label><i class="bi bi-toggle-on"></i> Statut</label>
            <select name="statut" class="form-control">
              <option value="">Tous</option>
              <option value="actif"   <?= $statut_filter==='actif'   ? 'selected':'' ?>>Actif</option>
              <option value="inactif" <?= $statut_filter==='inactif' ? 'selected':'' ?>>Inactif</option>
            </select>
          </div>
          <div class="form-group">
            <button type="submit" class="btn btn-primary" style="width:100%;padding:10px 12px"><i class="bi bi-funnel-fill"></i> Filtrer</button>
          </div>
        </div>
      </form>
    </div>

    <div class="table-card">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th>
              <th>Rôle</th><th>Statut</th><th>Date inscription</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-500)">Aucun utilisateur trouvé</td></tr>
            <?php else: ?>
              <?php foreach ($users as $user): ?>
              <tr>
                <td>#<?= $user['id_utilisateur'] ?></td>
                <td><strong><?= escape_data($user['nom']) ?></strong></td>
                <td><?= escape_data($user['prenom']) ?></td>
                <td><?= escape_data($user['email']) ?></td>
                <td><span class="role-badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                <td><span class="status-badge <?= $user['statut'] ?>"><?= ucfirst($user['statut']) ?></span></td>
                <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                <td>
                  <a href="admin-edit.php?id=<?= $user['id_utilisateur'] ?>" class="btn btn-primary">
                    <i class="bi bi-pencil-fill"></i> Modifier
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<script>
document.querySelectorAll('.alert-close').forEach(btn => btn.addEventListener('click', () => btn.closest('.alert').remove()));
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => {
    a.style.opacity='0'; a.style.transform='translateY(-10px)'; a.style.transition='all .3s';
    setTimeout(() => a.remove(), 300);
  });
}, 5000);
</script>
</body>
</html>