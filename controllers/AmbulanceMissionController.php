<?php
require_once __DIR__ . '/../models/Ambulance.php';
require_once __DIR__ . '/../models/Mission.php';
require_once __DIR__ . '/../config.php';

class AmbulanceMissionController {

    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
        // Switch to 'sante' database where ambulance & mission tables live
        $this->pdo->exec("USE sante");
    }

    /* ══════════════════════════════════════════
     *  AMBULANCE – READ
     * ══════════════════════════════════════════ */

    public function getAllAmbulances(array $filters = []): array {
        try {
            // JOIN mission to get the count of missions per ambulance
            $sql    = "SELECT a.*,
                              COUNT(m.idMission)            AS nb_missions,
                              SUM(m.estTerminee = 0)        AS missions_en_cours,
                              SUM(m.estTerminee = 1)        AS missions_terminees
                       FROM ambulance a
                       LEFT JOIN mission m ON m.idAmbulance = a.idAmbulance
                       WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $sql .= " AND (a.immatriculation LIKE ? OR a.modele LIKE ? OR a.statut LIKE ?)";
                $t = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$t, $t, $t]);
            }
            if (isset($filters['disponible']) && $filters['disponible'] !== '') {
                $sql .= " AND a.estDisponible = ?";
                $params[] = (int)$filters['disponible'];
            }
            if (!empty($filters['statut'])) {
                $sql .= " AND a.statut = ?";
                $params[] = $filters['statut'];
            }

            $sql .= " GROUP BY a.idAmbulance ORDER BY a.idAmbulance DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public function getAmbulanceById(int $id): ?array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM ambulance WHERE idAmbulance = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getAmbulanceStats(): array {
        try {
            $total      = $this->pdo->query("SELECT COUNT(*) FROM ambulance")->fetchColumn();
            $available  = $this->pdo->query("SELECT COUNT(*) FROM ambulance WHERE estDisponible = 1")->fetchColumn();
            $enService  = $this->pdo->query("SELECT COUNT(*) FROM ambulance WHERE statut = 'En service'")->fetchColumn();
            return [
                'total'     => (int)$total,
                'available' => (int)$available,
                'enService' => (int)$enService,
            ];
        } catch (Exception $e) {
            return ['total' => 0, 'available' => 0, 'enService' => 0];
        }
    }

    /* ══════════════════════════════════════════
     *  AMBULANCE – WRITE (Admin only)
     * ══════════════════════════════════════════ */

    public function createAmbulance(array $data): array {
        try {
            if (empty($data['immatriculation']) || empty($data['modele'])) {
                return ['success' => false, 'message' => "L'immatriculation et le modèle sont obligatoires."];
            }
            $stmt = $this->pdo->prepare(
                "INSERT INTO ambulance (immatriculation, statut, modele, capacite, estDisponible)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                htmlspecialchars(trim($data['immatriculation'])),
                $data['statut'] ?? 'En service',
                htmlspecialchars(trim($data['modele'])),
                (int)($data['capacite'] ?? 2),
                isset($data['estDisponible']) ? 1 : 0,
            ]);
            return ['success' => true, 'message' => "Ambulance créée avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateAmbulance(int $id, array $data): array {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE ambulance
                 SET immatriculation=?, statut=?, modele=?, capacite=?, estDisponible=?
                 WHERE idAmbulance=?"
            );
            $stmt->execute([
                htmlspecialchars(trim($data['immatriculation'] ?? '')),
                $data['statut'] ?? 'En service',
                htmlspecialchars(trim($data['modele'] ?? '')),
                (int)($data['capacite'] ?? 2),
                isset($data['estDisponible']) ? 1 : 0,
                $id,
            ]);
            return ['success' => true, 'message' => "Ambulance mise à jour avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteAmbulance(int $id): array {
        try {
            // Delete related missions first
            $this->pdo->prepare("DELETE FROM mission WHERE idAmbulance = ?")->execute([$id]);
            $stmt = $this->pdo->prepare("DELETE FROM ambulance WHERE idAmbulance = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => "Ambulance supprimée avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /* ══════════════════════════════════════════
     *  MISSION – READ
     * ══════════════════════════════════════════ */

    public function getAllMissions(array $filters = []): array {
        try {
            // Explicit aliases to avoid column name collision between mission & ambulance
            $sql    = "SELECT
                          m.idMission,
                          m.dateDebut,
                          m.dateFin,
                          m.typeMission,
                          m.lieuDepart,
                          m.lieuArrivee,
                          m.equipe,
                          m.estTerminee,
                          m.idAmbulance,
                          a.immatriculation  AS amb_immatriculation,
                          a.modele           AS amb_modele,
                          a.statut           AS amb_statut,
                          a.estDisponible    AS amb_estDisponible
                       FROM mission m
                       LEFT JOIN ambulance a ON a.idAmbulance = m.idAmbulance
                       WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $sql .= " AND (m.typeMission LIKE ? OR m.lieuDepart LIKE ? OR m.lieuArrivee LIKE ? OR m.equipe LIKE ? OR a.immatriculation LIKE ?)";
                $t = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$t, $t, $t, $t, $t]);
            }
            if (isset($filters['estTerminee']) && $filters['estTerminee'] !== '') {
                $sql .= " AND m.estTerminee = ?";
                $params[] = (int)$filters['estTerminee'];
            }
            if (!empty($filters['typeMission'])) {
                $sql .= " AND m.typeMission = ?";
                $params[] = $filters['typeMission'];
            }
            if (!empty($filters['idAmbulance'])) {
                $sql .= " AND m.idAmbulance = ?";
                $params[] = (int)$filters['idAmbulance'];
            }

            $sql .= " ORDER BY m.idMission DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public function getMissionById(int $id): ?array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT m.*, a.immatriculation, a.modele
                 FROM mission m
                 LEFT JOIN ambulance a ON m.idAmbulance = a.idAmbulance
                 WHERE m.idMission = ?"
            );
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getMissionStats(): array {
        try {
            $total     = $this->pdo->query("SELECT COUNT(*) FROM mission")->fetchColumn();
            $ongoing   = $this->pdo->query("SELECT COUNT(*) FROM mission WHERE estTerminee = 0")->fetchColumn();
            $completed = $this->pdo->query("SELECT COUNT(*) FROM mission WHERE estTerminee = 1")->fetchColumn();
            return [
                'total'     => (int)$total,
                'ongoing'   => (int)$ongoing,
                'completed' => (int)$completed,
            ];
        } catch (Exception $e) {
            return ['total' => 0, 'ongoing' => 0, 'completed' => 0];
        }
    }

    /* ══════════════════════════════════════════
     *  MISSION – WRITE (Admin only)
     * ══════════════════════════════════════════ */

    public function createMission(array $data): array {
        try {
            if (empty($data['dateDebut']) || empty($data['typeMission']) || empty($data['idAmbulance'])) {
                return ['success' => false, 'message' => "La date de début, le type de mission et l'ambulance sont obligatoires."];
            }
            $stmt = $this->pdo->prepare(
                "INSERT INTO mission (dateDebut, dateFin, typeMission, lieuDepart, lieuArrivee, equipe, estTerminee, idAmbulance)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['dateDebut'],
                !empty($data['dateFin']) ? $data['dateFin'] : null,
                htmlspecialchars(trim($data['typeMission'])),
                htmlspecialchars(trim($data['lieuDepart'] ?? '')),
                htmlspecialchars(trim($data['lieuArrivee'] ?? '')),
                htmlspecialchars(trim($data['equipe'] ?? '')),
                isset($data['estTerminee']) ? 1 : 0,
                (int)$data['idAmbulance'],
            ]);
            return ['success' => true, 'message' => "Mission créée avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateMission(int $id, array $data): array {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE mission
                 SET dateDebut=?, dateFin=?, typeMission=?, lieuDepart=?, lieuArrivee=?, equipe=?, estTerminee=?, idAmbulance=?
                 WHERE idMission=?"
            );
            $stmt->execute([
                $data['dateDebut'] ?? '',
                !empty($data['dateFin']) ? $data['dateFin'] : null,
                htmlspecialchars(trim($data['typeMission'] ?? '')),
                htmlspecialchars(trim($data['lieuDepart'] ?? '')),
                htmlspecialchars(trim($data['lieuArrivee'] ?? '')),
                htmlspecialchars(trim($data['equipe'] ?? '')),
                isset($data['estTerminee']) ? 1 : 0,
                (int)($data['idAmbulance'] ?? 0),
                $id,
            ]);
            return ['success' => true, 'message' => "Mission mise à jour avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteMission(int $id): array {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM mission WHERE idMission = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => "Mission supprimée avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /* Helper: all ambulances for <select> dropdowns */
    public function getAmbulancesForSelect(): array {
        try {
            $stmt = $this->pdo->query("SELECT idAmbulance, immatriculation, modele FROM ambulance ORDER BY immatriculation");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
