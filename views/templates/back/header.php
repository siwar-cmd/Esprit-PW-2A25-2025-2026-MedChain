<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'MedChain', ENT_QUOTES, 'UTF-8'); ?> — MedChain Admin</title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? 'Tableau de bord administrateur MedChain', ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-w:         260px;
            --sidebar-bg:        #0f172a;
            --sidebar-border:    #1e293b;
            --sidebar-text:      #94a3b8;
            --sidebar-hover-bg:  rgba(99,102,241,.12);
            --sidebar-hover-txt: #c7d2fe;
            --sidebar-active-bg: rgba(99,102,241,.20);
            --sidebar-active-txt:#818cf8;
            --topbar-h:          64px;
            --body-bg:           #0f172a;
            --card-bg:           #1e293b;
            --card-border:       #334155;
            --text-primary:      #f1f5f9;
            --text-muted:        #64748b;
            --accent:            #6366f1;
            --accent-light:      #818cf8;
            --success:           #10b981;
            --warning:           #f59e0b;
            --danger:            #ef4444;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            margin: 0;
            min-height: 100vh;
        }

        /* ── SIDEBAR ─────────────────────────────────────────── */
        .admin-sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform .3s ease;
            overflow-y: auto;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 24px 20px;
            border-bottom: 1px solid var(--sidebar-border);
            text-decoration: none;
        }
        .sidebar-brand .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #fff;
            flex-shrink: 0;
        }
        .sidebar-brand .brand-text { line-height: 1.2; }
        .sidebar-brand .brand-name  { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
        .sidebar-brand .brand-sub   { font-size: .7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; }

        .sidebar-nav { flex: 1; padding: 12px 0; }
        .sidebar-section {
            padding: 16px 20px 6px;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-muted);
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 0;
            font-size: .875rem;
            font-weight: 500;
            transition: background .15s, color .15s;
            position: relative;
        }
        .sidebar-link:hover  { background: var(--sidebar-hover-bg); color: var(--sidebar-hover-txt); }
        .sidebar-link.active { background: var(--sidebar-active-bg); color: var(--sidebar-active-txt); }
        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0; top: 6px; bottom: 6px;
            width: 3px;
            background: var(--accent-light);
            border-radius: 0 3px 3px 0;
        }
        .sidebar-link i { font-size: 1rem; width: 20px; text-align: center; }
        .badge-pill {
            margin-left: auto;
            background: var(--danger);
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
        }

        .sidebar-footer {
            border-top: 1px solid var(--sidebar-border);
            padding: 16px 20px;
        }
        .sidebar-user {
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; color: #fff; font-weight: 700;
            flex-shrink: 0;
        }
        .sidebar-user-info { flex: 1; min-width: 0; }
        .sidebar-user-name  { font-size: .8rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user-role  { font-size: .7rem; color: var(--text-muted); }

        /* ── MAIN CONTENT ────────────────────────────────────── */
        .admin-main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-topbar {
            position: sticky; top: 0;
            height: var(--topbar-h);
            background: rgba(15,23,42,.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--card-border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px;
            z-index: 900;
        }

        .topbar-title { font-size: 1.05rem; font-weight: 600; color: var(--text-primary); }
        .topbar-actions { display: flex; align-items: center; gap: 12px; }

        .btn-topbar {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--sidebar-text);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: .8rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: border-color .15s, color .15s;
        }
        .btn-topbar:hover { border-color: var(--accent-light); color: var(--accent-light); }

        .admin-content {
            flex: 1;
            padding: 28px;
        }

        /* ── CARDS ───────────────────────────────────────────── */
        .mc-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            overflow: hidden;
        }
        .mc-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px;
        }
        .mc-card-header h5 {
            margin: 0;
            font-size: .9rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* ── STAT CARDS ──────────────────────────────────────── */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px;
            display: flex; align-items: center; gap: 16px;
            transition: border-color .2s, transform .2s;
        }
        .stat-card:hover { border-color: var(--accent); transform: translateY(-2px); }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .stat-icon.purple { background: rgba(99,102,241,.15); color: #818cf8; }
        .stat-icon.green  { background: rgba(16,185,129,.15); color: #34d399; }
        .stat-icon.amber  { background: rgba(245,158,11,.15); color: #fbbf24; }
        .stat-icon.red    { background: rgba(239,68,68,.15);  color: #f87171; }
        .stat-icon.blue   { background: rgba(59,130,246,.15); color: #60a5fa; }

        .stat-value { font-size: 1.6rem; font-weight: 700; color: var(--text-primary); line-height: 1; }
        .stat-label { font-size: .8rem; color: var(--text-muted); margin-top: 4px; }

        /* ── TABLE ───────────────────────────────────────────── */
        .mc-table { width: 100%; border-collapse: collapse; }
        .mc-table th {
            padding: 10px 14px;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            background: rgba(255,255,255,.03);
            border-bottom: 1px solid var(--card-border);
            white-space: nowrap;
        }
        .mc-table td {
            padding: 12px 14px;
            font-size: .875rem;
            color: var(--text-primary);
            border-bottom: 1px solid rgba(255,255,255,.04);
            vertical-align: middle;
        }
        .mc-table tr:last-child td { border-bottom: none; }
        .mc-table tr:hover td { background: rgba(255,255,255,.025); }

        /* ── BADGES ──────────────────────────────────────────── */
        .badge-role, .badge-statut {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
        }
        .badge-admin   { background: rgba(139,92,246,.2); color: #c4b5fd; border: 1px solid rgba(139,92,246,.3); }
        .badge-medecin { background: rgba(16,185,129,.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,.3); }
        .badge-patient { background: rgba(59,130,246,.2); color: #93c5fd; border: 1px solid rgba(59,130,246,.3); }
        .badge-actif       { background: rgba(16,185,129,.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,.3); }
        .badge-inactif     { background: rgba(100,116,139,.2); color: #94a3b8; border: 1px solid rgba(100,116,139,.3); }
        .badge-en_attente  { background: rgba(245,158,11,.2);  color: #fbbf24; border: 1px solid rgba(245,158,11,.3); }
        .badge-en_cours    { background: rgba(59,130,246,.2);  color: #93c5fd; border: 1px solid rgba(59,130,246,.3); }
        .badge-termine     { background: rgba(16,185,129,.2);  color: #6ee7b7; border: 1px solid rgba(16,185,129,.3); }
        .badge-annule      { background: rgba(100,116,139,.2); color: #94a3b8; border: 1px solid rgba(100,116,139,.3); }
        .badge-en_retard   { background: rgba(239,68,68,.2);   color: #f87171; border: 1px solid rgba(239,68,68,.3); }

        /* ── ALERTS ──────────────────────────────────────────── */
        .mc-alert {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: .875rem;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .mc-alert-success { background: rgba(16,185,129,.1);  border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
        .mc-alert-danger  { background: rgba(239,68,68,.1);   border: 1px solid rgba(239,68,68,.3);  color: #f87171; }
        .mc-alert-warning { background: rgba(245,158,11,.1);  border: 1px solid rgba(245,158,11,.3); color: #fbbf24; }

        /* ── RESPONSIVE ─────────────────────────────────────── */
        @media (max-width: 992px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
        }
    </style>
    <?php echo $extraHead ?? ''; ?>
</head>
<body>

<!-- ═══ SIDEBAR ════════════════════════════════════════════════════════════ -->
<aside class="admin-sidebar" id="adminSidebar">

    <a href="<?php echo routeUrl('admin', 'dashboard', ['office' => 'back']); ?>" class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-hospital"></i></div>
        <div class="brand-text">
            <div class="brand-name">MedChain</div>
            <div class="brand-sub">Back Office</div>
        </div>
    </a>

    <nav class="sidebar-nav">

        <!-- Overview -->
        <div class="sidebar-section">Tableau de bord</div>
        <a href="<?php echo routeUrl('admin', 'dashboard', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-grid-1x2"></i> Tableau de bord
        </a>
        <a href="<?php echo routeUrl('admin', 'stats', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'stats' ? 'active' : ''; ?>">
            <i class="bi bi-bar-chart-line"></i> Statistiques
        </a>

        <!-- Users -->
        <div class="sidebar-section">Gestion utilisateurs</div>
        <a href="<?php echo routeUrl('admin', 'users', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'users' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Tous les utilisateurs
        </a>
        <a href="<?php echo routeUrl('admin', 'createUser', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'createUser' ? 'active' : ''; ?>">
            <i class="bi bi-person-plus"></i> Créer un compte
        </a>

        <!-- Leisure -->
        <div class="sidebar-section">Objets Loisirs</div>
        <a href="<?php echo routeUrl('objet', 'list', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'objet-list' ? 'active' : ''; ?>">
            <i class="bi bi-boxes"></i> Catalogue
        </a>
        <a href="<?php echo routeUrl('objet', 'add', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'objet-add' ? 'active' : ''; ?>">
            <i class="bi bi-plus-circle"></i> Ajouter un objet
        </a>
        <a href="<?php echo routeUrl('pret', 'pending', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'pret-pending' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i> Demandes en attente
        </a>
        <a href="<?php echo routeUrl('pret', 'confirmed', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'pret-confirmed' ? 'active' : ''; ?>">
            <i class="bi bi-check-circle"></i> Emprunts en cours
        </a>
        <a href="<?php echo routeUrl('pret', 'list', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'pret-list' ? 'active' : ''; ?>">
            <i class="bi bi-list-ul"></i> Tous les emprunts
        </a>

        <!-- Medical -->
        <div class="sidebar-section">Médical</div>
        <a href="<?php echo routeUrl('rendezvous', 'list', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'rdv-list' ? 'active' : ''; ?>">
            <i class="bi bi-calendar2-check"></i> Rendez-vous
        </a>
        <a href="<?php echo routeUrl('ambulance', 'list', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'ambulance-list' ? 'active' : ''; ?>">
            <i class="bi bi-truck"></i> Ambulances
        </a>
        <a href="<?php echo routeUrl('ambulance', 'missions', ['office' => 'back']); ?>"
           class="sidebar-link <?php echo ($currentNav ?? '') === 'missions' ? 'active' : ''; ?>">
            <i class="bi bi-geo-alt"></i> Missions
        </a>

    </nav>

    <!-- User Info Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?php echo strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1)); ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">
                    <?php echo htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="sidebar-user-role">Administrateur</div>
            </div>
            <a href="<?php echo routeUrl('auth', 'logout'); ?>" title="Déconnexion" style="color: var(--text-muted); margin-left: 4px;">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

</aside>

<!-- ═══ MAIN ════════════════════════════════════════════════════════════════ -->
<main class="admin-main">

    <!-- Top Bar -->
    <div class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
            <button id="sidebarToggle" class="btn-topbar d-lg-none" style="padding:6px 10px">
                <i class="bi bi-list" style="font-size:1.1rem;margin:0"></i>
            </button>
            <span class="topbar-title"><?php echo htmlspecialchars($pageTitle ?? 'Tableau de bord', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="topbar-actions">
            <a href="<?php echo routeUrl('objet', 'list', ['office' => 'front']); ?>" class="btn-topbar" target="_blank">
                <i class="bi bi-eye"></i> Voir le site
            </a>
            <a href="<?php echo routeUrl('auth', 'logout'); ?>" class="btn-topbar">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Page Content -->
    <div class="admin-content">
