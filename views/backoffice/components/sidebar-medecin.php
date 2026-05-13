<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));
$depth        = ($current_dir === 'backoffice') ? '' : '../';
$root         = ($current_dir === 'backoffice') ? '../../' : '../../../';

$prenom   = $_SESSION['user_prenom'] ?? 'Dr.';
$nom      = $_SESSION['user_nom'] ?? '';
?>
<aside class="dashboard-sidebar">
    <!-- Logo -->
    <div class="sidebar-logo-zone">
        <a class="sidebar-logo-link" href="<?= $depth ?>medecin-dashboard.php">
            <div class="sidebar-logo-icon"><i class="bi bi-heart-pulse-fill"></i></div>
            <div>
                <div class="sidebar-logo-text">Med<span>Chain</span></div>
                <div class="sidebar-tagline">Espace Médecin</div>
            </div>
        </a>
    </div>

    <!-- User card -->
    <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
        <div class="sidebar-user-name">Dr. <?= htmlspecialchars($prenom . ' ' . $nom) ?></div>
        <div class="sidebar-user-role"><i class="bi bi-shield-check"></i> Médecin</div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Navigation</div>
        <a href="<?= $depth ?>medecin-dashboard.php"
           class="sidebar-nav-item <?= $current_page === 'medecin-dashboard.php' ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-speedometer2"></i></div> Tableau de bord
        </a>
        <a href="<?= $depth ?>rendezvous/medecin-index.php"
           class="sidebar-nav-item <?= $current_dir === 'rendezvous' ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-calendar-check"></i></div> Rendez-vous
        </a>
        <a href="<?= $depth ?>ficherdv/medecin-index.php"
           class="sidebar-nav-item <?= $current_dir === 'ficherdv' ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-file-earmark-medical"></i></div> Fiches Médicales
        </a>

        <div class="sidebar-nav-section-label">Stock &amp; Pharmacie</div>
        <a href="<?= $depth ?>lot_medicament/medecin-index.php"
           class="sidebar-nav-item <?= $current_dir === 'lot_medicament' ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-capsule"></i></div> Lots Médicaments
        </a>
        <a href="<?= $depth ?>distribution/medecin-index.php"
           class="sidebar-nav-item <?= $current_dir === 'distribution' ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-clipboard2-pulse"></i></div> Distributions
        </a>

        <div class="sidebar-nav-section-label">Flotte &amp; Missions</div>
        <a href="<?= $depth ?>missions/medecin-demandes.php"
           class="sidebar-nav-item <?= $current_page === 'medecin-demandes.php' ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-send-plus"></i></div> Demander Mission
        </a>
        <a href="<?= $depth ?>missions/medecin-map.php"
           class="sidebar-nav-item <?= $current_page === 'medecin-map.php' ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-clock-history"></i></div> Carte Temps Réel
        </a>
        <a href="<?= $depth ?>ambulances/medecin-index.php"
           class="sidebar-nav-item <?= ($current_dir === 'ambulances' && $current_page === 'medecin-index.php') ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-truck-front"></i></div> Ambulances
        </a>
        <a href="<?= $depth ?>missions/medecin-index.php"
           class="sidebar-nav-item <?= ($current_dir === 'missions' && $current_page === 'medecin-index.php') ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-geo-alt"></i></div> Missions
        </a>

        <div class="sidebar-nav-section-label">Bloc Opératoire</div>
        <a href="<?= $depth ?>bloc-operation/medecin-intervention-index.php"
           class="sidebar-nav-item <?= $current_page === 'medecin-intervention-index.php' ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-scissors"></i></div> Interventions
        </a>
        <a href="<?= $depth ?>bloc-operation/medecin-materiel-index.php"
           class="sidebar-nav-item <?= $current_page === 'medecin-materiel-index.php' ? 'active' : '' ?>">
            <div class="nav-icon"><i class="bi bi-bag-plus"></i></div> Matériel
        </a>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="<?= $root ?>frontoffice/auth/profile.php" class="sidebar-nav-item">
            <div class="nav-icon"><i class="bi bi-person-circle"></i></div> Mon Profil
        </a>
        <a href="<?= $root ?>controllers/logout.php" class="sidebar-nav-item logout" id="logoutBtnMedecin">
            <div class="nav-icon"><i class="bi bi-box-arrow-right"></i></div> Déconnexion
        </a>
    </div>
</aside>

<script>
document.getElementById('logoutBtnMedecin')?.addEventListener('click', function(e) {
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
