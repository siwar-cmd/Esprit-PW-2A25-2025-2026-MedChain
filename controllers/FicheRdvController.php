<?php
require_once __DIR__ . '/../models/FicheRendezVous.php';
require_once __DIR__ . '/../models/RendezVous.php';

class FicheRdvController {
    private $db;
    private $table_name = "FicheRendezVous";

    public function __construct($db) { $this->db = $db; }

    private function sqlRead() {
        $query = "SELECT f.*, r.dateHeureDebut, r.typeConsultation FROM " . $this->table_name . " f LEFT JOIN RendezVous r ON f.idRDV = r.idRDV ORDER BY f.idFiche DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function sqlReadOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idFiche = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $f = new FicheRendezVous();
            $f->setIdFiche($row['idFiche']);
            $f->setIdRDV($row['idRDV']);
            $f->setDateGeneration($row['dateGeneration']);
            $f->setPiecesAApporter($row['piecesAApporter']);
            $f->setConsignesAvantConsultation($row['consignesAvantConsultation']);
            $f->setTarifConsultation($row['tarifConsultation']);
            $f->setModeRemboursement($row['modeRemboursement']);
            $f->setEmailEnvoye($row['emailEnvoye']);
            $f->setCalendrierAjoute($row['calendrierAjoute']);
            return $f;
        }
        return null;
    }

    private function sqlReadAllRdvs() {
        $query = "SELECT * FROM RendezVous ORDER BY dateHeureDebut DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sqlCreate(FicheRendezVous $f) {
        $idRDV    = $f->getIdRDV();
        $pieces   = htmlspecialchars(strip_tags($f->getPiecesAApporter()));
        $consignes= htmlspecialchars(strip_tags($f->getConsignesAvantConsultation()));
        $tarif    = floatval($f->getTarifConsultation());
        $modeR    = htmlspecialchars(strip_tags($f->getModeRemboursement()));
        $query = "INSERT INTO " . $this->table_name . " SET idRDV=:idRDV, piecesAApporter=:pieces, consignesAvantConsultation=:consignes, tarifConsultation=:tarif, modeRemboursement=:modeR";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':idRDV',   $idRDV); $stmt->bindParam(':pieces',   $pieces);
        $stmt->bindParam(':consignes',$consignes); $stmt->bindParam(':tarif', $tarif);
        $stmt->bindParam(':modeR',   $modeR);
        return $stmt->execute();
    }

    private function sqlUpdate(FicheRendezVous $f) {
        $idRDV    = $f->getIdRDV();
        $pieces   = htmlspecialchars(strip_tags($f->getPiecesAApporter()));
        $consignes= htmlspecialchars(strip_tags($f->getConsignesAvantConsultation()));
        $tarif    = floatval($f->getTarifConsultation());
        $modeR    = htmlspecialchars(strip_tags($f->getModeRemboursement()));
        $id       = $f->getIdFiche();
        $query = "UPDATE " . $this->table_name . " SET idRDV=:idRDV, piecesAApporter=:pieces, consignesAvantConsultation=:consignes, tarifConsultation=:tarif, modeRemboursement=:modeR WHERE idFiche=:id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':idRDV',    $idRDV);    $stmt->bindParam(':pieces',    $pieces);
        $stmt->bindParam(':consignes',$consignes);$stmt->bindParam(':tarif',     $tarif);
        $stmt->bindParam(':modeR',    $modeR);    $stmt->bindParam(':id',        $id);
        return $stmt->execute();
    }

    private function sqlDelete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE idFiche = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    private function sqlSetGenere($id) {
        $stmt = $this->db->prepare("UPDATE " . $this->table_name . " SET dateGeneration = CURDATE() WHERE idFiche = ?");
        return $stmt->execute([$id]);
    }

    private function sqlSetEmail($id) {
        $stmt = $this->db->prepare("UPDATE " . $this->table_name . " SET emailEnvoye = TRUE WHERE idFiche = ?");
        return $stmt->execute([$id]);
    }

    private function sqlSetCalendrier($id) {
        $stmt = $this->db->prepare("UPDATE " . $this->table_name . " SET calendrierAjoute = TRUE WHERE idFiche = ?");
        return $stmt->execute([$id]);
    }

    private function sqlGetStats() {
        $stats = [];
        $stats['remboursements'] = $this->db->query("SELECT modeRemboursement, COUNT(*) as total FROM " . $this->table_name . " GROUP BY modeRemboursement")->fetchAll(PDO::FETCH_ASSOC);
        $stats['emails']         = $this->db->query("SELECT emailEnvoye, COUNT(*) as total FROM " . $this->table_name . " GROUP BY emailEnvoye")->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }

    private function validate(FicheRendezVous $f, &$errors) {
        $errors = [];
        if (empty($f->getIdRDV())) $errors['idRDV'] = "Veuillez associer un RDV.";
        if ($f->getTarifConsultation() === '' || !is_numeric($f->getTarifConsultation())) $errors['tarifConsultation'] = "Le tarif doit être numérique.";
        return empty($errors);
    }

    public function index() {
        $stmt = $this->sqlRead();
        $fiches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/ficherdv/index.php';
    }

    public function create() {
        $rdvs = $this->sqlReadAllRdvs();
        require_once __DIR__ . '/../views/ficherdv/form.php';
    }

    public function store() {
        if ($_POST) {
            $f = new FicheRendezVous();
            $f->setIdRDV($_POST['idRDV']);
            $f->setPiecesAApporter($_POST['piecesAApporter'] ?? '');
            $f->setConsignesAvantConsultation($_POST['consignesAvantConsultation'] ?? '');
            $f->setTarifConsultation($_POST['tarifConsultation']);
            $f->setModeRemboursement($_POST['modeRemboursement'] ?? '');
            $errors = [];
            if ($this->validate($f, $errors) && $this->sqlCreate($f)) {
                header("Location: index.php?page=ficherdv&msg=created");
            } else { $oldData = $_POST; $rdvs = $this->sqlReadAllRdvs(); require_once __DIR__ . '/../views/ficherdv/form.php'; }
        }
    }

    public function edit($id) {
        $f = $this->sqlReadOne($id);
        if ($f) {
            $ficheData = ['idFiche' => $f->getIdFiche(), 'idRDV' => $f->getIdRDV(),
                          'piecesAApporter' => $f->getPiecesAApporter(), 'consignesAvantConsultation' => $f->getConsignesAvantConsultation(),
                          'tarifConsultation' => $f->getTarifConsultation(), 'modeRemboursement' => $f->getModeRemboursement()];
            $rdvs = $this->sqlReadAllRdvs();
            require_once __DIR__ . '/../views/ficherdv/form.php';
        } else { header("Location: index.php?page=ficherdv&msg=notfound"); }
    }

    public function update($id) {
        if ($_POST) {
            $f = new FicheRendezVous();
            $f->setIdFiche($id);
            $f->setIdRDV($_POST['idRDV']);
            $f->setPiecesAApporter($_POST['piecesAApporter'] ?? '');
            $f->setConsignesAvantConsultation($_POST['consignesAvantConsultation'] ?? '');
            $f->setTarifConsultation($_POST['tarifConsultation']);
            $f->setModeRemboursement($_POST['modeRemboursement'] ?? '');
            $errors = [];
            if ($this->validate($f, $errors) && $this->sqlUpdate($f)) {
                header("Location: index.php?page=ficherdv&msg=updated");
            } else { $ficheData = $_POST; $ficheData['idFiche'] = $id; $rdvs = $this->sqlReadAllRdvs(); require_once __DIR__ . '/../views/ficherdv/form.php'; }
        }
    }

    public function delete($id) {
        if ($this->sqlDelete($id)) header("Location: index.php?page=ficherdv&msg=deleted");
        else header("Location: index.php?page=ficherdv");
    }

    public function marquerGenere($id)       { $this->sqlSetGenere($id);      header("Location: index.php?page=ficherdv&msg=action"); }
    public function marquerEmailEnvoye($id)  { $this->sqlSetEmail($id);       header("Location: index.php?page=ficherdv&msg=action"); }
    public function marquerCalendrier($id)   { $this->sqlSetCalendrier($id);  header("Location: index.php?page=ficherdv&msg=action"); }

    public function stats() {
        $stats = $this->sqlGetStats();
        require_once __DIR__ . '/../views/ficherdv/stats.php';
    }
}
?>
