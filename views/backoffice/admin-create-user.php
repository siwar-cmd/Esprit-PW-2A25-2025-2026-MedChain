<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();

$error_message = null;
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_data = array_map(function($value) {
        if (is_string($value)) {
            $value = strip_tags($value);
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $value = trim($value);
        }
        return $value;
    }, $_POST);
    
    $result = $adminController->createUser($post_data);
    
    if ($result['success']) {
        $success_message = $result['message'];
        $_POST = [];
    } else {
        $error_message = $result['message'];
    }
}

function escape_data($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Utilisateur - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
            --green-deep: #094D3C;
            --green-light: #E8F7F2;
            --navy: #1E3A52;
            --gray-500: #6B7280;
            --gray-200: #E5E7EB;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --shadow-lg: 0 12px 40px rgba(0,0,0,.10);
            --shadow-green: 0 8px 30px rgba(29,158,117,.22);
            --radius-md: 12px;
            --radius-lg: 20px;
            --radius-xl: 28px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
            position: relative;
            z-index: 2;
        }

        .dashboard-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .dashboard-logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .dashboard-logo-icon { width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
        .dashboard-logo-icon i { font-size: 18px; color: white; }
        .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; }
        .dashboard-logo-text span { color: #3b82f6; }

        .dashboard-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
        .dashboard-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .dashboard-nav-item i { font-size: 18px; width: 24px; }
        .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .dashboard-nav-item.active { background: rgba(59,130,246,0.2); color: #3b82f6; }
        .dashboard-nav-item.logout { margin-top: auto; margin-bottom: 20px; color: #F87171; }
        .dashboard-nav-item.logout:hover { background: rgba(248,113,113,0.1); }
        .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }

        .dashboard-main { padding: 32px 40px; overflow-y: auto; }

        .card { background: var(--white); border-radius: var(--radius-xl); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); padding: 40px; max-width: 900px; margin: 0 auto; }
        
        .header { text-align: center; margin-bottom: 32px; }
        .header h1 { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 700; color: var(--navy); margin-bottom: 8px; }
        .header p { color: var(--gray-500); font-size: 14px; }

        .alert { padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: #F0FDF4; border-left: 4px solid #22C55E; color: #166534; }
        .alert-error { background: #FEF2F2; border-left: 4px solid #EF4444; color: #B91C1C; }
        .alert-close { margin-left: auto; background: none; border: none; font-size: 20px; cursor: pointer; opacity: 0.6; }

        .form-group { margin-bottom: 24px; }
        .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        label { display: block; margin-bottom: 8px; color: var(--navy); font-weight: 600; font-size: 13px; }
        label i { color: var(--green); margin-right: 6px; }
        .required { color: #EF4444; margin-left: 4px; }

        .form-control { width: 100%; padding: 12px 16px; border: 2px solid var(--gray-200); border-radius: var(--radius-md); font-size: 14px; transition: all 0.3s; }
        .form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,.15); }
        .form-control.is-invalid { border-color: #EF4444; background: #FEF2F2; }
        .form-control.is-valid { border-color: #22C55E; background: #F0FDF4; }

        .invalid-feedback { font-size: 11px; color: #EF4444; margin-top: 5px; display: none; }
        .is-invalid ~ .invalid-feedback { display: block; }

        .btn { padding: 12px 24px; border-radius: var(--radius-md); font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; box-shadow: 0 3px 12px rgba(29,158,117,.30); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(29,158,117,.40); }
        .btn-outline { background: transparent; border: 2px solid var(--gray-200); color: var(--gray-700); }
        .btn-outline:hover { border-color: var(--green); color: var(--green); }
        .btn-secondary { background: #6B7280; color: white; }
        .btn-secondary:hover { background: #4B5563; transform: translateY(-2px); }

        .actions { display: flex; gap: 12px; margin-top: 32px; flex-wrap: wrap; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <aside class="dashboard-sidebar" id="sidebar">
        <div class="dashboard-logo">
            <a href="admin-dashboard.php">
                <div class="dashboard-logo-icon"><i class="fas fa-hospital-alt"></i></div>
                <div class="dashboard-logo-text">Med<span>Chain</span></div>
            </a>
        </div>
        
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="admin-dashboard.php" class="dashboard-nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="admin-users.php" class="dashboard-nav-item">
                <i class="fas fa-users"></i> Utilisateurs
            </a>
            <a href="admin-create-user.php" class="dashboard-nav-item active">
                <i class="fas fa-user-plus"></i> Nouvel utilisateur
            </a>
            <a href="rendezvous/admin-index.php" class="dashboard-nav-item">
                <i class="fas fa-calendar-check"></i> Rendez-vous
            </a>
            <a href="ficherdv/admin-index.php" class="dashboard-nav-item">
                <i class="fas fa-file-medical-alt"></i> Fiches Médicales
            </a>
            <a href="admin-reports-statistics.php" class="dashboard-nav-item">
                <i class="fas fa-chart-pie"></i> Statistiques
            </a>
            
            <div class="dashboard-nav-title">Personnel</div>
            <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item">
                <i class="fas fa-user-circle"></i> Mon profil
            </a>
            <a href="../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirmLogout(event)">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="card" data-aos="fade-up" data-aos-duration="600">
            <div class="header">
                <h1><i class="bi bi-person-plus-fill"></i> Créer un utilisateur</h1>
                <p>Ajouter un nouvel utilisateur à la plateforme</p>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><?= htmlspecialchars($success_message) ?></div>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= htmlspecialchars($error_message) ?></div>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="createUserForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="bi bi-person-fill"></i> Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" class="form-control" value="<?= escape_data($_POST['prenom'] ?? '') ?>" required>
                        <div class="invalid-feedback">Le prénom est requis (2-50 caractères)</div>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-person-fill"></i> Nom <span class="required">*</span></label>
                        <input type="text" name="nom" class="form-control" value="<?= escape_data($_POST['nom'] ?? '') ?>" required>
                        <div class="invalid-feedback">Le nom est requis (2-50 caractères)</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-envelope-fill"></i> Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= escape_data($_POST['email'] ?? '') ?>" required>
                    <div class="invalid-feedback">Email invalide</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="bi bi-calendar-fill"></i> Date de naissance</label>
                        <input type="date" name="dateNaissance" class="form-control" value="<?= escape_data($_POST['dateNaissance'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-geo-alt-fill"></i> Adresse</label>
                        <input type="text" name="adresse" class="form-control" value="<?= escape_data($_POST['adresse'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="bi bi-person-badge-fill"></i> Rôle <span class="required">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="">Sélectionner un rôle</option>
                            <option value="patient" <?= (($_POST['role'] ?? '') === 'patient') ? 'selected' : '' ?>>Patient</option>
                            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un rôle</div>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-toggle-on"></i> Statut <span class="required">*</span></label>
                        <select name="statut" class="form-control" required>
                            <option value="">Sélectionner un statut</option>
                            <option value="actif" <?= (($_POST['statut'] ?? '') === 'actif') ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif" <?= (($_POST['statut'] ?? '') === 'inactif') ? 'selected' : '' ?>>Inactif</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un statut</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="bi bi-lock-fill"></i> Mot de passe <span class="required">*</span></label>
                        <input type="password" name="mot_de_passe" id="password" class="form-control" required>
                        <div class="invalid-feedback">Le mot de passe doit contenir au moins 6 caractères</div>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-lock-fill"></i> Confirmer <span class="required">*</span></label>
                        <input type="password" name="confirm_mot_de_passe" id="confirm_password" class="form-control" required>
                        <div class="invalid-feedback">Les mots de passe ne correspondent pas</div>
                    </div>
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Créer l'utilisateur</button>
                    <button type="reset" class="btn btn-outline"><i class="bi bi-arrow-repeat"></i> Réinitialiser</button>
                    <a href="admin-users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 20 });
    
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.alert').remove());
    });
    
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
    
    const form = document.getElementById('createUserForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.classList.add('is-invalid');
            confirmPassword.classList.remove('is-valid');
            return false;
        } else if (password.value.length >= 6) {
            confirmPassword.classList.remove('is-invalid');
            confirmPassword.classList.add('is-valid');
            return true;
        }
        return false;
    }
    
    password.addEventListener('input', function() {
        if (this.value.length >= 6) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
        validatePassword();
    });
    
    confirmPassword.addEventListener('input', validatePassword);
    
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const required = form.querySelectorAll('[required]');
            required.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            if (password.value.length < 6) {
                password.classList.add('is-invalid');
                isValid = false;
            }
            
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Veuillez corriger les erreurs dans le formulaire.');
            }
        });
    }
</script>
</body>
</html>