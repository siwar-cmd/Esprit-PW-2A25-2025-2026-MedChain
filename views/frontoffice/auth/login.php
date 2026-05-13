<?php
session_start();
include_once '../../../controllers/AuthController.php';
require_once '../../../controllers/OAuthController.php';

$authController = new AuthController();
$oauthController = new OAuthController();

if ($authController->isLoggedIn()) {
    $currentUser = $authController->getCurrentUser();
    if ($currentUser && $currentUser->estAdmin()) {
        header('Location: ../../backoffice/admin-dashboard.php');
    } else {
        header('Location: profile.php');
    }
    exit;
}

$error = '';
$email = '';

$googleAuthUrl   = $oauthController->getGoogleAuthUrl();
$facebookAuthUrl = $oauthController->getFacebookAuthUrl();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $errors       = [];

    if (empty($email)) {
        $errors[] = "L'adresse email est obligatoire";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    } elseif (strlen($email) > 255) {
        $errors[] = "L'adresse email est trop longue";
    } else {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    if (empty($mot_de_passe)) {
        $errors[] = "Le mot de passe est obligatoire";
    } elseif (strlen($mot_de_passe) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    } elseif (strlen($mot_de_passe) > 255) {
        $errors[] = "Le mot de passe est trop long";
    }

    if (empty($errors)) {
        $result = $authController->login($email, $mot_de_passe);
        if ($result['success']) {
            $user = $authController->getCurrentUser();
            if ($user && $user->estAdmin()) {
                header('Location: ../../backoffice/admin-dashboard.php');
            } else {
                header('Location: profile.php');
            }
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - MedChain</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />

    <style>
        /* ══════════════════════════════════════
           RESET & ROOT VARIABLES
        ══════════════════════════════════════ */
        * { margin: 0; padding: 0; box-sizing: border-box; }

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
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.04);
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
            background: linear-gradient(145deg,#f0faf6 0%,#e8f7f1 50%,#ddf3ea 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: -120px; right: -120px;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle,rgba(29,158,117,.10) 0%,transparent 70%);
            pointer-events: none; z-index: 0;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -80px; left: -80px;
            width: 380px; height: 380px;
            border-radius: 50%;
            background: radial-gradient(circle,rgba(29,158,117,.07) 0%,transparent 70%);
            pointer-events: none; z-index: 0;
        }

        .login-container { max-width: 480px; width: 100%; margin: 0 auto; position: relative; z-index: 2; }

        .login-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 48px 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(29,158,117,.15);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .login-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-green); }

        .logo {
            display: flex; align-items: center; justify-content: center;
            gap: 12px; margin-bottom: 32px; text-decoration: none;
            transition: transform 0.3s;
        }
        .logo:hover { transform: scale(1.02); }
        .logo-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg,var(--green),var(--green-dark));
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            box-shadow: var(--shadow-green);
        }
        .logo-icon i { font-size: 24px; color: white; }
        .logo-text { font-family: 'Syne',sans-serif; font-size: 26px; font-weight: 800; color: var(--navy); letter-spacing: -0.5px; }
        .logo-text span { color: var(--green); }

        .login-header { text-align: center; margin-bottom: 32px; }
        .login-header h2 { font-family: 'Syne',sans-serif; font-size: 28px; font-weight: 700; color: var(--navy); margin-bottom: 8px; }
        .login-header p { color: var(--gray-500); font-size: 14px; }

        .alert {
            padding: 16px 20px; border-radius: var(--radius-md); margin-bottom: 24px;
            display: flex; align-items: flex-start; gap: 12px;
            animation: slideIn 0.4s ease-out;
        }
        @keyframes slideIn { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }
        .alert-error  { background:#FEF2F2; border-left:4px solid #EF4444; color:#B91C1C; }
        .alert-success{ background:#F0FDF4; border-left:4px solid #22C55E; color:#166534; }
        .alert i { font-size: 18px; margin-top: 2px; }
        .alert div { flex: 1; line-height: 1.5; }

        .login-form { margin-bottom: 24px; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--navy); font-weight: 600; font-size: 14px; }
        .form-group label i { color: var(--green); margin-right: 6px; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 16px; color: var(--gray-500); font-size: 18px; transition: color 0.3s; }
        .form-control {
            width: 100%; padding: 14px 16px 14px 48px;
            border: 2px solid var(--gray-200); border-radius: var(--radius-md);
            font-size: 15px; font-family: 'DM Sans',sans-serif;
            background: var(--white); transition: all 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,.15); }
        .form-control.error { border-color: #EF4444; background: #FEF2F2; }
        .input-wrapper:focus-within .input-icon { color: var(--green); }
        .password-toggle { position: absolute; right: 16px; background: none; border: none; color: var(--gray-500); cursor: pointer; font-size: 18px; transition: color 0.3s; }
        .password-toggle:hover { color: var(--green); }
        .forgot-password { text-align: right; margin-top: 8px; }
        .forgot-password a { color: var(--green); text-decoration: none; font-size: 13px; font-weight: 500; }
        .forgot-password a:hover { color: var(--green-dark); text-decoration: underline; }

        .btn-login {
            width: 100%; padding: 14px 24px;
            background: linear-gradient(135deg,var(--green),var(--green-dark));
            color: white; border: none; border-radius: var(--radius-md);
            font-size: 16px; font-weight: 600; font-family: 'DM Sans',sans-serif;
            cursor: pointer; transition: all 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 3px 12px rgba(29,158,117,.30);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(29,158,117,.40); background: linear-gradient(135deg,var(--green-dark),var(--green-deep)); }
        .btn-login:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        .btn-login .spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .divider { display: flex; align-items: center; text-align: center; margin: 24px 0; color: var(--gray-500); font-size: 13px; }
        .divider::before,.divider::after { content:''; flex:1; border-bottom:1px solid var(--gray-200); }
        .divider::before { margin-right: 16px; }
        .divider::after  { margin-left: 16px; }

        .social-buttons { display: flex; flex-direction: column; gap: 12px; margin: 16px 0 8px; }

        .btn-social {
            display: flex; align-items: center; justify-content: center; gap: 12px;
            width: 100%; padding: 12px 16px; border-radius: var(--radius-md);
            font-size: 15px; font-weight: 500; font-family: 'DM Sans',sans-serif;
            text-decoration: none; cursor: pointer; transition: all 0.3s;
            border: 2px solid transparent;
        }
        .btn-google  { background: #fff; border-color: var(--gray-200); color: var(--gray-700); }
        .btn-google:hover  { border-color: #4285F4; color: #4285F4; background: #f0f4ff; transform: translateY(-2px); }
        .btn-facebook{ background: #fff; border-color: var(--gray-200); color: var(--gray-700); }
        .btn-facebook:hover{ border-color: #1877F2; color: #1877F2; background: #eff5ff; transform: translateY(-2px); }

        /* ══ BOUTON FACE ID ══════════════════════════════════════════════════ */
        .btn-faceid {
            display: flex; align-items: center; justify-content: center; gap: 12px;
            width: 100%; padding: 13px 16px; border-radius: var(--radius-md);
            font-size: 15px; font-weight: 600; font-family: 'DM Sans',sans-serif;
            cursor: pointer; transition: all 0.3s;
            background: linear-gradient(135deg,#0ea5e9,#0284c7);
            color: white; border: none;
            box-shadow: 0 3px 12px rgba(14,165,233,.30);
        }
        .btn-faceid:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(14,165,233,.45); background: linear-gradient(135deg,#0284c7,#0369a1); }

        /* Icône Face ID animée */
        .face-icon-wrap { position: relative; width: 22px; height: 22px; }
        .face-icon-wrap .bi { font-size: 20px; }
        .face-scan-ring {
            position: absolute; inset: -4px;
            border: 2px solid rgba(255,255,255,0.6);
            border-radius: 50%;
            animation: facePulse 2s ease-in-out infinite;
        }
        @keyframes facePulse {
            0%,100% { transform: scale(1); opacity: 0.6; }
            50%      { transform: scale(1.3); opacity: 0; }
        }

        .btn-register {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 12px 24px;
            background: var(--green-light); color: var(--green-dark);
            border-radius: var(--radius-md); font-size: 15px; font-weight: 600;
            text-decoration: none; transition: all 0.3s;
        }
        .btn-register:hover { background: var(--green); color: white; transform: translateY(-2px); }

        .back-home { text-align: center; margin-top: 20px; }
        .back-home a { color: var(--gray-500); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }
        .back-home a:hover { color: var(--green); }

        /* ══ MODAL FACE ID ═══════════════════════════════════════════════════ */
        #faceModal {
            display: none; position: fixed; inset: 0; z-index: 99999;
            align-items: center; justify-content: center;
        }
        #faceModalBackdrop {
            position: absolute; inset: 0;
            background: rgba(10,30,50,0.65); backdrop-filter: blur(8px);
        }
        #faceModalCard {
            position: relative; z-index: 2; width: 100%; max-width: 440px;
            margin: 20px; background: #fff; border-radius: 24px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.22); overflow: hidden;
            transform: scale(0.88); opacity: 0;
            transition: all 0.35s cubic-bezier(.34,1.56,.64,1);
        }

        /* Header du modal */
        .face-modal-header {
            background: linear-gradient(135deg,#0ea5e9,#0284c7);
            padding: 24px 28px 20px; position: relative;
        }
        .face-modal-header h3 { font-family: 'Syne',sans-serif; font-size: 20px; font-weight: 700; color: white; margin-bottom: 4px; }
        .face-modal-header p  { color: rgba(255,255,255,0.80); font-size: 13px; }
        .face-modal-close {
            position: absolute; top: 14px; right: 14px;
            background: rgba(255,255,255,0.2); border: none; color: white;
            width: 32px; height: 32px; border-radius: 50%; font-size: 16px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .face-modal-close:hover { background: rgba(255,255,255,0.35); }

        /* Corps du modal */
        .face-modal-body { padding: 24px 28px; }

        /* Zone webcam */
        .webcam-wrapper {
            position: relative; border-radius: 16px; overflow: hidden;
            background: #0f172a; margin-bottom: 16px;
            border: 3px solid #e2e8f0;
            box-shadow: inset 0 2px 12px rgba(0,0,0,0.15);
        }
        #faceVideo {
            width: 100%; display: block;
            transform: scaleX(-1); /* miroir */
        }

        /* Overlay scanning */
        .scan-overlay {
            position: absolute; inset: 0; pointer-events: none;
            display: flex; align-items: center; justify-content: center;
        }
        .scan-frame {
            width: 180px; height: 200px; position: relative;
        }
        .scan-frame::before,.scan-frame::after {
            content: ''; position: absolute;
            width: 30px; height: 30px;
            border-color: rgba(14,165,233,0.85);
            border-style: solid;
        }
        .scan-frame::before { top: 0; left: 0; border-width: 3px 0 0 3px; border-radius: 4px 0 0 0; }
        .scan-frame::after  { bottom: 0; right: 0; border-width: 0 3px 3px 0; border-radius: 0 0 4px 0; }
        .scan-corner-tr,.scan-corner-bl { position: absolute; width: 30px; height: 30px; border-color: rgba(14,165,233,0.85); border-style: solid; }
        .scan-corner-tr { top: 0; right: 0; border-width: 3px 3px 0 0; border-radius: 0 4px 0 0; }
        .scan-corner-bl { bottom: 0; left: 0; border-width: 0 0 3px 3px; border-radius: 0 0 0 4px; }

        /* Ligne de scan animée */
        .scan-line {
            position: absolute; left: 10px; right: 10px; height: 2px;
            background: linear-gradient(90deg,transparent,rgba(14,165,233,0.9),transparent);
            animation: scanMove 2.5s ease-in-out infinite;
            top: 0;
        }
        @keyframes scanMove {
            0%   { top: 10%; }
            50%  { top: 85%; }
            100% { top: 10%; }
        }

        /* États du modal */
        .face-status {
            text-align: center; padding: 10px 0 4px;
            font-size: 14px; font-weight: 500; min-height: 28px;
        }
        .face-status.info    { color: #0284c7; }
        .face-status.success { color: #16a34a; }
        .face-status.error   { color: #dc2626; }
        .face-status.loading { color: #6b7280; }

        /* Cercle de progression */
        .face-progress {
            display: flex; justify-content: center; margin: 8px 0 12px; gap: 6px;
        }
        .face-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--gray-200); transition: all 0.4s;
        }
        .face-dot.active { background: #0ea5e9; transform: scale(1.3); }
        .face-dot.done   { background: #16a34a; }

        /* Bouton capturer */
        #btnCapture {
            width: 100%; padding: 13px 20px;
            background: linear-gradient(135deg,#0ea5e9,#0284c7);
            color: white; border: none; border-radius: var(--radius-md);
            font-size: 15px; font-weight: 600; font-family: 'DM Sans',sans-serif;
            cursor: pointer; transition: all 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 3px 12px rgba(14,165,233,.30);
        }
        #btnCapture:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(14,165,233,.45); }
        #btnCapture:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        #btnCapture .btn-spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: white; border-radius: 50%;
            animation: spin 0.7s linear infinite; display: none;
        }

        canvas#snapCanvas { display: none; }

        @media (max-width: 520px) {
            .login-card { padding: 32px 24px; }
        }
        @media (max-width: 480px) {
            body { padding: 20px 16px; }
            .login-card { padding: 28px 20px; }
        }
    </style>
</head>
<body>

<!-- ══ FORMULAIRE DE CONNEXION ══════════════════════════════════════════════ -->
<div class="login-container">
    <div class="login-card" data-aos="fade-up" data-aos-duration="600">
        <a href="../home/index.php" class="logo">
            <div class="logo-icon"><i class="bi bi-plus-square-fill"></i></div>
            <div class="logo-text">Med<span>Chain</span></div>
        </a>

        <div class="login-header">
            <h2>Connexion</h2>
            <p>Accédez à votre espace santé sécurisé</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?php echo $error; ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <div>Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.</div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" class="login-form">
            <div class="form-group">
                <label for="email"><i class="bi bi-envelope-fill"></i> Adresse email</label>
                <div class="input-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?php echo htmlspecialchars($email); ?>"
                           placeholder="exemple@email.com"
                           autocomplete="email" required>
                </div>
            </div>

            <div class="form-group">
                <label for="mot_de_passe"><i class="bi bi-lock-fill"></i> Mot de passe</label>
                <div class="input-wrapper">
                    <i class="bi bi-key input-icon"></i>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control"
                           placeholder="Votre mot de passe"
                           autocomplete="current-password" required>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
                <div class="forgot-password">
                    <a href="forgot-password.php"><i class="bi bi-question-circle"></i> Mot de passe oublié ?</a>
                </div>
            </div>

            <button type="submit" class="btn-login" id="submitBtn">
                <span class="spinner" id="spinner"></span>
                <i class="bi bi-box-arrow-in-right"></i>
                <span id="btnText">Se connecter</span>
            </button>
        </form>

        <!-- Séparateur -->
        <div class="divider"><span>Ou continuer avec</span></div>

        <div class="social-buttons">
            <!-- Google -->
            <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" class="btn-social btn-google">
                <i class="bi bi-google"></i> Continuer avec Google
            </a>
            <!-- Facebook -->
            <a href="<?php echo htmlspecialchars($facebookAuthUrl); ?>" class="btn-social btn-facebook">
                <i class="bi bi-facebook"></i> Continuer avec Facebook
            </a>
            <!-- ══ FACE ID ══ -->
            <button type="button" class="btn-faceid" id="btnOpenFaceId" onclick="openFaceModal()">
                <div class="face-icon-wrap">
                    <i class="bi bi-person-bounding-box"></i>
                    <div class="face-scan-ring"></div>
                </div>
                Se connecter avec Face ID
            </button>
        </div>

        <div class="divider"><span>Nouveau sur MedChain ?</span></div>

        <div class="register-link">
            <button type="button" onclick="openRoleModal()" class="btn-register" style="border:none;cursor:pointer;width:100%;">
                <i class="bi bi-person-plus-fill"></i>
                Créer un compte
            </button>
        </div>

        <div class="back-home">
            <a href="../home/index.php"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
        </div>
    </div>
</div>

<!-- ══ MODAL FACE ID ═════════════════════════════════════════════════════════ -->
<div id="faceModal">
    <div id="faceModalBackdrop" onclick="closeFaceModal()"></div>
    <div id="faceModalCard">

        <!-- Header -->
        <div class="face-modal-header">
            <button class="face-modal-close" onclick="closeFaceModal()">✕</button>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
                <div style="width:40px;height:40px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-person-bounding-box" style="color:white;font-size:20px;"></i>
                </div>
                <h3 style="margin:0;">Connexion Face ID</h3>
            </div>
            <p>Placez votre visage dans le cadre et appuyez sur "Vérifier"</p>
        </div>

        <!-- Body -->
        <div class="face-modal-body">

            <!-- Email (pré-rempli si déjà saisi) -->
            <div style="margin-bottom:14px;">
                <label style="font-size:13px;font-weight:600;color:var(--navy);display:block;margin-bottom:6px;">
                    <i class="bi bi-envelope-fill" style="color:#0ea5e9;margin-right:5px;"></i>
                    Email du compte
                </label>
                <input type="email" id="faceEmail" placeholder="votre@email.com"
                       style="width:100%;padding:11px 14px;border:2px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:'DM Sans',sans-serif;transition:border-color 0.3s;"
                       onfocus="this.style.borderColor='#0ea5e9'"
                       onblur="this.style.borderColor='var(--gray-200)'">
            </div>

            <!-- Webcam -->
            <div class="webcam-wrapper">
                <video id="faceVideo" autoplay playsinline></video>
                <div class="scan-overlay">
                    <div class="scan-frame">
                        <div class="scan-corner-tr"></div>
                        <div class="scan-corner-bl"></div>
                        <div class="scan-line"></div>
                    </div>
                </div>
            </div>
            <canvas id="snapCanvas" width="640" height="480"></canvas>

            <!-- Indicateurs de progression -->
            <div class="face-progress">
                <div class="face-dot active" id="dot1"></div>
                <div class="face-dot" id="dot2"></div>
                <div class="face-dot" id="dot3"></div>
            </div>

            <!-- Statut -->
            <div class="face-status info" id="faceStatus">
                <i class="bi bi-camera-video-fill"></i>
                Caméra en cours d'initialisation…
            </div>

            <!-- Bouton capturer & vérifier -->
            <button id="btnCapture" onclick="capturAndVerify()" disabled>
                <span class="btn-spinner" id="captureSpinner"></span>
                <i class="bi bi-person-check-fill" id="captureIcon"></i>
                <span id="captureText">Vérifier mon visage</span>
            </button>

            <!-- Note de confidentialité -->
            <p style="text-align:center;font-size:11px;color:var(--gray-500);margin-top:12px;line-height:1.5;">
                <i class="bi bi-shield-lock-fill" style="color:#0ea5e9;"></i>
                Votre image est analysée en temps réel et <strong>non stockée</strong> dans cette session.
            </p>
        </div>
    </div>
</div>

<!-- ══ MODAL RÔLE (inchangé) ════════════════════════════════════════════════ -->
<div id="roleModal" style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;">
    <div onclick="closeRoleModal()" style="position:absolute;inset:0;background:rgba(14,40,60,0.55);backdrop-filter:blur(6px);"></div>
    <div id="roleModalCard" style="position:relative;z-index:2;width:100%;max-width:460px;margin:20px;background:#fff;border-radius:24px;box-shadow:0 24px 80px rgba(0,0,0,0.18);overflow:hidden;transform:scale(0.9);opacity:0;transition:all 0.3s cubic-bezier(.34,1.56,.64,1);">
        <div style="background:linear-gradient(135deg,#1D9E75,#0F6E56);padding:28px 32px 24px;position:relative;">
            <button onclick="closeRoleModal()" style="position:absolute;top:16px;right:16px;background:rgba(255,255,255,0.2);border:none;color:white;width:32px;height:32px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">✕</button>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                <div style="width:42px;height:42px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="bi bi-person-plus-fill" style="color:white;font-size:20px;"></i></div>
                <span style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:white;">Créer un compte</span>
            </div>
            <p style="color:rgba(255,255,255,0.8);font-size:13px;margin:0;">Choisissez votre type de compte pour continuer</p>
        </div>
        <div id="step1" style="padding:28px 32px;">
            <p style="font-size:12px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Quel est votre rôle ?</p>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <button onclick="selectRole('admin')" style="display:flex;align-items:center;gap:16px;padding:18px 20px;border:2px solid #E5E7EB;border-radius:16px;background:white;cursor:pointer;transition:all 0.25s;text-align:left;width:100%;" onmouseover="this.style.borderColor='#6366f1';this.style.background='#f5f3ff';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#E5E7EB';this.style.background='white';this.style.transform='none'">
                    <div style="width:48px;height:48px;border-radius:14px;background:rgba(99,102,241,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-shield-lock-fill" style="font-size:22px;color:#6366f1;"></i></div>
                    <div style="flex:1;"><div style="font-family:'Syne',sans-serif;font-weight:700;font-size:15px;color:#1E3A52;margin-bottom:3px;">Administrateur</div><div style="font-size:12px;color:#6B7280;">Gestion complète de la plateforme</div></div>
                    <i class="bi bi-chevron-right" style="color:#9CA3AF;font-size:14px;"></i>
                </button>
                <button onclick="goToStep2()" style="display:flex;align-items:center;gap:16px;padding:18px 20px;border:2px solid #E5E7EB;border-radius:16px;background:white;cursor:pointer;transition:all 0.25s;text-align:left;width:100%;" onmouseover="this.style.borderColor='#1D9E75';this.style.background='#f0faf6';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#E5E7EB';this.style.background='white';this.style.transform='none'">
                    <div style="width:48px;height:48px;border-radius:14px;background:#E8F7F2;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-people-fill" style="font-size:22px;color:#1D9E75;"></i></div>
                    <div style="flex:1;"><div style="font-family:'Syne',sans-serif;font-weight:700;font-size:15px;color:#1E3A52;margin-bottom:3px;">Utilisateur</div><div style="font-size:12px;color:#6B7280;">Patient ou médecin — accès aux services médicaux</div></div>
                    <i class="bi bi-chevron-right" style="color:#9CA3AF;font-size:14px;"></i>
                </button>
            </div>
        </div>
        <div id="step2" style="display:none;padding:28px 32px;">
            <button onclick="backToStep1()" style="display:flex;align-items:center;gap:6px;background:none;border:none;color:#1D9E75;font-size:13px;font-weight:600;cursor:pointer;margin-bottom:20px;padding:0;font-family:'DM Sans',sans-serif;"><i class="bi bi-arrow-left"></i> Retour</button>
            <p style="font-size:12px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Vous êtes… ?</p>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <button onclick="selectRole('patient')" style="display:flex;align-items:center;gap:16px;padding:18px 20px;border:2px solid #E5E7EB;border-radius:16px;background:white;cursor:pointer;transition:all 0.25s;text-align:left;width:100%;" onmouseover="this.style.borderColor='#1D9E75';this.style.background='#f0faf6';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#E5E7EB';this.style.background='white';this.style.transform='none'">
                    <div style="width:48px;height:48px;border-radius:14px;background:#E8F7F2;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-person-heart" style="font-size:22px;color:#1D9E75;"></i></div>
                    <div style="flex:1;"><div style="font-family:'Syne',sans-serif;font-weight:700;font-size:15px;color:#1E3A52;margin-bottom:3px;">Patient</div><div style="font-size:12px;color:#6B7280;">Accédez à vos dossiers médicaux &amp; consultations</div></div>
                    <i class="bi bi-chevron-right" style="color:#9CA3AF;font-size:14px;"></i>
                </button>
                <button onclick="selectRole('medecin')" style="display:flex;align-items:center;gap:16px;padding:18px 20px;border:2px solid #E5E7EB;border-radius:16px;background:white;cursor:pointer;transition:all 0.25s;text-align:left;width:100%;" onmouseover="this.style.borderColor='#0891b2';this.style.background='#ecfeff';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#E5E7EB';this.style.background='white';this.style.transform='none'">
                    <div style="width:48px;height:48px;border-radius:14px;background:#ecfeff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-hospital-fill" style="font-size:22px;color:#0891b2;"></i></div>
                    <div style="flex:1;"><div style="font-family:'Syne',sans-serif;font-weight:700;font-size:15px;color:#1E3A52;margin-bottom:3px;">Médecin</div><div style="font-size:12px;color:#6B7280;">Gérez vos patients &amp; ordonnances en ligne</div></div>
                    <i class="bi bi-chevron-right" style="color:#9CA3AF;font-size:14px;"></i>
                </button>
            </div>
        </div>
        <div style="padding:0 32px 24px;text-align:center;">
            <span style="font-size:13px;color:#6B7280;">Déjà un compte ? </span>
            <a href="#" onclick="closeRoleModal()" style="font-size:13px;color:#1D9E75;font-weight:600;text-decoration:none;">Se connecter</a>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ once: true, offset: 20 });

// ── Toggle password ──────────────────────────────────────────────────────────
const togglePassword = document.getElementById('togglePassword');
const passwordInput  = document.getElementById('mot_de_passe');
if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        const icon = this.querySelector('i');
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });
}

// ── Validation formulaire principal ─────────────────────────────────────────
const loginForm = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const submitBtn  = document.getElementById('submitBtn');
const spinner    = document.getElementById('spinner');
const btnText    = document.getElementById('btnText');

if (loginForm) {
    emailInput.addEventListener('input', function() {
        this.classList.remove('error');
        const err = document.querySelector('.alert-error');
        if (err && this.value.trim()) err.remove();
    });
    passwordInput.addEventListener('input', function() {
        this.classList.remove('error');
        const err = document.querySelector('.alert-error');
        if (err && this.value.trim()) err.remove();
    });

    loginForm.addEventListener('submit', function(e) {
        let isValid = true;
        let errorMessage = '';
        const email    = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email) {
            errorMessage = "L'adresse email est obligatoire";
            emailInput.classList.add('error'); isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errorMessage = "Format d'email invalide";
            emailInput.classList.add('error'); isValid = false;
        } else { emailInput.classList.remove('error'); }

        if (!password) {
            if (!errorMessage) errorMessage = 'Le mot de passe est obligatoire';
            passwordInput.classList.add('error'); isValid = false;
        } else if (password.length < 6) {
            if (!errorMessage) errorMessage = 'Le mot de passe doit contenir au moins 6 caractères';
            passwordInput.classList.add('error'); isValid = false;
        } else { passwordInput.classList.remove('error'); }

        if (!isValid) {
            e.preventDefault();
            let errorDiv = document.querySelector('.alert-error');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-error';
                loginForm.insertBefore(errorDiv, loginForm.firstChild);
            }
            errorDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i><div>${errorMessage}</div>`;
        } else {
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Connexion en cours…';
        }
    });
}

// Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.opacity = '0'; a.style.transform = 'translateY(-10px)';
        a.style.transition = 'all 0.3s';
        setTimeout(() => a.remove(), 300);
    });
}, 5000);

// ════════════════════════════════════════════════════════════════════════════
//  FACE ID — Logique principale
// ════════════════════════════════════════════════════════════════════════════

let faceStream = null;

function openFaceModal() {
    const modal = document.getElementById('faceModal');
    const card  = document.getElementById('faceModalCard');
    modal.style.display = 'flex';
    setTimeout(() => { card.style.transform = 'scale(1)'; card.style.opacity = '1'; }, 10);
    document.body.style.overflow = 'hidden';

    // Pré-remplir email si déjà saisi
    const emailVal = document.getElementById('email').value.trim();
    if (emailVal) document.getElementById('faceEmail').value = emailVal;

    // Démarrer la webcam
    startCamera();
}

function closeFaceModal() {
    const modal = document.getElementById('faceModal');
    const card  = document.getElementById('faceModalCard');
    card.style.transform = 'scale(0.88)';
    card.style.opacity   = '0';
    setTimeout(() => { modal.style.display = 'none'; }, 320);
    document.body.style.overflow = '';
    stopCamera();
    resetFaceUI();
}

function startCamera() {
    const video = document.getElementById('faceVideo');
    setStatus('loading', '<i class="bi bi-hourglass-split"></i> Accès à la caméra…');

    navigator.mediaDevices.getUserMedia({ video: { width:640, height:480, facingMode:'user' }, audio: false })
        .then(stream => {
            faceStream = stream;
            video.srcObject = stream;
            video.onloadedmetadata = () => {
                setStatus('info', '<i class="bi bi-camera-video-fill"></i> Positionnez votre visage dans le cadre');
                document.getElementById('btnCapture').disabled = false;
                setDot(1);
            };
        })
        .catch(err => {
            console.error('Camera error:', err);
            if (err.name === 'NotAllowedError') {
                setStatus('error', '<i class="bi bi-camera-video-off-fill"></i> Accès caméra refusé. Vérifiez les permissions du navigateur.');
            } else {
                setStatus('error', '<i class="bi bi-exclamation-triangle-fill"></i> Impossible d\'accéder à la caméra.');
            }
        });
}

function stopCamera() {
    if (faceStream) {
        faceStream.getTracks().forEach(t => t.stop());
        faceStream = null;
    }
    const video = document.getElementById('faceVideo');
    video.srcObject = null;
}

function capturAndVerify() {
    const email = document.getElementById('faceEmail').value.trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setStatus('error', '<i class="bi bi-exclamation-triangle-fill"></i> Veuillez saisir un email valide.');
        document.getElementById('faceEmail').focus();
        return;
    }

    // Capture de l'image depuis la vidéo
    const video  = document.getElementById('faceVideo');
    const canvas = document.getElementById('snapCanvas');
    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    // On annule le miroir pour envoyer l'image naturelle à l'API
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageBase64 = canvas.toDataURL('image/jpeg', 0.85);

    // UI — état chargement
    setDot(2);
    setStatus('loading', '<i class="bi bi-cpu-fill"></i> Analyse du visage en cours…');
    setBtnLoading(true);

    fetch('Face_login.php', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ image: imageBase64, email: email })
    })
    .then(r => {
        // Si PHP renvoie une erreur (404, 500...), lire le texte brut pour le log
        if (!r.ok) {
            return r.text().then(txt => {
                console.error('[FaceID] Erreur HTTP ' + r.status + ':', txt);
                throw new Error('HTTP_' + r.status);
            });
        }
        return r.json();
    })
    .then(data => {
        setBtnLoading(false);

        if (data.success) {
            setDot(3, true);
            setStatus('success',
                `<i class="bi bi-check-circle-fill"></i> Visage reconnu ! Confiance : ${Number(data.confidence || 0).toFixed(1)}%`
            );
            // Redirection après 1.2 secondes
            setTimeout(() => {
                if (data.user && data.user.role === 'admin') {
                    window.location.href = '../../backoffice/admin-dashboard.php';
                } else {
                    window.location.href = 'profile.php';
                }
            }, 1200);
        } else {
            setDot(1);

            // Cas spécifique : aucun visage enregistré → guider l'utilisateur
            if (data.no_face_registered) {
                setStatus('error',
                    `<i class="bi bi-person-x-fill"></i> Aucun visage enregistré pour cet email.<br>
                     <span style="font-size:12px;margin-top:6px;display:inline-block;">
                       Connectez-vous d'abord avec votre mot de passe, puis activez Face ID depuis votre profil.
                       <br><a href="#" onclick="closeFaceModal()" style="color:#0284c7;text-decoration:underline;font-weight:600;">
                         <i class="bi bi-box-arrow-in-right"></i> Utiliser mon mot de passe
                       </a>
                     </span>`
                );
            } else {
                setStatus('error', `<i class="bi bi-x-circle-fill"></i> ${escapeHtml(data.message || 'Visage non reconnu.')}`);
            }
        }
    })
    .catch(err => {
        console.error('[FaceID] Erreur:', err.message);
        setBtnLoading(false);
        setDot(1);
        if (err.message === 'HTTP_404') {
            setStatus('error', '<i class="bi bi-exclamation-triangle-fill"></i> Fichier Face_login.php introuvable (404).');
        } else if (err.message === 'HTTP_500') {
            setStatus('error', '<i class="bi bi-exclamation-triangle-fill"></i> Erreur serveur PHP. Vérifiez les logs.');
        } else {
            setStatus('error', '<i class="bi bi-wifi-off"></i> Erreur réseau. Veuillez réessayer.');
        }
    });
}

// ── Helpers UI ───────────────────────────────────────────────────────────────
function setStatus(type, html) {
    const el = document.getElementById('faceStatus');
    el.className = 'face-status ' + type;
    el.innerHTML = html;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

function setDot(active, allDone = false) {
    for (let i = 1; i <= 3; i++) {
        const dot = document.getElementById('dot' + i);
        dot.className = 'face-dot';
        if (allDone && i <= active)  dot.classList.add('done');
        else if (i === active)        dot.classList.add('active');
    }
}

function setBtnLoading(loading) {
    const btn     = document.getElementById('btnCapture');
    const sp      = document.getElementById('captureSpinner');
    const icon    = document.getElementById('captureIcon');
    const txt     = document.getElementById('captureText');
    btn.disabled  = loading;
    sp.style.display   = loading ? 'inline-block' : 'none';
    icon.style.display = loading ? 'none' : 'inline';
    txt.textContent    = loading ? 'Vérification…' : 'Vérifier mon visage';
}

function resetFaceUI() {
    setStatus('info', '<i class="bi bi-camera-video-fill"></i> Caméra en cours d\'initialisation…');
    setDot(1);
    setBtnLoading(false);
    document.getElementById('btnCapture').disabled = true;
}
// Fermer avec Échap
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeFaceModal();
        closeRoleModal();
    }
});

// ── Modal Rôle ───────────────────────────────────────────────────────────────
function openRoleModal() {
    const modal = document.getElementById('roleModal');
    const card  = document.getElementById('roleModalCard');
    modal.style.display = 'flex';
    setTimeout(() => { card.style.transform = 'scale(1)'; card.style.opacity = '1'; }, 10);
    document.body.style.overflow = 'hidden';
    document.getElementById('step1').style.display = 'block';
    document.getElementById('step2').style.display = 'none';
}
function closeRoleModal() {
    const modal = document.getElementById('roleModal');
    const card  = document.getElementById('roleModalCard');
    card.style.transform = 'scale(0.9)'; card.style.opacity = '0';
    setTimeout(() => { modal.style.display = 'none'; }, 300);
    document.body.style.overflow = '';
}
function goToStep2()   { document.getElementById('step1').style.display='none'; document.getElementById('step2').style.display='block'; }
function backToStep1() { document.getElementById('step2').style.display='none'; document.getElementById('step1').style.display='block'; }
function selectRole(role) { window.location.href = 'register.php?role=' + role; }
</script>

</body>
</html>
