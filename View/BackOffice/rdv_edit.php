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
    <title>MedChain | Modifier Rendez-vous</title>
    <link rel="stylesheet" href="style.css">
    
    <style>
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #FF9800;
        }
        .btn-submit {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-right: 10px;
            font-size: 14px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }
        .btn-back {
            background: #666;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            font-size: 14px;
        }
        .btn-back:hover {
            background: #555;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        .message-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        small {
            color: #666;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }
        .content-header {
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-planifie { background: #2196F3; color: white; }
        .status-confirme { background: #1D9E75; color: white; }
        .status-annule { background: #e74c3c; color: white; }
        .status-reporte { background: #FF9800; color: white; }
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
                <h1>Modifier Rendez-vous #<?php echo $id; ?></h1>
                <p>Modifiez les informations du rendez-vous</p>
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

            <?php if(isset($_GET['error'])): ?>
                <div class="message-error">
                    <?php 
                        if($_GET['error'] == 'date_invalid') echo "⚠ Les dates sont invalides. Vérifiez que la date de fin est après la date de début.";
                        if($_GET['error'] == 'db_error') echo "⚠ Erreur lors de la modification.";
                    ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <strong>📌 Statut actuel :</strong> 
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

            <div class="form-container">
                <form method="POST" action="rdv_update.php?id=<?php echo $id; ?>">
                    <div class="form-group">
                        <label>Date et heure de début</label>
                        <input type="text" name="dateHeureDebut" value="<?php echo $rdv->dateHeureDebut; ?>" placeholder="AAAA-MM-JJ HH:MM:SS">
                        <small>Format: 2024-12-25 14:30:00</small>
                    </div>

                    <div class="form-group">
                        <label>Date et heure de fin</label>
                        <input type="text" name="dateHeureFin" value="<?php echo $rdv->dateHeureFin; ?>" placeholder="AAAA-MM-JJ HH:MM:SS">
                        <small>Format: 2024-12-25 15:30:00</small>
                    </div>

                    <div class="form-group">
                        <label>Type de consultation</label>
                        <select name="typeConsultation">
                            <option value="Consultation générale" <?php echo $rdv->typeConsultation == 'Consultation générale' ? 'selected' : ''; ?>>🏥 Consultation générale</option>
                            <option value="Consultation spécialiste" <?php echo $rdv->typeConsultation == 'Consultation spécialiste' ? 'selected' : ''; ?>>👨‍⚕️ Consultation spécialiste</option>
                            <option value="Téléconsultation" <?php echo $rdv->typeConsultation == 'Téléconsultation' ? 'selected' : ''; ?>>💻 Téléconsultation</option>
                            <option value="Urgence" <?php echo $rdv->typeConsultation == 'Urgence' ? 'selected' : ''; ?>>🚨 Urgence</option>
                            <option value="Contrôle" <?php echo $rdv->typeConsultation == 'Contrôle' ? 'selected' : ''; ?>>📋 Contrôle</option>
                            <option value="Suivi post-opératoire" <?php echo $rdv->typeConsultation == 'Suivi post-opératoire' ? 'selected' : ''; ?>>🏥 Suivi post-opératoire</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Motif</label>
                        <textarea name="motif" rows="4" placeholder="Décrivez le motif de la consultation..."><?php echo htmlspecialchars($rdv->motif); ?></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-submit">💾 Enregistrer</button>
                        <a href="rdv_list.php" class="btn-back">← Annuler</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

</body>
</html>