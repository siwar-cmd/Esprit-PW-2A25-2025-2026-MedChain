<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}
if (!defined('APP_ENTRY_URL')) {
    define('APP_ENTRY_URL', '/projet/index1.php');
}
if (!function_exists('routeUrl')) {
    function routeUrl(string $controller = 'objet', string $action = 'list', array $params = []): string
    {
        $query = array_merge(['office' => $params['office'] ?? 'front', 'controller' => $controller, 'action' => $action], $params);
        return APP_ENTRY_URL . '?' . http_build_query($query);
    }
}

$isLoggedIn  = isset($_SESSION['user_id']);
$userRole    = $_SESSION['user_role'] ?? null;
$userName    = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$currentCtrl = $_GET['controller'] ?? 'objet';
$currentAct  = $_GET['action']     ?? 'list';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedChain — Objets Loisir</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --green:      #1D9E75;
            --green-dark: #0F6E56;
            --green-light:#E8F7F2;
            --green-pale: #F2FBF7;
            --navy:       #1E3A52;
            --gray-700:   #374151;
            --gray-500:   #6B7280;
            --gray-200:   #E5E7EB;
            --white:      #ffffff;
            --shadow-sm:  0 1px 3px rgba(0,0,0,.08);
            --shadow-md:  0 4px 16px rgba(0,0,0,.08);
            --radius-sm:  8px;
            --radius-md:  12px;
            --radius-lg:  20px;
            --radius-xl:  28px;
        }

        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            color: var(--gray-700);
            background-color: #f4f9f6;
            line-height: 1.65;
            margin: 0;
        }
        a { text-decoration: none; color: inherit; }

        /* ── Topbar ── */
        .mc-topbar {
            background: #0b7a5a;
            padding: 7px 0;
            font-size: 12.5px;
            color: rgba(255,255,255,.85);
        }
        .mc-topbar .inner {
            max-width: 1240px; margin: 0 auto; padding: 0 28px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .tb-left { display: flex; gap: 20px; align-items: center; }
        .tb-left span { display: flex; align-items: center; gap: 6px; }
        .tb-left a { color: rgba(255,255,255,.85); }
        .tb-left a:hover { color: #fff; }

        /* ── Navbar ── */
        #mc-header {
            background: var(--white);
            border-bottom: 1px solid rgba(0,0,0,.06);
            position: sticky;
            top: 0;
            z-index: 500;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }
        .mc-branding {
            max-width: 1240px; margin: 0 auto; padding: 0 28px;
            display: flex; align-items: center; justify-content: space-between;
            height: 66px; gap: 20px;
        }

        /* Logo */
        .mc-logo { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .mc-logo .mc-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            display: flex; align-items: center; justify-content: center;
        }
        .mc-logo .mc-icon i { color: #fff; font-size: 18px; }
        .mc-logo .mc-wordmark {
            font-family: 'Syne', sans-serif; font-size: 20px;
            font-weight: 700; color: var(--navy);
        }
        .mc-logo .mc-wordmark span { color: var(--green); }

        /* Nav links */
        .mc-nav { display: flex; align-items: center; gap: 4px; flex: 1; justify-content: center; }
        .mc-nav a {
            font-size: 14px; font-weight: 500; color: var(--gray-500);
            padding: 7px 16px; border-radius: var(--radius-sm);
            transition: all .2s; display: inline-flex; align-items: center; gap: 6px;
        }
        .mc-nav a:hover { color: var(--green); background: rgba(29,158,117,.07); }
        .mc-nav a.active { color: var(--green); background: rgba(29,158,117,.10); font-weight: 600; }

        /* Actions */
        .mc-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

        .btn-outline-mc {
            font-size: 13.5px; font-weight: 500; padding: 8px 18px;
            border-radius: var(--radius-sm); border: 1.5px solid rgba(0,0,0,.15);
            background: transparent; color: var(--navy); cursor: pointer;
            transition: all .2s; display: inline-flex; align-items: center;
        }
        .btn-outline-mc:hover { border-color: var(--green); color: var(--green); }

        .btn-solid-mc {
            font-size: 13.5px; font-weight: 600; padding: 8px 20px;
            border-radius: var(--radius-sm);
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: #fff; border: none; cursor: pointer; transition: all .25s;
            display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 3px 10px rgba(29,158,117,.28);
        }
        .btn-solid-mc:hover { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(29,158,117,.38); color: #fff; }

        /* User dropdown */
        .mc-user-menu {
            position: relative; display: inline-flex; align-items: center;
            gap: 8px; cursor: pointer; padding: 6px 12px;
            border-radius: var(--radius-md); transition: background .2s;
        }
        .mc-user-menu:hover { background: rgba(29,158,117,.08); }
        .mc-user-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 16px; flex-shrink: 0;
        }
        .mc-user-name { font-size: 14px; font-weight: 500; color: var(--navy); }

        .mc-dropdown {
            position: absolute; top: calc(100% + 6px); right: 0;
            background: var(--white); border-radius: var(--radius-md);
            box-shadow: 0 12px 40px rgba(0,0,0,.12);
            min-width: 210px; display: none; z-index: 600;
            border: 1px solid var(--gray-200); overflow: hidden;
        }
        .mc-user-menu:hover .mc-dropdown { display: block; }
        .mc-dropdown a {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 16px; color: var(--gray-700); font-size: 13.5px;
            transition: background .15s;
        }
        .mc-dropdown a:hover { background: var(--green-pale); color: var(--green); }
        .mc-dropdown-divider { height: 1px; background: var(--gray-200); margin: 4px 0; }

        /* ── Page content components ── */

        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(29,158,117,.12);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--gray-200);
            display: flex; justify-content: space-between;
            align-items: center; flex-wrap: wrap; gap: 12px;
            background: var(--white);
        }
        .card-title {
            font-family: 'Syne', sans-serif;
            font-size: 17px; font-weight: 700; color: var(--navy);
            display: flex; align-items: center; gap: 8px;
        }
        .card-title i { color: var(--green); }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: var(--radius-md);
            font-size: 13.5px; font-weight: 600; cursor: pointer;
            transition: all .25s; border: none; text-decoration: none;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: #fff; box-shadow: 0 3px 10px rgba(29,158,117,.25);
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(29,158,117,.35); color: #fff; }
        .btn-secondary { background: #f1f5f9; color: var(--navy); box-shadow: none; }
        .btn-secondary:hover { background: var(--gray-200); color: var(--navy); transform: none; box-shadow: none; }
        .btn-success { background: linear-gradient(135deg,#22C55E,#16A34A); box-shadow: 0 3px 10px rgba(34,197,94,.25); }
        .btn-success:hover { box-shadow: 0 5px 16px rgba(34,197,94,.35); color: #fff; }
        .btn-danger  { background: linear-gradient(135deg,#EF4444,#DC2626); box-shadow: 0 3px 10px rgba(239,68,68,.25); }
        .btn-danger:hover  { box-shadow: 0 5px 16px rgba(239,68,68,.35); color: #fff; }

        /* Alerts */
        .alert {
            padding: 13px 18px; border-radius: var(--radius-md);
            margin-bottom: 18px; display: flex; align-items: center;
            gap: 10px; font-size: 14px;
        }
        .alert-success { background: #f0fdf4; border-left: 4px solid #22C55E; color: #166534; }
        .alert-error   { background: #fef2f2; border-left: 4px solid #EF4444; color: #b91c1c; }

        /* Status badges */
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-disponible   { background: #f0fdf4; color: #16A34A; }
        .status-indisponible { background: #fef2f2; color: #DC2626; }
        .status-en_attente   { background: #fff7ed; color: #C2410C; }
        .status-en_cours     { background: #eff6ff; color: #1D4ED8; }
        .status-termine      { background: #f0fdf4; color: #16A34A; }
        .status-annule       { background: #f1f5f9; color: #64748B; }
        .status-en_retard    { background: #fef2f2; color: #DC2626; }

        /* Tables */
        .table { width: 100%; border-collapse: collapse; }
        .table th {
            background: #f8fafc; padding: 11px 16px; text-align: left;
            font-weight: 600; color: var(--gray-500); font-size: 12.5px;
            border-bottom: 1px solid var(--gray-200);
            text-transform: uppercase; letter-spacing: .04em;
        }
        .table td { padding: 13px 16px; border-bottom: 1px solid var(--gray-200); font-size: 14px; vertical-align: middle; }
        .table tr:last-child td { border-bottom: none; }
        .table tbody tr:hover td { background: #f8fafc; }

        /* Forms */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13.5px; font-weight: 600; color: var(--navy); margin-bottom: 6px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 10px 14px;
            border: 1.5px solid var(--gray-200); border-radius: var(--radius-md);
            font-size: 14px; font-family: inherit; color: var(--navy);
            background: #fff; transition: border-color .2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none; border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(29,158,117,.1);
        }
        .form-control {
            width: 100%; padding: 10px 14px;
            border: 1.5px solid var(--gray-200); border-radius: var(--radius-md);
            font-size: 14px; font-family: inherit; color: var(--navy); background: #fff;
        }
        .form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,.1); }
        .form-error { color: #DC2626; font-size: 12px; margin-top: 4px; }
        .grid    { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; padding: 24px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; padding: 0 24px 24px; }

        /* Object cards grid */
        .objects-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(270px,1fr)); gap: 20px; padding: 24px; }
        .object-card { background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); overflow: hidden; transition: all .3s; display: flex; flex-direction: column; }
        .object-card:hover { border-color: rgba(29,158,117,.4); box-shadow: 0 12px 36px rgba(29,158,117,.12); transform: translateY(-4px); }
        .object-card-header { background: linear-gradient(135deg,var(--green-light),#d4f0e8); padding: 24px; display: flex; align-items: center; justify-content: center; }
        .object-card-icon { width: 60px; height: 60px; border-radius: var(--radius-lg); background: linear-gradient(135deg,var(--green),var(--green-dark)); display: flex; align-items: center; justify-content: center; }
        .object-card-icon i { font-size: 26px; color: white; }
        .object-card-body { padding: 18px 20px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .object-card-title { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; color: var(--navy); }
        .object-card-meta { font-size: 13px; color: var(--gray-500); display: flex; align-items: center; gap: 6px; }
        .object-card-meta i { color: var(--green); }
        .object-card-footer { padding: 14px 20px; border-top: 1px solid var(--gray-200); display: flex; gap: 10px; }

        /* Stats mini cards */
        .stats-mini { display: grid; grid-template-columns: repeat(auto-fit,minmax(130px,1fr)); gap: 14px; margin-bottom: 24px; }
        .stat-mini-card { background: var(--white); border-radius: var(--radius-lg); padding: 16px; display: flex; align-items: center; gap: 12px; border: 1px solid rgba(29,158,117,.12); box-shadow: var(--shadow-sm); }

        @media (max-width: 768px) {
            .mc-nav { display: none; }
            .objects-grid { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="mc-topbar">
    <div class="inner">
        <div class="tb-left">
            <span><i class="bi bi-envelope" style="font-size:12px;"></i>
                <a href="mailto:contact@medchain.com">contact@medchain.com</a>
            </span>
            <span><i class="bi bi-telephone" style="font-size:12px;"></i> +216 71 000 000</span>
        </div>
    </div>
</div>

<!-- Navbar -->
<header id="mc-header">
    <div class="mc-branding">

        <!-- Logo -->
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="mc-logo">
            <div class="mc-icon"><i class="bi bi-plus-square-fill"></i></div>
            <div class="mc-wordmark">Med<span>Chain</span></div>
        </a>

        <!-- Nav links -->
        <nav class="mc-nav">
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
               class="<?php echo $currentCtrl === 'objet' ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> Catalogue
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'myLoans', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
               class="<?php echo ($currentCtrl === 'pret' && $currentAct === 'myLoans') ? 'active' : ''; ?>">
                <i class="bi bi-bookmark-check"></i> Mes prêts
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('chat', 'index', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
               class="<?php echo $currentCtrl === 'chat' ? 'active' : ''; ?>">
                <i class="bi bi-robot"></i> Assistant IA
            </a>
        </nav>

        <!-- Actions -->
        <div class="mc-actions">
            <?php if ($isLoggedIn): ?>
                <?php
                $notifModel    = new NotificationController();
                $unreadCount   = $notifModel->countUnread((int) $_SESSION['user_id']);
                $recentNotifs  = $notifModel->getRecent((int) $_SESSION['user_id'], 5);
                ?>

                <!-- Bell icon -->
                <div class="mc-bell-menu" style="position:relative;">
                    <button class="mc-bell-btn" id="bellBtn"
                            style="position:relative;background:none;border:none;cursor:pointer;
                                   padding:8px;border-radius:var(--radius-md);transition:background .2s;"
                            onmouseover="this.style.background='rgba(29,158,117,.08)'"
                            onmouseout="this.style.background='none'">
                        <i class="bi bi-bell-fill" style="font-size:20px;color:var(--navy);"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span style="position:absolute;top:4px;right:4px;min-width:18px;height:18px;
                                         background:#EF4444;color:#fff;border-radius:50%;font-size:10px;
                                         font-weight:700;display:flex;align-items:center;justify-content:center;
                                         padding:0 4px;line-height:1;">
                                <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <!-- Bell dropdown -->
                    <div id="bellDropdown"
                         style="display:none;position:absolute;top:calc(100% + 8px);right:0;
                                background:#fff;border-radius:var(--radius-lg);min-width:300px;
                                box-shadow:0 12px 40px rgba(0,0,0,.14);border:1px solid var(--gray-200);
                                z-index:700;overflow:hidden;">

                        <div style="padding:14px 18px;border-bottom:1px solid var(--gray-200);
                                    display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;
                                         color:var(--navy);">Notifications</span>
                            <?php if ($unreadCount > 0): ?>
                                <a href="<?php echo htmlspecialchars(routeUrl('notification', 'markRead', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
                                   style="font-size:12px;color:var(--green);font-weight:600;">
                                    Tout marquer lu
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($recentNotifs)): ?>
                            <div style="padding:24px;text-align:center;color:var(--gray-500);font-size:13px;">
                                <i class="bi bi-bell-slash" style="font-size:28px;opacity:.3;display:block;margin-bottom:8px;"></i>
                                Aucune notification
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentNotifs as $notif): ?>
                                <div style="padding:12px 18px;border-bottom:1px solid var(--gray-200);
                                            background:<?php echo $notif['is_read'] ? '#fff' : '#f0fdf4'; ?>;
                                            display:flex;gap:10px;align-items:flex-start;">
                                    <div style="width:8px;height:8px;border-radius:50%;margin-top:5px;flex-shrink:0;
                                                background:<?php echo $notif['is_read'] ? 'transparent' : '#1D9E75'; ?>;"></div>
                                    <div style="flex:1;">
                                        <p style="margin:0;font-size:13px;color:var(--navy);line-height:1.5;">
                                            <?php echo htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                        <span style="font-size:11px;color:var(--gray-500);">
                                            <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($notif['date_creation'])), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mc-user-menu">
                    <div class="mc-user-avatar"><i class="bi bi-person-fill"></i></div>
                    <span class="mc-user-name"><?php echo htmlspecialchars($userName ?: 'Utilisateur', ENT_QUOTES, 'UTF-8'); ?></span>
                    <i class="bi bi-chevron-down" style="font-size:11px;color:var(--gray-500);"></i>
                    <div class="mc-dropdown">
                        <a href="/projet/views/frontoffice/auth/profile.php">
                            <i class="bi bi-person-circle"></i> Mon profil
                        </a>
                        <?php if ($userRole === 'admin'): ?>
                            <a href="<?php echo htmlspecialchars(routeUrl('admin', 'dashboard', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bi bi-speedometer2"></i> Administration
                            </a>
                        <?php endif; ?>
                        <div class="mc-dropdown-divider"></div>
                        <a href="/projet/controllers/logout.php"
                           onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/projet/views/frontoffice/auth/login.php" class="btn-outline-mc">Connexion</a>
                <a href="/projet/views/frontoffice/auth/register.php" class="btn-solid-mc">
                    <i class="bi bi-person-plus" style="font-size:13px;"></i> Inscription
                </a>
            <?php endif; ?>
        </div>

    </div>
</header>

<!-- Main content -->
<main class="container my-5" style="min-height:70vh;">
<script>
(function(){
    var btn = document.getElementById('bellBtn');
    var dd  = document.getElementById('bellDropdown');
    if (!btn || !dd) return;
    btn.addEventListener('click', function(e){
        e.stopPropagation();
        dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', function(){ dd.style.display = 'none'; });
    dd.addEventListener('click', function(e){ e.stopPropagation(); });
})();
</script>
