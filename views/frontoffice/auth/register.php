<?php
session_start();
include_once '../../../controllers/AuthController.php';

$authController = new AuthController();

if ($authController->isLoggedIn()) {
    header('Location: ../home/index.php');
    exit;
}

// Detect role from GET param (from modal) or POST
$selectedRole = $_GET['role'] ?? $_POST['selected_role'] ?? 'patient';
if (!in_array($selectedRole, ['admin', 'patient', 'medecin'])) {
    $selectedRole = 'patient';
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedRole = trim($_POST['selected_role'] ?? 'patient');
    
    // Map modal roles to DB roles
    $dbRole = ($selectedRole === 'admin') ? 'admin' : 'user';
    $userType = ($selectedRole === 'medecin') ? 'medecin' : (($selectedRole === 'patient') ? 'patient' : null);
    
    $data = [
        'nom' => trim($_POST['nom'] ?? ''),
        'prenom' => trim($_POST['prenom'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'dateNaissance' => trim($_POST['dateNaissance'] ?? ''),
        'adresse' => trim($_POST['adresse'] ?? ''),
        'telephone' => trim($_POST['telephone'] ?? ''),
        'role' => $dbRole,
        'user_type' => $userType
    ];

    $errors = [];
    
    $required = ['nom', 'prenom', 'email', 'mot_de_passe', 'confirm_password'];

    // Validation du role
    if (!in_array($selectedRole, ['admin', 'patient', 'medecin'])) {
        $errors[] = "Rôle invalide sélectionné";
    }
    
    // Médecin extra fields validation
    if ($selectedRole === 'medecin') {
        if (empty($_POST['specialite'])) $errors[] = "La spécialité est obligatoire";
        if (empty($_POST['num_ordre'])) $errors[] = "Le numéro d'ordre est obligatoire";
        $data['specialite'] = trim($_POST['specialite'] ?? '');
        $data['num_ordre'] = trim($_POST['num_ordre'] ?? '');
    }
    
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
        // Le rôle est déjà dans $data depuis le formulaire
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


        /* ═══════════════════════════
           AI Password Suggester
        ═══════════════════════════ */
        .ai-suggest-btn {
            width: 100%;
            margin-top: 10px;
            padding: 9px 16px;
            border: 2px dashed rgba(99,102,241,.4);
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, rgba(99,102,241,.05), rgba(139,92,246,.05));
            color: #6366f1;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .ai-suggest-btn:hover {
            border-color: #6366f1;
            background: linear-gradient(135deg, rgba(99,102,241,.12), rgba(139,92,246,.10));
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(99,102,241,.2);
        }

        .ai-btn-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .ai-badge {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }

        .ai-panel {
            display: none;
            margin-top: 10px;
            border: 1.5px solid rgba(99,102,241,.25);
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #fafafa, #f5f3ff);
            overflow: hidden;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 4px 20px rgba(99,102,241,.12);
        }

        .ai-panel.visible {
            display: block;
        }

        .ai-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: linear-gradient(135deg, rgba(99,102,241,.1), rgba(139,92,246,.08));
            border-bottom: 1px solid rgba(99,102,241,.15);
            font-size: 12px;
            font-weight: 600;
            color: #4f46e5;
        }

        .ai-close {
            background: none;
            border: none;
            color: #6366f1;
            cursor: pointer;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .ai-close:hover {
            background: rgba(99,102,241,.15);
        }

        .ai-panel-body {
            padding: 12px 14px;
            min-height: 60px;
        }

        .ai-loading {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray-500);
            font-size: 12px;
        }

        .ai-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(99,102,241,.2);
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            flex-shrink: 0;
        }

        .ai-suggestions-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ai-suggestion-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: white;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
            gap: 10px;
        }

        .ai-suggestion-item:hover {
            border-color: #6366f1;
            box-shadow: 0 2px 8px rgba(99,102,241,.15);
            transform: translateX(3px);
        }

        .ai-suggestion-item.selected {
            border-color: var(--green);
            background: var(--green-pale);
        }

        .ai-pw-text {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            letter-spacing: 0.5px;
            flex: 1;
            word-break: break-all;
        }

        .ai-pw-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .ai-pw-strength {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 10px;
        }

        .ai-pw-strength.strong {
            background: #dcfce7;
            color: #166534;
        }

        .ai-pw-strength.very-strong {
            background: #dbeafe;
            color: #1e40af;
        }

        .ai-use-btn {
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white;
            border: none;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .ai-use-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(29,158,117,.3);
        }

        .ai-panel-footer {
            padding: 8px 14px;
            border-top: 1px solid rgba(99,102,241,.12);
            display: flex;
            justify-content: flex-end;
        }

        .ai-refresh-btn {
            background: none;
            border: 1px solid rgba(99,102,241,.3);
            color: #6366f1;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .ai-refresh-btn:hover {
            background: rgba(99,102,241,.08);
            border-color: #6366f1;
        }

        .ai-tip {
            font-size: 11px;
            color: var(--gray-500);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Role Selector */
        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 4px;
        }

        .role-option {
            cursor: pointer;
        }

        .role-option input[type="radio"] {
            display: none;
        }

        .role-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            background: var(--white);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .role-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(29,158,117,.05), transparent);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .role-option:hover .role-card {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(29,158,117,.12);
            transform: translateY(-2px);
        }

        .role-option input:checked + .role-card {
            border-color: var(--green);
            background: var(--green-pale);
            box-shadow: 0 0 0 3px rgba(29,158,117,.15);
        }

        .role-option input:checked + .role-card::before {
            opacity: 1;
        }

        .role-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--green-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--green);
            flex-shrink: 0;
            transition: all 0.25s;
        }

        .role-icon.admin-icon {
            background: rgba(99,102,241,.1);
            color: #6366f1;
        }

        .role-option input:checked + .role-card .role-icon {
            background: var(--green);
            color: white;
        }

        .role-option input:checked + .role-card .role-icon.admin-icon {
            background: #6366f1;
            color: white;
        }

        .role-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .role-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--navy);
        }

        .role-desc {
            font-size: 11px;
            color: var(--gray-500);
        }

        .role-check {
            color: var(--green);
            font-size: 18px;
            opacity: 0;
            transition: opacity 0.2s, transform 0.2s;
            transform: scale(0.5);
        }

        .role-option input:checked + .role-card .role-check {
            opacity: 1;
            transform: scale(1);
        }

        /* ═══════════════════════════
           AI Text Corrector
        ═══════════════════════════ */
        .ai-correct-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 11px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            padding: 4px 8px;
            cursor: pointer;
            display: none;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
            white-space: nowrap;
            z-index: 5;
        }

        .ai-correct-btn.visible {
            display: flex;
        }

        .ai-correct-btn:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 2px 8px rgba(99,102,241,.4);
        }

        .ai-correct-btn.loading {
            opacity: 0.7;
            cursor: wait;
        }

        .ai-correct-btn .ai-micro-spinner {
            width: 10px;
            height: 10px;
            border: 1.5px solid rgba(255,255,255,0.4);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .ai-corrected-hint {
            font-size: 11px;
            margin-top: 4px;
            color: #6366f1;
            display: none;
            align-items: center;
            gap: 5px;
        }

        .ai-corrected-hint.show {
            display: flex;
        }

        .ai-corrected-hint .undo-link {
            color: var(--gray-500);
            cursor: pointer;
            text-decoration: underline;
            font-size: 10px;
        }

        .ai-corrected-hint .undo-link:hover {
            color: #EF4444;
        }

        /* Textarea correction button */
        .textarea-ai-wrapper {
            position: relative;
        }

        .textarea-ai-btn {
            position: absolute;
            top: 8px;
            right: 10px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 11px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            padding: 4px 8px;
            cursor: pointer;
            display: none;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
            z-index: 5;
        }

        .textarea-ai-btn.visible {
            display: flex;
        }

        .textarea-ai-btn:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            box-shadow: 0 2px 8px rgba(99,102,241,.4);
        }

        @media (max-width: 768px) {
            .register-card { padding: 32px 24px; }
            .form-grid { grid-template-columns: 1fr; gap: 0; }
            .form-group.full-width { grid-column: span 1; }
            .role-selector { grid-template-columns: 1fr; }
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
            <?php
            $roleLabels = [
                'admin'   => ['icon' => 'bi-shield-lock-fill', 'title' => 'Compte Administrateur', 'sub' => 'Accès complet à la gestion de la plateforme', 'color' => '#6366f1'],
                'patient' => ['icon' => 'bi-person-heart',     'title' => 'Compte Patient',         'sub' => 'Accédez à vos dossiers médicaux & consultations', 'color' => '#1D9E75'],
                'medecin' => ['icon' => 'bi-hospital-fill',    'title' => 'Compte Médecin',         'sub' => 'Gérez vos patients & ordonnances en ligne', 'color' => '#0891b2'],
            ];
            $rl = $roleLabels[$selectedRole];
            ?>
            <div style="width:56px;height:56px;border-radius:16px;background:<?php echo $rl['color']; ?>20;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="bi <?php echo $rl['icon']; ?>" style="font-size:26px;color:<?php echo $rl['color']; ?>;"></i>
            </div>
            <h2><?php echo $rl['title']; ?></h2>
            <p><?php echo $rl['sub']; ?></p>
            <a href="../home/index.php" onclick="history.back();return false;" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#6B7280;text-decoration:none;margin-top:8px;transition:color 0.2s;" onmouseover="this.style.color='#1D9E75'" onmouseout="this.style.color='#6B7280'">
                <i class="bi bi-arrow-left"></i> Changer de type de compte
            </a>
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
            <input type="hidden" name="selected_role" value="<?php echo htmlspecialchars($selectedRole); ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="bi bi-person-fill"></i> Prénom <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" id="prenom" name="prenom" class="form-control" 
                               value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>"
                               placeholder="Votre prénom" required style="padding-right: 90px;">
                        <button type="button" class="ai-correct-btn" id="aiCorrectPrenom" title="Corriger avec l'IA">
                            <i class="bi bi-stars"></i> Corriger
                        </button>
                    </div>
                    <div class="ai-corrected-hint" id="prenomHint">
                        <i class="bi bi-check-circle-fill" style="color:#6366f1"></i>
                        <span>Corrigé par l'IA</span>
                        <span class="undo-link" id="undoPrenom">Annuler</span>
                    </div>
                    <div class="validation-message" id="prenom-error">Le prénom doit contenir au moins 2 lettres</div>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-person-fill"></i> Nom <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>"
                               placeholder="Votre nom" required style="padding-right: 90px;">
                        <button type="button" class="ai-correct-btn" id="aiCorrectNom" title="Corriger avec l'IA">
                            <i class="bi bi-stars"></i> Corriger
                        </button>
                    </div>
                    <div class="ai-corrected-hint" id="nomHint">
                        <i class="bi bi-check-circle-fill" style="color:#6366f1"></i>
                        <span>Corrigé par l'IA</span>
                        <span class="undo-link" id="undoNom">Annuler</span>
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
                    <!-- Bouton IA -->
                    <button type="button" id="aiPasswordBtn" class="ai-suggest-btn">
                        <span class="ai-btn-inner">
                            <i class="bi bi-stars"></i>
                            <span>Suggérer avec l'IA</span>
                            <span class="ai-badge">IA</span>
                        </span>
                    </button>

                    <!-- Panel suggestions IA -->
                    <div class="ai-panel" id="aiPanel">
                        <div class="ai-panel-header">
                            <span><i class="bi bi-stars"></i> Suggestions IA sécurisées</span>
                            <button type="button" class="ai-close" id="aiClose"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <div class="ai-panel-body" id="aiPanelBody">
                            <div class="ai-loading" id="aiLoading">
                                <div class="ai-spinner"></div>
                                <span>Génération en cours...</span>
                            </div>
                            <div id="aiSuggestions" class="ai-suggestions-list"></div>
                        </div>
                        <div class="ai-panel-footer">
                            <button type="button" id="aiRefresh" class="ai-refresh-btn">
                                <i class="bi bi-arrow-clockwise"></i> Regénérer
                            </button>
                        </div>
                    </div>

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
                    <div class="input-wrapper textarea-ai-wrapper">
                        <i class="bi bi-geo-alt input-icon"></i>
                        <textarea id="adresse" name="adresse" class="form-control" 
                                  placeholder="Votre adresse complète" style="padding-right: 90px;"><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?></textarea>
                        <button type="button" class="textarea-ai-btn" id="aiCorrectAdresse" title="Corriger avec l'IA">
                            <i class="bi bi-stars"></i> Corriger
                        </button>
                    </div>
                    <div class="ai-corrected-hint" id="adresseHint">
                        <i class="bi bi-check-circle-fill" style="color:#6366f1"></i>
                        <span>Corrigé par l'IA</span>
                        <span class="undo-link" id="undoAdresse">Annuler</span>
                    </div>
                </div>

                <?php if ($selectedRole === 'medecin'): ?>
                <!-- Médecin-specific fields -->
                <div class="form-group">
                    <label><i class="bi bi-hospital-fill" style="color:#0891b2;"></i> Spécialité <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-clipboard2-pulse input-icon" style="color:#0891b2;"></i>
                        <select name="specialite" id="specialite" class="form-control" required style="padding-left:42px;appearance:none;">
                            <option value="">— Choisir une spécialité —</option>
                            <option value="medecin_general" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='medecin_general') ? 'selected' : ''; ?>>Médecin Généraliste</option>
                            <option value="cardiologue" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='cardiologue') ? 'selected' : ''; ?>>Cardiologue</option>
                            <option value="dermatologue" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='dermatologue') ? 'selected' : ''; ?>>Dermatologue</option>
                            <option value="gynécologue" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='gynécologue') ? 'selected' : ''; ?>>Gynécologue</option>
                            <option value="neurologue" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='neurologue') ? 'selected' : ''; ?>>Neurologue</option>
                            <option value="ophtalmologue" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='ophtalmologue') ? 'selected' : ''; ?>>Ophtalmologue</option>
                            <option value="orthopédiste" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='orthopédiste') ? 'selected' : ''; ?>>Orthopédiste</option>
                            <option value="pédiatre" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='pédiatre') ? 'selected' : ''; ?>>Pédiatre</option>
                            <option value="psychiatre" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='psychiatre') ? 'selected' : ''; ?>>Psychiatre</option>
                            <option value="radiologue" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='radiologue') ? 'selected' : ''; ?>>Radiologue</option>
                            <option value="urgentiste" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='urgentiste') ? 'selected' : ''; ?>>Urgentiste</option>
                            <option value="autre" <?php echo (isset($_POST['specialite']) && $_POST['specialite']==='autre') ? 'selected' : ''; ?>>Autre</option>
                        </select>
                    </div>
                    <div class="validation-message" id="specialite-error">Veuillez choisir votre spécialité</div>
                </div>

                <div class="form-group">
                    <label><i class="bi bi-card-checklist" style="color:#0891b2;"></i> N° Ordre Médical <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="bi bi-hash input-icon" style="color:#0891b2;"></i>
                        <input type="text" id="num_ordre" name="num_ordre" class="form-control"
                               value="<?php echo isset($_POST['num_ordre']) ? htmlspecialchars($_POST['num_ordre']) : ''; ?>"
                               placeholder="Ex: TN-MED-2024-XXXX" required>
                    </div>
                    <div class="validation-message" id="num_ordre-error">Numéro d'ordre invalide</div>
                </div>

                <div class="form-group full-width">
                    <div style="background:#ecfeff;border:1px solid #a5f3fc;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px;">
                        <i class="bi bi-info-circle-fill" style="color:#0891b2;font-size:18px;flex-shrink:0;"></i>
                        <p style="margin:0;font-size:13px;color:#0e7490;">Votre dossier médecin sera vérifié par notre équipe avant activation. Vous recevrez un email de confirmation sous 24h.</p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($selectedRole === 'admin'): ?>
                <!-- Admin info box -->
                <div class="form-group full-width">
                    <div style="background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.25);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px;">
                        <i class="bi bi-shield-lock-fill" style="color:#6366f1;font-size:18px;flex-shrink:0;"></i>
                        <p style="margin:0;font-size:13px;color:#4f46e5;">Les comptes administrateurs ont accès à toutes les fonctions de gestion. La création nécessite une vérification préalable.</p>
                    </div>
                </div>
                <?php endif; ?>

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
    
    // Role radio validation
    // Vérifie le rôle via le champ caché selected_role (pas de radio buttons)
    function validateRole() {
        const hiddenRole = document.querySelector('input[name="selected_role"]');
        const validRoles = ['admin', 'patient', 'medecin'];
        return hiddenRole && validRoles.includes(hiddenRole.value.trim());
    }
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
            
            if (!validateRole()) {
                isValid = false;
            }
            
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
    

    // ═══════════════════════════════════════
    // AI Password Suggester
    // ═══════════════════════════════════════
    const aiPasswordBtn = document.getElementById('aiPasswordBtn');
    const aiPanel = document.getElementById('aiPanel');
    const aiClose = document.getElementById('aiClose');
    const aiRefresh = document.getElementById('aiRefresh');
    const aiLoading = document.getElementById('aiLoading');
    const aiSuggestions = document.getElementById('aiSuggestions');

    async function generateAIPasswords() {
        aiLoading.style.display = 'flex';
        aiSuggestions.innerHTML = '';
        aiPanel.classList.add('visible');

        try {
            const response = await fetch('https://api.anthropic.com/v1/messages', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    model: 'claude-sonnet-4-20250514',
                    max_tokens: 1000,
                    messages: [{
                        role: 'user',
                        content: `Génère exactement 4 mots de passe très sécurisés pour un compte médical. 
Réponds UNIQUEMENT avec un JSON valide, sans texte avant ou après, format exact:
{
  "passwords": [
    { "value": "MotDePasse1!", "label": "Alphanumérique" },
    { "value": "Mot-De-Passe2!", "label": "Avec tirets" },
    { "value": "MotDeP@sse3", "label": "Avec symboles" },
    { "value": "M0t-D3-P@sse!", "label": "Mixte complexe" }
  ]
}
Règles impératives: minimum 12 caractères, majuscules, minuscules, chiffres, symboles. Mots de passe UNIQUES et FORTS.`
                    }]
                })
            });

            const data = await response.json();
            const raw = data.content.map(b => b.text || '').join('');
            const clean = raw.replace(/```json|```/g, '').trim();
            const parsed = JSON.parse(clean);

            aiLoading.style.display = 'none';
            renderSuggestions(parsed.passwords);
        } catch (err) {
            aiLoading.style.display = 'none';
            // Fallback: generate locally if API fails
            const fallback = generateFallbackPasswords();
            renderSuggestions(fallback);
        }
    }

    function generateFallbackPasswords() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        const generate = (len) => Array.from({length: len}, () => chars[Math.floor(Math.random() * chars.length)]).join('');
        return [
            { value: generate(14), label: 'Aléatoire fort' },
            { value: generate(16), label: 'Très sécurisé' },
            { value: generate(12), label: 'Standard sécurisé' },
            { value: generate(18), label: 'Maximum sécurité' }
        ];
    }

    function getStrengthLabel(pw) {
        let score = 0;
        if (pw.length >= 12) score++;
        if (pw.length >= 16) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return score >= 5 ? { label: 'Très fort', cls: 'very-strong' } : { label: 'Fort', cls: 'strong' };
    }

    function renderSuggestions(passwords) {
        aiSuggestions.innerHTML = '';
        passwords.forEach((pw, i) => {
            const strength = getStrengthLabel(pw.value);
            const item = document.createElement('div');
            item.className = 'ai-suggestion-item';
            item.innerHTML = `
                <span class="ai-pw-text">${pw.value}</span>
                <span class="ai-pw-meta">
                    <span class="ai-pw-strength ${strength.cls}">${strength.label}</span>
                    <button type="button" class="ai-use-btn" data-pw="${pw.value}">
                        <i class="bi bi-check2"></i> Utiliser
                    </button>
                </span>
            `;
            item.querySelector('.ai-use-btn').addEventListener('click', function() {
                const pw = this.dataset.pw;
                passwordInput.value = pw;
                confirmInput.value = pw;
                // Show password temporarily
                passwordInput.type = 'text';
                confirmInput.type = 'text';
                setTimeout(() => {
                    passwordInput.type = 'password';
                    confirmInput.type = 'password';
                }, 2000);
                // Trigger strength update
                updatePasswordStrength();
                validateField(passwordInput);
                // Highlight
                document.querySelectorAll('.ai-suggestion-item').forEach(el => el.classList.remove('selected'));
                item.classList.add('selected');
                // Close panel after delay
                setTimeout(() => {
                    aiPanel.classList.remove('visible');
                }, 800);
            });
            aiSuggestions.appendChild(item);
        });

        const tip = document.createElement('p');
        tip.className = 'ai-tip';
        tip.innerHTML = '<i class="bi bi-info-circle"></i> Cliquez "Utiliser" pour remplir automatiquement les deux champs.';
        aiSuggestions.appendChild(tip);
    }

    if (aiPasswordBtn) {
        aiPasswordBtn.addEventListener('click', () => {
            if (aiPanel.classList.contains('visible')) {
                aiPanel.classList.remove('visible');
            } else {
                generateAIPasswords();
            }
        });
    }

    if (aiClose) {
        aiClose.addEventListener('click', () => aiPanel.classList.remove('visible'));
    }

    if (aiRefresh) {
        aiRefresh.addEventListener('click', generateAIPasswords);
    }

    // ═══════════════════════════════════════
    // AI Text Corrector (Prénom, Nom, Adresse)
    // ═══════════════════════════════════════
    const textCorrections = [
        {
            inputId: 'prenom',
            btnId: 'aiCorrectPrenom',
            hintId: 'prenomHint',
            undoId: 'undoPrenom',
            type: 'prénom',
            fieldLabel: 'prénom'
        },
        {
            inputId: 'nom',
            btnId: 'aiCorrectNom',
            hintId: 'nomHint',
            undoId: 'undoNom',
            type: 'nom de famille',
            fieldLabel: 'nom'
        },
        {
            inputId: 'adresse',
            btnId: 'aiCorrectAdresse',
            hintId: 'adresseHint',
            undoId: 'undoAdresse',
            type: 'adresse postale',
            fieldLabel: 'adresse'
        }
    ];

    // Dictionary of common French typos for instant local correction (fallback)
    const commonTypos = {
        'famme': 'femme', 'feme': 'femme', 'hoome': 'homme', 'hom': 'homme',
        'bonjore': 'bonjour', 'bonour': 'bonjour', 'medesin': 'médecin',
        'docter': 'docteur', 'doctuer': 'docteur', 'rue': 'rue',
        'avnue': 'avenue', 'aveune': 'avenue', 'boulevrad': 'boulevard',
        'boluevard': 'boulevard', 'impace': 'impasse', 'cour': 'cour',
        'allé': 'allée', 'allée': 'allée', 'tunsi': 'Tunis', 'tuns': 'Tunis',
        'sfax': 'Sfax', 'sousse': 'Sousse', 'bizrte': 'Bizerte',
        'monahstir': 'Monastir', 'mahida': 'Mahdia'
    };

    function localTypoFix(word) {
        const lower = word.toLowerCase();
        return commonTypos[lower] || null;
    }

    textCorrections.forEach(cfg => {
        const inputEl = document.getElementById(cfg.inputId);
        const btnEl   = document.getElementById(cfg.btnId);
        const hintEl  = document.getElementById(cfg.hintId);
        const undoEl  = document.getElementById(cfg.undoId);
        if (!inputEl || !btnEl) return;

        let previousValue = '';
        let correctionTimer = null;
        let isProcessing = false;
        let wordCorrections = {}; // track corrected words

        // ── Show spinner in btn while correcting ──
        const setBtnLoading = (on) => {
            isProcessing = on;
            if (on) {
                btnEl.classList.add('loading');
                btnEl.innerHTML = '<span class="ai-micro-spinner"></span>';
                btnEl.classList.add('visible');
            } else {
                btnEl.classList.remove('loading');
                btnEl.innerHTML = '<i class="bi bi-stars"></i> Corriger';
            }
        };

        // ── Show/hide manual button ──
        const toggleBtn = () => {
            if (!isProcessing) {
                const val = inputEl.value.trim();
                val.length >= 2 ? btnEl.classList.add('visible') : btnEl.classList.remove('visible');
            }
        };

        // ── Core AI correction call ──
        async function runAICorrection(text) {
            const response = await fetch('spell-proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: cfg.type,
                    messages: [{
                        role: 'user',
                        content: `Tu es un correcteur orthographique automatique pour un formulaire d'inscription médical.
Corrige UNIQUEMENT les fautes d'orthographe dans ce texte (${cfg.type}).
Règles STRICTES:
- Corrige les fautes de frappe et d'orthographe (ex: "famme" → "femme", "docter" → "docteur")
- Mets la première lettre de chaque mot en majuscule pour un prénom/nom
- Ne change pas les mots corrects
- Ne traduis pas, ne reformule pas
- Réponds UNIQUEMENT avec le texte corrigé, sans guillemets, sans explication

Texte: ${text}`
                    }]
                })
            });
            const data = await response.json();
            return data.content?.map(b => b.text || '').join('').trim() || text;
        }

        // ── Real-time: correct word-by-word on Space/Enter ──
        inputEl.addEventListener('keydown', async (e) => {
            if ((e.key === ' ' || e.key === 'Enter') && !isProcessing) {
                const fullText = inputEl.value;
                const words = fullText.trimEnd().split(/\s+/);
                const lastWord = words[words.length - 1];

                if (!lastWord || lastWord.length < 3) return;

                // 1. Try local dictionary first (instant)
                const localFix = localTypoFix(lastWord);
                if (localFix && localFix.toLowerCase() !== lastWord.toLowerCase()) {
                    // Replace last word instantly
                    const cursorPos = inputEl.selectionStart;
                    const before = fullText.substring(0, fullText.lastIndexOf(lastWord));
                    inputEl.value = before + localFix + (e.key === ' ' ? ' ' : '');
                    e.preventDefault();
                    showWordCorrected(lastWord, localFix);
                    return;
                }

                // 2. AI correction for unknown words (debounced, non-blocking)
                clearTimeout(correctionTimer);
                correctionTimer = setTimeout(async () => {
                    if (isProcessing) return;
                    const snapshot = inputEl.value.trim();
                    if (!snapshot || wordCorrections[snapshot]) return;

                    setBtnLoading(true);
                    try {
                        const corrected = await runAICorrection(snapshot);
                        if (corrected && corrected !== snapshot && corrected.length > 0) {
                            previousValue = snapshot;
                            wordCorrections[snapshot] = corrected;
                            // Preserve cursor at end
                            inputEl.value = corrected;
                            if (hintEl) hintEl.classList.add('show');
                            inputEl.classList.add('valid');
                            inputEl.dispatchEvent(new Event('input'));
                        }
                    } catch(err) {
                        // silent fail — no disruption to user
                    } finally {
                        setBtnLoading(false);
                        toggleBtn();
                    }
                }, 600);
            }
        });

        // ── On blur: full correction of the whole field ──
        inputEl.addEventListener('blur', async () => {
            clearTimeout(correctionTimer);
            const val = inputEl.value.trim();
            if (!val || val.length < 2 || isProcessing) return;

            setBtnLoading(true);
            previousValue = val;
            try {
                const corrected = await runAICorrection(val);
                if (corrected && corrected !== val) {
                    wordCorrections[val] = corrected;
                    inputEl.value = corrected;
                    if (hintEl) hintEl.classList.add('show');
                    inputEl.classList.add('valid');
                }
            } catch(err) {
                // silent fail
            } finally {
                setBtnLoading(false);
                toggleBtn();
            }
        });

        // ── Manual button click (also triggers full correction) ──
        btnEl.addEventListener('click', async () => {
            const val = inputEl.value.trim();
            if (!val || isProcessing) return;
            clearTimeout(correctionTimer);
            setBtnLoading(true);
            previousValue = val;
            try {
                const corrected = await runAICorrection(val);
                if (corrected && corrected !== val) {
                    inputEl.value = corrected;
                    if (hintEl) hintEl.classList.add('show');
                    inputEl.classList.add('valid');
                    inputEl.dispatchEvent(new Event('input'));
                }
            } catch(err) {
                // fallback capitalization
                const fixed = val.split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                inputEl.value = fixed;
            } finally {
                setBtnLoading(false);
                toggleBtn();
            }
        });

        inputEl.addEventListener('input', () => {
            if (!isProcessing) {
                toggleBtn();
                if (hintEl) hintEl.classList.remove('show');
            }
        });

        // Show inline correction tooltip briefly
        function showWordCorrected(original, corrected) {
            if (!hintEl) return;
            previousValue = inputEl.value;
            hintEl.querySelector('span:first-of-type') && (hintEl.querySelector('span').textContent = `"${original}" → "${corrected}"`);
            hintEl.classList.add('show');
            setTimeout(() => hintEl.classList.remove('show'), 3000);
        }

        // Undo
        if (undoEl) {
            undoEl.addEventListener('click', () => {
                if (previousValue) {
                    inputEl.value = previousValue;
                    inputEl.classList.remove('valid');
                    hintEl && hintEl.classList.remove('show');
                    wordCorrections = {};
                    inputEl.focus();
                }
            });
        }
    });

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