-- ============================================
-- BLOC OPÉRATION - CRÉATION DES TABLES
-- ============================================
-- À exécuter dans phpMyAdmin ou CLI MySQL

-- Table Matériel
DROP TABLE IF EXISTS materiel;
CREATE TABLE materiel (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table Intervention
DROP TABLE IF EXISTS intervention;
CREATE TABLE intervention (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de test pour matériel
INSERT INTO materiel (nom, categorie, disponibilite, statutSterilisation, nombreUtilisationsMax, nombreUtilisationsActuelles) 
VALUES 
('Bistouri Électrique', 'Instruments', 'Disponible', 'Stérilisé', 100, 45),
('Écarteur', 'Instruments', 'Disponible', 'Stérilisé', 50, 20);

-- Données de test pour intervention
INSERT INTO intervention (type, date_intervention, duree, niveau_urgence, chirurgien, salle, description) 
VALUES 
('Chirurgie générale', '2026-05-10 08:00:00', 120, 2, 'Dr. Jean Dupont', 'Salle A', 'Intervention de routine'),
('Chirurgie cardiaque', '2026-05-11 14:00:00', 180, 4, 'Dr. Marie Laurent', 'Salle B', 'Intervention urgente');
