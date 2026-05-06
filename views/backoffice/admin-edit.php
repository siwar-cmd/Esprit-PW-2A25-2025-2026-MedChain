<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/login.php');
    exit;
}

if (!defined('BASE_PATH'))    define('BASE_PATH', dirname(__DIR__, 2));
if (!defined('APP_ENTRY_URL')) define('APP_ENTRY_URL', '/midchaine/index1.php');
require_once BASE_PATH . '/models/config.php';
require_once BASE_PATH . '/models/Database.php';
require_once BASE_PATH . '/controllers/AdminController.php';

$adminController = new AdminController();

$user_id = $_GET['id'] ?? null;
if (!$user_id) { header('Location: admin-users.php'); exit; }

$user = $adminController->getUserById($user_id);
if (!$user) { header('Location: admin-users.php'); exit; }

$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $adminController->updateUser($user_id, $_POST);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: admin-users.php');
        exit;
    }
    $error_message = $result['message'];
}

function escape_data($data) { return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Utilisateur — MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F2FBF7;
            --navy: #1E3A52; --gray-700: #374151; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-lg: 0 12px 40px rgba(0,0,0,.10);
            --shadow-green: 0 8px 30px rgba(29,158,117,.22);
            --radius-md: 12px; --radius-lg: 20px; --radius-xl: 28px;
        }
        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
    </style>
    <?php require __DIR__ . '/_sidebar_css.php'; ?>
</head>
<body>

<div class="dashboard-container">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <main class="dashboard-main">
        <div style="max-width:720px;">

            <div style="margin-bottom:24px;">
                <h1 style="font-family:'Syne',sans-serif;font-size:24px;font-weight:700;color:var(--navy);">
                    <i class="bi bi-pencil-fill" style="color:var(--green);"></i> Modifier l'utilisateur
                </h1>
                <p style="color:var(--gray-500);font-size:14px;margin-top:4px;">Modifier le rôle et le statut d'un utilisateur.</p>
            </div>

            <?php if ($error_message): ?>
                <div style="background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C;padding:13px 18px;border-radius:var(--radius-md);margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- User info card -->
            <div style="background:var(--green-pale);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;border:1px solid rgba(29,158,117,.2);display:flex;align-items:center;gap:20px;">
                <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-person-fill" style="font-size:28px;color:white;"></i>
                </div>
                <div>
                    <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--navy);">
                        <?= escape_data($user['prenom'] . ' ' . $user['nom']) ?>
                    </div>
                    <div style="font-size:13px;color:var(--gray-500);margin-top:2px;">
                        <i class="bi bi-envelope"></i> <?= escape_data($user['email']) ?>
                    </div>
                    <div style="margin-top:8px;display:flex;gap:8px;">
                        <span style="padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#FEF2F2;color:#EF4444;">
                            <?= ucfirst($user['role']) ?>
                        </span>
                        <span style="padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;background:<?= $user['statut']==='actif' ? '#F0FDF4' : '#FEF2F2' ?>;color:<?= $user['statut']==='actif' ? '#16A34A' : '#DC2626' ?>;">
                            <?= ucfirst($user['statut']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Edit form -->
            <div style="background:var(--white);border-radius:var(--radius-xl);border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);padding:28px;">
                <form method="POST">
                    <div style="margin-bottom:20px;">
                        <label style="display:block;font-size:13.5px;font-weight:600;color:var(--navy);margin-bottom:6px;">
                            <i class="bi bi-person-badge-fill" style="color:var(--green);"></i> Rôle *
                        </label>
                        <select name="role" style="width:100%;padding:10px 14px;border:1.5px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:inherit;color:var(--navy);" required>
                            <option value="patient"  <?= $user['role']==='patient'  ? 'selected' : '' ?>>Patient</option>
                            <option value="medecin"  <?= $user['role']==='medecin'  ? 'selected' : '' ?>>Médecin</option>
                            <option value="admin"    <?= $user['role']==='admin'    ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                    </div>

                    <div style="margin-bottom:28px;">
                        <label style="display:block;font-size:13.5px;font-weight:600;color:var(--navy);margin-bottom:6px;">
                            <i class="bi bi-toggle-on" style="color:var(--green);"></i> Statut *
                        </label>
                        <select name="statut" style="width:100%;padding:10px 14px;border:1.5px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:inherit;color:var(--navy);" required>
                            <option value="actif"      <?= $user['statut']==='actif'      ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif"    <?= $user['statut']==='inactif'    ? 'selected' : '' ?>>Inactif</option>
                            <option value="en_attente" <?= $user['statut']==='en_attente' ? 'selected' : '' ?>>En attente</option>
                        </select>
                    </div>

                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <button type="submit" style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;border:none;border-radius:var(--radius-md);font-size:14px;font-weight:600;cursor:pointer;">
                            <i class="bi bi-floppy-fill"></i> Enregistrer
                        </button>
                        <a href="admin-users.php" style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#F1F5F9;color:var(--navy);border-radius:var(--radius-md);font-size:14px;font-weight:600;">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

</body>
</html>
