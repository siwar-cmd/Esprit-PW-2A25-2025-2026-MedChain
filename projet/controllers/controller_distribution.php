<?php
require_once __DIR__ . '/../config.php';

class DistributionController {

    private $db;

    public function __construct() {
        $this->db = config::getConnexion();
    }

    /* ================= AJOUT ================= */
    public function add($data) {

        $sql = "INSERT INTO distribution_controlee
        (id_lot, date_distribution, quantite_distribuee, patient, responsable)
        VALUES
        (:id_lot, :date_distribution, :quantite_distribuee, :patient, :responsable)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_lot' => $data['id_lot'],
            'date_distribution' => $data['date_distribution'],
            'quantite_distribuee' => $data['quantite_distribuee'],
            'patient' => $data['patient'],
            'responsable' => $data['responsable']
        ]);
    }
public function getAll($search = null) {

    if ($search) {
        $sql = "SELECT * FROM distribution_controlee 
                WHERE patient LIKE :search 
                OR id_lot LIKE :search";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'search' => "%$search%"
        ]);
    } else {
        $sql = "SELECT * FROM distribution_controlee";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /* ================= LISTE ================= */
    public function list() {

        $sql = "SELECT * FROM distribution_controlee ORDER BY id_distribution DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function listDistributions() {
        return $this->list();
    }

    /* ================= DELETE ================= */
    public function delete($id) {

        $sql = "DELETE FROM distribution_controlee WHERE id_distribution = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);
    }

    /* ================= GET ONE ================= */
    public function getById($id) {

        $sql = "SELECT * FROM distribution_controlee WHERE id_distribution = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        return $stmt->fetch();
    }

    /* ================= UPDATE ================= */
    public function update($id, $data) {

        $sql = "UPDATE distribution_controlee SET
                id_lot = :id_lot,
                date_distribution = :date_distribution,
                quantite_distribuee = :quantite_distribuee,
                patient = :patient,
                responsable = :responsable
                WHERE id_distribution = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id,
            'id_lot' => $data['id_lot'],
            'date_distribution' => $data['date_distribution'],
            'quantite_distribuee' => $data['quantite_distribuee'],
            'patient' => $data['patient'],
            'responsable' => $data['responsable']
        ]);
    }

    /* ================= SEARCH RESPONSABLE ================= */
    public function searchByResponsable($responsable) {

        $sql = "SELECT * FROM distribution_controlee
                WHERE responsable LIKE :r
                ORDER BY id_distribution DESC";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'r' => "%".$responsable."%"
        ]);

        return $stmt->fetchAll();
    }

    /* ================= SEARCH GLOBAL (FIX FINAL HY093) ================= */
    public function search($value) {

        if (empty($value)) {
            return $this->list();
        }

        $sql = "SELECT * FROM distribution_controlee
                WHERE responsable LIKE :v1
                OR patient LIKE :v2
                OR id_lot LIKE :v3
                ORDER BY id_distribution DESC";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'v1' => "%".$value."%",
            'v2' => "%".$value."%",
            'v3' => "%".$value."%"
        ]);

        return $stmt->fetchAll();
    }

    /* ================= WRAPPER ================= */
    public function searchAll($value) {
        return $this->search($value);
    }
}
?>