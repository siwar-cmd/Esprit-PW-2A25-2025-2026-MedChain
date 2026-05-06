<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /midchaine/views/frontoffice/auth/login.php');
    exit;
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}
if (!defined('APP_ENTRY_URL')) {
    define('APP_ENTRY_URL', '/midchaine/index1.php');
}
if (!function_exists('routeUrl')) {
    function routeUrl(string $controller = 'objet', string $action = 'list', array $params = []): string
    {
        $query = array_merge(['office' => $params['office'] ?? 'front', 'controller' => $controller, 'action' => $action], $params);
        return APP_ENTRY_URL . '?' . http_build_query($query);
    }
}

$currentController = $_GET['controller'] ?? 'admin';
$currentAction     = $_GET['action']     ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ── Root variables ── */
        :root {
            --green:      #1D9E75;
            --green-dark: #0F6E56;
            --green-light:#E8F7F2;
            --navy:       #1E3A52;
            --sidebar-bg: #0f172a;
            --sidebar-w:  260px;
            --gray-200:   #E5E7EB;
            --gray-500:   #6B7280;
            --white:      #ffffff;
            --radius-sm:  8px;
            --radius-md:  12px;
            --radius-lg:  18px;
            --radius-xl:  24px;
            --shadow-sm:  0 1px 4px rgba(0,0,0,.08);
            --shadow-md:  0 4px 18px rgba(0,0,0,.10);
        }

        /* ── Base ── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #edf4f0;
            color: var(--navy);
            margin: 0;
        }
        a { text-decoration: none; color: inherit; }

        /* ══════════════════════════════════════
           SIDEBAR
        ══════════════════════════════════════ */
        .mc-sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        /* Logo */
        .mc-sidebar-logo {
            padding: 22px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mc-sidebar-logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .mc-sidebar-logo-icon i { font-size: 18px; color: #fff; }
        .mc-sidebar-logo-text {
            font-family: 'Syne', sans-serif;
            font-size: 19px; font-weight: 700; color: #fff;
        }
        .mc-sidebar-logo-text span { color: var(--green); }

        /* Nav */
        .mc-sidebar-nav {
            flex: 1;
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .mc-nav-section {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 1.1px;
            color: #475569;
            font-weight: 700;
            padding: 14px 12px 5px;
        }
        .mc-nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            color: #94a3b8;
            font-size: 13.5px;
            font-weight: 500;
            transition: background .2s, color .2s;
            cursor: pointer;
        }
        .mc-nav-item i { font-size: 16px; width: 20px; flex-shrink: 0; }
        .mc-nav-item:hover { background: rgba(255,255,255,.07); color: #fff; }
        .mc-nav-item.active {
            background: rgba(29,158,117,.18);
            color: var(--green);
            font-weight: 600;
        }
        .mc-nav-item.active i { color: var(--green); }
        .mc-nav-item.logout { color: #f87171; }
        .mc-nav-item.logout:hover { background: rgba(248,113,113,.1); color: #fca5a5; }

        /* Sidebar footer / user */
        .mc-sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid rgba(255,255,255,.07);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mc-sidebar-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 15px; flex-shrink: 0;
        }
        .mc-sidebar-username { font-size: 13px; font-weight: 600; color: #e2e8f0; line-height: 1.3; }
        .mc-sidebar-role    { font-size: 11px; color: #475569; }

        /* ══════════════════════════════════════
           MAIN CONTENT
        ══════════════════════════════════════ */
        .mc-main {
            flex-grow: 1;
            background-color: #edf4f0;
            padding: 28px 32px;
            overflow-y: auto;
            min-height: 100vh;
        }

        /* ══════════════════════════════════════
           CONTENT COMPONENTS
        ══════════════════════════════════════ */

        /* White card wrapper */
        .card {
            background: var(--white);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(29,158,117,.12);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .card-header {
            padding: 16px 22px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            background: var(--white);
        }
        .card-title {
            font-family: 'Syne', sans-serif;
            font-size: 17px; font-weight: 700; color: var(--navy);
            display: flex; align-items: center; gap: 8px;
        }
        .card-title i { color: var(--green); }

        /* Table */
        .table { width: 100%; border-collapse: collapse; }
        .table th {
            background: #f8fafc;
            padding: 11px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--gray-500);
            font-size: 12.5px;
            border-bottom: 1px solid var(--gray-200);
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .table td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 13.5px;
            vertical-align: middle;
        }
        .table tr:last-child td { border-bottom: none; }
        .table tbody tr:hover td { background: #f8fafc; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all .2s;
            border: none; text-decoration: none;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: #fff;
            box-shadow: 0 2px 8px rgba(29,158,117,.25);
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(29,158,117,.35); color: #fff; }
        .btn-secondary { background: #f1f5f9; color: var(--navy); box-shadow: none; }
        .btn-secondary:hover { background: var(--gray-200); color: var(--navy); transform: none; box-shadow: none; }
        .btn-success { background: linear-gradient(135deg,#22C55E,#16A34A); box-shadow: 0 2px 8px rgba(34,197,94,.25); }
        .btn-success:hover { box-shadow: 0 4px 14px rgba(34,197,94,.35); color: #fff; }
        .btn-danger  { background: linear-gradient(135deg,#EF4444,#DC2626); box-shadow: 0 2px 8px rgba(239,68,68,.25); }
        .btn-danger:hover  { box-shadow: 0 4px 14px rgba(239,68,68,.35); color: #fff; }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 18px;
            display: flex; align-items: center; gap: 10px;
            font-size: 13.5px;
        }
        .alert-success { background: #f0fdf4; border-left: 4px solid #22C55E; color: #166534; }
        .alert-error   { background: #fef2f2; border-left: 4px solid #EF4444; color: #b91c1c; }

        /* Status badges */
        .status { display: inline-block; padding: 3px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-disponible   { background: #f0fdf4; color: #16A34A; }
        .status-indisponible { background: #fef2f2; color: #DC2626; }
        .status-en_attente   { background: #fff7ed; color: #C2410C; }
        .status-en_cours     { background: #eff6ff; color: #1D4ED8; }
        .status-termine      { background: #f0fdf4; color: #16A34A; }
        .status-annule       { background: #f1f5f9; color: #64748B; }
        .status-en_retard    { background: #fef2f2; color: #DC2626; }

        /* Forms */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 10px 13px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 13.5px; font-family: inherit; color: var(--navy);
            background: #fff; transition: border-color .2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none; border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(29,158,117,.1);
        }
        .form-error { color: #DC2626; font-size: 12px; margin-top: 4px; }
        .grid    { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; padding: 22px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; padding: 0 22px 22px; }

        /* Stats grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(190px,1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card  {
            background: var(--white); border-radius: var(--radius-lg);
            padding: 18px; display: flex; align-items: center; gap: 14px;
            border: 1px solid rgba(29,158,117,.12); box-shadow: var(--shadow-sm);
        }
        .stat-icon { width: 48px; height: 48px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 21px; }
        .stat-icon.primary { background: rgba(29,158,117,.1); color: var(--green); }
        .stat-icon.warning { background: rgba(245,158,11,.1); color: #F59E0B; }
        .stat-icon.info    { background: rgba(59,130,246,.1);  color: #3B82F6; }
        .stat-icon.success { background: rgba(34,197,94,.1);   color: #22C55E; }
        .stat-number { font-size: 24px; font-weight: 700; color: var(--navy); line-height: 1; }
        .stat-label  { font-size: 12.5px; color: var(--gray-500); margin-top: 3px; }

        /* Responsive */
        @media (max-width: 768px) {
            .mc-sidebar { display: none; }
            .mc-main    { padding: 18px 16px; }
            .grid       { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ── Page wrapper ── -->
<div class="d-flex" style="min-height:100vh;">

    <!-- ══ SIDEBAR ══ -->
    <aside class="mc-sidebar">

        <a href="<?php echo htmlspecialchars(routeUrl('admin', 'dashboard', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
           class="mc-sidebar-logo">
            <div class="mc-sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
            <div class="mc-sidebar-logo-text">Med<span>Chain</span></div>
        </a>

        <nav class="mc-sidebar-nav">

            <div class="mc-nav-section">Navigation</div>
            <a href="<?php echo htmlspecialchars(routeUrl('admin', 'dashboard', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo $currentController === 'admin' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Tableau de bord
            </a>

            <div class="mc-nav-section">Objets Loisir</div>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo ($currentController === 'objet' && $currentAction === 'list') ? 'active' : ''; ?>">
                <i class="bi bi-box-seam-fill"></i> Liste des objets
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'add', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo ($currentController === 'objet' && $currentAction === 'add') ? 'active' : ''; ?>">
                <i class="bi bi-plus-circle-fill"></i> Ajouter un objet
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('categorie', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo $currentController === 'categorie' ? 'active' : ''; ?>">
                <i class="bi bi-tags-fill"></i> Catégories
            </a>

            <div class="mc-nav-section">Prêts</div>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'pending', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo ($currentController === 'pret' && $currentAction === 'pending') ? 'active' : ''; ?>">
                <i class="bi bi-hourglass-split"></i> Demandes en attente
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirmed', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo ($currentController === 'pret' && $currentAction === 'confirmed') ? 'active' : ''; ?>">
                <i class="bi bi-arrow-repeat"></i> Prêts en cours
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo ($currentController === 'pret' && $currentAction === 'list') ? 'active' : ''; ?>">
                <i class="bi bi-list-ul"></i> Tous les prêts
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'calendar', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo ($currentController === 'pret' && $currentAction === 'calendar') ? 'active' : ''; ?>">
                <i class="bi bi-calendar3"></i> Calendrier des prêts
            </a>

            <div class="mc-nav-section">Avis &amp; Réservations</div>
            <a href="<?php echo htmlspecialchars(routeUrl('avis', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo $currentController === 'avis' ? 'active' : ''; ?>">
                <i class="bi bi-star-fill"></i> Avis patients
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('reservation', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
               class="mc-nav-item <?php echo $currentController === 'reservation' ? 'active' : ''; ?>">
                <i class="bi bi-bookmark-star-fill"></i> Listes d'attente
            </a>

            <div class="mc-nav-section">Gestion</div>
            <a href="/midchaine/views/backoffice/admin-dashboard.php" class="mc-nav-item">
                <i class="bi bi-people-fill"></i> Utilisateurs
            </a>
            <a href="/midchaine/controllers/logout.php"
               class="mc-nav-item logout"
               onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>

        </nav>

        <div class="mc-sidebar-footer">
            <?php
            $notifModel   = new Notification();
            $unreadCount  = $notifModel->countUnread((int) ($_SESSION['user_id'] ?? 0));
            $recentNotifs = $notifModel->getRecent((int) ($_SESSION['user_id'] ?? 0), 5);
            ?>

            <!-- Bell icon in sidebar footer -->
            <div style="position:relative;margin-right:4px;">
                <button id="bellBtnBack"
                        style="background:none;border:none;cursor:pointer;padding:6px;
                               border-radius:8px;transition:background .2s;position:relative;"
                        onmouseover="this.style.background='rgba(255,255,255,.1)'"
                        onmouseout="this.style.background='none'">
                    <i class="bi bi-bell-fill" style="font-size:18px;color:#94a3b8;"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span style="position:absolute;top:2px;right:2px;min-width:16px;height:16px;
                                     background:#EF4444;color:#fff;border-radius:50%;font-size:9px;
                                     font-weight:700;display:flex;align-items:center;justify-content:center;
                                     padding:0 3px;line-height:1;">
                            <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- Bell dropdown -->
                <div id="bellDropdownBack"
                     style="display:none;position:absolute;bottom:calc(100% + 8px);left:0;
                            background:#fff;border-radius:12px;min-width:280px;
                            box-shadow:0 12px 40px rgba(0,0,0,.18);border:1px solid #E5E7EB;
                            z-index:9999;overflow:hidden;">

                    <div style="padding:12px 16px;border-bottom:1px solid #E5E7EB;
                                display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;
                                     color:#1E3A52;">Notifications</span>
                        <?php if ($unreadCount > 0): ?>
                            <a href="<?php echo htmlspecialchars(routeUrl('notification', 'markRead', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
                               style="font-size:11px;color:#1D9E75;font-weight:600;">Tout marquer lu</a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($recentNotifs)): ?>
                        <div style="padding:20px;text-align:center;color:#6B7280;font-size:12px;">
                            <i class="bi bi-bell-slash" style="font-size:24px;opacity:.3;display:block;margin-bottom:6px;"></i>
                            Aucune notification
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentNotifs as $notif): ?>
                            <div style="padding:10px 16px;border-bottom:1px solid #F1F5F9;
                                        background:<?php echo $notif['is_read'] ? '#fff' : '#f0fdf4'; ?>;
                                        display:flex;gap:8px;align-items:flex-start;">
                                <div style="width:7px;height:7px;border-radius:50%;margin-top:4px;flex-shrink:0;
                                            background:<?php echo $notif['is_read'] ? 'transparent' : '#1D9E75'; ?>;"></div>
                                <div>
                                    <p style="margin:0;font-size:12px;color:#1E3A52;line-height:1.4;">
                                        <?php echo htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                    <span style="font-size:10px;color:#6B7280;">
                                        <?php echo date('d/m/Y H:i', strtotime($notif['date_creation'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mc-sidebar-avatar"><i class="bi bi-person-fill"></i></div>
            <div>
                <div class="mc-sidebar-username">
                    <?php echo htmlspecialchars(trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Admin')), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="mc-sidebar-role">Administrateur</div>
            </div>
        </div>

    </aside>
    <!-- ══ END SIDEBAR ══ -->

    <!-- ══ MAIN CONTENT ══ -->
    <main class="mc-main">
<script>
(function(){
    var btn = document.getElementById('bellBtnBack');
    var dd  = document.getElementById('bellDropdownBack');
    if (!btn || !dd) return;
    btn.addEventListener('click', function(e){
        e.stopPropagation();
        dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', function(){ dd.style.display = 'none'; });
    dd.addEventListener('click', function(e){ e.stopPropagation(); });
})();
</script>
