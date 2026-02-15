<?php
require_once __DIR__ . '/../models/MaterielChirurgical.php';

class MaterielController {
    private $materiel;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->materiel = new MaterielChirurgical($db);
    }

    public function index() {
        $stmt = $this->materiel->read();
        $materiels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/materiel/index.php';
    }

    public function create() {
        require_once __DIR__ . '/../views/materiel/form.php';
    }

    public function store() {
        if($_POST) {
            $this->materiel->nom = $_POST['nom'];
            $this->materiel->categorie = $_POST['categorie'];
            $this->materiel->disponibilite = $_POST['disponibilite'];
            $this->materiel->statutSterilisation = $_POST['statutSterilisation'];
            $this->materiel->nombreUtilisationsMax = $_POST['nombreUtilisationsMax'];
            $this->materiel->nombreUtilisationsActuelles = $_POST['nombreUtilisationsActuelles'] ?? 0;

            if($this->materiel->create()) {
                header("Location: index.php?page=materiel&msg=created");
            } else {
                $errors = $this->materiel->getErrors();
                $oldData = $_POST;
                require_once __DIR__ . '/../views/materiel/form.php';
            }
        }
    }

    public function edit($id) {
        $this->materiel->idMateriel = $id;
        if($this->materiel->readOne()) {
            $materielData = [
                'idMateriel' => $this->materiel->idMateriel,
                'nom' => $this->materiel->nom,
                'categorie' => $this->materiel->categorie,
                'disponibilite' => $this->materiel->disponibilite,
                'statutSterilisation' => $this->materiel->statutSterilisation,
                'nombreUtilisationsMax' => $this->materiel->nombreUtilisationsMax,
                'nombreUtilisationsActuelles' => $this->materiel->nombreUtilisationsActuelles
            ];
            require_once __DIR__ . '/../views/materiel/form.php';
        } else {
            header("Location: index.php?page=materiel&msg=notfound");
        }
    }

    public function update($id) {
        if($_POST) {
            $this->materiel->idMateriel = $id;
            $this->materiel->nom = $_POST['nom'];
            $this->materiel->categorie = $_POST['categorie'];
            $this->materiel->disponibilite = $_POST['disponibilite'];
            $this->materiel->statutSterilisation = $_POST['statutSterilisation'];
            $this->materiel->nombreUtilisationsMax = $_POST['nombreUtilisationsMax'];
            $this->materiel->nombreUtilisationsActuelles = $_POST['nombreUtilisationsActuelles'] ?? 0;

            if($this->materiel->update()) {
                header("Location: index.php?page=materiel&msg=updated");
            } else {
                $errors = $this->materiel->getErrors();
                $materielData = $_POST;
                $materielData['idMateriel'] = $id;
                require_once __DIR__ . '/../views/materiel/form.php';
            }
        }
    }

    public function delete($id) {
        $this->materiel->idMateriel = $id;
        if($this->materiel->delete()) {
            header("Location: index.php?page=materiel&msg=deleted");
        } else {
            header("Location: index.php?page=materiel&msg=delete_error");
        }
    }

    public function stats() {
        $stats = $this->materiel->getStats();
        require_once __DIR__ . '/../views/materiel/stats.php';
    }
}
?>
