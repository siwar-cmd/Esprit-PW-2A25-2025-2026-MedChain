<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header('Location: admin-users.php');
    exit;
}

$user = $adminController->getUserById($user_id);
if (!$user) {
    header('Location: admin-users.php');
    exit;
}

$error_message = null;
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $adminController->updateUser($user_id, $_POST);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: admin-users.php');
        exit;
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
    <title>Modifier Utilisateur - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
            --green-deep: #094D3C;
            --green-light: #E8F7F2;
            --green-pale: #F2FBF7;
            --navy: #1E3A52;
            --gray-700: #374151;
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
            padding: 40px 20px;
            position: relative;
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

        .container { max-width: 800px; margin: 0 auto; position: relative; z-index: 2; }
        .card { background: var(--white); border-radius: var(--radius-xl); padding: 40px; box-shadow: var(--shadow-lg); border: 1px solid rgba(29,158,117,.15); transition: transform 0.3s; }
        .card:hover { transform: translateY(-4px); box-shadow: var(--shadow-green); }

        .logo { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 32px; text-decoration: none; }
        .logo-icon { width: 48px; height: 48px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-green); }
        .logo-icon i { font-size: 24px; color: white; }
        .logo-text { font-family: 'Syne', sans-serif; font-size: 24px; font-weight: 800; color: var(--navy); }
        .logo-text span { color: var(--green); }

        .header { text-align: center; margin-bottom: 32px; }
        .header h1 { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 700; color: var(--navy); margin-bottom: 8px; }
        .header p { color: var(--gray-500); font-size: 14px; }

        .user-info-card { background: var(--green-pale); border-radius: var(--radius-md); padding: 20px; margin-bottom: 24px; border-left: 4px solid var(--green); }
        .user-avatar { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--green), var(--green-dark)); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .user-avatar i { font-size: 36px; color: white; }
        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .role-badge.admin { background: #FEF2F2; color: #EF4444; }
        .role-badge.user { background: #F0FDF4; color: #22C55E; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge.actif { background: #F0FDF4; color: #22C55E; }
        .status-badge.inactif { background: #FEF2F2; color: #EF4444; }

        .alert { padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: #F0FDF4; border-left: 4px solid #22C55E; color: #166534; }
        .alert-error { background: #FEF2F2; border-left: 4px solid #EF4444; color: #B91C1C; }
        .alert i { font-size: 18px; }
        .alert-close { margin-left: auto; background: none; border: none; font-size: 20px; cursor: pointer; opacity: 0.6; }

        .form-group { margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; color: var(--navy); font-weight: 600; font-size: 13px; }
        label i { color: var(--green); margin-right: 6px; }
        .required { color: #EF4444; margin-left: 4px; }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,.15); }
        .form-control.is-invalid { border-color: #EF4444; background: #FEF2F2; }

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

        @media (max-width: 768px) {
            .card { padding: 24px; }
            .actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card" data-aos="fade-up">
        <a href="admin-dashboard.php" class="logo">
            <div class="logo-icon"><i class="bi bi-plus-square-fill"></i></div>
            <div class="logo-text">Med<span>Chain</span></div>
        </a>
        
        <div class="header">
            <h1><i class="bi bi-pencil-fill"></i> Modifier l'utilisateur</h1>
            <p>Modifier les informations d'un utilisateur</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= htmlspecialchars($error_message) ?></div>
                <button class="alert-close">&times;</button>
            </div>
        <?php endif; ?>
        
        <div class="user-info-card">
            <div class="user-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div style="text-align: center;">
                <h3><?= escape_data($user['prenom'] . ' ' . $user['nom']) ?></h3>
                <p><i class="bi bi-envelope"></i> <?= escape_data($user['email']) ?></p>
                <div>
                    <span class="role-badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
                    <span class="status-badge <?= $user['statut'] ?>"><?= ucfirst($user['statut']) ?></span>
                </div>
            </div>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="bi bi-person-badge-fill"></i> Rôle <span class="required">*</span></label>
                <select name="role" class="form-control" required>
                    <option value="patient" <?= $user['role'] === 'patient' ? 'selected' : '' ?>>Patient</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="bi bi-toggle-on"></i> Statut <span class="required">*</span></label>
                <select name="statut" class="form-control" required>
                    <option value="actif" <?= $user['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                    <option value="inactif" <?= $user['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                </select>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Enregistrer</button>
                <a href="admin-users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
            </div>
        </form>
    </div>
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
</script>
</body>
</html>