<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();
$dashboardData   = $adminController->dashboard();
$stats           = $dashboardData['stats'] ?? [];
$recentUsers     = $dashboardData['recentUsers'] ?? [];
$totalUsers      = $stats['total'] ?? 0;
$newThisMonth    = $stats['new_this_month'] ?? 0;
$statusStats     = $stats['by_status'] ?? [];
$activeUsers = $inactiveUsers = 0;
foreach ($statusStats as $s) {
    if ($s['statut']==='actif')   $activeUsers   = $s['count'];
    if ($s['statut']==='inactif') $inactiveUsers = $s['count'];
}
$success_message = $_SESSION['success_message'] ?? null;
$error_message   = $_SESSION['error_message']   ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tableau de Bord Admin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ══ RESET ══ */
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --green:#1D9E75;--green-dark:#0F6E56;--green-light:rgba(29,158,117,.12);
  --navy:#0F172A;--navy-2:#1E293B;--navy-3:#334155;
  --text:#E2E8F0;--muted:#64748B;
  --white:#fff;--bg:#f0faf6;
  --red:#EF4444;--blue:#3B82F6;--amber:#F59E0B;--purple:#8B5CF6;
  --radius:12px;--radius-lg:20px;
  --shadow:0 2px 8px rgba(0,0,0,.12);
  --sidebar-w:260px;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;}

/* ══ LAYOUT ══ */
.layout{display:flex;min-height:100vh;}

/* ══ SIDEBAR ══ */
.sidebar{width:var(--sidebar-w);background:var(--navy);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;transition:width .3s;overflow:hidden;}
.sidebar-logo{padding:20px 18px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0;}
.logo-box{width:34px;height:34px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff;flex-shrink:0;}
.logo-text{font-family:'Syne',sans-serif;font-size:19px;font-weight:700;color:#fff;white-space:nowrap;}
.logo-text span{color:var(--green);}
.sidebar-nav{flex:1;overflow-y:auto;padding:10px 10px 20px;}
.sidebar-nav::-webkit-scrollbar{width:3px;}
.sidebar-nav::-webkit-scrollbar-thumb{background:var(--navy-3);border-radius:3px;}

/* ── Nav items ── */
.nav-group{margin-bottom:4px;}
.nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.2px;color:var(--muted);padding:14px 12px 6px;font-weight:600;}
.nav-item{display:flex;align-items:center;gap:11px;padding:10px 12px;color:#94A3B8;text-decoration:none;border-radius:var(--radius);font-size:13.5px;font-weight:500;transition:all .2s;cursor:pointer;position:relative;white-space:nowrap;}
.nav-item i{font-size:17px;width:20px;flex-shrink:0;transition:color .2s;}
.nav-item:hover{background:rgba(255,255,255,.07);color:#fff;}
.nav-item.active{background:var(--green-light);color:var(--green);}
.nav-item.active i{color:var(--green);}
.nav-item.logout{color:#F87171;}
.nav-item.logout:hover{background:rgba(248,113,113,.1);}

/* ── Dropdown ── */
.nav-dropdown{position:relative;}
.nav-dropdown-toggle .chevron{margin-left:auto;font-size:12px;transition:transform .25s;}
.nav-dropdown.open .chevron{transform:rotate(180deg);}
.nav-sub{display:none;overflow:hidden;padding-left:12px;}
.nav-dropdown.open .nav-sub{display:block;}
.nav-sub-item{display:flex;align-items:center;gap:10px;padding:8px 12px;color:#64748B;text-decoration:none;border-radius:var(--radius);font-size:13px;font-weight:500;transition:all .2s;border-left:2px solid transparent;}
.nav-sub-item:hover{color:var(--green);border-left-color:var(--green);background:rgba(29,158,117,.06);}
.nav-sub-item.active{color:var(--green);border-left-color:var(--green);}
.nav-sub-item i{font-size:14px;}

/* ══ MAIN ══ */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;}

/* ── Topbar ── */
.topbar{background:var(--white);border-bottom:1px solid #E5E7EB;padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 1px 4px rgba(0,0,0,.05);}
.topbar-left h1{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#1E293B;}
.topbar-left p{font-size:12px;color:var(--muted);}
.topbar-right{display:flex;align-items:center;gap:14px;}
.topbar-avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;}
.topbar-notif{width:36px;height:36px;border-radius:50%;background:#F1F5F9;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:17px;cursor:pointer;transition:all .2s;}
.topbar-notif:hover{background:var(--green-light);color:var(--green);}

/* ── Content ── */
.content{padding:28px 32px;flex:1;}

/* ── Stats ── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:28px;}
.stat-card{background:var(--white);border-radius:var(--radius-lg);padding:20px 22px;display:flex;align-items:center;gap:16px;border:1px solid #E5E7EB;box-shadow:var(--shadow);transition:all .25s;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1);}
.stat-icon{width:50px;height:50px;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.si-green{background:rgba(29,158,117,.12);color:var(--green);}
.si-blue{background:rgba(59,130,246,.12);color:var(--blue);}
.si-amber{background:rgba(245,158,11,.12);color:var(--amber);}
.si-red{background:rgba(239,68,68,.12);color:var(--red);}
.si-purple{background:rgba(139,92,246,.12);color:var(--purple);}
.stat-info h3{font-size:26px;font-weight:800;color:#1E293B;line-height:1;}
.stat-info p{font-size:12px;color:var(--muted);margin-top:3px;}

/* ── Cards ── */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.card{background:var(--white);border-radius:var(--radius-lg);border:1px solid #E5E7EB;box-shadow:var(--shadow);overflow:hidden;}
.card-header{padding:16px 22px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;}
.card-header h2{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#1E293B;display:flex;align-items:center;gap:8px;}
.card-header h2 i{color:var(--green);}
.card-body{padding:18px 22px;}

/* ── Table ── */
.tbl{width:100%;border-collapse:collapse;}
.tbl th{background:#F8FAFC;padding:10px 14px;text-align:left;font-size:12px;font-weight:600;color:var(--muted);border-bottom:1px solid #E5E7EB;text-transform:uppercase;letter-spacing:.5px;}
.tbl td{padding:12px 14px;border-bottom:1px solid #F1F5F9;font-size:13.5px;color:#334155;}
.tbl tr:last-child td{border:none;}
.tbl tr:hover td{background:#FAFCFF;}

/* ── Badge ── */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-green{background:#DCFCE7;color:#16A34A;}
.badge-red{background:#FEF2F2;color:#DC2626;}
.badge-blue{background:#DBEAFE;color:#1D4ED8;}
.badge-amber{background:#FEF3C7;color:#92400E;}
.badge-gray{background:#F1F5F9;color:#475569;}

/* ── Quick actions ── */
.actions-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;}
.action-btn{background:var(--white);border:1px solid #E5E7EB;border-radius:var(--radius-lg);padding:18px;display:flex;flex-direction:column;align-items:center;gap:10px;text-decoration:none;color:#334155;font-size:13px;font-weight:600;transition:all .25s;cursor:pointer;}
.action-btn:hover{border-color:var(--green);color:var(--green);transform:translateY(-2px);box-shadow:0 4px 16px rgba(29,158,117,.15);}
.action-btn i{font-size:26px;}

/* ── Alerts ── */
.flash{padding:13px 18px;border-radius:var(--radius);margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;}
.flash-success{background:#F0FDF4;border-left:4px solid #22C55E;color:#166534;}
.flash-error{background:#FEF2F2;border-left:4px solid var(--red);color:#991B1B;}

/* ── Welcome banner ── */
.welcome{background:linear-gradient(135deg,#0F6E56,#1D9E75);border-radius:var(--radius-lg);padding:24px 28px;color:#fff;display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;box-shadow:0 6px 24px rgba(29,158,117,.3);}
.welcome h2{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;margin-bottom:4px;}
.welcome p{font-size:13px;opacity:.85;}
.welcome-icon{font-size:56px;opacity:.18;}

@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(2,1fr);}.actions-row{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.sidebar{width:0;}.main{margin-left:0;}.content{padding:16px;}.grid-2{grid-template-columns:1fr;}.stats-grid{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>
<div class="layout">

  <!-- ══ SIDEBAR ══ -->
  <aside class="sidebar" id="sidebar">
    <a href="admin-dashboard.php" class="sidebar-logo">
      <div class="logo-box"><i class="bi bi-plus-square-fill"></i></div>
      <span class="logo-text">Med<span>Chain</span></span>
    </a>
    <nav class="sidebar-nav">
      <div class="nav-label">Principal</div>

      <a href="admin-dashboard.php" class="nav-item active"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="gestion-utilisateurs.php" class="nav-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
      <a href="admin-reports-statistics.php" class="nav-item"><i class="bi bi-graph-up-arrow"></i> Statistiques</a>

      <div class="nav-label">Modules</div>

      <!-- Flotte & Missions dropdown -->
      <div class="nav-dropdown" id="dd-flotte">
        <div class="nav-item nav-dropdown-toggle" onclick="toggleDD('dd-flotte')">
          <i class="bi bi-truck-front-fill"></i> Flotte &amp; Missions
          <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="nav-sub">
          <a href="ambulances/admin-index.php" class="nav-sub-item"><i class="bi bi-truck-front"></i> Ambulances</a>
          <a href="missions/admin-index.php" class="nav-sub-item"><i class="bi bi-geo-alt"></i> Missions</a>
        </div>
      </div>

      <!-- Bloc Opératoire dropdown -->
      <div class="nav-dropdown" id="dd-bloc">
        <div class="nav-item nav-dropdown-toggle" onclick="toggleDD('dd-bloc')">
          <i class="bi bi-hospital"></i> Bloc Opératoire
          <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="nav-sub">
          <a href="#" class="nav-sub-item"><i class="bi bi-scissors"></i> Interventions</a>
          <a href="#" class="nav-sub-item"><i class="bi bi-bag-plus"></i> Matériel</a>
        </div>
      </div>

      <a href="#" class="nav-item"><i class="bi bi-shield-check"></i> Traçabilité</a>
      <a href="rendezvous/admin-index.php" class="nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
      <a href="#" class="nav-item"><i class="bi bi-balloon-heart"></i> Loisir</a>

      <div class="nav-label">Compte</div>
      <a href="../frontoffice/auth/profile.php" class="nav-item"><i class="bi bi-person-circle"></i> Mon Profil</a>
      <a href="../../controllers/logout.php" class="nav-item logout" id="logoutBtn"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </nav>
  </aside>

  <!-- ══ MAIN ══ -->
  <div class="main">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <h1>Tableau de Bord Admin</h1>
        <p><?= date('l d F Y') ?></p>
      </div>
      <div class="topbar-right">
        <div class="topbar-notif"><i class="bi bi-bell"></i></div>
        <div class="topbar-avatar">A</div>
      </div>
    </header>

    <!-- Content -->
    <div class="content">

      <?php if($success_message): ?>
      <div class="flash flash-success" id="flashMsg"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success_message) ?></div>
      <?php endif; ?>
      <?php if($error_message): ?>
      <div class="flash flash-error" id="flashMsg"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>

      <!-- Welcome -->
      <div class="welcome">
        <div>
          <h2>Bienvenue, Administrateur 👋</h2>
          <p>Gérez l'ensemble de la plateforme MedChain depuis ce tableau de bord.</p>
        </div>
        <i class="bi bi-shield-lock-fill welcome-icon"></i>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon si-green"><i class="bi bi-people-fill"></i></div>
          <div class="stat-info"><h3><?= $totalUsers ?></h3><p>Total utilisateurs</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-blue"><i class="bi bi-person-check"></i></div>
          <div class="stat-info"><h3><?= $activeUsers ?></h3><p>Comptes actifs</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-amber"><i class="bi bi-person-plus"></i></div>
          <div class="stat-info"><h3><?= $newThisMonth ?></h3><p>Nouveaux ce mois</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-red"><i class="bi bi-person-x"></i></div>
          <div class="stat-info"><h3><?= $inactiveUsers ?></h3><p>Comptes inactifs</p></div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="actions-row">
        <a href="gestion-utilisateurs.php" class="action-btn"><i class="bi bi-person-plus-fill" style="color:var(--green);"></i>Nouvel utilisateur</a>
        <a href="ambulances/admin-index.php" class="action-btn"><i class="bi bi-truck-front-fill" style="color:var(--blue);"></i>Gérer flotte</a>
        <a href="missions/admin-index.php" class="action-btn"><i class="bi bi-geo-alt-fill" style="color:var(--amber);"></i>Missions</a>
        <a href="admin-reports-statistics.php" class="action-btn"><i class="bi bi-graph-up-arrow" style="color:var(--purple);"></i>Statistiques</a>
      </div>

      <!-- Recent users table + Role chart -->
      <div class="grid-2">
        <div class="card">
          <div class="card-header">
            <h2><i class="bi bi-clock-history"></i> Derniers utilisateurs</h2>
            <a href="gestion-utilisateurs.php" style="font-size:13px;color:var(--green);text-decoration:none;">Voir tout →</a>
          </div>
          <div class="card-body" style="padding:0;">
            <table class="tbl">
              <thead><tr><th>Nom</th><th>Rôle</th><th>Statut</th></tr></thead>
              <tbody>
              <?php foreach(array_slice($recentUsers,0,6) as $u): ?>
              <tr>
                <td><strong><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></strong><br><small style="color:var(--muted);"><?= htmlspecialchars($u['email']) ?></small></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($u['role']) ?></span></td>
                <td><span class="badge <?= $u['statut']==='actif'?'badge-green':'badge-red' ?>"><?= $u['statut'] ?></span></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h2><i class="bi bi-pie-chart"></i> Répartition des rôles</h2></div>
          <div class="card-body">
            <?php foreach(($stats['by_role']??[]) as $r): ?>
            <div style="margin-bottom:14px;">
              <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px;">
                <span style="font-weight:600;color:#334155;text-transform:capitalize;"><?= htmlspecialchars($r['role']) ?></span>
                <span style="color:var(--muted);"><?= $r['count'] ?> / <?= $totalUsers ?></span>
              </div>
              <div style="background:#F1F5F9;border-radius:6px;height:8px;overflow:hidden;">
                <div style="background:linear-gradient(90deg,var(--green),var(--green-dark));height:100%;width:<?= $totalUsers>0?round($r['count']/$totalUsers*100):0 ?>%;border-radius:6px;transition:width .6s;"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /layout -->

<script>
/* ── Dropdown toggle ── */
function toggleDD(id){
  const el=document.getElementById(id);
  el.classList.toggle('open');
}

/* ── Logout SweetAlert ── */
document.getElementById('logoutBtn').addEventListener('click',function(e){
  e.preventDefault(); const href=this.href;
  Swal.fire({title:'Déconnexion',text:'Êtes-vous sûr de vouloir vous déconnecter ?',icon:'question',showCancelButton:true,confirmButtonColor:'#1D9E75',cancelButtonColor:'#6B7280',confirmButtonText:'Oui, déconnecter',cancelButtonText:'Annuler'})
  .then(r=>{if(r.isConfirmed)window.location.href=href;});
});

/* ── Auto-dismiss flash ── */
setTimeout(()=>{const f=document.getElementById('flashMsg');if(f){f.style.transition='opacity .5s';f.style.opacity='0';setTimeout(()=>f.remove(),500);}},4000);
</script>
</body>
</html>