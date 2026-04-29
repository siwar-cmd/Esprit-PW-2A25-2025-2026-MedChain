<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loisir - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .loisir-container {
            max-width: 900px;
            margin: 60px auto;
            padding: 0 20px;
            text-align: center;
        }
        .loisir-container h1 {
            color: var(--secondary);
            font-size: 2.2rem;
            margin-bottom: 15px;
        }
        .loisir-container p {
            color: #666;
            font-size: 1.05rem;
            margin-bottom: 40px;
        }
        .loisir-cards {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .loisir-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
            padding: 30px 25px;
            width: 240px;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid transparent;
        }
        .loisir-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 28px rgba(0,0,0,0.13);
            border-color: var(--accent);
        }
        .loisir-card .icon {
            font-size: 2.8rem;
            margin-bottom: 15px;
        }
        .loisir-card h3 {
            color: var(--secondary);
            margin: 0 0 10px;
        }
        .loisir-card p {
            font-size: 0.9rem;
            color: #888;
            margin: 0;
        }
    </style>
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
                <li><a href="loisir.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="loisir-container">
            <h1>🎉 Espace Loisir</h1>
            <p>Détendez-vous et découvrez notre sélection d'activités pour le bien-être du personnel médical.</p>
            <div class="loisir-cards">
                <div class="loisir-card">
                    <div class="icon">🧘</div>
                    <h3>Bien-être</h3>
                    <p>Séances de relaxation et méditation pour le personnel soignant.</p>
                </div>
                <div class="loisir-card">
                    <div class="icon">🏃</div>
                    <h3>Sport</h3>
                    <p>Activités sportives et programme de remise en forme adaptés.</p>
                </div>
                <div class="loisir-card">
                    <div class="icon">📚</div>
                    <h3>Culture</h3>
                    <p>Bibliothèque médicale et ressources culturelles en ligne.</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
