<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
