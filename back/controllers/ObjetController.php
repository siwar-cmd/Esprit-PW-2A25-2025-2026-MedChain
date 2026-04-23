<?php
require_once __DIR__ . '/../models/ObjetLoisir.php';

class ObjetController {
    private $objetLoisir;

    public function __construct() {
        $this->objetLoisir = new ObjetLoisir();
    }

    public function index() {
        // Redirect to standalone objets.php
        header('Location: objets.php');
        exit;
    }

    public function create() {
        // Redirect to standalone objet_form.php
        header('Location: objet_form.php');
        exit;
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom_objet' => $_POST['nom_objet'] ?? '',
                'type_objet' => $_POST['type_objet'] ?? '',
                'quantite' => $_POST['quantite'] ?? 0,
                'etat' => $_POST['etat'] ?? '',
                'description' => $_POST['description'] ?? ''
            ];
            
            if ($this->objetLoisir->ajouterObjet($data)) {
                header('Location: objets.php?success=added');
                exit;
            } else {
                header('Location: objets.php?error=add_failed');
                exit;
            }
        }
    }

    public function edit($id) {
        // Redirect to standalone objet_form.php with ID
        header("Location: objet_form.php?id=$id");
        exit;
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom_objet' => $_POST['nom_objet'] ?? '',
                'type_objet' => $_POST['type_objet'] ?? '',
                'quantite' => $_POST['quantite'] ?? 0,
                'etat' => $_POST['etat'] ?? '',
                'description' => $_POST['description'] ?? ''
            ];
            
            if ($this->objetLoisir->modifierObjet($id, $data)) {
                header('Location: objets.php?success=updated');
                exit;
            } else {
                header('Location: objets.php?error=update_failed');
                exit;
            }
        }
    }

    public function delete($id) {
        if ($this->objetLoisir->supprimerObjet($id)) {
            header('Location: objets.php?success=deleted');
        } else {
            header('Location: objets.php?error=delete_failed');
        }
        exit;
    }
    
    public function show($id) {
        // Redirect to standalone objets.php with show action
        header("Location: objets.php?action=show&id=$id");
        exit;
    }
}
