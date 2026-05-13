<?php
/**
 * Script de vérification et création des tables Bloc Opération
 * À accéder via: localhost/projet/projet/setup-bloc-operation.php
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

// ===== VÉRIFICATION TABLE MATERIEL =====
try {
    $stmt = $pdo->query("DESCRIBE materiel");
    $materielColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $messages[] = "✅ Table materiel existe (" . count($materielColumns) . " colonnes)";
    
    // Vérifier les colonnes requises
    $columnNames = array_column($materielColumns, 'Field');
    $requiredColumns = ['idMateriel', 'nom', 'categorie', 'disponibilite', 'statutSterilisation', 'nombreUtilisationsMax', 'nombreUtilisationsActuelles'];
    
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columnNames)) {
            $errors[] = "❌ Colonne manquante dans materiel: $col";
        }
    }
    
    if (empty($errors)) {
        $messages[] = "✅ Toutes les colonnes requises existent";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Table materiel n'existe pas: " . $e->getMessage();
}

// ===== VÉRIFICATION TABLE INTERVENTION =====
try {
    $stmt = $pdo->query("DESCRIBE intervention");
    $interventionColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $messages[] = "✅ Table intervention existe (" . count($interventionColumns) . " colonnes)";
    
    // Vérifier les colonnes requises
    $columnNames = array_column($interventionColumns, 'Field');
    $requiredColumns = ['id', 'type', 'date_intervention', 'duree', 'niveau_urgence', 'chirurgien', 'salle', 'description'];
    
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columnNames)) {
            $errors[] = "❌ Colonne manquante dans intervention: $col";
        }
    }
    
    if (empty($errors)) {
        $messages[] = "✅ Toutes les colonnes requises existent";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Table intervention n'existe pas: " . $e->getMessage();
}

// ===== VÉRIFICATION DONNÉES =====
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM materiel");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $messages[] = "📊 Materiel: $count enregistrements";
} catch (Exception $e) {
    // Table doesn't exist
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM intervention");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $messages[] = "📊 Intervention: $count enregistrements";
} catch (Exception $e) {
    // Table doesn't exist
}

// ===== CRÉATION AUTOMATIQUE =====
if (!empty($errors)) {
    $messages[] = "🔧 Création des tables requises...";
    
    try {
        // Créer materiel
        $pdo->exec("DROP TABLE IF EXISTS materiel");
        $pdo->exec("CREATE TABLE materiel (
            idMateriel INT PRIMARY KEY AUTO_INCREMENT,
            nom VARCHAR(255) NOT NULL,
            categorie VARCHAR(100),
            disponibilite VARCHAR(50),
            statutSterilisation VARCHAR(50),
            nombreUtilisationsMax INT DEFAULT 0,
            nombreUtilisationsActuelles INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_nom (nom),
            INDEX idx_categorie (categorie)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $messages[] = "✅ Table materiel créée";
        
        // Créer intervention
        $pdo->exec("DROP TABLE IF EXISTS intervention");
        $pdo->exec("CREATE TABLE intervention (
            id INT PRIMARY KEY AUTO_INCREMENT,
            type VARCHAR(255),
            date_intervention DATETIME,
            duree INT,
            niveau_urgence INT DEFAULT 2,
            chirurgien VARCHAR(255),
            salle VARCHAR(100),
            description LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_chirurgien (chirurgien),
            INDEX idx_date (date_intervention),
            INDEX idx_niveau_urgence (niveau_urgence)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $messages[] = "✅ Table intervention créée";
        
        // Insérer données de test pour matériel
        $pdo->exec("INSERT INTO materiel (nom, categorie, disponibilite, statutSterilisation, nombreUtilisationsMax, nombreUtilisationsActuelles) VALUES 
            ('Bistouri Électrique', 'Instruments de coupe', 'Disponible', 'Stérilisé', 100, 45),
            ('Écarteur Automatique', 'Instruments de retraction', 'Disponible', 'Stérilisé', 50, 20),
            ('Pince Hémostatique', 'Instruments de saisie', 'Disponible', 'Stérilisé', 200, 85),
            ('Seringue 10ml', 'Consommables', 'Disponible', 'Non stérilisé', 500, 150),
            ('Gants Chirurgicaux', 'Équipement de protection', 'Disponible', 'Non stérilisé', 1000, 400),
            ('Masque Respiratoire', 'Équipement de protection', 'Disponible', 'Non stérilisé', 800, 300),
            ('Champs Stériles', 'Textiles', 'Disponible', 'Stérilisé', 300, 120),
            ('Compresses Stériles', 'Consommables', 'Disponible', 'Stérilisé', 600, 250)");
        
        $messages[] = "✅ 8 matériels de test créés";
        
        // Insérer données de test pour interventions
        $pdo->exec("INSERT INTO intervention (type, date_intervention, duree, niveau_urgence, chirurgien, salle, description) VALUES 
            ('Chirurgie générale', '2026-05-10 08:00:00', 120, 2, 'Dr. Jean Dupont', 'Salle A', 'Appendicectomie programmée'),
            ('Chirurgie cardiaque', '2026-05-11 14:00:00', 180, 4, 'Dr. Marie Laurent', 'Salle B', 'Intervention cardiaque urgente'),
            ('Chirurgie plastique', '2026-05-12 10:00:00', 90, 1, 'Dr. Pierre Martin', 'Salle C', 'Chirurgie esthétique'),
            ('Chirurgie vasculaire', '2026-05-13 11:00:00', 150, 3, 'Dr. Jean Dupont', 'Salle A', 'Bypass artériel'),
            ('Chirurgie thoracique', '2026-05-14 15:00:00', 200, 4, 'Dr. Sophie Bernard', 'Salle B', 'Lobectomie pulmonaire urgente'),
            ('Orthopédie', '2026-05-15 09:00:00', 110, 2, 'Dr. Marc Durand', 'Salle C', 'Fixation de fracture'),
            ('Neurochirurgie', '2026-05-16 08:30:00', 240, 5, 'Dr. Luc Garnier', 'Salle A', 'Ablation de tumeur cérébrale'),
            ('Urogynécologie', '2026-05-17 13:00:00', 75, 1, 'Dr. Anne Leclerc', 'Salle B', 'Intervention mineure programmée')");
        
        $messages[] = "✅ 8 interventions de test créées";
        
    } catch (Exception $e) {
        $errors[] = "❌ Erreur lors de la création: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Bloc Opération</title>
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
            margin-top: 20px;
        }
        a {
            display: inline-block;
            padding: 10px 20px;
            background: #1D9E75;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 10px;
        }
        a:hover {
            background: #0F6E56;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Configuration - Bloc Opération</h1>
        
        <?php foreach ($messages as $msg): ?>
            <div class="message"><?= $msg ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($errors as $err): ?>
            <div class="message error"><?= $err ?></div>
        <?php endforeach; ?>
        
        <?php if (empty($errors)): ?>
            <div class="message" style="border-left-color: #2196F3; background: #e3f2fd; color: #0d47a1;">
                ✅ Toutes les tables sont correctement configurées!
            </div>
        <?php endif; ?>
        
        <div class="button">
            <a href="views/backoffice/bloc-operation/materiel-index.php">📋 Aller aux Matériels</a>
            <a href="views/backoffice/bloc-operation/intervention-index.php">🔨 Aller aux Interventions</a>
            <a href="views/backoffice/admin-dashboard.php">🏠 Retour au Dashboard</a>
        </div>
    </div>
</body>
</html>
