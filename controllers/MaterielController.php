<?php
require_once __DIR__ . '/../models/MaterielChirurgical.php';

class MaterielController {
    private $db;
    private $table_name = "MaterielChirurgical";

    public function __construct($db) { $this->db = $db; }

    private function sqlRead() {
        $query = "SELECT m.*, estSterile(m.idMateriel) as sterile_calc, estUtilisable(m.idMateriel) as utilisable_calc FROM " . $this->table_name . " m ORDER BY m.idMateriel DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function sqlReadOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idMateriel = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $mat = new MaterielChirurgical();
            $mat->setIdMateriel($row['idMateriel']);
            $mat->setNom($row['nom']);
            $mat->setCategorie($row['categorie']);
            $mat->setDisponibilite($row['disponibilite']);
            $mat->setStatutSterilisation($row['statutSterilisation']);
            $mat->setNombreUtilisationsMax($row['nombreUtilisationsMax']);
            $mat->setNombreUtilisationsActuelles($row['nombreUtilisationsActuelles']);
            return $mat;
        }
        return null;
    }

    private function sqlCreate(MaterielChirurgical $mat) {
        $nom   = htmlspecialchars(strip_tags($mat->getNom()));
        $cat   = htmlspecialchars(strip_tags($mat->getCategorie()));
        $dispo = htmlspecialchars(strip_tags($mat->getDisponibilite()));
        $ster  = htmlspecialchars(strip_tags($mat->getStatutSterilisation()));
        $max   = filter_var($mat->getNombreUtilisationsMax(), FILTER_VALIDATE_INT);
        $act   = filter_var($mat->getNombreUtilisationsActuelles(), FILTER_VALIDATE_INT) ?: 0;
        $query = "INSERT INTO " . $this->table_name . " SET nom=:nom, categorie=:cat, disponibilite=:dispo, statutSterilisation=:ster, nombreUtilisationsMax=:max, nombreUtilisationsActuelles=:act";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nom',  $nom);  $stmt->bindParam(':cat',  $cat);
        $stmt->bindParam(':dispo',$dispo);$stmt->bindParam(':ster', $ster);
        $stmt->bindParam(':max',  $max);  $stmt->bindParam(':act',  $act);
        return $stmt->execute();
    }

    private function sqlUpdate(MaterielChirurgical $mat) {
        $nom   = htmlspecialchars(strip_tags($mat->getNom()));
        $cat   = htmlspecialchars(strip_tags($mat->getCategorie()));
        $dispo = htmlspecialchars(strip_tags($mat->getDisponibilite()));
        $ster  = htmlspecialchars(strip_tags($mat->getStatutSterilisation()));
        $max   = filter_var($mat->getNombreUtilisationsMax(), FILTER_VALIDATE_INT);
        $act   = filter_var($mat->getNombreUtilisationsActuelles(), FILTER_VALIDATE_INT) ?: 0;
        $id    = $mat->getIdMateriel();
        $query = "UPDATE " . $this->table_name . " SET nom=:nom, categorie=:cat, disponibilite=:dispo, statutSterilisation=:ster, nombreUtilisationsMax=:max, nombreUtilisationsActuelles=:act WHERE idMateriel=:id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nom',  $nom);  $stmt->bindParam(':cat',  $cat);
        $stmt->bindParam(':dispo',$dispo);$stmt->bindParam(':ster', $ster);
        $stmt->bindParam(':max',  $max);  $stmt->bindParam(':act',  $act);
        $stmt->bindParam(':id',   $id);
        return $stmt->execute();
    }

    private function sqlDelete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE idMateriel = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    private function sqlGetStats() {
        $stats = [];
        $stmt1 = $this->db->prepare("SELECT disponibilite, COUNT(idMateriel) as total FROM " . $this->table_name . " GROUP BY disponibilite");
        $stmt1->execute();
        $stats['disponibilites'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        $stmt2 = $this->db->prepare("SELECT statutSterilisation, COUNT(idMateriel) as total FROM " . $this->table_name . " GROUP BY statutSterilisation");
        $stmt2->execute();
        $stats['sterilisations'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }

    private function validate(MaterielChirurgical $mat, &$errors) {
        $errors = [];
        if (empty($mat->getNom()))      $errors['nom']      = "Le nom est obligatoire";
        if (empty($mat->getCategorie())) $errors['categorie'] = "La catégorie est obligatoire";
        if (empty($mat->getNombreUtilisationsMax()) || !is_numeric($mat->getNombreUtilisationsMax()))
            $errors['nombreUtilisationsMax'] = "Nb max d'utilisations invalide";
        return empty($errors);
    }

    public function index() {
        $stmt = $this->sqlRead();
        $materiels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/materiel/index.php';
    }

    public function create() { require_once __DIR__ . '/../views/materiel/form.php'; }

    public function store() {
        if ($_POST) {
            $mat = new MaterielChirurgical();
            $mat->setNom($_POST['nom']); $mat->setCategorie($_POST['categorie']);
            $mat->setDisponibilite($_POST['disponibilite']); $mat->setStatutSterilisation($_POST['statutSterilisation']);
            $mat->setNombreUtilisationsMax($_POST['nombreUtilisationsMax']);
            $mat->setNombreUtilisationsActuelles($_POST['nombreUtilisationsActuelles'] ?? 0);
            $errors = [];
            if ($this->validate($mat, $errors) && $this->sqlCreate($mat)) {
                header("Location: index.php?page=materiel&msg=created");
            } else { $oldData = $_POST; require_once __DIR__ . '/../views/materiel/form.php'; }
        }
    }

    public function edit($id) {
        $mat = $this->sqlReadOne($id);
        if ($mat) {
            $materielData = ['idMateriel' => $mat->getIdMateriel(), 'nom' => $mat->getNom(),
                             'categorie' => $mat->getCategorie(), 'disponibilite' => $mat->getDisponibilite(),
                             'statutSterilisation' => $mat->getStatutSterilisation(),
                             'nombreUtilisationsMax' => $mat->getNombreUtilisationsMax(),
                             'nombreUtilisationsActuelles' => $mat->getNombreUtilisationsActuelles()];
            require_once __DIR__ . '/../views/materiel/form.php';
        } else { header("Location: index.php?page=materiel&msg=notfound"); }
    }

    public function update($id) {
        if ($_POST) {
            $mat = new MaterielChirurgical();
            $mat->setIdMateriel($id);
            $mat->setNom($_POST['nom']); $mat->setCategorie($_POST['categorie']);
            $mat->setDisponibilite($_POST['disponibilite']); $mat->setStatutSterilisation($_POST['statutSterilisation']);
            $mat->setNombreUtilisationsMax($_POST['nombreUtilisationsMax']);
            $mat->setNombreUtilisationsActuelles($_POST['nombreUtilisationsActuelles'] ?? 0);
            $errors = [];
            if ($this->validate($mat, $errors) && $this->sqlUpdate($mat)) {
                header("Location: index.php?page=materiel&msg=updated");
            } else { $materielData = $_POST; $materielData['idMateriel'] = $id; require_once __DIR__ . '/../views/materiel/form.php'; }
        }
    }

    public function delete($id) {
        if ($this->sqlDelete($id)) header("Location: index.php?page=materiel&msg=deleted");
        else header("Location: index.php?page=materiel&msg=delete_error");
    }

    public function stats() {
        $stats = $this->sqlGetStats();
        require_once __DIR__ . '/../views/materiel/stats.php';
    }
}
?>
