<?php
require_once __DIR__ . '/../models/Mission.php';
require_once __DIR__ . '/../models/Ambulance.php';

class MissionController {
    private $db;
    private $table_name = "Mission";

    public function __construct($db) { $this->db = $db; }

    private function sqlRead() {
        $query = "SELECT m.*, a.immatriculation as immatriculation_ambulance FROM " . $this->table_name . " m LEFT JOIN Ambulance a ON m.idAmbulance = a.idAmbulance ORDER BY m.idMission DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function sqlReadOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idMission = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $m = new Mission();
            $m->setIdMission($row['idMission']);
            $m->setIdAmbulance($row['idAmbulance']);
            $m->setDateDebut($row['dateDebut']);
            $m->setDateFin($row['dateFin']);
            $m->setTypeMission($row['typeMission']);
            $m->setLieuDepart($row['lieuDepart']);
            $m->setLieuArrivee($row['lieuArrivee']);
            $m->setEquipe($row['equipe']);
            $m->setEstTerminee($row['estTerminee']);
            return $m;
        }
        return null;
    }

    private function sqlReadAllAmbulances() {
        $query = "SELECT * FROM Ambulance ORDER BY idAmbulance DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sqlIsAmbulanceBusy($idAmbulance, $excludeId = null) {
        $query = "SELECT idMission FROM " . $this->table_name . " WHERE idAmbulance = :idAmbulance AND estTerminee = 0";
        if ($excludeId) $query .= " AND idMission != :idMission";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':idAmbulance', $idAmbulance);
        if ($excludeId) $stmt->bindParam(':idMission', $excludeId);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function sqlCreate(Mission $m) {
        $idAmb = htmlspecialchars(strip_tags($m->getIdAmbulance()));
        $deb   = htmlspecialchars(strip_tags($m->getDateDebut()));
        $fin   = $m->getDateFin() ?: null;
        $type  = htmlspecialchars(strip_tags($m->getTypeMission()));
        $dep   = htmlspecialchars(strip_tags($m->getLieuDepart()));
        $arr   = htmlspecialchars(strip_tags($m->getLieuArrivee()));
        $eq    = htmlspecialchars(strip_tags($m->getEquipe()));
        $term  = $m->getEstTerminee();
        $query = "INSERT INTO " . $this->table_name . " SET idAmbulance=:idAmb, dateDebut=:deb, dateFin=:fin, typeMission=:type, lieuDepart=:dep, lieuArrivee=:arr, equipe=:eq, estTerminee=:term";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':idAmb', $idAmb); $stmt->bindParam(':deb', $deb);
        $stmt->bindValue(':fin', $fin, empty($fin) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':type', $type); $stmt->bindParam(':dep', $dep);
        $stmt->bindParam(':arr', $arr);   $stmt->bindParam(':eq', $eq);
        $stmt->bindParam(':term', $term);
        return $stmt->execute();
    }

    private function sqlUpdate(Mission $m) {
        $idAmb = htmlspecialchars(strip_tags($m->getIdAmbulance()));
        $deb   = htmlspecialchars(strip_tags($m->getDateDebut()));
        $fin   = $m->getDateFin() ?: null;
        $type  = htmlspecialchars(strip_tags($m->getTypeMission()));
        $dep   = htmlspecialchars(strip_tags($m->getLieuDepart()));
        $arr   = htmlspecialchars(strip_tags($m->getLieuArrivee()));
        $eq    = htmlspecialchars(strip_tags($m->getEquipe()));
        $term  = $m->getEstTerminee();
        $id    = $m->getIdMission();
        $query = "UPDATE " . $this->table_name . " SET idAmbulance=:idAmb, dateDebut=:deb, dateFin=:fin, typeMission=:type, lieuDepart=:dep, lieuArrivee=:arr, equipe=:eq, estTerminee=:term WHERE idMission=:id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':idAmb', $idAmb); $stmt->bindParam(':deb', $deb);
        $stmt->bindValue(':fin', $fin, empty($fin) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':type', $type); $stmt->bindParam(':dep', $dep);
        $stmt->bindParam(':arr', $arr);   $stmt->bindParam(':eq', $eq);
        $stmt->bindParam(':term', $term); $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    private function sqlDelete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE idMission = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    private function sqlGetStats() {
        $stats = [];
        $stmt1 = $this->db->prepare("SELECT a.immatriculation, COUNT(m.idMission) as total FROM " . $this->table_name . " m JOIN Ambulance a ON m.idAmbulance = a.idAmbulance GROUP BY m.idAmbulance LIMIT 5");
        $stmt1->execute();
        $stats['top_ambulances'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        $stmt2 = $this->db->prepare("SELECT SUM(CASE WHEN estTerminee=1 THEN 1 ELSE 0 END) as terminees, SUM(CASE WHEN estTerminee=0 THEN 1 ELSE 0 END) as en_cours FROM " . $this->table_name);
        $stmt2->execute();
        $stats['status_missions'] = $stmt2->fetch(PDO::FETCH_ASSOC);
        return $stats;
    }

    private function validate(Mission $m, &$errors, $excludeId = null) {
        $errors = [];
        if (empty($m->getIdAmbulance())) $errors['idAmbulance'] = "L'ambulance est obligatoire";
        if (empty($m->getDateDebut()))   $errors['dateDebut']   = "La date de début est obligatoire";
        if (empty($m->getTypeMission())) $errors['typeMission'] = "Le type de mission est obligatoire";
        if (empty($m->getLieuDepart()))  $errors['lieuDepart']  = "Le lieu de départ est obligatoire";
        if (empty($m->getLieuArrivee())) $errors['lieuArrivee'] = "Le lieu d'arrivée est obligatoire";
        if (!empty($m->getDateDebut()) && !empty($m->getDateFin()) && strtotime($m->getDateFin()) < strtotime($m->getDateDebut()))
            $errors['dateFin'] = "La date de fin ne peut pas précéder la date de début";
        if (empty($errors) && !$m->getEstTerminee() && $this->sqlIsAmbulanceBusy($m->getIdAmbulance(), $excludeId))
            $errors['idAmbulance'] = "Cette ambulance est déjà en mission";
        return empty($errors);
    }

    public function index() {
        $stmt = $this->sqlRead();
        $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/mission/index.php';
    }

    public function create() {
        $ambulances = $this->sqlReadAllAmbulances();
        require_once __DIR__ . '/../views/mission/form.php';
    }

    public function store() {
        if ($_POST) {
            $m = new Mission();
            $m->setIdAmbulance($_POST['idAmbulance']);
            $m->setDateDebut($_POST['dateDebut']); $m->setDateFin($_POST['dateFin'] ?: null);
            $m->setTypeMission($_POST['typeMission']); $m->setLieuDepart($_POST['lieuDepart']);
            $m->setLieuArrivee($_POST['lieuArrivee']); $m->setEquipe($_POST['equipe']);
            $m->setEstTerminee(isset($_POST['estTerminee']) ? 1 : 0);
            $errors = [];
            if ($this->validate($m, $errors) && $this->sqlCreate($m)) {
                header("Location: index.php?page=mission&msg=created");
            } else { $oldData = $_POST; $ambulances = $this->sqlReadAllAmbulances(); require_once __DIR__ . '/../views/mission/form.php'; }
        }
    }

    public function edit($id) {
        $m = $this->sqlReadOne($id);
        if ($m) {
            $mission = ['idMission' => $m->getIdMission(), 'idAmbulance' => $m->getIdAmbulance(),
                        'dateDebut' => $m->getDateDebut(), 'dateFin' => $m->getDateFin(),
                        'typeMission' => $m->getTypeMission(), 'lieuDepart' => $m->getLieuDepart(),
                        'lieuArrivee' => $m->getLieuArrivee(), 'equipe' => $m->getEquipe(), 'estTerminee' => $m->getEstTerminee()];
            $ambulances = $this->sqlReadAllAmbulances();
            require_once __DIR__ . '/../views/mission/form.php';
        } else { header("Location: index.php?page=mission&msg=notfound"); }
    }

    public function update($id) {
        if ($_POST) {
            $m = new Mission();
            $m->setIdMission($id);
            $m->setIdAmbulance($_POST['idAmbulance']);
            $m->setDateDebut($_POST['dateDebut']); $m->setDateFin($_POST['dateFin'] ?: null);
            $m->setTypeMission($_POST['typeMission']); $m->setLieuDepart($_POST['lieuDepart']);
            $m->setLieuArrivee($_POST['lieuArrivee']); $m->setEquipe($_POST['equipe']);
            $m->setEstTerminee(isset($_POST['estTerminee']) ? 1 : 0);
            $errors = [];
            if ($this->validate($m, $errors, $id) && $this->sqlUpdate($m)) {
                header("Location: index.php?page=mission&msg=updated");
            } else { $mission = $_POST; $mission['idMission'] = $id; $ambulances = $this->sqlReadAllAmbulances(); require_once __DIR__ . '/../views/mission/form.php'; }
        }
    }

    public function delete($id) {
        if ($this->sqlDelete($id)) header("Location: index.php?page=mission&msg=deleted");
        else header("Location: index.php?page=mission&msg=delete_error");
    }

    public function stats() {
        $stats = $this->sqlGetStats();
        require_once __DIR__ . '/../views/mission/stats.php';
    }
}
?>
