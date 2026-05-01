<?php
session_start();
include_once '../../../controllers/AuthController.php'; 
include_once '../../../controllers/PasswordController.php';
include_once '../../../controllers/ProfileController.php';

$authController = new AuthController();
$passwordController = new PasswordController();
$profileController = new ProfileController();

if (!$authController->isLoggedIn()) {
    header('Location: sign-in.php');
    exit;
}

$user = $authController->getCurrentUser();
$isAdmin = $user && $user->estAdmin();

$profile_error = null;
$profile_success = null;
$photo_error = null;
$photo_success = null;
$password_error = null;
$password_success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $result = $profileController->updateProfile($user->getId(), $_POST);
        
        if ($result['success']) {
            $profile_success = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
            $user = $authController->getCurrentUser();
        } else {
            $profile_error = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
        }
    }
    
    if (isset($_POST['update_photo']) && isset($_FILES['photo_profil'])) {
        $result = $profileController->updateProfilePhoto($user->getId(), $_FILES['photo_profil']);
        
        if ($result['success']) {
            $photo_success = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
            $user = $authController->getCurrentUser();
        } else {
            $photo_error = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
        }
    }
   
    if (isset($_POST['delete_photo'])) {
        $result = $profileController->deleteProfilePhoto($user->getId());
        
        if ($result['success']) {
            $photo_success = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
            $user = $authController->getCurrentUser();
        } else {
            $photo_error = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_error = "Veuillez remplir tous les champs";
        } elseif ($new_password !== $confirm_password) {
            $password_error = "Les nouveaux mots de passe ne correspondent pas";
        } elseif (strlen($new_password) < 6) {
            $password_error = "Le nouveau mot de passe doit contenir au moins 6 caractères";
        } else {
            $result = $passwordController->changePassword($user->getId(), $current_password, $new_password);
            
            if ($result['success']) {
                $password_success = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
                $_POST = [];
            } else {
                $password_error = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
            }
        }
    }
}

function getProfilePhotoUrl($user) {
    return $user->getPhotoProfilUrl();
}
$photo_url = getProfilePhotoUrl($user);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    
    <style>
        /* ══════════════════════════════════════
           RESET & ROOT VARIABLES
        ══════════════════════════════════════ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
            --green-deep: #094D3C;
            --green-light: #E8F7F2;
            --green-pale: #F2FBF7;
            --navy: #1E3A52;
            --navy-light: #2C4964;
            --gray-700: #374151;
            --gray-500: #6B7280;
            --gray-200: #E5E7EB;
            --gray-100: #F9FAFB;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --shadow-lg: 0 12px 40px rgba(0,0,0,.10);
            --shadow-green: 0 8px 30px rgba(29,158,117,.22);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 20px;
            --radius-xl: 28px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -120px;
            right: -120px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(29,158,117,.10) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -80px;
            left: -80px;
            width: 380px;
            height: 380px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(29,158,117,.07) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* ═══ FRONTOFFICE SIDEBAR — design moderne ═══ */
        .dashboard-container { display:grid; grid-template-columns:280px 1fr; min-height:100vh; position:relative; z-index:2; }
        .dashboard-sidebar { background:linear-gradient(160deg,#fff 0%,#f0fdf9 60%,#e6faf3 100%); border-right:1px solid rgba(29,158,117,.15); position:sticky; top:0; height:100vh; display:flex; flex-direction:column; overflow-y:auto; box-shadow:4px 0 24px rgba(29,158,117,.08); }
        .sidebar-logo-zone { padding:26px 22px 20px; border-bottom:1px solid rgba(29,158,117,.12); }
        .sidebar-logo-link { display:flex; align-items:center; gap:12px; text-decoration:none; }
        .sidebar-logo-icon { width:42px; height:42px; background:linear-gradient(135deg,var(--green),var(--green-dark)); border-radius:13px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(29,158,117,.35); }
        .sidebar-logo-icon i { font-size:20px; color:white; }
        .sidebar-logo-text { font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--navy); }
        .sidebar-logo-text span { color:var(--green); }
        .sidebar-tagline { font-size:11px; color:var(--gray-500); margin-top:3px; }
        .sidebar-user-card { margin:18px 16px; background:linear-gradient(135deg,var(--green),var(--green-dark)); border-radius:var(--radius-lg); padding:18px 16px; box-shadow:var(--shadow-green); position:relative; overflow:hidden; }
        .sidebar-user-card::before { content:''; position:absolute; top:-20px; right:-20px; width:90px; height:90px; border-radius:50%; background:rgba(255,255,255,.1); }
        .sidebar-user-avatar { width:44px; height:44px; border-radius:50%; background:rgba(255,255,255,.25); border:2.5px solid rgba(255,255,255,.5); display:flex; align-items:center; justify-content:center; margin-bottom:12px; }
        .sidebar-user-avatar i { font-size:22px; color:white; }
        .sidebar-user-name { font-size:15px; font-weight:700; color:white; }
        .sidebar-user-role { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:rgba(255,255,255,.85); background:rgba(255,255,255,.18); padding:3px 10px; border-radius:20px; margin-top:4px; }
        .sidebar-health-widget { margin:0 16px 6px; background:var(--white); border:1px solid rgba(29,158,117,.15); border-radius:var(--radius-md); padding:14px 16px; }
        .sidebar-health-label { font-size:11px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
        .sidebar-health-bar-wrap { background:var(--gray-200); border-radius:6px; height:6px; overflow:hidden; }
        .sidebar-health-bar { height:100%; border-radius:6px; background:linear-gradient(90deg,var(--green),#34D399); animation:health-grow 1.2s ease-out forwards; }
        @keyframes health-grow { from{width:0} to{width:78%} }
        .sidebar-health-stats { display:flex; justify-content:space-between; margin-top:8px; }
        .sidebar-health-stat { font-size:12px; color:var(--gray-500); }
        .sidebar-health-stat strong { color:var(--green); font-weight:700; }
        .sidebar-nav { flex:1; display:flex; flex-direction:column; gap:3px; padding:12px 12px 0; }
        .sidebar-nav-section-label { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:#A0AEC0; padding:14px 12px 6px; }
        .sidebar-nav-item { display:flex; align-items:center; gap:13px; padding:11px 14px; color:var(--gray-500); text-decoration:none; border-radius:var(--radius-md); transition:all .25s; font-size:14px; font-weight:500; position:relative; }
        .sidebar-nav-item .nav-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; background:rgba(29,158,117,.08); color:var(--green); transition:all .25s; }
        .sidebar-nav-item:hover { background:rgba(29,158,117,.07); color:var(--green-dark); }
        .sidebar-nav-item:hover .nav-icon { background:rgba(29,158,117,.15); transform:scale(1.08); }
        .sidebar-nav-item.active { background:linear-gradient(90deg,rgba(29,158,117,.12),rgba(29,158,117,.04)); color:var(--green-dark); font-weight:600; }
        .sidebar-nav-item.active .nav-icon { background:linear-gradient(135deg,var(--green),var(--green-dark)); color:white; box-shadow:0 4px 12px rgba(29,158,117,.30); }
        .sidebar-nav-item.active::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:3px; border-radius:0 3px 3px 0; background:var(--green); }
        .sidebar-nav-item.logout { color:#E53E3E; margin:0 0 4px; }
        .sidebar-nav-item.logout .nav-icon { background:rgba(229,62,62,.08); color:#E53E3E; }
        .sidebar-nav-item.logout:hover { background:rgba(229,62,62,.07); }
        .sidebar-footer { padding:16px; border-top:1px solid rgba(29,158,117,.10); margin-top:auto; }
        .sidebar-footer-back { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:var(--radius-md); background:var(--green-pale); color:var(--green-dark); font-size:13px; font-weight:600; text-decoration:none; transition:all .2s; border:1px solid rgba(29,158,117,.2); }
        .sidebar-footer-back:hover { background:rgba(29,158,117,.15); transform:translateX(-3px); }
        .dashboard-main { padding:32px 40px; overflow-y:auto; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid rgba(29,158,117,.15);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: rgba(29,158,117,.3);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.primary { background: rgba(29,158,117,0.1); color: var(--green); }
        .stat-icon.success { background: rgba(34,197,94,0.1); color: #22C55E; }
        .stat-icon.warning { background: rgba(245,158,11,0.1); color: #F59E0B; }
        .stat-icon.danger { background: rgba(239,68,68,0.1); color: #EF4444; }
        .stat-icon.info { background: rgba(59,130,246,0.1); color: #3B82F6; }

        .stat-content h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 4px;
        }

        .stat-content p {
            font-size: 13px;
            color: var(--gray-500);
        }

        .profile-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(29,158,117,.15);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 32px;
        }

        .profile-header {
            padding: 32px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .profile-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--navy);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-header h2 i {
            color: var(--green);
        }

        .role-badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        .role-badge.admin { background: #FEF2F2; color: #EF4444; }
        .role-badge.user { background: #F0FDF4; color: #22C55E; }

        .profile-body {
            padding: 32px;
        }

        .avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 16px;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--green);
            box-shadow: var(--shadow-md);
        }

        .avatar-initials {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 700;
            color: white;
            border: 4px solid var(--green-light);
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 36px;
            height: 36px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            color: var(--green);
            transition: all 0.3s;
            border: none;
        }

        .avatar-edit-btn:hover {
            background: var(--green);
            color: white;
            transform: scale(1.1);
        }

        .profile-name {
            text-align: center;
            margin-bottom: 8px;
        }

        .profile-name h3 {
            font-size: 22px;
            font-weight: 700;
            color: var(--navy);
        }

        .profile-name p {
            color: var(--gray-500);
            font-size: 14px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.actif { background: #F0FDF4; color: #22C55E; }
        .status-badge.inactif { background: #FEF2F2; color: #EF4444; }
        .status-badge.en_attente { background: #FEF3C7; color: #F59E0B; }

        .profile-tabs {
            display: flex;
            gap: 8px;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 24px;
        }

        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-500);
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 2px solid transparent;
        }

        .tab-btn:hover {
            color: var(--green);
        }

        .tab-btn.active {
            color: var(--green);
            border-bottom-color: var(--green);
        }

        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--navy);
            font-weight: 600;
            font-size: 13px;
        }

        .form-group label i {
            color: var(--green);
            margin-right: 6px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            background: var(--white);
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(29,158,117,.15);
        }

        .form-control.is-invalid {
            border-color: #EF4444;
            background: #FEF2F2;
        }

        .form-control.is-valid {
            border-color: #22C55E;
            background: #F0FDF4;
        }

        .error-message {
            font-size: 11px;
            color: #EF4444;
            margin-top: 5px;
        }

        .password-input-container {
            position: relative;
        }

        .password-input-container .form-control {
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
        }

        .btn {
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white;
            box-shadow: 0 3px 12px rgba(29,158,117,.30);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29,158,117,.40);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--gray-200);
            color: var(--gray-700);
        }

        .btn-outline:hover {
            border-color: var(--green);
            color: var(--green);
        }

        .btn-danger {
            background: #EF4444;
            color: white;
        }

        .btn-danger:hover {
            background: #DC2626;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-card {
            background: var(--gray-100);
            border-radius: var(--radius-md);
            padding: 16px;
            border-left: 3px solid var(--green);
        }

        .info-card .info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--gray-500);
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .info-card .info-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--navy);
        }

        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #F0FDF4;
            border-left: 4px solid #22C55E;
            color: #166534;
        }

        .alert-error {
            background: #FEF2F2;
            border-left: 4px solid #EF4444;
            color: #B91C1C;
        }

        .alert i {
            font-size: 18px;
        }

        .alert-close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
        }

        .quick-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
        }

        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 240px 1fr;
            }
            .dashboard-main {
                padding: 24px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            .dashboard-sidebar {
                position: fixed;
                left: -280px;
                top: 0;
                bottom: 0;
                width: 260px;
                z-index: 1000;
                transition: left 0.3s;
            }
            .dashboard-sidebar.open {
                left: 0;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .dashboard-main {
                padding: 16px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .quick-actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        <?php if ($isAdmin): ?>
        /* Admin Blue Theme Override */
        .dashboard-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important;
            border-right: none !important;
        }
        .sidebar-logo-icon {
            background: rgba(255,255,255,0.1) !important;
        }
        .sidebar-logo-text span {
            color: #3b82f6 !important;
        }
        .sidebar-nav-item.active {
            background: rgba(59,130,246,0.2) !important;
            color: #3b82f6 !important;
        }
        .sidebar-nav-item.active .nav-icon {
            background: #3b82f6 !important;
            color: white !important;
        }
        .sidebar-nav-item.active::before {
            background: #3b82f6 !important;
        }
        .sidebar-nav-item:not(.active) {
            color: #94A3B8 !important;
        }
        .sidebar-nav-item:not(.active):hover {
            background: rgba(255,255,255,0.1) !important;
            color: white !important;
        }
        .sidebar-nav-item:not(.active):hover .nav-icon {
            background: rgba(255,255,255,0.15) !important;
        }
        .sidebar-nav-section-label {
            color: #64748B !important;
        }
        .sidebar-health-widget {
            display: none !important; /* Admins don't need the health widget */
        }
        .sidebar-user-card {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
        }
        .sidebar-user-avatar {
            background: rgba(255,255,255,0.1) !important;
            border-color: rgba(255,255,255,0.2) !important;
        }
        .sidebar-footer-back {
            background: rgba(255,255,255,0.05) !important;
            color: white !important;
            border-color: rgba(255,255,255,0.1) !important;
        }
        <?php endif; ?>
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar FrontOffice Moderne -->
    <aside class="dashboard-sidebar" id="sidebar">
      <div class="sidebar-logo-zone">
        <a href="../home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="<?= $isAdmin ? 'fas fa-hospital-alt' : 'bi bi-plus-square-fill' ?>"></i></div>
          <div>
            <div class="sidebar-logo-text">Med<span>Chain</span></div>
            <div class="sidebar-tagline"><?php
              if ($user->getRole() === 'medecin') echo 'Espace Médecin';
              elseif ($user->getRole() === 'admin') echo 'Administration';
              else echo 'Espace Patient';
            ?></div>
          </div>
        </a>
      </div>
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="<?= $isAdmin ? 'fas fa-user-shield' : 'bi bi-person-fill' ?>"></i></div>
        <div class="sidebar-user-name"><?= htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) ?></div>
        <div class="sidebar-user-role"><i class="<?= $isAdmin ? 'fas fa-shield-alt' : 'bi bi-heart-pulse-fill' ?>"></i> <?= ucfirst($user->getRole()) ?></div>
      </div>
      <div class="sidebar-health-widget">
        <div class="sidebar-health-label"><i class="bi bi-activity" style="color:var(--green);margin-right:5px;"></i>Suivi de santé</div>
        <div class="sidebar-health-bar-wrap"><div class="sidebar-health-bar"></div></div>
        <div class="sidebar-health-stats">
          <span class="sidebar-health-stat">Profil <strong>78%</strong> complet</span>
          <span class="sidebar-health-stat" style="color:var(--green);"><i class="bi bi-shield-check"></i> Actif</span>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Navigation</div>
        <a href="../home/index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="<?= $isAdmin ? 'fas fa-home' : 'bi bi-house-door-fill' ?>"></i></span> Accueil
        </a>
        <a href="profile.php" class="sidebar-nav-item active">
          <span class="nav-icon"><i class="<?= $isAdmin ? 'fas fa-user-circle' : 'bi bi-person-fill' ?>"></i></span> Mon Profil
        </a>
        <?php if ($user->getRole() === 'patient'): ?>
        <div class="sidebar-nav-section-label">Mes Services</div>
        <a href="../rendezvous/index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-calendar-check"></i></span> Mes Rendez-vous
        </a>
        <a href="../ficherdv/index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span> Mes Fiches Médicales
        </a>
        <?php elseif ($user->getRole() === 'medecin'): ?>
        <div class="sidebar-nav-section-label">Mes Services</div>
        <a href="../../backoffice/rendezvous/medecin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-calendar-check"></i></span> Rendez-vous
        </a>
        <a href="../../backoffice/ficherdv/medecin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span> Fiches Médicales
        </a>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
        <div class="sidebar-nav-section-label">Administration</div>
        <a href="../../backoffice/admin-dashboard.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span> Tableau de bord
        </a>
        <a href="../../backoffice/admin-users.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="fas fa-users"></i></span> Utilisateurs
        </a>
        <a href="../../backoffice/admin-create-user.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="fas fa-user-plus"></i></span> Nouvel utilisateur
        </a>
        <a href="../../backoffice/rendezvous/admin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="fas fa-calendar-check"></i></span> Rendez-vous
        </a>
        <a href="../../backoffice/ficherdv/admin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="fas fa-file-medical-alt"></i></span> Fiches Médicales
        </a>
        <a href="../../backoffice/admin-reports-statistics.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="fas fa-chart-pie"></i></span> Statistiques
        </a>
        <?php endif; ?>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout" onclick="confirmSwal(event, this, '')">
          <span class="nav-icon"><i class="<?= $isAdmin ? 'fas fa-sign-out-alt' : 'bi bi-box-arrow-left' ?>"></i></span> Déconnexion
        </a>
        <div style="margin-top:10px;">
          <a href="../home/index.php" class="sidebar-footer-back">
            <i class="<?= $isAdmin ? 'fas fa-arrow-circle-left' : 'bi bi-arrow-left-circle-fill' ?>"></i> Retour au site
          </a>
        </div>
      </div>
    </aside>
    
    <!-- Main Content -->
    <main class="dashboard-main">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $user->estActif() ? 'Actif' : 'Inactif'; ?></h3>
                    <p>Statut</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon <?php echo $isAdmin ? 'danger' : 'success'; ?>">
                    <i class="bi <?php echo $isAdmin ? 'bi-shield-lock-fill' : 'bi-person-fill'; ?>"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo ucfirst($user->getRole()); ?></h3>
                    <p>Rôle</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-calendar-date-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php 
                        $date = new DateTime($user->getDateInscription());
                        echo $date->format('d/m/Y');
                    ?></h3>
                    <p>Membre depuis</p>
                </div>
            </div>
            
            <?php if ($user->getDateNaissance()): ?>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-cake2-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $user->getAge(); ?> ans</h3>
                    <p>Âge</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <h2><i class="bi bi-person-circle"></i> Gestion du profil</h2>
                <span class="role-badge <?php echo $isAdmin ? 'admin' : 'patient'; ?>">
                    <i class="bi <?php echo $isAdmin ? 'bi-shield-lock-fill' : 'bi-person-fill'; ?>"></i>
                    <?php echo ucfirst($user->getRole()); ?>
                </span>
            </div>
            
            <div class="profile-body">
                <!-- Alerts -->
                <?php if ($profile_success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <div><?php echo $profile_success; ?></div>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($profile_error): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?php echo $profile_error; ?></div>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($photo_success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <div><?php echo $photo_success; ?></div>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($photo_error): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?php echo $photo_error; ?></div>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($password_success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <div><?php echo $password_success; ?></div>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($password_error): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?php echo $password_error; ?></div>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                
                <!-- Avatar Section -->
                <div class="avatar-section">
                    <div class="avatar-container">
                        <?php if ($photo_url && $photo_url != '/assets/images/default-avatar.png'): ?>
                            <img src="<?php echo $photo_url; ?>" alt="Photo de profil" class="avatar" id="currentPhoto">
                        <?php else: ?>
                            <div class="avatar-initials" id="currentInitials">
                                <?php echo strtoupper(substr($user->getPrenom(), 0, 1) . substr($user->getNom(), 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <button class="avatar-edit-btn" id="avatarEditBtn">
                            <i class="bi bi-camera-fill"></i>
                        </button>
                        <form method="POST" enctype="multipart/form-data" id="photoUploadForm" style="display:none;">
                            <input type="file" name="photo_profil" id="photoInput" accept="image/*">
                            <input type="hidden" name="update_photo" value="1">
                        </form>
                        <?php if ($photo_url && $photo_url != '/assets/images/default-avatar.png'): ?>
                        <form method="POST" id="deletePhotoForm" style="display:none;">
                            <input type="hidden" name="delete_photo" value="1">
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="profile-name">
                        <h3><?php echo htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()); ?></h3>
                        <p><?php echo htmlspecialchars($user->getEmail()); ?></p>
                        <span class="status-badge <?php echo $user->estActif() ? 'actif' : 'inactif'; ?>">
                            <i class="bi bi-<?php echo $user->estActif() ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                            <?php echo ucfirst($user->getStatut()); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="profile-tabs">
                    <button class="tab-btn active" data-tab="edit">
                        <i class="bi bi-pencil-fill"></i> Modifier
                    </button>
                    <button class="tab-btn" data-tab="password">
                        <i class="bi bi-key-fill"></i> Mot de passe
                    </button>
                    <button class="tab-btn" data-tab="info">
                        <i class="bi bi-info-circle-fill"></i> Informations
                    </button>
                </div>
                
                <!-- Tab: Edit Profile -->
                <div class="tab-pane active" id="tab-edit">
                    <form method="POST" id="profileForm">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="bi bi-person-fill"></i> Prénom *</label>
                                <input type="text" name="prenom" class="form-control" 
                                       value="<?php echo htmlspecialchars($user->getPrenom()); ?>" required>
                                <div class="error-message" id="prenom-error"></div>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-person-fill"></i> Nom *</label>
                                <input type="text" name="nom" class="form-control" 
                                       value="<?php echo htmlspecialchars($user->getNom()); ?>" required>
                                <div class="error-message" id="nom-error"></div>
                            </div>
                            <div class="form-group full-width">
                                <label><i class="bi bi-envelope-fill"></i> Email *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user->getEmail()); ?>" required>
                                <div class="error-message" id="email-error"></div>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-calendar-fill"></i> Date de naissance</label>
                                <input type="date" name="dateNaissance" class="form-control" 
                                       value="<?php echo $user->getDateNaissance() ? htmlspecialchars($user->getDateNaissance()) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-geo-alt-fill"></i> Adresse</label>
                                <input type="text" name="adresse" class="form-control" 
                                       value="<?php echo $user->getAdresse() ? htmlspecialchars($user->getAdresse()) : ''; ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save-fill"></i> Enregistrer
                        </button>
                    </form>
                </div>
                
                <!-- Tab: Change Password -->
                <div class="tab-pane" id="tab-password">
                    <form method="POST" id="passwordForm">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label><i class="bi bi-key-fill"></i> Mot de passe actuel</label>
                                <div class="password-input-container">
                                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-key-fill"></i> Nouveau mot de passe</label>
                                <div class="password-input-container">
                                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-key-fill"></i> Confirmer</label>
                                <div class="password-input-container">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-repeat"></i> Changer le mot de passe
                        </button>
                    </form>
                </div>
                
                <!-- Tab: Information -->
                <div class="tab-pane" id="tab-info">
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-label">Nom complet</div>
                            <div class="info-value"><?php echo htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()); ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($user->getEmail()); ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Rôle</div>
                            <div class="info-value"><?php echo ucfirst($user->getRole()); ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Statut</div>
                            <div class="info-value"><?php echo ucfirst($user->getStatut()); ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Date d'inscription</div>
                            <div class="info-value"><?php 
                                $date = new DateTime($user->getDateInscription());
                                echo $date->format('d/m/Y à H:i');
                            ?></div>
                        </div>
                        <?php if ($user->getDateNaissance()): ?>
                        <div class="info-card">
                            <div class="info-label">Date de naissance</div>
                            <div class="info-value"><?php 
                                $dateNaissance = new DateTime($user->getDateNaissance());
                                echo $dateNaissance->format('d/m/Y');
                            ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($user->getAdresse()): ?>
                        <div class="info-card">
                            <div class="info-label">Adresse</div>
                            <div class="info-value"><?php echo htmlspecialchars($user->getAdresse()); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($isAdmin): ?>
                    <div class="alert alert-success" style="margin-top: 20px;">
                        <i class="bi bi-shield-lock-fill"></i>
                        <div>Vous avez accès aux fonctionnalités d'administration du système.</div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="../home/index.php" class="btn btn-outline">
                        <i class="bi bi-house-door-fill"></i> Accueil
                    </a>
                    <?php if ($isAdmin): ?>
                        <a href="../../backoffice/admin-dashboard.php" class="btn btn-primary">
                            <i class="bi bi-speedometer2"></i> Admin Dashboard
                        </a>
                    <?php else: ?>
                        <a href="../appointments/" class="btn btn-primary">
                            <i class="bi bi-calendar-check-fill"></i> Mes rendez-vous
                        </a>
                    <?php endif; ?>
                    <a href="../../../controllers/logout.php" class="btn btn-danger" onclick="confirmSwal(event, this, '')">
                        <i class="bi bi-box-arrow-right"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 20 });
    
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
    }
    
    // Tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.getAttribute('data-tab');
            
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            btn.classList.add('active');
            document.getElementById(`tab-${tabId}`).classList.add('active');
        });
    });
    
    // Avatar edit
    const avatarEditBtn = document.getElementById('avatarEditBtn');
    const photoInput = document.getElementById('photoInput');
    const photoUploadForm = document.getElementById('photoUploadForm');
    
    if (avatarEditBtn) {
        avatarEditBtn.addEventListener('click', () => {
            photoInput.click();
        });
    }
    
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (file.size > 2 * 1024 * 1024) {
                    alert('La taille de la photo ne doit pas dépasser 2MB');
                    this.value = '';
                    return;
                }
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Formats acceptés: JPEG, PNG, GIF, WebP');
                    this.value = '';
                    return;
                }
                photoUploadForm.submit();
            }
        });
    }
    
    // Password toggle
    function togglePassword(fieldId) {
        const input = document.getElementById(fieldId);
        const icon = input.parentElement.querySelector('.password-toggle i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    }
    
    // Close alerts
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.alert').remove();
        });
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
    
    // Form validation
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', (e) => {
            let isValid = true;
            const prenom = profileForm.querySelector('[name="prenom"]');
            const nom = profileForm.querySelector('[name="nom"]');
            const email = profileForm.querySelector('[name="email"]');
            
            if (!prenom.value.trim() || prenom.value.trim().length < 2) {
                prenom.classList.add('is-invalid');
                document.getElementById('prenom-error').textContent = 'Prénom invalide (2+ caractères)';
                isValid = false;
            } else {
                prenom.classList.remove('is-invalid');
                prenom.classList.add('is-valid');
            }
            
            if (!nom.value.trim() || nom.value.trim().length < 2) {
                nom.classList.add('is-invalid');
                document.getElementById('nom-error').textContent = 'Nom invalide (2+ caractères)';
                isValid = false;
            } else {
                nom.classList.remove('is-invalid');
                nom.classList.add('is-valid');
            }
            
            if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                email.classList.add('is-invalid');
                document.getElementById('email-error').textContent = 'Email invalide';
                isValid = false;
            } else {
                email.classList.remove('is-invalid');
                email.classList.add('is-valid');
            }
            
            if (!isValid) e.preventDefault();
        });
    }
    
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', (e) => {
            const newPass = passwordForm.querySelector('[name="new_password"]');
            const confirmPass = passwordForm.querySelector('[name="confirm_password"]');
            
            if (newPass.value !== confirmPass.value) {
                alert('Les mots de passe ne correspondent pas');
                e.preventDefault();
            } else if (newPass.value.length < 6) {
                alert('Le mot de passe doit contenir au moins 6 caractères');
                e.preventDefault();
            }
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
