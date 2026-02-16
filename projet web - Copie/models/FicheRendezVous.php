<?php
class FicheRendezVous {
    private $conn;
    private $table_name = "FicheRendezVous";

    public $idFiche;
    public $idRDV;
    public $dateGeneration;
    public $piecesAApporter;
    public $consignesAvantConsultation;
    public $tarifConsultation;
    public $modeRemboursement;
    public $emailEnvoye;
    public $calendrierAjoute;

    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function validate() {
        $this->errors = [];
        if(empty($this->idRDV)) $this->errors['idRDV'] = "Veuillez associer un RDV.";
        if($this->tarifConsultation === '' || !is_numeric($this->tarifConsultation)) $this->errors['tarifConsultation'] = "Le tarif doit être numérique.";
        return empty($this->errors);
    }

    public function sanitize() {
        $this->piecesAApporter = htmlspecialchars(strip_tags($this->piecesAApporter));
        $this->consignesAvantConsultation = htmlspecialchars(strip_tags($this->consignesAvantConsultation));
        $this->modeRemboursement = htmlspecialchars(strip_tags($this->modeRemboursement));
        $this->tarifConsultation = floatval($this->tarifConsultation);
    }

    public function read() {
        $query = "SELECT f.*, r.dateHeureDebut, r.typeConsultation 
                  FROM " . $this->table_name . " f 
                  LEFT JOIN RendezVous r ON f.idRDV = r.idRDV 
                  ORDER BY f.idFiche DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idFiche = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idFiche);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->idFiche = $row['idFiche'];
            $this->idRDV = $row['idRDV'];
            $this->dateGeneration = $row['dateGeneration'];
            $this->piecesAApporter = $row['piecesAApporter'];
            $this->consignesAvantConsultation = $row['consignesAvantConsultation'];
            $this->tarifConsultation = $row['tarifConsultation'];
            $this->modeRemboursement = $row['modeRemboursement'];
            $this->emailEnvoye = $row['emailEnvoye'];
            $this->calendrierAjoute = $row['calendrierAjoute'];
            return true;
        }
        return false;
    }

    public function create() {
        $this->sanitize();
        if(!$this->validate()) return false;
        $query = "INSERT INTO " . $this->table_name . "
                  SET idRDV=:idRDV, piecesAApporter=:pieces, consignesAvantConsultation=:consignes, 
                      tarifConsultation=:tarif, modeRemboursement=:modeR ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":idRDV", $this->idRDV);
        $stmt->bindParam(":pieces", $this->piecesAApporter);
        $stmt->bindParam(":consignes", $this->consignesAvantConsultation);
        $stmt->bindParam(":tarif", $this->tarifConsultation);
        $stmt->bindParam(":modeR", $this->modeRemboursement);
        return $stmt->execute();
    }

    public function update() {
        $this->sanitize();
        if(!$this->validate()) return false;
        $query = "UPDATE " . $this->table_name . "
                  SET idRDV=:idRDV, piecesAApporter=:pieces, consignesAvantConsultation=:consignes, 
                      tarifConsultation=:tarif, modeRemboursement=:modeR 
                  WHERE idFiche = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":idRDV", $this->idRDV);
        $stmt->bindParam(":pieces", $this->piecesAApporter);
        $stmt->bindParam(":consignes", $this->consignesAvantConsultation);
        $stmt->bindParam(":tarif", $this->tarifConsultation);
        $stmt->bindParam(":modeR", $this->modeRemboursement);
        $stmt->bindParam(":id", $this->idFiche);
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE idFiche = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idFiche);
        return $stmt->execute();
    }

    public function setGenere() {
        $q = "UPDATE " . $this->table_name . " SET dateGeneration = CURDATE() WHERE idFiche = ?";
        $stmt = $this->conn->prepare($q);
        return $stmt->execute([$this->idFiche]);
    }

    public function setEmail() {
        $q = "UPDATE " . $this->table_name . " SET emailEnvoye = TRUE WHERE idFiche = ?";
        $stmt = $this->conn->prepare($q);
        return $stmt->execute([$this->idFiche]);
    }

    public function setCalendrier() {
        $q = "UPDATE " . $this->table_name . " SET calendrierAjoute = TRUE WHERE idFiche = ?";
        $stmt = $this->conn->prepare($q);
        return $stmt->execute([$this->idFiche]);
    }

    public function getStats() {
        $stats = [];
        $q1 = "SELECT modeRemboursement, COUNT(*) as total FROM " . $this->table_name . " GROUP BY modeRemboursement";
        $stats['remboursements'] = $this->conn->query($q1)->fetchAll(PDO::FETCH_ASSOC);

        $q2 = "SELECT emailEnvoye, COUNT(*) as total FROM " . $this->table_name . " GROUP BY emailEnvoye";
        $stats['emails'] = $this->conn->query($q2)->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }
    public function getErrors() { return $this->errors; }
}
?>
