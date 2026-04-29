<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partage Sécurisé - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .page-hero {
            background: linear-gradient(135deg, #1D9E75 0%, #0F6E56 100%);
            color: #fff;
            padding: 60px 5%;
            text-align: center;
        }
        .page-hero h1 { font-size: 2rem; margin-bottom: 12px; }
        .page-hero p  { font-size: 1.05rem; opacity: 0.9; max-width: 580px; margin: 0 auto; }

        .partage-wrapper {
            max-width: 780px;
            margin: 50px auto;
            padding: 0 20px;
        }
        .partage-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 36px 40px;
        }
        .partage-card h2 {
            color: var(--secondary);
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 1.3rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #444;
            margin-bottom: 7px;
            font-size: 0.95rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.97rem;
            font-family: inherit;
            color: #333;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .form-row { display: flex; gap: 18px; }
        .form-row .form-group { flex: 1; }
        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border: none;
            padding: 13px 32px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.88; }
        .info-box {
            background: #f0fdf8;
            border-left: 4px solid var(--primary);
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 28px;
            font-size: 0.92rem;
            color: #0F6E56;
        }
        .success-banner {
            display: none;
            background: #d1fae5;
            border: 1.5px solid var(--primary);
            border-radius: 10px;
            padding: 16px 22px;
            color: #065f46;
            font-weight: 600;
            margin-top: 20px;
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

    <div class="page-hero">
        <h1>🔐 Partage Sécurisé</h1>
        <p>Partagez vos données médicales avec vos praticiens en toute confidentialité.</p>
    </div>

    <div class="partage-wrapper">
        <div class="partage-card">
            <div class="info-box">
                🛡️ Toutes les données partagées sont chiffrées et accessibles uniquement au praticien désigné.
            </div>
            <h2>Créer un accès praticien</h2>
            <form id="partageForm" onsubmit="handleSubmit(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom du praticien</label>
                        <input type="text" placeholder="Dr. Dupont" required>
                    </div>
                    <div class="form-group">
                        <label>Spécialité</label>
                        <select>
                            <option value="">-- Sélectionner --</option>
                            <option>Médecin généraliste</option>
                            <option>Cardiologue</option>
                            <option>Chirurgien</option>
                            <option>Radiologue</option>
                            <option>Urgentiste</option>
                            <option>Autre</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email du praticien</label>
                    <input type="email" placeholder="praticien@hopital.fr" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Durée d'accès</label>
                        <select>
                            <option>24 heures</option>
                            <option>7 jours</option>
                            <option>30 jours</option>
                            <option>Accès permanent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type de données partagées</label>
                        <select>
                            <option>Dossier complet</option>
                            <option>Ordonnances uniquement</option>
                            <option>Résultats d'analyses</option>
                            <option>Compte-rendus opératoires</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Message au praticien (optionnel)</label>
                    <textarea placeholder="Ajoutez un message ou des instructions particulières..."></textarea>
                </div>
                <button type="submit" class="btn-submit">✉️ Envoyer l'invitation</button>
            </form>
            <div class="success-banner" id="successBanner">
                ✅ Invitation envoyée avec succès ! Le praticien recevra un lien d'accès sécurisé par email.
            </div>
        </div>
    </div>

    <script>
        function handleSubmit(e) {
            e.preventDefault();
            document.getElementById('successBanner').style.display = 'block';
            e.target.reset();
            setTimeout(() => document.getElementById('successBanner').style.display = 'none', 5000);
        }
    </script>
</body>
</html>
