<?php
// Shared backoffice sidebar — include this in every backoffice page
if (!defined('APP_ENTRY_URL')) {
    define('APP_ENTRY_URL', '/projet/index1.php');
}
if (!function_exists('routeUrl')) {
    function routeUrl(string $controller = 'objet', string $action = 'list', array $params = []): string {
        $query = array_merge(['office' => $params['office'] ?? 'front', 'controller' => $controller, 'action' => $action], $params);
        return APP_ENTRY_URL . '?' . http_build_query($query);
    }
}
$_current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="dashboard-sidebar" id="sidebar">
    <div class="dashboard-logo">
        <a href="admin-dashboard.php">
            <div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
            <div class="dashboard-logo-text">Med<span>Chain</span></div>
        </a>
    </div>

    <nav class="dashboard-nav">
        <div class="dashboard-nav-title">Navigation</div>
        <a href="admin-dashboard.php" class="dashboard-nav-item <?= $_current_page === 'admin-dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="admin-users.php" class="dashboard-nav-item <?= $_current_page === 'admin-users.php' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Utilisateurs
        </a>
        <a href="admin-create-user.php" class="dashboard-nav-item <?= $_current_page === 'admin-create-user.php' ? 'active' : '' ?>">
            <i class="bi bi-person-plus-fill"></i> Nouvel utilisateur
        </a>
        <a href="admin-reports-statistics.php" class="dashboard-nav-item <?= $_current_page === 'admin-reports-statistics.php' ? 'active' : '' ?>">
            <i class="bi bi-graph-up"></i> Statistiques
        </a>

        <div class="dashboard-nav-title">Objets Loisir</div>
        <a href="<?= routeUrl('objet', 'list', ['office' => 'back']) ?>" class="dashboard-nav-item">
            <i class="bi bi-box-seam-fill"></i> Liste des objets
        </a>
        <a href="<?= routeUrl('objet', 'add', ['office' => 'back']) ?>" class="dashboard-nav-item">
            <i class="bi bi-plus-circle-fill"></i> Ajouter un objet
        </a>
        <a href="<?= routeUrl('pret', 'pending', ['office' => 'back']) ?>" class="dashboard-nav-item">
            <i class="bi bi-hourglass-split"></i> Demandes en attente
        </a>
        <a href="<?= routeUrl('pret', 'confirmed', ['office' => 'back']) ?>" class="dashboard-nav-item">
            <i class="bi bi-arrow-repeat"></i> Prêts en cours
        </a>
        <a href="<?= routeUrl('pret', 'list', ['office' => 'back']) ?>" class="dashboard-nav-item">
            <i class="bi bi-list-ul"></i> Tous les prêts
        </a>

        <div class="dashboard-nav-title">Gestion</div>
        <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item">
            <i class="bi bi-person-circle"></i> Mon profil
        </a>
        <a href="/projet/controllers/logout.php"
           class="dashboard-nav-item logout"
           onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>
    </nav>

    <div style="padding:16px 20px;border-top:1px solid rgba(255,255,255,.08);margin-top:auto;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;color:white;font-size:15px;flex-shrink:0;">
                <i class="bi bi-person-fill"></i>
            </div>
            <div>
                <div style="font-size:13px;font-weight:600;color:white;">
                    <?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div style="font-size:11px;color:#64748B;">Administrateur</div>
            </div>
        </div>
    </div>
</aside>
