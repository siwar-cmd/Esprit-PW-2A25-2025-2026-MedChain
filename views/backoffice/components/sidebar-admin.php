<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));
$depth        = ($current_dir === 'backoffice') ? '' : '../';
$root         = ($current_dir === 'backoffice') ? '../../' : '../../../';
?>
<aside class="dashboard-sidebar">
    <div class="dashboard-logo">
        <a href="<?= $depth ?>admin-dashboard.php">
            <div class="dashboard-logo-icon"><i class="bi bi-heart-pulse-fill"></i></div>
            <span class="dashboard-logo-text">Med<span>Chain</span></span>
        </a>
    </div>

    <nav class="dashboard-nav">
        <div class="dashboard-nav-title">Navigation</div>
        <a href="<?= $depth ?>admin-dashboard.php"
           class="dashboard-nav-item <?= $current_page === 'admin-dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= $depth ?>admin-users.php"
           class="dashboard-nav-item <?= $current_page === 'admin-users.php' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Utilisateurs
        </a>
        <a href="<?= $depth ?>admin-reports-statistics.php"
           class="dashboard-nav-item <?= $current_page === 'admin-reports-statistics.php' ? 'active' : '' ?>">
            <i class="bi bi-graph-up"></i> Statistiques
        </a>
        <a href="<?= $depth ?>rendezvous/admin-index.php"
           class="dashboard-nav-item <?= $current_dir === 'rendezvous' ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i> Rendez-vous
        </a>
        <a href="<?= $depth ?>ficherdv/admin-index.php"
           class="dashboard-nav-item <?= $current_dir === 'ficherdv' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-medical"></i> Fiches Médicales
        </a>

        <div class="dashboard-nav-title">Stock &amp; Pharmacie</div>
        <a href="<?= $depth ?>lot_medicament/admin-index.php"
           class="dashboard-nav-item <?= $current_dir === 'lot_medicament' ? 'active' : '' ?>">
            <i class="bi bi-capsule"></i> Lots Médicaments
        </a>
        <a href="<?= $depth ?>distribution/admin-index.php"
           class="dashboard-nav-item <?= $current_dir === 'distribution' ? 'active' : '' ?>">
            <i class="bi bi-truck"></i> Distributions
        </a>

        <div class="dashboard-nav-title">Flotte &amp; Missions</div>
        <a href="<?= $depth ?>missions/admin-demandes.php"
           class="dashboard-nav-item <?= $current_page === 'admin-demandes.php' ? 'active' : '' ?>">
            <i class="bi bi-send-plus-fill"></i> Demandes
        </a>
        <a href="<?= $depth ?>missions/admin-map.php"
           class="dashboard-nav-item <?= $current_page === 'admin-map.php' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i> Carte Temps Réel
        </a>
        <a href="<?= $depth ?>ambulances/admin-index.php"
           class="dashboard-nav-item <?= ($current_dir === 'ambulances' && $current_page === 'admin-index.php') ? 'active' : '' ?>">
            <i class="bi bi-truck-front-fill"></i> Ambulance
        </a>
        <a href="<?= $depth ?>missions/admin-index.php"
           class="dashboard-nav-item <?= ($current_dir === 'missions' && $current_page === 'admin-index.php') ? 'active' : '' ?>">
            <i class="bi bi-geo-alt-fill"></i> Registre Missions
        </a>

        <div class="dashboard-nav-title">Bloc Opération</div>
        <a href="<?= $root ?>admin_map.html" class="dashboard-nav-item">
            <i class="bi bi-map-fill"></i> Carte 3D
        </a>
        <a href="<?= $depth ?>bloc-operation/materiel-index.php"
           class="dashboard-nav-item <?= ($current_dir === 'bloc-operation' && $current_page === 'materiel-index.php') ? 'active' : '' ?>">
            <i class="bi bi-tools"></i> Matériel
        </a>
        <a href="<?= $depth ?>bloc-operation/intervention-index.php"
           class="dashboard-nav-item <?= ($current_dir === 'bloc-operation' && $current_page === 'intervention-index.php') ? 'active' : '' ?>">
            <i class="bi bi-heart-pulse-fill"></i> Interventions
        </a>

        <div class="dashboard-nav-title">Compte</div>
        <a href="<?= $root ?>frontoffice/auth/profile.php" class="dashboard-nav-item">
            <i class="bi bi-person-circle"></i> Mon profil
        </a>
        <a href="<?= $root ?>controllers/logout.php" class="dashboard-nav-item logout" id="logoutLink">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>
    </nav>
</aside>

<script>
document.getElementById('logoutLink')?.addEventListener('click', function(e) {
    e.preventDefault();
    const href = this.href;
    Swal.fire({
        title: 'Déconnexion',
        text: 'Êtes-vous sûr de vouloir vous déconnecter ?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1D9E75',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Oui, déconnecter',
        cancelButtonText: 'Annuler'
    }).then(r => { if (r.isConfirmed) window.location.href = href; });
});
</script>
