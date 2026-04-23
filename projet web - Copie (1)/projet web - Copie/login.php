<?php
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($email === 'client@gmail.com' && $pass === 'client123') {
        // Redirection vers le front office
        header('Location: index.php');
        exit;
    } elseif ($email === 'admin@gmail.com' && $pass === 'admin123') {
        // Redirection vers le back office
        $backOffice = 'http://localhost/projet%20web2%20-%20Copie/projet%20web2%20-%20Copie/projet%20web2%20-%20Copie/projet%20web2%20-%20Copie/index.php';
        header('Location: ' . $backOffice);
        exit;
    } else {
        $error = 'Email ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedChain | Connexion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            position: relative;
            overflow: hidden;
        }

        /* Animated background blobs */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            animation: float 8s ease-in-out infinite alternate;
        }
        body::before {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #6c63ff, #302b63);
            top: -100px; left: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #00d2ff, #3a7bd5);
            bottom: -100px; right: -100px;
            animation-delay: 3s;
        }

        @keyframes float {
            from { transform: translateY(0) scale(1); }
            to   { transform: translateY(30px) scale(1.05); }
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 16px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            margin-bottom: 36px;
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #6c63ff, #3a7bd5);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(108, 99, 255, 0.4);
        }

        .brand-logo svg {
            width: 32px; height: 32px; fill: white;
        }

        .brand h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .brand p {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.5);
            margin-top: 6px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.4;
        }

        .form-group input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            color: #ffffff;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.25s ease;
            outline: none;
        }

        .form-group input::placeholder { color: rgba(255,255,255,0.25); }

        .form-group input:focus {
            border-color: #6c63ff;
            background: rgba(108, 99, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
        }

        .error-msg {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 80, 80, 0.12);
            border: 1px solid rgba(255, 80, 80, 0.3);
            border-radius: 10px;
            padding: 12px 14px;
            color: #ff6b6b;
            font-size: 0.85rem;
            margin-bottom: 20px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60%  { transform: translateX(-6px); }
            40%, 80%  { transform: translateX(6px); }
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #6c63ff, #3a7bd5);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 8px 20px rgba(108, 99, 255, 0.35);
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.1);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(108,99,255,0.5); }
        .btn-login:hover::after { opacity: 1; }
        .btn-login:active { transform: translateY(0); }

        .footer-note {
            text-align: center;
            margin-top: 28px;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <div class="brand">
            <div class="brand-logo">
                <!-- Medical cross icon -->
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
                </svg>
            </div>
            <h1>MedChain</h1>
            <p>Connectez-vous pour continuer</p>
        </div>

        <?php if ($error): ?>
        <div class="error-msg">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <div class="form-group">
                <label for="email">Adresse Email</label>
                <div class="input-wrapper">
                    <span class="icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="exemple@gmail.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <span class="icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Votre mot de passe"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <button type="submit" class="btn-login">Se connecter</button>
        </form>

        <p class="footer-note">MedChain &copy; <?php echo date('Y'); ?> &mdash; Tous droits r&eacute;serv&eacute;s</p>
    </div>
</div>

</body>
</html>
