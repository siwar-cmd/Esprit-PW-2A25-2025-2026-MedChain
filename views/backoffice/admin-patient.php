<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();

// Récupération de tous les utilisateurs
$usersResult = $adminController->getAllUsers();
$allUsers = $usersResult['success'] ? $usersResult['users'] : [];

// Récupération des filtres depuis l'URL
$search = trim($_GET['search'] ?? '');
$statut_filter = $_GET['statut'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'date_inscription';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Filtrage manuel : on ne garde que les patients
$patients = array_filter($allUsers, function($user) use ($search, $statut_filter) {
    // Ne garder que le rôle 'patient'
    if (($user['role'] ?? '') !== 'patient') return false;
    
    // Filtre par statut
    if ($statut_filter !== '' && ($user['statut'] ?? '') !== $statut_filter) return false;
    
    // Filtre par recherche (nom, prénom, email, téléphone, date de naissance)
    if ($search !== '') {
        $searchLower = strtolower($search);
        $nomPrenom = strtolower(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
        $email = strtolower($user['email'] ?? '');
        $telephone = strtolower($user['telephone'] ?? '');
        $dateNaissance = strtolower($user['date_naissance'] ?? '');
        $adresse = strtolower($user['adresse'] ?? '');
        
        // Formatage de la date de naissance pour la recherche
        $dateNaissanceFormatted = '';
        if (!empty($user['date_naissance'])) {
            $dateNaissanceFormatted = strtolower(date('d/m/Y', strtotime($user['date_naissance'])));
        }
        
        if (strpos($nomPrenom, $searchLower) === false && 
            strpos($email, $searchLower) === false &&
            strpos($telephone, $searchLower) === false &&
            strpos($dateNaissance, $searchLower) === false &&
            strpos($dateNaissanceFormatted, $searchLower) === false &&
            strpos($adresse, $searchLower) === false) {
            return false;
        }
    }
    return true;
});
$patients = array_values($patients); // ré-indexation

// Tri des patients
usort($patients, function($a, $b) use ($sort_by, $sort_order) {
    $valueA = $a[$sort_by] ?? '';
    $valueB = $b[$sort_by] ?? '';
    
    if ($sort_by === 'date_inscription' || $sort_by === 'date_naissance') {
        $valueA = strtotime($valueA);
        $valueB = strtotime($valueB);
    }
    
    if ($sort_order === 'ASC') {
        return $valueA <=> $valueB;
    } else {
        return $valueB <=> $valueA;
    }
});

// Statistiques des patients
$total_patients = count($patients);
$patients_actifs = count(array_filter($patients, fn($p) => ($p['statut'] ?? '') === 'actif'));
$patients_inactifs = $total_patients - $patients_actifs;

// Messages de succès/erreur
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Génération d'un token CSRF pour les formulaires d'action
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des patients - MedChain Admin</title>
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
        .dashboard-container{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
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
        .dashboard-main{padding:32px 40px;overflow-y:auto}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:16px}
        .page-header h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:var(--navy)}
        .stats-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:28px}
        .stat-card{background:#fff;border-radius:var(--radius-lg);padding:20px;box-shadow:var(--shadow-sm);border:1px solid var(--gray-200)}
        .stat-card-title{font-size:13px;color:var(--gray-500);margin-bottom:8px}
        .stat-card-value{font-size:32px;font-weight:700;color:var(--navy)}
        .stat-card-desc{font-size:12px;color:var(--gray-500);margin-top:8px}
        .alert{padding:14px 18px;border-radius:var(--radius-md);margin-bottom:24px;display:flex;align-items:center;gap:12px;}
        .alert-success{background:#F0FDF4;border-left:4px solid #22C55E;color:#166534}
        .alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C}
        .alert-close{margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer}
        .card{background:#fff;border-radius:var(--radius-lg);border:1px solid var(--gray-200);box-shadow:var(--shadow-sm);overflow:hidden}
        .card-header{padding:16px 22px;border-bottom:1px solid var(--gray-200);font-weight:600;display:flex;justify-content:space-between;align-items:center}
        .card-body{padding:20px 22px}
        .form-control, .form-select{padding:8px 12px;border:1px solid var(--gray-200);border-radius:8px;width:100%;font-size:14px}
        .form-label{font-size:13px;margin-bottom:6px;display:block;color:var(--gray-700);font-weight:500}
        .btn-sm{padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;transition:all .2s;border:none;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
        .btn-outline-primary{background:transparent;border:1.5px solid var(--gray-200);color:var(--gray-700)}
        .btn-outline-primary:hover{border-color:var(--green);color:var(--green)}
        .btn-outline-warning{background:transparent;border:1.5px solid #F59E0B;color:#D97706}
        .btn-outline-warning:hover{background:#FEF3C7}
        .btn-outline-success{background:transparent;border:1.5px solid #22C55E;color:#16A34A}
        .btn-outline-success:hover{background:#F0FDF4}
        .btn-outline-danger{background:transparent;border:1.5px solid #EF4444;color:#DC2626}
        .btn-outline-danger:hover{background:#FEF2F2}
        .status-badge.actif{background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:20px;font-size:12px;display:inline-block}
        .status-badge.inactif{background:#fee2e2;color:#991b1b;padding:4px 10px;border-radius:20px;font-size:12px;display:inline-block}
        .btn-group{display:flex;gap:6px;flex-wrap:wrap}
        .filter-reset{color:var(--gray-500);text-decoration:none;font-size:13px}
        .filter-reset:hover{color:var(--green)}
        .table{width:100%;border-collapse:collapse}
        .table th{background:#F8FAFC;padding:14px 16px;text-align:left;font-weight:600;color:#64748B;border-bottom:1px solid var(--gray-200);font-size:13px}
        .table th a{color:#64748B;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
        .table th a:hover{color:var(--green)}
        .table td{padding:16px;border-bottom:1px solid var(--gray-200);vertical-align:middle}
        .text-center{text-align:center}
        .text-muted{color:var(--gray-500)}
        .row{display:flex;flex-wrap:wrap;margin:-8px}
        .col-md-2,.col-md-3,.col-md-5,.col-md-7{flex:1;padding:8px;min-width:150px}
        .mt-3{margin-top:16px}
        .mb-4{margin-bottom:24px}
        .gap-2{gap:8px}
        .d-flex{display:flex}
        .align-items-end{align-items:flex-end}
        .py-5{padding-top:48px;padding-bottom:48px}
        @media(max-width:900px){.dashboard-container{grid-template-columns:1fr}.dashboard-sidebar{display:none}.dashboard-main{padding:20px 16px}}
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
            <a href="admin-dashboard.php" class="dashboard-nav-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="admin-users.php" class="dashboard-nav-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
            <a href="admin-create-user.php" class="dashboard-nav-item"><i class="bi bi-person-plus-fill"></i> Nouvel utilisateur</a>
            <a href="admin-reports-statistics.php" class="dashboard-nav-item"><i class="bi bi-graph-up"></i> Statistiques</a>
            <div class="dashboard-nav-title">Médical</div>
            <a href="admin-medecin.php" class="dashboard-nav-item"><i class="bi bi-heart-pulse-fill"></i> Médecins</a>
            <a href="admin-patient.php" class="dashboard-nav-item active"><i class="bi bi-person-lines-fill"></i> Patients</a>
            <a href="admin-medical-dashboard.php" class="dashboard-nav-item"><i class="bi bi-activity"></i> Suivi santé avancé</a>
            <div class="dashboard-nav-title">Gestion</div>
            <a href="admin-chatbot.php" class="dashboard-nav-item"><i class="bi bi-robot"></i> Assistant IA</a>
            <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
            <a href="../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Déconnexion ?')"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
        <div class="page-header">
            <h1><i class="bi bi-person-lines-fill"></i> Gestion des patients</h1>
            <a href="admin-create-user.php?role=patient" class="btn-sm" style="background:var(--green);color:#fff;border:none;padding:10px 18px;border-radius:8px;text-decoration:none;">
                <i class="bi bi-person-plus-fill"></i> Ajouter un patient
            </a>
        </div>

        <!-- Statistiques -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-card-title">Total patients</div>
                <div class="stat-card-value"><?= $total_patients ?></div>
                <div class="stat-card-desc"> inscrits au total</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title">Patients actifs</div>
                <div class="stat-card-value" style="color:#22C55E"><?= $patients_actifs ?></div>
                <div class="stat-card-desc"> comptes actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title">Patients inactifs</div>
                <div class="stat-card-value" style="color:#EF4444"><?= $patients_inactifs ?></div>
                <div class="stat-card-desc"> comptes désactivés</div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= e($success_message) ?><button class="alert-close">&times;</button></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= e($error_message) ?><button class="alert-close">&times;</button></div>
        <?php endif; ?>

        <!-- Formulaire de filtres -->
        <div class="card mb-4">
            <div class="card-header">
                <span><i class="bi bi-funnel-fill"></i> Filtres</span>
                <?php if ($search || $statut_filter): ?>
                    <a href="admin-patient.php" class="filter-reset"><i class="bi bi-x-circle"></i> Réinitialiser</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-7">
                        <label class="form-label">Recherche par nom, email, téléphone, date de naissance ou adresse</label>
                        <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Nom, prénom, email, téléphone, date de naissance (jj/mm/aaaa), adresse...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut du compte</label>
                        <select name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="actif" <?= $statut_filter === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif" <?= $statut_filter === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn-sm" style="background:var(--green);color:#fff;border:none;padding:8px 20px">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des patients -->
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-list-ul"></i> Liste des patients</span>
                <span class="text-muted" style="font-size:13px"><?= count($patients) ?> patient(s) affiché(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'id_utilisateur', 'sort_order' => $sort_by === 'id_utilisateur' && $sort_order === 'ASC' ? 'DESC' : 'ASC'])) ?>">ID <?= $sort_by === 'id_utilisateur' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'nom', 'sort_order' => $sort_by === 'nom' && $sort_order === 'ASC' ? 'DESC' : 'ASC'])) ?>">Nom complet <?= $sort_by === 'nom' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></a></th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'date_naissance', 'sort_order' => $sort_by === 'date_naissance' && $sort_order === 'ASC' ? 'DESC' : 'ASC'])) ?>">Date naissance <?= $sort_by === 'date_naissance' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'statut', 'sort_order' => $sort_by === 'statut' && $sort_order === 'ASC' ? 'DESC' : 'ASC'])) ?>">Statut <?= $sort_by === 'statut' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'date_inscription', 'sort_order' => $sort_by === 'date_inscription' && $sort_order === 'ASC' ? 'DESC' : 'ASC'])) ?>">Inscription <?= $sort_by === 'date_inscription' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($patients)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size:48px;display:block;margin-bottom:10px"></i>
                                Aucun patient trouvé avec ces critères
                            </td>

                            </tr>
                        <?php else: ?>
                            <?php foreach ($patients as $p): ?>
                            <tr>
                                <td>#<?= e($p['id_utilisateur']) ?></td>
                                <td>
                                    <strong><?= e($p['prenom'] . ' ' . $p['nom']) ?></strong>
                                    <?php if (!empty($p['adresse'])): ?><br><small class="text-muted"><?= e(substr($p['adresse'], 0, 30)) . (strlen($p['adresse'] ?? '') > 30 ? '...' : '') ?></small><?php endif; ?>
                                </td>
                                <td><?= e($p['email']) ?></td>
                                <td><?= e($p['telephone'] ?? '—') ?></td>
                                <td>
                                    <?php if (!empty($p['date_naissance'])): ?>
                                        <?= date('d/m/Y', strtotime($p['date_naissance'])) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge <?= e($p['statut']) ?>"><?= $p['statut'] === 'actif' ? '✓ Actif' : '✗ Inactif' ?></span></td>
                                <td><?= date('d/m/Y', strtotime($p['date_inscription'])) ?></td>
                                <td class="btn-group">
                                    <a href="admin-edit.php?id=<?= e($p['id_utilisateur']) ?>" class="btn-sm btn-outline-primary" title="Modifier">
                                        <i class="bi bi-pencil-fill"></i> Modifier
                                    </a>
                                    <?php if ($p['id_utilisateur'] != $_SESSION['user_id']): ?>
                                        <?php if ($p['statut'] === 'actif'): ?>
                                            <form method="POST" action="admin-deactivate.php" onsubmit="return confirm('Désactiver ce patient ? Il ne pourra plus se connecter.')" style="display:inline">
                                                <input type="hidden" name="user_id" value="<?= e($p['id_utilisateur']) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <button type="submit" class="btn-sm btn-outline-warning" title="Désactiver">
                                                    <i class="bi bi-person-slash"></i> Désactiver
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="admin-activate.php" onsubmit="return confirm('Activer ce patient ? Il pourra à nouveau se connecter.')" style="display:inline">
                                                <input type="hidden" name="user_id" value="<?= e($p['id_utilisateur']) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <button type="submit" class="btn-sm btn-outline-success" title="Activer">
                                                    <i class="bi bi-check-lg"></i> Activer
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="admin-delete.php" onsubmit="return confirm('⚠️ ATTENTION : Supprimer définitivement ce patient ? Cette action est irréversible et supprimera toutes ses données associées.')" style="display:inline">
                                            <input type="hidden" name="user_id" value="<?= e($p['id_utilisateur']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <button type="submit" class="btn-sm btn-outline-danger" title="Supprimer définitivement">
                                                <i class="bi bi-trash-fill"></i> Supprimer
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:12px">(Vous-même)</span>
                                    <?php endif; ?>
                                  </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <a href="admin-dashboard.php" class="btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Retour au tableau de bord</a>
        </div>
    </main>
</div>
<script>
    document.querySelectorAll('.alert-close').forEach(btn => btn.addEventListener('click', () => btn.closest('.alert').remove()));
    setTimeout(() => document.querySelectorAll('.alert').forEach(a => a.remove()), 5000);
</script>
</body>
</html>