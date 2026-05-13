<?php
require_once __DIR__ . '/../models/Ambulance.php';
require_once __DIR__ . '/../models/Mission.php';
require_once __DIR__ . '/../models/DemandeAmbulance.php';
require_once __DIR__ . '/../config.php';

class AmbulanceMissionController {

    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    /* ══════════════════════════════════════════
     *  AMBULANCE – READ
     * ══════════════════════════════════════════ */

    public function getAllAmbulances(array $filters = []): array {
        try {
            $page  = max(1, (int)($filters['page'] ?? 1));
            $limit = max(1, (int)($filters['limit'] ?? 5));
            $offset = ($page - 1) * $limit;

            $sqlCount = "SELECT COUNT(*) FROM ambulance a WHERE 1=1";
            $sql      = "SELECT a.*,
                               COUNT(m.idMission)            AS nb_missions,
                               SUM(IFNULL(m.estTerminee = 0, 0)) AS missions_en_cours,
                               SUM(IFNULL(m.estTerminee = 1, 0)) AS missions_terminees
                        FROM ambulance a
                        LEFT JOIN mission m ON m.idAmbulance = a.idAmbulance
                        WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $searchSql = " AND (a.immatriculation LIKE ? OR a.modele LIKE ? OR a.statut LIKE ?)";
                $sqlCount .= $searchSql;
                $sql      .= $searchSql;
                $t = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$t, $t, $t]);
            }
            if (isset($filters['disponible']) && $filters['disponible'] !== '') {
                $dispSql = " AND a.estDisponible = ?";
                $sqlCount .= $dispSql;
                $sql      .= $dispSql;
                $params[] = (int)$filters['disponible'];
            }
            if (!empty($filters['statut'])) {
                $statSql = " AND a.statut = ?";
                $sqlCount .= $statSql;
                $sql      .= $statSql;
                $params[] = $filters['statut'];
            }

            $total = 0;
            $stmtCount = $this->pdo->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetchColumn();

            $sql .= " GROUP BY a.idAmbulance ORDER BY a.idAmbulance DESC LIMIT $limit OFFSET $offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($missions as &$m) {
                if (empty($m['meteo']) && !empty($m['lieuDepart'])) {
                    $m['meteo'] = $this->fetchWeather($m['lieuDepart']);
                    $this->pdo->prepare("UPDATE mission SET meteo = ? WHERE idMission = ?")
                              ->execute([$m['meteo'], $m['idMission']]);
                }
            }
            return [
                'success' => true, 
                'data'    => $missions,
                'total'   => $total,
                'pages'   => ceil($total / $limit),
                'page'    => $page,
                'limit'   => $limit
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => [], 'total' => 0];
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
            $total         = $this->pdo->query("SELECT COUNT(*) FROM ambulance")->fetchColumn();
            $available     = $this->pdo->query("SELECT COUNT(*) FROM ambulance WHERE estDisponible = 1")->fetchColumn();
            $enService     = $this->pdo->query("SELECT COUNT(*) FROM ambulance WHERE statut = 'En service'")->fetchColumn();
            $enMaintenance = $this->pdo->query("SELECT COUNT(*) FROM ambulance WHERE statut = 'En maintenance'")->fetchColumn();
            $horsService   = $this->pdo->query("SELECT COUNT(*) FROM ambulance WHERE statut = 'Hors service'")->fetchColumn();
            return [
                'total'         => (int)$total,
                'available'     => (int)$available,
                'enService'     => (int)$enService,
                'enMaintenance' => (int)$enMaintenance,
                'horsService'   => (int)$horsService,
            ];
        } catch (Exception $e) {
            return ['total'=>0,'available'=>0,'enService'=>0,'enMaintenance'=>0,'horsService'=>0];
        }
    }

    /* ══════════════════════════════════════════
     *  AMBULANCE – WRITE (Admin only)
     * ══════════════════════════════════════════ */

    public function createAmbulance(array $data): array {
        try {
            $immatriculation = trim($data['immatriculation'] ?? '');
            $modele = trim($data['modele'] ?? '');
            $capacite = trim($data['capacite'] ?? '2');

            if ($immatriculation === '' || $modele === '') {
                return ['success' => false, 'message' => "L'immatriculation et le modèle sont obligatoires."];
            }
            if (!is_numeric($capacite) || (int)$capacite < 1) {
                return ['success' => false, 'message' => "La capacité doit être un nombre supérieur ou égal à 1."];
            }

            // Robust insert: handle missing columns if necessary
            try {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO ambulance (immatriculation, statut, modele, capacite, estDisponible, lat, lng)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    htmlspecialchars($immatriculation),
                    $data['statut'] ?? 'En service',
                    htmlspecialchars($modele),
                    (int)$capacite,
                    isset($data['estDisponible']) ? 1 : 0,
                    !empty($data['lat']) ? (float)$data['lat'] : null,
                    !empty($data['lng']) ? (float)$data['lng'] : null,
                ]);
            } catch (Exception $e) {
                // Fallback to old schema if lat/lng are missing
                $stmt = $this->pdo->prepare(
                    "INSERT INTO ambulance (immatriculation, statut, modele, capacite, estDisponible)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    htmlspecialchars($immatriculation),
                    $data['statut'] ?? 'En service',
                    htmlspecialchars($modele),
                    (int)$capacite,
                    isset($data['estDisponible']) ? 1 : 0
                ]);
            }
            return ['success' => true, 'message' => "Ambulance créée avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateAmbulance(int $id, array $data): array {
        try {
            $immatriculation = trim($data['immatriculation'] ?? '');
            $modele = trim($data['modele'] ?? '');
            $capacite = trim($data['capacite'] ?? '2');

            if ($immatriculation === '' || $modele === '') {
                return ['success' => false, 'message' => "L'immatriculation et le modèle sont obligatoires."];
            }
            if (!is_numeric($capacite) || (int)$capacite < 1) {
                return ['success' => false, 'message' => "La capacité doit être un nombre supérieur ou égal à 1."];
            }

            // Robust update: handle missing columns if necessary
            try {
                $stmt = $this->pdo->prepare(
                    "UPDATE ambulance
                     SET immatriculation=?, statut=?, modele=?, capacite=?, estDisponible=?, lat=?, lng=?
                     WHERE idAmbulance=?"
                );
                $stmt->execute([
                    htmlspecialchars($immatriculation),
                    $data['statut'] ?? 'En service',
                    htmlspecialchars($modele),
                    (int)$capacite,
                    isset($data['estDisponible']) ? 1 : 0,
                    !empty($data['lat']) ? (float)$data['lat'] : null,
                    !empty($data['lng']) ? (float)$data['lng'] : null,
                    $id,
                ]);
            } catch (Exception $e) {
                // Fallback to old schema if lat/lng are missing
                $stmt = $this->pdo->prepare(
                    "UPDATE ambulance
                     SET immatriculation=?, statut=?, modele=?, capacite=?, estDisponible=?
                     WHERE idAmbulance=?"
                );
                $stmt->execute([
                    htmlspecialchars($immatriculation),
                    $data['statut'] ?? 'En service',
                    htmlspecialchars($modele),
                    (int)$capacite,
                    isset($data['estDisponible']) ? 1 : 0,
                    $id,
                ]);
            }
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
            $page  = max(1, (int)($filters['page'] ?? 1));
            $limit = max(1, (int)($filters['limit'] ?? 5));
            $offset = ($page - 1) * $limit;

            $sqlCount = "SELECT COUNT(*) FROM mission m LEFT JOIN ambulance a ON a.idAmbulance = m.idAmbulance WHERE 1=1";
            $sql      = "SELECT
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
                $searchSql = " AND (m.typeMission LIKE ? OR m.lieuDepart LIKE ? OR m.lieuArrivee LIKE ? OR m.equipe LIKE ? OR a.immatriculation LIKE ?)";
                $sqlCount .= $searchSql;
                $sql      .= $searchSql;
                $t = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$t, $t, $t, $t, $t]);
            }
            if (isset($filters['estTerminee']) && $filters['estTerminee'] !== '') {
                $termSql = " AND m.estTerminee = ?";
                $sqlCount .= $termSql;
                $sql      .= $termSql;
                $params[] = (int)$filters['estTerminee'];
            }
            if (!empty($filters['typeMission'])) {
                $typeSql = " AND m.typeMission = ?";
                $sqlCount .= $typeSql;
                $sql      .= $typeSql;
                $params[] = $filters['typeMission'];
            }
            if (!empty($filters['idAmbulance'])) {
                $ambSql = " AND m.idAmbulance = ?";
                $sqlCount .= $ambSql;
                $sql      .= $ambSql;
                $params[] = (int)$filters['idAmbulance'];
            }

            $stmtCount = $this->pdo->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetchColumn();

            $sql .= " ORDER BY m.idMission DESC LIMIT $limit OFFSET $offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($missions as &$m) {
                if (empty($m['meteo']) && !empty($m['lieuDepart'])) {
                    $m['meteo'] = $this->fetchWeather($m['lieuDepart']);
                    $this->pdo->prepare("UPDATE mission SET meteo = ? WHERE idMission = ?")
                              ->execute([$m['meteo'], $m['idMission']]);
                }
            }
            return [
                'success' => true, 
                'data'    => $missions,
                'total'   => $total,
                'pages'   => ceil($total / $limit),
                'page'    => $page,
                'limit'   => $limit
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => [], 'total' => 0];
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
            $dateD = $data['dateDebut'] ?? $data['dateHeure'] ?? null;
            if (empty($dateD) || empty($data['typeMission']) || empty($data['idAmbulance'])) {
                return ['success' => false, 'message' => "La date de début, le type de mission et l'ambulance sont obligatoires."];
            }
            $hasActiveTransaction = $this->pdo->inTransaction();
            if (!$hasActiveTransaction) {
                $this->pdo->beginTransaction();
            }

            // Fetch weather and adjust duration
            $meteo = $this->fetchWeather($data['lieuDepart'] ?? 'Tunis');
            
            $stmt = $this->pdo->prepare(
                "INSERT INTO mission (dateDebut, dateFin, typeMission, lieuDepart, lieuArrivee, equipe, estTerminee, idAmbulance, meteo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $dateD,
                !empty($data['dateFin']) ? $data['dateFin'] : null,
                htmlspecialchars(trim($data['typeMission'])),
                htmlspecialchars(trim($data['lieuDepart'] ?? '')),
                htmlspecialchars(trim($data['lieuArrivee'] ?? '')),
                htmlspecialchars(trim($data['equipe'] ?? '')),
                isset($data['estTerminee']) ? 1 : 0,
                (int)$data['idAmbulance'],
                $meteo
            ]);
            $missionId = $this->pdo->lastInsertId();

            // Auto-update ambulance status if mission is ongoing
            if (!isset($data['estTerminee']) || $data['estTerminee'] == 0) {
                $this->pdo->prepare("UPDATE ambulance SET estDisponible = 0, statut = 'En service' WHERE idAmbulance = ?")
                          ->execute([(int)$data['idAmbulance']]);
            } else {
                // If created as FINISHED, trigger notification
                $mId = $missionId;
                $msg = "Nouvelle Mission #{$mId} terminée immédiatement le " . date('d/m H:i');
                $this->pdo->prepare("INSERT INTO notifications_queue (idMission, message, role_destinataire) VALUES (?, ?, ?)")
                          ->execute([$mId, $msg, 'admin']);
            }

            if (!$hasActiveTransaction) {
                $this->pdo->commit();
            }
            return ['success' => true, 'message' => "Mission créée avec succès.", 'idMission' => $missionId];
        } catch (Exception $e) {
            if (!$hasActiveTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateMission(int $id, array $data): array {
        try {
            // Fix: Check if idAmbulance exists to avoid Foreign Key violation during update
            $idAmbulance = (int)($data['idAmbulance'] ?? 0);
            if ($idAmbulance > 0) {
                $checkAmb = $this->pdo->prepare("SELECT idAmbulance FROM ambulance WHERE idAmbulance = ?");
                $checkAmb->execute([$idAmbulance]);
                if (!$checkAmb->fetch()) {
                    $idAmbulance = null;
                }
            } else {
                $idAmbulance = null;
            }

            $stmt = $this->pdo->prepare(
                "UPDATE mission
                 SET dateDebut=?, dateFin=?, typeMission=?, lieuDepart=?, lieuArrivee=?, equipe=?, estTerminee=?, idAmbulance=?, meteo=?
                 WHERE idMission=?"
            );
            $meteo = $data['meteo'] ?? $this->fetchWeather($data['lieuDepart'] ?? 'Tunis');
            $stmt->execute([
                $data['dateDebut'] ?? '',
                !empty($data['dateFin']) ? $data['dateFin'] : null,
                htmlspecialchars(trim($data['typeMission'] ?? '')),
                htmlspecialchars(trim($data['lieuDepart'] ?? '')),
                htmlspecialchars(trim($data['lieuArrivee'] ?? '')),
                htmlspecialchars(trim($data['equipe'] ?? '')),
                isset($data['estTerminee']) ? 1 : 0,
                $idAmbulance,
                $meteo,
                $id,
            ]);

            // If mission completed, make ambulance available and trigger notification
            if (isset($data['estTerminee']) && $data['estTerminee'] == 1) {
                $m = $this->getMissionById($id);
                if ($m) {
                    $this->pdo->prepare("UPDATE ambulance SET estDisponible = 1 WHERE idAmbulance = ?")
                              ->execute([(int)$m['idAmbulance']]);
                    
                    // Queue Real-time Notification for Windows Toast
                    $msg = "Mission #{$id} Terminée par l'ambulance " . ($m['immatriculation'] ?? 'N/A') . " le " . date('d/m H:i');
                    $this->pdo->prepare("INSERT INTO notifications_queue (idMission, message, role_destinataire) VALUES (?, ?, ?)")
                              ->execute([$id, $msg, 'admin']);
                }
            }

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

    /* ══════════════════════════════════════════
     *  DEMANDE AMBULANCE
     * ══════════════════════════════════════════ */

    public function getAllDemandes(array $filters = []): array {
        try {
            $page  = max(1, (int)($filters['page'] ?? 1));
            $limit = max(1, (int)($filters['limit'] ?? 5));
            $offset = ($page - 1) * $limit;

            $sqlCount = "SELECT COUNT(*) FROM demande_ambulance d WHERE 1=1";
            $sql      = "SELECT d.*, u.nom AS med_nom, u.prenom AS med_prenom
                        FROM demande_ambulance d
                        LEFT JOIN user.utilisateur u ON d.idMedecin = u.id_utilisateur
                        WHERE 1=1";
            $params = [];

            if (!empty($filters['idMedecin'])) {
                $sqlCount .= " AND d.idMedecin = ?";
                $sql      .= " AND d.idMedecin = ?";
                $params[] = (int)$filters['idMedecin'];
            }
            if (!empty($filters['statut'])) {
                $sqlCount .= " AND d.statut = ?";
                $sql      .= " AND d.statut = ?";
                $params[] = $filters['statut'];
            }
            if (!empty($filters['search'])) {
                $searchSql = " AND (d.typeMission LIKE ? OR d.lieuDepart LIKE ? OR d.lieuArrivee LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
                $sqlCount .= $searchSql;
                $sql      .= $searchSql;
                $t = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$t, $t, $t, $t, $t]);
            }

            $stmtCount = $this->pdo->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetchColumn();

            $sql .= " ORDER BY d.dateCreation DESC LIMIT $limit OFFSET $offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'data'    => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total'   => $total,
                'pages'   => ceil($total / $limit),
                'page'    => $page,
                'limit'   => $limit
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => [], 'total' => 0];
        }
    }

    public function createDemande(array $data): array {
        try {
            if (empty($data['typeMission']) || empty($data['lieuDepart']) || empty($data['lieuArrivee']) || empty($data['dateHeure'])) {
                return ['success' => false, 'message' => "Tous les champs obligatoires doivent être remplis."];
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO demande_ambulance (typeMission, lieuDepart, lieuArrivee, dateHeure, remarques, idMedecin, statut)
                 VALUES (?, ?, ?, ?, ?, ?, 'en attente')"
            );
            $stmt->execute([
                htmlspecialchars($data['typeMission']),
                htmlspecialchars($data['lieuDepart']),
                htmlspecialchars($data['lieuArrivee']),
                $data['dateHeure'],
                !empty($data['remarques']) ? htmlspecialchars($data['remarques']) : null,
                (int)$data['idMedecin']
            ]);
            return ['success' => true, 'message' => "Votre demande d'ambulance a été envoyée avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateDemandeStatus(int $idDemande, string $statut, ?int $idAmbulance = null): array {
        try {
            $hasActiveTransaction = $this->pdo->inTransaction();
            if (!$hasActiveTransaction) {
                $this->pdo->beginTransaction();
            }

            if ($statut === 'acceptee') {
                if (!$idAmbulance) {
                    throw new Exception("Une ambulance doit être assignée pour accepter une demande.");
                }

                // Get demande info
                $stmt = $this->pdo->prepare("SELECT * FROM demande_ambulance WHERE idDemande = ?");
                $stmt->execute([$idDemande]);
                $demande = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$demande) throw new Exception("Demande non trouvée.");

                // Create mission
                $res = $this->createMission([
                    'dateDebut'   => $demande['dateHeure'],
                    'typeMission' => $demande['typeMission'],
                    'lieuDepart'  => $demande['lieuDepart'],
                    'lieuArrivee' => $demande['lieuArrivee'],
                    'idAmbulance' => $idAmbulance,
                    'equipe'      => "Assignée par Admin"
                ]);

                if (!$res['success']) throw new Exception($res['message']);

                // Update demande with mission link
                $stmt = $this->pdo->prepare("UPDATE demande_ambulance SET statut = 'acceptee', idMission = ? WHERE idDemande = ?");
                $stmt->execute([$res['idMission'], $idDemande]);

            } else {
                $stmt = $this->pdo->prepare("UPDATE demande_ambulance SET statut = ? WHERE idDemande = ?");
                $stmt->execute([$statut, $idDemande]);
            }

            if (!$hasActiveTransaction) {
                $this->pdo->commit();
            }
            return ['success' => true, 'message' => "Demande mise à jour (" . $statut . ")"];
        } catch (Exception $e) {
            if (!$hasActiveTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getDemandeStats(?int $idMedecin = null): array {
        try {
            $where = $idMedecin ? " WHERE idMedecin = " . (int)$idMedecin : "";
            
            $total    = $this->pdo->query("SELECT COUNT(*) FROM demande_ambulance" . $where)->fetchColumn();
            $pending  = $this->pdo->query("SELECT COUNT(*) FROM demande_ambulance" . ($idMedecin ? $where . " AND statut = 'en attente'" : " WHERE statut = 'en attente'"))->fetchColumn();
            $accepted = $this->pdo->query("SELECT COUNT(*) FROM demande_ambulance" . ($idMedecin ? $where . " AND statut = 'acceptee'" : " WHERE statut = 'acceptee'"))->fetchColumn();
            $refused  = $this->pdo->query("SELECT COUNT(*) FROM demande_ambulance" . ($idMedecin ? $where . " AND statut = 'refusee'" : " WHERE statut = 'refusee'"))->fetchColumn();
            
            return [
                'total'    => (int)$total,
                'pending'  => (int)$pending,
                'accepted' => (int)$accepted,
                'refused'  => (int)$refused
            ];
        } catch (Exception $e) {
            return ['total'=>0,'pending'=>0,'accepted'=>0,'refused'=>0];
        }
    }

    /* Helper: all ambulances for <select> dropdowns */
    public function getAmbulancesForSelect(): array {
        try {
            $stmt = $this->pdo->query("SELECT idAmbulance, immatriculation, modele FROM ambulance WHERE estDisponible = 1 ORDER BY immatriculation");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAmbulancesForMap(): array {
        try {
            $stmt = $this->pdo->query("SELECT idAmbulance, immatriculation, modele, statut, estDisponible, lat, lng FROM ambulance");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function createDemandeFromMap($data): array {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO demande_ambulance (idMedecin, lieuDepart, lieuArrivee, dateHeure, typeMission, statut, remarques)
                 VALUES (?, ?, ?, NOW(), 'Urgence (Carte)', 'en attente', ?)"
            );
            $remarques = "Distance: " . $data['distance'] . " | Temps estimé: " . $data['temps_estime'];
            $stmt->execute([
                (int)$data['idUtilisateur'],
                htmlspecialchars($data['lieu_depart']),
                htmlspecialchars($data['lieu_destination']),
                $remarques
            ]);
            return ['success' => true, 'message' => "Demande enregistrée avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * WEATHER LOGIC - OpenWeatherMap API
     */
    private function fetchWeather($city): string {
        $apiKey = "04a29a0d8e8267098e72c0828773950d"; 
        
        // Handle Map-based locations or generic names
        if (strpos($city, '(Carte)') !== false || strlen($city) < 3) {
            $cityClean = "Tunis";
        } else {
            $cityClean = trim(explode(' ', $city)[0]); 
        }
        
        $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($cityClean) . ",TN&units=metric&appid={$apiKey}&lang=fr";
        
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 2]]);
            $response = @file_get_contents($url, false, $ctx);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                if (isset($data['main'])) {
                    $temp = round($data['main']['temp']);
                    $cond = ucfirst($data['weather'][0]['description']);
                    $icon = $data['weather'][0]['icon'];
                    return "{$temp}|{$cond}|{$icon}";
                }
            }
        } catch (Exception $e) { }

        // Final Fallback if API fails or city not found
        return "24|Beau temps|01d";
    }
}
