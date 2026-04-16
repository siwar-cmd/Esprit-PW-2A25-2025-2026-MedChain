<?php
// Configuration et données dynamiques
session_start();

// Simulation de données provenant d'une base de données
$dashboard_data = [
    'dossiers_actifs' => 133,
    'partages_donnees' => 314,
    'alertes_critiques' => 30,
    'nouveaux_patients' => 19,
    'activites_hebdo' => [12, 19, 15, 25, 22, 30, 28],
    'jours' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
];

// Dernières activités (simulation)
$dernieres_activites = [
    [
        'date' => '12/07/2023',
        'couleur' => '#1D9E75',
        'activite' => 'Consultation patient',
        'utilisateur' => 'Dr. Sophie L.'
    ],
    [
        'date' => '11/02/2023',
        'couleur' => '#0F6E56',
        'activite' => 'Prescription médicale',
        'utilisateur' => 'Dr. Sophie L.'
    ],
    [
        'date' => '10/07/2023',
        'couleur' => '#1D9E75',
        'activite' => 'Audit de sécurité',
        'utilisateur' => 'Dr. Sophie L.'
    ],
    [
        'date' => '09/07/2023',
        'couleur' => '#FF6B6B',
        'activite' => 'Alerte médicament',
        'utilisateur' => 'Dr. Marc D.'
    ],
    [
        'date' => '08/07/2023',
        'couleur' => '#4ECDC4',
        'activite' => 'Partage de dossier',
        'utilisateur' => 'Dr. Julie M.'
    ]
];

// Liens de navigation
$nav_items = [
    'index.php' => ['icon' => '🏠', 'text' => 'Accueil', 'active' => true],
    'rdv_list.php' => ['icon' => '📅', 'text' => 'Rendez-vous', 'active' => false],
    'partage.php' => ['icon' => '🏥', 'text' => 'Intervention', 'active' => false],
    'alertes.php' => ['icon' => '💊', 'text' => 'Traçabilité des médicaments', 'active' => false],
    'rapports.php' => ['icon' => '🚑', 'text' => 'Ambulances et missions', 'active' => false],
    'utilisateurs.php' => ['icon' => '👥', 'text' => 'Utilisateurs', 'active' => false],
    'parametres.php' => ['icon' => '🎮', 'text' => 'Prêts de loisirs', 'active' => false]
];

// KPI Cards
$kpi_cards = [
    [
        'lien' => 'patients.php',
        'image' => 'suivi.PNG',
        'titre' => 'Dossiers Actifs',
        'valeur' => $dashboard_data['dossiers_actifs'],
        'classe' => ''
    ],
    [
        'lien' => 'partage.php',
        'image' => 'partage.PNG',
        'titre' => 'Partages Données',
        'valeur' => $dashboard_data['partages_donnees'],
        'classe' => ''
    ],
    [
        'lien' => 'alertes.php',
        'image' => 'alerte.PNG',
        'titre' => 'Alertes Critiques',
        'valeur' => $dashboard_data['alertes_critiques'],
        'classe' => 'text-alert'
    ],
    [
        'lien' => 'patients.php',
        'image' => 'nouv patient.PNG',
        'titre' => 'Nouveaux Patients',
        'valeur' => $dashboard_data['nouveaux_patients'],
        'classe' => ''
    ]
];

// Fonction pour formater les données pour JavaScript
function formatDataForChart($data) {
    return json_encode($data);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedChain | Dashboard Interactif</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <?php if (isset($_GET['debug']) && $_GET['debug'] == 1): ?>
    <style>
        .debug-panel {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            font-size: 12px;
            z-index: 9999;
            border-radius: 5px;
        }
    </style>
    <?php endif; ?>
</head>
<body>

    <div class="app-wrapper">
        
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="logo.PNG" alt="MedChain" class="img-logo">
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <?php foreach ($nav_items as $url => $item): ?>
                    <li <?php echo $item['active'] ? 'class="active"' : ''; ?>>
                        <a href="<?php echo $url; ?>" class="nav-item">
                            <?php echo $item['icon']; ?> <?php echo htmlspecialchars($item['text']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>

        <main class="content-main">
            
            <header class="content-header">
                <h1>Vue d'Ensemble Sécurisée</h1>
                <p>Bienvenue sur votre portail d'administration, <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Docteur'; ?>.</p>
                <?php if (isset($_GET['last_login'])): ?>
                <small>Dernière connexion : <?php echo htmlspecialchars($_GET['last_login']); ?></small>
                <?php endif; ?>
            </header>

            <section class="kpi-container">
                <?php foreach ($kpi_cards as $card): ?>
                <a href="<?php echo $card['lien']; ?>" class="card-kpi">
                    <img src="<?php echo $card['image']; ?>" alt="">
                    <div class="kpi-txt">
                        <h3><?php echo htmlspecialchars($card['titre']); ?></h3>
                        <p class="<?php echo $card['classe']; ?>"><?php echo $card['valeur']; ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </section>

            <div class="dashboard-grid">
                
                <section class="panel chart-box">
                    <h3>Statistiques d'activité</h3>
                    <div class="canvas-wrap">
                        <canvas id="activityChart"></canvas>
                    </div>
                </section>

                <section class="panel activity-box">
                    <h3>Dernières Activités</h3>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>ID</th>
                                    <th>Activité</th>
                                    <th>Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dernieres_activites as $activite): ?>
                                <tr>
                                    <td><?php echo $activite['date']; ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $activite['couleur']; ?>">
                                            <?php echo substr(md5($activite['couleur']), 0, 7); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($activite['activite']); ?></td>
                                    <td><?php echo htmlspecialchars($activite['utilisateur']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($dernieres_activites) > 3): ?>
                    <div class="view-all-link">
                        <a href="activites.php">Voir toutes les activités →</a>
                    </div>
                    <?php endif; ?>
                </section>

            </div>
        </main>
    </div>

    <script>
        // Données dynamiques injectées depuis PHP
        const chartData = {
            labels: <?php echo formatDataForChart($dashboard_data['jours']); ?>,
            values: <?php echo formatDataForChart($dashboard_data['activites_hebdo']); ?>
        };

        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Activités',
                    data: chartData.values,
                    borderColor: '#1D9E75',
                    backgroundColor: 'rgba(29, 158, 117, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#1D9E75'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Activités: ${context.raw}`;
                            }
                        }
                    }
                }
            }
        });

        // Rafraîchissement automatique des données (optionnel)
        <?php if (isset($_GET['auto_refresh']) && $_GET['auto_refresh'] == 1): ?>
        setTimeout(function() {
            location.reload();
        }, 30000); // Rafraîchit toutes les 30 secondes
        <?php endif; ?>
    </script>

    <?php if (isset($_GET['debug']) && $_GET['debug'] == 1): ?>
    <div class="debug-panel">
        <strong>Debug Info:</strong><br>
        PHP Version: <?php echo phpversion(); ?><br>
        Session ID: <?php echo session_id(); ?><br>
        Dernière MAJ: <?php echo date('H:i:s'); ?>
    </div>
    <?php endif; ?>
</body>
</html>