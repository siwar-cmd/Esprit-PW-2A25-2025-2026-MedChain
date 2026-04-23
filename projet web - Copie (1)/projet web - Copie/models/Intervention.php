<?php
class Intervention {
    private $conn;
    private $table_name = "Intervention";

    public $idIntervention;
    public $dateHeureDebut;
    public $dateHeureFinPrevu;
    public $typeIntervention;
    public $niveauUrgence;
    
    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function validate() {
        $this->errors = [];
        
        // Validation dateHeureFinPrevu vs dateHeureDebut
        if(!empty($this->dateHeureDebut) && !empty($this->dateHeureFinPrevu)) {
            if(strtotime($this->dateHeureFinPrevu) < strtotime($this->dateHeureDebut)) {
                $this->errors['dateHeureFinPrevu'] = "La date de fin prévue ne peut pas précéder la date de début";
            }
        }
        
        if(empty($this->typeIntervention)) {
            $this->errors['typeIntervention'] = "Le type d'intervention est obligatoire";
        }
        
        if(empty($this->niveauUrgence) || !is_numeric($this->niveauUrgence)) {
            $this->errors['niveauUrgence'] = "Le niveau d'urgence est obligatoire et doit être un nombre";
        }

        return empty($this->errors);
    }

    public function sanitize() {
        $this->dateHeureDebut = $this->dateHeureDebut ? htmlspecialchars(strip_tags($this->dateHeureDebut)) : null;
        $this->dateHeureFinPrevu = $this->dateHeureFinPrevu ? htmlspecialchars(strip_tags($this->dateHeureFinPrevu)) : null;
        $this->typeIntervention = htmlspecialchars(strip_tags($this->typeIntervention));
        $this->niveauUrgence = filter_var($this->niveauUrgence, FILTER_VALIDATE_INT);
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY idIntervention DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idIntervention = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idIntervention);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->dateHeureDebut = $row['dateHeureDebut'];
            $this->dateHeureFinPrevu = $row['dateHeureFinPrevu'];
            $this->typeIntervention = $row['typeIntervention'];
            $this->niveauUrgence = $row['niveauUrgence'];
            return true;
        }
        return false;
    }

    public function create() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "INSERT INTO " . $this->table_name . "
                  SET dateHeureDebut=:dateHeureDebut, dateHeureFinPrevu=:dateHeureFinPrevu, 
                      typeIntervention=:typeIntervention, niveauUrgence=:niveauUrgence";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":dateHeureDebut", $this->dateHeureDebut, empty($this->dateHeureDebut) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(":dateHeureFinPrevu", $this->dateHeureFinPrevu, empty($this->dateHeureFinPrevu) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":typeIntervention", $this->typeIntervention);
        $stmt->bindParam(":niveauUrgence", $this->niveauUrgence);

        return $stmt->execute();
    }

    public function update() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "UPDATE " . $this->table_name . "
                  SET dateHeureDebut=:dateHeureDebut, dateHeureFinPrevu=:dateHeureFinPrevu, 
                      typeIntervention=:typeIntervention, niveauUrgence=:niveauUrgence
                  WHERE idIntervention = :idIntervention";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":dateHeureDebut", $this->dateHeureDebut, empty($this->dateHeureDebut) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(":dateHeureFinPrevu", $this->dateHeureFinPrevu, empty($this->dateHeureFinPrevu) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":typeIntervention", $this->typeIntervention);
        $stmt->bindParam(":niveauUrgence", $this->niveauUrgence);
        $stmt->bindParam(":idIntervention", $this->idIntervention);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE idIntervention = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idIntervention);
        return $stmt->execute();
    }

    // Call stored procedure: planifier
    public function planifier() {
        $query = "CALL planifier(?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idIntervention);
        return $stmt->execute();
    }

    // Call stored procedure: annuler
    public function annuler($raison) {
        $query = "CALL annuler(?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idIntervention);
        $stmt->bindParam(2, $raison);
        return $stmt->execute();
    }
    
    public function getStats() {
        $stats = [];
        
        // Urgence
        $query1 = "SELECT niveauUrgence, COUNT(idIntervention) as total 
                   FROM " . $this->table_name . " 
                   GROUP BY niveauUrgence";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->execute();
        $stats['urgences'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        
        // Types
        $query2 = "SELECT typeIntervention, COUNT(idIntervention) as total 
                   FROM " . $this->table_name . " 
                   GROUP BY typeIntervention";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->execute();
        $stats['types'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    public function getErrors() {
        return $this->errors;
    }
}
?>
