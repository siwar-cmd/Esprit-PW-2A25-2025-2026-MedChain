<?php
require_once __DIR__ . '/../models/LotMedicament.php';

class LotController {
    private $db;
    private $table_name = "Lot_medicament_sensible";

    public function __construct($db) { $this->db = $db; }

    private function sqlRead() {
        $query = "SELECT *, (CURDATE() > datePeremption) as estPerime FROM " . $this->table_name . " ORDER BY datePeremption ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function sqlReadOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idLot = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
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

    private function sqlCreate(LotMedicament $lot) {
        $nom = htmlspecialchars(strip_tags($lot->getNomMedicament()));
        $num = htmlspecialchars(strip_tags($lot->getNumeroLot()));
        $qte = intval($lot->getQuantite());
        $dp  = $lot->getDatePeremption();
        $query = "INSERT INTO " . $this->table_name . " SET nomMedicament=:nom, numeroLot=:num, quantite=:qte, datePeremption=:dp";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":nom", $nom);
        $stmt->bindParam(":num", $num);
        $stmt->bindParam(":qte", $qte);
        $stmt->bindParam(":dp",  $dp);
        return $stmt->execute();
    }

    private function sqlUpdate(LotMedicament $lot) {
        $nom = htmlspecialchars(strip_tags($lot->getNomMedicament()));
        $num = htmlspecialchars(strip_tags($lot->getNumeroLot()));
        $qte = intval($lot->getQuantite());
        $dp  = $lot->getDatePeremption();
        $id  = $lot->getIdLot();
        $query = "UPDATE " . $this->table_name . " SET nomMedicament=:nom, numeroLot=:num, quantite=:qte, datePeremption=:dp WHERE idLot=:id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":nom", $nom); $stmt->bindParam(":num", $num);
        $stmt->bindParam(":qte", $qte); $stmt->bindParam(":dp",  $dp);
        $stmt->bindParam(":id",  $id);
        return $stmt->execute();
    }

    private function sqlDelete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE idLot = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    private function sqlGetStats() {
        $stats = [];
        $stats['quantites']   = $this->db->query("SELECT nomMedicament, SUM(quantite) as total FROM " . $this->table_name . " GROUP BY nomMedicament")->fetchAll(PDO::FETCH_ASSOC);
        $stats['peremptions'] = $this->db->query("SELECT (CURDATE() > datePeremption) as perime, COUNT(*) as nb FROM " . $this->table_name . " GROUP BY (CURDATE() > datePeremption)")->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }

    private function validate(LotMedicament $lot, &$errors) {
        $errors = [];
        if (empty($lot->getNomMedicament())) $errors['nomMedicament'] = "Le nom du médicament est requis.";
        if (empty($lot->getNumeroLot()))     $errors['numeroLot']     = "Le numéro de lot est requis.";
        $q = $lot->getQuantite();
        if ($q === '' || !is_numeric($q) || $q < 0) $errors['quantite'] = "Quantité invalide.";
        if (empty($lot->getDatePeremption())) $errors['datePeremption'] = "Date de péremption requise.";
        return empty($errors);
    }

    public function index() {
        $stmt = $this->sqlRead();
        $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/lot/index.php';
    }

    public function create() { require_once __DIR__ . '/../views/lot/form.php'; }

    public function store() {
        if ($_POST) {
            $lot = new LotMedicament();
            $lot->setNomMedicament($_POST['nomMedicament'] ?? '');
            $lot->setNumeroLot($_POST['numeroLot'] ?? '');
            $lot->setQuantite($_POST['quantite'] ?? '');
            $lot->setDatePeremption($_POST['datePeremption'] ?? '');
            $errors = [];
            if ($this->validate($lot, $errors) && $this->sqlCreate($lot)) {
                header("Location: index.php?page=lot&msg=created");
            } else { $oldData = $_POST; require_once __DIR__ . '/../views/lot/form.php'; }
        }
    }

    public function edit($id) {
        $lot = $this->sqlReadOne($id);
        if ($lot) {
            $lotData = ['idLot' => $lot->getIdLot(), 'nomMedicament' => $lot->getNomMedicament(),
                        'numeroLot' => $lot->getNumeroLot(), 'quantite' => $lot->getQuantite(),
                        'datePeremption' => $lot->getDatePeremption()];
            require_once __DIR__ . '/../views/lot/form.php';
        } else { header("Location: index.php?page=lot&msg=notfound"); }
    }

    public function update($id) {
        if ($_POST) {
            $lot = new LotMedicament();
            $lot->setIdLot($id);
            $lot->setNomMedicament($_POST['nomMedicament'] ?? '');
            $lot->setNumeroLot($_POST['numeroLot'] ?? '');
            $lot->setQuantite($_POST['quantite'] ?? '');
            $lot->setDatePeremption($_POST['datePeremption'] ?? '');
            $errors = [];
            if ($this->validate($lot, $errors) && $this->sqlUpdate($lot)) {
                header("Location: index.php?page=lot&msg=updated");
            } else { $lotData = $_POST; $lotData['idLot'] = $id; require_once __DIR__ . '/../views/lot/form.php'; }
        }
    }

    public function delete($id) {
        if ($this->sqlDelete($id)) header("Location: index.php?page=lot&msg=deleted");
        else header("Location: index.php?page=lot");
    }

    public function stats() {
        $stats = $this->sqlGetStats();
        require_once __DIR__ . '/../views/lot/stats.php';
    }
}
?>
