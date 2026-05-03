<?php
require_once __DIR__ . '/../models/Distribution.php';
require_once __DIR__ . '/../controllers/LotMedicamentController.php';
require_once __DIR__ . '/../config.php';

class DistributionController {
    private $pdo;
    private $lotController;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure' => false,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
        }
        $this->pdo = config::getConnexion();
        $this->lotController = new LotMedicamentController();
    }

    public function getAllDistributions($filters = []): array {
        try {
            $sql = 'SELECT d.*, lm.nom_medicament 
                    FROM distribution d 
                    JOIN lot_medicament lm ON d.id_lot = lm.id_lot 
                    WHERE 1=1';
            $params = [];
            
            if (!empty($filters['search'])) {
                $sql .= ' AND (lm.nom_medicament LIKE ? OR d.patient LIKE ? OR d.responsable LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= ' ORDER BY d.date_distribution DESC';
            
            $req = $this->pdo->prepare($sql);
            $req->execute($params);
            
            $distributions = $req->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                "success" => true,
                "distributions" => $distributions,
                "count" => count($distributions)
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getDistributionById($id): ?array {
        try {
            $sql = 'SELECT d.*, lm.nom_medicament 
                    FROM distribution d 
                    JOIN lot_medicament lm ON d.id_lot = lm.id_lot 
                    WHERE d.id_distribution = ?';
            $req = $this->pdo->prepare($sql);
            $req->execute([$id]);
            $distribution = $req->fetch(PDO::FETCH_ASSOC);
            return $distribution ?: null;
        } catch (Exception $e) {
            error_log("Erreur getDistributionById: " . $e->getMessage());
            return null;
        }
    }

    public function createDistribution($data): array {
        try {
            $required = ['id_lot', 'date_distribution', 'quantite_distribuee', 'patient', 'responsable'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ["success" => false, "message" => "Le champ $field est obligatoire", "field" => $field];
                }
            }

            if ($data['quantite_distribuee'] <= 0) {
                return ["success" => false, "message" => "La quantité distribuée doit être supérieure à zéro.", "field" => "quantite_distribuee"];
            }

            // Vérifier la quantité restante
            $lot = $this->lotController->getLotMedicamentById($data['id_lot']);
            if (!$lot) {
                return ["success" => false, "message" => "Lot de médicament introuvable.", "field" => "id_lot"];
            }

            if ($data['quantite_distribuee'] > $lot['quantite_restante']) {
                return ["success" => false, "message" => "Quantité insuffisante dans le lot. Restant: " . $lot['quantite_restante'], "field" => "quantite_distribuee"];
            }
            
            $sql = 'INSERT INTO distribution (id_lot, date_distribution, quantite_distribuee, patient, responsable) 
                    VALUES (?, ?, ?, ?, ?)';
            $req = $this->pdo->prepare($sql);
            $success = $req->execute([
                $data['id_lot'],
                $data['date_distribution'],
                (int)$data['quantite_distribuee'],
                htmlspecialchars($data['patient']),
                htmlspecialchars($data['responsable'])
            ]);
            
            if ($success) {
                return ["success" => true, "message" => "Distribution créée avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la création de la distribution"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function updateDistribution($id, $data): array {
        try {
            $distribution = $this->getDistributionById($id);
            if (!$distribution) {
                return ["success" => false, "message" => "Distribution non trouvée"];
            }
            
            if (isset($data['quantite_distribuee'])) {
                if ($data['quantite_distribuee'] <= 0) {
                    return ["success" => false, "message" => "La quantité distribuée doit être supérieure à zéro.", "field" => "quantite_distribuee"];
                }

                $lot = $this->lotController->getLotMedicamentById($distribution['id_lot']);
                
                // La quantité disponible pour la modification est la quantité restante actuelle + la quantité de cette distribution
                $availableQuantity = $lot['quantite_restante'] + $distribution['quantite_distribuee'];
                
                if ($data['quantite_distribuee'] > $availableQuantity) {
                    return ["success" => false, "message" => "Quantité insuffisante dans le lot. Maximum autorisé: " . $availableQuantity, "field" => "quantite_distribuee"];
                }
            }

            $updates = [];
            $params = [];
            
            $allowedFields = ['date_distribution', 'quantite_distribuee', 'patient', 'responsable'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    if ($field === 'quantite_distribuee') {
                        $params[] = (int)$data[$field];
                    } else {
                        $params[] = htmlspecialchars($data[$field]);
                    }
                }
            }
            
            if (empty($updates)) {
                return ["success" => false, "message" => "Aucune donnée à mettre à jour"];
            }
            
            $params[] = $id;
            $sql = "UPDATE distribution SET " . implode(', ', $updates) . " WHERE id_distribution = ?";
            
            $req = $this->pdo->prepare($sql);
            $success = $req->execute($params);
            
            if ($success) {
                return ["success" => true, "message" => "Distribution mise à jour avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la mise à jour"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function deleteDistribution($id): array {
        try {
            $distribution = $this->getDistributionById($id);
            if (!$distribution) {
                return ["success" => false, "message" => "Distribution non trouvée"];
            }
            
            $req = $this->pdo->prepare("DELETE FROM distribution WHERE id_distribution = ?");
            $success = $req->execute([$id]);
            
            if ($success) {
                return ["success" => true, "message" => "Distribution supprimée avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la suppression"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getStats(): array {
        try {
            // Total distributions
            $req = $this->pdo->query("SELECT COUNT(*) as total FROM distribution");
            $total = $req->fetch(PDO::FETCH_ASSOC)['total'];

            // Total quantité distribuée
            $req = $this->pdo->query("SELECT SUM(quantite_distribuee) as sum_distribuee FROM distribution");
            $sum_distribuee = $req->fetch(PDO::FETCH_ASSOC)['sum_distribuee'] ?? 0;

            // Distributions ce mois
            $sqlCeMois = "SELECT COUNT(*) as ce_mois FROM distribution WHERE MONTH(date_distribution) = MONTH(CURRENT_DATE()) AND YEAR(date_distribution) = YEAR(CURRENT_DATE())";
            $req = $this->pdo->query($sqlCeMois);
            $ce_mois = $req->fetch(PDO::FETCH_ASSOC)['ce_mois'];

            return [
                'total' => $total,
                'sum_distribuee' => $sum_distribuee,
                'ce_mois' => $ce_mois
            ];
        } catch (Exception $e) {
            return ['total' => 0, 'sum_distribuee' => 0, 'ce_mois' => 0];
        }
    }
}
