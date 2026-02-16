<?php
require_once __DIR__ . '/../models/Distribution.php';
require_once __DIR__ . '/../models/LotMedicament.php';

class DistributionController {
    private $dist;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->dist = new Distribution($db);
    }

    public function index() {
        $stmt = $this->dist->read();
        $distributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/distribution/index.php';
    }

    public function create() { 
        $lotModel = new LotMedicament($this->db);
        $lots = $lotModel->read()->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/distribution/form.php'; 
    }

    public function store() {
        if($_POST) {
            $this->dist->idLot = $_POST['idLot'];
            $this->dist->quantite = $_POST['quantite'] ?? '';
            $this->dist->dateDistribution = $_POST['dateDistribution'] ?? '';
            $this->dist->destinataire = $_POST['destinataire'] ?? '';

            // Validation de stock (Sécurité serveur)
            $lotModel = new LotMedicament($this->db);
            $lotModel->idLot = $this->dist->idLot;
            $lotModel->readOne();
            
            if(is_numeric($this->dist->quantite) && $this->dist->quantite > $lotModel->quantite) {
                $this->dist->errors['quantite'] = "Erreur : La quantité demandée (" . $this->dist->quantite . ") dépasse le stock disponible (" . $lotModel->quantite . ").";
                $errors = $this->dist->getErrors();
                $oldData = $_POST;
                $lots = $lotModel->read()->fetchAll(PDO::FETCH_ASSOC);
                require_once __DIR__ . '/../views/distribution/form.php';
                return;
            }

            if($this->dist->create()) {
                // Diminuer la quantité dans le lot
                $lotModel->subtractQuantity($this->dist->quantite);
                header("Location: index.php?page=distribution&msg=created");
            } else {
                $errors = $this->dist->getErrors();
                $oldData = $_POST;
                $lots = $lotModel->read()->fetchAll(PDO::FETCH_ASSOC);
                require_once __DIR__ . '/../views/distribution/form.php';
            }
        }
    }

    public function edit($id) {
        $this->dist->idDistribution = $id;
        if($this->dist->readOne()) {
            $distData = [
                'idDistribution' => $this->dist->idDistribution,
                'idLot' => $this->dist->idLot,
                'quantite' => $this->dist->quantite,
                'dateDistribution' => $this->dist->dateDistribution,
                'destinataire' => $this->dist->destinataire
            ];
            $lotModel = new LotMedicament($this->db);
            // Lecture classique pour le select
            $lots = $lotModel->read()->fetchAll(PDO::FETCH_ASSOC);
            require_once __DIR__ . '/../views/distribution/form.php';
        } else { header("Location: index.php?page=distribution&msg=notfound"); }
    }

    public function update($id) {
        if($_POST) {
            $this->dist->idDistribution = $id;
            
            // On mémorise l'ancienne distrib pour calculer le différentiel
            $oldDist = clone $this->dist;
            $oldDist->readOne();
            $oldQuantite = (int)$oldDist->quantite;
            $oldIdLot = $oldDist->idLot;

            $this->dist->idLot = $_POST['idLot'];
            $this->dist->quantite = $_POST['quantite'] ?? '';
            $this->dist->dateDistribution = $_POST['dateDistribution'] ?? '';
            $this->dist->destinataire = $_POST['destinataire'] ?? '';

            if($this->dist->update()) {
                $lotModel = new LotMedicament($this->db);
                
                // Si on a gardé le même lot, on applique juste la différence (- ou +)
                if($oldIdLot == $this->dist->idLot) {
                    $diff = (int)$this->dist->quantite - $oldQuantite;
                    $lotModel->idLot = $this->dist->idLot;
                    $lotModel->subtractQuantity($diff);
                } else {
                    // Si on a changé de lot :
                    // 1. Remettre l'ancienne quantité dans l'ancien lot
                    $lotModel->idLot = $oldIdLot;
                    $lotModel->addQuantity($oldQuantite);
                    // 2. Soustraire la nouvelle quantité du nouveau lot
                    $lotModel->idLot = $this->dist->idLot;
                    $lotModel->subtractQuantity($this->dist->quantite);
                }

                header("Location: index.php?page=distribution&msg=updated");
            } else {
                $errors = $this->dist->getErrors();
                $distData = $_POST;
                $distData['idDistribution'] = $id;
                $lotModel = new LotMedicament($this->db);
                $lots = $lotModel->read()->fetchAll(PDO::FETCH_ASSOC);
                require_once __DIR__ . '/../views/distribution/form.php';
            }
        }
    }

    public function delete($id) {
        $this->dist->idDistribution = $id;
        if($this->dist->readOne()) {
            // Lecture pour savoir combien restituer
            $qte = $this->dist->quantite;
            $lotId = $this->dist->idLot;
            
            if($this->dist->delete()) {
                $lotModel = new LotMedicament($this->db);
                $lotModel->idLot = $lotId;
                $lotModel->addQuantity($qte);
                header("Location: index.php?page=distribution&msg=deleted");
            } else header("Location: index.php?page=distribution");
        } else header("Location: index.php?page=distribution");
    }

    public function stats() {
        $stats = $this->dist->getStats();
        require_once __DIR__ . '/../views/distribution/stats.php';
    }
}
?>
