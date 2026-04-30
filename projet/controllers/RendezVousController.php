<?php
require_once __DIR__ . '/../models/RendezVous.php';
require_once __DIR__ . '/../config.php';

class RendezVousController {
    private $pdo;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure' => false,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
        }
        $this->pdo = config::getConnexion();
    }

    public function getAllRendezVous($filters = [], $role = 'admin', $userId = null): array {
        try {
            $sql = 'SELECT r.*, u1.nom as client_nom, u1.prenom as client_prenom, u2.nom as medecin_nom, u2.prenom as medecin_prenom 
                    FROM rendezvous r 
                    LEFT JOIN utilisateur u1 ON r.idClient = u1.id_utilisateur 
                    LEFT JOIN utilisateur u2 ON r.idMedecin = u2.id_utilisateur 
                    WHERE 1=1';
            $params = [];
            
            if ($role === 'patient' && $userId !== null) {
                $sql .= ' AND r.idClient = ?';
                $params[] = $userId;
            } elseif ($role === 'medecin' && $userId !== null) {
                $sql .= ' AND r.idMedecin = ?';
                $params[] = $userId;
            }
            
            if (!empty($filters['search'])) {
                $sql .= ' AND (u1.nom LIKE ? OR u1.prenom LIKE ? OR u2.nom LIKE ? OR r.motif LIKE ? OR r.typeConsultation LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['statut'])) {
                $sql .= ' AND r.statut = ?';
                $params[] = $filters['statut'];
            }
            
            $sql .= ' ORDER BY r.dateHeureDebut DESC';
            
            $req = $this->pdo->prepare($sql);
            $req->execute($params);
            
            $rdvs = $req->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                "success" => true,
                "rdvs" => $rdvs,
                "count" => count($rdvs)
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getRendezVousById($id): ?array {
        try {
            $sql = 'SELECT r.*, u1.nom as client_nom, u1.prenom as client_prenom, u2.nom as medecin_nom, u2.prenom as medecin_prenom 
                    FROM rendezvous r 
                    LEFT JOIN utilisateur u1 ON r.idClient = u1.id_utilisateur 
                    LEFT JOIN utilisateur u2 ON r.idMedecin = u2.id_utilisateur 
                    WHERE r.idRDV = ?';
            $req = $this->pdo->prepare($sql);
            $req->execute([$id]);
            $rdv = $req->fetch(PDO::FETCH_ASSOC);
            return $rdv ?: null;
        } catch (Exception $e) {
            error_log("Erreur getRendezVousById: " . $e->getMessage());
            return null;
        }
    }

    public function createRendezVous($data): array {
        try {
            $required = ['dateHeureDebut', 'dateHeureFin', 'typeConsultation', 'idClient', 'idMedecin'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ["success" => false, "message" => "Le champ $field est obligatoire"];
                }
            }
            
            $sql = 'INSERT INTO rendezvous (dateHeureDebut, dateHeureFin, statut, typeConsultation, motif, idClient, idMedecin) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)';
            $req = $this->pdo->prepare($sql);
            $success = $req->execute([
                $data['dateHeureDebut'],
                $data['dateHeureFin'],
                $data['statut'] ?? 'planifie',
                htmlspecialchars($data['typeConsultation']),
                htmlspecialchars($data['motif'] ?? ''),
                $data['idClient'],
                $data['idMedecin']
            ]);
            
            if ($success) {
                return ["success" => true, "message" => "Rendez-vous créé avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la création du rendez-vous"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function updateRendezVous($id, $data): array {
        try {
            $rdv = $this->getRendezVousById($id);
            if (!$rdv) {
                return ["success" => false, "message" => "Rendez-vous non trouvé"];
            }
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['dateHeureDebut', 'dateHeureFin', 'statut', 'typeConsultation', 'motif', 'idMedecin'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = htmlspecialchars($data[$field]);
                }
            }
            
            if (empty($updates)) {
                return ["success" => false, "message" => "Aucune donnée à mettre à jour"];
            }
            
            $params[] = $id;
            $sql = "UPDATE rendezvous SET " . implode(', ', $updates) . " WHERE idRDV = ?";
            
            $req = $this->pdo->prepare($sql);
            $success = $req->execute($params);
            
            if ($success) {
                return ["success" => true, "message" => "Rendez-vous mis à jour avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la mise à jour"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function deleteRendezVous($id): array {
        try {
            $rdv = $this->getRendezVousById($id);
            if (!$rdv) {
                return ["success" => false, "message" => "Rendez-vous non trouvé"];
            }
            
            // Delete associated fiche
            $this->pdo->prepare("DELETE FROM ficherendezvous WHERE idRDV = ?")->execute([$id]);
            
            $req = $this->pdo->prepare("DELETE FROM rendezvous WHERE idRDV = ?");
            $success = $req->execute([$id]);
            
            if ($success) {
                return ["success" => true, "message" => "Rendez-vous supprimé avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la suppression"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getStats($role = 'admin', $userId = null): array {
        try {
            $cond = "1=1";
            $params = [];
            if ($role === 'patient' && $userId) {
                $cond = "idClient = ?";
                $params[] = $userId;
            } elseif ($role === 'medecin' && $userId) {
                $cond = "idMedecin = ?";
                $params[] = $userId;
            }

            // Total RDV
            $req = $this->pdo->prepare("SELECT COUNT(*) as total FROM rendezvous WHERE $cond");
            $req->execute($params);
            $total = $req->fetch(PDO::FETCH_ASSOC)['total'];

            // Par statut
            $req = $this->pdo->prepare("SELECT statut, COUNT(*) as count FROM rendezvous WHERE $cond GROUP BY statut");
            $req->execute($params);
            $byStatus = $req->fetchAll(PDO::FETCH_ASSOC);

            // Ce mois
            $sqlCeMois = "SELECT COUNT(*) as ce_mois FROM rendezvous WHERE $cond AND MONTH(dateHeureDebut) = MONTH(CURRENT_DATE()) AND YEAR(dateHeureDebut) = YEAR(CURRENT_DATE())";
            $req = $this->pdo->prepare($sqlCeMois);
            $req->execute($params);
            $ceMois = $req->fetch(PDO::FETCH_ASSOC)['ce_mois'];

            return [
                'total' => $total,
                'ce_mois' => $ceMois,
                'by_status' => $byStatus
            ];
        } catch (Exception $e) {
            return ['total' => 0, 'ce_mois' => 0, 'by_status' => []];
        }
    }
}
