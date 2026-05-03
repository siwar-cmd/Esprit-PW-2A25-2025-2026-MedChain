    <aside class="dashboard-sidebar">
      <div class="sidebar-logo-zone">
        <a href="../../frontoffice/home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div>
            <div class="sidebar-logo-text">Med<span>Chain</span></div>
            <div class="sidebar-tagline">Espace Médecin</div>
          </div>
        </a>
      </div>
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-badge-fill"></i></div>
        <div class="sidebar-user-name">Dr. <?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?></div>
        <div class="sidebar-user-role"><i class="bi bi-heart-pulse-fill"></i> Médecin</div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Mes Consultations</div>
        <a href="../rendezvous/medecin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-calendar-check"></i></span> Rendez-vous
        </a>
        <a href="../ficherdv/medecin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span> Fiches Médicales
        </a>
        
        <div class="sidebar-nav-section-label">Gestion Stock</div>
        <a href="../lot_medicament/medecin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-box-seam"></i></span> Lots Médicaments
        </a>
        <a href="../distribution/medecin-index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-truck"></i></span> Distributions
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout">
          <span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span> Déconnexion
        </a>
      </div>
    </aside>
