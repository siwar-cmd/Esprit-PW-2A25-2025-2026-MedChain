<?php
require_once __DIR__ . '/../model/Lot_medicament_sensible.php';

class controller_lot
{
    private $model;

    public function __construct()
    {
        $this->model = new Lot_medicament_sensible();
    }

    // ================= ADD WITH FIELD VALIDATION =================
    public function ajouter($data)
    {
        $errors = [];

        // 🔴 Required fields
        if (empty($data['nom_medicament'])) {
            $errors['nom'] = "Nom obligatoire";
        }

        if (empty($data['type_medicament'])) {
            $errors['type'] = "Type obligatoire";
        }

        if (empty($data['date_fabrication'])) {
            $errors['df'] = "Date fabrication obligatoire";
        }

        if (empty($data['date_expiration'])) {
            $errors['de'] = "Date expiration obligatoire";
        }

        if (empty($data['quantite_initial'])) {
            $errors['qte'] = "Quantité obligatoire";
        }

        // 🔴 Numeric check
        if (
            !empty($data['quantite_initial']) &&
            (!is_numeric($data['quantite_initial']) || $data['quantite_initial'] <= 0)
        ) {
            $errors['qte'] = "Quantité invalide";
        }

        // 🔴 Date logic
        if (
            !empty($data['date_fabrication']) &&
            !empty($data['date_expiration']) &&
            strtotime($data['date_expiration']) < strtotime($data['date_fabrication'])
        ) {
            $errors['de'] = "La date d'expiration doit être superieur à la date de fabrication";
        }

        // ❌ If errors exist, return them
        if (!empty($errors)) {
            return $errors;
        }

        // ✅ Insert
        return $this->model->ajouter($data);
    }

    public function afficher()
    {
        return $this->model->afficher();
    }

    public function supprimer($id)
    {
        return $this->model->supprimer($id);
    }

    public function modifier($id, $data)
    {
        return $this->model->modifier($id, $data);
    }
    public function getById($id)
{
    return $this->model->getById($id);
}
}