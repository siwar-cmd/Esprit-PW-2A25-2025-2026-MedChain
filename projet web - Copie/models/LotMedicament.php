<?php
class LotMedicament {
    private $conn;
    private $table_name = "Lot_medicament_sensible";

    public $idLot;
    public $nomMedicament;
    public $numeroLot;
    public $quantite;
    public $datePeremption;

    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function validate() {
        $this->errors = [];
        if(empty($this->nomMedicament)) $this->errors['nomMedicament'] = "Le nom du médicament est requis.";
        if(empty($this->numeroLot)) $this->errors['numeroLot'] = "Le numéro de lot est requis.";
        if($this->quantite === '' || !is_numeric($this->quantite) || $this->quantite < 0) $this->errors['quantite'] = "Quantité invalide.";
        if(empty($this->datePeremption)) $this->errors['datePeremption'] = "Date de péremption requise.";
        return empty($this->errors);
    }

    public function sanitize() {
        $this->nomMedicament = htmlspecialchars(strip_tags($this->nomMedicament));
        $this->numeroLot = htmlspecialchars(strip_tags($this->numeroLot));
        $this->quantite = intval($this->quantite);
    }

    public function read() {
        // Ajout d'un booléen calculé pour la peremption (Aujourd'hui > datePeremption)
        $query = "SELECT *, (CURDATE() > datePeremption) as estPerime FROM " . $this->table_name . " ORDER BY datePeremption ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idLot = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idLot);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->idLot = $row['idLot'];
            $this->nomMedicament = $row['nomMedicament'];
            $this->numeroLot = $row['numeroLot'];
            $this->quantite = $row['quantite'];
            $this->datePeremption = $row['datePeremption'];
            return true;
        }
        return false;
    }

    public function create() {
        $this->sanitize();
        if(!$this->validate()) return false;
        $query = "INSERT INTO " . $this->table_name . "
                  SET nomMedicament=:nom, numeroLot=:num, quantite=:qte, datePeremption=:dp";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nom", $this->nomMedicament);
        $stmt->bindParam(":num", $this->numeroLot);
        $stmt->bindParam(":qte", $this->quantite);
        $stmt->bindParam(":dp", $this->datePeremption);
        return $stmt->execute();
    }

    public function update() {
        $this->sanitize();
        if(!$this->validate()) return false;
        $query = "UPDATE " . $this->table_name . "
                  SET nomMedicament=:nom, numeroLot=:num, quantite=:qte, datePeremption=:dp
                  WHERE idLot = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nom", $this->nomMedicament);
        $stmt->bindParam(":num", $this->numeroLot);
        $stmt->bindParam(":qte", $this->quantite);
        $stmt->bindParam(":dp", $this->datePeremption);
        $stmt->bindParam(":id", $this->idLot);
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE idLot = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idLot);
        return $stmt->execute();
    }

    public function subtractQuantity($amount) {
        $query = "UPDATE " . $this->table_name . " SET quantite = quantite - :amount WHERE idLot = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":id", $this->idLot);
        return $stmt->execute();
    }
    
    public function addQuantity($amount) {
        $query = "UPDATE " . $this->table_name . " SET quantite = quantite + :amount WHERE idLot = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":id", $this->idLot);
        return $stmt->execute();
    }

    public function getStats() {
        $stats = [];
        $q1 = "SELECT nomMedicament, SUM(quantite) as total FROM " . $this->table_name . " GROUP BY nomMedicament";
        $stats['quantites'] = $this->conn->query($q1)->fetchAll(PDO::FETCH_ASSOC);

        $q2 = "SELECT (CURDATE() > datePeremption) as perime, COUNT(*) as nb FROM " . $this->table_name . " GROUP BY (CURDATE() > datePeremption)";
        $stats['peremptions'] = $this->conn->query($q2)->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }
    public function getErrors() { return $this->errors; }
}
?>
