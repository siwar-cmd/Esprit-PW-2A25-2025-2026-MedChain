<?php
require_once __DIR__ . '/../models/Intervention.php';

class InterventionController {
    private $db;
    private $table_name = "Intervention";

    public function __construct($db) { $this->db = $db; }

    private function sqlRead() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY idIntervention DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function sqlReadOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idIntervention = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $i = new Intervention();
            $i->setIdIntervention($row['idIntervention']);
            $i->setDateHeureDebut($row['dateHeureDebut']);
            $i->setDateHeureFinPrevu($row['dateHeureFinPrevu']);
            $i->setTypeIntervention($row['typeIntervention']);
            $i->setNiveauUrgence($row['niveauUrgence']);
            return $i;
        }
        return null;
    }

    private function sqlCreate(Intervention $i) {
        $deb  = $i->getDateHeureDebut() ? htmlspecialchars(strip_tags($i->getDateHeureDebut())) : null;
        $fin  = $i->getDateHeureFinPrevu() ? htmlspecialchars(strip_tags($i->getDateHeureFinPrevu())) : null;
        $type = htmlspecialchars(strip_tags($i->getTypeIntervention()));
        $niv  = filter_var($i->getNiveauUrgence(), FILTER_VALIDATE_INT);
        $query = "INSERT INTO " . $this->table_name . " SET dateHeureDebut=:deb, dateHeureFinPrevu=:fin, typeIntervention=:type, niveauUrgence=:niv";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':deb',  $deb,  empty($deb)  ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':fin',  $fin,  empty($fin)  ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':niv',  $niv);
        return $stmt->execute();
    }

    private function sqlUpdate(Intervention $i) {
        $deb  = $i->getDateHeureDebut() ? htmlspecialchars(strip_tags($i->getDateHeureDebut())) : null;
        $fin  = $i->getDateHeureFinPrevu() ? htmlspecialchars(strip_tags($i->getDateHeureFinPrevu())) : null;
        $type = htmlspecialchars(strip_tags($i->getTypeIntervention()));
        $niv  = filter_var($i->getNiveauUrgence(), FILTER_VALIDATE_INT);
        $id   = $i->getIdIntervention();
        $query = "UPDATE " . $this->table_name . " SET dateHeureDebut=:deb, dateHeureFinPrevu=:fin, typeIntervention=:type, niveauUrgence=:niv WHERE idIntervention=:id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':deb',  $deb,  empty($deb)  ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':fin',  $fin,  empty($fin)  ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':niv',  $niv);
        $stmt->bindParam(':id',   $id);
        return $stmt->execute();
    }

    private function sqlDelete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE idIntervention = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    // Procédure stockée : planifier
    private function sqlPlanifier($id) {
        $query = "CALL planifier(?)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    // Procédure stockée : annuler
    private function sqlAnnuler($id, $raison) {
        $query = "CALL annuler(?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $raison);
        return $stmt->execute();
    }

    private function sqlGetStats() {
        $stats = [];
        $stmt1 = $this->db->prepare("SELECT niveauUrgence, COUNT(idIntervention) as total FROM " . $this->table_name . " GROUP BY niveauUrgence");
        $stmt1->execute();
        $stats['urgences'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        $stmt2 = $this->db->prepare("SELECT typeIntervention, COUNT(idIntervention) as total FROM " . $this->table_name . " GROUP BY typeIntervention");
        $stmt2->execute();
        $stats['types'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }

    private function validate(Intervention $i, &$errors) {
        $errors = [];
        if (!empty($i->getDateHeureDebut()) && !empty($i->getDateHeureFinPrevu()) && strtotime($i->getDateHeureFinPrevu()) < strtotime($i->getDateHeureDebut()))
            $errors['dateHeureFinPrevu'] = "La date de fin prévue ne peut pas précéder la date de début";
        if (empty($i->getTypeIntervention())) $errors['typeIntervention'] = "Le type d'intervention est obligatoire";
        if (empty($i->getNiveauUrgence()) || !is_numeric($i->getNiveauUrgence())) $errors['niveauUrgence'] = "Le niveau d'urgence est obligatoire";
        return empty($errors);
    }

    public function index() {
        $stmt = $this->sqlRead();
        $interventions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/intervention/index.php';
    }

    public function create() { require_once __DIR__ . '/../views/intervention/form.php'; }

    public function store() {
        if ($_POST) {
            $i = new Intervention();
            $i->setDateHeureDebut(!empty($_POST['dateHeureDebut']) ? $_POST['dateHeureDebut'] : null);
            $i->setDateHeureFinPrevu(!empty($_POST['dateHeureFinPrevu']) ? $_POST['dateHeureFinPrevu'] : null);
            $i->setTypeIntervention($_POST['typeIntervention']);
            $i->setNiveauUrgence($_POST['niveauUrgence']);
            $errors = [];
            if ($this->validate($i, $errors) && $this->sqlCreate($i)) {
                header("Location: index.php?page=intervention&msg=created");
            } else { $oldData = $_POST; require_once __DIR__ . '/../views/intervention/form.php'; }
        }
    }

    public function edit($id) {
        $i = $this->sqlReadOne($id);
        if ($i) {
            $interventionData = ['idIntervention' => $i->getIdIntervention(), 'dateHeureDebut' => $i->getDateHeureDebut(),
                                 'dateHeureFinPrevu' => $i->getDateHeureFinPrevu(), 'typeIntervention' => $i->getTypeIntervention(),
                                 'niveauUrgence' => $i->getNiveauUrgence()];
            require_once __DIR__ . '/../views/intervention/form.php';
        } else { header("Location: index.php?page=intervention&msg=notfound"); }
    }

    public function update($id) {
        if ($_POST) {
            $i = new Intervention();
            $i->setIdIntervention($id);
            $i->setDateHeureDebut(!empty($_POST['dateHeureDebut']) ? $_POST['dateHeureDebut'] : null);
            $i->setDateHeureFinPrevu(!empty($_POST['dateHeureFinPrevu']) ? $_POST['dateHeureFinPrevu'] : null);
            $i->setTypeIntervention($_POST['typeIntervention']);
            $i->setNiveauUrgence($_POST['niveauUrgence']);
            $errors = [];
            if ($this->validate($i, $errors) && $this->sqlUpdate($i)) {
                header("Location: index.php?page=intervention&msg=updated");
            } else { $interventionData = $_POST; $interventionData['idIntervention'] = $id; require_once __DIR__ . '/../views/intervention/form.php'; }
        }
    }

    public function delete($id) {
        if ($this->sqlDelete($id)) header("Location: index.php?page=intervention&msg=deleted");
        else header("Location: index.php?page=intervention");
    }

    public function stats() {
        $stats = $this->sqlGetStats();
        require_once __DIR__ . '/../views/intervention/stats.php';
    }

    public function planifier($id) {
        if ($this->sqlPlanifier($id)) header("Location: index.php?page=intervention&msg=planified");
        else header("Location: index.php?page=intervention&msg=planify_error");
    }

    public function annuler($id) {
        if ($_POST && isset($_POST['raison'])) {
            if ($this->sqlAnnuler($id, $_POST['raison'])) header("Location: index.php?page=intervention&msg=canceled");
            else header("Location: index.php?page=intervention&msg=cancel_error");
        } else { header("Location: index.php?page=intervention"); }
    }
}
?>
