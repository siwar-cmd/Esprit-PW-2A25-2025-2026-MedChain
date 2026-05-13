<?php
require_once __DIR__ . '/../models/Ambulance.php';

class AmbulanceController {
    private $db;
    private $table_name = "Ambulance";

    public function __construct($db) { $this->db = $db; }

    private function sqlRead() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY idAmbulance DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function sqlReadOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idAmbulance = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $a = new Ambulance();
            $a->setIdAmbulance($row['idAmbulance']);
            $a->setImmatriculation($row['immatriculation']);
            $a->setStatut($row['statut']);
            $a->setModele($row['modele']);
            $a->setCapacite($row['capacite']);
            $a->setEstDisponible($row['estDisponible']);
            return $a;
        }
        return null;
    }

    private function sqlImmatriculationExists($immatriculation, $excludeId = null) {
        if ($excludeId) {
            $query = "SELECT idAmbulance FROM " . $this->table_name . " WHERE immatriculation = :imm AND idAmbulance != :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':imm', $immatriculation);
            $stmt->bindParam(':id',  $excludeId);
        } else {
            $query = "SELECT idAmbulance FROM " . $this->table_name . " WHERE immatriculation = :imm";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':imm', $immatriculation);
        }
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function sqlHasMissions($id) {
        $query = "SELECT idMission FROM Mission WHERE idAmbulance = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function sqlCreate(Ambulance $a) {
        $imm  = strtoupper(trim(htmlspecialchars(strip_tags($a->getImmatriculation()))));
        $stat = trim(htmlspecialchars(strip_tags($a->getStatut())));
        $mod  = ucwords(trim(htmlspecialchars(strip_tags($a->getModele()))));
        $cap  = filter_var($a->getCapacite(), FILTER_VALIDATE_INT);
        $disp = $a->getEstDisponible();
        $query = "INSERT INTO " . $this->table_name . " SET immatriculation=:imm, statut=:stat, modele=:mod, capacite=:cap, estDisponible=:disp";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':imm',  $imm);  $stmt->bindParam(':stat', $stat);
        $stmt->bindParam(':mod',  $mod);  $stmt->bindParam(':cap',  $cap);
        $stmt->bindParam(':disp', $disp);
        return $stmt->execute();
    }

    private function sqlUpdate(Ambulance $a) {
        $imm  = strtoupper(trim(htmlspecialchars(strip_tags($a->getImmatriculation()))));
        $stat = trim(htmlspecialchars(strip_tags($a->getStatut())));
        $mod  = ucwords(trim(htmlspecialchars(strip_tags($a->getModele()))));
        $cap  = filter_var($a->getCapacite(), FILTER_VALIDATE_INT);
        $disp = $a->getEstDisponible();
        $id   = $a->getIdAmbulance();
        $query = "UPDATE " . $this->table_name . " SET immatriculation=:imm, statut=:stat, modele=:mod, capacite=:cap, estDisponible=:disp WHERE idAmbulance=:id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':imm',  $imm);  $stmt->bindParam(':stat', $stat);
        $stmt->bindParam(':mod',  $mod);  $stmt->bindParam(':cap',  $cap);
        $stmt->bindParam(':disp', $disp); $stmt->bindParam(':id',   $id);
        return $stmt->execute();
    }

    private function sqlDelete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE idAmbulance = ?";
        $stmt = $this->db->prepare($query);
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    private function sqlSearch($keywords) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE immatriculation LIKE ? OR modele LIKE ? OR statut LIKE ? ORDER BY idAmbulance DESC";
        $stmt = $this->db->prepare($query);
        $kw = "%" . htmlspecialchars(strip_tags($keywords)) . "%";
        $stmt->bindParam(1, $kw); $stmt->bindParam(2, $kw); $stmt->bindParam(3, $kw);
        $stmt->execute();
        return $stmt;
    }

    private function sqlGetStats() {
        $stats = [];
        $stmt1 = $this->db->prepare("SELECT statut, COUNT(idAmbulance) as total FROM " . $this->table_name . " GROUP BY statut");
        $stmt1->execute();
        $stats['status_count'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        $stmt2 = $this->db->prepare("SELECT estDisponible, COUNT(idAmbulance) as total FROM " . $this->table_name . " GROUP BY estDisponible");
        $stmt2->execute();
        $stats['dispo_count'] = ['dispo' => 0, 'indispo' => 0];
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            if ($row['estDisponible'] == 1) $stats['dispo_count']['dispo'] = $row['total'];
            else $stats['dispo_count']['indispo'] = $row['total'];
        }
        return $stats;
    }

    private function validate(Ambulance $a, &$errors, $isUpdate = false) {
        $errors = [];
        $imm = $a->getImmatriculation();
        if (empty($imm)) $errors['immatriculation'] = "L'immatriculation est obligatoire";
        elseif (strlen($imm) < 5) $errors['immatriculation'] = "L'immatriculation doit contenir au moins 5 caractères";
        elseif (strlen($imm) > 20) $errors['immatriculation'] = "L'immatriculation ne doit pas dépasser 20 caractères";
        elseif (!preg_match('/^[A-Z0-9-]+$/i', $imm)) $errors['immatriculation'] = "L'immatriculation ne doit contenir que des lettres, chiffres et tirets";
        $mod = $a->getModele();
        if (empty($mod)) $errors['modele'] = "Le modèle est obligatoire";
        elseif (strlen($mod) < 2) $errors['modele'] = "Le modèle doit contenir au moins 2 caractères";
        elseif (strlen($mod) > 50) $errors['modele'] = "Le modèle ne doit pas dépasser 50 caractères";
        $validStatus = ['En service', 'En maintenance', 'Hors service'];
        if (empty($a->getStatut())) $errors['statut'] = "Le statut est obligatoire";
        elseif (!in_array($a->getStatut(), $validStatus)) $errors['statut'] = "Statut invalide";
        $cap = $a->getCapacite();
        if (empty($cap)) $errors['capacite'] = "La capacité est obligatoire";
        elseif (!is_numeric($cap) || $cap < 1 || $cap > 20 || !ctype_digit((string)$cap)) $errors['capacite'] = "Capacité invalide (1-20 places)";
        if (!isset($errors['immatriculation'])) {
            if ($isUpdate) { if ($this->sqlImmatriculationExists($imm, $a->getIdAmbulance())) $errors['immatriculation'] = "Cette immatriculation est déjà utilisée"; }
            else { if ($this->sqlImmatriculationExists($imm)) $errors['immatriculation'] = "Cette immatriculation existe déjà"; }
        }
        return empty($errors);
    }

    public function index() {
        $stmt = $this->sqlRead();
        $ambulances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/ambulance/index.php';
    }

    public function create() { require_once __DIR__ . '/../views/ambulance/form.php'; }

    public function store() {
        if ($_POST) {
            $a = new Ambulance();
            $a->setImmatriculation($_POST['immatriculation']);
            $a->setStatut($_POST['statut']);
            $a->setModele($_POST['modele']);
            $a->setCapacite($_POST['capacite']);
            $a->setEstDisponible(isset($_POST['estDisponible']) ? 1 : 0);
            $errors = [];
            if ($this->validate($a, $errors) && $this->sqlCreate($a)) {
                header("Location: index.php?page=ambulance&msg=created");
            } else { $oldData = $_POST; require_once __DIR__ . '/../views/ambulance/form.php'; }
        }
    }

    public function edit($id) {
        $a = $this->sqlReadOne($id);
        if ($a) {
            $ambulance = ['idAmbulance' => $a->getIdAmbulance(), 'immatriculation' => $a->getImmatriculation(),
                          'statut' => $a->getStatut(), 'modele' => $a->getModele(),
                          'capacite' => $a->getCapacite(), 'estDisponible' => $a->getEstDisponible()];
            require_once __DIR__ . '/../views/ambulance/form.php';
        } else { header("Location: index.php?page=ambulance&msg=notfound"); }
    }

    public function update($id) {
        if ($_POST) {
            $a = new Ambulance();
            $a->setIdAmbulance($id);
            $a->setImmatriculation($_POST['immatriculation']);
            $a->setStatut($_POST['statut']);
            $a->setModele($_POST['modele']);
            $a->setCapacite($_POST['capacite']);
            $a->setEstDisponible(isset($_POST['estDisponible']) ? 1 : 0);
            $errors = [];
            if ($this->validate($a, $errors, true) && $this->sqlUpdate($a)) {
                header("Location: index.php?page=ambulance&msg=updated");
            } else { $ambulance = $_POST; $ambulance['idAmbulance'] = $id; require_once __DIR__ . '/../views/ambulance/form.php'; }
        }
    }

    public function delete($id) {
        if ($this->sqlHasMissions($id)) {
            header("Location: index.php?page=ambulance&msg=delete_error");
            return;
        }
        if ($this->sqlDelete($id)) header("Location: index.php?page=ambulance&msg=deleted");
        else header("Location: index.php?page=ambulance&msg=delete_error");
    }

    public function show($id) {
        $a = $this->sqlReadOne($id);
        if ($a) { require_once __DIR__ . '/../views/ambulance/show.php'; }
        else { header("Location: index.php?page=ambulance&msg=notfound"); }
    }

    public function search() {
        if (isset($_GET['search'])) {
            $stmt = $this->sqlSearch($_GET['search']);
            $ambulances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            require_once __DIR__ . '/../views/ambulance/index.php';
        } else { $this->index(); }
    }

    public function stats() {
        $stats = $this->sqlGetStats();
        require_once __DIR__ . '/../views/ambulance/stats.php';
    }
}
?>