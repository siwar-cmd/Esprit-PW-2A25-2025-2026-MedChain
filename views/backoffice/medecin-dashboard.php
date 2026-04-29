<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../controllers/AmbulanceMissionController.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$auth  = new AuthController();
$user  = $auth->getCurrentUser();
$userName = $user ? $user->getPrenom().' '.$user->getNom() : 'Médecin';
$initials = $user ? strtoupper(substr($user->getPrenom(),0,1).substr($user->getNom(),0,1)) : 'DR';

$ctrl         = new AmbulanceMissionController();
$ambStats     = $ctrl->getAmbulanceStats();
$missionStats = $ctrl->getMissionStats();
$today        = date('l d F Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Espace Médecin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --teal:#1D9E75;--teal-dark:#0F6E56;--teal-light:rgba(29,158,117,.1);--teal-pale:#f0faf6;
  --slate:#1E3A52;--text:#374151;--muted:#6B7280;--border:#E5E7EB;
  --white:#fff;--bg:#f5fbf8;
  --blue:#3B82F6;--amber:#F59E0B;--purple:#8B5CF6;--rose:#F43F5E;
  --radius:12px;--radius-lg:20px;--radius-xl:28px;
  --shadow:0 1px 4px rgba(0,0,0,.07);--shadow-md:0 4px 16px rgba(0,0,0,.09);
  --sidebar-w:240px;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;}

/* ══ LAYOUT ══ */
.layout{display:flex;min-height:100vh;}

/* ══ SIDEBAR – lighter teal theme for médecin ══ */
.sidebar{
  width:var(--sidebar-w);
  background:linear-gradient(180deg,#0d2137 0%,#0F172A 100%);
  display:flex;flex-direction:column;
  position:fixed;top:0;left:0;height:100vh;z-index:100;
  border-right:1px solid rgba(29,158,117,.2);
}
.sidebar-logo{padding:20px 16px;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:10px;text-decoration:none;}
.logo-mark{width:32px;height:32px;background:linear-gradient(135deg,var(--teal),var(--teal-dark));border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;flex-shrink:0;}
.logo-txt{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:#fff;}
.logo-txt em{color:var(--teal);font-style:normal;}

/* ── User mini-card in sidebar ── */
.sidebar-user{margin:12px 10px;padding:10px 12px;background:rgba(29,158,117,.1);border-radius:var(--radius);border:1px solid rgba(29,158,117,.2);display:flex;align-items:center;gap:10px;}
.user-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-dark));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;}
.user-info p{font-size:12.5px;font-weight:600;color:#E2E8F0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;}
.user-info span{font-size:11px;color:var(--teal);font-weight:500;}

.sidebar-nav{flex:1;overflow-y:auto;padding:8px 10px 16px;}
.sidebar-nav::-webkit-scrollbar{width:3px;}
.sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px;}
.nav-section{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#475569;padding:12px 10px 5px;font-weight:600;}
.nav-link{display:flex;align-items:center;gap:10px;padding:9px 12px;color:#94A3B8;text-decoration:none;border-radius:var(--radius);font-size:13.5px;font-weight:500;transition:all .2s;white-space:nowrap;}
.nav-link i{font-size:16px;width:18px;flex-shrink:0;}
.nav-link:hover{background:rgba(255,255,255,.07);color:#fff;}
.nav-link.active{background:var(--teal-light);color:var(--teal);}
.nav-link.logout{color:#F87171;margin-top:4px;}
.nav-link.logout:hover{background:rgba(248,113,113,.1);}

/* ── Dropdown ── */
.nav-dropdown{position:relative;}
.dd-toggle{display:flex;align-items:center;gap:10px;padding:9px 12px;color:#94A3B8;border-radius:var(--radius);font-size:13.5px;font-weight:500;cursor:pointer;transition:all .2s;white-space:nowrap;}
.dd-toggle i:first-child{font-size:16px;width:18px;flex-shrink:0;}
.chevron{margin-left:auto;font-size:11px;transition:transform .25s;}
.nav-dropdown.open .chevron{transform:rotate(180deg);}
.dd-toggle:hover{background:rgba(255,255,255,.07);color:#fff;}
.dd-menu{display:none;padding-left:10px;}
.nav-dropdown.open .dd-menu{display:block;}
.dd-item{display:flex;align-items:center;gap:8px;padding:7px 12px;color:#64748B;text-decoration:none;border-radius:8px;font-size:13px;font-weight:500;transition:all .2s;border-left:2px solid transparent;}
.dd-item:hover{color:var(--teal);border-left-color:var(--teal);background:rgba(29,158,117,.06);}
.dd-item i{font-size:13px;}

/* ══ MAIN ══ */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;}

/* ── Topbar ── */
.topbar{background:var(--white);border-bottom:1px solid var(--border);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 1px 4px rgba(0,0,0,.04);}
.topbar h1{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--slate);}
.topbar p{font-size:12px;color:var(--muted);}
.topbar-right{display:flex;align-items:center;gap:12px;}
.topbar-chip{background:var(--teal-light);color:var(--teal);padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid rgba(29,158,117,.2);}

/* ── Content ── */
.content{padding:26px 28px;flex:1;}

/* ── Welcome card ── */
.welcome-card{background:linear-gradient(135deg,var(--teal-dark) 0%,var(--teal) 100%);border-radius:var(--radius-xl);padding:24px 28px;color:#fff;display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;box-shadow:0 6px 24px rgba(29,158,117,.28);}
.welcome-card h2{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;margin-bottom:4px;}
.welcome-card p{font-size:13px;opacity:.85;}
.welcome-card .wc-icon{font-size:52px;opacity:.18;}

/* ── Stats ── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.stat-box{background:var(--white);border-radius:var(--radius-lg);padding:18px;display:flex;align-items:center;gap:14px;border:1px solid var(--border);box-shadow:var(--shadow);transition:all .2s;}
.stat-box:hover{transform:translateY(-2px);box-shadow:var(--shadow-md);}
.stat-ic{width:44px;height:44px;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.ic-teal{background:rgba(29,158,117,.1);color:var(--teal);}
.ic-blue{background:rgba(59,130,246,.1);color:var(--blue);}
.ic-amber{background:rgba(245,158,11,.1);color:var(--amber);}
.ic-rose{background:rgba(244,63,94,.1);color:var(--rose);}
.stat-box h3{font-size:24px;font-weight:800;color:var(--slate);}
.stat-box p{font-size:11.5px;color:var(--muted);}

/* ── Module cards ── */
.modules-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:24px;}
.mod-card{background:var(--white);border-radius:var(--radius-xl);border:1px solid var(--border);box-shadow:var(--shadow);padding:22px 24px;display:flex;flex-direction:column;gap:14px;text-decoration:none;color:inherit;transition:all .25s;}
.mod-card:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(29,158,117,.13);border-color:var(--teal);}
.mod-header{display:flex;align-items:center;gap:14px;}
.mod-icon{width:48px;height:48px;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.mi-teal{background:rgba(29,158,117,.1);color:var(--teal);}
.mi-blue{background:rgba(59,130,246,.1);color:var(--blue);}
.mi-amber{background:rgba(245,158,11,.1);color:var(--amber);}
.mi-purple{background:rgba(139,92,246,.1);color:var(--purple);}
.mod-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--slate);}
.mod-desc{font-size:13px;color:var(--muted);line-height:1.5;}
.mod-footer{display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid var(--border);}
.ro-badge{background:#DCFCE7;color:#15803D;font-size:11.5px;font-weight:600;padding:4px 10px;border-radius:20px;display:flex;align-items:center;gap:4px;}
.mod-arrow{width:28px;height:28px;border-radius:50%;background:var(--teal);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;transition:transform .2s;}
.mod-card:hover .mod-arrow{transform:translateX(4px);}

@media(max-width:900px){.stats-row{grid-template-columns:repeat(2,1fr);}.modules-grid{grid-template-columns:1fr;}}
@media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}.content{padding:16px;}}
</style>
</head>
<body>
<div class="layout">

  <!-- ══ SIDEBAR ══ -->
  <aside class="sidebar">
    <a href="medecin-dashboard.php" class="sidebar-logo">
      <div class="logo-mark"><i class="bi bi-heart-pulse-fill"></i></div>
      <span class="logo-txt">Med<em>Chain</em></span>
    </a>

    <!-- User mini-card -->
    <div class="sidebar-user">
      <div class="user-avatar"><?= $initials ?></div>
      <div class="user-info">
        <p>Dr. <?= htmlspecialchars($userName) ?></p>
        <span>Médecin</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">Navigation</div>
      <a href="medecin-dashboard.php" class="nav-link active"><i class="bi bi-speedometer2"></i> Tableau de bord</a>
      <a href="rendezvous/medecin-index.php" class="nav-link"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
      <a href="ficherdv/medecin-index.php" class="nav-link"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>

      <div class="nav-section">Flotte &amp; Missions</div>

      <!-- Dropdown Flotte -->
      <div class="nav-dropdown" id="dd-flotte">
        <div class="dd-toggle" onclick="toggleDD('dd-flotte')">
          <i class="bi bi-truck-front-fill"></i> Flotte &amp; Missions
          <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="dd-menu">
          <a href="ambulances/medecin-index.php" class="dd-item"><i class="bi bi-truck-front"></i> Ambulances</a>
          <a href="missions/medecin-index.php" class="dd-item"><i class="bi bi-geo-alt"></i> Missions</a>
        </div>
      </div>

      <!-- Dropdown Bloc Opératoire -->
      <div class="nav-dropdown" id="dd-bloc">
        <div class="dd-toggle" onclick="toggleDD('dd-bloc')">
          <i class="bi bi-hospital"></i> Bloc Opératoire
          <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="dd-menu">
          <a href="#" class="dd-item"><i class="bi bi-scissors"></i> Interventions</a>
          <a href="#" class="dd-item"><i class="bi bi-bag-plus"></i> Matériel</a>
        </div>
      </div>

      <a href="#" class="nav-link"><i class="bi bi-shield-check"></i> Traçabilité</a>
      <a href="#" class="nav-link"><i class="bi bi-balloon-heart"></i> Loisir</a>

      <div class="nav-section">Compte</div>
      <a href="../frontoffice/auth/profile.php" class="nav-link"><i class="bi bi-person-circle"></i> Mon Profil</a>
      <a href="../../controllers/logout.php" class="nav-link logout" id="logoutBtn"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </nav>
  </aside>

  <!-- ══ MAIN ══ -->
  <div class="main">
    <header class="topbar">
      <div>
        <h1>Espace Médecin</h1>
        <p><?= $today ?></p>
      </div>
      <div class="topbar-right">
        <span class="topbar-chip"><i class="bi bi-eye"></i> Mode lecture</span>
      </div>
    </header>

    <div class="content">

      <!-- Welcome -->
      <div class="welcome-card">
        <div>
          <h2>Bonjour, Dr. <?= htmlspecialchars($userName) ?> 👋</h2>
          <p>Bienvenue sur votre espace médecin MedChain. Toutes vos informations en un coup d'œil.</p>
        </div>
        <i class="bi bi-heart-pulse-fill wc-icon"></i>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-box">
          <div class="stat-ic ic-teal"><i class="bi bi-truck-front"></i></div>
          <div><h3><?= $ambStats['total'] ?></h3><p>Ambulances</p></div>
        </div>
        <div class="stat-box">
          <div class="stat-ic ic-teal"><i class="bi bi-check-circle"></i></div>
          <div><h3><?= $ambStats['available'] ?></h3><p>Disponibles</p></div>
        </div>
        <div class="stat-box">
          <div class="stat-ic ic-amber"><i class="bi bi-arrow-repeat"></i></div>
          <div><h3><?= $missionStats['ongoing'] ?></h3><p>Missions en cours</p></div>
        </div>
        <div class="stat-box">
          <div class="stat-ic ic-blue"><i class="bi bi-geo-alt"></i></div>
          <div><h3><?= $missionStats['total'] ?></h3><p>Total missions</p></div>
        </div>
      </div>

      <!-- Modules -->
      <div class="modules-grid">
        <a href="rendezvous/medecin-index.php" class="mod-card">
          <div class="mod-header">
            <div class="mod-icon mi-teal"><i class="bi bi-calendar-check"></i></div>
            <div><div class="mod-title">Mes Rendez-vous</div></div>
          </div>
          <div class="mod-desc">Consultez vos consultations planifiées avec vos patients.</div>
          <div class="mod-footer">
            <span class="ro-badge"><i class="bi bi-eye"></i> Voir mes RDV</span>
            <div class="mod-arrow"><i class="bi bi-arrow-right"></i></div>
          </div>
        </a>

        <a href="ficherdv/medecin-index.php" class="mod-card">
          <div class="mod-header">
            <div class="mod-icon mi-blue"><i class="bi bi-file-earmark-medical"></i></div>
            <div><div class="mod-title">Fiches Médicales</div></div>
          </div>
          <div class="mod-desc">Accédez aux fiches de rendez-vous et consignes pré-consultation.</div>
          <div class="mod-footer">
            <span class="ro-badge"><i class="bi bi-eye"></i> Voir les fiches</span>
            <div class="mod-arrow"><i class="bi bi-arrow-right"></i></div>
          </div>
        </a>

        <a href="ambulances/medecin-index.php" class="mod-card">
          <div class="mod-header">
            <div class="mod-icon mi-amber"><i class="bi bi-truck-front-fill"></i></div>
            <div><div class="mod-title">Flotte Ambulances</div></div>
          </div>
          <div class="mod-desc">Consultez la disponibilité des ambulances et le statut de la flotte.</div>
          <div class="mod-footer">
            <span class="ro-badge"><i class="bi bi-eye"></i> Lecture seule</span>
            <div class="mod-arrow"><i class="bi bi-arrow-right"></i></div>
          </div>
        </a>

        <a href="missions/medecin-index.php" class="mod-card">
          <div class="mod-header">
            <div class="mod-icon mi-purple"><i class="bi bi-geo-alt-fill"></i></div>
            <div><div class="mod-title">Registre Missions</div></div>
          </div>
          <div class="mod-desc">Suivez les missions médicales en cours et l'historique des interventions.</div>
          <div class="mod-footer">
            <span class="ro-badge"><i class="bi bi-eye"></i> Lecture seule</span>
            <div class="mod-arrow"><i class="bi bi-arrow-right"></i></div>
          </div>
        </a>
      </div>

    </div>
  </div>
</div>

<script>
function toggleDD(id){
  document.getElementById(id).classList.toggle('open');
}
document.getElementById('logoutBtn').addEventListener('click',function(e){
  e.preventDefault(); const href=this.href;
  Swal.fire({title:'Déconnexion',text:'Êtes-vous sûr de vouloir vous déconnecter ?',icon:'question',showCancelButton:true,confirmButtonColor:'#1D9E75',cancelButtonColor:'#6B7280',confirmButtonText:'Oui, déconnecter',cancelButtonText:'Annuler'})
  .then(r=>{if(r.isConfirmed)window.location.href=href;});
});
</script>
</body>
</html>
