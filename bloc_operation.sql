-- Créer les tables pour Bloc Opération

-- Table Matériel
CREATE TABLE IF NOT EXISTS materiel (
    id_materiel INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    description LONGTEXT,
    quantite INT NOT NULL DEFAULT 0,
    prix_unitaire DECIMAL(10,2),
    fournisseur VARCHAR(255),
    date_acquisition DATE,
    statut ENUM('disponible', 'en_maintenance', 'hors_service') DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES utilisateur(id_utilisateur),
    INDEX idx_statut (statut),
    INDEX idx_nom (nom)
);

-- Table Intervention
CREATE TABLE IF NOT EXISTS intervention (
    id_intervention INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    description LONGTEXT,
    date_intervention DATETIME NOT NULL,
    medecin_id INT NOT NULL,
    patient_id INT,
    statut ENUM('planifiee', 'en_cours', 'terminee', 'annulee') DEFAULT 'planifiee',
    resultat_intervention LONGTEXT,
    notes_medecin LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medecin_id) REFERENCES utilisateur(id_utilisateur),
    FOREIGN KEY (patient_id) REFERENCES utilisateur(id_utilisateur),
    INDEX idx_medecin (medecin_id),
    INDEX idx_statut (statut),
    INDEX idx_date (date_intervention)
);

-- Table de liaison : Intervention <-> Matériel
CREATE TABLE IF NOT EXISTS intervention_materiel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    intervention_id INT NOT NULL,
    materiel_id INT NOT NULL,
    quantite_utilisee INT DEFAULT 1,
    FOREIGN KEY (intervention_id) REFERENCES intervention(id_intervention) ON DELETE CASCADE,
    FOREIGN KEY (materiel_id) REFERENCES materiel(id_materiel) ON DELETE CASCADE,
    UNIQUE KEY unique_intervention_materiel (intervention_id, materiel_id)
);
