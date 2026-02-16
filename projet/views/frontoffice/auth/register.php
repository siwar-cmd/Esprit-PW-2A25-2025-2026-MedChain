<?php
session_start();
include_once '../../../controllers/AuthController.php';

$authController = new AuthController();

if ($authController->isLoggedIn()) {
    header('Location: ../home/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => trim($_POST['nom'] ?? ''),
        'prenom' => trim($_POST['prenom'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'dateNaissance' => trim($_POST['dateNaissance'] ?? ''),
        'adresse' => trim($_POST['adresse'] ?? ''),
        'telephone' => trim($_POST['telephone'] ?? '')
    ];

    $errors = [];
    
    $required = ['nom', 'prenom', 'email', 'mot_de_passe', 'confirm_password'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "Le champ '" . ucfirst($field) . "' est obligatoire";
        }
    }
    
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    }
    
    if (!empty($data['mot_de_passe'])) {
        if (strlen($data['mot_de_passe']) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
        }
        if ($data['mot_de_passe'] !== $data['confirm_password']) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
    }
    
    if (!empty($data['telephone'])) {
        if (!preg_match('/^[0-9+\-\s]{8,20}$/', $data['telephone'])) {
            $errors[] = "Format de téléphone invalide";
        }
    }
    
    if (!empty($data['dateNaissance'])) {
        $birthDate = new DateTime($data['dateNaissance']);
        $today = new DateTime();
        $minDate = new DateTime();
        $minDate->modify('-120 years');
        
        if ($birthDate > $today) {
            $errors[] = "La date de naissance ne peut pas être dans le futur";
        } elseif ($birthDate < $minDate) {
            $errors[] = "L'âge maximum est de 120 ans";
        }
    }
    
    if (empty($errors)) {
        $allowed_roles = ['admin', 'patient', 'medecin'];
        $role = $_POST['role'] ?? 'patient';
        if (!in_array($role, $allowed_roles)) {
            $role = 'patient';
        }
        $data['role'] = $role;
        $data['statut'] = 'actif';
        unset($data['confirm_password']);
        
        $result = $authController->register($data);
        
        if ($result['success']) {
            $success = "Votre compte a été créé avec succès ! Vous allez être redirigé vers la page de connexion...";
            header('refresh:3;url=login.php');
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
    <title>Inscription - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    
    <style>
        /* ══════════════════════════════════════
           RESET & ROOT VARIABLES (identique à votre code)
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

        .container {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        /* Register card */
        .register-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 48px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(29,158,117,.15);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .register-card:hover {
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

        .register-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .register-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .register-header p {
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
            padding: 16px 20px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-box i {
            color: var(--green);
            font-size: 20px;
        }

        .info-box p {
            color: var(--gray-700);
            font-size: 13px;
            margin: 0;
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

        .required {
            color: #EF4444;
            margin-left: 4px;
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
            padding: 12px 14px 12px 42px;
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

        .form-control.error {
            border-color: #EF4444;
            background: #FEF2F2;
        }

        .form-control.valid {
            border-color: #22C55E;
            background: #F0FDF4;
        }

        textarea.form-control {
            padding-left: 42px;
            resize: vertical;
            min-height: 80px;
        }

        .input-wrapper:focus-within .input-icon {
            color: var(--green);
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--green);
        }

        /* Password Strength (conservé) */
        .password-strength {
            margin-top: 8px;
        }

        .strength-bar {
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 6px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }

        .strength-fill.weak { width: 25%; background: #EF4444; }
        .strength-fill.fair { width: 50%; background: #F59E0B; }
        .strength-fill.good { width: 75%; background: #3B82F6; }
        .strength-fill.strong { width: 100%; background: #22C55E; }

        .strength-text {
            font-size: 11px;
            color: var(--gray-500);
        }

        .validation-message {
            font-size: 11px;
            margin-top: 5px;
            color: #EF4444;
            display: none;
        }

        .validation-message.show {
            display: block;
        }

        .btn-submit {
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
            margin-top: 20px;
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

        .btn-submit .spinner {
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
            font-size: 12px;
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

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: var(--green);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .login-link a:hover {
            color: var(--green-dark);
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 16px;
        }

        .back-home a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--gray-500);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.2s;
        }

        .back-home a:hover {
            color: var(--green);
        }

        @media (max-width: 768px) {
            .register-card { padding: 32px 24px; }
            .form-grid { grid-template-columns: 1fr; gap: 0; }
            .form-group.full-width { grid-column: span 1; }
            .logo-icon { width: 40px; height: 40px; }
            .logo-icon i { font-size: 20px; }
            .logo-text { font-size: 22px; }
            .register-header h2 { font-size: 24px; }
        }

        @media (max-width: 480px) {
            body { padding: 20px 16px; }
            .register-card { padding: 24px 20px; }
            .form-control { padding: 10px 12px 10px 38px; font-size: 13px; }
            .input-icon { left: 12px; font-size: 14px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="register-card" data-aos="fade-up" data-aos-duration="600">
        <!-- Logo -->
        <a href="../home/index.php" class="logo">
            <div class="logo-icon">
                <i class="bi bi-plus-square-fill"></i>
            </div>
            <div class="logo-text">Med<span>Chain</span></div>
        </a>
        
        <!-- Header -->
        <div class="register-header">
            <h2>Créer un compte</h2>
            <p>Rejoignez la plateforme médicale de confiance</p>
        </div>
        
        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?php echo $error; ?></div>
            </div>
        <?php endif; ?>
        
        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <div><?php echo $success; ?></div>
            </div>
        <?php endif; ?>
        
        <!-- Info Box -->
        <div class="info-box">
            <i class="bi bi-shield-lock-fill"></i>
            <p>Vos données sont sécurisées et protégées par le chiffrement blockchain. Aucune information ne sera partagée sans votre consentement.</p>
        </div>
        
        <!-- Registration Form -->
        <form method="POST" action="" id="registerForm" novalidate>
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="bi bi-person-fill"></i> Prénom <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" id="prenom" name="prenom" class="form-control" 
                               value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>"
                               placeholder="Votre prénom" required>
                    </div>
                    <div class="validation-message" id="prenom-error">Le prénom doit contenir au moins 2 lettres</div>
                </div>
                
                <div class="form-group full-width">
                    <label><i class="bi bi-person-badge-fill"></i> Type de compte <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-person-badge input-icon"></i>
                        <select name="role" id="role" class="form-control" required style="padding-left: 42px; appearance: auto;">
                            <option value="patient" <?php echo (isset($_POST['role']) && $_POST['role'] === 'patient') ? 'selected' : ''; ?>>Patient</option>
                            <option value="medecin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'medecin') ? 'selected' : ''; ?>>Médecin</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-person-fill"></i> Nom <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>"
                               placeholder="Votre nom" required>
                    </div>
                    <div class="validation-message" id="nom-error">Le nom doit contenir au moins 2 lettres</div>
                </div>
                
                <div class="form-group full-width">
                    <label><i class="bi bi-envelope-fill"></i> Email <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="exemple@email.com" required>
                    </div>
                    <div class="validation-message" id="email-error">Format d'email invalide</div>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-key-fill"></i> Mot de passe <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" 
                               placeholder="Minimum 6 caractères" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                    <!-- SECTION GÉNÉRATEUR SUPPRIMÉE -->
                    <div class="password-strength">
                        <div class="strength-text" id="strengthText">Force du mot de passe</div>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                    </div>
                    <div class="validation-message" id="password-error">Le mot de passe doit contenir au moins 6 caractères</div>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-key-fill"></i> Confirmer <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Confirmez votre mot de passe" required>
                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                    <div class="validation-message" id="confirm-error">Les mots de passe ne correspondent pas</div>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-calendar-fill"></i> Date de naissance</label>
                    <div class="input-wrapper">
                        <i class="bi bi-calendar input-icon"></i>
                        <input type="date" id="dateNaissance" name="dateNaissance" class="form-control" 
                               value="<?php echo isset($_POST['dateNaissance']) ? htmlspecialchars($_POST['dateNaissance']) : ''; ?>">
                    </div>
                    <div class="validation-message" id="date-error">Date invalide</div>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-telephone-fill"></i> Téléphone</label>
                    <div class="input-wrapper">
                        <i class="bi bi-telephone input-icon"></i>
                        <input type="tel" id="telephone" name="telephone" class="form-control" 
                               value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>"
                               placeholder="+216 XX XXX XXX">
                    </div>
                    <div class="validation-message" id="phone-error">Format de téléphone invalide</div>
                </div>
                
                <div class="form-group full-width">
                    <label><i class="bi bi-geo-alt-fill"></i> Adresse</label>
                    <div class="input-wrapper">
                        <i class="bi bi-geo-alt input-icon"></i>
                        <textarea id="adresse" name="adresse" class="form-control" 
                                  placeholder="Votre adresse complète"><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-submit" id="submitBtn">
                <span class="spinner" id="spinner"></span>
                <i class="bi bi-person-plus-fill"></i>
                <span id="btnText">Créer mon compte</span>
            </button>
        </form>
        
        <div class="divider">
            <span>Vous avez déjà un compte ?</span>
        </div>
        
        <div class="login-link">
            <a href="login.php">
                <i class="bi bi-box-arrow-in-right"></i> Se connecter
            </a>
        </div>
        
        <div class="back-home">
            <a href="../home/index.php">
                <i class="bi bi-arrow-left"></i> Retour à l'accueil
            </a>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 20 });
    
    // DOM Elements
    const form = document.getElementById('registerForm');
    const prenomInput = document.getElementById('prenom');
    const nomInput = document.getElementById('nom');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('mot_de_passe');
    const confirmInput = document.getElementById('confirm_password');
    const dateInput = document.getElementById('dateNaissance');
    const phoneInput = document.getElementById('telephone');
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.getElementById('spinner');
    const btnText = document.getElementById('btnText');
    
    // Password Toggle
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirm = document.getElementById('toggleConfirmPassword');
    
    if (togglePassword) {
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            togglePassword.querySelector('i').classList.toggle('bi-eye');
            togglePassword.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }
    
    if (toggleConfirm) {
        toggleConfirm.addEventListener('click', () => {
            const type = confirmInput.type === 'password' ? 'text' : 'password';
            confirmInput.type = type;
            toggleConfirm.querySelector('i').classList.toggle('bi-eye');
            toggleConfirm.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }
    
    // Password Strength Checker (conservé)
    function checkPasswordStrength(password) {
        let score = 0;
        if (password.length >= 6) score++;
        if (password.length >= 10) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        if (score <= 2) return { level: 'weak', text: 'Faible' };
        if (score <= 4) return { level: 'fair', text: 'Moyen' };
        if (score <= 5) return { level: 'good', text: 'Bon' };
        return { level: 'strong', text: 'Fort' };
    }
    
    function updatePasswordStrength() {
        const password = passwordInput.value;
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        if (!password) {
            strengthFill.style.width = '0%';
            strengthFill.className = 'strength-fill';
            strengthText.textContent = 'Force du mot de passe';
            return;
        }
        
        const strength = checkPasswordStrength(password);
        strengthFill.style.width = strength.level === 'weak' ? '25%' : 
                                   strength.level === 'fair' ? '50%' : 
                                   strength.level === 'good' ? '75%' : '100%';
        strengthFill.className = `strength-fill ${strength.level}`;
        strengthText.textContent = `Force : ${strength.text}`;
    }
    
    passwordInput.addEventListener('input', updatePasswordStrength);
    
    // Validation Functions (identique)
    function validateField(field) {
        const value = field.value.trim();
        const errorId = field.id + '-error';
        const errorElement = document.getElementById(errorId);
        
        if (!errorElement) return true;
        
        switch(field.id) {
            case 'prenom':
            case 'nom':
                if (!value) {
                    field.classList.add('error');
                    field.classList.remove('valid');
                    errorElement.classList.add('show');
                    errorElement.textContent = 'Ce champ est obligatoire';
                    return false;
                } else if (value.length < 2 || !/^[A-Za-zÀ-ÿ\s\-']+$/.test(value)) {
                    field.classList.add('error');
                    field.classList.remove('valid');
                    errorElement.classList.add('show');
                    errorElement.textContent = field.id === 'prenom' ? 'Prénom invalide (2+ lettres)' : 'Nom invalide (2+ lettres)';
                    return false;
                } else {
                    field.classList.remove('error');
                    field.classList.add('valid');
                    errorElement.classList.remove('show');
                    return true;
                }
                
            case 'email':
                if (!value) {
                    field.classList.add('error');
                    field.classList.remove('valid');
                    errorElement.classList.add('show');
                    errorElement.textContent = 'L\'email est obligatoire';
                    return false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    field.classList.add('error');
                    field.classList.remove('valid');
                    errorElement.classList.add('show');
                    errorElement.textContent = 'Format d\'email invalide';
                    return false;
                } else {
                    field.classList.remove('error');
                    field.classList.add('valid');
                    errorElement.classList.remove('show');
                    return true;
                }
                
            case 'mot_de_passe':
                if (!value) {
                    field.classList.add('error');
                    field.classList.remove('valid');
                    errorElement.classList.add('show');
                    errorElement.textContent = 'Le mot de passe est obligatoire';
                    return false;
                } else if (value.length < 6) {
                    field.classList.add('error');
                    field.classList.remove('valid');
                    errorElement.classList.add('show');
                    errorElement.textContent = 'Le mot de passe doit contenir au moins 6 caractères';
                    return false;
                } else {
                    field.classList.remove('error');
                    field.classList.add('valid');
                    errorElement.classList.remove('show');
                    return true;
                }
                
            case 'confirm_password':
                const password = document.getElementById('mot_de_passe').value;
                if (!value) {
                    field.classList.add('error');
                    field.classList.remove('valid');
                    errorElement.classList.add('show');
                    errorElement.textContent = 'Confirmation requise';
                    return false;
                } else if (value !== password) {
                    field.classList.add('error');
                    field.classList.remove('valid');
                    errorElement.classList.add('show');
                    errorElement.textContent = 'Les mots de passe ne correspondent pas';
                    return false;
                } else {
                    field.classList.remove('error');
                    field.classList.add('valid');
                    errorElement.classList.remove('show');
                    return true;
                }
                
            case 'telephone':
                if (value && !/^[0-9+\-\s]{8,20}$/.test(value)) {
                    field.classList.add('error');
                    field.classList.remove('valid');
                    errorElement.classList.add('show');
                    return false;
                } else {
                    field.classList.remove('error');
                    if (value) field.classList.add('valid');
                    errorElement.classList.remove('show');
                    return true;
                }
                
            case 'dateNaissance':
                if (value) {
                    const selected = new Date(value);
                    const today = new Date();
                    if (selected > today) {
                        field.classList.add('error');
                        field.classList.remove('valid');
                        errorElement.classList.add('show');
                        errorElement.textContent = 'La date ne peut pas être dans le futur';
                        return false;
                    } else {
                        field.classList.remove('error');
                        field.classList.add('valid');
                        errorElement.classList.remove('show');
                        return true;
                    }
                } else {
                    field.classList.remove('error', 'valid');
                    errorElement.classList.remove('show');
                    return true;
                }
                
            default:
                return true;
        }
    }
    
    // Add event listeners
    const fields = ['prenom', 'nom', 'email', 'mot_de_passe', 'confirm_password', 'telephone', 'dateNaissance'];
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', () => validateField(field));
            field.addEventListener('blur', () => validateField(field));
        }
    });
    
    // Form Submission
    if (form) {
        form.addEventListener('submit', (e) => {
            let isValid = true;
            
            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && !validateField(field)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                const firstError = document.querySelector('.form-control.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            } else {
                submitBtn.disabled = true;
                spinner.style.display = 'inline-block';
                btnText.textContent = 'Création en cours...';
            }
        });
    }
    
    // Auto-hide alerts
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