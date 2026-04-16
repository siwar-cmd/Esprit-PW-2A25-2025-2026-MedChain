<?php
require_once 'Model/rdv.php';

class RdvController {
    private $rdv;

    public function __construct() {
        $this->rdv = new Rdv();
    }

    // Afficher la liste des rendez-vous
    public function index() {
        $stmt = $this->rdv->readAll();
        include 'View/FrontOffice/rdv/list.php';
    }

    // Afficher le formulaire de création
    public function create() {
        include 'View/FrontOffice/rdv/create.php';
    }

    // Enregistrer un nouveau rendez-vous avec validation PHP
    public function store() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Validation PHP côté serveur
            $errors = array();
            
            // Vérifier les champs obligatoires
            if(empty($_POST['dateHeureDebut'])) {
                $errors[] = 'date_debut_required';
            }
            if(empty($_POST['dateHeureFin'])) {
                $errors[] = 'date_fin_required';
            }
            if(empty($_POST['typeConsultation'])) {
                $errors[] = 'type_required';
            }
            
            // Valider le format de la date
            if(!empty($_POST['dateHeureDebut'])) {
                $dateDebut = strtotime($_POST['dateHeureDebut']);
                if($dateDebut === false) {
                    $errors[] = 'date_debut_invalid_format';
                }
            }
            
            if(!empty($_POST['dateHeureFin'])) {
                $dateFin = strtotime($_POST['dateHeureFin']);
                if($dateFin === false) {
                    $errors[] = 'date_fin_invalid_format';
                }
            }
            
            // Vérifier que la date de fin est après la date de début
            if(!empty($_POST['dateHeureDebut']) && !empty($_POST['dateHeureFin'])) {
                $dateDebut = strtotime($_POST['dateHeureDebut']);
                $dateFin = strtotime($_POST['dateHeureFin']);
                
                if($dateDebut !== false && $dateFin !== false) {
                    if($dateFin <= $dateDebut) {
                        $errors[] = 'date_invalid_order';
                    }
                    
                    // Vérifier que la date n'est pas dans le passé
                    $now = time();
                    if($dateDebut < $now) {
                        $errors[] = 'date_past';
                    }
                }
            }
            
            // Si des erreurs, rediriger avec les erreurs
            if(!empty($errors)) {
                $error_string = implode(',', $errors);
                header("Location: index.php?page=rdv&action=create&error=" . urlencode($error_string));
                exit();
            }
            
            // Tout est valide, on crée le rendez-vous
            $this->rdv->dateHeureDebut = $_POST['dateHeureDebut'];
            $this->rdv->dateHeureFin = $_POST['dateHeureFin'];
            $this->rdv->statut = 'planifie';
            $this->rdv->typeConsultation = $_POST['typeConsultation'];
            $this->rdv->motif = $_POST['motif'];

            if($this->rdv->create()) {
                header("Location: index.php?page=rdv&action=index&msg=created");
            } else {
                header("Location: index.php?page=rdv&action=create&error=db_error");
            }
        }
    }

    // Afficher les détails d'un rendez-vous
    public function show($id) {
        $this->rdv->idRDV = $id;
        if($this->rdv->readOne()) {
            include 'View/FrontOffice/rdv/view.php';
        } else {
            header("Location: index.php?page=rdv&action=index&error=notfound");
        }
    }

    // Afficher le formulaire d'édition
    public function edit($id) {
        $this->rdv->idRDV = $id;
        if($this->rdv->readOne()) {
            include 'View/FrontOffice/rdv/edit.php';
        } else {
            header("Location: index.php?page=rdv&action=index&error=notfound");
        }
    }

    // Mettre à jour un rendez-vous avec validation PHP
    public function update($id) {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Validation PHP côté serveur
            $errors = array();
            
            if(empty($_POST['dateHeureDebut'])) {
                $errors[] = 'date_debut_required';
            }
            if(empty($_POST['dateHeureFin'])) {
                $errors[] = 'date_fin_required';
            }
            if(empty($_POST['typeConsultation'])) {
                $errors[] = 'type_required';
            }
            
            if(!empty($_POST['dateHeureDebut']) && !empty($_POST['dateHeureFin'])) {
                $dateDebut = strtotime($_POST['dateHeureDebut']);
                $dateFin = strtotime($_POST['dateHeureFin']);
                
                if($dateDebut !== false && $dateFin !== false) {
                    if($dateFin <= $dateDebut) {
                        $errors[] = 'date_invalid_order';
                    }
                }
            }
            
            if(!empty($errors)) {
                $error_string = implode(',', $errors);
                header("Location: index.php?page=rdv&action=edit&id=$id&error=" . urlencode($error_string));
                exit();
            }
            
            $this->rdv->idRDV = $id;
            $this->rdv->dateHeureDebut = $_POST['dateHeureDebut'];
            $this->rdv->dateHeureFin = $_POST['dateHeureFin'];
            $this->rdv->typeConsultation = $_POST['typeConsultation'];
            $this->rdv->motif = $_POST['motif'];

            if($this->rdv->update()) {
                header("Location: index.php?page=rdv&action=index&msg=updated");
            } else {
                header("Location: index.php?page=rdv&action=edit&id=$id&error=db_error");
            }
        }
    }

    // Supprimer un rendez-vous
    public function delete($id) {
        $this->rdv->idRDV = $id;
        if($this->rdv->delete()) {
            header("Location: index.php?page=rdv&action=index&msg=deleted");
        } else {
            header("Location: index.php?page=rdv&action=index&error=1");
        }
    }

    // Confirmer un rendez-vous
    public function confirm($id) {
        $this->rdv->idRDV = $id;
        if($this->rdv->confirmer()) {
            header("Location: index.php?page=rdv&action=index&msg=confirmed");
        } else {
            header("Location: index.php?page=rdv&action=index&error=1");
        }
    }

    // Afficher le formulaire d'annulation
    public function cancelForm($id) {
        $this->rdv->idRDV = $id;
        if($this->rdv->readOne()) {
            include 'View/FrontOffice/rdv/cancel.php';
        } else {
            header("Location: index.php?page=rdv&action=index&error=notfound");
        }
    }

    // Annuler un rendez-vous avec validation PHP
    public function cancel($id) {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Validation du motif d'annulation
            if(empty($_POST['motif_annulation']) || trim($_POST['motif_annulation']) == '') {
                header("Location: index.php?page=rdv&action=cancel&id=$id&error=motif_required");
                exit();
            }
            
            $motif = $_POST['motif_annulation'];
            $this->rdv->idRDV = $id;
            if($this->rdv->annuler($motif)) {
                header("Location: index.php?page=rdv&action=index&msg=cancelled");
            } else {
                header("Location: index.php?page=rdv&action=index&error=1");
            }
        }
    }

    // Afficher le formulaire de report
    public function postponeForm($id) {
        $this->rdv->idRDV = $id;
        if($this->rdv->readOne()) {
            include 'View/FrontOffice/rdv/postpone.php';
        } else {
            header("Location: index.php?page=rdv&action=index&error=notfound");
        }
    }

    // Reporter un rendez-vous avec validation PHP
    public function postpone($id) {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Validation de la nouvelle date
            if(empty($_POST['nouvelleDate'])) {
                header("Location: index.php?page=rdv&action=postpone&id=$id&error=date_required");
                exit();
            }
            
            $nouvelleDate = $_POST['nouvelleDate'];
            $dateTimestamp = strtotime($nouvelleDate);
            
            if($dateTimestamp === false) {
                header("Location: index.php?page=rdv&action=postpone&id=$id&error=date_invalid_format");
                exit();
            }
            
            $now = time();
            if($dateTimestamp < $now) {
                header("Location: index.php?page=rdv&action=postpone&id=$id&error=date_past");
                exit();
            }
            
            $this->rdv->idRDV = $id;
            if($this->rdv->reporter($nouvelleDate)) {
                header("Location: index.php?page=rdv&action=index&msg=postponed");
            } else {
                header("Location: index.php?page=rdv&action=index&error=1");
            }
        }
    }
}
?>