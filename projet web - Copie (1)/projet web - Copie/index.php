<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer le CRUD si l'action est demandée ou si on est sur la page ambulance, mission ou intervention
if(isset($_GET['action']) || (isset($_GET['page']) && in_array($_GET['page'], ['ambulance', 'mission', 'intervention']))) {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/controllers/AmbulanceController.php';
    require_once __DIR__ . '/controllers/MissionController.php';
    require_once __DIR__ . '/controllers/InterventionController.php';
    require_once __DIR__ . '/models/Ambulance.php';
    require_once __DIR__ . '/models/Mission.php';
    require_once __DIR__ . '/models/Intervention.php';

    $database = new Database();
    $db = $database->getConnection();

    if(!$db) {
        die("Erreur de connexion à la base de données");
    }

    $page = isset($_GET['page']) ? $_GET['page'] : 'ambulance';

    if ($page == 'mission') {
        $controller = new MissionController($db);
    } elseif ($page == 'intervention') {
        $controller = new InterventionController($db);
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
                $controller->planifier($id);
            }
            break;
        case 'annuler':
            if($page == 'intervention' && $id) {
                $controller->annuler($id);
            }
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
                    <a href="#" class="dropbtn">Flotte &amp; Missions &#x2B07;</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=ambulance">Gestion Ambulances</a>
                        <a href="index.php?page=mission">Registre Missions</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Bloc op&eacute;ratoire &#x2B07;</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=intervention">Interventions</a>
                    </div>
                </li>
                <li><a href="blog.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <img src="image acc.png" alt="Equipe Medicale MedChain" class="hero-img">
        </section>

        <section class="services-container">
            <a href="index.php?page=ambulance" class="service-card">
                <img src="Capture.PNG" alt="Icone Ambulance">
                <h3>Gestion des Ambulances</h3>
                <p>Gerez votre parc d'ambulances : ajout, modification, suppression et suivi.</p>
                <div class="btn-action">Acceder a la gestion</div>
            </a>

            <a href="index.php?page=mission" class="service-card">
                <img src="partage.PNG" alt="Icone Mission">
                <h3>Gestion des Missions</h3>
                <p>Affectez vos ambulances a des missions et suivez les trajets.</p>
                <div class="btn-action">Gerer les missions</div>
            </a>

            <a href="index.php?page=intervention" class="service-card">
                <img src="suivi.PNG" alt="Icone Intervention">
                <h3>Gestion des Interventions</h3>
                <p>Planifiez et suivez les interventions chirurgicales.</p>
                <div class="btn-action">Gerer les interventions</div>
            </a>
        </section>
    </main>

    <style>
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