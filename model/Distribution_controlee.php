<?php
require_once __DIR__ . '/../config/config.php';

class Distribution_controlee
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Config::getConnexion();
    }

    // 🔹 Ajouter distribution + update quantite
    public function ajouter($data)
    {
        // 1. vérifier quantité restante
        $stmt = $this->pdo->prepare("SELECT quantite_restante FROM lot_medicament_sensible WHERE id_lot=?");
        $stmt->execute([$data['id_lot']]);
        $lot = $stmt->fetch();

        if (!$lot || $lot['quantite_restante'] < $data['quantite_distribuee']) {
            return false; // quantité insuffisante ❌
        }

        // 2. insertion
        $sql = "INSERT INTO distribution_controlee 
        (id_lot, date_distribution, quantite_distribuee, destinataire, responsable)
        VALUES (?, NOW(), ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['id_lot'],
            $data['quantite_distribuee'],
            $data['destinataire'],
            $data['responsable']
        ]);

        // 3. update quantite restante
        $update = $this->pdo->prepare("UPDATE lot_medicament_sensible 
        SET quantite_restante = quantite_restante - ? WHERE id_lot=?");

        $update->execute([
            $data['quantite_distribuee'],
            $data['id_lot']
        ]);

        return true;
    }

    // 🔹 Afficher
    public function afficher()
    {
        return $this->pdo->query("SELECT * FROM distribution_controlee")->fetchAll();
    }

    // 🔹 Supprimer
    public function supprimer($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM distribution_controlee WHERE id_distribution=?");
        return $stmt->execute([$id]);
    }
}