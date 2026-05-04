<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'MedChain', ENT_QUOTES, 'UTF-8'); ?> — MedChain</title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? 'Plateforme médicale MedChain', ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --nav-bg:          rgba(10,15,30,.92);
            --nav-border:      rgba(255,255,255,.08);
            --body-bg:         #080d1a;
            --card-bg:         rgba(255,255,255,.04);
            --card-border:     rgba(255,255,255,.08);
            --text-primary:    #f1f5f9;
            --text-muted:      #64748b;
            --accent:          #6366f1;
            --accent-light:    #818cf8;
            --success:         #10b981;
            --warning:         #f59e0b;
            --danger:          #ef4444;
            --footer-bg:       #050914;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            margin: 0;
        }

        /* ── NAVBAR ──────────────────────────────────────────── */
        .mc-navbar {
            position: sticky; top: 0;
            background: var(--nav-bg);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--nav-border);
            z-index: 1000;
        }
        .mc-navbar .container-fluid { padding: 0 28px; }

        .nav-brand {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .nav-brand .brand-ico {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
        }
        .nav-brand .brand-lbl { font-size: 1rem; font-weight: 700; color: var(--text-primary); }

        .mc-nav-link {
            color: var(--text-muted) !important;
            font-size: .85rem;
            font-weight: 500;
            padding: 8px 12px !important;
            border-radius: 8px;
            transition: background .15s, color .15s;
        }
        .mc-nav-link:hover, .mc-nav-link.active {
            background: rgba(99,102,241,.12);
            color: #c7d2fe !important;
        }

        /* ── ROLE BADGE in nav ───────────────────────────────── */
        .role-chip {
            font-size: .72rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .role-patient { background: rgba(59,130,246,.15); color: #93c5fd; border: 1px solid rgba(59,130,246,.3); }
        .role-medecin { background: rgba(16,185,129,.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,.3); }

        /* ── MAIN CONTENT ────────────────────────────────────── */
        .mc-main { padding: 32px 0; min-height: calc(100vh - 130px); }

        /* ── CARDS ───────────────────────────────────────────── */
        .mc-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            overflow: hidden;
            transition: border-color .2s, transform .2s, box-shadow .2s;
        }
        .mc-card:hover {
            border-color: rgba(99,102,241,.4);
            transform: translateY(-2px);
            box-shadow: 0 16px 40px rgba(0,0,0,.3);
        }

        /* ── BADGES ──────────────────────────────────────────── */
        .badge-statut {
            display: inline-block; padding: 3px 10px;
            border-radius: 999px; font-size: .72rem; font-weight: 600;
        }
        .badge-en_attente { background: rgba(245,158,11,.2);  color: #fbbf24; border: 1px solid rgba(245,158,11,.3); }
        .badge-en_cours   { background: rgba(59,130,246,.2);  color: #93c5fd; border: 1px solid rgba(59,130,246,.3); }
        .badge-termine    { background: rgba(16,185,129,.2);  color: #6ee7b7; border: 1px solid rgba(16,185,129,.3); }
        .badge-annule     { background: rgba(100,116,139,.2); color: #94a3b8; border: 1px solid rgba(100,116,139,.3); }
        .badge-en_retard  { background: rgba(239,68,68,.2);   color: #f87171; border: 1px solid rgba(239,68,68,.3); }

        /* ── ALERTS ──────────────────────────────────────────── */
        .mc-alert { border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: .875rem; display: flex; align-items: flex-start; gap: 10px; }
        .mc-alert-success { background: rgba(16,185,129,.1);  border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
        .mc-alert-danger  { background: rgba(239,68,68,.1);   border: 1px solid rgba(239,68,68,.3);  color: #f87171; }
        .mc-alert-info    { background: rgba(59,130,246,.1);  border: 1px solid rgba(59,130,246,.3); color: #93c5fd; }

        /* ── FOOTER ──────────────────────────────────────────── */
        .mc-footer {
            background: var(--footer-bg);
            border-top: 1px solid var(--nav-border);
            padding: 20px 28px;
            text-align: center;
            color: var(--text-muted);
            font-size: .8rem;
        }
    </style>
    <?php echo $extraHead ?? ''; ?>
</head>
<body>

<!-- ═══ NAVBAR ══════════════════════════════════════════════════════════════ -->
<nav class="mc-navbar navbar navbar-expand-lg">
    <div class="container-fluid">

        <a class="nav-brand me-4" href="<?php echo routeUrl('objet', 'list', ['office' => 'front']); ?>">
            <div class="brand-ico"><i class="bi bi-hospital"></i></div>
            <span class="brand-lbl">MedChain</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#frontNav"
                style="border-color:rgba(255,255,255,.15);color:#94a3b8">
            <i class="bi bi-list"></i>
        </button>

        <div class="collapse navbar-collapse" id="frontNav">
            <ul class="navbar-nav me-auto gap-1">
                <li class="nav-item">
                    <a class="mc-nav-link nav-link <?php echo ($currentNav ?? '') === 'catalogue' ? 'active' : ''; ?>"
                       href="<?php echo routeUrl('objet', 'list', ['office' => 'front']); ?>">
                        <i class="bi bi-boxes me-1"></i>Catalogue
                    </a>
                </li>
                <?php if (!empty($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="mc-nav-link nav-link <?php echo ($currentNav ?? '') === 'my-loans' ? 'active' : ''; ?>"
                       href="<?php echo routeUrl('pret', 'myLoans', ['office' => 'front']); ?>">
                        <i class="bi bi-bag me-1"></i>Mes emprunts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="mc-nav-link nav-link <?php echo ($currentNav ?? '') === 'rdv' ? 'active' : ''; ?>"
                       href="<?php echo routeUrl('rendezvous', 'list', ['office' => 'front']); ?>">
                        <i class="bi bi-calendar2-check me-1"></i>Rendez-vous
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav gap-2 align-items-lg-center">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="role-chip role-<?php echo htmlspecialchars($_SESSION['user_role'] ?? 'patient', ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="mc-nav-link nav-link"
                           href="<?php echo routeUrl('utilisateur', 'profile', ['office' => 'front']); ?>">
                            <i class="bi bi-person-gear me-1"></i>Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="mc-nav-link nav-link"
                           href="<?php echo routeUrl('auth', 'logout'); ?>">
                            <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="mc-nav-link nav-link"
                           href="<?php echo routeUrl('auth', 'login'); ?>">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo routeUrl('auth', 'register'); ?>"
                           style="background: linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;padding:7px 16px;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none">
                            S'inscrire
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ═══ MAIN ════════════════════════════════════════════════════════════════ -->
<main class="mc-main">
    <div class="container-xl">
