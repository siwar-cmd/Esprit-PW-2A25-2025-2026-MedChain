<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Ambulance.php';
require_once __DIR__ . '/../models/Mission.php';

/**
 * AmbulanceMissionController
 *
 * Back-office ONLY (admin).  No front-office routes for this module.
 * All SQL is delegated to Ambulance and Mission static repository methods.
 *
 * IMPORTANT: The old "USE sante" database switch has been removed.
 * Ambulance and mission tables now live in the unified `medchain` database.
 */
class AmbulanceMissionController extends BaseController
{
    // ══════════════════════════════════════════════════════════════
    //  AMBULANCE — CRUD
    // ══════════════════════════════════════════════════════════════

    /** GET back/ambulance/list */
    public function list(): void
    {
        $this->requireAdmin();

        $filters = [
            'search'     => trim($_GET['search']     ?? ''),
            'disponible' => trim($_GET['disponible'] ?? ''),
            'statut'     => trim($_GET['statut']     ?? ''),
        ];

        $result     = Ambulance::getAll($filters);
        $ambulances = $result['data'] ?? [];
        $stats      = Ambulance::getStats();
        $mStats     = Mission::getStats();

        $pageTitle  = 'Gestion des Ambulances';
        $currentNav = 'ambulance-list';
        require VIEWS_BACK . '/ambulance/list.php';
    }

    /** GET back/ambulance/create */
    public function createAmbulance(): void
    {
        $this->requireAdmin();
        $errors = [];

        $pageTitle  = 'Ajouter une Ambulance';
        $currentNav = 'ambulance-list';
        require VIEWS_BACK . '/ambulance/create.php';
    }

    /** POST back/ambulance/store */
    public function storeAmbulance(): void
    {
        $this->requireAdmin();

        $result = Ambulance::create($_POST);

        if ($result['success']) {
            redirectToRoute('ambulance', 'list', ['office' => 'back', 'success' => 'amb_created']);
        }

        $errors = [$result['message']];
        $pageTitle  = 'Ajouter une Ambulance';
        $currentNav = 'ambulance-list';
        require VIEWS_BACK . '/ambulance/create.php';
    }

    /** GET back/ambulance/edit */
    public function editAmbulance(): void
    {
        $this->requireAdmin();

        $id        = (int) ($_GET['id'] ?? 0);
        $ambulance = Ambulance::findById($id);

        if (!$ambulance) {
            redirectToRoute('ambulance', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        $errors = [];

        $pageTitle  = 'Modifier l\'Ambulance';
        $currentNav = 'ambulance-list';
        require VIEWS_BACK . '/ambulance/edit.php';
    }

    /** POST back/ambulance/update */
    public function updateAmbulance(): void
    {
        $this->requireAdmin();

        $id     = (int) ($_POST['id'] ?? 0);
        $result = Ambulance::update($id, $_POST);

        if ($result['success']) {
            redirectToRoute('ambulance', 'list', ['office' => 'back', 'success' => 'amb_updated']);
        }

        $ambulance = Ambulance::findById($id);
        $errors    = [$result['message']];
        $pageTitle  = 'Modifier l\'Ambulance';
        $currentNav = 'ambulance-list';
        require VIEWS_BACK . '/ambulance/edit.php';
    }

    /** POST back/ambulance/delete */
    public function deleteAmbulance(): void
    {
        $this->requireAdmin();

        $id     = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        $result = Ambulance::delete($id);
        $param  = $result['success'] ? ['success' => 'amb_deleted'] : ['error' => $result['message']];

        redirectToRoute('ambulance', 'list', array_merge(['office' => 'back'], $param));
    }

    // ══════════════════════════════════════════════════════════════
    //  MISSION — CRUD
    // ══════════════════════════════════════════════════════════════

    /** GET back/ambulance/missions */
    public function missions(): void
    {
        $this->requireAdmin();

        $filters = [
            'search'      => trim($_GET['search']      ?? ''),
            'estTerminee' => trim($_GET['estTerminee'] ?? ''),
            'typeMission' => trim($_GET['typeMission'] ?? ''),
            'idAmbulance' => trim($_GET['idAmbulance'] ?? ''),
        ];

        $result   = Mission::getAll($filters);
        $missions = $result['data'] ?? [];
        $stats    = Mission::getStats();

        $pageTitle  = 'Gestion des Missions';
        $currentNav = 'missions';
        require VIEWS_BACK . '/ambulance/missions.php';
    }

    /** GET back/ambulance/createMission */
    public function createMission(): void
    {
        $this->requireAdmin();

        $ambulances = Ambulance::forSelect();
        $errors     = [];

        $pageTitle  = 'Nouvelle Mission';
        $currentNav = 'missions';
        require VIEWS_BACK . '/ambulance/mission-create.php';
    }

    /** POST back/ambulance/storeMission */
    public function storeMission(): void
    {
        $this->requireAdmin();

        $result = Mission::create($_POST);

        if ($result['success']) {
            redirectToRoute('ambulance', 'missions', ['office' => 'back', 'success' => 'mission_created']);
        }

        $ambulances = Ambulance::forSelect();
        $errors     = [$result['message']];
        $pageTitle  = 'Nouvelle Mission';
        $currentNav = 'missions';
        require VIEWS_BACK . '/ambulance/mission-create.php';
    }

    /** GET back/ambulance/editMission */
    public function editMission(): void
    {
        $this->requireAdmin();

        $id      = (int) ($_GET['id'] ?? 0);
        $mission = Mission::findById($id);

        if (!$mission) {
            redirectToRoute('ambulance', 'missions', ['office' => 'back', 'error' => 'not_found']);
        }

        $ambulances = Ambulance::forSelect();
        $errors     = [];

        $pageTitle  = 'Modifier la Mission';
        $currentNav = 'missions';
        require VIEWS_BACK . '/ambulance/mission-edit.php';
    }

    /** POST back/ambulance/updateMission */
    public function updateMission(): void
    {
        $this->requireAdmin();

        $id     = (int) ($_POST['id'] ?? 0);
        $result = Mission::update($id, $_POST);

        if ($result['success']) {
            redirectToRoute('ambulance', 'missions', ['office' => 'back', 'success' => 'mission_updated']);
        }

        $mission    = Mission::findById($id);
        $ambulances = Ambulance::forSelect();
        $errors     = [$result['message']];
        $pageTitle  = 'Modifier la Mission';
        $currentNav = 'missions';
        require VIEWS_BACK . '/ambulance/mission-edit.php';
    }

    /** POST back/ambulance/deleteMission */
    public function deleteMission(): void
    {
        $this->requireAdmin();

        $id     = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        $result = Mission::delete($id);
        $param  = $result['success'] ? ['success' => 'mission_deleted'] : ['error' => $result['message']];

        redirectToRoute('ambulance', 'missions', array_merge(['office' => 'back'], $param));
    }
}
