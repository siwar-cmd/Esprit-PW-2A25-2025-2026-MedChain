<?php
require_once __DIR__ . '/../models/Pret.php';

class PretController {
    private $pret;

    public function __construct() {
        $this->pret = new Pret();
    }

    public function index() {
        // Redirect to standalone prets.php
        header('Location: prets.php');
        exit;
    }

    public function create() {
        // Redirect to standalone pret_form.php
        header('Location: pret_form.php');
        exit;
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id_objet' => $_POST['id_objet'] ?? 0,
                'nom_patient' => $_POST['nom_patient'] ?? '',
                'date_pret' => $_POST['date_pret'] ?? date('Y-m-d'),
                'date_retour_prevue' => $_POST['date_retour_prevue'] ?? ''
            ];
            
            if ($this->pret->creerPret($data)) {
                header('Location: prets.php?success=created');
                exit;
            } else {
                header('Location: prets.php?error=create_failed');
                exit;
            }
        }
    }

    public function confirm($id) {
        if ($this->pret->confirmerPret($id)) {
            header('Location: prets.php?success=confirmed');
        } else {
            header('Location: prets.php?error=confirm_failed');
        }
        exit;
    }

    public function cancel($id) {
        if ($this->pret->annulerPret($id)) {
            header('Location: prets.php?success=cancelled');
        } else {
            header('Location: prets.php?error=cancel_failed');
        }
        exit;
    }

    public function return($id) {
        if ($this->pret->retournerPret($id)) {
            header('Location: prets.php?success=returned');
        } else {
            header('Location: prets.php?error=return_failed');
        }
        exit;
    }
    
    public function show($id) {
        // Redirect to standalone prets.php with show action
        header("Location: prets.php?action=show&id=$id");
        exit;
    }
}
