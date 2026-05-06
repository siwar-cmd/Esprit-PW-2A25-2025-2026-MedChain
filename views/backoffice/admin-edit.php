<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';

// Vérification CSRF pour les soumissions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = 'Token de sécurité invalide. Veuillez réessayer.';
        header('Location: admin-users.php');
        exit;
    }
}

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
    // Récupération des données du formulaire
    $data = [
        'nom'          => trim($_POST['nom'] ?? ''),
        'prenom'       => trim($_POST['prenom'] ?? ''),
        'email'        => trim($_POST['email'] ?? ''),
        'role'         => $_POST['role'] ?? '',
        'statut'       => $_POST['statut'] ?? '',
        'telephone'    => trim($_POST['telephone'] ?? ''),
        'specialite'   => trim($_POST['specialite'] ?? ''),
        'adresse'      => trim($_POST['adresse'] ?? ''),
        'dateNaissance'=> trim($_POST['dateNaissance'] ?? '')
    ];
    
    // Validation simple
    if (empty($data['nom']) || empty($data['prenom']) || empty($data['email']) || empty($data['role']) || empty($data['statut'])) {
        $error_message = "Tous les champs obligatoires doivent être remplis.";
    } else {
        $result = $adminController->updateUser($user_id, $data);
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'admin-users.php'));
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}

// Générer un token CSRF pour le formulaire
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un utilisateur - MedChain Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;
            --gray-700:#374151;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#fff;
            --shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 16px rgba(0,0,0,.08);
            --radius-md:12px;--radius-lg:20px;--radius-xl:28px;
        }
        body{font-family:'DM Sans',sans-serif;background:linear-gradient(145deg,#f0faf6,#e8f7f1,#ddf3ea);min-height:100vh;overflow-x:hidden}
        
        /* DASHBOARD LAYOUT */
        .dashboard-container{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
        
        /* SIDEBAR */
        .dashboard-sidebar{background:linear-gradient(180deg,var(--navy) 0%,#0F172A 100%);position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto}
        .dashboard-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:20px}
        .dashboard-logo a{display:flex;align-items:center;gap:10px;text-decoration:none}
        .dashboard-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center}
        .dashboard-logo-icon i{font-size:18px;color:#fff}
        .dashboard-logo-text{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#fff}
        .dashboard-logo-text span{color:var(--green)}
        .dashboard-nav{flex:1;display:flex;flex-direction:column;gap:4px;padding:0 12px}
        .dashboard-nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#94A3B8;text-decoration:none;border-radius:var(--radius-md);transition:all .3s;font-size:14px;font-weight:500}
        .dashboard-nav-item i{font-size:18px;width:24px}
        .dashboard-nav-item:hover{background:rgba(255,255,255,.1);color:#fff}
        .dashboard-nav-item.active{background:rgba(29,158,117,.2);color:var(--green)}
        .dashboard-nav-item.logout{margin-top:auto;margin-bottom:20px;color:#F87171}
        .dashboard-nav-item.logout:hover{background:rgba(248,113,113,.1)}
        .dashboard-nav-title{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748B;padding:16px 16px 8px;font-weight:600}
        
        /* MAIN */
        .dashboard-main{padding:32px 40px;overflow-y:auto}
        .container{max-width:900px;margin:0 auto}
        .card{background:#fff;border-radius:var(--radius-xl);padding:32px;box-shadow:var(--shadow-md);border:1px solid var(--gray-200)}
        .header{text-align:center;margin-bottom:28px}
        .header h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:var(--navy);margin-bottom:8px}
        .header p{color:var(--gray-500);font-size:14px}
        .alert{padding:14px 18px;border-radius:var(--radius-md);margin-bottom:24px;display:flex;align-items:center;gap:12px}
        .alert-success{background:#F0FDF4;border-left:4px solid #22C55E;color:#166534}
        .alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C}
        .alert-close{margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer}
        .form-group{margin-bottom:24px}
        label{display:block;margin-bottom:8px;color:var(--navy);font-weight:600;font-size:13px}
        label i{color:var(--green);margin-right:6px}
        .required{color:#EF4444;margin-left:4px}
        .form-control, .form-select{
            width:100%;padding:12px 16px;border:2px solid var(--gray-200);border-radius:var(--radius-md);
            font-size:14px;transition:all 0.3s;background:#fff
        }
        .form-control:focus, .form-select:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px rgba(29,158,117,.15)}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
        .actions{display:flex;gap:12px;margin-top:32px;flex-wrap:wrap}
        .btn{padding:12px 24px;border-radius:var(--radius-md);font-size:14px;font-weight:600;cursor:pointer;transition:all 0.3s;border:none;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .btn-primary{background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff;box-shadow:0 3px 12px rgba(29,158,117,.30)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(29,158,117,.40)}
        .btn-secondary{background:#6B7280;color:#fff}
        .btn-secondary:hover{background:#4B5563;transform:translateY(-2px)}
        .btn-outline{background:transparent;border:2px solid var(--gray-200);color:var(--gray-700)}
        .btn-outline:hover{border-color:var(--green);color:var(--green)}
        
        @media(max-width:900px){
            .dashboard-container{grid-template-columns:1fr}
            .dashboard-sidebar{display:none}
            .dashboard-main{padding:20px 16px}
        }
        @media(max-width:600px){
            .row{grid-template-columns:1fr;gap:0}
            .card{padding:24px}
            .actions{flex-direction:column}
            .btn{width:100%;justify-content:center}
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-logo">
            <a href="admin-dashboard.php">
                <div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
                <div class="dashboard-logo-text">Med<span>Chain</span></div>
            </a>
        </div>
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="admin-dashboard.php" class="dashboard-nav-item">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="admin-users.php" class="dashboard-nav-item">
                <i class="bi bi-people-fill"></i> Utilisateurs
            </a>
            <a href="admin-create-user.php" class="dashboard-nav-item">
                <i class="bi bi-person-plus-fill"></i> Nouvel utilisateur
            </a>
            <a href="admin-reports-statistics.php" class="dashboard-nav-item">
                <i class="bi bi-graph-up"></i> Statistiques
            </a>
            <div class="dashboard-nav-title">Médical</div>
            <a href="admin-medecin.php" class="dashboard-nav-item">
                <i class="bi bi-heart-pulse-fill"></i> Médecins
            </a>
            <a href="admin-patient.php" class="dashboard-nav-item">
                <i class="bi bi-person-lines-fill"></i> Patients
            </a>
            <a href="admin-medical-dashboard.php" class="dashboard-nav-item">
                <i class="bi bi-activity"></i> Suivi santé avancé
            </a>
            <div class="dashboard-nav-title">Outils</div>
            <a href="admin-chatbot.php" class="dashboard-nav-item">
                <i class="bi bi-robot"></i> Assistant IA
            </a>
            <div class="dashboard-nav-title">Gestion</div>
            <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item">
                <i class="bi bi-person-circle"></i> Mon profil
            </a>
            <a href="../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
        <div class="container">
            <div class="card">
                <div class="header">
                    <h1><i class="bi bi-pencil-fill"></i> Modifier un utilisateur</h1>
                    <p>Modifier les informations de <?= e($user['prenom'] . ' ' . $user['nom']) ?></p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?= e($error_message) ?></div>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                    <div class="row">
                        <div class="form-group">
                            <label><i class="bi bi-person-fill"></i> Prénom <span class="required">*</span></label>
                            <input type="text" name="prenom" class="form-control" value="<?= e($user['prenom']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-person-fill"></i> Nom <span class="required">*</span></label>
                            <input type="text" name="nom" class="form-control" value="<?= e($user['nom']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="bi bi-envelope-fill"></i> Email <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label><i class="bi bi-telephone-fill"></i> Téléphone</label>
                            <input type="text" name="telephone" class="form-control" value="<?= e($user['telephone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-calendar-fill"></i> Date de naissance</label>
                            <input type="date" name="dateNaissance" class="form-control" value="<?= e($user['dateNaissance'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="bi bi-geo-alt-fill"></i> Adresse</label>
                        <textarea name="adresse" class="form-control" rows="2"><?= e($user['adresse'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label><i class="bi bi-person-badge-fill"></i> Rôle <span class="required">*</span></label>
                            <select name="role" id="roleSelect" class="form-select" required>
                                <option value="admin"   <?= $user['role'] === 'admin'   ? 'selected' : '' ?>>Administrateur</option>
                                <option value="medecin" <?= $user['role'] === 'medecin' ? 'selected' : '' ?>>Médecin</option>
                                <option value="patient" <?= $user['role'] === 'patient' ? 'selected' : '' ?>>Patient</option>
                                <option value="user"    <?= $user['role'] === 'user'    ? 'selected' : '' ?>>Utilisateur standard</option>
                            </select>
                        </div>

                        <div class="form-group" id="specialiteGroup" style="display: <?= ($user['role'] === 'medecin') ? 'block' : 'none' ?>;">
                            <label><i class="bi bi-stethoscope-fill"></i> Spécialité (médecin)</label>
                            <input type="text" name="specialite" class="form-control" value="<?= e($user['specialite'] ?? '') ?>" placeholder="ex: Cardiologie, Généraliste...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="bi bi-toggle-on"></i> Statut <span class="required">*</span></label>
                        <select name="statut" class="form-select" required>
                            <option value="actif"   <?= $user['statut'] === 'actif'   ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif" <?= $user['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                        </select>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Enregistrer</button>
                        <button type="reset" class="btn btn-outline"><i class="bi bi-arrow-repeat"></i> Réinitialiser</button>
                        <a href="admin-users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour à la liste</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    // Gestion de l'affichage du champ spécialité selon le rôle sélectionné
    const roleSelect = document.getElementById('roleSelect');
    const specialiteGroup = document.getElementById('specialiteGroup');

    function toggleSpecialite() {
        if (roleSelect.value === 'medecin') {
            specialiteGroup.style.display = 'block';
        } else {
            specialiteGroup.style.display = 'none';
        }
    }

    roleSelect.addEventListener('change', toggleSpecialite);
    toggleSpecialite();

    // Fermeture des alertes
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