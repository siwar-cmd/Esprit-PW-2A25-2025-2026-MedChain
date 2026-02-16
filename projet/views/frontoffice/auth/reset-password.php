<?php
session_start();
require_once __DIR__ . '/../../../controllers/PasswordController.php';

$passwordController = new PasswordController();
$error_message = null;
$success_message = null;
$token = $_GET['token'] ?? '';

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
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error_message = "Veuillez remplir tous les champs.";
    } elseif ($newPassword !== $confirmPassword) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($newPassword) < 8) {
        $error_message = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $result = $passwordController->resetPassword($token, $newPassword);
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

        .reset-container {
            max-width: 520px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 2;
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

        .reset-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .reset-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .reset-header p {
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

        .info-box {
            background: var(--green-pale);
            border: 1px solid rgba(29,158,117,.2);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-box i {
            color: var(--green);
            font-size: 18px;
        }

        .info-box p {
            color: var(--gray-700);
            font-size: 13px;
            margin: 0;
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

        .requirements {
            margin-top: 20px;
            padding: 16px;
            background: var(--gray-100);
            border-radius: var(--radius-md);
        }

        .requirements p {
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 10px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 6px;
        }

        .requirement i {
            font-size: 10px;
            color: var(--gray-400);
        }

        .requirement.met i {
            color: #22C55E;
        }

        .requirement.met {
            color: var(--green);
        }

        .btn-reset {
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
            margin-top: 24px;
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29,158,117,.40);
            background: linear-gradient(135deg, var(--green-dark), var(--green-deep));
        }

        .btn-reset:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-reset .spinner {
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

        .back-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
        }

        .back-link a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-500);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }

        .back-link a:hover {
            color: var(--green);
        }

        @media (max-width: 560px) {
            .reset-card {
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
            
            .reset-header h2 {
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
            
            .reset-card {
                padding: 28px 20px;
            }
        }
    </style>
</head>
<body>

<div class="reset-container">
    <div class="reset-card" data-aos="fade-up" data-aos-duration="600">
        <a href="../home/index.php" class="logo">
            <div class="logo-icon">
                <i class="bi bi-plus-square-fill"></i>
            </div>
            <div class="logo-text">Med<span>Chain</span></div>
        </a>
        
        <div class="reset-header">
            <h2>Réinitialisation</h2>
            <p>Créez un nouveau mot de passe sécurisé</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= htmlspecialchars($error_message) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <div><?= htmlspecialchars($success_message) ?></div>
            </div>
            <div class="back-link">
                <a href="sign-in.php">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Se connecter
                </a>
            </div>
        <?php endif; ?>

        <?php if (!$success_message && !empty($token)): ?>
            <div class="info-box">
                <i class="bi bi-info-circle-fill"></i>
                <p>Votre nouveau mot de passe doit contenir au moins 8 caractères avec des lettres et des chiffres.</p>
            </div>
            
            <form method="POST" action="" id="resetForm">
                <div class="form-group">
                    <label><i class="bi bi-lock-fill"></i> Nouveau mot de passe</label>
                    <div class="input-wrapper">
                        <i class="bi bi-key input-icon"></i>
                        <input type="password" id="new_password" name="new_password" class="form-control" 
                               placeholder="Minimum 8 caractères" required>
                        <button type="button" class="password-toggle" id="toggleNew">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-lock-fill"></i> Confirmer le mot de passe</label>
                    <div class="input-wrapper">
                        <i class="bi bi-key input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Confirmez votre mot de passe" required>
                        <button type="button" class="password-toggle" id="toggleConfirm">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="requirements">
                    <p>Exigences de sécurité :</p>
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
                
                <button type="submit" class="btn-reset" id="submitBtn">
                    <span class="spinner" id="spinner"></span>
                    <i class="bi bi-arrow-repeat"></i>
                    <span id="btnText">Réinitialiser</span>
                </button>
            </form>
            
            <div class="back-link">
                <a href="sign-in.php">
                    <i class="bi bi-arrow-left"></i>
                    Retour à la connexion
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 20 });
    
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const form = document.getElementById('resetForm');
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.getElementById('spinner');
    const btnText = document.getElementById('btnText');
    
    // Password toggle
    document.getElementById('toggleNew')?.addEventListener('click', () => {
        const type = newPassword.type === 'password' ? 'text' : 'password';
        newPassword.type = type;
        const icon = document.getElementById('toggleNew').querySelector('i');
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });
    
    document.getElementById('toggleConfirm')?.addEventListener('click', () => {
        const type = confirmPassword.type === 'password' ? 'text' : 'password';
        confirmPassword.type = type;
        const icon = document.getElementById('toggleConfirm').querySelector('i');
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });
    
    function updateRequirements() {
        const password = newPassword.value;
        const confirm = confirmPassword.value;
        
        const hasLength = password.length >= 8;
        const hasLetter = /[A-Za-z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasMatch = password === confirm && password !== '';
        
        const reqLength = document.getElementById('reqLength');
        const reqLetter = document.getElementById('reqLetter');
        const reqNumber = document.getElementById('reqNumber');
        const reqMatch = document.getElementById('reqMatch');
        
        reqLength.className = hasLength ? 'requirement met' : 'requirement';
        reqLength.querySelector('i').className = hasLength ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        
        reqLetter.className = hasLetter ? 'requirement met' : 'requirement';
        reqLetter.querySelector('i').className = hasLetter ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        
        reqNumber.className = hasNumber ? 'requirement met' : 'requirement';
        reqNumber.querySelector('i').className = hasNumber ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        
        reqMatch.className = hasMatch ? 'requirement met' : 'requirement';
        reqMatch.querySelector('i').className = hasMatch ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        
        return hasLength && hasLetter && hasNumber && hasMatch;
    }
    
    newPassword.addEventListener('input', updateRequirements);
    confirmPassword.addEventListener('input', updateRequirements);
    
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!updateRequirements()) {
                e.preventDefault();
            } else {
                submitBtn.disabled = true;
                spinner.style.display = 'inline-block';
                btnText.textContent = 'Réinitialisation...';
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