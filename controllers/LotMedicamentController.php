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
            $sql = "SELECT lm.*, 
                           (lm.quantite_initial - COALESCE((SELECT SUM(quantite_distribuee) FROM distribution d WHERE d.id_lot = lm.id_lot AND d.statut = 'Accepte'), 0)) as quantite_restante 
                    FROM lot_medicament lm 
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['search'])) {
                $sql .= ' AND (lm.nom_medicament LIKE ? OR lm.type_medicament LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $allowedSorts = [
                'nom' => 'lm.nom_medicament',
                'type' => 'lm.type_medicament',
                'expiration' => 'lm.date_expiration',
                'initial' => 'lm.quantite_initial',
                'restante' => 'quantite_restante'
            ];
            
            $sortField = $filters['sort'] ?? 'expiration';
            $sortDir = strtoupper($filters['dir'] ?? 'ASC');
            if (!in_array($sortDir, ['ASC', 'DESC'])) $sortDir = 'ASC';

            $orderBy = $allowedSorts['expiration'] . ' ASC';
            if (isset($allowedSorts[$sortField])) {
                $orderBy = $allowedSorts[$sortField] . ' ' . $sortDir;
            }
            $sql .= ' ORDER BY ' . $orderBy;
            
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
            $sql = "SELECT lm.*, 
                           (lm.quantite_initial - COALESCE((SELECT SUM(quantite_distribuee) FROM distribution d WHERE d.id_lot = lm.id_lot AND d.statut = 'Accepte'), 0)) as quantite_restante 
                    FROM lot_medicament lm 
                    WHERE lm.id_lot = ?";
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

            if (strtotime($data['date_expiration']) <= strtotime($data['date_fabrication'])) {
                return ["success" => false, "message" => "La date de fabrication ne peut pas etre posterieure a la date d expiration.", "field" => "date_fabrication"];
            }

            if ($data['quantite_initial'] <= 0) {
                return ["success" => false, "message" => "La quantite initiale doit etre superieure a zero.", "field" => "quantite_initial"];
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
                return ["success" => true, "message" => "Lot de medicament cree avec succes"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la creation du lot de medicament"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function updateLotMedicament($id, $data): array {
        try {
            if (isset($data['date_expiration'], $data['date_fabrication']) && strtotime($data['date_expiration']) <= strtotime($data['date_fabrication'])) {
                return ["success" => false, "message" => "La date de fabrication ne peut pas etre posterieure a la date d expiration.", "field" => "date_fabrication"];
            }

            $lot = $this->getLotMedicamentById($id);
            if (!$lot) {
                return ["success" => false, "message" => "Lot non trouve"];
            }
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['nom_medicament', 'type_medicament', 'date_fabrication', 'date_expiration', 'quantite_initial', 'description'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    if ($field === 'quantite_initial') {
                        $distribuee = $lot['quantite_initial'] - $lot['quantite_restante'];
                        if ((int)$data[$field] < $distribuee) {
                            return ["success" => false, "message" => "La quantite initiale ne peut pas etre inferieure a la quantite deja distribuee ($distribuee).", "field" => "quantite_initial"];
                        }
                        $params[] = (int)$data[$field];
                    } else {
                        $params[] = htmlspecialchars($data[$field]);
                    }
                }
            }
            
            if (empty($updates)) {
                return ["success" => false, "message" => "Aucune donnee a mettre a jour"];
            }
            
            $params[] = $id;
            $sql = "UPDATE lot_medicament SET " . implode(', ', $updates) . " WHERE id_lot = ?";
            
            $req = $this->pdo->prepare($sql);
            $success = $req->execute($params);
            
            if ($success) {
                return ["success" => true, "message" => "Lot mis a jour avec succes"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la mise a jour"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function deleteLotMedicament($id): array {
        try {
            $lot = $this->getLotMedicamentById($id);
            if (!$lot) {
                return ["success" => false, "message" => "Lot non trouve"];
            }
            
            $req = $this->pdo->prepare("DELETE FROM lot_medicament WHERE id_lot = ?");
            $success = $req->execute([$id]);
            
            if ($success) {
                return ["success" => true, "message" => "Lot supprime avec succes"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la suppression"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getStats(): array {
        try {
            $req = $this->pdo->query("SELECT COUNT(*) as total FROM lot_medicament");
            $total = $req->fetch(PDO::FETCH_ASSOC)['total'];

            $req = $this->pdo->query("SELECT SUM(quantite_initial) as sum_initial FROM lot_medicament");
            $sum_initial = $req->fetch(PDO::FETCH_ASSOC)['sum_initial'] ?? 0;

            $sql = "SELECT SUM(lm.quantite_initial - COALESCE((SELECT SUM(quantite_distribuee) FROM distribution d WHERE d.id_lot = lm.id_lot AND d.statut = 'Accepte'), 0)) as sum_restante FROM lot_medicament lm";
            $req = $this->pdo->query($sql);
            $sum_restante = $req->fetch(PDO::FETCH_ASSOC)['sum_restante'] ?? 0;

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
