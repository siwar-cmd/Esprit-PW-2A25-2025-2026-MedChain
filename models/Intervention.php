<?php

class Intervention {
    private $id;
    private $type;
    private $date_intervention;
    private $duree;
    private $niveau_urgence;
    private $chirurgien;
    private $salle;
    private $description;
    private $created_at;
    
    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    // ==================== GETTERS ====================
    public function getId() { return $this->id; }
    public function getType() { return $this->type; }
    public function getDateIntervention() { return $this->date_intervention; }
    public function getDuree() { return $this->duree; }
    public function getNiveauUrgence() { return $this->niveau_urgence; }
    public function getChirurgien() { return $this->chirurgien; }
    public function getSalle() { return $this->salle; }
    public function getDescription() { return $this->description; }
    public function getCreatedAt() { return $this->created_at; }

    // ==================== SETTERS ====================
    public function setType($type) { $this->type = htmlspecialchars(trim($type), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setDateIntervention($date) { $this->date_intervention = $date; return $this; }
    public function setDuree($d) { $this->duree = (int)$d; return $this; }
    public function setNiveauUrgence($n) { $this->niveau_urgence = (int)$n; return $this; }
    public function setChirurgien($c) { $this->chirurgien = htmlspecialchars(trim($c), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setSalle($s) { $this->salle = htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setDescription($description) { $this->description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8'); return $this; }

    // ==================== UTILITY METHODS ====================
    public static function getNiveauUrgenceLabel($niveau) {
        $labels = [
            1 => 'Faible',
            2 => 'Modérée',
            3 => 'Élevée',
            4 => 'Critique',
            5 => 'Extrême'
        ];
        return $labels[$niveau] ?? 'Non défini';
    }

    // ===== CRUD OPERATIONS =====
    
    // CREATE
    public function create(): bool {
        try {
            $sql = "INSERT INTO intervention (type, date_intervention, duree, niveau_urgence, chirurgien, salle, description) 
                    VALUES (:type, :date, :duree, :niveau_urgence, :chirurgien, :salle, :description)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':type' => $this->type,
                ':date' => $this->date_intervention,
                ':duree' => $this->duree,
                ':niveau_urgence' => $this->niveau_urgence,
                ':chirurgien' => $this->chirurgien,
                ':salle' => $this->salle,
                ':description' => $this->description
            ]);
        } catch (Exception $e) {
            error_log("Erreur création intervention: " . $e->getMessage());
            return false;
        }
    }

    // READ ALL
    public function getAll($page = 1, $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
                $sql = "SELECT * FROM intervention ORDER BY date_intervention DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lecture interventions: " . $e->getMessage());
            return [];
        }
    }

    // READ ONE
    public function getById($id): ?array {
        try {
                $sql = "SELECT * FROM intervention WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("Erreur lecture intervention: " . $e->getMessage());
            return null;
        }
    }

    // UPDATE
    public function update($id): bool {
        try {
            $sql = "UPDATE intervention SET 
                    type = :type, 
                    date_intervention = :date, 
                    duree = :duree, 
                    niveau_urgence = :niveau_urgence, 
                    chirurgien = :chirurgien, 
                    salle = :salle, 
                    description = :description
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => (int)$id,
                ':type' => $this->type,
                ':date' => $this->date_intervention,
                ':duree' => $this->duree,
                ':niveau_urgence' => $this->niveau_urgence,
                ':chirurgien' => $this->chirurgien,
                ':salle' => $this->salle,
                ':description' => $this->description
            ]);
        } catch (Exception $e) {
            error_log("Erreur mise à jour intervention: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function delete($id): bool {
        try {
            $sql = "DELETE FROM intervention WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => (int)$id]);
        } catch (Exception $e) {
            error_log("Erreur suppression intervention: " . $e->getMessage());
            return false;
        }
    }

    // SEARCH
    public function search($keyword): array {
        try {
            $keyword = htmlspecialchars(trim($keyword), ENT_QUOTES, 'UTF-8');
            $sql = "SELECT * FROM intervention 
                    WHERE type LIKE :keyword 
                    OR description LIKE :keyword
                    ORDER BY date_intervention DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':keyword' => "%$keyword%"]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur recherche intervention: " . $e->getMessage());
            return [];
        }
    }

    // GET BY MEDECIN (pour les médecins)
    public function getByMedecin($prenomNom, $nomPrenom, $page = 1, $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
            $sql = "SELECT * FROM intervention 
                    WHERE chirurgien LIKE :prenomNom OR chirurgien LIKE :nomPrenom 
                    ORDER BY date_intervention DESC 
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':prenomNom', '%' . $prenomNom . '%', PDO::PARAM_STR);
            $stmt->bindValue(':nomPrenom', '%' . $nomPrenom . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur: " . $e->getMessage());
            return [];
        }
    }

    // COUNT
    public function count(): int {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM intervention");
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            return 0;
        }
    }

    // COUNT BY MEDECIN
    public function countByMedecin($prenomNom, $nomPrenom): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM intervention 
                    WHERE chirurgien LIKE :prenomNom OR chirurgien LIKE :nomPrenom";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':prenomNom', '%' . $prenomNom . '%', PDO::PARAM_STR);
            $stmt->bindValue(':nomPrenom', '%' . $nomPrenom . '%', PDO::PARAM_STR);
            $stmt->execute();
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            return 0;
        }
    }

    // STATISTICS
    public function getStatistics(): array {
        try {
            $stats = [
                'total' => 0,
                'par_statut' => [],
                'par_mois' => []
            ];

            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM intervention");
            $stats['total'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // If your intervention table doesn't have a 'statut' column, this will return empty
            try {
                $stmt = $this->pdo->query("SELECT statut, COUNT(*) as count FROM intervention GROUP BY statut");
                $stats['par_statut'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $stats['par_statut'] = [];
            }

            return $stats;
        } catch (Exception $e) {
            error_log("Erreur statistiques intervention: " . $e->getMessage());
            return [];
        }
    }

    // GET ALL FOR PDF EXPORT
    public function getAllForExport(): array {
        try {
            $sql = "SELECT i.*, u.nom, u.prenom FROM intervention i 
                    LEFT JOIN utilisateur u ON i.medecin_id = u.id_utilisateur 
                    ORDER BY i.date_intervention DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur export intervention: " . $e->getMessage());
            return [];
        }
    }

    // GET ALL FOR DROPDOWN OPTIONS
    public function getAllOptions(): array {
        try {
            $sql = "SELECT id, type, chirurgien, date_intervention FROM intervention ORDER BY date_intervention DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur liste interventions: " . $e->getMessage());
            return [];
        }
    }
}
