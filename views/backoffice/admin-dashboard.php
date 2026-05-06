<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();
$dashboardData = $adminController->dashboard();

$success_message = $_SESSION['success_message'] ?? null;
$error_message   = $_SESSION['error_message']   ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$stats       = $dashboardData['stats']       ?? [];
$recentUsers = $dashboardData['recentUsers'] ?? [];

$totalUsers   = $stats['total']          ?? 0;
$newThisMonth = $stats['new_this_month'] ?? 0;
$roleStats    = $stats['by_role']        ?? [];
$statusStats  = $stats['by_status']      ?? [];

$activeUsers   = 0;
$inactiveUsers = 0;
foreach ($statusStats as $s) {
    if ($s['statut'] === 'actif')   $activeUsers   = $s['count'];
    if ($s['statut'] === 'inactif') $inactiveUsers = $s['count'];
}

$adminCount = 0;
$userCount  = 0;
foreach ($roleStats as $r) {
    if ($r['role'] === 'admin') $adminCount = $r['count'];
    if ($r['role'] === 'user')  $userCount  = $r['count'];
}

$activePct = $totalUsers > 0 ? round($activeUsers / $totalUsers * 100) : 0;

function fmtDate($d) {
    $dt  = new DateTime($d);
    $now = new DateTime();
    $diff = $now->diff($dt);
    if ($diff->days == 0)  return "Aujourd'hui " . $dt->format('H:i');
    if ($diff->days == 1)  return "Hier " . $dt->format('H:i');
    if ($diff->days < 7)   return "Il y a {$diff->days}j";
    return $dt->format('d/m/Y');
}
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — MedChain Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ── Reset & Tokens ── */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --green:#1D9E75;--green-dark:#0F6E56;--green-glow:rgba(29,158,117,.18);
  --navy:#1E3A52;--navy-deep:#0F172A;
  --blue:#3B82F6;--amber:#F59E0B;--rose:#EF4444;--purple:#8B5CF6;
  --gray-50:#F8FAFC;--gray-100:#F1F5F9;--gray-200:#E2E8F0;
  --gray-400:#94A3B8;--gray-500:#64748B;--gray-700:#374151;
  --white:#fff;
  --r-sm:10px;--r-md:14px;--r-lg:20px;--r-xl:28px;
  --sh-sm:0 1px 4px rgba(0,0,0,.07);
  --sh-md:0 4px 20px rgba(0,0,0,.08);
  --sh-lg:0 12px 48px rgba(0,0,0,.10);
  --sh-green:0 8px 32px rgba(29,158,117,.20);
}

body{font-family:'DM Sans',sans-serif;background:#f0f7f4;min-height:100vh;overflow-x:hidden;color:var(--gray-700)}

/* ── Layout ── */
.layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh}

/* ── Sidebar ── */
.sidebar{background:linear-gradient(180deg,var(--navy) 0%,var(--navy-deep) 100%);position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto;z-index:100}
.sidebar-logo{padding:24px 20px 20px;border-bottom:1px solid rgba(255,255,255,.08)}
.sidebar-logo a{display:flex;align-items:center;gap:10px;text-decoration:none}
.logo-box{width:38px;height:38px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:var(--r-md);display:flex;align-items:center;justify-content:center;box-shadow:var(--sh-green);flex-shrink:0}
.logo-box i{font-size:19px;color:#fff}
.logo-name{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#fff}
.logo-name em{color:var(--green);font-style:normal}

.sidebar-admin{display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.06)}
.admin-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;flex-shrink:0}
.admin-avatar i{font-size:16px;color:#fff}
.admin-info{flex:1;min-width:0}
.admin-name{font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.admin-role{font-size:11px;color:var(--gray-400)}
.admin-status{width:8px;height:8px;border-radius:50%;background:#22C55E;flex-shrink:0}

.nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:2px}
.nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.2px;color:#475569;padding:12px 8px 6px;font-weight:700}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#94A3B8;text-decoration:none;border-radius:var(--r-sm);transition:all .25s;font-size:13.5px;font-weight:500;position:relative}
.nav-item i{font-size:17px;width:22px;flex-shrink:0}
.nav-item:hover{background:rgba(255,255,255,.08);color:#fff}
.nav-item.active{background:rgba(29,158,117,.18);color:var(--green)}
.nav-item.active::before{content:'';position:absolute;left:0;top:25%;bottom:25%;width:3px;background:var(--green);border-radius:0 3px 3px 0}
.nav-item .badge{margin-left:auto;background:var(--rose);color:#fff;font-size:10px;padding:2px 7px;border-radius:20px;font-weight:700}
.nav-item.logout{color:#F87171;margin-top:4px}
.nav-item.logout:hover{background:rgba(248,113,113,.1)}

/* ── Main ── */
.main{display:flex;flex-direction:column;min-height:100vh;overflow:hidden}

/* ── Topbar ── */
.topbar{background:#fff;border-bottom:1px solid var(--gray-200);padding:0 36px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:var(--sh-sm)}
.topbar-left h1{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--navy)}
.topbar-left p{font-size:12px;color:var(--gray-500);margin-top:1px}
.topbar-right{display:flex;align-items:center;gap:10px}
.topbar-btn{width:38px;height:38px;border-radius:var(--r-sm);border:1.5px solid var(--gray-200);background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;color:var(--gray-500);text-decoration:none;position:relative}
.topbar-btn:hover{border-color:var(--green);color:var(--green)}
.topbar-btn .dot{position:absolute;top:6px;right:6px;width:7px;height:7px;border-radius:50%;background:var(--rose);border:2px solid #fff}
.topbar-profile{display:flex;align-items:center;gap:8px;padding:6px 12px;border-radius:var(--r-md);border:1.5px solid var(--gray-200);text-decoration:none;transition:all .2s}
.topbar-profile:hover{border-color:var(--green)}
.topbar-profile-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center}
.topbar-profile-avatar i{font-size:14px;color:#fff}
.topbar-profile-name{font-size:13px;font-weight:600;color:var(--navy)}

/* ── Content ── */
.content{flex:1;padding:28px 36px;overflow-y:auto}

/* ── Alert ── */
.alert{padding:13px 18px;border-radius:var(--r-md);margin-bottom:20px;display:flex;align-items:center;gap:12px;animation:slideIn .3s ease}
@keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.alert-success{background:#F0FDF4;border-left:4px solid #22C55E;color:#166534}
.alert-error{background:#FEF2F2;border-left:4px solid var(--rose);color:#991B1B}
.alert-close{margin-left:auto;background:none;border:none;font-size:18px;cursor:pointer;opacity:.5}

/* ── Section title ── */
.section-title{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.8px;margin-bottom:14px;margin-top:28px;display:flex;align-items:center;gap:8px}
.section-title::after{content:'';flex:1;height:1px;background:var(--gray-200)}

/* ── KPI Row ── */
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}

.kpi{background:#fff;border-radius:var(--r-lg);padding:20px 22px;border:1px solid var(--gray-200);box-shadow:var(--sh-sm);transition:all .3s;position:relative;overflow:hidden}
.kpi::after{content:'';position:absolute;bottom:-20px;right:-20px;width:80px;height:80px;border-radius:50%;opacity:.06}
.kpi.c-green::after{background:var(--green)}
.kpi.c-blue::after{background:var(--blue)}
.kpi.c-amber::after{background:var(--amber)}
.kpi.c-rose::after{background:var(--rose)}
.kpi:hover{transform:translateY(-3px);box-shadow:var(--sh-md)}

.kpi-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px}
.kpi-icon{width:44px;height:44px;border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:20px}
.kpi.c-green .kpi-icon{background:rgba(29,158,117,.1);color:var(--green)}
.kpi.c-blue  .kpi-icon{background:rgba(59,130,246,.1);color:var(--blue)}
.kpi.c-amber .kpi-icon{background:rgba(245,158,11,.1);color:var(--amber)}
.kpi.c-rose  .kpi-icon{background:rgba(239,68,68,.1);color:var(--rose)}

.kpi-trend{display:flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px}
.kpi-trend.up{background:#F0FDF4;color:#16A34A}
.kpi-trend.down{background:#FEF2F2;color:#DC2626}
.kpi-trend.neutral{background:var(--gray-100);color:var(--gray-500)}

.kpi-val{font-family:'Syne',sans-serif;font-size:32px;font-weight:700;color:var(--navy);line-height:1;margin-bottom:4px}
.kpi-lbl{font-size:13px;color:var(--gray-500);font-weight:500}
.kpi-sub{font-size:11px;color:var(--gray-400);margin-top:6px}

/* ── Progress bar in KPI ── */
.kpi-bar{height:4px;background:var(--gray-100);border-radius:4px;margin-top:14px;overflow:hidden}
.kpi-bar-fill{height:100%;border-radius:4px;transition:width 1.2s cubic-bezier(.4,0,.2,1)}
.kpi.c-green .kpi-bar-fill{background:linear-gradient(90deg,var(--green),#34D399)}
.kpi.c-blue  .kpi-bar-fill{background:linear-gradient(90deg,var(--blue),#60A5FA)}
.kpi.c-amber .kpi-bar-fill{background:linear-gradient(90deg,var(--amber),#FCD34D)}
.kpi.c-rose  .kpi-bar-fill{background:linear-gradient(90deg,var(--rose),#F87171)}

/* ── Middle row ── */
.mid-row{display:grid;grid-template-columns:1fr 340px;gap:16px;margin-top:16px}

/* ── Card ── */
.card{background:#fff;border-radius:var(--r-lg);border:1px solid var(--gray-200);box-shadow:var(--sh-sm);overflow:hidden}
.card-head{padding:18px 22px 14px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--gray-100)}
.card-head h2{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:8px}
.card-head h2 i{color:var(--green)}
.card-body{padding:20px 22px}

/* ── Table ── */
.tbl{width:100%;border-collapse:collapse}
.tbl th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400);padding:0 12px 10px;text-align:left}
.tbl td{padding:12px;border-top:1px solid var(--gray-100);vertical-align:middle;font-size:13.5px}
.tbl tr:hover td{background:var(--gray-50)}
.tbl-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0}
.tbl-user{display:flex;align-items:center;gap:10px}
.tbl-name{font-weight:600;color:var(--navy);font-size:13.5px}
.tbl-email{font-size:11px;color:var(--gray-400)}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge-green{background:#F0FDF4;color:#16A34A}
.badge-red{background:#FEF2F2;color:#DC2626}
.badge-blue{background:#EFF6FF;color:#2563EB}
.badge-amber{background:#FFFBEB;color:#D97706}

/* ── Role Donut widget ── */
.donut-wrap{position:relative;width:160px;height:160px;margin:0 auto}
.donut-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
.donut-center-val{font-family:'Syne',sans-serif;font-size:26px;font-weight:700;color:var(--navy);line-height:1}
.donut-center-lbl{font-size:11px;color:var(--gray-400);margin-top:2px}
.legend{display:flex;flex-direction:column;gap:10px;margin-top:20px}
.legend-item{display:flex;align-items:center;justify-content:space-between}
.legend-left{display:flex;align-items:center;gap:8px}
.legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.legend-lbl{font-size:13px;color:var(--gray-700)}
.legend-val{font-size:13px;font-weight:700;color:var(--navy)}
.legend-pct{font-size:11px;color:var(--gray-400);margin-left:4px}

/* ── Activity feed ── */
.feed{display:flex;flex-direction:column;gap:0}
.feed-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--gray-100);position:relative}
.feed-item:last-child{border-bottom:none}
.feed-icon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.feed-icon.fi-green{background:rgba(29,158,117,.12);color:var(--green)}
.feed-icon.fi-blue{background:rgba(59,130,246,.12);color:var(--blue)}
.feed-icon.fi-rose{background:rgba(239,68,68,.12);color:var(--rose)}
.feed-icon.fi-amber{background:rgba(245,158,11,.12);color:var(--amber)}
.feed-text{flex:1;min-width:0}
.feed-msg{font-size:13px;color:var(--gray-700);font-weight:500}
.feed-msg strong{color:var(--navy)}
.feed-time{font-size:11px;color:var(--gray-400);margin-top:2px}

/* ── Bottom row ── */
.bot-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}

/* ── Quick actions ── */
.qa-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.qa{display:flex;align-items:center;gap:14px;padding:16px;border-radius:var(--r-md);border:1.5px solid var(--gray-200);text-decoration:none;transition:all .25s;background:#fff}
.qa:hover{border-color:var(--green);background:rgba(29,158,117,.03);transform:translateY(-2px);box-shadow:var(--sh-sm)}
.qa-icon{width:42px;height:42px;border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;transition:all .25s}
.qa:hover .qa-icon{transform:scale(1.08)}
.qa-icon.q-green{background:rgba(29,158,117,.1);color:var(--green)}
.qa-icon.q-blue{background:rgba(59,130,246,.1);color:var(--blue)}
.qa-icon.q-amber{background:rgba(245,158,11,.1);color:var(--amber)}
.qa-icon.q-purple{background:rgba(139,92,246,.1);color:var(--purple)}
.qa-label{font-size:13.5px;font-weight:600;color:var(--navy)}
.qa-desc{font-size:11px;color:var(--gray-400);margin-top:1px}

/* ── Health bar ── */
.health-row{display:flex;flex-direction:column;gap:14px}
.health-item{}
.health-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.health-name{font-size:13px;font-weight:600;color:var(--navy)}
.health-pct{font-size:13px;font-weight:700}
.health-bar{height:8px;background:var(--gray-100);border-radius:4px;overflow:hidden}
.health-fill{height:100%;border-radius:4px;transition:width 1.4s cubic-bezier(.4,0,.2,1)}

/* ── Btn ── */
.btn{padding:9px 18px;border-radius:var(--r-sm);font-size:13px;font-weight:600;cursor:pointer;transition:all .25s;border:none;display:inline-flex;align-items:center;gap:7px;text-decoration:none;font-family:'DM Sans',sans-serif}
.btn-primary{background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff;box-shadow:0 3px 12px rgba(29,158,117,.28)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(29,158,117,.38)}
.btn-ghost{background:transparent;border:1.5px solid var(--gray-200);color:var(--gray-700)}
.btn-ghost:hover{border-color:var(--green);color:var(--green)}
.btn-sm{padding:6px 13px;font-size:12px}

/* ── Responsive ── */
@media(max-width:1200px){.kpi-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:960px){
  .layout{grid-template-columns:1fr}
  .sidebar{display:none}
  .mid-row,.bot-row{grid-template-columns:1fr}
  .content{padding:20px 18px}
  .topbar{padding:0 18px}
}
@media(max-width:640px){.kpi-row{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="layout">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <a href="admin-dashboard.php">
      <div class="logo-box"><i class="bi bi-plus-square-fill"></i></div>
      <div class="logo-name">Med<em>Chain</em></div>
    </a>
  </div>

  <div class="sidebar-admin">
    <div class="admin-avatar"><i class="bi bi-person-fill"></i></div>
    <div class="admin-info">
      <div class="admin-name"><?= e(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Admin')) ?></div>
      <div class="admin-role">Administrateur</div>
    </div>
    <div class="admin-status"></div>
  </div>

  <nav class="nav">
    <div class="nav-label">Navigation</div>
    <a href="admin-dashboard.php"          class="nav-item active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="admin-users.php"              class="nav-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
    <a href="admin-create-user.php"        class="nav-item"><i class="bi bi-person-plus-fill"></i> Nouvel utilisateur</a>
    <a href="admin-reports-statistics.php" class="nav-item"><i class="bi bi-graph-up"></i> Statistiques</a>
    <div class="nav-label">Médical</div>
    <a href="admin-medecin.php" class="nav-item"><i class="bi bi-heart-pulse-fill"></i> Médecins</a>
    <a href="admin-patient.php" class="nav-item"><i class="bi bi-person-lines-fill"></i> Patients</a>
    <a href="admin-medical-dashboard.php" class="nav-item"><i class="bi bi-activity"></i> Suivi santé avancé</a>
    <div class="nav-label">Gestion</div>
    <a href="../frontoffice/auth/profile.php" class="nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
    <a href="admin-chatbot.php" class="nav-item"><i class="bi bi-robot"></i> Assistant IA</a>
    <a href="../../controllers/logout.php" class="nav-item logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
  </nav>
</aside>

<!-- ══ MAIN ══ -->
<div class="main">

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <h1>Tableau de bord</h1>
      <p><?= date('l d F Y', time()) ?></p>
    </div>
    <div class="topbar-right">
      <a href="admin-users.php" class="topbar-btn" title="Utilisateurs"><i class="bi bi-people-fill"></i></a>
      <a href="admin-reports-statistics.php" class="topbar-btn" title="Statistiques"><i class="bi bi-graph-up"></i></a>
      <a href="../frontoffice/auth/profile.php" class="topbar-profile">
        <div class="topbar-profile-avatar"><i class="bi bi-person-fill"></i></div>
        <span class="topbar-profile-name"><?= e($_SESSION['user_prenom'] ?? 'Admin') ?></span>
      </a>
    </div>
  </div>

  <!-- Content -->
  <div class="content">

    <?php if ($success_message): ?>
      <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i><?= e($success_message) ?><button class="alert-close">&times;</button></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
      <div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i><?= e($error_message) ?><button class="alert-close">&times;</button></div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="section-title"><i class="bi bi-bar-chart-fill" style="color:var(--green)"></i> Vue d'ensemble</div>
    <div class="kpi-row">

      <div class="kpi c-green">
        <div class="kpi-top">
          <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
          <div class="kpi-trend up"><i class="bi bi-arrow-up-short"></i><?= $newThisMonth ?> ce mois</div>
        </div>
        <div class="kpi-val" data-count="<?= $totalUsers ?>">0</div>
        <div class="kpi-lbl">Total utilisateurs</div>
        <div class="kpi-sub">Tous les comptes enregistrés</div>
        <div class="kpi-bar"><div class="kpi-bar-fill" style="width:0" data-w="100%"></div></div>
      </div>

      <div class="kpi c-blue">
        <div class="kpi-top">
          <div class="kpi-icon"><i class="bi bi-person-check-fill"></i></div>
          <div class="kpi-trend up"><i class="bi bi-arrow-up-short"></i><?= $activePct ?>%</div>
        </div>
        <div class="kpi-val" data-count="<?= $activeUsers ?>">0</div>
        <div class="kpi-lbl">Comptes actifs</div>
        <div class="kpi-sub">Utilisateurs connectables</div>
        <div class="kpi-bar"><div class="kpi-bar-fill" style="width:0" data-w="<?= $activePct ?>%"></div></div>
      </div>

      <div class="kpi c-amber">
        <div class="kpi-top">
          <div class="kpi-icon"><i class="bi bi-person-x-fill"></i></div>
          <?php $inPct = $totalUsers>0?round($inactiveUsers/$totalUsers*100):0; ?>
          <div class="kpi-trend <?= $inactiveUsers>0?'down':'neutral' ?>"><i class="bi bi-arrow-<?= $inactiveUsers>0?'down':'dash' ?>-short"></i><?= $inPct ?>%</div>
        </div>
        <div class="kpi-val" data-count="<?= $inactiveUsers ?>">0</div>
        <div class="kpi-lbl">Comptes inactifs</div>
        <div class="kpi-sub">À réactiver si besoin</div>
        <div class="kpi-bar"><div class="kpi-bar-fill" style="width:0" data-w="<?= $inPct ?>%"></div></div>
      </div>

      <div class="kpi c-rose">
        <div class="kpi-top">
          <div class="kpi-icon"><i class="bi bi-shield-lock-fill"></i></div>
          <div class="kpi-trend neutral"><i class="bi bi-dash-short"></i>Admins</div>
        </div>
        <div class="kpi-val" data-count="<?= $adminCount ?>">0</div>
        <div class="kpi-lbl">Administrateurs</div>
        <div class="kpi-sub">Accès complet au système</div>
        <?php $adPct = $totalUsers>0?round($adminCount/$totalUsers*100):0; ?>
        <div class="kpi-bar"><div class="kpi-bar-fill" style="width:0" data-w="<?= $adPct ?>%"></div></div>
      </div>

    </div><!-- /kpi-row -->

    <!-- Middle row -->
    <div class="section-title"><i class="bi bi-clock-history" style="color:var(--green)"></i> Activité récente</div>
    <div class="mid-row">

      <!-- Derniers utilisateurs -->
      <div class="card">
        <div class="card-head">
          <h2><i class="bi bi-people-fill"></i> Derniers utilisateurs inscrits</h2>
          <a href="admin-users.php" class="btn btn-ghost btn-sm"><i class="bi bi-eye"></i> Voir tout</a>
        </div>
        <div class="card-body" style="padding:0">
          <?php if (empty($recentUsers)): ?>
            <div style="text-align:center;padding:40px;color:var(--gray-400)"><i class="bi bi-people" style="font-size:36px;opacity:.4"></i><p style="margin-top:10px">Aucun utilisateur récent</p></div>
          <?php else: ?>
          <table class="tbl">
            <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Statut</th><th>Inscrit</th><th></th></tr></thead>
            <tbody>
              <?php foreach (array_slice($recentUsers,0,7) as $u): ?>
              <tr>
                <td>
                  <div class="tbl-user">
                    <div class="tbl-avatar"><?= strtoupper(substr($u['prenom']??'?',0,1)) ?></div>
                    <div><div class="tbl-name"><?= e($u['prenom'].' '.$u['nom']) ?></div><div class="tbl-email"><?= e($u['email']) ?></div></div>
                  </div>
                </td>
                <td>
                  <span class="badge <?= $u['role']==='admin'?'badge-red':'badge-blue' ?>">
                    <i class="bi <?= $u['role']==='admin'?'bi-shield-fill':'bi-person-fill' ?>"></i>
                    <?= ucfirst(e($u['role'])) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $u['statut']==='actif'?'badge-green':'badge-red' ?>">
                    <i class="bi <?= $u['statut']==='actif'?'bi-check-circle-fill':'bi-x-circle-fill' ?>"></i>
                    <?= ucfirst(e($u['statut'])) ?>
                  </span>
                </td>
                <td style="color:var(--gray-400);font-size:12px"><?= fmtDate($u['date_inscription']) ?></td>
                <td><a href="admin-edit.php?id=<?= $u['id_utilisateur'] ?>" class="btn btn-ghost btn-sm"><i class="bi bi-pencil-fill"></i></a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

      <!-- Répartition rôles -->
      <div class="card">
        <div class="card-head">
          <h2><i class="bi bi-pie-chart-fill"></i> Répartition</h2>
        </div>
        <div class="card-body">
          <div class="donut-wrap">
            <canvas id="roleChart" width="160" height="160"></canvas>
            <div class="donut-center">
              <div class="donut-center-val"><?= $totalUsers ?></div>
              <div class="donut-center-lbl">Total</div>
            </div>
          </div>
          <div class="legend">
            <?php
            $roleColors = ['admin'=>'#EF4444','user'=>'#1D9E75','medecin'=>'#3B82F6','patient'=>'#F59E0B'];
            foreach ($roleStats as $r):
              $col = $roleColors[$r['role']] ?? '#94A3B8';
              $pct = $totalUsers>0?round($r['count']/$totalUsers*100):0;
            ?>
            <div class="legend-item">
              <div class="legend-left">
                <div class="legend-dot" style="background:<?= $col ?>"></div>
                <span class="legend-lbl"><?= ucfirst(e($r['role'])) ?></span>
              </div>
              <div><span class="legend-val"><?= $r['count'] ?></span><span class="legend-pct"><?= $pct ?>%</span></div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Santé du système -->
          <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--gray-100)">
            <div style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px">Santé du système</div>
            <div class="health-row">
              <div class="health-item">
                <div class="health-top">
                  <span class="health-name">Taux d'activation</span>
                  <span class="health-pct" style="color:var(--green)"><?= $activePct ?>%</span>
                </div>
                <div class="health-bar"><div class="health-fill" style="width:0;background:linear-gradient(90deg,var(--green),#34D399)" data-w="<?= $activePct ?>%"></div></div>
              </div>
              <div class="health-item">
                <div class="health-top">
                  <span class="health-name">Croissance mensuelle</span>
                  <?php $gPct = $totalUsers>0?min(100,round($newThisMonth/$totalUsers*100)):0; ?>
                  <span class="health-pct" style="color:var(--blue)"><?= $gPct ?>%</span>
                </div>
                <div class="health-bar"><div class="health-fill" style="width:0;background:linear-gradient(90deg,var(--blue),#60A5FA)" data-w="<?= $gPct ?>%"></div></div>
              </div>
              <div class="health-item">
                <div class="health-top">
                  <span class="health-name">Admins / Total</span>
                  <span class="health-pct" style="color:var(--rose)"><?= $adPct ?>%</span>
                </div>
                <div class="health-bar"><div class="health-fill" style="width:0;background:linear-gradient(90deg,var(--rose),#F87171)" data-w="<?= $adPct ?>%"></div></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /mid-row -->

    <!-- Bottom row -->
    <div class="section-title"><i class="bi bi-lightning-fill" style="color:var(--green)"></i> Actions rapides &amp; Activité</div>
    <div class="bot-row">

      <!-- Quick actions -->
      <div class="card">
        <div class="card-head"><h2><i class="bi bi-grid-3x3-gap-fill"></i> Actions rapides</h2></div>
        <div class="card-body">
          <div class="qa-grid">
            <a href="admin-create-user.php" class="qa">
              <div class="qa-icon q-green"><i class="bi bi-person-plus-fill"></i></div>
              <div><div class="qa-label">Créer un utilisateur</div><div class="qa-desc">Ajouter un nouveau compte</div></div>
            </a>
            <a href="admin-users.php" class="qa">
              <div class="qa-icon q-blue"><i class="bi bi-people-fill"></i></div>
              <div><div class="qa-label">Gérer les comptes</div><div class="qa-desc"><?= $totalUsers ?> utilisateurs</div></div>
            </a>
            <a href="admin-reports-statistics.php" class="qa">
              <div class="qa-icon q-amber"><i class="bi bi-graph-up"></i></div>
              <div><div class="qa-label">Voir statistiques</div><div class="qa-desc">Rapports &amp; analyses</div></div>
            </a>
            <a href="admin-export-excel.php" class="qa">
              <div class="qa-icon q-purple"><i class="bi bi-file-earmark-excel-fill"></i></div>
              <div><div class="qa-label">Exporter Excel</div><div class="qa-desc">Télécharger les données</div></div>
            </a>
            <a href="admin-export-pdf.php" class="qa">
              <div class="qa-icon q-rose" style="background:rgba(239,68,68,.1);color:var(--rose)"><i class="bi bi-file-earmark-pdf-fill"></i></div>
              <div><div class="qa-label">Exporter PDF</div><div class="qa-desc">Rapport imprimable</div></div>
            </a>
            <a href="../frontoffice/auth/profile.php" class="qa">
              <div class="qa-icon q-green"><i class="bi bi-person-circle"></i></div>
              <div><div class="qa-label">Mon profil</div><div class="qa-desc">Paramètres du compte</div></div>
            </a>
          </div>
        </div>
      </div>

      <!-- Activity feed -->
      <div class="card">
        <div class="card-head"><h2><i class="bi bi-activity"></i> Dernières activités</h2></div>
        <div class="card-body" style="padding:8px 22px">
          <div class="feed">
            <?php if (!empty($recentUsers)): ?>
              <?php foreach (array_slice($recentUsers,0,6) as $u): ?>
              <div class="feed-item">
                <div class="feed-icon fi-green"><i class="bi bi-person-plus-fill"></i></div>
                <div class="feed-text">
                  <div class="feed-msg"><strong><?= e($u['prenom'].' '.$u['nom']) ?></strong> a rejoint en tant que <em><?= e($u['role']) ?></em></div>
                  <div class="feed-time"><i class="bi bi-clock" style="font-size:10px"></i> <?= fmtDate($u['date_inscription']) ?></div>
                </div>
                <span class="badge <?= $u['statut']==='actif'?'badge-green':'badge-red' ?>" style="align-self:center;flex-shrink:0"><?= ucfirst(e($u['statut'])) ?></span>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div style="text-align:center;padding:32px;color:var(--gray-400)">
                <i class="bi bi-activity" style="font-size:32px;opacity:.3"></i>
                <p style="margin-top:10px;font-size:13px">Aucune activité récente</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div><!-- /bot-row -->

  </div><!-- /content -->
</div><!-- /main -->
</div><!-- /layout -->

<script>
/* ── Count-up animation ── */
document.querySelectorAll('.kpi-val[data-count]').forEach(el => {
  const target = parseInt(el.dataset.count);
  if (!target) { el.textContent = '0'; return; }
  let start = 0;
  const step = Math.ceil(target / 40);
  const timer = setInterval(() => {
    start = Math.min(start + step, target);
    el.textContent = start.toLocaleString('fr-FR');
    if (start >= target) clearInterval(timer);
  }, 30);
});

/* ── Bar width animation ── */
setTimeout(() => {
  document.querySelectorAll('[data-w]').forEach(el => {
    el.style.width = el.dataset.w;
  });
}, 200);

/* ── Donut chart ── */
const roleCtx = document.getElementById('roleChart');
if (roleCtx) {
  const data = {
    labels: [<?php
      $lbls = []; $vals = []; $cols = [];
      foreach($roleStats as $r){
        $lbls[] = '"'.ucfirst($r['role']).'"';
        $vals[] = $r['count'];
        $col = ['admin'=>'"#EF4444"','user'=>'"#1D9E75"','medecin'=>'"#3B82F6"','patient'=>'"#F59E0B"'];
        $cols[] = $col[$r['role']] ?? '"#94A3B8"';
      }
      echo implode(',',$lbls);
    ?>],
    datasets:[{
      data:[<?= implode(',',$vals) ?>],
      backgroundColor:[<?= implode(',',$cols) ?>],
      borderWidth:0,
      hoverOffset:6
    }]
  };
  new Chart(roleCtx,{
    type:'doughnut',
    data,
    options:{
      cutout:'72%',
      plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>' '+ctx.label+': '+ctx.raw}}},
      animation:{animateRotate:true,duration:1000}
    }
  });
}

/* ── Alert close ── */
document.querySelectorAll('.alert-close').forEach(b => b.addEventListener('click', () => b.closest('.alert').remove()));
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => { a.style.opacity='0'; a.style.transform='translateY(-8px)'; a.style.transition='all .3s'; setTimeout(()=>a.remove(),300); });
}, 5000);
</script>
</body>
</html>