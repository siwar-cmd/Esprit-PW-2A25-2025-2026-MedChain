<?php
// ── Safe variable initialization ─────────────────────────────────────────────
// All values come from the session set by AuthController on login.
// No model instantiation needed — we never call getRole() on an object here.
$_sb_userId    = (int)  ($_SESSION['user_id']    ?? 0);
$_sb_userRole  =        $_SESSION['user_role']   ?? 'patient';
$_sb_userName  = trim(($_SESSION['user_prenom']  ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$_sb_isLoggedIn = $_sb_userId > 0;

// $currentCtrl and $currentAct are set by the front header template.
// Use isset() guards so this partial is safe even if included standalone.
$_sb_ctrl = isset($currentCtrl) ? $currentCtrl : ($_GET['controller'] ?? 'objet');
$_sb_act  = isset($currentAct)  ? $currentAct  : ($_GET['action']     ?? 'list');

// Guard against fatal redeclaration when the sidebar is included on
// multiple pages within the same PHP process (e.g. OPcache, tests).
if (!function_exists('_sb_active')) {
    function _sb_active(string $ctrl, string $act = ''): string {
        global $_sb_ctrl, $_sb_act;
        if ($act === '') return $_sb_ctrl === $ctrl ? 'active' : '';
        return ($_sb_ctrl === $ctrl && $_sb_act === $act) ? 'active' : '';
    }
}
?>

<?php /* Inject the dashboard sidebar CSS (defines .dashboard-sidebar, .dashboard-nav-item, etc.) */ ?>
<?php require_once BASE_PATH . '/views/backoffice/_sidebar_css.php'; ?>

<style>
    /* ── Layout wrapper for sidebar pages ── */
    .dashboard-container {
        display: flex;
        align-items: flex-start;
        min-height: 100vh;
    }
    .dashboard-main {
        flex: 1;
        min-width: 0;
        padding: 28px 32px;
        background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
        min-height: 100vh;
    }
    .dashboard-sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        flex-shrink: 0;
    }
    @media (max-width: 900px) {
        .dashboard-sidebar { display: none; }
        .dashboard-main    { padding: 18px 16px; }
    }
</style>

<aside class="dashboard-sidebar" id="sidebar">

    <div class="dashboard-logo">
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
            <div class="dashboard-logo-text">Med<span>Chain</span></div>
        </a>
    </div>

    <nav class="dashboard-nav">

        <div class="dashboard-nav-title">Catalogue</div>
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
           class="dashboard-nav-item <?php echo _sb_active('objet', 'list'); ?>">
            <i class="bi bi-box-seam-fill"></i> Tous les objets
        </a>
        <a href="<?php echo htmlspecialchars(routeUrl('chat', 'index', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
           class="dashboard-nav-item <?php echo _sb_active('chat'); ?>">
            <i class="bi bi-robot"></i> Assistant IA
        </a>

        <div class="dashboard-nav-title">Mes emprunts</div>
        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'myLoans', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
           class="dashboard-nav-item <?php echo _sb_active('pret', 'myLoans'); ?>">
            <i class="bi bi-bookmark-check-fill"></i> Mes prêts
        </a>
        <a href="<?php echo htmlspecialchars(routeUrl('reservation', 'myList', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
           class="dashboard-nav-item <?php echo _sb_active('reservation', 'myList'); ?>">
            <i class="bi bi-bookmark-star-fill"></i> Mes réservations
        </a>

        <div class="dashboard-nav-title">Mon compte</div>
        <a href="/projet/views/frontoffice/auth/profile.php"
           class="dashboard-nav-item">
            <i class="bi bi-person-circle"></i> Mon profil
        </a>
        <?php if ($_sb_isLoggedIn && $_sb_userRole === 'admin'): ?>
        <a href="<?php echo htmlspecialchars(routeUrl('admin', 'dashboard', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
           class="dashboard-nav-item">
            <i class="bi bi-speedometer2"></i> Administration
        </a>
        <?php endif; ?>

        <div class="dashboard-nav-title">Session</div>
        <a href="/projet/controllers/logout.php"
           class="dashboard-nav-item logout"
           onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>

    </nav>

    <?php if ($_sb_isLoggedIn): ?>
    <div style="padding:16px 20px;border-top:1px solid rgba(255,255,255,.08);margin-top:auto;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;color:white;font-size:15px;flex-shrink:0;">
                <i class="bi bi-person-fill"></i>
            </div>
            <div>
                <div style="font-size:13px;font-weight:600;color:white;">
                    <?php echo htmlspecialchars($_sb_userName ?: 'Utilisateur', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div style="font-size:11px;color:#64748B;">
                    <?php echo htmlspecialchars(ucfirst($_sb_userRole), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</aside>
