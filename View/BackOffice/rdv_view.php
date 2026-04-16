<?php
session_start();
require_once '../../Model/rdv.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$rdv = new Rdv();
$rdv->idRDV = $id;
$rdv->readOne();

// Simulation de données pour les KPI
$dashboard_data = [
    'dossiers_actifs' => 133,
    'partages_donnees' => 314,
    'alertes_critiques' => 30,
    'nouveaux_patients' => 19,
];

// Liens de navigation
$nav_items = [
    'backoffice_rdv.php' => ['icon' => '🏠', 'text' => 'Accueil', 'active' => false],
    'rdv_list.php' => ['icon' => '📅', 'text' => 'Rendez-vous', 'active' => true],
    'partage.php' => ['icon' => '🏥', 'text' => 'Intervention', 'active' => false],
    'alertes.php' => ['icon' => '💊', 'text' => 'Traçabilité des médicaments', 'active' => false],
    'rapports.php' => ['icon' => '🚑', 'text' => 'Ambulances et missions', 'active' => false],
    'utilisateurs.php' => ['icon' => '👥', 'text' => 'Utilisateurs', 'active' => false],
    'parametres.php' => ['icon' => '⚙️', 'text' => 'Paramètres', 'active' => false],
    '../../index.php' => ['icon' => '🚪', 'text' => 'Retour FrontOffice', 'active' => false]
];

// KPI Cards
$kpi_cards = [
    [
        'lien' => 'rdv_list.php',
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
        'lien' => 'rdv_list.php',
        'image' => 'nouv patient.PNG',
        'titre' => 'Nouveaux Patients',
        'valeur' => $dashboard_data['nouveaux_patients'],
        'classe' => ''
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedChain | Détails du Rendez-vous</title>
    <link rel="stylesheet" href="style.css">
    
    <style>
        .details-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 700px;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .detail-row {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #eef2f4;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            width: 180px;
            color: #555;
        }
        .detail-value {
            flex: 1;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
        }
        .status-planifie { background: #2196F3; color: white; }
        .status-confirme { background: #1D9E75; color: white; }
        .status-annule { background: #e74c3c; color: white; }
        .status-reporte { background: #FF9800; color: white; }
        .actions {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eef2f4;
        }
        .btn {
            padding: 10px 25px;
            text-decoration: none;
            border-radius: 8px;
            margin: 5px;
            display: inline-block;
            font-size: 14px;
        }
        .btn-edit {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            color: white;
        }
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }
        .btn-back {
            background: #666;
            color: white;
        }
        .btn-back:hover {
            background: #555;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-delete:hover {
            background: #c0392b;
        }
        .content-header {
            margin-bottom: 20px;
        }
        .motif-text {
            background: #f8fafb;
            padding: 12px;
            border-radius: 8px;
            margin-top: 5px;
            line-height: 1.5;
        }
    </style>
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
                <h1>Détails du Rendez-vous #<?php echo $id; ?></h1>
                <p>Consultez les informations complètes du rendez-vous</p>
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

            <div class="details-container">
                <div class="detail-row">
                    <div class="detail-label">📅 Date et heure de début :</div>
                    <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($rdv->dateHeureDebut)); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">⏰ Date et heure de fin :</div>
                    <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($rdv->dateHeureFin)); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">📌 Statut :</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo $rdv->statut; ?>">
                            <?php 
                                $statusLabels = [
                                    'planifie' => '📅 Planifié',
                                    'confirme' => '✅ Confirmé',
                                    'annule' => '❌ Annulé',
                                    'reporte' => '🔄 Reporté'
                                ];
                                echo $statusLabels[$rdv->statut] ?? $rdv->statut;
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">🏥 Type de consultation :</div>
                    <div class="detail-value"><?php echo htmlspecialchars($rdv->typeConsultation); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">📝 Motif :</div>
                    <div class="detail-value">
                        <?php if(!empty($rdv->motif)): ?>
                            <div class="motif-text"><?php echo nl2br(htmlspecialchars($rdv->motif)); ?></div>
                        <?php else: ?>
                            <span style="color: #999;">Non spécifié</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="actions">
                    <a href="rdv_edit.php?id=<?php echo $id; ?>" class="btn btn-edit">✏ Modifier</a>
                    <a href="rdv_list.php" class="btn btn-back">← Retour</a>
                    <a href="rdv_delete.php?id=<?php echo $id; ?>" class="btn btn-delete" onclick="return confirm('Supprimer définitivement ce rendez-vous ?')">🗑 Supprimer</a>
                </div>
            </div>
        </main>
    </div>

</body>
</html>