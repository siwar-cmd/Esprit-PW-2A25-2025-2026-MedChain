<?php
class Distribution {
    private $conn;
    private $table_name = "Distribution_controlee";

    public $idDistribution;
    public $idLot;
    public $quantite;
    public $dateDistribution;
    public $destinataire;

    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function validate() {
        $this->errors = [];
        if(empty($this->idLot)) $this->errors['idLot'] = "Un lot doit être sélectionné.";
        if($this->quantite === '' || !is_numeric($this->quantite) || $this->quantite <= 0) $this->errors['quantite'] = "La quantité doit être supérieure à 0.";
        if(empty($this->dateDistribution)) $this->errors['dateDistribution'] = "La date est requise.";
        if(empty($this->destinataire)) $this->errors['destinataire'] = "Le destinataire est requis.";
        return empty($this->errors);
    }

    public function sanitize() {
        $this->destinataire = htmlspecialchars(strip_tags($this->destinataire));
        $this->quantite = intval($this->quantite);
    }

    public function read() {
        $query = "SELECT d.*, l.nomMedicament, l.numeroLot 
                  FROM " . $this->table_name . " d 
                  LEFT JOIN Lot_medicament_sensible l ON d.idLot = l.idLot 
                  ORDER BY d.dateDistribution DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idDistribution = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idDistribution);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->idDistribution = $row['idDistribution'];
            $this->idLot = $row['idLot'];
            $this->quantite = $row['quantite'];
            $this->dateDistribution = $row['dateDistribution'];
            $this->destinataire = $row['destinataire'];
            return true;
        }
        return false;
    }

    public function create() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "INSERT INTO " . $this->table_name . "
                  SET idLot=:idL, quantite=:qte, dateDistribution=:dd, destinataire=:dest";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":idL", $this->idLot);
        $stmt->bindParam(":qte", $this->quantite);
        $stmt->bindParam(":dd", $this->dateDistribution);
        $stmt->bindParam(":dest", $this->destinataire);
        return $stmt->execute();
    }

    public function update() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "UPDATE " . $this->table_name . "
                  SET idLot=:idL, quantite=:qte, dateDistribution=:dd, destinataire=:dest
                  WHERE idDistribution = :idD";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":idL", $this->idLot);
        $stmt->bindParam(":qte", $this->quantite);
        $stmt->bindParam(":dd", $this->dateDistribution);
        $stmt->bindParam(":dest", $this->destinataire);
        $stmt->bindParam(":idD", $this->idDistribution);
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE idDistribution = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idDistribution);
        return $stmt->execute();
    }

    public function getStats() {
        $stats = [];
        $q1 = "SELECT destinataire, SUM(quantite) as total FROM " . $this->table_name . " GROUP BY destinataire";
        $stats['destinataires'] = $this->conn->query($q1)->fetchAll(PDO::FETCH_ASSOC);

        $q2 = "SELECT l.nomMedicament, COUNT(d.idDistribution) as nbDistributions 
               FROM " . $this->table_name . " d 
               LEFT JOIN Lot_medicament_sensible l ON d.idLot = l.idLot 
               GROUP BY l.nomMedicament";
        $stats['meds'] = $this->conn->query($q2)->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }

    public function getErrors() { return $this->errors; }
}
?>
