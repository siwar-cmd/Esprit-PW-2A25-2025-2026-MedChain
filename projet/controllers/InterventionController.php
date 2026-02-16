<?php
require_once __DIR__ . '/../models/Intervention.php';
require_once __DIR__ . '/../config.php';

class InterventionController {
    private $interventionModel;

    public function __construct() {
        $this->interventionModel = new Intervention();
    }

    // ==================== CRUD ====================

    public function getAllInterventions() {
        return $this->interventionModel->getAllInterventions();
    }

    public function getInterventionById($id) {
        return $this->interventionModel->getInterventionById($id);
    }

    public function addIntervention($data) {
        // Validation
        $errors = [];
        if (empty($data['type'])) $errors[] = "Le type est obligatoire";
        if (empty($data['date_intervention'])) $errors[] = "La date est obligatoire";
        if (empty($data['duree']) || $data['duree'] <= 0) $errors[] = "La durée doit être positive";
        if (empty($data['chirurgien'])) $errors[] = "Le chirurgien est obligatoire";
        if (empty($data['niveau_urgence']) || $data['niveau_urgence'] < 1 || $data['niveau_urgence'] > 5) $errors[] = "Le niveau d'urgence doit être entre 1 et 5";

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $result = $this->interventionModel->addIntervention($data);
        if ($result) {
            return ['success' => true, 'message' => 'Intervention ajoutée avec succès'];
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout'];
    }

    public function updateIntervention($id, $data) {
        $errors = [];
        if (empty($data['type'])) $errors[] = "Le type est obligatoire";
        if (empty($data['date_intervention'])) $errors[] = "La date est obligatoire";
        if (empty($data['duree']) || $data['duree'] <= 0) $errors[] = "La durée doit être positive";
        if (empty($data['chirurgien'])) $errors[] = "Le chirurgien est obligatoire";
        if (empty($data['niveau_urgence']) || $data['niveau_urgence'] < 1 || $data['niveau_urgence'] > 5) $errors[] = "Le niveau d'urgence doit être entre 1 et 5";

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $result = $this->interventionModel->updateIntervention($id, $data);
        if ($result) {
            return ['success' => true, 'message' => 'Intervention mise à jour avec succès'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
    }

    public function deleteIntervention($id) {
        $result = $this->interventionModel->deleteIntervention($id);
        if ($result) {
            return ['success' => true, 'message' => 'Intervention supprimée avec succès'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la suppression'];
    }

    // ==================== RECHERCHE & TRI ====================

    public function searchInterventions($keyword) {
        return $this->interventionModel->searchInterventions($keyword);
    }

    public function getAllSorted($column, $order) {
        return $this->interventionModel->getAllSorted($column, $order);
    }

    // ==================== STATISTIQUES ====================

    public function getStatistics() {
        return $this->interventionModel->getStatistics();
    }

    // ==================== JOINTURE ====================

    public function getInterventionsWithAnnulations() {
        return $this->interventionModel->getInterventionsWithAnnulations();
    }

    public function getInterventionWithAnnulation($id) {
        return $this->interventionModel->getInterventionWithAnnulation($id);
    }
}
