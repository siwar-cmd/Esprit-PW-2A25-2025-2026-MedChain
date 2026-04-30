<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();
$usersResult = $adminController->getAllUsers();
$users = $usersResult['success'] ? $usersResult['users'] : [];

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
            --navy: #1E3A52;
            --gray-500: #6B7280;
            --gray-200: #E5E7EB;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --radius-md: 12px;
            --radius-lg: 20px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container { max-width: 1200px; margin: 0 auto; }
        
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(29,158,117,.15);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--navy);
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(29,158,117,.3); }

        .btn-edit { background: #3B82F6; color: white; }
        .btn-edit:hover { background: #2563EB; transform: translateY(-2px); }

        .btn-delete { background: #EF4444; color: white; }
        .btn-delete:hover { background: #DC2626; transform: translateY(-2px); }

        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success { background: #F0FDF4; border-left: 4px solid #22C55E; color: #166534; }
        .alert-error { background: #FEF2F2; border-left: 4px solid #EF4444; color: #B91C1C; }
        .alert-close { margin-left: auto; background: none; border: none; font-size: 20px; cursor: pointer; opacity: 0.6; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--gray-200); }
        th { background: #F8FAFC; font-weight: 600; color: var(--navy); }
        tr:hover { background: #F8FAFC; }

        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .role-badge.admin { background: #FEF2F2; color: #EF4444; }
        .role-badge.user { background: #F0FDF4; color: #22C55E; }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge.actif { background: #F0FDF4; color: #22C55E; }
        .status-badge.inactif { background: #FEF2F2; color: #EF4444; }

        .actions { display: flex; gap: 8px; }
        .footer-actions { margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap; }

        @media (max-width: 768px) {
            .card { padding: 20px; }
            table { font-size: 13px; }
            th, td { padding: 8px; }
            .actions { flex-direction: column; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <h1><i class="bi bi-people-fill"></i> Gestion des utilisateurs</h1>
            <a href="admin-create-user.php" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Nouvel utilisateur</a>
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
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Date d'inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">Aucun utilisateur trouvé</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id_utilisateur'] ?></td>
                            <td><?= htmlspecialchars($user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['prenom']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><span class="role-badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                            <td><span class="status-badge <?= $user['statut'] ?>"><?= ucfirst($user['statut']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                            <td class="actions">
                                <a href="admin-edit.php?id=<?= $user['id_utilisateur'] ?>" class="btn btn-edit"><i class="bi bi-pencil-fill"></i> Modifier</a>
                                <form method="POST" action="admin-delete.php" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    <input type="hidden" name="user_id" value="<?= $user['id_utilisateur'] ?>">
                                    <button type="submit" class="btn btn-delete"><i class="bi bi-trash-fill"></i> Supprimer</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer-actions">
            <a href="admin-dashboard.php" class="btn btn-primary"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="admin-create-user.php" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Nouvel utilisateur</a>
        </div>
    </div>
</div>

<script>
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