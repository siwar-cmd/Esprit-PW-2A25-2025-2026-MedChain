<?php
session_start();
include_once '../../../controllers/AuthController.php';

$authController = new AuthController();

if ($authController->isLoggedIn()) {
    // Redirection selon le rôle déjà connecté
    $currentUser = $authController->getCurrentUser();
    if ($currentUser && $currentUser->estAdmin()) {
        header('Location: ../../backoffice/admin-dashboard.php');
    } elseif ($currentUser && $currentUser->getRole() === 'medecin') {
        header('Location: ../../backoffice/medecin-dashboard.php');
    } else {
        header('Location: profile.php');
    }
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $errors = [];
    
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
            $selectedRole = $_POST['role'] ?? 'patient';
            
            if ($user->getRole() !== $selectedRole) {
                $authController->logout();
                $displayRole = $selectedRole === 'patient' ? 'patient' : $selectedRole;
                $error = "Erreur: Ce compte n'est pas enregistré en tant que " . htmlspecialchars($displayRole) . ".";
            } else {
                // Redirection selon le rôle après connexion
                if ($user->estAdmin()) {
                    header('Location: ../../backoffice/admin-dashboard.php');
                } elseif ($user->getRole() === 'medecin') {
                    header('Location: ../../backoffice/medecin-dashboard.php');
                } else {
                    header('Location: profile.php');
                }
                exit;
            }
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

        .login-container {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .login-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 48px 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(29,158,117,.15);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .login-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-green);
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .logo:hover {
            transform: scale(1.02);
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-green);
        }

        .logo-icon i {
            font-size: 24px;
            color: white;
        }

        .logo-text {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.5px;
        }

        .logo-text span {
            color: var(--green);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--gray-500);
            font-size: 14px;
        }

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
            to { opacity: 1; transform: translateY(0); }
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

        .alert i {
            font-size: 18px;
            margin-top: 2px;
        }

        .alert div {
            flex: 1;
            line-height: 1.5;
        }

        .login-form {
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--navy);
            font-weight: 600;
            font-size: 14px;
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
            left: 16px;
            color: var(--gray-500);
            font-size: 18px;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            background: var(--white);
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(29,158,117,.15);
        }

        .form-control.error {
            border-color: #EF4444;
            background: #FEF2F2;
        }

        .input-wrapper:focus-within .input-icon {
            color: var(--green);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            font-size: 18px;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--green);
        }

        .forgot-password {
            text-align: right;
            margin-top: 8px;
        }

        .forgot-password a {
            color: var(--green);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-password a:hover {
            color: var(--green-dark);
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 3px 12px rgba(29,158,117,.30);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29,158,117,.40);
            background: linear-gradient(135deg, var(--green-dark), var(--green-deep));
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: var(--gray-500);
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--gray-200);
        }

        .divider::before {
            margin-right: 16px;
        }

        .divider::after {
            margin-left: 16px;
        }

        .register-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
        }

        .register-link p {
            color: var(--gray-500);
            font-size: 14px;
            margin-bottom: 12px;
        }

        .btn-register {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 28px;
            background: transparent;
            color: var(--green);
            border: 2px solid var(--green);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-register:hover {
            background: var(--green);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-green);
        }

        .back-home {
            text-align: center;
            margin-top: 20px;
        }

        .back-home a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-500);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }

        .back-home a:hover {
            color: var(--green);
        }

        @media (max-width: 560px) {
            .login-card {
                padding: 32px 24px;
            }
            
            .logo-icon {
                width: 40px;
                height: 40px;
            }
            
            .logo-icon i {
                font-size: 20px;
            }
            
            .logo-text {
                font-size: 22px;
            }
            
            .login-header h2 {
                font-size: 24px;
            }
            
            .form-control {
                padding: 12px 14px 12px 44px;
                font-size: 14px;
            }
            
            .input-icon {
                left: 14px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 16px;
            }
            
            .login-card {
                padding: 28px 20px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card" data-aos="fade-up" data-aos-duration="600">
        <a href="../home/index.php" class="logo">
            <div class="logo-icon">
                <i class="bi bi-plus-square-fill"></i>
            </div>
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
                <label for="role">
                    <i class="bi bi-person-badge-fill"></i> Je me connecte en tant que
                </label>
                <div class="input-wrapper">
                    <i class="bi bi-person-badge input-icon"></i>
                    <select name="role" id="role" class="form-control" style="padding-left: 48px; appearance: auto;" required>
                        <option value="patient" <?php echo (isset($_POST['role']) && $_POST['role'] === 'patient') ? 'selected' : ''; ?>>Patient</option>
                        <option value="medecin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'medecin') ? 'selected' : ''; ?>>Médecin</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="email">
                    <i class="bi bi-envelope-fill"></i> Adresse email
                </label>
                <div class="input-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($email); ?>" 
                           placeholder="exemple@email.com"
                           autocomplete="email"
                           required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="mot_de_passe">
                    <i class="bi bi-lock-fill"></i> Mot de passe
                </label>
                <div class="input-wrapper">
                    <i class="bi bi-key input-icon"></i>
                    <input type="password" 
                           id="mot_de_passe" 
                           name="mot_de_passe" 
                           class="form-control" 
                           placeholder="Votre mot de passe"
                           autocomplete="current-password"
                           required>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
                <div class="forgot-password">
                    <a href="forgot-password.php">
                        <i class="bi bi-question-circle"></i> Mot de passe oublié ?
                    </a>
                </div>
            </div>
            
            <button type="submit" class="btn-login" id="submitBtn">
                <span class="spinner" id="spinner"></span>
                <i class="bi bi-box-arrow-in-right"></i>
                <span id="btnText">Se connecter</span>
            </button>
        </form>
        
        <div class="divider">
            <span>Nouveau sur MedChain ?</span>
        </div>
        
        <div class="register-link">
            <a href="register.php" class="btn-register">
                <i class="bi bi-person-plus-fill"></i>
                Créer un compte
            </a>
        </div>
        
        <div class="back-home">
            <a href="../home/index.php">
                <i class="bi bi-arrow-left"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 20 });
    
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('mot_de_passe');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
    
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.getElementById('spinner');
    const btnText = document.getElementById('btnText');
    
    if (loginForm) {
        emailInput.addEventListener('input', function() {
            this.classList.remove('error');
            const errorDiv = document.querySelector('.alert-error');
            if (errorDiv && this.value.trim() !== '') errorDiv.remove();
        });
        
        passwordInput.addEventListener('input', function() {
            this.classList.remove('error');
            const errorDiv = document.querySelector('.alert-error');
            if (errorDiv && this.value.trim() !== '') errorDiv.remove();
        });
        
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            let errorMessage = '';
            
            const email = emailInput.value.trim();
            const password = passwordInput.value;
            
            if (!email) {
                errorMessage = 'L\'adresse email est obligatoire';
                emailInput.classList.add('error');
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errorMessage = 'Format d\'email invalide';
                emailInput.classList.add('error');
                isValid = false;
            } else {
                emailInput.classList.remove('error');
            }
            
            if (!password) {
                if (!errorMessage) errorMessage = 'Le mot de passe est obligatoire';
                passwordInput.classList.add('error');
                isValid = false;
            } else if (password.length < 6) {
                if (!errorMessage) errorMessage = 'Le mot de passe doit contenir au moins 6 caractères';
                passwordInput.classList.add('error');
                isValid = false;
            } else {
                passwordInput.classList.remove('error');
            }
            
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
                btnText.textContent = 'Connexion en cours...';
            }
        });
    }
    
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
</script>
</body>
</html>