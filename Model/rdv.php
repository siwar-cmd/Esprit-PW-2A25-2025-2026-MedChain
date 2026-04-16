<?php
require_once 'config.php';

class Rdv {
    private $conn;
    private $table_name = "rendezvous";

    public $idRDV;
    public $dateHeureDebut;
    public $dateHeureFin;
    public $statut;
    public $typeConsultation;
    public $motif;

    public function __construct() {
        $database = new Config();
        $this->conn = $database->getConnection();
    }

    // Créer un rendez-vous
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET dateHeureDebut=:dateHeureDebut, 
                      dateHeureFin=:dateHeureFin, 
                      statut=:statut, 
                      typeConsultation=:typeConsultation, 
                      motif=:motif";

        $stmt = $this->conn->prepare($query);

        $this->dateHeureDebut = htmlspecialchars(strip_tags($this->dateHeureDebut));
        $this->dateHeureFin = htmlspecialchars(strip_tags($this->dateHeureFin));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        $this->typeConsultation = htmlspecialchars(strip_tags($this->typeConsultation));
        $this->motif = htmlspecialchars(strip_tags($this->motif));

        $stmt->bindParam(":dateHeureDebut", $this->dateHeureDebut);
        $stmt->bindParam(":dateHeureFin", $this->dateHeureFin);
        $stmt->bindParam(":statut", $this->statut);
        $stmt->bindParam(":typeConsultation", $this->typeConsultation);
        $stmt->bindParam(":motif", $this->motif);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Lire tous les rendez-vous
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY dateHeureDebut DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Lire un rendez-vous par ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idRDV = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idRDV);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->dateHeureDebut = $row['dateHeureDebut'];
            $this->dateHeureFin = $row['dateHeureFin'];
            $this->statut = $row['statut'];
            $this->typeConsultation = $row['typeConsultation'];
            $this->motif = $row['motif'];
            return true;
        }
        return false;
    }

    // Mettre à jour un rendez-vous
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET dateHeureDebut = :dateHeureDebut,
                      dateHeureFin = :dateHeureFin,
                      typeConsultation = :typeConsultation,
                      motif = :motif
                  WHERE idRDV = :idRDV";

        $stmt = $this->conn->prepare($query);

        $this->dateHeureDebut = htmlspecialchars(strip_tags($this->dateHeureDebut));
        $this->dateHeureFin = htmlspecialchars(strip_tags($this->dateHeureFin));
        $this->typeConsultation = htmlspecialchars(strip_tags($this->typeConsultation));
        $this->motif = htmlspecialchars(strip_tags($this->motif));
        $this->idRDV = htmlspecialchars(strip_tags($this->idRDV));

        $stmt->bindParam(":dateHeureDebut", $this->dateHeureDebut);
        $stmt->bindParam(":dateHeureFin", $this->dateHeureFin);
        $stmt->bindParam(":typeConsultation", $this->typeConsultation);
        $stmt->bindParam(":motif", $this->motif);
        $stmt->bindParam(":idRDV", $this->idRDV);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Supprimer un rendez-vous
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE idRDV = ?";
        $stmt = $this->conn->prepare($query);
        $this->idRDV = htmlspecialchars(strip_tags($this->idRDV));
        $stmt->bindParam(1, $this->idRDV);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Confirmer un rendez-vous
    public function confirmer() {
        $query = "UPDATE " . $this->table_name . " SET statut = 'confirme' WHERE idRDV = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idRDV);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Annuler un rendez-vous
    public function annuler($motif) {
        $query = "UPDATE " . $this->table_name . " SET statut = 'annule', motif = CONCAT(motif, ' - Annulation: ', ?) WHERE idRDV = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $motif);
        $stmt->bindParam(2, $this->idRDV);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Reporter un rendez-vous
    public function reporter($nouvelleDate) {
        $query = "UPDATE " . $this->table_name . " SET dateHeureDebut = ?, statut = 'reporte' WHERE idRDV = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $nouvelleDate);
        $stmt->bindParam(2, $this->idRDV);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>