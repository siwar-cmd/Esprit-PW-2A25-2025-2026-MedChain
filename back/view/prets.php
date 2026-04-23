<?php
require_once __DIR__ . '/../config/database.php';

// Database connection
$database = new Database();
$pdo = $database->getConnection();

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';
$view = $_GET['view'] ?? '';

// Build query
$sql = "SELECT p.*, o.nom_objet, o.type_objet 
        FROM pret p 
        JOIN objet_loisir o ON p.id_objet = o.id_objet 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND p.nom_patient LIKE ?";
    $params[] = "%$search%";
}

if ($statut) {
    $sql .= " AND p.statut = ?";
    $params[] = $statut;
}

if ($view === 'retard') {
    $sql .= " AND p.statut = 'en_cours' AND p.date_retour_prevue < CURDATE()";
}

$sql .= " ORDER BY p.date_pret DESC";

// Execute query
try {
    $stmt = $database->query($sql, $params);
    $prets = $stmt->fetchAll();
} catch (Exception $e) {
    $prets = [];
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Prêts - Système de Gestion des Loisirs</title>
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Variables */
        :root {
            --primary-color: #2563eb;
            --dark-bg: #1e293b;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --success-color: #16a34a;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --sidebar-width: 280px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Layout Container */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark-bg);
            color: var(--white);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .sidebar-logo img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--white);
            padding: 8px;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: var(--white);
            border-left-color: var(--primary-color);
        }

        .nav-item.active {
            background: var(--primary-color);
            color: var(--white);
            border-left-color: var(--white);
        }

        .nav-icon {
            margin-right: 1rem;
            font-size: 1.25rem;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            background: var(--light-bg);
        }

        /* Header */
        .header {
            background: var(--white);
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 2rem;
            flex: 1;
        }

        .search-bar {
            position: relative;
            max-width: 400px;
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .notification-btn, .user-menu {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: var(--text-secondary);
            position: relative;
        }

        .notification-btn:hover, .user-menu:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--danger-color);
            color: var(--white);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #1e40af);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
        }

        /* Content */
        .content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        /* Alert */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: var(--success-color);
            border-left-color: var(--success-color);
        }

        .alert-danger {
            background: #fee2e2;
            color: var(--danger-color);
            border-left-color: var(--danger-color);
        }

        /* Search Section */
        .search-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.9rem;
        }

        .search-select {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.9rem;
            background: var(--white);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #1e40af;
        }

        .btn-secondary {
            background: var(--white);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--light-bg);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-success {
            background: var(--success-color);
            color: var(--white);
        }

        .btn-warning {
            background: var(--warning-color);
            color: var(--white);
        }

        .btn-danger {
            background: var(--danger-color);
            color: var(--white);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        /* Tables */
        .table-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--light-bg);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border-color);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: var(--light-bg);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-success {
            background: #dcfce7;
            color: var(--success-color);
        }

        .badge-warning {
            background: #fef3c7;
            color: var(--warning-color);
        }

        .badge-danger {
            background: #fee2e2;
            color: var(--danger-color);
        }

        .badge-secondary {
            background: #f1f5f9;
            color: var(--text-secondary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-state-description {
            margin-bottom: 1.5rem;
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            color: var(--text-primary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .header-left {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-bar {
                max-width: 100%;
            }
            
            .content {
                padding: 1rem;
            }
            
            .search-section {
                flex-direction: column;
            }
        }
    </style>
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
                <a href="prets.php" class="nav-item active">
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
                <h1 class="page-title">Gestion des Prêts</h1>
                <p class="page-subtitle">Gestion des prêts d'objets de loisirs</p>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php
                        switch ($_GET['success']) {
                            case 'created': echo 'Prêt créé avec succès!'; break;
                            case 'confirmed': echo 'Prêt confirmé avec succès!'; break;
                            case 'cancelled': echo 'Prêt annulé avec succès!'; break;
                            case 'returned': echo 'Objet retourné avec succès!'; break;
                            default: echo 'Opération réussie!';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <?php
                        switch ($_GET['error']) {
                            case 'create_failed': echo 'Erreur lors de la création du prêt'; break;
                            case 'confirm_failed': echo 'Erreur lors de la confirmation'; break;
                            case 'cancel_failed': echo 'Erreur lors de l\'annulation'; break;
                            case 'return_failed': echo 'Erreur lors du retour'; break;
                            default: echo 'Une erreur est survenue!';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Search Section -->
                <div class="search-section">
                    <form method="GET" action="prets.php" style="display: flex; gap: 1rem; flex: 1;">
                        <input type="text" name="search" class="search-input" placeholder="Rechercher par patient..." value="<?= htmlspecialchars($search) ?>">
                        <select name="statut" class="search-select">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="en_cours" <?= $statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="termine" <?= $statut === 'termine' ? 'selected' : '' ?>>Terminé</option>
                            <option value="annule" <?= $statut === 'annule' ? 'selected' : '' ?>>Annulé</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </form>
                </div>

                <!-- Actions -->
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                    <a href="pret_form.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Créer un prêt
                    </a>
                    <a href="prets.php?view=retard" class="btn btn-warning">
                        <i class="fas fa-exclamation-triangle"></i> Prêts en retard
                    </a>
                    <a href="objets.php" class="btn btn-secondary">
                        <i class="fas fa-box"></i> Voir les objets
                    </a>
                </div>

                <!-- Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Objet</th>
                                <th>Type</th>
                                <th>Date de prêt</th>
                                <th>Retour prévu</th>
                                <th>Retour effectif</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($prets)): ?>
                                <?php foreach ($prets as $pret): ?>
                                    <tr>
                                        <td><?= $pret['id_pret'] ?></td>
                                        <td><?= htmlspecialchars($pret['nom_patient']) ?></td>
                                        <td><?= htmlspecialchars($pret['nom_objet']) ?></td>
                                        <td><?= htmlspecialchars($pret['type_objet']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($pret['date_pret'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($pret['date_retour_prevue'])) ?></td>
                                        <td><?= $pret['date_retour_effective'] ? date('d/m/Y', strtotime($pret['date_retour_effective'])) : '-' ?></td>
                                        <td>
                                            <span class="badge badge-<?= $pret['statut'] === 'en_cours' ? 'success' : ($pret['statut'] === 'en_attente' ? 'warning' : 'secondary') ?>">
                                                <?= htmlspecialchars($pret['statut']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="prets.php?action=show&id=<?= $pret['id_pret'] ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($pret['statut'] === 'en_attente'): ?>
                                                    <a href="index.php?page=prets&action=confirm&id=<?= $pret['id_pret'] ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="index.php?page=prets&action=cancel&id=<?= $pret['id_pret'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($pret['statut'] === 'en_cours'): ?>
                                                    <a href="index.php?page=prets&action=return&id=<?= $pret['id_pret'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-undo"></i>
                                                    </a>
                                                    <a href="index.php?page=prets&action=cancel&id=<?= $pret['id_pret'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-inbox"></i>
                                        </div>
                                        <div class="empty-state-title">Aucun prêt trouvé</div>
                                        <div class="empty-state-description">Commencez par créer votre premier prêt</div>
                                        <a href="pret_form.php" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Créer un prêt
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
