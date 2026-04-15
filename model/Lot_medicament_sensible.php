<?php
require_once __DIR__ . '/../config/config.php';

class Lot_medicament_sensible
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Config::getConnexion();
    }

    // 🔹 Ajouter
    public function ajouter($data)
    {
        $sql = "INSERT INTO lot_medicament_sensible 
        (nom_medicament, type_medicament, date_fabrication, date_expiration, quantite_initial, quantite_restante, description)
        VALUES (:nom, :type, :df, :de, :qi, :qr, :desc)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'nom' => $data['nom_medicament'],
            'type' => $data['type_medicament'],
            'df' => $data['date_fabrication'],
            'de' => $data['date_expiration'],
            'qi' => $data['quantite_initial'],
            'qr' => $data['quantite_initial'],
            'desc' => $data['description']
        ]);
    }

    // 🔹 Afficher
    public function afficher()
    {
        return $this->pdo->query("SELECT * FROM lot_medicament_sensible")->fetchAll();
    }

    // 🔹 Supprimer
    public function supprimer($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM lot_medicament_sensible WHERE id_lot = ?");
        return $stmt->execute([$id]);
    }

    // 🔹 Modifier
    public function modifier($id, $data)
    {
        $sql = "UPDATE lot_medicament_sensible SET 
        nom_medicament=?, type_medicament=?, date_fabrication=?, date_expiration=?, quantite_initial=?, description=?
        WHERE id_lot=?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nom_medicament'],
            $data['type_medicament'],
            $data['date_fabrication'],
            $data['date_expiration'],
            $data['quantite_initial'],
            $data['description'],
            $id
        ]);
    }

    // 🔹 Get by id
    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lot_medicament_sensible WHERE id_lot=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}