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

// ── Statistiques du jour ──────────────────────────────────────────────
try {
    $db = config::getConnexion();
    $today = date('Y-m-d');

    // Total créés aujourd'hui
    $stmtTotal = $db->prepare(
        "SELECT COUNT(*) FROM utilisateur WHERE DATE(date_inscription) = :today"
    );
    $stmtTotal->execute([':today' => $today]);
    $stats_today_total = (int) $stmtTotal->fetchColumn();

    // Détail par rôle
    $stmtRoles = $db->prepare(
        "SELECT role, COUNT(*) as cnt
         FROM utilisateur
         WHERE DATE(date_inscription) = :today
         GROUP BY role"
    );
    $stmtRoles->execute([':today' => $today]);
    $stats_by_role = $stmtRoles->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $stats_today_total = null; // DB non dispo : on masque le widget
    $stats_by_role = [];
}

$role_icons = [
    'patient'  => ['icon' => 'bi-heart-pulse-fill', 'color' => '#3B82F6'],
    'medecin'  => ['icon' => 'bi-stethoscope',       'color' => '#8B5CF6'],
    'user'     => ['icon' => 'bi-person-fill',        'color' => '#F59E0B'],
    'admin'    => ['icon' => 'bi-shield-fill',        'color' => '#EF4444'],
];
// ─────────────────────────────────────────────────────────────────────

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
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    
    <style>
        /* ── Stats du jour ── */
        .stats-day-widget {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            max-width: 860px;
            margin-bottom: 28px;
            align-items: stretch;
        }
        .stats-day-main {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            border-radius: var(--radius-lg);
            padding: 22px 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 160px;
            box-shadow: var(--shadow-green);
            position: relative;
            overflow: hidden;
        }
        .stats-day-main::before {
            content: '';
            position: absolute;
            top: -20px; right: -20px;
            width: 90px; height: 90px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }
        .stats-day-main::after {
            content: '';
            position: absolute;
            bottom: -30px; left: -10px;
            width: 70px; height: 70px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .stats-day-main-icon {
            font-size: 22px;
            color: rgba(255,255,255,0.75);
            margin-bottom: 6px;
        }
        .stats-day-main-number {
            font-family: 'Syne', sans-serif;
            font-size: 48px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 4px;
        }
        .stats-day-main-label {
            font-size: 11px;
            color: rgba(255,255,255,0.75);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: center;
        }
        .stats-day-roles-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .stats-role-card {
            background: var(--white);
            border-radius: var(--radius-md);
            border: 1.5px solid rgba(0,0,0,0.06);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stats-role-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stats-role-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .stats-role-info { flex: 1; min-width: 0; }
        .stats-role-name {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--gray-500);
            margin-bottom: 2px;
        }
        .stats-role-count {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 700;
            line-height: 1;
        }
        .stats-role-bar-wrap {
            margin-top: 6px;
            height: 3px;
            border-radius: 99px;
            background: rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .stats-role-bar {
            height: 100%;
            border-radius: 99px;
            background: var(--bar-color);
            transition: width 0.6s cubic-bezier(.4,0,.2,1);
        }
        .stats-day-empty-msg {
            grid-column: 1 / -1;
            text-align: center;
            color: var(--gray-500);
            font-size: 13px;
            font-style: italic;
            padding: 16px;
        }


        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
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
            background: linear-gradient(180deg, var(--navy) 0%, #0F172A 100%);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .dashboard-logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .dashboard-logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
        .dashboard-logo-icon i { font-size: 18px; color: white; }
        .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; }
        .dashboard-logo-text span { color: var(--green); }

        .dashboard-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
        .dashboard-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .dashboard-nav-item i { font-size: 18px; width: 24px; }
        .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }
        .dashboard-nav-item.logout { margin-top: auto; margin-bottom: 20px; color: #F87171; }
        .dashboard-nav-item.logout:hover { background: rgba(248,113,113,0.1); }
        .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }

        .dashboard-main { padding: 40px; overflow-y: auto; }

        .page-header { margin-bottom: 32px; }
        .page-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 700; color: var(--navy); margin-bottom: 6px; }
        .page-header p { color: var(--gray-500); font-size: 14px; }

        .card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(29,158,117,.15);
            max-width: 860px;
        }

        .alert { padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: #F0FDF4; border-left: 4px solid #22C55E; color: #166534; }
        .alert-error { background: #FEF2F2; border-left: 4px solid #EF4444; color: #B91C1C; }
        .alert i { font-size: 18px; }
        .alert-close { margin-left: auto; background: none; border: none; font-size: 20px; cursor: pointer; opacity: 0.6; }

        .form-group { margin-bottom: 24px; }
        .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        label { display: block; margin-bottom: 8px; color: var(--navy); font-weight: 600; font-size: 13px; }
        label i { color: var(--green); margin-right: 6px; }
        .required { color: #EF4444; margin-left: 4px; }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.3s;
            background: white;
        }
        .form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,.15); }
        .form-control.is-invalid { border-color: #EF4444; background: #FEF2F2; }
        .form-control.is-valid { border-color: #22C55E; }

        .invalid-feedback { font-size: 11px; color: #EF4444; margin-top: 5px; display: none; }
        .valid-feedback { font-size: 11px; color: #22C55E; margin-top: 5px; display: none; }
        .is-invalid ~ .invalid-feedback { display: block; }
        .is-valid ~ .valid-feedback { display: block; }

        .btn { padding: 12px 24px; border-radius: var(--radius-md); font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-family: 'DM Sans', sans-serif; }
        .btn-primary { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; box-shadow: 0 3px 12px rgba(29,158,117,.30); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(29,158,117,.40); }
        .btn-outline { background: transparent; border: 2px solid var(--gray-200); color: var(--gray-700); }
        .btn-outline:hover { border-color: var(--green); color: var(--green); }
        .btn-secondary { background: #6B7280; color: white; }
        .btn-secondary:hover { background: #4B5563; transform: translateY(-2px); }

        .form-actions { display: flex; gap: 12px; margin-top: 32px; flex-wrap: wrap; }

        @media (max-width: 900px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .dashboard-sidebar { display: none; }
            .dashboard-main { padding: 24px 16px; }
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .card { padding: 24px; }
            .form-actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
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
            <a href="admin-create-user.php" class="dashboard-nav-item active">
                <i class="bi bi-person-plus-fill"></i> Nouvel utilisateur
            </a>
            <a href="admin-reports-statistics.php" class="dashboard-nav-item">
                <i class="bi bi-graph-up"></i> Statistiques
            </a>
            <div class="dashboard-nav-title">Outils</div>
            <a href="admin-chatbot.php" class="dashboard-nav-item"><i class="bi bi-robot"></i> Assistant IA</a>
            <div class="dashboard-nav-title">Gestion</div>
            <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item">
                <i class="bi bi-person-circle"></i> Mon profil
            </a>
            <a href="../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="dashboard-main">

        <div class="page-header" data-aos="fade-down">
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

        <!-- ── Widget statistiques du jour ── -->
        <?php if ($stats_today_total !== null): ?>
        <?php
        $role_styles = [
            'patient'  => ['icon' => 'bi-heart-pulse-fill', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'text' => '#1D4ED8'],
            'medecin'  => ['icon' => 'bi-stethoscope',       'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'text' => '#6D28D9'],
            'user'     => ['icon' => 'bi-person-fill',        'color' => '#F59E0B', 'bg' => '#FFFBEB', 'text' => '#B45309'],
            'admin'    => ['icon' => 'bi-shield-fill',        'color' => '#EF4444', 'bg' => '#FEF2F2', 'text' => '#B91C1C'],
        ];
        $max_count = max(array_values($stats_by_role) ?: [1]);
        ?>
        <div class="stats-day-widget" data-aos="fade-up">

            <!-- Grille rôles -->
            <div class="stats-day-roles-grid">
                <?php
                $has_any = false;
                foreach ($role_styles as $role => $s):
                    $count = $stats_by_role[$role] ?? 0;
                    $pct   = $stats_today_total > 0 ? round(($count / $max_count) * 100) : 0;
                    $has_any = true;
                ?>
                <div class="stats-role-card">
                    <div class="stats-role-icon" style="background:<?= $s['bg'] ?>; color:<?= $s['color'] ?>">
                        <i class="bi <?= $s['icon'] ?>"></i>
                    </div>
                    <div class="stats-role-info">
                        <div class="stats-role-name"><?= ucfirst($role) ?></div>
                        <div class="stats-role-count" style="color:<?= $count > 0 ? $s['text'] : 'var(--gray-200)' ?>"><?= $count ?></div>
                        <div class="stats-role-bar-wrap">
                            <div class="stats-role-bar" style="width:<?= $pct ?>%; --bar-color:<?= $s['color'] ?>"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Bloc total -->
            <div class="stats-day-main">
                <i class="bi bi-calendar-check-fill stats-day-main-icon"></i>
                <div class="stats-day-main-number"><?= $stats_today_total ?></div>
                <div class="stats-day-main-label">Créé<?= $stats_today_total > 1 ? 's' : '' ?> aujourd'hui</div>
            </div>

        </div>
        <?php endif; ?>

        <div class="card" data-aos="fade-up">
            <form method="POST" id="createUserForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="bi bi-person-fill"></i> Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" class="form-control" value="<?= escape_data($_POST['prenom'] ?? '') ?>" required>
                        <div class="invalid-feedback">Le prénom est requis (2-50 caractères)</div>
                        <div class="valid-feedback">✓ Valide</div>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-person-fill"></i> Nom <span class="required">*</span></label>
                        <input type="text" name="nom" class="form-control" value="<?= escape_data($_POST['nom'] ?? '') ?>" required>
                        <div class="invalid-feedback">Le nom est requis (2-50 caractères)</div>
                        <div class="valid-feedback">✓ Valide</div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="bi bi-envelope-fill"></i> Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= escape_data($_POST['email'] ?? '') ?>" required>
                    <div class="invalid-feedback">Email invalide</div>
                    <div class="valid-feedback">✓ Valide</div>
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
                        <select name="role" id="roleSelect" class="form-control" required>
                            <option value="">Sélectionner un rôle</option>
                            <option value="patient" <?= (($_POST['role'] ?? '') === 'patient') ? 'selected' : '' ?>>Patient</option>
                            <option value="medecin" <?= (($_POST['role'] ?? '') === 'medecin') ? 'selected' : '' ?>>Médecin</option>
                            <option value="user"    <?= (($_POST['role'] ?? '') === 'user')    ? 'selected' : '' ?>>Utilisateur</option>
                            <option value="admin"   <?= (($_POST['role'] ?? '') === 'admin')   ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un rôle</div>
                    </div>
                    <div class="form-group" id="specialiteGroup" style="display: none;">
                        <label><i class="bi bi-stethoscope-fill"></i> Spécialité (pour médecin)</label>
                        <input type="text" name="specialite" class="form-control" value="<?= escape_data($_POST['specialite'] ?? '') ?>" placeholder="ex: Cardiologie, Généraliste...">
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-toggle-on"></i> Statut <span class="required">*</span></label>
                        <select name="statut" class="form-control" required>
                            <option value="">Sélectionner un statut</option>
                            <option value="actif"   <?= (($_POST['statut'] ?? '') === 'actif')   ? 'selected' : '' ?>>Actif</option>
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
                        <div class="valid-feedback">✓ Valide</div>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-lock-fill"></i> Confirmer <span class="required">*</span></label>
                        <input type="password" name="confirm_mot_de_passe" id="confirm_password" class="form-control" required>
                        <div class="invalid-feedback">Les mots de passe ne correspondent pas</div>
                        <div class="valid-feedback">✓ Valide</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Créer l'utilisateur</button>
                    <button type="reset"  class="btn btn-outline"><i class="bi bi-arrow-repeat"></i> Réinitialiser</button>
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

    // Afficher/cacher le champ spécialité selon le rôle sélectionné
    const roleSelect = document.getElementById('roleSelect');
    const specialiteGroup = document.getElementById('specialiteGroup');

    function toggleSpecialite() {
        if (roleSelect.value === 'medecin') {
            specialiteGroup.style.display = 'block';
        } else {
            specialiteGroup.style.display = 'none';
            document.querySelector('input[name="specialite"]').value = '';
        }
    }

    roleSelect.addEventListener('change', toggleSpecialite);
    toggleSpecialite(); // initialisation

    // Validation des mots de passe
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
        if (this.value.length >= 6) { this.classList.remove('is-invalid'); this.classList.add('is-valid'); }
        else { this.classList.remove('is-valid'); this.classList.add('is-invalid'); }
        validatePassword();
    });

    confirmPassword.addEventListener('input', validatePassword);

    form.addEventListener('submit', function(e) {
        let isValid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) { field.classList.add('is-invalid'); isValid = false; }
        });
        if (password.value.length < 6) { password.classList.add('is-invalid'); isValid = false; }
        if (password.value !== confirmPassword.value) { confirmPassword.classList.add('is-invalid'); isValid = false; }
        if (!isValid) { e.preventDefault(); alert('Veuillez corriger les erreurs dans le formulaire.'); }
    });
</script>
</body>
</html>