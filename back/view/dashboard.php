<?php
require_once __DIR__ . '/../config/database.php';

// Database connection
$database = new Database();
$pdo = $database->getConnection();

// Get dashboard statistics
try {
    $stats = [];
    
    // Total objects
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM objet_loisir");
    $stats['total_objets'] = $stmt->fetch()['count'];
    
    // Available objects
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM objet_loisir WHERE disponibilite = 'disponible'");
    $stats['objets_disponibles'] = $stmt->fetch()['count'];
    
    // Total loans
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pret");
    $stats['total_prets'] = $stmt->fetch()['count'];
    
    // Active loans
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pret WHERE statut = 'en_cours'");
    $stats['prets_en_cours'] = $stmt->fetch()['count'];
    
    // Late loans
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pret WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()");
    $stats['prets_en_retard'] = $stmt->fetch()['count'];
    
    // Recent activities
    $stmt = $pdo->query("
        SELECT 'pret' as type, nom_patient as user, 'Prêt créé' as description, date_pret as date 
        FROM pret 
        ORDER BY date_pret DESC 
        LIMIT 5
    ");
    $recentActivities = $stmt->fetchAll();
    
} catch (Exception $e) {
    $stats = [
        'total_objets' => 0,
        'objets_disponibles' => 0,
        'total_prets' => 0,
        'prets_en_cours' => 0,
        'prets_en_retard' => 0
    ];
    $recentActivities = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Système de Gestion des Loisirs</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
                <a href="objets.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-box"></i></span>
                    <span>Objets Loisirs</span>
                </a>
                <a href="prets.php" class="nav-item">
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
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Vue d'ensemble du système de gestion des loisirs</p>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card primary">
                        <div class="summary-card-header">
                            <div class="summary-card-icon">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="summary-card-title">Total des prêts</div>
                        </div>
                        <div class="summary-card-value"><?= $stats['total_prets'] ?></div>
                        <div class="summary-card-change positive">
                            <i class="fas fa-arrow-up"></i> 12% ce mois
                        </div>
                    </div>

                    <div class="summary-card success">
                        <div class="summary-card-header">
                            <div class="summary-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="summary-card-title">Prêts actifs</div>
                        </div>
                        <div class="summary-card-value"><?= $stats['prets_en_cours'] ?></div>
                        <div class="summary-card-change positive">
                            <i class="fas fa-arrow-up"></i> 5% ce mois
                        </div>
                    </div>

                    <div class="summary-card warning">
                        <div class="summary-card-header">
                            <div class="summary-card-icon">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <div class="summary-card-title">Objets disponibles</div>
                        </div>
                        <div class="summary-card-value"><?= $stats['objets_disponibles'] ?></div>
                        <div class="summary-card-change negative">
                            <i class="fas fa-arrow-down"></i> 3% ce mois
                        </div>
                    </div>

                    <div class="summary-card danger">
                        <div class="summary-card-header">
                            <div class="summary-card-icon">
                                <i class="fas fa-undo"></i>
                            </div>
                            <div class="summary-card-title">Prêts en retard</div>
                        </div>
                        <div class="summary-card-value"><?= $stats['prets_en_retard'] ?></div>
                        <div class="summary-card-change">
                            <i class="fas fa-arrow-up"></i> 8% ce mois
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Statistiques</h2>
                        <div class="section-actions">
                            <button class="btn btn-secondary">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                        <div class="chart-container">
                            <canvas id="loansChart"></canvas>
                        </div>
                        <div class="chart-container">
                            <canvas id="objectsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Loans Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Prêts récents</h2>
                        <div class="section-actions">
                            <a href="prets.php" class="btn btn-secondary">Voir tout</a>
                            <a href="pret_form.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nouveau prêt
                            </a>
                        </div>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom du patient</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($activity['user']) ?></td>
                                        <td><?= htmlspecialchars($activity['description']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($activity['date'])) ?></td>
                                        <td><span class="badge badge-secondary">Info</span></td>
                                        <td>
                                            <a href="prets.php" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-inbox"></i>
                                        </div>
                                        <div class="empty-state-title">Aucune activité récente</div>
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

                <!-- Objects Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Objets récents</h2>
                        <div class="section-actions">
                            <a href="objets.php" class="btn btn-secondary">Voir tout</a>
                            <a href="objet_form.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nouvel objet
                            </a>
                        </div>
                    </div>

                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="empty-state-title">Aucun objet trouvé</div>
                        <div class="empty-state-description">Commencez par ajouter votre premier objet</div>
                        <a href="objet_form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Ajouter un objet
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

        // Chart initialization
        function initCharts() {
            // Loans per week chart
            const loansChartCtx = document.getElementById('loansChart');
            if (loansChartCtx) {
                new Chart(loansChartCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                        datasets: [{
                            label: 'Prêts par semaine',
                            data: [12, 19, 15, 25],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#2563eb'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    borderDash: [2, 2]
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // Objects status chart
            const objectsChartCtx = document.getElementById('objectsChart');
            if (objectsChartCtx) {
                new Chart(objectsChartCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Disponibles', 'Indisponibles'],
                        datasets: [{
                            data: [65, 35],
                            backgroundColor: ['#16a34a', '#dc2626'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }

        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', initCharts);
    </script>
</body>
</html>
<style>
/* ================= ROOT VARIABLES ================= */
:root {
    --primary-color: #3b82f6;
    --success-color: #22c55e;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;

    --white: #ffffff;
    --light-bg: #f8fafc;
    --border-color: #e2e8f0;

    --text-primary: #0f172a;
    --text-secondary: #64748b;
}

/* ================= RESET ================= */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* ================= LAYOUT ================= */
body {
    font-family: Arial, sans-serif;
    background: var(--light-bg);
}

/* ================= SIDEBAR ================= */
.sidebar-nav {
    width: 240px;
    height: 100vh;
    background-color: #1e293b;
    padding: 20px 0;
    position: fixed;
    left: 0;
    top: 0;
}

.sidebar-nav ul {
    list-style: none;
}

.sidebar-nav a {
    display: block;
    padding: 12px 20px;
    color: #cbd5f5;
    text-decoration: none;
    transition: 0.3s;
}

.sidebar-nav a:hover {
    background-color: #334155;
    color: #fff;
    padding-left: 26px;
}

.sidebar-nav a.active {
    background-color: var(--primary-color);
    color: #fff;
}

/* ================= CONTENT ================= */
.content {
    margin-left: 240px; /* FIX: prevents overlap with sidebar */
    padding: 2rem;
    max-width: 1200px;
}

.page-title {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.page-subtitle {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

/* ================= SUMMARY CARDS ================= */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: var(--white);
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid var(--border-color);
    transition: 0.3s;
}

.summary-card:hover {
    transform: translateY(-3px);
}

.summary-card-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.summary-card-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.summary-card.primary .summary-card-icon { background: var(--primary-color); }
.summary-card.success .summary-card-icon { background: var(--success-color); }
.summary-card.warning .summary-card-icon { background: var(--warning-color); }
.summary-card.danger .summary-card-icon { background: var(--danger-color); }

.summary-card-value {
    font-size: 1.8rem;
    font-weight: bold;
}

.summary-card-change.positive { color: var(--success-color); }
.summary-card-change.negative { color: var(--danger-color); }

/* ================= CONTENT SECTIONS ================= */
.content-section {
    background: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.section-title {
    font-size: 1.2rem;
    font-weight: bold;
}

/* ================= TABLE ================= */
.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: var(--light-bg);
    padding: 10px;
    text-align: left;
    font-size: 12px;
}

.table td {
    padding: 10px;
    border-top: 1px solid var(--border-color);
}

.table tr:hover {
    background: var(--light-bg);
}

/* ================= BUTTONS ================= */
.btn {
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-secondary {
    background: white;
    border: 1px solid var(--border-color);
}

/* ================= BADGES ================= */
.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
}

.badge-success { background: #dcfce7; color: var(--success-color); }
.badge-warning { background: #fef3c7; color: var(--warning-color); }
.badge-danger { background: #fee2e2; color: var(--danger-color); }

/* ================= EMPTY ================= */
.empty-state {
    text-align: center;
    padding: 2rem;
}

/* ================= MOBILE ================= */
@media (max-width: 768px) {
    .sidebar-nav {
        width: 200px;
    }

    .content {
        margin-left: 200px;
        padding: 1rem;
    }

    .summary-cards {
        grid-template-columns: 1fr;
    }
}
</style>