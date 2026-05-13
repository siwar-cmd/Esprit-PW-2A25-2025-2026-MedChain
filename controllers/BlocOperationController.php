<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Materiel.php';
require_once __DIR__ . '/../models/Intervention.php';

class BlocOperationController {
    private $pdo;
    private $user_role;
    private $user_id;

    public function __construct() {
        $this->pdo = config::getConnexion();
        $this->user_role = $_SESSION['user_role'] ?? null;
        $this->user_id = $_SESSION['user_id'] ?? null;
    }

    // ===== MATÉRIEL - CRUD =====
    
    public function createMateriel($data): array {
        if ($this->user_role !== 'admin') {
            return ["success" => false, "message" => "Accès refusé"];
        }

        try {
            $materiel = new Materiel();

            // Validation
            $id_materiel = trim($data['id_materiel'] ?? '');
            if ($id_materiel === '') {
                return ["success" => false, "message" => "L'ID du matériel est requis."];
            }
            if ($materiel->existsByIdMaterielStr($id_materiel)) {
                return ["success" => false, "message" => "Un matériel avec cet ID existe déjà."];
            }

            $disponibilite = $data['disponibilite'] ?? '';
            $allowedDisponibilite = ['en_stock', 'utilise', 'indisponible'];
            if ($disponibilite !== '' && !in_array($disponibilite, $allowedDisponibilite)) {
                return ["success" => false, "message" => "Valeur de disponibilité invalide."];
            }

            $statutSter = $data['statutSterilisation'] ?? '';
            $allowedSter = ['sterilise', 'non_sterilise'];
            if ($statutSter !== '' && !in_array($statutSter, $allowedSter)) {
                return ["success" => false, "message" => "Valeur de stérilisation invalide."];
            }

            $max = isset($data['nombreUtilisationsMax']) ? (int)$data['nombreUtilisationsMax'] : 0;
            $act = isset($data['nombreUtilisationsActuelles']) ? (int)$data['nombreUtilisationsActuelles'] : 0;
            if ($max < 0 || $act < 0) {
                return ["success" => false, "message" => "Les nombres d'utilisations doivent être positifs ou nuls."];
            }

            $bloc = trim($data['bloc'] ?? '');
            $roomCenters = [
                'ER' => ['x' => -30, 'y' => 1, 'z' => -30],
                'ICU' => ['x' => 0, 'y' => 1, 'z' => -30],
                'Radiology' => ['x' => 30, 'y' => 1, 'z' => -30],
                'Lab' => ['x' => -30, 'y' => 1, 'z' => 30],
                'Hall' => ['x' => 0, 'y' => 1, 'z' => 30],
                'Storage' => ['x' => 30, 'y' => 1, 'z' => 30]
            ];

            if ($bloc === '' || !array_key_exists($bloc, $roomCenters)) {
                return ["success" => false, "message" => "Un bloc valide est requis."];
            }

            $center = $roomCenters[$bloc];
            $offsetX = (mt_rand() / mt_getrandmax() - 0.5) * 8;
            $offsetZ = (mt_rand() / mt_getrandmax() - 0.5) * 8;

            $pos_x = $center['x'] + $offsetX;
            $pos_y = $center['y'];
            $pos_z = $center['z'] + $offsetZ;

            $materiel->setIdMaterielStr($id_materiel)
                     ->setBloc($bloc)
                     ->setPosX($pos_x)
                     ->setPosY($pos_y)
                     ->setPosZ($pos_z)
                     ->setCategorie($data['categorie'] ?? '')
                     ->setIdIntervention($data['id_intervention'] ?? null)
                     ->setDisponibilite($disponibilite)
                     ->setStatutSterilisation($statutSter)
                     ->setNombreUtilisationsMax($max)
                     ->setNombreUtilisationsActuelles($act)
                     ->setDateUtilisationDebut($data['date_utilisation_debut'] ?? null)
                     ->setDateUtilisationFin($data['date_utilisation_fin'] ?? null);

            if ($materiel->create()) {
                return ["success" => true, "message" => "Matériel créé avec succès"];
            }
            return ["success" => false, "message" => "Erreur lors de la création"];
        } catch (Exception $e) {
            error_log("Erreur creation materiel: " . $e->getMessage());
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getMaterielList($page = 1, $limit = 10): array {
        try {
            $materiel = new Materiel();
            if ($this->user_role === 'medecin') {
                $prenomNom = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
                $nomPrenom = trim(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? ''));
                $data = $materiel->getByMedecin($prenomNom, $nomPrenom, $page, $limit);
                $total = $materiel->countByMedecin($prenomNom, $nomPrenom);
            } else {
                $data = $materiel->getAll($page, $limit);
                $total = $materiel->count();
            }
            return ["success" => true, "data" => $data, "total" => $total];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getMaterielById($id): array {
        try {
            $materiel = new Materiel();
            $data = $materiel->getById($id);
            if ($data) {
                return ["success" => true, "data" => $data];
            }
            return ["success" => false, "message" => "Matériel introuvable"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function updateMateriel($id, $data): array {
        if ($this->user_role !== 'admin') {
            return ["success" => false, "message" => "Accès refusé"];
        }

        try {
            $materiel = new Materiel();
            $existing = $materiel->getById($id);
            if (!$existing) {
                return ["success" => false, "message" => "Matériel introuvable"];
            }
            // Validation
            $id_materiel = trim($data['id_materiel'] ?? $existing['id_materiel']);
            if ($id_materiel === '') {
                return ["success" => false, "message" => "L'ID du matériel est requis."];
            }
            // If name changed, ensure uniqueness
            if ($id_materiel !== ($existing['id_materiel'] ?? '') && $materiel->existsByIdMaterielStr($id_materiel)) {
                return ["success" => false, "message" => "Un matériel avec cet ID existe déjà."];
            }

            $disponibilite = $data['disponibilite'] ?? ($existing['disponibilite'] ?? '');
            $allowedDisponibilite = ['en_stock', 'utilise', 'indisponible'];
            if ($disponibilite !== '' && !in_array($disponibilite, $allowedDisponibilite)) {
                return ["success" => false, "message" => "Valeur de disponibilité invalide."];
            }

            $statutSter = $data['statutSterilisation'] ?? ($existing['statutSterilisation'] ?? '');
            $allowedSter = ['sterilise', 'non_sterilise'];
            if ($statutSter !== '' && !in_array($statutSter, $allowedSter)) {
                return ["success" => false, "message" => "Valeur de stérilisation invalide."];
            }

            $max = isset($data['nombreUtilisationsMax']) ? (int)$data['nombreUtilisationsMax'] : ($existing['nombreUtilisationsMax'] ?? 0);
            $act = isset($data['nombreUtilisationsActuelles']) ? (int)$data['nombreUtilisationsActuelles'] : ($existing['nombreUtilisationsActuelles'] ?? 0);
            if ($max < 0 || $act < 0) {
                return ["success" => false, "message" => "Les nombres d'utilisations doivent être positifs ou nuls."];
            }

            $bloc = trim($data['bloc'] ?? ($existing['bloc'] ?? ''));
            $roomCenters = [
                'ER' => ['x' => -30, 'y' => 1, 'z' => -30],
                'ICU' => ['x' => 0, 'y' => 1, 'z' => -30],
                'Radiology' => ['x' => 30, 'y' => 1, 'z' => -30],
                'Lab' => ['x' => -30, 'y' => 1, 'z' => 30],
                'Hall' => ['x' => 0, 'y' => 1, 'z' => 30],
                'Storage' => ['x' => 30, 'y' => 1, 'z' => 30]
            ];

            if ($bloc === '' || !array_key_exists($bloc, $roomCenters)) {
                return ["success" => false, "message" => "Un bloc valide est requis."];
            }

            // If the bloc changed, recalculate the positions
            $pos_x = $existing['pos_x'];
            $pos_y = $existing['pos_y'];
            $pos_z = $existing['pos_z'];

            if ($bloc !== $existing['bloc'] || $pos_x === null) {
                $center = $roomCenters[$bloc];
                $offsetX = (mt_rand() / mt_getrandmax() - 0.5) * 8;
                $offsetZ = (mt_rand() / mt_getrandmax() - 0.5) * 8;
                $pos_x = $center['x'] + $offsetX;
                $pos_y = $center['y'];
                $pos_z = $center['z'] + $offsetZ;
            }

            $materiel->setIdMaterielStr($id_materiel)
                     ->setBloc($bloc)
                     ->setPosX($pos_x)
                     ->setPosY($pos_y)
                     ->setPosZ($pos_z)
                     ->setCategorie($data['categorie'] ?? ($existing['categorie'] ?? ''))
                     ->setIdIntervention(isset($data['id_intervention']) ? $data['id_intervention'] : ($existing['id_intervention'] ?? null))
                     ->setDisponibilite($disponibilite)
                     ->setStatutSterilisation($statutSter)
                     ->setNombreUtilisationsMax($max)
                     ->setNombreUtilisationsActuelles($act)
                     ->setDateUtilisationDebut(isset($data['date_utilisation_debut']) ? $data['date_utilisation_debut'] : ($existing['date_utilisation_debut'] ?? null))
                     ->setDateUtilisationFin(isset($data['date_utilisation_fin']) ? $data['date_utilisation_fin'] : ($existing['date_utilisation_fin'] ?? null));

            if ($materiel->update($id)) {
                return ["success" => true, "message" => "Matériel mis à jour"];
            }
            return ["success" => false, "message" => "Erreur lors de la mise à jour"];
        } catch (Exception $e) {
            error_log("Erreur update materiel: " . $e->getMessage());
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function deleteMateriel($id): array {
        if ($this->user_role !== 'admin') {
            return ["success" => false, "message" => "Accès refusé"];
        }

        try {
            $materiel = new Materiel();
            if ($materiel->delete($id)) {
                return ["success" => true, "message" => "Matériel supprimé"];
            }
            return ["success" => false, "message" => "Erreur lors de la suppression"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function searchMateriel($keyword): array {
        try {
            $materiel = new Materiel();
            $results = $materiel->search($keyword);
            return ["success" => true, "data" => $results];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getMaterielStats(): array {
        if ($this->user_role !== 'admin') {
            return ["success" => false, "message" => "Accès refusé"];
        }

        try {
            $materiel = new Materiel();
            $stats = $materiel->getStatistics();
            return ["success" => true, "data" => $stats];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getMaterielOptions(): array {
        try {
            $materiel = new Materiel();
            $options = $materiel->getAllOptions();
            return ["success" => true, "data" => $options];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getInterventionOptions(): array {
        try {
            $intervention = new Intervention();
            $options = $intervention->getAllOptions();
            return ["success" => true, "data" => $options];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    // ===== INTERVENTION - CRUD =====
    
    public function createIntervention($data): array {
        if ($this->user_role !== 'admin') {
            return ["success" => false, "message" => "Accès refusé"];
        }

        try {
            // Validation
            $type = trim($data['type'] ?? '');
            if ($type === '') {
                return ["success" => false, "message" => "Le type d'intervention est requis."];
            }

            $date = $data['date_intervention'] ?? '';
            if ($date === '') {
                return ["success" => false, "message" => "La date et l'heure de l'intervention sont requises."];
            }

            $duree = isset($data['duree']) ? (int)$data['duree'] : 0;
            if ($duree < 0) {
                return ["success" => false, "message" => "La durée doit être un nombre positif."];
            }

            $niveau = isset($data['niveau_urgence']) ? (int)$data['niveau_urgence'] : 0;
            if ($niveau < 1 || $niveau > 5) {
                return ["success" => false, "message" => "Le niveau d'urgence doit être entre 1 et 5."];
            }

            // Chirurgien handling
            $chirurgienName = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?: null;
            $chirurgien = $this->user_role === 'medecin' ? ($chirurgienName ?? ($data['chirurgien'] ?? '')) : ($data['chirurgien'] ?? $chirurgienName);
            if ($this->user_role === 'admin' && trim($chirurgien) === '') {
                return ["success" => false, "message" => "Veuillez sélectionner un chirurgien."];
            }

            $intervention = new Intervention();
            $intervention->setType($type)
                         ->setDateIntervention($date)
                         ->setDuree($duree)
                         ->setNiveauUrgence($niveau)
                         ->setChirurgien($chirurgien)
                         ->setSalle($data['salle'] ?? '')
                         ->setDescription($data['description'] ?? '');

            if ($intervention->create()) {
                return ["success" => true, "message" => "Intervention créée"];
            }
            return ["success" => false, "message" => "Erreur lors de la création de l'intervention"];
        } catch (Exception $e) {
            error_log("Erreur creation intervention: " . $e->getMessage());
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getInterventionList($page = 1, $limit = 10): array {
        try {
            $intervention = new Intervention();
            if ($this->user_role === 'medecin') {
                // On affiche tout pour le médecin pour remplir le tableau
                // $prenomNom = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
                // $nomPrenom = trim(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? ''));
                // $data = $intervention->getByMedecin($prenomNom, $nomPrenom, $page, $limit);
                // $total = $intervention->countByMedecin($prenomNom, $nomPrenom);
                $data = $intervention->getAll($page, $limit);
                $total = $intervention->count();
            } else {
                // Tous les autres rôles voient TOUTES les interventions
                $data = $intervention->getAll($page, $limit);
                $total = $intervention->count();
            }
            
            return ["success" => true, "data" => $data, "total" => $total];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getInterventionById($id): array {
        try {
            $intervention = new Intervention();
            $data = $intervention->getById($id);
            if ($data) {
                return ["success" => true, "data" => $data];
            }
            return ["success" => false, "message" => "Intervention introuvable"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function updateIntervention($id, $data): array {
        if (!in_array($this->user_role, ['admin', 'medecin'])) {
            return ["success" => false, "message" => "Accès refusé"];
        }

        try {
            $intervention = new Intervention();
            $existing = $intervention->getById($id);
            if (!$existing) {
                return ["success" => false, "message" => "Intervention introuvable"];
            }

            // Validation for updates
            $type = trim($data['type'] ?? ($existing['type'] ?? ''));
            if ($type === '') {
                return ["success" => false, "message" => "Le type d'intervention est requis."];
            }

            $date = $data['date_intervention'] ?? ($existing['date_intervention'] ?? '');
            if ($date === '') {
                return ["success" => false, "message" => "La date et l'heure de l'intervention sont requises."];
            }

            $duree = isset($data['duree']) ? (int)$data['duree'] : ($existing['duree'] ?? 0);
            if ($duree < 0) {
                return ["success" => false, "message" => "La durée doit être un nombre positif."];
            }

            $niveau = isset($data['niveau_urgence']) ? (int)$data['niveau_urgence'] : ($existing['niveau_urgence'] ?? 0);
            if ($niveau < 1 || $niveau > 5) {
                return ["success" => false, "message" => "Le niveau d'urgence doit être entre 1 et 5."];
            }

            $intervention->setType($data['type'] ?? ($existing['type'] ?? ''))
                         ->setDateIntervention($data['date_intervention'] ?? ($existing['date_intervention'] ?? ''))
                         ->setDuree($data['duree'] ?? ($existing['duree'] ?? 0))
                         ->setNiveauUrgence($data['niveau_urgence'] ?? ($existing['niveau_urgence'] ?? 0))
                         ->setChirurgien($data['chirurgien'] ?? ($existing['chirurgien'] ?? ''))
                         ->setSalle($data['salle'] ?? ($existing['salle'] ?? ''))
                         ->setDescription($data['description'] ?? ($existing['description'] ?? ''));

            if ($intervention->update($id)) {
                return ["success" => true, "message" => "Intervention mise à jour"];
            }
            return ["success" => false, "message" => "Erreur"];
        } catch (Exception $e) {
            error_log("Erreur update intervention: " . $e->getMessage());
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function deleteIntervention($id): array {
        if ($this->user_role !== 'admin') {
            return ["success" => false, "message" => "Accès refusé"];
        }

        try {
            $intervention = new Intervention();
            if ($intervention->delete($id)) {
                return ["success" => true, "message" => "Intervention supprimée"];
            }
            return ["success" => false, "message" => "Erreur"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function searchIntervention($keyword): array {
        try {
            $intervention = new Intervention();
            $results = $intervention->search($keyword);
            // Tous les rôles voient tous les résultats
            return ["success" => true, "data" => array_values($results)];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getInterventionStats(): array {
        if ($this->user_role !== 'admin') {
            return ["success" => false, "message" => "Accès refusé"];
        }

        try {
            $intervention = new Intervention();
            $stats = $intervention->getStatistics();
            return ["success" => true, "data" => $stats];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
