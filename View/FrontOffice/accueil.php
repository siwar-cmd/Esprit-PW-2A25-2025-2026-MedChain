<?php
// accueil.php - Page d'accueil du frontoffice
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedChain | Accueil</title>
    <link rel="stylesheet" href="View/FrontOffice/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo"><a href="index.php"><img src="View/FrontOffice/logo.PNG" alt="MedChain Logo"></a></div>
            <ul class="nav-links">
                <li class="dropdown">
                    <a href="#" class="dropbtn">Flotte & Missions ⬇</a>
                    <div class="dropdown-content">
                        <a href="#">Gestion Ambulances</a>
                        <a href="#">Registre Missions</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Bloc opératoire ⬇</a>
                    <div class="dropdown-content">
                        <a href="#">Interventions</a>
                        <a href="#">Matériel Médical</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Traçabilité ⬇</a>
                    <div class="dropdown-content">
                        <a href="#">Lots Médicaments</a>
                        <a href="#">Distributions</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Rendez-vous ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=rdv&action=index">Agenda RDV</a>
                        <a href="index.php?page=ficherdv&action=index">Fiches de RDV</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Loisir ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=rdv&action=index">Agenda RDV</a>
                        <a href="index.php?page=ficherdv&action=index">Fiches de RDV</a>
                    </div>
                </li>
                <li><a href="/projet/View/BackOffice/backoffice_rdv.php">🔒 BackOffice</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <img src="View/FrontOffice/image acc.png" alt="Équipe Médicale MedChain" class="hero-img">
        </section>

        <section class="services-container">
            <a href="#" class="service-card">
                <img src="View/FrontOffice/Capture.PNG" alt="Icône Ambulance">
                <h3>Gestion des Ambulances</h3>
                <p>Gérez votre parc d'ambulances : ajout, modification, suppression et suivi.</p>
                <div class="btn-action">Accéder à la gestion</div>
            </a>

            <a href="#" class="service-card">
                <img src="View/FrontOffice/partage.PNG" alt="Icône Mission">
                <h3>Gestion des Missions</h3>
                <p>Affectez vos ambulances à des missions et suivez les trajets.</p>
                <div class="btn-action">Gérer les missions</div>
            </a>

            <a href="#" class="service-card">
                <img src="View/FrontOffice/partage.PNG" alt="Icône Partage">
                <h3>Partage Sécurisé</h3>
                <p>Partagez vos données avec vos praticiens en toute sécurité.</p>
                <div class="btn-action">Créer Votre Praticien</div>
            </a>

            <a href="#" class="service-card">
                <img src="View/FrontOffice/suivi.PNG" alt="Icône Suivi">
                <h3>Suivi Post-Opératoire</h3>
                <p>Un engagement qualité pour votre suivi santé après l'opération.</p>
                <div class="btn-action">Créer Engagements</div>
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
    .navbar {
        background-color: #2c3e50;
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .nav-links {
        display: flex;
        list-style: none;
        gap: 2rem;
    }
    .nav-links a {
        color: white;
        text-decoration: none;
    }
    .hero-img {
        width: 100%;
        height: auto;
    }
    .services-container {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        padding: 2rem;
        justify-content: center;
    }
    .service-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        text-decoration: none;
        color: #333;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.3s;
        width: 250px;
    }
    .service-card:hover {
        transform: translateY(-5px);
    }
    .service-card img {
        width: 80px;
        height: 80px;
        margin-bottom: 1rem;
    }
    .btn-action {
        background-color: #3498db;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        margin-top: 1rem;
        display: inline-block;
    }
    </style>
</body>
</html>