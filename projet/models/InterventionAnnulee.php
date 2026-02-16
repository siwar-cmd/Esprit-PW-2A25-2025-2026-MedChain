<?php
require_once __DIR__ . '/../config.php';

class InterventionAnnulee {
    private $idIntervention;
    private $raison;
    private $dateAnnulation;

    // ==================== CONSTRUCTEUR ====================
    public function __construct($idIntervention = 0, $raison = "", $dateAnnulation = "") {
        $this->idIntervention = $idIntervention;
        $this->raison = $raison;
        $this->dateAnnulation = $dateAnnulation;
    }

    // ==================== GETTERS ====================
    public function getIdIntervention() { return $this->idIntervention; }
    public function getRaison() { return $this->raison; }
    public function getDateAnnulation() { return $this->dateAnnulation; }

    // ==================== SETTERS ====================
    public function setIdIntervention($id) { $this->idIntervention = (int)$id; return $this; }
    public function setRaison($raison) { $this->raison = htmlspecialchars(trim($raison), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setDateAnnulation($date) { $this->dateAnnulation = $date; return $this; }

    // ==================== MÉTHODES CRUD ====================

    public function addAnnulation($data) {
        $pdo = config::getConnexion();
        $sql = "INSERT INTO intervention_annulee (idIntervention, raison, dateAnnulation) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            (int)$data['idIntervention'],
            htmlspecialchars($data['raison'])
        ]);
    }

    public function getAllAnnulations() {
        $pdo = config::getConnexion();
        $sql = "SELECT ia.*, i.type, i.chirurgien, i.date_intervention, i.salle 
                FROM intervention_annulee ia 
                INNER JOIN intervention i ON ia.idIntervention = i.id 
                ORDER BY ia.dateAnnulation DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAnnulationByInterventionId($idIntervention) {
        $pdo = config::getConnexion();
        $sql = "SELECT ia.*, i.type, i.chirurgien, i.date_intervention, i.salle 
                FROM intervention_annulee ia 
                INNER JOIN intervention i ON ia.idIntervention = i.id 
                WHERE ia.idIntervention = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idIntervention]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isInterventionAnnulee($idIntervention) {
        $pdo = config::getConnexion();
        $sql = "SELECT COUNT(*) as count FROM intervention_annulee WHERE idIntervention = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idIntervention]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    }

    // Liste des interventions non-annulées (pour le formulaire d'ajout)
    public function getInterventionsNonAnnulees() {
        $pdo = config::getConnexion();
        $sql = "SELECT i.* FROM intervention i 
                LEFT JOIN intervention_annulee ia ON i.id = ia.idIntervention 
                WHERE ia.idIntervention IS NULL 
                ORDER BY i.date_intervention DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
