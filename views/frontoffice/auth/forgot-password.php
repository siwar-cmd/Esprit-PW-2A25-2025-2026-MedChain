<?php
/**
 * forgot-password.php
 * Chemin : projet/views/frontoffice/auth/forgot-password.php
 */
session_start();
require_once __DIR__ . '/../../../controllers/PasswordController.php';

$passwordController = new PasswordController();
$error_message   = null;
$success_message = null;
$reset_link_dev  = null; // affiché uniquement en mode développement

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error_message = "Veuillez saisir votre adresse email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format d'email invalide.";
    } else {
        $result = $passwordController->sendResetLink($email);

        if ($result['success']) {
            $success_message = $result['message'];
            // Mode développement : afficher le lien directement
            if (isset($result['reset_link'])) {
                $reset_link_dev = $result['reset_link'];
            }
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - MedChain</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />

    <style>
        /* ══════════════════════════════════════
           RESET & ROOT VARIABLES
        ══════════════════════════════════════ */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green:        #1D9E75;
            --green-dark:   #0F6E56;
            --green-deep:   #094D3C;
            --green-light:  #E8F7F2;
            --green-pale:   #F2FBF7;
            --navy:         #1E3A52;
            --navy-light:   #2C4964;
            --gray-700:     #374151;
            --gray-500:     #6B7280;
            --gray-200:     #E5E7EB;
            --gray-100:     #F9FAFB;
            --white:        #ffffff;
            --shadow-sm:    0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md:    0 4px 16px rgba(0,0,0,.08);
            --shadow-lg:    0 12px 40px rgba(0,0,0,.10);
            --shadow-green: 0 8px 30px rgba(29,158,117,.22);
            --radius-sm:    8px;
            --radius-md:    12px;
            --radius-lg:    20px;
            --radius-xl:    28px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
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
            background: radial-gradient(circle, rgba(29,158,117,.10) 0%, transparent 70%);
            pointer-events: none; z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -80px; left: -80px;
            width: 380px; height: 380px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(29,158,117,.07) 0%, transparent 70%);
            pointer-events: none; z-index: 0;
        }

        /* ── Container ─────────────────────────── */
        .forgot-container {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .forgot-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 48px 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(29,158,117,.15);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .forgot-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-green);
        }

        /* ── Logo ───────────────────────────────── */
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
            text-decoration: none;
            transition: transform 0.3s;
        }
        .logo:hover { transform: scale(1.02); }

        .logo-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            box-shadow: var(--shadow-green);
        }
        .logo-icon i { font-size: 24px; color: white; }

        .logo-text {
            font-family: 'Syne', sans-serif;
            font-size: 26px; font-weight: 800;
            color: var(--navy); letter-spacing: -0.5px;
        }
        .logo-text span { color: var(--green); }

        /* ── Header ─────────────────────────────── */
        .forgot-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .forgot-header .icon-circle {
            width: 64px; height: 64px;
            background: var(--green-light);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        }

        .forgot-header .icon-circle i {
            font-size: 28px;
            color: var(--green);
        }

        .forgot-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 26px; font-weight: 700;
            color: var(--navy); margin-bottom: 8px;
        }

        .forgot-header p {
            color: var(--gray-500);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ── Alerts ─────────────────────────────── */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: #FEF2F2;
            border-left: 4px solid #EF4444;
            color: #B91C1C;
        }

        .alert-success {
            background: #F0FDF4;
            border-left: 4px solid #22C55E;
            color: #166534;
        }

        .alert i { font-size: 18px; margin-top: 2px; }
        .alert div { flex: 1; line-height: 1.5; }

        /* ── Info box ───────────────────────────── */
        .info-box {
            background: var(--green-pale);
            border: 1px solid rgba(29,158,117,.2);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .info-box i { color: var(--green); font-size: 18px; margin-top: 1px; flex-shrink: 0; }
        .info-box p { color: var(--gray-700); font-size: 13px; margin: 0; line-height: 1.5; }

        /* ── Dev link box ───────────────────────── */
        .dev-link-box {
            background: #fffbeb;
            border: 1px dashed #f59e0b;
            border-radius: var(--radius-md);
            padding: 14px 18px;
            margin-bottom: 20px;
        }

        .dev-link-box p {
            font-size: 12px;
            color: #92400e;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .dev-link-box a {
            font-size: 12px;
            color: #b45309;
            word-break: break-all;
            text-decoration: underline;
        }

        /* ── Form ───────────────────────────────── */
        .form-group { margin-bottom: 20px; }

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

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: var(--gray-500);
            font-size: 16px;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 13px 14px 13px 44px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            background: var(--white);
            transition: all 0.3s;
            color: var(--gray-700);
        }

        .form-control::placeholder { color: var(--gray-500); }

        .form-control:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(29,158,117,.15);
        }

        .form-control.error {
            border-color: #EF4444;
            background: #FEF2F2;
        }

        .form-control.valid {
            border-color: #22C55E;
            background: #F0FDF4;
        }

        .input-wrapper:focus-within .input-icon { color: var(--green); }

        .validation-message {
            font-size: 11px;
            margin-top: 5px;
            color: #EF4444;
            display: none;
        }

        .validation-message.show { display: block; }

        /* ── Submit button ──────────────────────── */
        .btn-submit {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 3px 12px rgba(29,158,117,.30);
            margin-top: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29,158,117,.40);
            background: linear-gradient(135deg, var(--green-dark), var(--green-deep));
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Success state ──────────────────────── */
        .success-state {
            text-align: center;
            padding: 10px 0 20px;
        }

        .success-state .success-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #22C55E, #16A34A);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }

        .success-state .success-icon i {
            font-size: 32px;
            color: white;
        }

        .success-state h3 {
            font-family: 'Syne', sans-serif;
            font-size: 20px; font-weight: 700;
            color: var(--navy);
            margin-bottom: 10px;
        }

        .success-state p {
            color: var(--gray-500);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 6px;
        }

        /* ── Steps ──────────────────────────────── */
        .steps {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 20px 0;
            text-align: left;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: var(--green-pale);
            border-radius: var(--radius-md);
            border: 1px solid rgba(29,158,117,.15);
        }

        .step-num {
            width: 26px; height: 26px;
            background: var(--green);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
            flex-shrink: 0;
        }

        .step span {
            font-size: 13px;
            color: var(--gray-700);
        }

        /* ── Divider & links ────────────────────── */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: var(--gray-500);
            font-size: 12px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--gray-200);
        }

        .divider::before { margin-right: 16px; }
        .divider::after  { margin-left: 16px; }

        .back-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        .back-links a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .back-links a.btn-login-link {
            color: var(--green);
        }

        .back-links a.btn-login-link:hover {
            color: var(--green-dark);
            text-decoration: underline;
        }

        .back-links a.btn-home-link {
            color: var(--gray-500);
            font-size: 13px;
        }

        .back-links a.btn-home-link:hover {
            color: var(--green);
        }

        /* ── Responsive ─────────────────────────── */
        @media (max-width: 480px) {
            .forgot-card { padding: 32px 24px; }
            .forgot-header h2 { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="forgot-container">
    <div class="forgot-card" data-aos="fade-up" data-aos-duration="600">

        <!-- Logo -->
        <a href="../home/index.php" class="logo">
            <div class="logo-icon">
                <i class="bi bi-plus-square-fill"></i>
            </div>
            <div class="logo-text">Med<span>Chain</span></div>
        </a>

        <?php if (!$success_message): ?>
        <!-- ═══ ÉTAT INITIAL : Formulaire ═══ -->

            <div class="forgot-header">
                <div class="icon-circle">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h2>Mot de passe oublié ?</h2>
                <p>Saisissez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= htmlspecialchars($error_message) ?></div>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <i class="bi bi-info-circle-fill"></i>
                <p>Le lien de réinitialisation sera valable pendant <strong>1 heure</strong>. Vérifiez aussi vos spams si vous ne recevez pas l'email.</p>
            </div>

            <form method="POST" action="" id="forgotForm" novalidate>
                <div class="form-group">
                    <label for="email">
                        <i class="bi bi-envelope-fill"></i> Adresse email
                    </label>
                    <div class="input-wrapper">
                        <i class="bi bi-envelope input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            placeholder="exemple@email.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                    <div class="validation-message" id="email-error">Veuillez saisir un email valide.</div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="spinner" id="spinner"></span>
                    <i class="bi bi-send-fill"></i>
                    <span id="btnText">Envoyer le lien</span>
                </button>
            </form>

        <?php else: ?>
        <!-- ═══ ÉTAT SUCCÈS ═══ -->

            <div class="success-state" data-aos="zoom-in" data-aos-duration="500">
                <div class="success-icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h3>Email envoyé !</h3>
                <p>Si l'adresse <strong><?= htmlspecialchars($_POST['email'] ?? '') ?></strong> est associée à un compte MedChain, vous recevrez un lien de réinitialisation.</p>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-num">1</div>
                    <span>Ouvrez votre boîte email</span>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <span>Cliquez sur le lien de réinitialisation</span>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <span>Créez votre nouveau mot de passe</span>
                </div>
            </div>

            <?php if ($reset_link_dev): ?>
                <!-- Mode développement uniquement — à retirer en production -->
                <div class="dev-link-box">
                    <p>⚠️ Mode développement — lien de réinitialisation :</p>
                    <a href="<?= htmlspecialchars($reset_link_dev) ?>"><?= htmlspecialchars($reset_link_dev) ?></a>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <div class="divider"><span>Revenir à</span></div>

        <div class="back-links">
            <a href="login.php" class="btn-login-link">
                <i class="bi bi-box-arrow-in-right"></i>
                Se connecter
            </a>
            <a href="../home/index.php" class="btn-home-link">
                <i class="bi bi-arrow-left"></i>
                Retour à l'accueil
            </a>
        </div>

    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 20 });

    const form      = document.getElementById('forgotForm');
    const emailEl   = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');
    const spinner   = document.getElementById('spinner');
    const btnText   = document.getElementById('btnText');
    const emailErr  = document.getElementById('email-error');

    if (emailEl) {
        emailEl.addEventListener('input', () => {
            emailEl.classList.remove('error', 'valid');
            emailErr && emailErr.classList.remove('show');
        });

        emailEl.addEventListener('blur', () => {
            validateEmail();
        });
    }

    function validateEmail() {
        const val = emailEl.value.trim();
        if (!val) {
            emailEl.classList.add('error');
            emailErr && emailErr.classList.add('show');
            emailErr && (emailErr.textContent = 'Veuillez saisir votre adresse email.');
            return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            emailEl.classList.add('error');
            emailErr && emailErr.classList.add('show');
            emailErr && (emailErr.textContent = 'Format d\'email invalide.');
            return false;
        }
        emailEl.classList.add('valid');
        emailErr && emailErr.classList.remove('show');
        return true;
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            if (!validateEmail()) {
                e.preventDefault();
                emailEl.focus();
                return;
            }
            // Spinner
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Envoi en cours...';
        });
    }
</script>
</body>
</html>