<?php
require_once __DIR__ . '/../config/database.php';

class HealthController {
    private $pdo;

    public function __construct() {
        $this->pdo = getPDO(); // adaptez selon votre fonction de connexion
    }

    // Récupère les consultations d'un médecin (avec limit)
    public function getConsultationsByMedecin($idMedecin, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nom, u.prenom, u.email
            FROM consultations c
            JOIN utilisateur u ON c.id_patient = u.id_utilisateur
            WHERE c.id_medecin = ?
            ORDER BY c.date_consultation DESC
            LIMIT ?
        ");
        $stmt->execute([$idMedecin, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Nombre total de consultations d'un médecin
    public function countConsultationsByMedecin($idMedecin) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM consultations WHERE id_medecin = ?");
        $stmt->execute([$idMedecin]);
        return $stmt->fetchColumn();
    }

    // Patients distincts d'un médecin
    public function getPatientsByMedecin($idMedecin) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom, u.email, u.telephone
            FROM consultations c
            JOIN utilisateur u ON c.id_patient = u.id_utilisateur
            WHERE c.id_medecin = ?
        ");
        $stmt->execute([$idMedecin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dernières mesures d'un patient pour un type donné
    public function getLastMesure($idPatient, $type) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM mesures_sante
            WHERE id_patient = ? AND type_mesure = ?
            ORDER BY date_mesure DESC LIMIT 1
        ");
        $stmt->execute([$idPatient, $type]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Historique des mesures (pour graphiques)
    public function getMesuresPatient($idPatient, $type, $limit = 30) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM mesures_sante
            WHERE id_patient = ? AND type_mesure = ?
            ORDER BY date_mesure DESC
            LIMIT ?
        ");
        $stmt->execute([$idPatient, $type, $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($rows); // ordre chronologique
    }

    // Statistiques pour un médecin
    public function getMedecinStats($idMedecin) {
        $nbPatients = $this->countDistinctPatients($idMedecin);
        $nbConsultations = $this->countConsultationsByMedecin($idMedecin);
        $dernieresConsultations = $this->getConsultationsByMedecin($idMedecin, 5);
        return [
            'nb_patients' => $nbPatients,
            'nb_consultations' => $nbConsultations,
            'dernieres_consultations' => $dernieresConsultations
        ];
    }

    private function countDistinctPatients($idMedecin) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT id_patient) FROM consultations WHERE id_medecin = ?
        ");
        $stmt->execute([$idMedecin]);
        return $stmt->fetchColumn();
    }

    // Pour l'admin : tous les patients (rôle patient)
    public function getAllPatients() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM utilisateur WHERE role = 'patient'
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>