<?php
session_start();

$role = isset($_GET['role']) ? $_GET['role'] : 'client';
$error = '';

// Credentials
$credentials = [
    'admin'  => ['email' => 'admin@gmail.com',  'password' => 'admin123'],
    'client' => ['email' => 'client@gmail.com', 'password' => 'client123'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? 'client');

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!isset($credentials[$role])) {
        $error = 'Rôle invalide.';
    } elseif ($email !== $credentials[$role]['email'] || $password !== $credentials[$role]['password']) {
        $error = 'Email ou mot de passe incorrect.';
    } else {
        // Success — redirect based on role
        $_SESSION['role']  = $role;
        $_SESSION['email'] = $email;

        if ($role === 'admin') {
            // Admin → other project
            header('Location: /projet%20web2%20-%20Copie/projet%20web2%20-%20Copie/projet%20web2%20-%20Copie/index.php');
        } else {
            // Client → current project
            header('Location: index.php');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedChain | Connexion <?= ucfirst(htmlspecialchars($role)) ?></title>
    <meta name="description" content="Connexion à la plateforme MedChain">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="home.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="login-body">

    <!-- Animated background -->
    <div class="bg-particles">
        <span></span><span></span><span></span>
        <span></span><span></span><span></span>
    </div>

    <!-- Back button -->
    <a href="home.php" class="login-back-btn" id="btn-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour
    </a>

    <div class="login-wrapper">
        <!-- Left panel -->
        <div class="login-panel login-panel-left <?= $role === 'admin' ? 'panel-admin' : 'panel-client' ?>">
            <div class="login-panel-content">
                <div class="login-panel-icon">
                    <?php if ($role === 'admin'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?php endif; ?>
                </div>
                <h2>
                    <?= $role === 'admin' ? 'Espace Administrateur' : 'Espace Client' ?>
                </h2>
                <p>
                    <?= $role === 'admin'
                        ? 'Accédez au tableau de bord complet de gestion médicale, des ambulances aux distributions.'
                        : 'Consultez vos rendez-vous, vos ordonnances et votre dossier médical en toute sécurité.' ?>
                </p>
                <div class="login-panel-features">
                    <?php if ($role === 'admin'): ?>
                    <div class="login-panel-feature"><span>✓</span> Gestion des ambulances</div>
                    <div class="login-panel-feature"><span>✓</span> Suivi des interventions</div>
                    <div class="login-panel-feature"><span>✓</span> Rapports & statistiques</div>
                    <?php else: ?>
                    <div class="login-panel-feature"><span>✓</span> Vos rendez-vous</div>
                    <div class="login-panel-feature"><span>✓</span> Votre dossier médical</div>
                    <div class="login-panel-feature"><span>✓</span> Suivi post-opératoire</div>
                    <?php endif; ?>
                </div>
                <img src="logo.PNG" alt="MedChain" class="login-panel-logo">
            </div>
        </div>

        <!-- Right panel — form -->
        <div class="login-panel login-panel-right">
            <div class="login-form-wrapper">
                <div class="login-role-badge <?= $role === 'admin' ? 'badge-admin' : 'badge-client' ?>">
                    <?= $role === 'admin' ? '🔐 Administrateur' : '👤 Client' ?>
                </div>
                <h1 class="login-form-title">Connexion</h1>
                <p class="login-form-subtitle">Entrez vos identifiants pour accéder à votre espace.</p>

                <?php if ($error): ?>
                <div class="login-error" id="login-error-msg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="login-form" id="loginForm" novalidate>
                    <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

                    <div class="login-field" id="field-email">
                        <label for="email">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            Adresse Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="votre@email.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            autocomplete="email"
                        >
                        <span class="field-error" id="err-email"></span>
                    </div>

                    <div class="login-field" id="field-password">
                        <label for="password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Mot de passe
                        </label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" id="togglePass" aria-label="Afficher le mot de passe">
                                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <span class="field-error" id="err-password"></span>
                    </div>

                    <button type="submit" class="login-submit <?= $role === 'admin' ? 'submit-admin' : 'submit-client' ?>" id="submitBtn">
                        <span class="btn-text">Se connecter</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>
                </form>

                <p class="login-switch">
                    Vous êtes <?= $role === 'admin' ? 'un client' : 'un administrateur' ?> ?
                    <a href="login.php?role=<?= $role === 'admin' ? 'client' : 'admin' ?>" id="link-switch-role">
                        Accédez à l'espace <?= $role === 'admin' ? 'client' : 'admin' ?>
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const toggleBtn = document.getElementById('togglePass');
        const passInput = document.getElementById('password');
        toggleBtn.addEventListener('click', () => {
            const show = passInput.type === 'password';
            passInput.type = show ? 'text' : 'password';
            toggleBtn.querySelector('.eye-icon').style.display    = show ? 'none'  : '';
            toggleBtn.querySelector('.eye-off-icon').style.display = show ? ''     : 'none';
        });

        // Client-side validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let valid = true;
            const email    = document.getElementById('email');
            const password = document.getElementById('password');
            const errEmail = document.getElementById('err-email');
            const errPass  = document.getElementById('err-password');

            errEmail.textContent = '';
            errPass.textContent  = '';
            document.getElementById('field-email').classList.remove('field-invalid');
            document.getElementById('field-password').classList.remove('field-invalid');

            if (!email.value.trim()) {
                errEmail.textContent = 'L\'email est requis.';
                document.getElementById('field-email').classList.add('field-invalid');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                errEmail.textContent = 'Format d\'email invalide.';
                document.getElementById('field-email').classList.add('field-invalid');
                valid = false;
            }

            if (!password.value.trim()) {
                errPass.textContent = 'Le mot de passe est requis.';
                document.getElementById('field-password').classList.add('field-invalid');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    </script>
</body>
</html>
