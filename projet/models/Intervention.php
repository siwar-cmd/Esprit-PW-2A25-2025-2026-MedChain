<?php
require_once __DIR__ . '/../config.php';

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

    // ==================== CONSTRUCTEUR ====================
    public function __construct($type = "", $date_intervention = "", $duree = 0, $niveau_urgence = 1, $chirurgien = "", $salle = "", $description = "") {
        $this->type = $type;
        $this->date_intervention = $date_intervention;
        $this->duree = $duree;
        $this->niveau_urgence = $niveau_urgence;
        $this->chirurgien = $chirurgien;
        $this->salle = $salle;
        $this->description = $description;
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
    public function setId($id) { $this->id = (int)$id; return $this; }
    public function setType($type) { $this->type = htmlspecialchars(trim($type), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setDateIntervention($date) { $this->date_intervention = $date; return $this; }
    public function setDuree($duree) { $this->duree = (int)$duree; return $this; }
    public function setNiveauUrgence($niveau) { $this->niveau_urgence = (int)$niveau; return $this; }
    public function setChirurgien($chirurgien) { $this->chirurgien = htmlspecialchars(trim($chirurgien), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setSalle($salle) { $this->salle = htmlspecialchars(trim($salle), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setDescription($description) { $this->description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; return $this; }

    // ==================== MÉTHODES CRUD ====================

    public function getAllInterventions() {
        $pdo = config::getConnexion();
        $sql = "SELECT * FROM intervention ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInterventionById($id) {
        $pdo = config::getConnexion();
        $sql = "SELECT * FROM intervention WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addIntervention($data) {
        $pdo = config::getConnexion();
        $sql = "INSERT INTO intervention (type, date_intervention, duree, niveau_urgence, chirurgien, salle, description, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            htmlspecialchars($data['type']),
            $data['date_intervention'],
            (int)$data['duree'],
            (int)$data['niveau_urgence'],
            htmlspecialchars($data['chirurgien']),
            htmlspecialchars($data['salle'] ?? ''),
            htmlspecialchars($data['description'] ?? '')
        ]);
    }

    public function updateIntervention($id, $data) {
        $pdo = config::getConnexion();
        $sql = "UPDATE intervention SET type = ?, date_intervention = ?, duree = ?, niveau_urgence = ?, chirurgien = ?, salle = ?, description = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            htmlspecialchars($data['type']),
            $data['date_intervention'],
            (int)$data['duree'],
            (int)$data['niveau_urgence'],
            htmlspecialchars($data['chirurgien']),
            htmlspecialchars($data['salle'] ?? ''),
            htmlspecialchars($data['description'] ?? ''),
            (int)$id
        ]);
    }

    public function deleteIntervention($id) {
        $pdo = config::getConnexion();
        // Supprimer les annulations liées d'abord (jointure)
        $sql1 = "DELETE FROM intervention_annulee WHERE idIntervention = ?";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([$id]);
        // Puis supprimer l'intervention
        $sql2 = "DELETE FROM intervention WHERE id = ?";
        $stmt2 = $pdo->prepare($sql2);
        return $stmt2->execute([$id]);
    }

    // ==================== RECHERCHE DYNAMIQUE ====================

    public function searchInterventions($keyword) {
        $pdo = config::getConnexion();
        $sql = "SELECT * FROM intervention 
                WHERE type LIKE ? OR chirurgien LIKE ? OR salle LIKE ? OR description LIKE ? 
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $term = '%' . $keyword . '%';
        $stmt->execute([$term, $term, $term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== TRI ====================

    public function getAllSorted($column = 'created_at', $order = 'DESC') {
        $pdo = config::getConnexion();
        $allowedColumns = ['id', 'type', 'date_intervention', 'duree', 'niveau_urgence', 'chirurgien', 'salle', 'created_at'];
        $allowedOrders = ['ASC', 'DESC'];
        if (!in_array($column, $allowedColumns)) $column = 'created_at';
        if (!in_array(strtoupper($order), $allowedOrders)) $order = 'DESC';
        $sql = "SELECT * FROM intervention ORDER BY $column $order";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== STATISTIQUES ====================

    public function getStatistics() {
        $pdo = config::getConnexion();
        $stats = [];

        // Total
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM intervention");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Par type
        $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM intervention GROUP BY type ORDER BY count DESC");
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Par niveau d'urgence
        $stmt = $pdo->query("SELECT niveau_urgence, COUNT(*) as count FROM intervention GROUP BY niveau_urgence ORDER BY niveau_urgence");
        $stats['by_urgence'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Par mois (12 derniers mois)
        $stmt = $pdo->query("SELECT DATE_FORMAT(date_intervention, '%Y-%m') as mois, COUNT(*) as count 
                             FROM intervention 
                             WHERE date_intervention >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                             GROUP BY mois ORDER BY mois");
        $stats['by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Durée moyenne
        $stmt = $pdo->query("SELECT AVG(duree) as avg_duree FROM intervention");
        $stats['avg_duree'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_duree'] ?? 0);

        // Interventions annulées
        $stmt = $pdo->query("SELECT COUNT(*) as total_annulees FROM intervention_annulee");
        $stats['total_annulees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_annulees'];

        return $stats;
    }

    // ==================== JOINTURE — Interventions avec annulations ====================

    public function getInterventionsWithAnnulations() {
        $pdo = config::getConnexion();
        $sql = "SELECT i.*, ia.raison AS annulation_raison, ia.dateAnnulation AS annulation_date 
                FROM intervention i 
                LEFT JOIN intervention_annulee ia ON i.id = ia.idIntervention 
                ORDER BY i.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInterventionWithAnnulation($id) {
        $pdo = config::getConnexion();
        $sql = "SELECT i.*, ia.raison AS annulation_raison, ia.dateAnnulation AS annulation_date 
                FROM intervention i 
                LEFT JOIN intervention_annulee ia ON i.id = ia.idIntervention 
                WHERE i.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
