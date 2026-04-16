<?php
require_once 'config.php';

class FicheRdv {
    private $conn;
    private $table_name = "ficherendezvous";

    public $idFiche;
    public $idRDV;
    public $dateGeneration;
    public $piecesAApporter;
    public $consignesAvantConsultation;
    public $tarifConsultation;
    public $modeRemboursement;
    public $emailEnvoye;
    public $calendrierAjoute;

    public function __construct() {
        $database = new Config();
        $this->conn = $database->getConnection();
    }

    // Créer une fiche
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET idRDV=:idRDV,
                      dateGeneration=:dateGeneration,
                      piecesAApporter=:piecesAApporter,
                      consignesAvantConsultation=:consignesAvantConsultation,
                      tarifConsultation=:tarifConsultation,
                      modeRemboursement=:modeRemboursement,
                      emailEnvoye=:emailEnvoye,
                      calendrierAjoute=:calendrierAjoute";

        $stmt = $this->conn->prepare($query);

        $this->idRDV = htmlspecialchars(strip_tags($this->idRDV));
        $this->dateGeneration = htmlspecialchars(strip_tags($this->dateGeneration));
        $this->piecesAApporter = htmlspecialchars(strip_tags($this->piecesAApporter));
        $this->consignesAvantConsultation = htmlspecialchars(strip_tags($this->consignesAvantConsultation));
        $this->tarifConsultation = htmlspecialchars(strip_tags($this->tarifConsultation));
        $this->modeRemboursement = htmlspecialchars(strip_tags($this->modeRemboursement));
        $this->emailEnvoye = htmlspecialchars(strip_tags($this->emailEnvoye));
        $this->calendrierAjoute = htmlspecialchars(strip_tags($this->calendrierAjoute));

        $stmt->bindParam(":idRDV", $this->idRDV);
        $stmt->bindParam(":dateGeneration", $this->dateGeneration);
        $stmt->bindParam(":piecesAApporter", $this->piecesAApporter);
        $stmt->bindParam(":consignesAvantConsultation", $this->consignesAvantConsultation);
        $stmt->bindParam(":tarifConsultation", $this->tarifConsultation);
        $stmt->bindParam(":modeRemboursement", $this->modeRemboursement);
        $stmt->bindParam(":emailEnvoye", $this->emailEnvoye);
        $stmt->bindParam(":calendrierAjoute", $this->calendrierAjoute);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Lire toutes les fiches
    public function readAll() {
        $query = "SELECT f.*, r.dateHeureDebut, r.typeConsultation, r.statut 
                  FROM " . $this->table_name . " f
                  LEFT JOIN rendezvous r ON f.idRDV = r.idRDV
                  ORDER BY f.dateGeneration DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Lire une fiche par ID
    public function readOne() {
        $query = "SELECT f.*, r.dateHeureDebut, r.dateHeureFin, r.typeConsultation, r.motif, r.statut
                  FROM " . $this->table_name . " f
                  LEFT JOIN rendezvous r ON f.idRDV = r.idRDV
                  WHERE f.idFiche = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idFiche);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->idRDV = $row['idRDV'];
            $this->dateGeneration = $row['dateGeneration'];
            $this->piecesAApporter = $row['piecesAApporter'];
            $this->consignesAvantConsultation = $row['consignesAvantConsultation'];
            $this->tarifConsultation = $row['tarifConsultation'];
            $this->modeRemboursement = $row['modeRemboursement'];
            $this->emailEnvoye = $row['emailEnvoye'];
            $this->calendrierAjoute = $row['calendrierAjoute'];
            return $row;
        }
        return false;
    }

    // Mettre à jour une fiche
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET piecesAApporter = :piecesAApporter,
                      consignesAvantConsultation = :consignesAvantConsultation,
                      tarifConsultation = :tarifConsultation,
                      modeRemboursement = :modeRemboursement
                  WHERE idFiche = :idFiche";

        $stmt = $this->conn->prepare($query);

        $this->piecesAApporter = htmlspecialchars(strip_tags($this->piecesAApporter));
        $this->consignesAvantConsultation = htmlspecialchars(strip_tags($this->consignesAvantConsultation));
        $this->tarifConsultation = htmlspecialchars(strip_tags($this->tarifConsultation));
        $this->modeRemboursement = htmlspecialchars(strip_tags($this->modeRemboursement));
        $this->idFiche = htmlspecialchars(strip_tags($this->idFiche));

        $stmt->bindParam(":piecesAApporter", $this->piecesAApporter);
        $stmt->bindParam(":consignesAvantConsultation", $this->consignesAvantConsultation);
        $stmt->bindParam(":tarifConsultation", $this->tarifConsultation);
        $stmt->bindParam(":modeRemboursement", $this->modeRemboursement);
        $stmt->bindParam(":idFiche", $this->idFiche);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Supprimer une fiche
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE idFiche = ?";
        $stmt = $this->conn->prepare($query);
        $this->idFiche = htmlspecialchars(strip_tags($this->idFiche));
        $stmt->bindParam(1, $this->idFiche);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Générer PDF (simulation)
    public function genererPDF() {
        // Implémentation de génération PDF
        return true;
    }

    // Envoyer par email
    public function envoyerParEmail() {
        $query = "UPDATE " . $this->table_name . " SET emailEnvoye = 1 WHERE idFiche = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idFiche);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Ajouter au calendrier
    public function ajouterAuCalendrier() {
        $query = "UPDATE " . $this->table_name . " SET calendrierAjoute = 1 WHERE idFiche = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idFiche);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>