<?php
require_once __DIR__ . '/../model/Distribution_controlee.php';

class controller_distribution
{
    private $model;

    public function __construct()
    {
        $this->model = new Distribution_controlee();
    }

    public function ajouter($data)
    {
        if ($data['quantite_distribuee'] <= 0) {
            return "Quantité invalide";
        }

        $result = $this->model->ajouter($data);

        if (!$result) {
            return "Quantité insuffisante ❌";
        }

        return "Distribution ajoutée ✅";
    }

    public function afficher()
    {
        return $this->model->afficher();
    }

    public function supprimer($id)
    {
        return $this->model->supprimer($id);
    }
}