<?php
require_once __DIR__ . '/../models/Materiel.php';
require_once __DIR__ . '/../config.php';

class MaterielController {
    private $materielModel;

    public function __construct() {
        $this->materielModel = new Materiel();
    }

    // ==================== CRUD ====================

    public function getAllMateriels() {
        return $this->materielModel->getAllMateriels();
    }

    public function getMaterielById($id) {
        return $this->materielModel->getMaterielById($id);
    }

    public function addMateriel($data) {
        $errors = [];
        if (empty($data['nom'])) $errors[] = "Le nom est obligatoire";
        if (empty($data['categorie'])) $errors[] = "La catégorie est obligatoire";
        if (empty($data['disponibilite'])) $errors[] = "La disponibilité est obligatoire";

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $result = $this->materielModel->addMateriel($data);
        if ($result) {
            return ['success' => true, 'message' => 'Matériel ajouté avec succès'];
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout'];
    }

    public function updateMateriel($id, $data) {
        $errors = [];
        if (empty($data['nom'])) $errors[] = "Le nom est obligatoire";
        if (empty($data['categorie'])) $errors[] = "La catégorie est obligatoire";
        if (empty($data['disponibilite'])) $errors[] = "La disponibilité est obligatoire";

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $result = $this->materielModel->updateMateriel($id, $data);
        if ($result) {
            return ['success' => true, 'message' => 'Matériel mis à jour avec succès'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
    }

    public function deleteMateriel($id) {
        $result = $this->materielModel->deleteMateriel($id);
        if ($result) {
            return ['success' => true, 'message' => 'Matériel supprimé avec succès'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la suppression'];
    }

    public function searchMateriels($keyword) {
        return $this->materielModel->searchMateriels($keyword);
    }
}
