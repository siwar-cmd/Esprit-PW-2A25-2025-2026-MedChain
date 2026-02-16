<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le chemin de base
define('BASE_PATH', __DIR__);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedChain | Accueil</title>
    <link rel="stylesheet" href="../templete_front/style.css">
    <style>
        .nav-auth {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 20px;
        }

        .btn-connexion,
        .btn-inscription {
            display: inline-block;
            padding: 9px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-connexion {
            background: transparent;
            border: 2px solid #1D9E75;
            color: #1D9E75;
        }

        .btn-connexion:hover {
            background: #1D9E75;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29,158,117,0.25);
        }

        .btn-inscription {
            background: linear-gradient(135deg, #1D9E75, #0F6E56);
            border: 2px solid transparent;
            color: #fff;
        }

        .btn-inscription:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(15,110,86,0.35);
        }

        /* Si déjà connecté — affichage du nom + déconnexion */
        .user-greeting {
            font-size: 14px;
            color: #0F6E56;
            font-weight: 500;
        }

        .btn-deconnexion {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            background: #e74c3c;
            color: #fff;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-deconnexion:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <header>
        <nav class="navbar">
            <div class="logo">
                <img src="../templete_front/logo.PNG" alt="MedChain Logo">
            </div>
            <ul class="nav-links">
                <li><a href="innovation.php" target="_blank">L'Innovation</a></li>
                <li><a href="fonctionnalites.php" target="_blank">Fonctionnalités</a></li>
                <li><a href="securite.php" target="_blank">Sécurité Blockchain</a></li>
                <li><a href="cas_usage.php" target="_blank">Cas d'Usage</a></li>
                <li><a href="blog.php" target="_blank">Blog</a></li>
            </ul>

            <!-- Boutons Connexion / Inscription ou accueil utilisateur -->
            <div class="nav-auth">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="user-greeting">
                        Bonjour, <?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Utilisateur'); ?>
                    </span>
                    <a href="../profile.php" class="btn-connexion">Mon Profil</a>
                    <a href="../auth/logout.php" class="btn-deconnexion">Déconnexion</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn-connexion">Connexion</a>
                    <a href="../auth/register.php" class="btn-inscription">Inscription</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <img src="../templete_front/image acc.png" alt="Équipe Médicale MedChain" class="hero-img">
        </section>

        <section class="services-container">

            <a href="../templete_front/dossier.php" class="service-card">
                <img src="../templete_front/Capture.PNG" alt="Icône Dossier">
                <h3>Accès Dossier</h3>
                <p>Gérez votre santé et celle de votre famille en toute simplicité.</p>
                <div class="btn-action">Créer Mon Compte Patient</div>
            </a>

            <a href="../templete_front/partage.php" class="service-card">
                <img src="../templete_front/partage.PNG" alt="Icône Partage">
                <h3>Partage Sécurisé</h3>
                <p>Partagez vos données avec vos praticiens en toute sécurité.</p>
                <div class="btn-action">Créer Votre Praticien</div>
            </a>

            <a href="../templete_front/suivi.php" class="service-card">
                <img src="../templete_front/suivi.PNG" alt="Icône Suivi">
                <h3>Suivi Post-Opératoire</h3>
                <p>Un engagement qualité pour votre suivi santé après l'opération.</p>
                <div class="btn-action">Créer Engagements</div>
            </a>

        </section>
    </main>

</body>
</html>