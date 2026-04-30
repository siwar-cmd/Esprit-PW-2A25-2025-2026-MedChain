<?php
require_once 'models/rdv.php';

class RendezVousController {
    private $model;

    public function __construct($pdo) {
        $this->model = new RendezVous($pdo);
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list':
                $this->list();
                break;
            case 'create':
                $this->create();
                break;
            case 'edit':
                $this->edit();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'statut':
                $this->updateStatut();
                break;
            case 'stats':
                $this->stats();
                break;
            default:
                $this->list();
        }
    }

    private function list() {
        $search = $_GET['search'] ?? '';
        $rdvs = $search
            ? $this->model->search($search)
            : $this->model->getAll();

        $message = $_GET['msg'] ?? '';
        require 'views/rdv/list.php';
    }

    private function create() {
        $errors = [];
        $data = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'dateHeureDebut'   => $_POST['dateHeureDebut'] ?? '',
                'dateHeureFin'     => $_POST['dateHeureFin'] ?? '',
                'statut'           => $_POST['statut'] ?? 'PLANIFIE',
                'typeConsultation' => $_POST['typeConsultation'] ?? '',
                'motif'            => $_POST['motif'] ?? '',
            ];

            if (empty($data['dateHeureDebut'])) $errors[] = "La date de début est obligatoire.";
            if (empty($data['typeConsultation'])) $errors[] = "Le type de consultation est obligatoire.";

            if (empty($errors)) {
                $this->model->create($data);
                header("Location: index.php?page=rdv&msg=created");
                exit;
            }
        }

        require 'views/rdv/form.php';
    }

    private function edit() {
        $id = intval($_GET['id'] ?? 0);
        $errors = [];
        $data = $this->model->getById($id);

        if (!$data) {
            header("Location: index.php?page=rdv&msg=notfound");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'dateHeureDebut'   => $_POST['dateHeureDebut'] ?? '',
                'dateHeureFin'     => $_POST['dateHeureFin'] ?? '',
                'statut'           => $_POST['statut'] ?? 'PLANIFIE',
                'typeConsultation' => $_POST['typeConsultation'] ?? '',
                'motif'            => $_POST['motif'] ?? '',
            ];

            if (empty($data['dateHeureDebut'])) $errors[] = "La date de début est obligatoire.";
            if (empty($data['typeConsultation'])) $errors[] = "Le type de consultation est obligatoire.";

            if (empty($errors)) {
                $this->model->update($id, $data);
                header("Location: index.php?page=rdv&msg=updated");
                exit;
            }
        }

        require 'views/rdv/form.php';
    }

    private function delete() {
        $id = intval($_GET['id'] ?? 0);
        if ($id) {
            $this->model->delete($id);
        }
        header("Location: index.php?page=rdv&msg=deleted");
        exit;
    }

    private function updateStatut() {
        $id     = intval($_GET['id'] ?? 0);
        $statut = $_GET['statut'] ?? '';
        $allowed = ['PLANIFIE', 'CONFIRME', 'ANNULE', 'TERMINE'];

        if ($id && in_array($statut, $allowed)) {
            $this->model->updateStatut($id, $statut);
        }
        header("Location: index.php?page=rdv&msg=statut_updated");
        exit;
    }

    private function stats() {
        $stats = $this->model->getStats();
        require 'views/rdv/stats.php';
    }
}