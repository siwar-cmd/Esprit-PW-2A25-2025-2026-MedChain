<?php
require_once __DIR__ . '/../models/RendezVous.php';

class RdvController {
    private $db;
    private $table_name = "RendezVous";

    public function __construct($db) { $this->db = $db; }

    private function sqlRead() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY dateHeureDebut DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function sqlReadOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idRDV = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $r = new RendezVous();
            $r->setIdRDV($row['idRDV']);
            $r->setDateHeureDebut($row['dateHeureDebut']);
            $r->setDateHeureFin($row['dateHeureFin']);
            $r->setStatut($row['statut']);
            $r->setTypeConsultation($row['typeConsultation']);
            $r->setMotif($row['motif']);
            return $r;
        }
        return null;
    }

    private function sqlCreate(RendezVous $r) {
        $deb  = $r->getDateHeureDebut();
        $fin  = $r->getDateHeureFin();
        $stat = $r->getStatut() ? htmlspecialchars(strip_tags($r->getStatut())) : 'planifie';
        $type = htmlspecialchars(strip_tags($r->getTypeConsultation()));
        $mot  = htmlspecialchars(strip_tags($r->getMotif()));
        $query = "INSERT INTO " . $this->table_name . " SET dateHeureDebut=:deb, dateHeureFin=:fin, statut=:stat, typeConsultation=:type, motif=:mot";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':deb', $deb); $stmt->bindParam(':fin', $fin);
        $stmt->bindParam(':stat',$stat);$stmt->bindParam(':type',$type);
        $stmt->bindParam(':mot', $mot);
        return $stmt->execute();
    }

    private function sqlUpdate(RendezVous $r) {
        $deb  = $r->getDateHeureDebut();
        $fin  = $r->getDateHeureFin();
        $stat = $r->getStatut() ? htmlspecialchars(strip_tags($r->getStatut())) : 'planifie';
        $type = htmlspecialchars(strip_tags($r->getTypeConsultation()));
        $mot  = htmlspecialchars(strip_tags($r->getMotif()));
        $id   = $r->getIdRDV();
        $query = "UPDATE " . $this->table_name . " SET dateHeureDebut=:deb, dateHeureFin=:fin, statut=:stat, typeConsultation=:type, motif=:mot WHERE idRDV=:id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':deb', $deb); $stmt->bindParam(':fin', $fin);
        $stmt->bindParam(':stat',$stat);$stmt->bindParam(':type',$type);
        $stmt->bindParam(':mot', $mot); $stmt->bindParam(':id',  $id);
        return $stmt->execute();
    }

    private function sqlDelete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE idRDV = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    private function sqlConfirmer($id) {
        $query = "UPDATE " . $this->table_name . " SET statut = 'confirme' WHERE idRDV = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    private function sqlAnnuler($id, $raison) {
        $query = "UPDATE " . $this->table_name . " SET statut = 'annule', motif = ? WHERE idRDV = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $raison); $stmt->bindParam(2, $id);
        return $stmt->execute();
    }

    private function sqlReporter($id, $nouvelleDate) {
        $query = "UPDATE " . $this->table_name . " SET dateHeureDebut = ?, statut = 'reporte' WHERE idRDV = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $nouvelleDate); $stmt->bindParam(2, $id);
        return $stmt->execute();
    }

    private function sqlGetStats() {
        $stats = [];
        $stats['statuts'] = $this->db->query("SELECT statut, COUNT(*) as total FROM " . $this->table_name . " GROUP BY statut")->fetchAll(PDO::FETCH_ASSOC);
        $stats['types']   = $this->db->query("SELECT typeConsultation, COUNT(*) as total FROM " . $this->table_name . " GROUP BY typeConsultation")->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }

    private function validate(RendezVous $r, &$errors) {
        $errors = [];
        if (empty($r->getTypeConsultation())) $errors['typeConsultation'] = "Type requis.";
        if (empty($r->getDateHeureDebut()))   $errors['dateHeureDebut']   = "Date de début requise.";
        if (empty($r->getDateHeureFin()))     $errors['dateHeureFin']     = "Date de fin requise.";
        if (!empty($r->getDateHeureDebut()) && !empty($r->getDateHeureFin()) && strtotime($r->getDateHeureFin()) <= strtotime($r->getDateHeureDebut()))
            $errors['dateHeureFin'] = "La date de fin doit être postérieure au début.";
        return empty($errors);
    }

    public function index() {
        $stmt = $this->sqlRead();
        $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/rdv/index.php';
    }

    public function create() { require_once __DIR__ . '/../views/rdv/form.php'; }

    public function store() {
        if ($_POST) {
            $r = new RendezVous();
            $r->setDateHeureDebut($_POST['dateHeureDebut'] ?? null);
            $r->setDateHeureFin($_POST['dateHeureFin'] ?? null);
            $r->setStatut($_POST['statut'] ?? 'planifie');
            $r->setTypeConsultation($_POST['typeConsultation'] ?? '');
            $r->setMotif($_POST['motif'] ?? '');
            $errors = [];
            if ($this->validate($r, $errors) && $this->sqlCreate($r)) {
                header("Location: index.php?page=rdv&msg=created");
            } else { $oldData = $_POST; require_once __DIR__ . '/../views/rdv/form.php'; }
        }
    }

    public function edit($id) {
        $r = $this->sqlReadOne($id);
        if ($r) {
            $rdvData = ['idRDV' => $r->getIdRDV(), 'dateHeureDebut' => $r->getDateHeureDebut(),
                        'dateHeureFin' => $r->getDateHeureFin(), 'statut' => $r->getStatut(),
                        'typeConsultation' => $r->getTypeConsultation(), 'motif' => $r->getMotif()];
            require_once __DIR__ . '/../views/rdv/form.php';
        } else { header("Location: index.php?page=rdv&msg=notfound"); }
    }

    public function update($id) {
        if ($_POST) {
            $r = new RendezVous();
            $r->setIdRDV($id);
            $r->setDateHeureDebut($_POST['dateHeureDebut'] ?? null);
            $r->setDateHeureFin($_POST['dateHeureFin'] ?? null);
            $r->setStatut($_POST['statut'] ?? 'planifie');
            $r->setTypeConsultation($_POST['typeConsultation'] ?? '');
            $r->setMotif($_POST['motif'] ?? '');
            $errors = [];
            if ($this->validate($r, $errors) && $this->sqlUpdate($r)) {
                header("Location: index.php?page=rdv&msg=updated");
            } else { $rdvData = $_POST; $rdvData['idRDV'] = $id; require_once __DIR__ . '/../views/rdv/form.php'; }
        }
    }

    public function delete($id) {
        if ($this->sqlDelete($id)) header("Location: index.php?page=rdv&msg=deleted");
        else header("Location: index.php?page=rdv");
    }

    public function actionConfirmer($id) {
        if ($this->sqlConfirmer($id)) header("Location: index.php?page=rdv&msg=confirmed");
        else header("Location: index.php?page=rdv");
    }

    public function actionAnnuler($id) {
        if (isset($_POST['raison'])) {
            if ($this->sqlAnnuler($id, $_POST['raison'])) header("Location: index.php?page=rdv&msg=canceled");
            else header("Location: index.php?page=rdv");
        } else header("Location: index.php?page=rdv");
    }

    public function actionReporter($id) {
        if (isset($_POST['newDate'])) {
            if ($this->sqlReporter($id, $_POST['newDate'])) header("Location: index.php?page=rdv&msg=postponed");
            else header("Location: index.php?page=rdv");
        } else header("Location: index.php?page=rdv");
    }

    public function stats() {
        $stats = $this->sqlGetStats();
        require_once __DIR__ . '/../views/rdv/stats.php';
    }
}
?>
