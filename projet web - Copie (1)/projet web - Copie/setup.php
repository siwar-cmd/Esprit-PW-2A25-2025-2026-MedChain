<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    die("Erreur de connexion à la base de données");
}

$queries = [
    // Table Lot
    "CREATE TABLE IF NOT EXISTS Lot_medicament_sensible (
        idLot INT AUTO_INCREMENT PRIMARY KEY,
        nomMedicament VARCHAR(100),
        numeroLot VARCHAR(50),
        quantite INT,
        datePeremption DATE
    )",
    // Table Distribution
    "CREATE TABLE IF NOT EXISTS Distribution_controlee (
        idDistribution INT AUTO_INCREMENT PRIMARY KEY,
        idLot INT,
        quantite INT,
        dateDistribution DATETIME,
        destinataire VARCHAR(100),
        FOREIGN KEY (idLot) REFERENCES Lot_medicament_sensible(idLot) ON DELETE CASCADE
    )",
    // Table RendezVous
    "CREATE TABLE IF NOT EXISTS RendezVous (
        idRDV INT AUTO_INCREMENT PRIMARY KEY,
        dateHeureDebut DATETIME,
        dateHeureFin DATETIME,
        statut VARCHAR(20) DEFAULT 'planifie',
        typeConsultation VARCHAR(100),
        motif TEXT
    )",
    // Table FicheRendezVous
    "CREATE TABLE IF NOT EXISTS FicheRendezVous (
        idFiche INT AUTO_INCREMENT PRIMARY KEY,
        idRDV INT,
        dateGeneration DATE,
        piecesAApporter TEXT,
        consignesAvantConsultation TEXT,
        tarifConsultation FLOAT,
        modeRemboursement VARCHAR(100),
        emailEnvoye BOOLEAN DEFAULT FALSE,
        calendrierAjoute BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (idRDV) REFERENCES RendezVous(idRDV) ON DELETE CASCADE
    )",
    // Table Intervention
    "CREATE TABLE IF NOT EXISTS Intervention (
        idIntervention INT AUTO_INCREMENT PRIMARY KEY,
        dateHeureDebut DATETIME,
        dateHeureFinPrevu DATETIME,
        typeIntervention VARCHAR(100),
        niveauUrgence INT
    )",
    // Table Intervention_annulee
    "CREATE TABLE IF NOT EXISTS Intervention_annulee (
        idIntervention INT,
        raison VARCHAR(255),
        dateAnnulation DATETIME
    )",
    // Table MaterielChirurgical
    "CREATE TABLE IF NOT EXISTS MaterielChirurgical (
        idMateriel INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100),
        categorie VARCHAR(50),
        disponibilite VARCHAR(50),
        statutSterilisation VARCHAR(50),
        nombreUtilisationsMax INT,
        nombreUtilisationsActuelles INT DEFAULT 0
    )",
    // Procédure planifier
    "DROP PROCEDURE IF EXISTS planifier",
    "CREATE PROCEDURE planifier(IN id INT)
    BEGIN
        UPDATE Intervention SET dateHeureDebut = NOW() WHERE idIntervention = id;
    END",
    // Procédure annuler
    "DROP PROCEDURE IF EXISTS annuler",
    "CREATE PROCEDURE annuler(IN id INT, IN raison_text VARCHAR(255))
    BEGIN
        DELETE FROM Intervention WHERE idIntervention = id;
        INSERT INTO Intervention_annulee VALUES (id, raison_text, NOW());
    END",
    // Fonction estSterile
    "DROP FUNCTION IF EXISTS estSterile",
    "CREATE FUNCTION estSterile(id INT) RETURNS BOOLEAN
    DETERMINISTIC
    BEGIN
        DECLARE sterile BOOLEAN;
        SELECT statutSterilisation = 'sterilise' INTO sterile 
        FROM MaterielChirurgical WHERE idMateriel = id;
        RETURN sterile;
    END",
    // Fonction estUtilisable
    "DROP FUNCTION IF EXISTS estUtilisable",
    "CREATE FUNCTION estUtilisable(id INT) RETURNS BOOLEAN
    DETERMINISTIC
    BEGIN
        DECLARE utilisable BOOLEAN;
        SELECT (disponibilite = 'disponible' AND statutSterilisation = 'sterilise' 
                AND nombreUtilisationsActuelles < nombreUtilisationsMax) INTO utilisable
        FROM MaterielChirurgical WHERE idMateriel = id;
        RETURN utilisable;
    END"
];

foreach ($queries as $query) {
    try {
        $db->exec($query);
        echo "<p>Requête exécutée avec succès : " . substr($query, 0, 50) . "...</p>";
    } catch(PDOException $e) {
        echo "<p>Erreur sur requête : " . $e->getMessage() . "</p>";
    }
}
echo "<p>Fin de l'installation.</p>";
?>
