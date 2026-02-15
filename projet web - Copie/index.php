<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer le CRUD si l'action est demandée ou si on est sur la page ambulance ou mission ou intervention ou materiel ou rdv ou ficherdv ou lot ou distribution
if(isset($_GET['action']) || (isset($_GET['page']) && in_array($_GET['page'], ['ambulance', 'mission', 'intervention', 'materiel', 'rdv', 'ficherdv', 'lot', 'distribution']))) {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/controllers/AmbulanceController.php';
    require_once __DIR__ . '/controllers/MissionController.php';
    require_once __DIR__ . '/controllers/InterventionController.php';
    require_once __DIR__ . '/controllers/MaterielController.php';
    require_once __DIR__ . '/controllers/RdvController.php';
    require_once __DIR__ . '/controllers/FicheRdvController.php';
    require_once __DIR__ . '/controllers/LotController.php';
    require_once __DIR__ . '/controllers/DistributionController.php';
    require_once __DIR__ . '/models/Ambulance.php';
    require_once __DIR__ . '/models/Mission.php';
    require_once __DIR__ . '/models/Intervention.php';
    require_once __DIR__ . '/models/MaterielChirurgical.php';
    require_once __DIR__ . '/models/RendezVous.php';
    require_once __DIR__ . '/models/FicheRendezVous.php';
    require_once __DIR__ . '/models/LotMedicament.php';
    require_once __DIR__ . '/models/Distribution.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Vérifier si la connexion est réussie
    if(!$db) {
        die("Erreur de connexion à la base de données");
    }
    
    $page = isset($_GET['page']) ? $_GET['page'] : 'ambulance';
    if ($page == 'mission') {
        $controller = new MissionController($db);
    } elseif ($page == 'intervention') {
        $controller = new InterventionController($db);
    } elseif ($page == 'materiel') {
        $controller = new MaterielController($db);
    } elseif ($page == 'rdv') {
        $controller = new RdvController($db);
    } elseif ($page == 'ficherdv') {
        $controller = new FicheRdvController($db);
    } elseif ($page == 'lot') {
        $controller = new LotController($db);
    } elseif ($page == 'distribution') {
        $controller = new DistributionController($db);
    } else {
        $controller = new AmbulanceController($db);
    }
    
    $action = isset($_GET['action']) ? $_GET['action'] : 'index';
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    switch($action) {
        case 'index':
            $controller->index();
            break;
        case 'create':
            $controller->create();
            break;
        case 'store':
            $controller->store();
            break;
        case 'edit':
            if($id) $controller->edit($id);
            else header("Location: index.php?page=$page");
            break;
        case 'update':
            if($id) $controller->update($id);
            else header("Location: index.php?page=$page");
            break;
        case 'delete':
            if($id) $controller->delete($id);
            else header("Location: index.php?page=$page");
            break;
        case 'show':
            if(method_exists($controller, 'show') && $id) $controller->show($id);
            else header("Location: index.php?page=$page");
            break;
        case 'search':
            if(method_exists($controller, 'search')) $controller->search();
            else $controller->index();
            break;
        case 'stats':
            $controller->stats();
            break;
        case 'planifier':
            if($page == 'intervention' && $id) {
                // Instantiation controller handles method
                $controller->planifier($id);
            }
            break;
        case 'annuler':
            if($page == 'intervention' && $id) {
                $controller->annuler($id);
            } elseif ($page == 'rdv' && $id) {
                // RDV controller also has annuler
                $controller->actionAnnuler($id);
            }
            break;
        case 'confirmer':
            if($page == 'rdv' && $id) $controller->actionConfirmer($id);
            break;
        case 'reporter':
            if($page == 'rdv' && $id) $controller->actionReporter($id);
            break;
        case 'marquerGenere':
            if($page == 'ficherdv' && $id) $controller->marquerGenere($id);
            break;
        case 'marquerEmail':
            if($page == 'ficherdv' && $id) $controller->marquerEmailEnvoye($id);
            break;
        case 'marquerCalendrier':
            if($page == 'ficherdv' && $id) $controller->marquerCalendrier($id);
            break;
        default:
            $controller->index();
            break;
    }
} else {
    // Afficher la page d'accueil
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>MedChain | Accueil</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header>
            <nav class="navbar">
                <div class="logo"><a href="index.php"><img src="logo.PNG" alt="MedChain Logo"></a></div>
                <ul class="nav-links">
                    <li class="dropdown">
                        <a href="#" class="dropbtn">Flotte & Missions ⬇</a>
                        <div class="dropdown-content">
                            <a href="index.php?page=ambulance">Gestion Ambulances</a>
                            <a href="index.php?page=mission">Registre Missions</a>
                        </div>
                    </li>
                    <li class="dropdown">
                        <a href="#" class="dropbtn">Bloc opératoire ⬇</a>
                        <div class="dropdown-content">
                            <a href="index.php?page=intervention">Interventions</a>
                            <a href="index.php?page=materiel">Matériel Médical</a>
                        </div>
                    </li>
                    <li class="dropdown">
                        <a href="#" class="dropbtn">Traçabilité ⬇</a>
                        <div class="dropdown-content">
                            <a href="index.php?page=lot">Lots Médicaments</a>
                            <a href="index.php?page=distribution">Distributions</a>
                        </div>
                    </li>
                    <li class="dropdown">
                        <a href="#" class="dropbtn">Rendez-vous ⬇</a>
                        <div class="dropdown-content">
                            <a href="index.php?page=rdv">Agenda RDV</a>
                            <a href="index.php?page=ficherdv">Fiches de RDV</a>
                        </div>
                    </li>
                    <li><a href="blog.php">Loisir</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="hero">
                <img src="image acc.png" alt="Équipe Médicale MedChain" class="hero-img">
            </section>

            <section class="services-container">
                <a href="index.php?page=ambulance" class="service-card">
                    <img src="Capture.PNG" alt="Icône Ambulance">
                    <h3>Gestion des Ambulances</h3>
                    <p>Gérez votre parc d'ambulances : ajout, modification, suppression et suivi.</p>
                    <div class="btn-action">Accéder à la gestion</div>
                </a>

                <a href="index.php?page=mission" class="service-card">
                    <img src="partage.PNG" alt="Icône Mission">
                    <h3>Gestion des Missions</h3>
                    <p>Affectez vos ambulances à des missions et suivez les trajets.</p>
                    <div class="btn-action">Gérer les missions</div>
                </a>

                <a href="partage.php" class="service-card">
                    <img src="partage.PNG" alt="Icône Partage">
                    <h3>Partage Sécurisé</h3>
                    <p>Partagez vos données avec vos praticiens en toute sécurité.</p>
                    <div class="btn-action">Créer Votre Praticien</div>
                </a>

                <a href="suivi.php" class="service-card">
                    <img src="suivi.PNG" alt="Icône Suivi">
                    <h3>Suivi Post-Opératoire</h3>
                    <p>Un engagement qualité pour votre suivi santé après l'opération.</p>
                    <div class="btn-action">Créer Engagements</div>
                </a>
            </section>
        </main>
        
        <style>
        /* Ajout de style rapide pour le dropdown du menu sur l'accueil */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 100;
            border-radius: 5px;
            overflow: hidden;
        }
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        </style>
    </body>
    </html>
    <?php
}
?>