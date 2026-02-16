<?php
require_once __DIR__ . '/../models/Intervention.php';

class InterventionController {
    private $intervention;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->intervention = new Intervention($db);
    }

    public function index() {
        $stmt = $this->intervention->read();
        $interventions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/intervention/index.php';
    }

    public function create() {
        require_once __DIR__ . '/../views/intervention/form.php';
    }

    public function store() {
        if($_POST) {
            $this->intervention->dateHeureDebut = !empty($_POST['dateHeureDebut']) ? $_POST['dateHeureDebut'] : null;
            $this->intervention->dateHeureFinPrevu = !empty($_POST['dateHeureFinPrevu']) ? $_POST['dateHeureFinPrevu'] : null;
            $this->intervention->typeIntervention = $_POST['typeIntervention'];
            $this->intervention->niveauUrgence = $_POST['niveauUrgence'];

            if($this->intervention->create()) {
                header("Location: index.php?page=intervention&msg=created");
            } else {
                $errors = $this->intervention->getErrors();
                $oldData = $_POST;
                require_once __DIR__ . '/../views/intervention/form.php';
            }
        }
    }

    public function edit($id) {
        $this->intervention->idIntervention = $id;
        if($this->intervention->readOne()) {
            $interventionData = [
                'idIntervention' => $this->intervention->idIntervention,
                'dateHeureDebut' => $this->intervention->dateHeureDebut,
                'dateHeureFinPrevu' => $this->intervention->dateHeureFinPrevu,
                'typeIntervention' => $this->intervention->typeIntervention,
                'niveauUrgence' => $this->intervention->niveauUrgence
            ];
            require_once __DIR__ . '/../views/intervention/form.php';
        } else {
            header("Location: index.php?page=intervention&msg=notfound");
        }
    }

    public function update($id) {
        if($_POST) {
            $this->intervention->idIntervention = $id;
            $this->intervention->dateHeureDebut = !empty($_POST['dateHeureDebut']) ? $_POST['dateHeureDebut'] : null;
            $this->intervention->dateHeureFinPrevu = !empty($_POST['dateHeureFinPrevu']) ? $_POST['dateHeureFinPrevu'] : null;
            $this->intervention->typeIntervention = $_POST['typeIntervention'];
            $this->intervention->niveauUrgence = $_POST['niveauUrgence'];

            if($this->intervention->update()) {
                header("Location: index.php?page=intervention&msg=updated");
            } else {
                $errors = $this->intervention->getErrors();
                $interventionData = $_POST;
                $interventionData['idIntervention'] = $id;
                require_once __DIR__ . '/../views/intervention/form.php';
            }
        }
    }

    public function delete($id) {
        $this->intervention->idIntervention = $id;
        if($this->intervention->delete()) {
            header("Location: index.php?page=intervention&msg=deleted");
        } else {
            header("Location: index.php?page=intervention");
        }
    }

    public function stats() {
        $stats = $this->intervention->getStats();
        require_once __DIR__ . '/../views/intervention/stats.php';
    }

    public function planifier($id) {
        $this->intervention->idIntervention = $id;
        if($this->intervention->planifier()) {
            header("Location: index.php?page=intervention&msg=planified");
        } else {
            header("Location: index.php?page=intervention&msg=planify_error");
        }
    }

    public function annuler($id) {
        if($_POST && isset($_POST['raison'])) {
            $this->intervention->idIntervention = $id;
            $raison = $_POST['raison'];
            if($this->intervention->annuler($raison)) {
                header("Location: index.php?page=intervention&msg=canceled");
            } else {
                header("Location: index.php?page=intervention&msg=cancel_error");
            }
        } else {
            // S'il n'y a pas de raison postée
            header("Location: index.php?page=intervention");
        }
    }
}
?>
