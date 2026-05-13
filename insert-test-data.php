<?php
/**
 * Script d'insertion de données de test pour Bloc Opération
 * À accéder via: localhost/projet/projet/insert-test-data.php
 */

session_start();

// Redirection si pas admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('<h1 style="color: red;">❌ Accès refusé - Vous devez être admin</h1>');
}

require_once __DIR__ . '/config.php';

$pdo = config::getConnexion();
$messages = [];
$errors = [];

// ===== INSERTION MATÉRIEL =====
try {
    // Vérifier si la table est vide
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM materiel");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($count == 0) {
        $pdo->exec("INSERT INTO materiel (nom, categorie, disponibilite, statutSterilisation, nombreUtilisationsMax, nombreUtilisationsActuelles) VALUES 
            ('Bistouri Électrique', 'Instruments de coupe', 'Disponible', 'Stérilisé', 100, 45),
            ('Écarteur Automatique', 'Instruments de retraction', 'Disponible', 'Stérilisé', 50, 20),
            ('Pince Hémostatique', 'Instruments de saisie', 'Disponible', 'Stérilisé', 200, 85),
            ('Seringue 10ml', 'Consommables', 'Disponible', 'Non stérilisé', 500, 150),
            ('Gants Chirurgicaux', 'Équipement de protection', 'Disponible', 'Non stérilisé', 1000, 400),
            ('Masque Respiratoire', 'Équipement de protection', 'Disponible', 'Non stérilisé', 800, 300),
            ('Champs Stériles', 'Textiles', 'Disponible', 'Stérilisé', 300, 120),
            ('Compresses Stériles', 'Consommables', 'Disponible', 'Stérilisé', 600, 250)");
        
        $messages[] = "✅ 8 matériels de test créés avec succès";
    } else {
        $messages[] = "ℹ️ Table materiel contient déjà $count matériels";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Erreur insertion matériel: " . $e->getMessage();
}

// ===== INSERTION INTERVENTION =====
try {
    // Vérifier si la table est vide
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM intervention");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($count == 0) {
        $pdo->exec("INSERT INTO intervention (type, date_intervention, duree, niveau_urgence, chirurgien, salle, description) VALUES 
            ('Chirurgie générale', '2026-05-10 08:00:00', 120, 2, 'Dr. Jean Dupont', 'Salle A', 'Appendicectomie programmée'),
            ('Chirurgie cardiaque', '2026-05-11 14:00:00', 180, 4, 'Dr. Marie Laurent', 'Salle B', 'Intervention cardiaque urgente'),
            ('Chirurgie plastique', '2026-05-12 10:00:00', 90, 1, 'Dr. Pierre Martin', 'Salle C', 'Chirurgie esthétique'),
            ('Chirurgie vasculaire', '2026-05-13 11:00:00', 150, 3, 'Dr. Jean Dupont', 'Salle A', 'Bypass artériel'),
            ('Chirurgie thoracique', '2026-05-14 15:00:00', 200, 4, 'Dr. Sophie Bernard', 'Salle B', 'Lobectomie pulmonaire urgente'),
            ('Orthopédie', '2026-05-15 09:00:00', 110, 2, 'Dr. Marc Durand', 'Salle C', 'Fixation de fracture'),
            ('Neurochirurgie', '2026-05-16 08:30:00', 240, 5, 'Dr. Luc Garnier', 'Salle A', 'Ablation de tumeur cérébrale'),
            ('Urogynécologie', '2026-05-17 13:00:00', 75, 1, 'Dr. Anne Leclerc', 'Salle B', 'Intervention mineure programmée')");
        
        $messages[] = "✅ 8 interventions de test créées avec succès";
    } else {
        $messages[] = "ℹ️ Table intervention contient déjà $count interventions";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Erreur insertion intervention: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insertion données - Bloc Opération</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1D9E75;
            border-bottom: 2px solid #1D9E75;
            padding-bottom: 10px;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #4CAF50;
            background: #f1f8f4;
            color: #2d6a3e;
        }
        .error {
            border-left-color: #f44336;
            background: #fdf1f0;
            color: #7d3a35;
        }
        .button {
            margin-top: 30px;
        }
        a {
            display: inline-block;
            padding: 12px 24px;
            background: #1D9E75;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        a:hover {
            background: #0F6E56;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Insertion de données de test</h1>
        
        <?php foreach ($messages as $msg): ?>
            <div class="message"><?= $msg ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($errors as $err): ?>
            <div class="message error"><?= $err ?></div>
        <?php endforeach; ?>
        
        <div class="button">
            <a href="views/backoffice/bloc-operation/materiel-index.php">📋 Voir les Matériels</a>
            <a href="views/backoffice/bloc-operation/intervention-index.php">🔨 Voir les Interventions</a>
        </div>
    </div>
</body>
</html>
