<?php
require_once __DIR__ . '/../models/RendezVous.php';

class RdvController {
    private $rdv;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->rdv = new RendezVous($db);
    }

    public function index() {
        $stmt = $this->rdv->read();
        $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/rdv/index.php';
    }

    public function create() { require_once __DIR__ . '/../views/rdv/form.php'; }

    public function store() {
        if($_POST) {
            $this->rdv->dateHeureDebut = $_POST['dateHeureDebut'] ?? null;
            $this->rdv->dateHeureFin = $_POST['dateHeureFin'] ?? null;
            $this->rdv->statut = $_POST['statut'] ?? 'planifie';
            $this->rdv->typeConsultation = $_POST['typeConsultation'] ?? '';
            $this->rdv->motif = $_POST['motif'] ?? '';

            if($this->rdv->create()) {
                header("Location: index.php?page=rdv&msg=created");
            } else {
                $errors = $this->rdv->getErrors();
                $oldData = $_POST;
                require_once __DIR__ . '/../views/rdv/form.php';
            }
        }
    }

    public function edit($id) {
        $this->rdv->idRDV = $id;
        if($this->rdv->readOne()) {
            $rdvData = [
                'idRDV' => $this->rdv->idRDV,
                'dateHeureDebut' => $this->rdv->dateHeureDebut,
                'dateHeureFin' => $this->rdv->dateHeureFin,
                'statut' => $this->rdv->statut,
                'typeConsultation' => $this->rdv->typeConsultation,
                'motif' => $this->rdv->motif
            ];
            require_once __DIR__ . '/../views/rdv/form.php';
        } else { header("Location: index.php?page=rdv&msg=notfound"); }
    }

    public function update($id) {
        if($_POST) {
            $this->rdv->idRDV = $id;
            $this->rdv->dateHeureDebut = $_POST['dateHeureDebut'] ?? null;
            $this->rdv->dateHeureFin = $_POST['dateHeureFin'] ?? null;
            $this->rdv->statut = $_POST['statut'] ?? 'planifie';
            $this->rdv->typeConsultation = $_POST['typeConsultation'] ?? '';
            $this->rdv->motif = $_POST['motif'] ?? '';

            if($this->rdv->update()) {
                header("Location: index.php?page=rdv&msg=updated");
            } else {
                $errors = $this->rdv->getErrors();
                $rdvData = $_POST;
                $rdvData['idRDV'] = $id;
                require_once __DIR__ . '/../views/rdv/form.php';
            }
        }
    }

    public function delete($id) {
        $this->rdv->idRDV = $id;
        if($this->rdv->delete()) header("Location: index.php?page=rdv&msg=deleted");
        else header("Location: index.php?page=rdv");
    }

    public function actionConfirmer($id) {
        $this->rdv->idRDV = $id;
        if($this->rdv->confirmer()) header("Location: index.php?page=rdv&msg=confirmed");
        else header("Location: index.php?page=rdv");
    }

    public function actionAnnuler($id) {
        if(isset($_POST['raison'])) {
            $this->rdv->idRDV = $id;
            if($this->rdv->annuler($_POST['raison'])) header("Location: index.php?page=rdv&msg=canceled");
            else header("Location: index.php?page=rdv");
        } else header("Location: index.php?page=rdv");
    }

    public function actionReporter($id) {
        if(isset($_POST['newDate'])) {
            $this->rdv->idRDV = $id;
            if($this->rdv->reporter($_POST['newDate'])) header("Location: index.php?page=rdv&msg=postponed");
            else header("Location: index.php?page=rdv");
        } else header("Location: index.php?page=rdv");
    }

    public function stats() {
        $stats = $this->rdv->getStats();
        require_once __DIR__ . '/../views/rdv/stats.php';
    }
}
?>
