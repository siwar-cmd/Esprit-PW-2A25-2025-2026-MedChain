<?php
require_once __DIR__ . '/../config/database.php';

// Get page and action parameters
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// Database connection
$database = new Database();
$pdo = $database->getConnection();

// Handle pret actions
if ($page === 'prets' && $action && $id) {
    try {
        switch ($action) {
            case 'confirm':
                $pdo->beginTransaction();
                
                // Update loan status
                $stmt = $pdo->prepare("UPDATE pret SET statut = 'en_cours' WHERE id_pret = ?");
                $stmt->execute([$id]);
                
                // Check if this was the last available item and update object availability
                $stmt = $pdo->prepare("
                    SELECT o.id_objet, o.quantite, COUNT(p.id_pret) as active_loans
                    FROM objet_loisir o
                    LEFT JOIN pret p ON o.id_objet = p.id_objet AND p.statut IN ('en_cours', 'en_attente')
                    WHERE o.id_objet = (SELECT id_objet FROM pret WHERE id_pret = ?)
                    GROUP BY o.id_objet, o.quantite
                ");
                $stmt->execute([$id]);
                $result = $stmt->fetch();
                
                if ($result && $result['active_loans'] >= $result['quantite']) {
                    $stmt = $pdo->prepare("UPDATE objet_loisir SET disponibilite = 'indisponible' WHERE id_objet = ?");
                    $stmt->execute([$result['id_objet']]);
                }
                
                $pdo->commit();
                header('Location: index.php?page=prets&success=confirmed');
                exit;
                
            case 'cancel':
                $pdo->beginTransaction();
                
                // Get loan details before cancellation
                $stmt = $pdo->prepare("SELECT id_objet FROM pret WHERE id_pret = ?");
                $stmt->execute([$id]);
                $loan = $stmt->fetch();
                
                // Update loan status
                $stmt = $pdo->prepare("UPDATE pret SET statut = 'annule' WHERE id_pret = ?");
                $stmt->execute([$id]);
                
                // Update object availability if needed
                if ($loan) {
                    $stmt = $pdo->prepare("
                        SELECT o.id_objet, o.quantite, COUNT(p.id_pret) as active_loans
                        FROM objet_loisir o
                        LEFT JOIN pret p ON o.id_objet = p.id_objet AND p.statut IN ('en_cours', 'en_attente')
                        WHERE o.id_objet = ?
                        GROUP BY o.id_objet, o.quantite
                    ");
                    $stmt->execute([$loan['id_objet']]);
                    $result = $stmt->fetch();
                    
                    if ($result && $result['active_loans'] < $result['quantite']) {
                        $stmt = $pdo->prepare("UPDATE objet_loisir SET disponibilite = 'disponible' WHERE id_objet = ?");
                        $stmt->execute([$result['id_objet']]);
                    }
                }
                
                $pdo->commit();
                header('Location: index.php?page=prets&success=cancelled');
                exit;
                
            case 'return':
                $pdo->beginTransaction();
                
                // Get loan details before return
                $stmt = $pdo->prepare("SELECT id_objet FROM pret WHERE id_pret = ?");
                $stmt->execute([$id]);
                $loan = $stmt->fetch();
                
                // Update loan status and return date
                $stmt = $pdo->prepare("UPDATE pret SET statut = 'termine', date_retour_effective = CURDATE() WHERE id_pret = ?");
                $stmt->execute([$id]);
                
                // Update object availability if needed
                if ($loan) {
                    $stmt = $pdo->prepare("
                        SELECT o.id_objet, o.quantite, COUNT(p.id_pret) as active_loans
                        FROM objet_loisir o
                        LEFT JOIN pret p ON o.id_objet = p.id_objet AND p.statut IN ('en_cours', 'en_attente')
                        WHERE o.id_objet = ?
                        GROUP BY o.id_objet, o.quantite
                    ");
                    $stmt->execute([$loan['id_objet']]);
                    $result = $stmt->fetch();
                    
                    if ($result && $result['active_loans'] < $result['quantite']) {
                        $stmt = $pdo->prepare("UPDATE objet_loisir SET disponibilite = 'disponible' WHERE id_objet = ?");
                        $stmt->execute([$result['id_objet']]);
                    }
                }
                
                $pdo->commit();
                header('Location: index.php?page=prets&success=returned');
                exit;
                
            case 'show':
                // Show loan details
                $stmt = $pdo->prepare("
                    SELECT p.*, o.nom_objet, o.type_objet, o.quantite,
                           (SELECT COUNT(*) FROM pret WHERE id_objet = o.id_objet AND statut IN ('en_cours', 'en_attente')) as active_loans
                    FROM pret p 
                    JOIN objet_loisir o ON p.id_objet = o.id_objet 
                    WHERE p.id_pret = ?
                ");
                $stmt->execute([$id]);
                $loan = $stmt->fetch();
                
                if ($loan) {
                    // Show loan details page
                    ?>
                    <!DOCTYPE html>
                    <html lang="fr">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Détails du prêt - Système de Gestion des Loisirs</title>
                        <link rel="stylesheet" href="assets/style.css">
                        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
                    </head>
                    <body>
                        <div class="dashboard-container">
                            <!-- Sidebar -->
                            <aside class="sidebar" id="sidebar">
                                <div class="sidebar-header">
                                    <div class="sidebar-logo">
                                        <img src="assets/logo.PNG" alt="Loisirs Management">
                                        <span>Loisirs</span>
                                    </div>
                                </div>
                                
                                <nav class="sidebar-nav">
                                    <a href="index.php" class="nav-item">
                                        <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                                        <span>Dashboard</span>
                                    </a>
                                    <a href="objets.php" class="nav-item">
                                        <span class="nav-icon"><i class="fas fa-box"></i></span>
                                        <span>Objets Loisirs</span>
                                    </a>
                                    <a href="index.php?page=prets" class="nav-item active">
                                        <span class="nav-icon"><i class="fas fa-hand-holding-usd"></i></span>
                                        <span>Gestion des Prêts</span>
                                    </a>
                                </nav>
                            </aside>

                            <!-- Main Content -->
                            <main class="main-content">
                                <header class="header">
                                    <div class="header-left">
                                        <button class="menu-toggle" id="menuToggle">
                                            <i class="fas fa-bars"></i>
                                        </button>
                                        <div class="search-bar">
                                            <i class="fas fa-search search-icon"></i>
                                            <input type="text" class="search-input" placeholder="Rechercher...">
                                        </div>
                                    </div>
                                    <div class="header-right">
                                        <button class="notification-btn">
                                            <i class="fas fa-bell"></i>
                                            <span class="notification-badge">3</span>
                                        </button>
                                        <div class="user-menu">
                                            <div class="user-avatar">JD</div>
                                        </div>
                                    </div>
                                </header>

                                <div class="content">
                                    <h1 class="page-title">Détails du prêt</h1>
                                    <p class="page-subtitle">Informations détaillées du prêt</p>

                                    <div class="detail-card">
                                        <div class="detail-header">
                                            <h2 class="detail-title">Prêt #<?= $loan['id_pret'] ?></h2>
                                            <span class="badge badge-<?= $loan['statut'] === 'en_cours' ? 'success' : ($loan['statut'] === 'en_attente' ? 'warning' : 'secondary') ?>">
                                                <?= htmlspecialchars($loan['statut']) ?>
                                            </span>
                                        </div>

                                        <div class="detail-grid">
                                            <div class="detail-item">
                                                <div class="detail-label">Patient</div>
                                                <div class="detail-value"><?= htmlspecialchars($loan['nom_patient']) ?></div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label">Objet</div>
                                                <div class="detail-value"><?= htmlspecialchars($loan['nom_objet']) ?></div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label">Type</div>
                                                <div class="detail-value"><?= htmlspecialchars($loan['type_objet']) ?></div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label">Date de prêt</div>
                                                <div class="detail-value"><?= date('d/m/Y', strtotime($loan['date_pret'])) ?></div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label">Retour prévu</div>
                                                <div class="detail-value"><?= date('d/m/Y', strtotime($loan['date_retour_prevue'])) ?></div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label">Retour effectif</div>
                                                <div class="detail-value"><?= $loan['date_retour_effective'] ? date('d/m/Y', strtotime($loan['date_retour_effective'])) : '-' ?></div>
                                            </div>
                                        </div>

                                        <div class="detail-item">
                                            <div class="detail-label">Disponibilité de l'objet</div>
                                            <div class="detail-value">
                                                <?= $loan['active_loans'] ?> / <?= $loan['quantite'] ?> en prêt
                                                <div class="availability-info availability-<?= $loan['active_loans'] >= $loan['quantite'] ? 'low' : ($loan['active_loans'] >= $loan['quantite'] * 0.7 ? 'medium' : 'high') ?>">
                                                    <?php
                                                    $remaining = $loan['quantite'] - $loan['active_loans'];
                                                    if ($remaining == 0) {
                                                        echo 'Aucun exemplaire disponible';
                                                    } elseif ($remaining <= 2) {
                                                        echo "Seulement $remaining exemplaire(s) disponible(s)";
                                                    } else {
                                                        echo "$remaining exemplaire(s) disponible(s)";
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="btn-actions">
                                            <a href="index.php?page=prets" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Retour
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </main>
                        </div>

                        <script>
                            // Mobile menu toggle
                            document.addEventListener('DOMContentLoaded', function() {
                                const menuToggle = document.querySelector('.menu-toggle');
                                const sidebar = document.querySelector('.sidebar');
                                
                                if (menuToggle && sidebar) {
                                    menuToggle.addEventListener('click', function() {
                                        sidebar.classList.toggle('active');
                                    });
                                }
                                
                                // Close sidebar when clicking outside on mobile
                                document.addEventListener('click', function(e) {
                                    if (window.innerWidth <= 768) {
                                        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                                            sidebar.classList.remove('active');
                                        }
                                    }
                                });
                            });
                        </script>
                    </body>
                    </html>
                    <?php
                } else {
                    header('Location: index.php?page=prets&error=not_found');
                }
                exit;
        }
    } catch (Exception $e) {
        header('Location: index.php?page=prets&error=system_error');
        exit;
    }
}

// Default: Show dashboard or redirect to appropriate page
if ($page === 'dashboard' || !$page) {
    // Include dashboard view or redirect
    header('Location: dashboard.php');
    exit;
} else {
    // Redirect to appropriate standalone page
    switch ($page) {
        case 'objets':
            header('Location: objets.php');
            exit;
        case 'prets':
            header('Location: prets.php');
            exit;
        default:
            header('Location: dashboard.php');
            exit;
    }
}
?>
