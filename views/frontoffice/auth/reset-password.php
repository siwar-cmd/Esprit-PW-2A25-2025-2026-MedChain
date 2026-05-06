<?php
/**
 * reset-password.php
 * Chemin : projet/views/frontoffice/auth/reset-password.php
 */
session_start();
require_once __DIR__ . '/../../../controllers/PasswordController.php';

$passwordController = new PasswordController();
$error_message   = null;
$success_message = null;
$token           = $_GET['token'] ?? '';

if (empty($token) && !isset($_POST['new_password'])) {
    $error_message = "Lien de réinitialisation invalide ou expiré.";
} elseif (!empty($token)) {
    $tokenCheck = $passwordController->validateToken($token);
    if (!$tokenCheck['success']) {
        $error_message = $tokenCheck['message'];
        $token = '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword     = $_POST['new_password']     ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $postToken       = $_POST['token']            ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error_message = "Veuillez remplir tous les champs.";
    } elseif ($newPassword !== $confirmPassword) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($newPassword) < 8) {
        $error_message = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $result = $passwordController->resetPassword($postToken, $newPassword);
        if ($result['success']) {
            $success_message = $result['message'];
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
    <title>Réinitialiser mot de passe - MedChain</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green:        #1D9E75;
            --green-dark:   #0F6E56;
            --green-deep:   #094D3C;
            --green-light:  #E8F7F2;
            --green-pale:   #F2FBF7;
            --navy:         #1E3A52;
            --gray-700:     #374151;
            --gray-500:     #6B7280;
            --gray-200:     #E5E7EB;
            --white:        #ffffff;
            --shadow-lg:    0 12px 40px rgba(0,0,0,.10);
            --shadow-green: 0 8px 30px rgba(29,158,117,.22);
            --radius-md:    12px;
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
            position: fixed; top: -120px; right: -120px;
            width: 500px; height: 500px; border-radius: 50%;
            background: radial-gradient(circle, rgba(29,158,117,.10) 0%, transparent 70%);
            pointer-events: none; z-index: 0;
        }

        body::after {
            content: '';
            position: fixed; bottom: -80px; left: -80px;
            width: 380px; height: 380px; border-radius: 50%;
            background: radial-gradient(circle, rgba(29,158,117,.07) 0%, transparent 70%);
            pointer-events: none; z-index: 0;
        }

        .reset-container {
            max-width: 480px; width: 100%;
            margin: 0 auto; position: relative; z-index: 2;
        }

        .reset-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 48px 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(29,158,117,.15);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .reset-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-green);
        }

        /* Logo */
        .logo {
            display: flex; align-items: center; justify-content: center;
            gap: 12px; margin-bottom: 32px;
            text-decoration: none; transition: transform 0.3s;
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

        /* Header */
        .page-header { text-align: center; margin-bottom: 28px; }

        .icon-circle {
            width: 64px; height: 64px;
            background: var(--green-light);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        }
        .icon-circle i { font-size: 28px; color: var(--green); }
        .icon-circle.red   { background: #FEE2E2; }
        .icon-circle.red i { color: #EF4444; }
        .icon-circle.green-solid {
            background: linear-gradient(135deg, #22C55E, #16A34A);
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .icon-circle.green-solid i { color: white; }

        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }

        .page-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 26px; font-weight: 700;
            color: var(--navy); margin-bottom: 8px;
        }
        .page-header p { color: var(--gray-500); font-size: 14px; line-height: 1.6; }

        /* Alerts */
        .alert {
            padding: 16px 20px; border-radius: var(--radius-md);
            margin-bottom: 24px;
            display: flex; align-items: flex-start; gap: 12px;
            animation: slideIn 0.4s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .alert-error   { background: #FEF2F2; border-left: 4px solid #EF4444; color: #B91C1C; }
        .alert-success { background: #F0FDF4; border-left: 4px solid #22C55E; color: #166534; }
        .alert i { font-size: 18px; margin-top: 2px; flex-shrink: 0; }
        .alert div { flex: 1; line-height: 1.5; }

        /* Info box */
        .info-box {
            background: var(--green-pale);
            border: 1px solid rgba(29,158,117,.2);
            border-radius: var(--radius-md);
            padding: 14px 18px; margin-bottom: 24px;
            display: flex; align-items: flex-start; gap: 12px;
        }
        .info-box i { color: var(--green); font-size: 18px; margin-top: 2px; flex-shrink: 0; }
        .info-box p { color: var(--gray-700); font-size: 13px; margin: 0; line-height: 1.5; }

        /* Form */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; margin-bottom: 8px;
            color: var(--navy); font-weight: 600; font-size: 13px;
        }
        .form-group label i { color: var(--green); margin-right: 6px; }

        .input-wrapper { position: relative; display: flex; align-items: center; }

        .input-icon {
            position: absolute; left: 14px;
            color: var(--gray-500); font-size: 16px; transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 13px 48px 13px 44px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px; font-family: 'DM Sans', sans-serif;
            background: var(--white); color: var(--gray-700);
            transition: all 0.3s;
        }
        .form-control::placeholder { color: var(--gray-500); }
        .form-control:focus {
            outline: none; border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(29,158,117,.15);
        }
        .form-control.error  { border-color: #EF4444; background: #FEF2F2; }
        .form-control.valid  { border-color: #22C55E; background: #F0FDF4; }
        .input-wrapper:focus-within .input-icon { color: var(--green); }

        .password-toggle {
            position: absolute; right: 14px;
            background: none; border: none;
            color: var(--gray-500); cursor: pointer;
            font-size: 16px; transition: color 0.3s; padding: 4px;
        }
        .password-toggle:hover { color: var(--green); }

        /* Strength */
        .strength-wrap { margin-top: 8px; }
        .strength-bar {
            height: 4px; background: var(--gray-200);
            border-radius: 2px; overflow: hidden; margin-bottom: 4px;
        }
        .strength-fill {
            height: 100%; width: 0%;
            transition: width 0.3s, background 0.3s; border-radius: 2px;
        }
        .strength-fill.weak   { width: 25%; background: #EF4444; }
        .strength-fill.fair   { width: 50%; background: #F59E0B; }
        .strength-fill.good   { width: 75%; background: #3B82F6; }
        .strength-fill.strong { width: 100%; background: #22C55E; }
        .strength-text { font-size: 11px; color: var(--gray-500); }

        /* Requirements */
        .requirements {
            background: var(--green-pale);
            border: 1px solid rgba(29,158,117,.15);
            border-radius: var(--radius-md);
            padding: 14px 16px; margin-bottom: 20px;
        }
        .requirements > p {
            font-size: 12px; font-weight: 600;
            color: var(--navy); margin-bottom: 10px;
        }
        .requirement {
            display: flex; align-items: center; gap: 8px;
            font-size: 12px; color: var(--gray-500);
            padding: 3px 0; transition: color 0.3s;
        }
        .requirement i { font-size: 14px; color: var(--gray-200); transition: color 0.3s; }
        .requirement.met { color: var(--green); }
        .requirement.met i { color: var(--green); }

        /* Submit */
        .btn-submit {
            width: 100%; padding: 14px 24px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white; border: none; border-radius: var(--radius-md);
            font-size: 15px; font-weight: 600; font-family: 'DM Sans', sans-serif;
            cursor: pointer; transition: all 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 3px 12px rgba(29,158,117,.30); margin-top: 8px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29,158,117,.40);
            background: linear-gradient(135deg, var(--green-dark), var(--green-deep));
        }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        .spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white; border-radius: 50%;
            animation: spin 0.8s linear infinite; display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Divider & links */
        .divider {
            display: flex; align-items: center; text-align: center;
            margin: 24px 0; color: var(--gray-500); font-size: 12px;
        }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid var(--gray-200); }
        .divider::before { margin-right: 16px; }
        .divider::after  { margin-left: 16px; }

        .back-links {
            display: flex; flex-direction: column;
            gap: 10px; align-items: center;
        }
        .back-links a {
            display: inline-flex; align-items: center; gap: 6px;
            text-decoration: none; font-size: 14px; font-weight: 500;
            transition: color 0.2s;
        }
        .link-primary { color: var(--green); }
        .link-primary:hover { color: var(--green-dark); text-decoration: underline; }
        .link-secondary { color: var(--gray-500); font-size: 13px; }
        .link-secondary:hover { color: var(--green); }

        @media (max-width: 480px) {
            .reset-card { padding: 32px 20px; }
            .page-header h2 { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="reset-container">
    <div class="reset-card" data-aos="fade-up" data-aos-duration="600">

        <a href="../home/index.php" class="logo">
            <div class="logo-icon"><i class="bi bi-plus-square-fill"></i></div>
            <div class="logo-text">Med<span>Chain</span></div>
        </a>

        <?php if ($success_message): ?>
        <!-- ═══ SUCCÈS ═══ -->
        <div class="page-header" data-aos="zoom-in">
            <div class="icon-circle green-solid" style="width:72px;height:72px;">
                <i class="bi bi-check-lg" style="font-size:32px;"></i>
            </div>
            <h2>Mot de passe réinitialisé !</h2>
            <p><?= htmlspecialchars($success_message) ?></p>
        </div>
        <div class="divider"><span>Prochaine étape</span></div>
        <div class="back-links">
            <a href="login.php" class="link-primary">
                <i class="bi bi-box-arrow-in-right"></i> Se connecter
            </a>
            <a href="../home/index.php" class="link-secondary">
                <i class="bi bi-arrow-left"></i> Retour à l'accueil
            </a>
        </div>

        <?php elseif ($error_message && empty($token)): ?>
        <!-- ═══ TOKEN INVALIDE ═══ -->
        <div class="page-header" data-aos="zoom-in">
            <div class="icon-circle red" style="width:72px;height:72px;">
                <i class="bi bi-x-lg" style="font-size:32px;color:#EF4444;"></i>
            </div>
            <h2>Lien invalide</h2>
            <p><?= htmlspecialchars($error_message) ?></p>
        </div>
        <div class="divider"><span>Que faire ?</span></div>
        <div class="back-links">
            <a href="forgot-password.php" class="link-primary">
                <i class="bi bi-arrow-repeat"></i> Demander un nouveau lien
            </a>
            <a href="login.php" class="link-secondary">
                <i class="bi bi-box-arrow-in-right"></i> Retour à la connexion
            </a>
        </div>

        <?php else: ?>
        <!-- ═══ FORMULAIRE ═══ -->
        <div class="page-header">
            <div class="icon-circle">
                <i class="bi bi-key-fill"></i>
            </div>
            <h2>Réinitialisation</h2>
            <p>Créez un nouveau mot de passe sécurisé</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= htmlspecialchars($error_message) ?></div>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <i class="bi bi-info-circle-fill"></i>
            <p>Votre nouveau mot de passe doit contenir au moins <strong>8 caractères</strong> avec des lettres et des chiffres.</p>
        </div>

        <form method="POST" action="" id="resetForm" novalidate>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label><i class="bi bi-lock-fill"></i> Nouveau mot de passe</label>
                <div class="input-wrapper">
                    <i class="bi bi-key input-icon"></i>
                    <input type="password" id="new_password" name="new_password"
                           class="form-control" placeholder="Minimum 8 caractères" required>
                    <button type="button" class="password-toggle" id="toggleNew">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
                <div class="strength-wrap">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <div class="strength-text" id="strengthText">Force du mot de passe</div>
                </div>
            </div>

            <div class="form-group">
                <label><i class="bi bi-lock-fill"></i> Confirmer le mot de passe</label>
                <div class="input-wrapper">
                    <i class="bi bi-key input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="form-control" placeholder="Confirmez votre mot de passe" required>
                    <button type="button" class="password-toggle" id="toggleConfirm">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
            </div>

            <div class="requirements">
                <p><i class="bi bi-shield-check"></i> Exigences de sécurité :</p>
                <div class="requirement" id="reqLength">
                    <i class="bi bi-circle"></i> Au moins 8 caractères
                </div>
                <div class="requirement" id="reqLetter">
                    <i class="bi bi-circle"></i> Au moins une lettre
                </div>
                <div class="requirement" id="reqNumber">
                    <i class="bi bi-circle"></i> Au moins un chiffre
                </div>
                <div class="requirement" id="reqMatch">
                    <i class="bi bi-circle"></i> Les mots de passe correspondent
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span class="spinner" id="spinner"></span>
                <i class="bi bi-arrow-repeat"></i>
                <span id="btnText">Réinitialiser</span>
            </button>
        </form>

        <div class="divider"><span>Ou</span></div>
        <div class="back-links">
            <a href="login.php" class="link-primary">
                <i class="bi bi-box-arrow-in-right"></i> Retour à la connexion
            </a>
            <a href="../home/index.php" class="link-secondary">
                <i class="bi bi-arrow-left"></i> Retour à l'accueil
            </a>
        </div>

        <?php endif; ?>

    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 20 });

    const newPwEl      = document.getElementById('new_password');
    const confirmEl    = document.getElementById('confirm_password');
    const submitBtn    = document.getElementById('submitBtn');
    const spinner      = document.getElementById('spinner');
    const btnText      = document.getElementById('btnText');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');

    // Toggle visibility
    document.getElementById('toggleNew')?.addEventListener('click', function () {
        const t = newPwEl.type === 'password' ? 'text' : 'password';
        newPwEl.type = t;
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });

    document.getElementById('toggleConfirm')?.addEventListener('click', function () {
        const t = confirmEl.type === 'password' ? 'text' : 'password';
        confirmEl.type = t;
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });

    // Strength check
    function checkStrength(pw) {
        let s = 0;
        if (pw.length >= 8)          s++;
        if (pw.length >= 12)         s++;
        if (/[a-z]/.test(pw))        s++;
        if (/[A-Z]/.test(pw))        s++;
        if (/[0-9]/.test(pw))        s++;
        if (/[^A-Za-z0-9]/.test(pw)) s++;
        if (s <= 2) return { cls: 'weak',   label: '🔴 Faible' };
        if (s <= 3) return { cls: 'fair',   label: '🟡 Moyen' };
        if (s <= 4) return { cls: 'good',   label: '🔵 Bon' };
        return              { cls: 'strong', label: '🟢 Fort' };
    }

    // Requirements live check
    function setReq(id, ok) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('met', ok);
        const icon = el.querySelector('i');
        if (icon) icon.className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
    }

    function updateRequirements() {
        const pw   = newPwEl?.value  || '';
        const conf = confirmEl?.value || '';

        setReq('reqLength', pw.length >= 8);
        setReq('reqLetter', /[a-zA-Z]/.test(pw));
        setReq('reqNumber', /[0-9]/.test(pw));
        setReq('reqMatch',  pw.length > 0 && pw === conf);

        if (strengthFill && strengthText) {
            if (!pw) {
                strengthFill.className = 'strength-fill';
                strengthFill.style.width = '0';
                strengthText.textContent = 'Force du mot de passe';
            } else {
                const s = checkStrength(pw);
                strengthFill.className = 'strength-fill ' + s.cls;
                strengthText.textContent = s.label;
            }
        }

        if (newPwEl) {
            newPwEl.classList.toggle('valid', pw.length >= 8);
            newPwEl.classList.toggle('error', pw.length > 0 && pw.length < 8);
        }
        if (confirmEl && conf.length > 0) {
            confirmEl.classList.toggle('valid', pw === conf);
            confirmEl.classList.toggle('error', pw !== conf);
        }
    }

    newPwEl?.addEventListener('input', updateRequirements);
    confirmEl?.addEventListener('input', updateRequirements);

    // Submit
    document.getElementById('resetForm')?.addEventListener('submit', function (e) {
        const pw   = newPwEl.value;
        const conf = confirmEl.value;

        if (!pw || !conf) {
            e.preventDefault();
            showFormError('Veuillez remplir tous les champs.');
            return;
        }
        if (pw.length < 8) {
            e.preventDefault();
            showFormError('Le mot de passe doit contenir au moins 8 caractères.');
            newPwEl.classList.add('error');
            return;
        }
        if (pw !== conf) {
            e.preventDefault();
            showFormError('Les mots de passe ne correspondent pas.');
            confirmEl.classList.add('error');
            return;
        }

        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';
        btnText.textContent = 'Réinitialisation...';
    });

    function showFormError(msg) {
        let el = document.querySelector('.alert-error');
        if (!el) {
            el = document.createElement('div');
            el.className = 'alert alert-error';
            const form = document.getElementById('resetForm');
            form.parentNode.insertBefore(el, form);
        }
        el.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i><div>${msg}</div>`;
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
</script>
</body>
</html>