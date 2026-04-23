<?php
require_once __DIR__ . '/../models/Distribution.php';
require_once __DIR__ . '/../models/LotMedicament.php';

class DistributionController {
    private $db;
    private $table_name = "Distribution_controlee";

    public function __construct($db) {
        $this->db = $db;
    }

    // -------------------------------------------------------------------------
    // SQL : Lire toutes les distributions (avec jointure Lot)
    // -------------------------------------------------------------------------
    private function sqlRead() {
        $query = "SELECT d.*, l.nomMedicament, l.numeroLot
                  FROM " . $this->table_name . " d
                  LEFT JOIN Lot_medicament_sensible l ON d.idLot = l.idLot
                  ORDER BY d.dateDistribution DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // -------------------------------------------------------------------------
    // SQL : Lire une seule distribution
    // -------------------------------------------------------------------------
    private function sqlReadOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idDistribution = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $dist = new Distribution();
            $dist->setIdDistribution($row['idDistribution']);
            $dist->setIdLot($row['idLot']);
            $dist->setQuantite($row['quantite']);
            $dist->setDateDistribution($row['dateDistribution']);
            $dist->setDestinataire($row['destinataire']);
            return $dist;
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // SQL : Créer une distribution
    // -------------------------------------------------------------------------
    private function sqlCreate(Distribution $dist) {
        $destinataire     = htmlspecialchars(strip_tags($dist->getDestinataire()));
        $quantite         = intval($dist->getQuantite());
        $idLot            = $dist->getIdLot();
        $dateDistribution = $dist->getDateDistribution();

        $query = "INSERT INTO " . $this->table_name . "
                  SET idLot=:idL, quantite=:qte, dateDistribution=:dd, destinataire=:dest";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":idL",  $idLot);
        $stmt->bindParam(":qte",  $quantite);
        $stmt->bindParam(":dd",   $dateDistribution);
        $stmt->bindParam(":dest", $destinataire);
        return $stmt->execute();
    }

    // -------------------------------------------------------------------------
    // SQL : Mettre à jour une distribution
    // -------------------------------------------------------------------------
    private function sqlUpdate(Distribution $dist) {
        $destinataire     = htmlspecialchars(strip_tags($dist->getDestinataire()));
        $quantite         = intval($dist->getQuantite());
        $idLot            = $dist->getIdLot();
        $dateDistribution = $dist->getDateDistribution();
        $idDistribution   = $dist->getIdDistribution();

        $query = "UPDATE " . $this->table_name . "
                  SET idLot=:idL, quantite=:qte, dateDistribution=:dd, destinataire=:dest
                  WHERE idDistribution = :idD";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":idL",  $idLot);
        $stmt->bindParam(":qte",  $quantite);
        $stmt->bindParam(":dd",   $dateDistribution);
        $stmt->bindParam(":dest", $destinataire);
        $stmt->bindParam(":idD",  $idDistribution);
        return $stmt->execute();
    }

    // -------------------------------------------------------------------------
    // SQL : Supprimer une distribution
    // -------------------------------------------------------------------------
    private function sqlDelete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE idDistribution = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    // -------------------------------------------------------------------------
    // SQL : Statistiques
    // -------------------------------------------------------------------------
    private function sqlGetStats() {
        $stats = [];
        $q1 = "SELECT destinataire, SUM(quantite) as total FROM " . $this->table_name . " GROUP BY destinataire";
        $stats['destinataires'] = $this->db->query($q1)->fetchAll(PDO::FETCH_ASSOC);

        $q2 = "SELECT l.nomMedicament, COUNT(d.idDistribution) as nbDistributions
               FROM " . $this->table_name . " d
               LEFT JOIN Lot_medicament_sensible l ON d.idLot = l.idLot
               GROUP BY l.nomMedicament";
        $stats['meds'] = $this->db->query($q2)->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }

    // -------------------------------------------------------------------------
    // SQL (LotMedicament) : Lire un lot
    // -------------------------------------------------------------------------
    private function sqlReadOneLot($idLot) {
        $query = "SELECT * FROM Lot_medicament_sensible WHERE idLot = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $idLot);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $lot = new LotMedicament();
            $lot->setIdLot($row['idLot']);
            $lot->setNomMedicament($row['nomMedicament']);
            $lot->setNumeroLot($row['numeroLot']);
            $lot->setQuantite($row['quantite']);
            $lot->setDatePeremption($row['datePeremption']);
            return $lot;
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // SQL (LotMedicament) : Soustraire une quantité du stock
    // -------------------------------------------------------------------------
    private function sqlSubtractQuantity($idLot, $amount) {
        $query = "UPDATE Lot_medicament_sensible SET quantite = quantite - :amount WHERE idLot = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":id", $idLot);
        return $stmt->execute();
    }

    // -------------------------------------------------------------------------
    // SQL (LotMedicament) : Ajouter une quantité au stock
    // -------------------------------------------------------------------------
    private function sqlAddQuantity($idLot, $amount) {
        $query = "UPDATE Lot_medicament_sensible SET quantite = quantite + :amount WHERE idLot = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":id", $idLot);
        return $stmt->execute();
    }

    // -------------------------------------------------------------------------
    // SQL (LotMedicament) : Lire tous les lots
    // -------------------------------------------------------------------------
    private function sqlReadAllLots() {
        $query = "SELECT *, (CURDATE() > datePeremption) as estPerime FROM Lot_medicament_sensible ORDER BY datePeremption ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================
    private function validate(Distribution $dist, &$errors) {
        $errors = [];
        if (empty($dist->getIdLot()))              $errors['idLot']            = "Un lot doit être sélectionné.";
        $q = $dist->getQuantite();
        if ($q === '' || !is_numeric($q) || $q <= 0) $errors['quantite']       = "La quantité doit être supérieure à 0.";
        if (empty($dist->getDateDistribution()))   $errors['dateDistribution'] = "La date est requise.";
        if (empty($dist->getDestinataire()))       $errors['destinataire']     = "Le destinataire est requis.";
        return empty($errors);
    }

    // =========================================================================
    // ACTIONS DU CONTROLLER
    // =========================================================================

    public function index() {
        $stmt = $this->sqlRead();
        $distributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/distribution/index.php';
    }

    public function create() {
        $lots = $this->sqlReadAllLots();
        require_once __DIR__ . '/../views/distribution/form.php';
    }

    public function store() {
        if ($_POST) {
            $dist = new Distribution();
            $dist->setIdLot($_POST['idLot']);
            $dist->setQuantite($_POST['quantite'] ?? '');
            $dist->setDateDistribution($_POST['dateDistribution'] ?? '');
            $dist->setDestinataire($_POST['destinataire'] ?? '');

            $errors = [];

            // Validation de stock (Sécurité serveur)
            $lot = $this->sqlReadOneLot($dist->getIdLot());
            if ($lot && is_numeric($dist->getQuantite()) && $dist->getQuantite() > $lot->getQuantite()) {
                $errors['quantite'] = "Erreur : La quantité demandée (" . $dist->getQuantite() . ") dépasse le stock disponible (" . $lot->getQuantite() . ").";
                $oldData = $_POST;
                $lots = $this->sqlReadAllLots();
                require_once __DIR__ . '/../views/distribution/form.php';
                return;
            }

            if ($this->validate($dist, $errors) && $this->sqlCreate($dist)) {
                $this->sqlSubtractQuantity($dist->getIdLot(), intval($dist->getQuantite()));
                header("Location: index.php?page=distribution&msg=created");
            } else {
                $oldData = $_POST;
                $lots = $this->sqlReadAllLots();
                require_once __DIR__ . '/../views/distribution/form.php';
            }
        }
    }

    public function edit($id) {
        $dist = $this->sqlReadOne($id);
        if ($dist) {
            $distData = [
                'idDistribution'   => $dist->getIdDistribution(),
                'idLot'            => $dist->getIdLot(),
                'quantite'         => $dist->getQuantite(),
                'dateDistribution' => $dist->getDateDistribution(),
                'destinataire'     => $dist->getDestinataire()
            ];
            $lots = $this->sqlReadAllLots();
            require_once __DIR__ . '/../views/distribution/form.php';
        } else {
            header("Location: index.php?page=distribution&msg=notfound");
        }
    }

    public function update($id) {
        if ($_POST) {
            // Mémoriser l'ancienne distribution pour le différentiel de stock
            $oldDist = $this->sqlReadOne($id);
            $oldQuantite = $oldDist ? intval($oldDist->getQuantite()) : 0;
            $oldIdLot    = $oldDist ? $oldDist->getIdLot() : null;

            $dist = new Distribution();
            $dist->setIdDistribution($id);
            $dist->setIdLot($_POST['idLot']);
            $dist->setQuantite($_POST['quantite'] ?? '');
            $dist->setDateDistribution($_POST['dateDistribution'] ?? '');
            $dist->setDestinataire($_POST['destinataire'] ?? '');

            $errors = [];
            if ($this->validate($dist, $errors) && $this->sqlUpdate($dist)) {
                // Ajustement du stock
                if ($oldIdLot == $dist->getIdLot()) {
                    $diff = intval($dist->getQuantite()) - $oldQuantite;
                    $this->sqlSubtractQuantity($dist->getIdLot(), $diff);
                } else {
                    $this->sqlAddQuantity($oldIdLot, $oldQuantite);
                    $this->sqlSubtractQuantity($dist->getIdLot(), intval($dist->getQuantite()));
                }
                header("Location: index.php?page=distribution&msg=updated");
            } else {
                $distData = $_POST;
                $distData['idDistribution'] = $id;
                $lots = $this->sqlReadAllLots();
                require_once __DIR__ . '/../views/distribution/form.php';
            }
        }
    }

    public function delete($id) {
        $dist = $this->sqlReadOne($id);
        if ($dist) {
            $qte   = $dist->getQuantite();
            $lotId = $dist->getIdLot();
            if ($this->sqlDelete($id)) {
                $this->sqlAddQuantity($lotId, $qte);
                header("Location: index.php?page=distribution&msg=deleted");
            } else {
                header("Location: index.php?page=distribution");
            }
        } else {
            header("Location: index.php?page=distribution");
        }
    }

    public function stats() {
        $stats = $this->sqlGetStats();
        require_once __DIR__ . '/../views/distribution/stats.php';
    }
}
?>
