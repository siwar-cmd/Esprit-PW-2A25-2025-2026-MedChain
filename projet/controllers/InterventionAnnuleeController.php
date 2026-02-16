<?php
require_once __DIR__ . '/../models/InterventionAnnulee.php';
require_once __DIR__ . '/../config.php';

class InterventionAnnuleeController {
    private $annuleeModel;

    public function __construct() {
        $this->annuleeModel = new InterventionAnnulee();
    }

    public function addAnnulation($data) {
        // Validation
        $errors = [];
        if (empty($data['idIntervention'])) $errors[] = "L'intervention est obligatoire";
        if (empty($data['raison'])) $errors[] = "La raison est obligatoire";

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Vérifier si déjà annulée
        if ($this->annuleeModel->isInterventionAnnulee($data['idIntervention'])) {
            return ['success' => false, 'message' => 'Cette intervention est déjà annulée'];
        }

        $result = $this->annuleeModel->addAnnulation($data);
        if ($result) {
            return ['success' => true, 'message' => 'Intervention annulée est terminée'];
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'annulation'];
    }

    public function getAllAnnulations() {
        return $this->annuleeModel->getAllAnnulations();
    }

    public function getInterventionsNonAnnulees() {
        return $this->annuleeModel->getInterventionsNonAnnulees();
    }

    public function getAnnulationByInterventionId($id) {
        return $this->annuleeModel->getAnnulationByInterventionId($id);
    }
}
