<?php
require_once __DIR__ . '/../models/Mission.php';
require_once __DIR__ . '/../models/Ambulance.php';

class MissionController {
    private $mission;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->mission = new Mission($db);
    }

    public function index() {
        $stmt = $this->mission->read();
        $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/mission/index.php';
    }

    public function create() {
        $ambulanceModel = new Ambulance($this->db);
        $ambulances = $ambulanceModel->read()->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/mission/form.php';
    }

    public function store() {
        if($_POST) {
            $this->mission->idAmbulance = $_POST['idAmbulance'];
            $this->mission->dateDebut = $_POST['dateDebut'];
            $this->mission->dateFin = $_POST['dateFin'];
            $this->mission->typeMission = $_POST['typeMission'];
            $this->mission->lieuDepart = $_POST['lieuDepart'];
            $this->mission->lieuArrivee = $_POST['lieuArrivee'];
            $this->mission->equipe = $_POST['equipe'];
            $this->mission->estTerminee = isset($_POST['estTerminee']) ? 1 : 0;

            if($this->mission->create()) {
                header("Location: index.php?page=mission&msg=created");
            } else {
                $errors = $this->mission->getErrors();
                $oldData = $_POST;
                $ambulanceModel = new Ambulance($this->db);
                $ambulances = $ambulanceModel->read()->fetchAll(PDO::FETCH_ASSOC);
                require_once __DIR__ . '/../views/mission/form.php';
            }
        }
    }

    public function edit($id) {
        $this->mission->idMission = $id;
        if($this->mission->readOne()) {
            $mission = [
                'idMission' => $this->mission->idMission,
                'idAmbulance' => $this->mission->idAmbulance,
                'dateDebut' => $this->mission->dateDebut,
                'dateFin' => $this->mission->dateFin,
                'typeMission' => $this->mission->typeMission,
                'lieuDepart' => $this->mission->lieuDepart,
                'lieuArrivee' => $this->mission->lieuArrivee,
                'equipe' => $this->mission->equipe,
                'estTerminee' => $this->mission->estTerminee
            ];
            $ambulanceModel = new Ambulance($this->db);
            $ambulances = $ambulanceModel->read()->fetchAll(PDO::FETCH_ASSOC);
            require_once __DIR__ . '/../views/mission/form.php';
        } else {
            header("Location: index.php?page=mission&msg=notfound");
        }
    }

    public function update($id) {
        if($_POST) {
            $this->mission->idMission = $id;
            $this->mission->idAmbulance = $_POST['idAmbulance'];
            $this->mission->dateDebut = $_POST['dateDebut'];
            $this->mission->dateFin = $_POST['dateFin'];
            $this->mission->typeMission = $_POST['typeMission'];
            $this->mission->lieuDepart = $_POST['lieuDepart'];
            $this->mission->lieuArrivee = $_POST['lieuArrivee'];
            $this->mission->equipe = $_POST['equipe'];
            $this->mission->estTerminee = isset($_POST['estTerminee']) ? 1 : 0;

            if($this->mission->update()) {
                header("Location: index.php?page=mission&msg=updated");
            } else {
                $errors = $this->mission->getErrors();
                $mission = $_POST;
                $mission['idMission'] = $id;
                $ambulanceModel = new Ambulance($this->db);
                $ambulances = $ambulanceModel->read()->fetchAll(PDO::FETCH_ASSOC);
                require_once __DIR__ . '/../views/mission/form.php';
            }
        }
    }

    public function delete($id) {
        $this->mission->idMission = $id;
        if($this->mission->delete()) {
            header("Location: index.php?page=mission&msg=deleted");
        } else {
            header("Location: index.php?page=mission&msg=delete_error");
        }
    }

    public function stats() {
        $stats = $this->mission->getStats();
        require_once __DIR__ . '/../views/mission/stats.php';
    }
}
?>
