<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
define('BASE_PATH', dirname(__DIR__));
define('MODEL_PATH', BASE_PATH . '/model/UserModel.php');

// Si déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    $redirectUrl = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') 
        ? '../admin/admin.php' 
        : '../profile.php';
    header('Location: ' . $redirectUrl);
    exit();
}

$error = '';
$success = '';

// Vérifier si inscription réussie
if (isset($_GET['success']) && $_GET['success'] === 'registered') {
    $success = "Inscription réussie ! Veuillez vous connecter.";
}

// Vérifier si déconnexion
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = "Vous avez été déconnecté avec succès.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!file_exists(MODEL_PATH)) {
        $error = "Erreur de configuration. Contactez l'administrateur.";
    } elseif (!class_exists('UserModel')) {
        require_once MODEL_PATH;
        if (!class_exists('UserModel')) {
            $error = "Erreur technique : configuration incorrecte";
        }
    }
    
    if (empty($error)) {
        $userModel = new UserModel();
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format d'email invalide";
        } else {
            $user = $userModel->login($email, $password);
            
            if ($user && isset($user['id'])) {
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_nom'] = htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                $_SESSION['user_email'] = htmlspecialchars($user['email']);
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                $_SESSION['user_status'] = $user['status'] ?? 'actif';
                
                if ($remember) {
                    $remember_token = bin2hex(random_bytes(32));
                    $expiry = time() + (86400 * 30);
                    setcookie('remember_token', $remember_token, $expiry, '/', '', false, true);
                    if (method_exists($userModel, 'saveRememberToken')) {
                        $userModel->saveRememberToken($user['id'], $remember_token, $expiry);
                    }
                } else {
                    setcookie('remember_token', '', time() - 3600, '/');
                }
                
                $redirectUrl = ($user['role'] === 'admin') ? '../admin/admin.php' : '../profile.php';
                header('Location: ' . $redirectUrl);
                exit();
            } else {
                $error = "Email ou mot de passe incorrect";
                error_log("Tentative de connexion échouée pour: " . $email);
            }
        }
    }
}

// Vérifier le token "se souvenir de moi"
if (empty($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    if (file_exists(MODEL_PATH)) {
        require_once MODEL_PATH;
        if (class_exists('UserModel')) {
            $userModel = new UserModel();
            if (method_exists($userModel, 'getUserByRememberToken')) {
                $user = $userModel->getUserByRememberToken($_COOKIE['remember_token']);
                if ($user && isset($user['id'])) {
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_nom'] = htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                    $_SESSION['user_email'] = htmlspecialchars($user['email']);
                    $_SESSION['user_role'] = $user['role'] ?? 'user';
                    $_SESSION['user_status'] = $user['status'] ?? 'actif';
                    $redirectUrl = ($user['role'] === 'admin') ? '../admin/admin.php' : '../profile.php';
                    header('Location: ' . $redirectUrl);
                    exit();
                } else {
                    setcookie('remember_token', '', time() - 3600, '/');
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* ===== Charte MedChain ===== */
        :root {
            --primary: #1D9E75;
            --secondary: #0F6E56;
            --accent: #9FE1CB;
            --neutral: #888780;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0faf6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== Top Bar ===== */
        .top-bar {
            background: var(--secondary);
            color: #fff;
            font-size: 13px;
            padding: 8px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar a { color: #fff; text-decoration: none; margin-right: 18px; transition: color 0.3s; }
        .top-bar a:hover { color: var(--accent); }
        .top-bar .social-links a { margin-right: 0; margin-left: 14px; }

        /* ===== Brand Bar ===== */
        .brand-bar {
            background: #fff;
            padding: 12px 5%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo {
            font-family: 'Segoe UI', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .brand-logo span { color: var(--secondary); }

        .brand-bar a.back-link {
            font-size: 14px;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .brand-bar a.back-link:hover { color: var(--primary); }

        /* ===== Main Layout ===== */
        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 15px;
        }

        .auth-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(15,110,86,0.12);
            width: 100%;
            max-width: 460px;
            overflow: hidden;
        }

        /* ===== Card Header ===== */
        .auth-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            text-align: center;
            padding: 32px 30px 28px;
        }

        .auth-header .auth-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 24px;
        }

        .auth-header h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .auth-header p {
            opacity: 0.88;
            font-size: 14px;
            margin: 0;
        }

        /* ===== Card Body ===== */
        .auth-body {
            padding: 36px 36px 30px;
        }

        /* ===== Form Controls ===== */
        .form-label {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 6px;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(29,158,117,0.15);
            outline: none;
        }

        .input-group .form-control { border-right: none; border-radius: 12px 0 0 12px; }
        .input-group .input-group-text {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-left: none;
            border-radius: 0 12px 12px 0;
            cursor: pointer;
            color: var(--neutral);
            transition: color 0.3s;
        }
        .input-group .input-group-text:hover { color: var(--primary); }
        .input-group:focus-within .input-group-text { border-color: var(--primary); }

        /* ===== Checkbox ===== */
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label { font-size: 14px; color: #555; }

        /* ===== Submit Button ===== */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 13px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
            letter-spacing: 0.3px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(29,158,117,0.35);
        }

        .btn-submit:active { transform: translateY(0); }

        /* ===== Links ===== */
        .auth-links { text-align: center; margin-top: 24px; }
        .auth-links a { color: var(--primary); text-decoration: none; font-weight: 500; font-size: 14px; transition: color 0.3s; }
        .auth-links a:hover { color: var(--secondary); }
        .auth-links .divider { margin: 0 8px; color: #ccc; }

        /* ===== Alerts ===== */
        .alert {
            border-radius: 12px;
            font-size: 14px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-danger { background: #fff0f0; color: #c0392b; }
        .alert-success { background: #f0faf6; color: var(--secondary); }

        /* ===== Footer ===== */
        .auth-footer {
            background: #0a2e24;
            color: #8fa89f;
            text-align: center;
            padding: 14px;
            font-size: 13px;
        }

        .auth-footer a { color: var(--accent); text-decoration: none; }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div>
            <a href="tel:+21670000000"><i class="fas fa-phone-alt"></i> +216 70 000 000</a>
            <a href="mailto:contact@medchain.com"><i class="fas fa-envelope"></i> contact@medchain.com</a>
        </div>
        <div class="social-links">
            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        </div>
    </div>

    <!-- Brand Bar -->
    <div class="brand-bar">
        <a href="../index.php" class="brand-logo">Med<span>Chain</span></a>
        <a href="../home/index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Retour à l'accueil</a>
    </div>

    <!-- Auth Form -->
    <div class="auth-wrapper">
        <div class="auth-card">

            <div class="auth-header">
                <div class="auth-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3>Connexion</h3>
                <p>Accédez à votre espace MedChain</p>
            </div>

            <div class="auth-body">

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">

                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="exemple@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="••••••••" required>
                            <span class="input-group-text" onclick="togglePassword()">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Se souvenir de moi</label>
                        </div>
                        <a href="forgot-password.php" style="font-size:13px; color:var(--secondary);">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" name="submit" class="btn-submit">
                        <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                    </button>

                </form>

                <div class="auth-links">
                    <span style="font-size:14px; color:#888;">Pas encore de compte ?</span>
                    <a href="register.php"> S'inscrire</a>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="auth-footer">
        &copy; <?php echo date('Y'); ?> MedChain — <a href="../home/index.php">Retour à l'accueil</a>
    </div>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Auto-fermeture des alertes après 5 secondes
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                setTimeout(() => bsAlert.close(), 5000);
            });
        }, 1000);
    </script>
</body>
</html>