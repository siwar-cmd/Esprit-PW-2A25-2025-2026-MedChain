<?php
require_once __DIR__ . '/../models/LotMedicament.php';
require_once __DIR__ . '/../config.php';

class LotMedicamentController {
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

    public function getAllLotMedicaments($filters = []): array {
        try {
            // Requête pour récupérer les lots et calculer dynamiquement la quantité restante
            $sql = 'SELECT lm.*, 
                           (lm.quantite_initial - COALESCE(SUM(d.quantite_distribuee), 0)) as quantite_restante 
                    FROM lot_medicament lm 
                    LEFT JOIN distribution d ON lm.id_lot = d.id_lot 
                    WHERE 1=1';
            $params = [];
            
            if (!empty($filters['search'])) {
                $sql .= ' AND (lm.nom_medicament LIKE ? OR lm.type_medicament LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= ' GROUP BY lm.id_lot ORDER BY lm.date_expiration ASC';
            
            $req = $this->pdo->prepare($sql);
            $req->execute($params);
            
            $lots = $req->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                "success" => true,
                "lots" => $lots,
                "count" => count($lots)
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getLotMedicamentById($id): ?array {
        try {
            $sql = 'SELECT lm.*, 
                           (lm.quantite_initial - COALESCE(SUM(d.quantite_distribuee), 0)) as quantite_restante 
                    FROM lot_medicament lm 
                    LEFT JOIN distribution d ON lm.id_lot = d.id_lot 
                    WHERE lm.id_lot = ?
                    GROUP BY lm.id_lot';
            $req = $this->pdo->prepare($sql);
            $req->execute([$id]);
            $lot = $req->fetch(PDO::FETCH_ASSOC);
            return $lot ?: null;
        } catch (Exception $e) {
            error_log("Erreur getLotMedicamentById: " . $e->getMessage());
            return null;
        }
    }

    public function createLotMedicament($data): array {
        try {
            $required = ['nom_medicament', 'type_medicament', 'date_fabrication', 'date_expiration', 'quantite_initial'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ["success" => false, "message" => "Le champ $field est obligatoire", "field" => $field];
                }
            }

            if ($data['date_fabrication'] > $data['date_expiration']) {
                return ["success" => false, "message" => "La date de fabrication ne peut pas être postérieure à la date d'expiration.", "field" => "date_fabrication"];
            }

            if ($data['quantite_initial'] <= 0) {
                return ["success" => false, "message" => "La quantité initiale doit être supérieure à zéro.", "field" => "quantite_initial"];
            }
            
            $sql = 'INSERT INTO lot_medicament (nom_medicament, type_medicament, date_fabrication, date_expiration, quantite_initial, description) 
                    VALUES (?, ?, ?, ?, ?, ?)';
            $req = $this->pdo->prepare($sql);
            $success = $req->execute([
                htmlspecialchars($data['nom_medicament']),
                htmlspecialchars($data['type_medicament']),
                $data['date_fabrication'],
                $data['date_expiration'],
                (int)$data['quantite_initial'],
                htmlspecialchars($data['description'] ?? '')
            ]);
            
            if ($success) {
                return ["success" => true, "message" => "Lot de médicament créé avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la création du lot de médicament"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function updateLotMedicament($id, $data): array {
        try {
            $lot = $this->getLotMedicamentById($id);
            if (!$lot) {
                return ["success" => false, "message" => "Lot non trouvé"];
            }
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['nom_medicament', 'type_medicament', 'date_fabrication', 'date_expiration', 'quantite_initial', 'description'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    // Ensure dates and ints are properly formatted
                    if ($field === 'quantite_initial') {
                        // verify that new initial quantity is not less than already distributed quantity
                        $distribuee = $lot['quantite_initial'] - $lot['quantite_restante'];
                        if ((int)$data[$field] < $distribuee) {
                            return ["success" => false, "message" => "La quantité initiale ne peut pas être inférieure à la quantité déjà distribuée ($distribuee).", "field" => "quantite_initial"];
                        }
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
            $sql = "UPDATE lot_medicament SET " . implode(', ', $updates) . " WHERE id_lot = ?";
            
            $req = $this->pdo->prepare($sql);
            $success = $req->execute($params);
            
            if ($success) {
                return ["success" => true, "message" => "Lot mis à jour avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la mise à jour"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function deleteLotMedicament($id): array {
        try {
            $lot = $this->getLotMedicamentById($id);
            if (!$lot) {
                return ["success" => false, "message" => "Lot non trouvé"];
            }
            
            $req = $this->pdo->prepare("DELETE FROM lot_medicament WHERE id_lot = ?");
            $success = $req->execute([$id]);
            
            if ($success) {
                return ["success" => true, "message" => "Lot supprimé avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la suppression"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getStats(): array {
        try {
            // Total Lots
            $req = $this->pdo->query("SELECT COUNT(*) as total FROM lot_medicament");
            $total = $req->fetch(PDO::FETCH_ASSOC)['total'];

            // Total Quantité Initiale
            $req = $this->pdo->query("SELECT SUM(quantite_initial) as sum_initial FROM lot_medicament");
            $sum_initial = $req->fetch(PDO::FETCH_ASSOC)['sum_initial'] ?? 0;

            // Total Quantité Restante
            $sql = "SELECT SUM(lm.quantite_initial - COALESCE((SELECT SUM(quantite_distribuee) FROM distribution d WHERE d.id_lot = lm.id_lot), 0)) as sum_restante FROM lot_medicament lm";
            $req = $this->pdo->query($sql);
            $sum_restante = $req->fetch(PDO::FETCH_ASSOC)['sum_restante'] ?? 0;

            // Lots expirés
            $req = $this->pdo->query("SELECT COUNT(*) as expires FROM lot_medicament WHERE date_expiration < CURRENT_DATE()");
            $expires = $req->fetch(PDO::FETCH_ASSOC)['expires'];

            return [
                'total_lots' => $total,
                'sum_initial' => $sum_initial,
                'sum_restante' => $sum_restante,
                'expires' => $expires
            ];
        } catch (Exception $e) {
            return ['total_lots' => 0, 'sum_initial' => 0, 'sum_restante' => 0, 'expires' => 0];
        }
    }
}
