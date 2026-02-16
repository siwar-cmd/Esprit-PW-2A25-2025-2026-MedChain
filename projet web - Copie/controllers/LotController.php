<?php
require_once __DIR__ . '/../models/LotMedicament.php';

class LotController {
    private $lot;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->lot = new LotMedicament($db);
    }

    public function index() {
        $stmt = $this->lot->read();
        $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/lot/index.php';
    }

    public function create() { require_once __DIR__ . '/../views/lot/form.php'; }

    public function store() {
        if($_POST) {
            $this->lot->nomMedicament = $_POST['nomMedicament'] ?? '';
            $this->lot->numeroLot = $_POST['numeroLot'] ?? '';
            $this->lot->quantite = $_POST['quantite'] ?? '';
            $this->lot->datePeremption = $_POST['datePeremption'] ?? '';

            if($this->lot->create()) {
                header("Location: index.php?page=lot&msg=created");
            } else {
                $errors = $this->lot->getErrors();
                $oldData = $_POST;
                require_once __DIR__ . '/../views/lot/form.php';
            }
        }
    }

    public function edit($id) {
        $this->lot->idLot = $id;
        if($this->lot->readOne()) {
            $lotData = [
                'idLot' => $this->lot->idLot,
                'nomMedicament' => $this->lot->nomMedicament,
                'numeroLot' => $this->lot->numeroLot,
                'quantite' => $this->lot->quantite,
                'datePeremption' => $this->lot->datePeremption
            ];
            require_once __DIR__ . '/../views/lot/form.php';
        } else { header("Location: index.php?page=lot&msg=notfound"); }
    }

    public function update($id) {
        if($_POST) {
            $this->lot->idLot = $id;
            $this->lot->nomMedicament = $_POST['nomMedicament'] ?? '';
            $this->lot->numeroLot = $_POST['numeroLot'] ?? '';
            $this->lot->quantite = $_POST['quantite'] ?? '';
            $this->lot->datePeremption = $_POST['datePeremption'] ?? '';

            if($this->lot->update()) {
                header("Location: index.php?page=lot&msg=updated");
            } else {
                $errors = $this->lot->getErrors();
                $lotData = $_POST;
                $lotData['idLot'] = $id;
                require_once __DIR__ . '/../views/lot/form.php';
            }
        }
    }

    public function delete($id) {
        $this->lot->idLot = $id;
        if($this->lot->delete()) header("Location: index.php?page=lot&msg=deleted");
        else header("Location: index.php?page=lot");
    }

    public function stats() {
        $stats = $this->lot->getStats();
        require_once __DIR__ . '/../views/lot/stats.php';
    }
}
?>
