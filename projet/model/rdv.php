<?php
class RendezVous {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM rendez_vous ORDER BY dateHeureDebut DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rendez_vous WHERE idRDV = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO rendez_vous (dateHeureDebut, dateHeureFin, statut, typeConsultation, motif)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['dateHeureDebut'],
            $data['dateHeureFin'],
            $data['statut'] ?? 'PLANIFIE',
            $data['typeConsultation'],
            $data['motif']
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE rendez_vous
            SET dateHeureDebut = ?, dateHeureFin = ?, statut = ?, typeConsultation = ?, motif = ?
            WHERE idRDV = ?
        ");
        return $stmt->execute([
            $data['dateHeureDebut'],
            $data['dateHeureFin'],
            $data['statut'],
            $data['typeConsultation'],
            $data['motif'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM rendez_vous WHERE idRDV = ?");
        return $stmt->execute([$id]);
    }

    public function updateStatut($id, $statut) {
        $stmt = $this->pdo->prepare("UPDATE rendez_vous SET statut = ? WHERE idRDV = ?");
        return $stmt->execute([$statut, $id]);
    }

    public function search($keyword) {
        $kw = "%$keyword%";
        $stmt = $this->pdo->prepare("
            SELECT * FROM rendez_vous
            WHERE typeConsultation LIKE ? OR motif LIKE ? OR statut LIKE ?
            ORDER BY dateHeureDebut DESC
        ");
        $stmt->execute([$kw, $kw, $kw]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats() {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) AS total,
                SUM(statut = 'PLANIFIE') AS planifie,
                SUM(statut = 'CONFIRME') AS confirme,
                SUM(statut = 'ANNULE') AS annule,
                SUM(statut = 'TERMINE') AS termine
            FROM rendez_vous
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}