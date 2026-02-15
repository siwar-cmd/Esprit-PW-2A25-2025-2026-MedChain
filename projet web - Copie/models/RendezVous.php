<?php
class RendezVous {
    private $conn;
    private $table_name = "RendezVous";

    public $idRDV;
    public $dateHeureDebut;
    public $dateHeureFin;
    public $statut;
    public $typeConsultation;
    public $motif;

    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function validate() {
        $this->errors = [];
        if(empty($this->typeConsultation)) $this->errors['typeConsultation'] = "Type requis.";
        if(empty($this->dateHeureDebut)) $this->errors['dateHeureDebut'] = "Date de début requise.";
        if(empty($this->dateHeureFin)) $this->errors['dateHeureFin'] = "Date de fin requise.";
        
        if(!empty($this->dateHeureDebut) && !empty($this->dateHeureFin)) {
            if(strtotime($this->dateHeureFin) <= strtotime($this->dateHeureDebut)) {
                $this->errors['dateHeureFin'] = "La date de fin doit être postérieure au début.";
            }
        }
        return empty($this->errors);
    }

    public function sanitize() {
        $this->typeConsultation = htmlspecialchars(strip_tags($this->typeConsultation));
        $this->motif = htmlspecialchars(strip_tags($this->motif));
        $this->statut = $this->statut ? htmlspecialchars(strip_tags($this->statut)) : 'planifie';
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY dateHeureDebut DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

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

    public function create() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "INSERT INTO " . $this->table_name . "
                  SET dateHeureDebut=:dateHeureDebut, dateHeureFin=:dateHeureFin, 
                      statut=:statut, typeConsultation=:typeConsultation, motif=:motif";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":dateHeureDebut", $this->dateHeureDebut);
        $stmt->bindParam(":dateHeureFin", $this->dateHeureFin);
        $stmt->bindParam(":statut", $this->statut);
        $stmt->bindParam(":typeConsultation", $this->typeConsultation);
        $stmt->bindParam(":motif", $this->motif);
        return $stmt->execute();
    }

    public function update() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "UPDATE " . $this->table_name . "
                  SET dateHeureDebut=:dateHeureDebut, dateHeureFin=:dateHeureFin, 
                      statut=:statut, typeConsultation=:typeConsultation, motif=:motif
                  WHERE idRDV = :idRDV";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":dateHeureDebut", $this->dateHeureDebut);
        $stmt->bindParam(":dateHeureFin", $this->dateHeureFin);
        $stmt->bindParam(":statut", $this->statut);
        $stmt->bindParam(":typeConsultation", $this->typeConsultation);
        $stmt->bindParam(":motif", $this->motif);
        $stmt->bindParam(":idRDV", $this->idRDV);
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE idRDV = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idRDV);
        return $stmt->execute();
    }

    public function confirmer() {
        $query = "UPDATE " . $this->table_name . " SET statut = 'confirme' WHERE idRDV = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idRDV);
        return $stmt->execute();
    }

    public function annuler($raison) {
        $query = "UPDATE " . $this->table_name . " SET statut = 'annule', motif = ? WHERE idRDV = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $raison);
        $stmt->bindParam(2, $this->idRDV);
        return $stmt->execute();
    }

    public function reporter($nouvelleDate) {
        $query = "UPDATE " . $this->table_name . " SET dateHeureDebut = ?, statut = 'reporte' WHERE idRDV = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $nouvelleDate);
        $stmt->bindParam(2, $this->idRDV);
        return $stmt->execute();
    }
    
    public function getStats() {
        $stats = [];
        $q1 = "SELECT statut, COUNT(*) as total FROM " . $this->table_name . " GROUP BY statut";
        $stats['statuts'] = $this->conn->query($q1)->fetchAll(PDO::FETCH_ASSOC);

        $q2 = "SELECT typeConsultation, COUNT(*) as total FROM " . $this->table_name . " GROUP BY typeConsultation";
        $stats['types'] = $this->conn->query($q2)->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }
    public function getErrors() { return $this->errors; }
}
?>
