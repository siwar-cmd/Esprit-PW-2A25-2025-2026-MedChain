<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/RendezVous.php';
require_once __DIR__ . '/../models/FicheRendezVous.php';
require_once __DIR__ . '/../models/Utilisateur.php';

/**
 * RendezVousController
 *
 * Routes all go through index.php?controller=rendezvous&action=X
 *
 * Back-office (admin only):  office=back
 * Front-office (patient/medecin): office=front
 *
 * All SQL is in RendezVous / FicheRendezVous static repository methods.
 * This controller is SQL-free.
 */
class RendezVousController extends BaseController
{
    // ══════════════════════════════════════════════════════════════
    //  BACK-OFFICE — Admin
    // ══════════════════════════════════════════════════════════════

    /** GET  back/rendezvous/list — full appointment list */
    public function adminList(): void
    {
        $this->requireAdmin();

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'statut' => trim($_GET['statut'] ?? ''),
        ];

        $result = RendezVous::getAll($filters, 'admin');
        $rdvs   = $result['rdvs'] ?? [];
        $stats  = RendezVous::getStats('admin');
        $medecins = Utilisateur::getMedecins(Database::getInstance());

        $pageTitle  = 'Gestion des Rendez-vous';
        $currentNav = 'rdv-list';
        require VIEWS_BACK . '/rendezvous/admin-index.php';
    }

    /** GET  back/rendezvous/create — show create form */
    public function adminCreate(): void
    {
        $this->requireAdmin();

        $medecins = Utilisateur::getMedecins(Database::getInstance());
        $patients = Utilisateur::getAll(['role' => 'patient'], Database::getInstance());
        $errors   = [];

        $pageTitle  = 'Nouveau Rendez-vous';
        $currentNav = 'rdv-list';
        require VIEWS_BACK . '/rendezvous/admin-create.php';
    }

    /** POST back/rendezvous/store — persist new RDV */
    public function adminStore(): void
    {
        $this->requireAdmin();

        $result = RendezVous::create($_POST);

        if ($result['success']) {
            redirectToRoute('rendezvous', 'list', ['office' => 'back', 'success' => 'created']);
        }

        $medecins = Utilisateur::getMedecins(Database::getInstance());
        $patients = Utilisateur::getAll(['role' => 'patient'], Database::getInstance());
        $errors   = [$result['message']];
        $pageTitle  = 'Nouveau Rendez-vous';
        $currentNav = 'rdv-list';
        require VIEWS_BACK . '/rendezvous/admin-create.php';
    }

    /** GET  back/rendezvous/edit — show edit form */
    public function adminEdit(int $id = 0): void
    {
        $this->requireAdmin();

        $id  = $id ?: (int) ($_GET['id'] ?? 0);
        $rdv = RendezVous::findById($id);

        if (!$rdv) {
            redirectToRoute('rendezvous', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        $medecins = Utilisateur::getMedecins(Database::getInstance());
        $errors   = [];

        $pageTitle  = 'Modifier le Rendez-vous';
        $currentNav = 'rdv-list';
        require VIEWS_BACK . '/rendezvous/admin-edit.php';
    }

    /** POST back/rendezvous/update — persist RDV update */
    public function adminUpdate(int $id = 0): void
    {
        $this->requireAdmin();

        $id     = $id ?: (int) ($_POST['id'] ?? 0);
        $result = RendezVous::update($id, $_POST);

        if ($result['success']) {
            redirectToRoute('rendezvous', 'list', ['office' => 'back', 'success' => 'updated']);
        }

        $rdv      = RendezVous::findById($id);
        $medecins = Utilisateur::getMedecins(Database::getInstance());
        $errors   = [$result['message']];
        $pageTitle  = 'Modifier le Rendez-vous';
        $currentNav = 'rdv-list';
        require VIEWS_BACK . '/rendezvous/admin-edit.php';
    }

    /** POST back/rendezvous/delete */
    public function adminDelete(int $id = 0): void
    {
        $this->requireAdmin();

        $id    = $id ?: (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        $result = RendezVous::delete($id);
        $param  = $result['success'] ? ['success' => 'deleted'] : ['error' => $result['message']];

        redirectToRoute('rendezvous', 'list', array_merge(['office' => 'back'], $param));
    }

    // ══════════════════════════════════════════════════════════════
    //  BACK-OFFICE — Médecin view (admin sidebar)
    // ══════════════════════════════════════════════════════════════

    public function medecinList(): void
    {
        $this->requireAdmin(); // médecin views accessed via back-office by admin

        $medecinId = (int) ($_GET['medecin_id'] ?? 0);
        $filters   = ['search' => trim($_GET['search'] ?? ''), 'statut' => trim($_GET['statut'] ?? '')];

        $result = RendezVous::getAll($filters, 'medecin', $medecinId ?: null);
        $rdvs   = $result['rdvs'] ?? [];

        $pageTitle  = 'RDV par Médecin';
        $currentNav = 'rdv-list';
        require VIEWS_BACK . '/rendezvous/medecin-index.php';
    }

    public function medecinEdit(int $id = 0): void
    {
        $this->requireAdmin();

        $id  = $id ?: (int) ($_GET['id'] ?? 0);
        $rdv = RendezVous::findById($id);

        if (!$rdv) {
            redirectToRoute('rendezvous', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        $medecins = Utilisateur::getMedecins(Database::getInstance());
        $errors   = [];

        $pageTitle  = 'Modifier le RDV (Médecin)';
        $currentNav = 'rdv-list';
        require VIEWS_BACK . '/rendezvous/medecin-edit.php';
    }

    // ══════════════════════════════════════════════════════════════
    //  FRONT-OFFICE — Patient / Médecin
    // ══════════════════════════════════════════════════════════════

    /** GET  front/rendezvous/list — user's own appointments */
    public function list(): void
    {
        $this->requireAuth();

        $userId  = $this->currentUserId();
        $role    = $this->currentUserRole();
        $filters = ['search' => trim($_GET['search'] ?? ''), 'statut' => trim($_GET['statut'] ?? '')];

        $result = RendezVous::getAll($filters, $role, $userId);
        $rdvs   = $result['rdvs'] ?? [];
        $stats  = RendezVous::getStats($role, $userId);

        $pageTitle  = 'Mes Rendez-vous';
        $currentNav = 'rdv';
        require VIEWS_FRONT . '/rendezvous/index.php';
    }

    /** GET  front/rendezvous/create */
    public function create(): void
    {
        $this->requireAuth();

        $medecins = Utilisateur::getMedecins(Database::getInstance());
        $errors   = [];

        $pageTitle  = 'Prendre un Rendez-vous';
        $currentNav = 'rdv';
        require VIEWS_FRONT . '/rendezvous/create.php';
    }

    /** POST front/rendezvous/store */
    public function store(): void
    {
        $this->requireAuth();

        $data             = $_POST;
        $data['idClient'] = $this->currentUserId();

        $result = RendezVous::create($data);

        if ($result['success']) {
            redirectToRoute('rendezvous', 'list', ['office' => 'front', 'success' => 'created']);
        }

        $medecins = Utilisateur::getMedecins(Database::getInstance());
        $errors   = [$result['message']];
        $pageTitle  = 'Prendre un Rendez-vous';
        $currentNav = 'rdv';
        require VIEWS_FRONT . '/rendezvous/create.php';
    }

    /** GET  front/rendezvous/edit */
    public function edit(int $id = 0): void
    {
        $this->requireAuth();

        $id  = $id ?: (int) ($_GET['id'] ?? 0);
        $rdv = RendezVous::findById($id);

        // Ownership check: patient can only edit their own
        if (!$rdv || ($this->currentUserRole() === 'patient' && (int) $rdv['idClient'] !== $this->currentUserId())) {
            redirectToRoute('rendezvous', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        $medecins = Utilisateur::getMedecins(Database::getInstance());
        $errors   = [];

        $pageTitle  = 'Modifier mon Rendez-vous';
        $currentNav = 'rdv';
        require VIEWS_FRONT . '/rendezvous/edit.php';
    }

    /** POST front/rendezvous/update */
    public function update(int $id = 0): void
    {
        $this->requireAuth();

        $id  = $id ?: (int) ($_POST['id'] ?? 0);
        $rdv = RendezVous::findById($id);

        if (!$rdv || ($this->currentUserRole() === 'patient' && (int) $rdv['idClient'] !== $this->currentUserId())) {
            redirectToRoute('rendezvous', 'list', ['office' => 'front', 'error' => 'forbidden']);
        }

        $result = RendezVous::update($id, $_POST);

        if ($result['success']) {
            redirectToRoute('rendezvous', 'list', ['office' => 'front', 'success' => 'updated']);
        }

        $rdv      = RendezVous::findById($id);
        $medecins = Utilisateur::getMedecins(Database::getInstance());
        $errors   = [$result['message']];
        $pageTitle  = 'Modifier mon Rendez-vous';
        $currentNav = 'rdv';
        require VIEWS_FRONT . '/rendezvous/edit.php';
    }

    /** POST front/rendezvous/delete */
    public function delete(int $id = 0): void
    {
        $this->requireAuth();

        $id  = $id ?: (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        $rdv = RendezVous::findById($id);

        if (!$rdv || ($this->currentUserRole() === 'patient' && (int) $rdv['idClient'] !== $this->currentUserId())) {
            redirectToRoute('rendezvous', 'list', ['office' => 'front', 'error' => 'forbidden']);
        }

        $result = RendezVous::delete($id);
        $param  = $result['success'] ? ['success' => 'deleted'] : ['error' => $result['message']];
        redirectToRoute('rendezvous', 'list', array_merge(['office' => 'front'], $param));
    }
}
