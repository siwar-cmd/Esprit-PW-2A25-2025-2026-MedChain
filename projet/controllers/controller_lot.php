<?php
require_once __DIR__ . "/../models/LotMedicament.php";

class LotController {

    private $pdo;

    public function __construct() {
        $this->pdo = new PDO("mysql:host=localhost;dbname=user", "root", "");
    }

    // ================= READ + SEARCH =================
    public function getAll($search = null) {

        if ($search) {
            $sql = "SELECT * FROM lot_medicament_sensible
                    WHERE nom_medicament LIKE :s
                    OR type_medicament LIKE :s
                    OR id_lot LIKE :s";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['s' => "%$search%"]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM lot_medicament_sensible");
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM lot_medicament_sensible WHERE id_lot=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /* ================= GET AVAILABLE LOTS ================= */
    public function getAvailableLots() {
        $sql = "SELECT id_lot, nom_medicament, quantite_restante 
                FROM lot_medicament_sensible 
                WHERE quantite_restante > 0 
                ORDER BY nom_medicament ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================= CREATE =================
    public function add(LotMedicament $l) {

        $sql = "INSERT INTO lot_medicament_sensible
        (id_lot, nom_medicament, type_medicament, date_fabrication, date_expiration, quantite_initial, quantite_restante, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $l->getIdLot(),
            $l->getNom(),
            $l->getType(),
            $l->getDateFabrication(),
            $l->getDateExpiration(),
            $l->getQuantiteInitial(),
            $l->getQuantiteRestante(),
            $l->getDescription()
        ]);
    }

    // ================= UPDATE =================
    public function update($id, LotMedicament $l) {

        $sql = "UPDATE lot_medicament_sensible SET
        nom_medicament=?,
        type_medicament=?,
        date_fabrication=?,
        date_expiration=?,
        quantite_initial=?,
        quantite_restante=?,
        description=?
        WHERE id_lot=?";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $l->getNom(),
            $l->getType(),
            $l->getDateFabrication(),
            $l->getDateExpiration(),
            $l->getQuantiteInitial(),
            $l->getQuantiteRestante(),
            $l->getDescription(),
            $id
        ]);
    }

    // ================= DELETE =================
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM lot_medicament_sensible WHERE id_lot=?");
        return $stmt->execute([$id]);
    }

    // ================= STATS =================
    public function stats() {

        $total = $this->pdo->query("SELECT COUNT(*) FROM lot_medicament_sensible")->fetchColumn();

        $expired = $this->pdo->query("SELECT COUNT(*) FROM lot_medicament_sensible WHERE date_expiration < CURDATE()")->fetchColumn();

        $low = $this->pdo->query("SELECT COUNT(*) FROM lot_medicament_sensible WHERE quantite_restante < 10")->fetchColumn();

        return [
            'total' => $total,
            'expired' => $expired,
            'low' => $low
        ];
    }

    // ================= STATS BY TYPE =================
    public function statsByType() {

        $sql = "SELECT type_medicament, COUNT(*) as total
                FROM lot_medicament_sensible
                GROUP BY type_medicament";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================= ALERTS =================
    public function getAlertLots() {

        $stmt = $this->pdo->query("SELECT * FROM lot_medicament_sensible");
        $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today = date('Y-m-d');
        $alerts = [];

        foreach ($lots as $l) {

            $status = "";

            if ($l['date_expiration'] < $today) {
                $status = "expired";
            }
            elseif (strtotime($l['date_expiration']) <= strtotime("+7 days")) {
                $status = "soon";
            }
            elseif ($l['quantite_restante'] < 10) {
                $status = "low";
            }

            if ($status != "") {
                $l['status'] = $status;
                $alerts[] = $l;
            }
        }

        return $alerts;
    }

    // ⭐ NEW FUNCTION (FRONTOFFICE ONLY - DO NOT TOUCH OLD LOGIC)
    public function getAllLots() {
        $stmt = $this->pdo->query("SELECT * FROM lot_medicament_sensible");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}