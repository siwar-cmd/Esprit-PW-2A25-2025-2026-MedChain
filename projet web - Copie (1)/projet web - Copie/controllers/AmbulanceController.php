<?php
require_once __DIR__ . '/../models/Ambulance.php';

class AmbulanceController {
    private $ambulance;

    public function __construct($db) {
        $this->ambulance = new Ambulance($db);
    }

    public function index() {
        $stmt = $this->ambulance->read();
        $ambulances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/ambulance/index.php';
    }

    public function create() {
        require_once __DIR__ . '/../views/ambulance/form.php';
    }

    public function store() {
        if($_POST) {
            $this->ambulance->immatriculation = $_POST['immatriculation'];
            $this->ambulance->statut = $_POST['statut'];
            $this->ambulance->modele = $_POST['modele'];
            $this->ambulance->capacite = $_POST['capacite'];
            $this->ambulance->estDisponible = isset($_POST['estDisponible']) ? 1 : 0;

            if($this->ambulance->create()) {
                header("Location: index.php?page=ambulance&msg=created");
            } else {
                // Passer les erreurs à la vue
                $errors = $this->ambulance->getErrors();
                $oldData = $_POST;
                require_once __DIR__ . '/../views/ambulance/form.php';
            }
        }
    }

    public function edit($id) {
        $this->ambulance->idAmbulance = $id;
        if($this->ambulance->readOne()) {
            $ambulance = [
                'idAmbulance' => $this->ambulance->idAmbulance,
                'immatriculation' => $this->ambulance->immatriculation,
                'statut' => $this->ambulance->statut,
                'modele' => $this->ambulance->modele,
                'capacite' => $this->ambulance->capacite,
                'estDisponible' => $this->ambulance->estDisponible
            ];
            require_once __DIR__ . '/../views/ambulance/form.php';
        } else {
            header("Location: index.php?page=ambulance&msg=notfound");
        }
    }

    public function update($id) {
        if($_POST) {
            $this->ambulance->idAmbulance = $id;
            $this->ambulance->immatriculation = $_POST['immatriculation'];
            $this->ambulance->statut = $_POST['statut'];
            $this->ambulance->modele = $_POST['modele'];
            $this->ambulance->capacite = $_POST['capacite'];
            $this->ambulance->estDisponible = isset($_POST['estDisponible']) ? 1 : 0;

            if($this->ambulance->update()) {
                header("Location: index.php?page=ambulance&msg=updated");
            } else {
                // Passer les erreurs à la vue
                $errors = $this->ambulance->getErrors();
                $ambulance = $_POST;
                $ambulance['idAmbulance'] = $id;
                require_once __DIR__ . '/../views/ambulance/form.php';
            }
        }
    }

    public function delete($id) {
        $this->ambulance->idAmbulance = $id;
        if($this->ambulance->delete()) {
            header("Location: index.php?page=ambulance&msg=deleted");
        } else {
            $errors = $this->ambulance->getErrors();
            // Rediriger avec message d'erreur
            header("Location: index.php?page=ambulance&msg=delete_error");
        }
    }

    public function show($id) {
        $this->ambulance->idAmbulance = $id;
        if($this->ambulance->readOne()) {
            require_once __DIR__ . '/../views/ambulance/show.php';
        } else {
            header("Location: index.php?page=ambulance&msg=notfound");
        }
    }

    public function search() {
        if(isset($_GET['search'])) {
            $keywords = $_GET['search'];
            $stmt = $this->ambulance->search($keywords);
            $ambulances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            require_once __DIR__ . '/../views/ambulance/index.php';
        } else {
            $this->index();
        }
    }

    public function stats() {
        $stats = $this->ambulance->getStats();
        require_once __DIR__ . '/../views/ambulance/stats.php';
    }
}
?>