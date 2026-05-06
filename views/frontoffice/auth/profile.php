<?php
session_start();
include_once '../../../controllers/AuthController.php'; 
include_once '../../../controllers/PasswordController.php';
include_once '../../../controllers/ProfileController.php';

$authController     = new AuthController();
$passwordController = new PasswordController();
$profileController  = new ProfileController();

if (!$authController->isLoggedIn()) {
    header('Location: sign-in.php');
    exit;
}

$user    = $authController->getCurrentUser();
$isAdmin = $user && $user->estAdmin();

$profile_error  = null; $profile_success  = null;
$photo_error    = null; $photo_success    = null;
$password_error = null; $password_success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $result = $profileController->updateProfile($user->getId(), $_POST);
        if ($result['success']) { $profile_success = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); $user = $authController->getCurrentUser(); }
        else                    { $profile_error   = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); }
    }
    if (isset($_POST['update_photo']) && isset($_FILES['photo_profil'])) {
        $result = $profileController->updateProfilePhoto($user->getId(), $_FILES['photo_profil']);
        if ($result['success']) { $photo_success = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); $user = $authController->getCurrentUser(); }
        else                    { $photo_error   = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); }
    }
    if (isset($_POST['delete_photo'])) {
        $result = $profileController->deleteProfilePhoto($user->getId());
        if ($result['success']) { $photo_success = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); $user = $authController->getCurrentUser(); }
        else                    { $photo_error   = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); }
    }
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_error = "Veuillez remplir tous les champs";
        } elseif ($new_password !== $confirm_password) {
            $password_error = "Les nouveaux mots de passe ne correspondent pas";
        } elseif (strlen($new_password) < 6) {
            $password_error = "Le nouveau mot de passe doit contenir au moins 6 caractères";
        } else {
            $result = $passwordController->changePassword($user->getId(), $current_password, $new_password);
            if ($result['success']) { $password_success = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); $_POST = []; }
            else                    { $password_error   = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); }
        }
    }
}
$photo_url = $user->getPhotoProfilUrl();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;
            --gray-700:#374151;--gray-500:#6B7280;--gray-400:#94A3B8;
            --gray-200:#E5E7EB;--gray-100:#F1F5F9;--white:#fff;
            --shadow-sm:0 1px 3px rgba(0,0,0,.08);
            --shadow-md:0 4px 16px rgba(0,0,0,.10);
            --shadow-lg:0 12px 40px rgba(0,0,0,.10);
            --radius-sm:8px;--radius-md:12px;--radius-lg:20px;--radius-xl:28px;
            --sidebar-w:260px;
        }

        body{
            font-family:'DM Sans',sans-serif;
            background:linear-gradient(145deg,#f0faf6,#e8f7f1,#ddf3ea);
            min-height:100vh;overflow-x:hidden;
        }

        /* ══════════════════════════════════════
           LAYOUT : main à gauche + sidebar à droite
        ══════════════════════════════════════ */
        .layout{
            display:grid;
            grid-template-columns:1fr var(--sidebar-w);
            min-height:100vh;
            max-width:1280px;
            margin:0 auto;
            gap:0;
        }

        /* ══ MAIN (colonne gauche) ══ */
        .main-col{
            padding:36px 32px 60px;
            min-width:0; /* évite le débordement */
        }

        /* ══════════════════════════════════════
           SIDEBAR DROITE
        ══════════════════════════════════════ */
        .sidebar{
            background:linear-gradient(175deg,#0d2233 0%,#071623 100%);
            position:sticky;
            top:0;
            height:100vh;
            display:flex;
            flex-direction:column;
            overflow-y:auto;
            border-left:1px solid rgba(255,255,255,.06);
            z-index:100;
        }

        /* Logo */
        .sb-logo{
            padding:22px 18px 18px;
            border-bottom:1px solid rgba(255,255,255,.07);
        }
        .sb-logo a{display:flex;align-items:center;gap:10px;text-decoration:none}
        .sb-logo-icon{
            width:38px;height:38px;flex-shrink:0;
            background:linear-gradient(135deg,var(--green),var(--green-dark));
            border-radius:var(--radius-md);
            display:flex;align-items:center;justify-content:center;
            box-shadow:0 4px 16px rgba(29,158,117,.30);
        }
        .sb-logo-icon i{font-size:19px;color:#fff}
        .sb-logo-text{font-family:'Syne',sans-serif;font-size:19px;font-weight:700;color:#fff}
        .sb-logo-text span{color:var(--green)}

        /* User block */
        .sb-user{
            display:flex;align-items:center;gap:10px;
            padding:14px 16px;
            border-bottom:1px solid rgba(255,255,255,.06);
            background:rgba(29,158,117,.06);
        }
        .sb-avatar{
            width:40px;height:40px;border-radius:50%;flex-shrink:0;
            background:linear-gradient(135deg,var(--green),var(--green-dark));
            display:flex;align-items:center;justify-content:center;
            font-weight:700;color:#fff;font-size:13px;overflow:hidden;
            border:2px solid rgba(29,158,117,.4);
        }
        .sb-avatar img{width:100%;height:100%;object-fit:cover}
        .sb-user-info{flex:1;min-width:0}
        .sb-user-name{font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .sb-user-role{font-size:11px;color:var(--gray-400);margin-top:1px}
        .sb-online{width:8px;height:8px;border-radius:50%;background:#22C55E;flex-shrink:0;box-shadow:0 0 6px #22C55E}

        /* Nav */
        .sb-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:1px}
        .sb-label{
            font-size:10px;text-transform:uppercase;letter-spacing:1.2px;
            color:#334155;padding:10px 8px 4px;font-weight:700;
        }
        .sb-item{
            display:flex;align-items:center;gap:10px;
            padding:10px 13px;color:#94A3B8;text-decoration:none;
            border-radius:var(--radius-sm);transition:all .22s;
            font-size:13.5px;font-weight:500;position:relative;
        }
        .sb-item i{font-size:16px;width:20px;flex-shrink:0}
        .sb-item:hover{background:rgba(255,255,255,.07);color:#fff}
        .sb-item.active{background:rgba(29,158,117,.18);color:var(--green)}
        .sb-item.active::before{
            content:'';position:absolute;right:0;top:20%;bottom:20%;
            width:3px;background:var(--green);border-radius:3px 0 0 3px;
        }
        .sb-item.logout{color:#F87171}
        .sb-item.logout:hover{background:rgba(248,113,113,.10)}
        .sb-spacer{flex:1;min-height:12px}

        /* ══ PAGE HEADER ══ */
        .page-header{
            display:flex;justify-content:space-between;align-items:center;
            margin-bottom:28px;flex-wrap:wrap;gap:12px;
        }
        .page-header h1{
            font-family:'Syne',sans-serif;font-size:26px;font-weight:700;
            color:var(--navy);display:flex;align-items:center;gap:10px;
        }
        .page-header h1 i{color:var(--green)}

        /* ══ STATS ══ */
        .stats-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
            gap:16px;margin-bottom:28px;
        }
        .stat-card{
            background:var(--white);border-radius:var(--radius-lg);
            padding:18px 20px;display:flex;align-items:center;gap:14px;
            border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);
            transition:all .3s;
        }
        .stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
        .stat-icon{
            width:48px;height:48px;border-radius:var(--radius-md);
            display:flex;align-items:center;justify-content:center;
            font-size:21px;flex-shrink:0;
        }
        .stat-icon.primary{background:rgba(29,158,117,.12);color:var(--green)}
        .stat-icon.success{background:rgba(34,197,94,.12);color:#22C55E}
        .stat-icon.warning{background:rgba(245,158,11,.12);color:#F59E0B}
        .stat-icon.info{background:rgba(59,130,246,.12);color:#3B82F6}
        .stat-content h3{font-family:'Syne',sans-serif;font-size:19px;font-weight:700;color:var(--navy);line-height:1.2}
        .stat-content p{color:var(--gray-500);font-size:12px;margin-top:4px}

        /* ══ PROFILE CARD ══ */
        .profile-card{
            background:var(--white);border-radius:var(--radius-xl);
            border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-lg);overflow:hidden;
        }
        .profile-card-header{
            padding:20px 28px;border-bottom:1px solid var(--gray-200);
            display:flex;justify-content:space-between;align-items:center;
            flex-wrap:wrap;gap:12px;
            background:linear-gradient(135deg,rgba(29,158,117,.04),transparent);
        }
        .profile-card-header h2{
            font-family:'Syne',sans-serif;font-size:18px;font-weight:700;
            color:var(--navy);display:flex;align-items:center;gap:9px;
        }
        .profile-card-header h2 i{color:var(--green)}
        .role-badge{
            display:inline-flex;align-items:center;gap:6px;
            padding:5px 14px;border-radius:50px;font-size:12px;font-weight:600;
        }
        .role-badge.user{background:#F0FDF4;color:#22C55E}
        .role-badge.admin{background:#FEF2F2;color:#EF4444}
        .profile-card-body{padding:28px}

        /* ══ AVATAR ══ */
        .avatar-section{
            display:flex;align-items:center;gap:22px;
            padding:20px 22px;
            background:linear-gradient(135deg,rgba(29,158,117,.05),transparent);
            border-radius:var(--radius-lg);margin-bottom:26px;
            border:1px solid rgba(29,158,117,.12);
        }
        .avatar-container{position:relative;flex-shrink:0}
        .avatar{width:92px;height:92px;border-radius:50%;object-fit:cover;border:4px solid var(--green);box-shadow:var(--shadow-md)}
        .avatar-initials{
            width:92px;height:92px;border-radius:50%;
            background:linear-gradient(135deg,var(--green),var(--green-dark));
            display:flex;align-items:center;justify-content:center;
            font-size:32px;font-weight:700;color:#fff;
            border:4px solid rgba(29,158,117,.25);
        }
        .avatar-edit-btn{
            position:absolute;bottom:2px;right:2px;
            width:30px;height:30px;background:var(--white);
            border-radius:50%;display:flex;align-items:center;justify-content:center;
            cursor:pointer;box-shadow:var(--shadow-md);
            color:var(--green);border:none;transition:all .3s;font-size:13px;
        }
        .avatar-edit-btn:hover{background:var(--green);color:#fff;transform:scale(1.1)}
        .avatar-info{flex:1;min-width:0}
        .avatar-info h3{font-family:'Syne',sans-serif;font-size:19px;font-weight:700;color:var(--navy);margin-bottom:4px}
        .avatar-info .email{color:var(--gray-500);font-size:13px;margin-bottom:10px;display:flex;align-items:center;gap:6px}
        .status-badge{
            display:inline-flex;align-items:center;gap:6px;
            padding:5px 12px;border-radius:50px;font-size:12px;font-weight:600;
        }
        .status-badge.actif{background:#F0FDF4;color:#22C55E}
        .status-badge.inactif{background:#FEF2F2;color:#EF4444}

        /* ══ TABS ══ */
        .profile-tabs{
            display:flex;gap:4px;border-bottom:2px solid var(--gray-200);
            margin-bottom:26px;flex-wrap:wrap;
        }
        .tab-btn{
            padding:10px 18px;background:none;border:none;
            font-size:13.5px;font-weight:600;color:var(--gray-500);
            cursor:pointer;transition:all .25s;
            border-bottom:2px solid transparent;margin-bottom:-2px;
            display:flex;align-items:center;gap:7px;
            border-radius:var(--radius-md) var(--radius-md) 0 0;
        }
        .tab-btn:hover{color:var(--green);background:rgba(29,158,117,.05)}
        .tab-btn.active{color:var(--green);border-bottom-color:var(--green);background:rgba(29,158,117,.06)}
        .tab-pane{display:none;animation:fadeIn .3s ease}
        .tab-pane.active{display:block}
        @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

        /* ══ FORM ══ */
        .form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px;margin-bottom:22px}
        .form-group{margin-bottom:0}
        .form-group.full-width{grid-column:span 2}
        label{display:block;margin-bottom:8px;color:var(--navy);font-weight:600;font-size:13px}
        label i{color:var(--green);margin-right:5px}
        .form-control{
            width:100%;padding:11px 16px;border:2px solid var(--gray-200);
            border-radius:var(--radius-md);font-size:14px;
            font-family:'DM Sans',sans-serif;transition:all .3s;background:#fff;
        }
        .form-control:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px rgba(29,158,117,.12)}
        .form-control.is-invalid{border-color:#EF4444;background:#FEF2F2}
        .form-control.is-valid{border-color:#22C55E;background:#F0FDF4}
        .error-message{font-size:11px;color:#EF4444;margin-top:5px}
        .password-input-container{position:relative}
        .password-input-container .form-control{padding-right:45px}
        .password-toggle{
            position:absolute;right:14px;top:50%;transform:translateY(-50%);
            background:none;border:none;color:var(--gray-500);cursor:pointer;padding:4px;
        }

        /* ══ INFO GRID ══ */
        .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:13px}
        .info-card{
            background:#F8FAFC;border-radius:var(--radius-md);padding:15px;
            border-left:3px solid var(--green);transition:all .3s;
        }
        .info-card:hover{background:#F0FDF4;transform:translateX(3px)}
        .info-label{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500);margin-bottom:6px;font-weight:600}
        .info-value{font-size:15px;font-weight:600;color:var(--navy)}

        /* ══ ALERTS ══ */
        .alert{
            padding:13px 16px;border-radius:var(--radius-md);
            margin-bottom:18px;display:flex;align-items:center;gap:12px;
            animation:slideIn .3s ease;
        }
        @keyframes slideIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        .alert-success{background:#F0FDF4;border-left:4px solid #22C55E;color:#166534}
        .alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C}
        .alert i{font-size:17px}
        .alert-close{margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:inherit;opacity:.6}

        /* ══ BUTTONS ══ */
        .btn{
            padding:10px 20px;border-radius:var(--radius-md);font-size:14px;font-weight:600;
            cursor:pointer;transition:all .3s;border:none;
            display:inline-flex;align-items:center;gap:8px;
            text-decoration:none;font-family:'DM Sans',sans-serif;
        }
        .btn-primary{background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff;box-shadow:0 3px 12px rgba(29,158,117,.30)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(29,158,117,.40)}
        .btn-danger{background:#EF4444;color:#fff}
        .btn-danger:hover{background:#DC2626;transform:translateY(-2px)}
        .actions-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:8px}

        /* ══ MOBILE OVERLAY ══ */
        .sb-overlay{
            display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:200;
        }
        .sb-toggle{
            display:none;position:fixed;top:14px;right:14px;z-index:300;
            width:42px;height:42px;border-radius:var(--radius-md);
            background:linear-gradient(135deg,var(--green),var(--green-dark));
            border:none;color:#fff;font-size:20px;
            align-items:center;justify-content:center;cursor:pointer;
            box-shadow:0 4px 16px rgba(29,158,117,.35);
        }

        /* ══ RESPONSIVE ══ */
        @media(max-width:1024px){
            .layout{grid-template-columns:1fr}
            .sidebar{
                position:fixed;top:0;right:-280px;height:100vh;
                width:var(--sidebar-w);
                transition:right .3s ease;
                z-index:250;
            }
            .sidebar.open{right:0}
            .sb-toggle{display:flex}
            .sb-overlay.open{display:block}
            .main-col{padding:24px 18px 50px}
        }
        @media(max-width:600px){
            .stats-grid{grid-template-columns:repeat(2,1fr)}
            .form-grid{grid-template-columns:1fr}
            .form-group.full-width{grid-column:span 1}
            .avatar-section{flex-direction:column;text-align:center}
            .avatar-info .email{justify-content:center}
            .profile-card-body{padding:18px}
        }
    </style>
</head>
<body>

<!-- ══ BOUTON TOGGLE MOBILE ══ -->
<button class="sb-toggle" id="sbToggle" aria-label="Ouvrir le menu">
    <i class="bi bi-layout-sidebar-reverse"></i>
</button>

<!-- ══ OVERLAY MOBILE ══ -->
<div class="sb-overlay" id="sbOverlay"></div>

<!-- ══ LAYOUT ══ -->
<div class="layout">

    <!-- ══════════════════════
         COLONNE PRINCIPALE (gauche)
    ══════════════════════ -->
    <div class="main-col">

        <div class="page-header">
            <h1><i class="bi bi-person-circle"></i> Mon Profil</h1>
            <span class="role-badge <?= $isAdmin ? 'admin' : 'user' ?>">
                <i class="bi <?= $isAdmin ? 'bi-shield-lock-fill' : 'bi-person-fill' ?>"></i>
                <?= ucfirst($user->getRole()) ?>
            </span>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-person-check-fill"></i></div>
                <div class="stat-content">
                    <h3><?= $user->estActif() ? 'Actif' : 'Inactif' ?></h3>
                    <p>Statut du compte</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon <?= $isAdmin ? 'info' : 'success' ?>">
                    <i class="bi <?= $isAdmin ? 'bi-shield-lock-fill' : 'bi-person-fill' ?>"></i>
                </div>
                <div class="stat-content">
                    <h3><?= ucfirst($user->getRole()) ?></h3>
                    <p>Rôle</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-calendar-date-fill"></i></div>
                <div class="stat-content">
                    <?php $d = new DateTime($user->getDateInscription()); ?>
                    <h3><?= $d->format('d/m/Y') ?></h3>
                    <p>Membre depuis</p>
                </div>
            </div>
            <?php if ($user->getDateNaissance()): ?>
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-cake2-fill"></i></div>
                <div class="stat-content">
                    <h3><?= $user->getAge() ?> ans</h3>
                    <p>Âge</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-card-header">
                <h2><i class="bi bi-person-lines-fill"></i> Gestion du profil</h2>
            </div>
            <div class="profile-card-body">

                <!-- Alerts -->
                <?php foreach ([
                    ['success', $profile_success],
                    ['error',   $profile_error],
                    ['success', $photo_success],
                    ['error',   $photo_error],
                    ['success', $password_success],
                    ['error',   $password_error],
                ] as [$type, $msg]): ?>
                <?php if ($msg): ?>
                <div class="alert alert-<?= $type ?>">
                    <i class="bi bi-<?= $type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
                    <div><?= $msg ?></div>
                    <button class="alert-close">&times;</button>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

                <!-- Avatar -->
                <div class="avatar-section">
                    <div class="avatar-container">
                        <?php if ($photo_url && $photo_url != '/assets/images/default-avatar.png'): ?>
                            <img src="<?= htmlspecialchars($photo_url) ?>" alt="Photo de profil" class="avatar">
                        <?php else: ?>
                            <div class="avatar-initials">
                                <?= strtoupper(substr($user->getPrenom(),0,1).substr($user->getNom(),0,1)) ?>
                            </div>
                        <?php endif; ?>
                        <button class="avatar-edit-btn" id="avatarEditBtn" title="Changer la photo">
                            <i class="bi bi-camera-fill"></i>
                        </button>
                        <form method="POST" enctype="multipart/form-data" id="photoUploadForm" style="display:none;">
                            <input type="file" name="photo_profil" id="photoInput" accept="image/*">
                            <input type="hidden" name="update_photo" value="1">
                        </form>
                    </div>
                    <div class="avatar-info">
                        <h3><?= htmlspecialchars($user->getPrenom().' '.$user->getNom()) ?></h3>
                        <div class="email"><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($user->getEmail()) ?></div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span class="status-badge <?= $user->estActif() ? 'actif' : 'inactif' ?>">
                                <i class="bi bi-<?= $user->estActif() ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                                <?= ucfirst($user->getStatut()) ?>
                            </span>
                            <?php if ($photo_url && $photo_url != '/assets/images/default-avatar.png'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="delete_photo" value="1">
                                <button type="submit" class="btn btn-danger" style="padding:5px 12px;font-size:12px;"
                                        onclick="return confirm('Supprimer la photo ?')">
                                    <i class="bi bi-trash-fill"></i> Supprimer
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="profile-tabs">
                    <button class="tab-btn active" data-tab="edit"><i class="bi bi-pencil-fill"></i> Modifier</button>
                    <button class="tab-btn" data-tab="password"><i class="bi bi-key-fill"></i> Mot de passe</button>
                    <button class="tab-btn" data-tab="info"><i class="bi bi-info-circle-fill"></i> Informations</button>
                </div>

                <!-- Tab: Edit -->
                <div class="tab-pane active" id="tab-edit">
                    <form method="POST" id="profileForm">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="bi bi-person-fill"></i> Prénom *</label>
                                <input type="text" name="prenom" class="form-control"
                                       value="<?= htmlspecialchars($user->getPrenom()) ?>" required>
                                <div class="error-message" id="prenom-error"></div>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-person-fill"></i> Nom *</label>
                                <input type="text" name="nom" class="form-control"
                                       value="<?= htmlspecialchars($user->getNom()) ?>" required>
                                <div class="error-message" id="nom-error"></div>
                            </div>
                            <div class="form-group full-width">
                                <label><i class="bi bi-envelope-fill"></i> Email *</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($user->getEmail()) ?>" required>
                                <div class="error-message" id="email-error"></div>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-calendar-fill"></i> Date de naissance</label>
                                <input type="date" name="dateNaissance" class="form-control"
                                       value="<?= $user->getDateNaissance() ? htmlspecialchars($user->getDateNaissance()) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-geo-alt-fill"></i> Adresse</label>
                                <input type="text" name="adresse" class="form-control"
                                       value="<?= $user->getAdresse() ? htmlspecialchars($user->getAdresse()) : '' ?>">
                            </div>
                        </div>
                        <div class="actions-row">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save-fill"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Password -->
                <div class="tab-pane" id="tab-password">
                    <form method="POST" id="passwordForm">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label><i class="bi bi-key-fill"></i> Mot de passe actuel *</label>
                                <div class="password-input-container">
                                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')"><i class="bi bi-eye-slash"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-lock-fill"></i> Nouveau mot de passe *</label>
                                <div class="password-input-container">
                                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')"><i class="bi bi-eye-slash"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-lock-fill"></i> Confirmer *</label>
                                <div class="password-input-container">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')"><i class="bi bi-eye-slash"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="actions-row">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-arrow-repeat"></i> Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Info -->
                <div class="tab-pane" id="tab-info">
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-label">Nom complet</div>
                            <div class="info-value"><?= htmlspecialchars($user->getPrenom().' '.$user->getNom()) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= htmlspecialchars($user->getEmail()) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Rôle</div>
                            <div class="info-value"><?= ucfirst($user->getRole()) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Statut</div>
                            <div class="info-value"><?= ucfirst($user->getStatut()) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Membre depuis</div>
                            <?php $d2 = new DateTime($user->getDateInscription()); ?>
                            <div class="info-value"><?= $d2->format('d/m/Y à H:i') ?></div>
                        </div>
                        <?php if ($user->getDateNaissance()): ?>
                        <div class="info-card">
                            <div class="info-label">Date de naissance</div>
                            <?php $dn = new DateTime($user->getDateNaissance()); ?>
                            <div class="info-value"><?= $dn->format('d/m/Y') ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($user->getAdresse()): ?>
                        <div class="info-card">
                            <div class="info-label">Adresse</div>
                            <div class="info-value"><?= htmlspecialchars($user->getAdresse()) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="alert alert-success" style="margin-top:20px">
                        <i class="bi bi-shield-lock-fill"></i>
                        <div>Vous avez accès aux fonctionnalités d'administration.
                            <a href="../../backoffice/admin-dashboard.php" style="color:inherit;font-weight:700">Aller au dashboard →</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div><!-- /profile-card-body -->
        </div><!-- /profile-card -->

    </div><!-- /main-col -->

    <!-- ══════════════════════
         SIDEBAR DROITE
    ══════════════════════ -->
    <aside class="sidebar" id="sidebar">

        <!-- Logo -->
        <div class="sb-logo">
            <a href="../home/index.php">
                <div class="sb-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
                <div class="sb-logo-text">Med<span>Chain</span></div>
            </a>
        </div>

        <!-- User -->
        <div class="sb-user">
            <div class="sb-avatar">
                <?php if ($photo_url && $photo_url != '/assets/images/default-avatar.png'): ?>
                    <img src="<?= htmlspecialchars($photo_url) ?>" alt="Photo">
                <?php else: ?>
                    <?= strtoupper(substr($user->getPrenom(),0,1).substr($user->getNom(),0,1)) ?>
                <?php endif; ?>
            </div>
            <div class="sb-user-info">
                <div class="sb-user-name"><?= htmlspecialchars($user->getPrenom().' '.$user->getNom()) ?></div>
                <div class="sb-user-role"><?= ucfirst($user->getRole()) ?></div>
            </div>
            <div class="sb-online"></div>
        </div>

        <!-- Nav -->
        <nav class="sb-nav">

            <div class="sb-label">Navigation</div>
            <a href="../home/index.php"   class="sb-item"><i class="bi bi-house-door-fill"></i> Accueil</a>
            <a href="profile.php"         class="sb-item active"><i class="bi bi-person-circle"></i> Mon Profil</a>
            <a href="../appointments/index.php"    class="sb-item"><i class="bi bi-calendar-check-fill"></i> Rendez-vous</a>
            <a href="../medical/index.php"         class="sb-item"><i class="bi bi-file-medical-fill"></i> Dossier médical</a>
            <a href="../messages/index.php"        class="sb-item"><i class="bi bi-chat-dots-fill"></i> Messages</a>

            <?php if ($isAdmin): ?>
            <div class="sb-label">Administration</div>
            <a href="../../backoffice/admin-dashboard.php" class="sb-item"><i class="bi bi-speedometer2"></i> Dashboard Admin</a>
            <a href="../../backoffice/admin-users.php"     class="sb-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
            <?php endif; ?>

            <div class="sb-spacer"></div>

            <div class="sb-label">Compte</div>
            <a href="../../../controllers/logout.php" class="sb-item logout"
               onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>

        </nav>
    </aside>

</div><!-- /layout -->

<script>
    /* ── Sidebar mobile ── */
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sbOverlay');
    const toggle   = document.getElementById('sbToggle');

    function openSidebar()  { sidebar.classList.add('open');  overlay.classList.add('open'); }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }

    toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
    overlay.addEventListener('click', closeSidebar);

    /* ── Tabs ── */
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        });
    });

    /* ── Avatar upload ── */
    const avatarEditBtn = document.getElementById('avatarEditBtn');
    const photoInput    = document.getElementById('photoInput');
    const photoForm     = document.getElementById('photoUploadForm');
    if (avatarEditBtn) avatarEditBtn.addEventListener('click', () => photoInput.click());
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            if (this.files[0].size > 2 * 1024 * 1024) { alert('Taille max : 2 MB'); this.value = ''; return; }
            if (!['image/jpeg','image/png','image/gif','image/webp'].includes(this.files[0].type)) {
                alert('Format accepté : JPEG, PNG, GIF ou WebP'); this.value = ''; return;
            }
            photoForm.submit();
        });
    }

    /* ── Toggle password visibility ── */
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon  = input.parentElement.querySelector('.password-toggle i');
        if (input.type === 'password') { input.type = 'text';     icon.classList.replace('bi-eye-slash','bi-eye'); }
        else                           { input.type = 'password'; icon.classList.replace('bi-eye','bi-eye-slash'); }
    }

    /* ── Auto-dismiss alerts ── */
    document.querySelectorAll('.alert-close').forEach(btn =>
        btn.addEventListener('click', () => btn.closest('.alert').remove())
    );
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(a => {
            a.style.transition = 'all .3s';
            a.style.opacity    = '0';
            a.style.transform  = 'translateY(-10px)';
            setTimeout(() => a.remove(), 300);
        });
    }, 5000);

    /* ── Profile form validation ── */
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', e => {
            let ok = true;
            const prenom = profileForm.querySelector('[name="prenom"]');
            const nom    = profileForm.querySelector('[name="nom"]');
            const email  = profileForm.querySelector('[name="email"]');
            const validate = (el, errId, test, msg) => {
                if (!test) { el.classList.add('is-invalid'); document.getElementById(errId).textContent = msg; ok = false; }
                else       { el.classList.remove('is-invalid'); el.classList.add('is-valid'); document.getElementById(errId).textContent = ''; }
            };
            validate(prenom, 'prenom-error', prenom.value.trim().length >= 2, 'Prénom invalide (2+ caractères)');
            validate(nom,    'nom-error',    nom.value.trim().length   >= 2, 'Nom invalide (2+ caractères)');
            validate(email,  'email-error',  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim()), 'Email invalide');
            if (!ok) e.preventDefault();
        });
    }

    /* ── Password form validation ── */
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', e => {
            const np = passwordForm.querySelector('[name="new_password"]');
            const cp = passwordForm.querySelector('[name="confirm_password"]');
            if (np.value !== cp.value)    { alert('Les mots de passe ne correspondent pas'); e.preventDefault(); }
            else if (np.value.length < 6) { alert('Minimum 6 caractères requis');            e.preventDefault(); }
        });
    }
</script>
</body>
</html>