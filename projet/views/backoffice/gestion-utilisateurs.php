<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => false, 
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

if (!isset($_SESSION['user_role'], $_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '../../controllers/AdminController.php';

function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function isActivePage(string $page): string {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page ? 'active' : '';
}

try {
    $adminController = new AdminController();
    
    $search = $_GET['search'] ?? '';
    $role_filter = $_GET['role'] ?? '';
    $statut_filter = $_GET['statut'] ?? '';
    
    $filters = [];
    if ($search) $filters['search'] = $search;
    if ($role_filter) $filters['role'] = $role_filter;
    if ($statut_filter) $filters['statut'] = $statut_filter;
    
    $usersResult = $adminController->getAllUsers($filters);
    $allUsers = $usersResult['success'] ? $usersResult['users'] : [];
    $totalUsers = count($allUsers);
    
    $stats = [
        'total' => $totalUsers,
        'actifs' => count(array_filter($allUsers, fn($u) => $u['statut'] === 'actif')),
        'inactifs' => count(array_filter($allUsers, fn($u) => $u['statut'] === 'inactif')),
        'admins' => count(array_filter($allUsers, fn($u) => $u['role'] === 'admin')),
        'medecins' => count(array_filter($allUsers, fn($u) => $u['role'] === 'medecin')),
        'patients' => count(array_filter($allUsers, fn($u) => $u['role'] === 'patient'))
    ];
    
} catch (Exception $e) {
    error_log("Gestion utilisateurs error: " . $e->getMessage());
    $errorMessage = "Une erreur technique est survenue.";
    $allUsers = [];
    $stats = [
        'total' => 0, 'actifs' => 0, 'inactifs' => 0,
        'admins' => 0, 'medecins' => 0, 'patients' => 0
    ];
}

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$userFullName = isset($_SESSION['user_prenom'], $_SESSION['user_nom']) 
    ? e($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'])
    : 'Administrateur';

$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

$pageTitle = "Gestion des Utilisateurs";
$logoutUrl = '../../controllers/logout.php?csrf=' . $csrfToken;
$profileUrl = '../../frontoffice/auth/profile.php';
$siteUrl = '../../frontoffice/home/index.php';
$dashboardUrl = 'admin-dashboard.php';
$createUserUrl = 'admin-create-user.php';
$editUserUrl = 'admin-edit-user.php';
?>

<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestion des utilisateurs - Tableau de bord administrateur">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= e($pageTitle) ?> | Système Médical</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 
   
</head>
<body>
    
    <div class="sidebar d-none d-md-block">
        <div class="logo">
            <h4 class="mb-0">
                <i class="fas fa-hospital-alt me-2"></i>
                Admin Panel
            </h4>
            <small class="text-white-50">Système Médical</small>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="<?= e($dashboardUrl) ?>">
                <i class="fas fa-tachometer-alt"></i> Tableau de bord
            </a>
            <a class="nav-link" href="gestion-medecins.php">
                <i class="fas fa-user-md"></i> Gestion médecins
            </a>
            <a class="nav-link active" href="gestion-utilisateurs.php">
                <i class="fas fa-users"></i> Gestion utilisateurs
            </a>
            <div class="mt-4 pt-3 border-top">
                <a class="nav-link" href="<?= e($siteUrl) ?>">
                    <i class="fas fa-external-link-alt"></i> Retour au site
                </a>
                <a class="nav-link text-danger" href="#" onclick="confirmLogout(event)">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </nav>
    </div>

 
    <nav class="navbar navbar-dark bg-primary d-md-none">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hospital-alt"></i> Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header bg-primary text-white">
            <h5 class="offcanvas-title">Menu Administrateur</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <a class="nav-link" href="<?= e($dashboardUrl) ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                </a>
                <a class="nav-link" href="gestion-medecins.php">
                    <i class="fas fa-user-md me-2"></i> Gestion médecins
                </a>
                <a class="nav-link active" href="gestion-utilisateurs.php">
                    <i class="fas fa-users me-2"></i> Gestion utilisateurs
                </a>
                <a class="nav-link" href="<?= e($siteUrl) ?>">
                    <i class="fas fa-external-link-alt me-2"></i> Retour au site
                </a>
                <a class="nav-link text-danger" href="#" onclick="confirmLogout(event)">
                    <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                </a>
            </nav>
        </div>
    </div>

    <div class="main-content">
     
        <div class="top-bar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0"><?= e($pageTitle) ?></h1>
                    <p class="text-muted mb-0">Gérez les comptes utilisateurs du système</p>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="dropdown">
                            <button class="btn btn-light d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                                <div class="user-avatar me-2">
                                    <?= strtoupper(substr($userFullName, 0, 1)) ?>
                                </div>
                                <span><?= e($_SESSION['user_prenom'] ?? 'Admin') ?></span>
                                <i class="fas fa-chevron-down ms-2"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?= e($profileUrl) ?>">
                                        <i class="fas fa-user me-2"></i> Mon profil
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="confirmLogout(event)">
                                        <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= e($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= e($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

       
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card total">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?= e($stats['total']) ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card actif">
                    <div class="stat-icon text-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-number"><?= e($stats['actifs']) ?></div>
                    <div class="stat-label">Actifs</div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card inactif">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-number"><?= e($stats['inactifs']) ?></div>
                    <div class="stat-label">Inactifs</div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card admin">
                    <div class="stat-icon text-danger">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stat-number"><?= e($stats['admins']) ?></div>
                    <div class="stat-label">Admins</div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card medecin">
                    <div class="stat-icon text-info">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-number"><?= e($stats['medecins']) ?></div>
                    <div class="stat-label">Médecins</div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card patient">
                    <div class="stat-icon" style="color: #6f42c1;">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="stat-number"><?= e($stats['patients']) ?></div>
                    <div class="stat-label">Patients</div>
                </div>
            </div>
        </div>

        <div class="card mb-4 <?= ($search || $role_filter || $statut_filter) ? 'filter-active' : '' ?>">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filtres et Recherche
                    <?php if ($search || $role_filter || $statut_filter): ?>
                        <span class="badge bg-primary ms-2">Filtres actifs</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= e($search) ?>" placeholder="Nom, prénom ou email...">
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">Tous les rôles</option>
                                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                                <option value="medecin" <?= $role_filter === 'medecin' ? 'selected' : '' ?>>Médecin</option>
                                <option value="patient" <?= $role_filter === 'patient' ? 'selected' : '' ?>>Patient</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="">Tous les statuts</option>
                                <option value="actif" <?= $statut_filter === 'actif' ? 'selected' : '' ?>>Actif</option>
                                <option value="inactif" <?= $statut_filter === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-search me-1"></i> Appliquer
                                </button>
                                <?php if ($search || $role_filter || $statut_filter): ?>
                                    <a href="gestion-utilisateurs.php" class="btn btn-outline-secondary" title="Réinitialiser">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Liste des Utilisateurs
                    <span class="badge bg-primary ms-2"><?= e(count($allUsers)) ?></span>
                </h5>
                <div>
                    <a href="<?= e($createUserUrl) ?>" class="btn btn-success">
                        <i class="fas fa-user-plus me-1"></i> Nouvel utilisateur
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($allUsers)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun utilisateur trouvé</h5>
                        <p class="text-muted">Commencez par ajouter un nouvel utilisateur</p>
                        <a href="<?= e($createUserUrl) ?>" class="btn btn-success mt-2">
                            <i class="fas fa-user-plus me-1"></i> Ajouter un utilisateur
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Inscription</th>
                                    <th class="actions-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $user): 
                                    $user_id = $user['id_utilisateur'] ?? $user['id'];
                                    $is_current_user = $user_id == $_SESSION['user_id'];
                                    $initials = strtoupper(substr($user['prenom'] ?? '', 0, 1) . substr($user['nom'] ?? '', 0, 1));
                                ?>
                                <tr>
                                    <td><strong>#<?= e($user_id) ?></strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar-small me-2">
                                                <?= e($initials) ?>
                                            </div>
                                            <div>
                                                <strong><?= e(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e($user['email'] ?? '') ?></td>
                                    <td>
                                        <span class="role-badge badge-<?= e($user['role'] ?? '') ?>">
                                            <?php 
                                                $roleText = match($user['role'] ?? '') {
                                                    'admin' => 'Administrateur',
                                                    'medecin' => 'Médecin',
                                                    'patient' => 'Patient',
                                                    default => ucfirst($user['role'] ?? '')
                                                };
                                                echo e($roleText);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge badge-<?= e($user['statut'] ?? '') ?>">
                                            <?php if (($user['statut'] ?? '') === 'actif'): ?>
                                                <i class="fas fa-check-circle me-1"></i> Actif
                                            <?php else: ?>
                                                <i class="fas fa-pause-circle me-1"></i> Inactif
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= e(date('d/m/Y', strtotime($user['date_inscription'] ?? ''))) ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="admin-edit-user.php?id=<?= e($user_id) ?>" 
                                               class="btn btn-outline-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if (!$is_current_user): ?>
                                                <?php if (($user['statut'] ?? '') === 'actif'): ?>
                                                    <form method="POST" action="admin-deactivate.php" 
                                                          onsubmit="return confirm('Désactiver cet utilisateur ?')" 
                                                          class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= e($user_id) ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                        <button type="submit" class="btn btn-outline-warning" title="Désactiver">
                                                            <i class="fas fa-user-slash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="admin-activate.php" 
                                                          onsubmit="return confirm('Activer cet utilisateur ?')" 
                                                          class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= e($user_id) ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                        <button type="submit" class="btn btn-outline-success" title="Activer">
                                                            <i class="fas fa-user-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary" disabled title="Action non disponible">
                                                    <i class="fas fa-user-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-plus fa-2x text-primary mb-3"></i>
                        <h5>Ajouter un utilisateur</h5>
                        <p class="text-muted">Créer un nouveau compte utilisateur</p>
                        <a href="<?= e($createUserUrl) ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Créer
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-file-export fa-2x text-success mb-3"></i>
                        <h5>Exporter les données</h5>
                        <p class="text-muted">Exporter la liste des utilisateurs</p>
                        <button class="btn btn-success" onclick="exportUsers()">
                            <i class="fas fa-download me-1"></i> Exporter
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar fa-2x text-info mb-3"></i>
                        <h5>Statistiques détaillées</h5>
                        <p class="text-muted">Voir les rapports d'activité</p>
                        <a href="statistiques.php" class="btn btn-info">
                            <i class="fas fa-chart-line me-1"></i> Voir
                        </a>
                    </div>
                </div>
            </div>
        </div>

 
        <div class="d-flex justify-content-between mt-4">
            <a href="<?= e($dashboardUrl) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Retour au tableau de bord
            </a>
            <a href="<?= e($siteUrl) ?>" class="btn btn-outline-primary">
                <i class="fas fa-home me-2"></i> Retour au site
            </a>
        </div>
        <footer class="mt-5 pt-4 border-top">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted">
                        &copy; <?= date('Y') ?> Système Médical. Tous droits réservés.
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        Connecté en tant qu'administrateur | 
                        <span id="currentTime"></span>
                    </span>
                </div>
            </div>
        </footer>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
  
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
    
        function confirmLogout(event) {
            event.preventDefault();
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                const logoutUrl = '<?= e($logoutUrl) ?>';
                window.location.href = logoutUrl;
            }
        }
        
 
        function exportUsers() {
            const search = '<?= e($search) ?>';
            const role = '<?= e($role_filter) ?>';
            const statut = '<?= e($statut_filter) ?>';
            
            let url = 'export-users.php?csrf=' + csrfToken;
            if (search) url += '&search=' + encodeURIComponent(search);
            if (role) url += '&role=' + encodeURIComponent(role);
            if (statut) url += '&statut=' + encodeURIComponent(statut);
            
            window.location.href = url;
        }
        
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filterForm');
            const inputs = filterForm.querySelectorAll('input, select');
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.location.href = 'gestion-utilisateurs.php';
                }
            });
            
            inputs.forEach(input => {
                if (input.value) {
                    input.classList.add('border-primary', 'border-2');
                }
                
                input.addEventListener('change', function() {
                    if (this.value) {
                        this.classList.add('border-primary', 'border-2');
                    } else {
                        this.classList.remove('border-primary', 'border-2');
                    }
                });
            });
        });
    </script>
</body>
</html>