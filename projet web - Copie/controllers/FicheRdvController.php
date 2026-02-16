<?php
require_once __DIR__ . '/../models/FicheRendezVous.php';
require_once __DIR__ . '/../models/RendezVous.php'; // Pour remplir la liste déroulante

class FicheRdvController {
    private $fiche;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->fiche = new FicheRendezVous($db);
    }

    public function index() {
        $stmt = $this->fiche->read();
        $fiches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/ficherdv/index.php';
    }

    public function create() { 
        $rdvModel = new RendezVous($this->db);
        $rdvs = $rdvModel->read()->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/ficherdv/form.php'; 
    }

    public function store() {
        if($_POST) {
            $this->fiche->idRDV = $_POST['idRDV'];
            $this->fiche->piecesAApporter = $_POST['piecesAApporter'] ?? '';
            $this->fiche->consignesAvantConsultation = $_POST['consignesAvantConsultation'] ?? '';
            $this->fiche->tarifConsultation = $_POST['tarifConsultation'];
            $this->fiche->modeRemboursement = $_POST['modeRemboursement'] ?? '';

            if($this->fiche->create()) {
                header("Location: index.php?page=ficherdv&msg=created");
            } else {
                $errors = $this->fiche->getErrors();
                $oldData = $_POST;
                $rdvModel = new RendezVous($this->db);
                $rdvs = $rdvModel->read()->fetchAll(PDO::FETCH_ASSOC);
                require_once __DIR__ . '/../views/ficherdv/form.php';
            }
        }
    }

    public function edit($id) {
        $this->fiche->idFiche = $id;
        if($this->fiche->readOne()) {
            $ficheData = [
                'idFiche' => $this->fiche->idFiche,
                'idRDV' => $this->fiche->idRDV,
                'piecesAApporter' => $this->fiche->piecesAApporter,
                'consignesAvantConsultation' => $this->fiche->consignesAvantConsultation,
                'tarifConsultation' => $this->fiche->tarifConsultation,
                'modeRemboursement' => $this->fiche->modeRemboursement
            ];
            $rdvModel = new RendezVous($this->db);
            $rdvs = $rdvModel->read()->fetchAll(PDO::FETCH_ASSOC);
            require_once __DIR__ . '/../views/ficherdv/form.php';
        } else { header("Location: index.php?page=ficherdv&msg=notfound"); }
    }

    public function update($id) {
        if($_POST) {
            $this->fiche->idFiche = $id;
            $this->fiche->idRDV = $_POST['idRDV'];
            $this->fiche->piecesAApporter = $_POST['piecesAApporter'] ?? '';
            $this->fiche->consignesAvantConsultation = $_POST['consignesAvantConsultation'] ?? '';
            $this->fiche->tarifConsultation = $_POST['tarifConsultation'];
            $this->fiche->modeRemboursement = $_POST['modeRemboursement'] ?? '';

            if($this->fiche->update()) {
                header("Location: index.php?page=ficherdv&msg=updated");
            } else {
                $errors = $this->fiche->getErrors();
                $ficheData = $_POST;
                $ficheData['idFiche'] = $id;
                $rdvModel = new RendezVous($this->db);
                $rdvs = $rdvModel->read()->fetchAll(PDO::FETCH_ASSOC);
                require_once __DIR__ . '/../views/ficherdv/form.php';
            }
        }
    }

    public function delete($id) {
        $this->fiche->idFiche = $id;
        if($this->fiche->delete()) header("Location: index.php?page=ficherdv&msg=deleted");
        else header("Location: index.php?page=ficherdv");
    }

    public function marquerGenere($id) {
        $this->fiche->idFiche = $id;
        $this->fiche->setGenere();
        header("Location: index.php?page=ficherdv&msg=action");
    }

    public function marquerEmailEnvoye($id) {
        $this->fiche->idFiche = $id;
        $this->fiche->setEmail();
        header("Location: index.php?page=ficherdv&msg=action");
    }

    public function marquerCalendrier($id) {
        $this->fiche->idFiche = $id;
        $this->fiche->setCalendrier();
        header("Location: index.php?page=ficherdv&msg=action");
    }

    public function stats() {
        $stats = $this->fiche->getStats();
        require_once __DIR__ . '/../views/ficherdv/stats.php';
    }
}
?>
