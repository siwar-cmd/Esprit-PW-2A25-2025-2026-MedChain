<?php
require_once 'Model/ficherdv.php';
require_once 'Model/rdv.php';

class FicherdvController {
    private $ficheRdv;
    private $rdv;

    public function __construct() {
        $this->ficheRdv = new FicheRdv();
        $this->rdv = new Rdv();
    }

    // Afficher la liste des fiches
    public function index() {
        $stmt = $this->ficheRdv->readAll();
        include 'View/FrontOffice/ficherdv/list.php';
    }

    // Afficher le formulaire de création
    public function create() {
        // Récupérer la liste des rendez-vous pour le select
        $stmt = $this->rdv->readAll();
        include 'View/FrontOffice/ficherdv/create.php';
    }

    // Enregistrer une nouvelle fiche
    public function store() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->ficheRdv->idRDV = $_POST['idRDV'];
            $this->ficheRdv->dateGeneration = date('Y-m-d');
            $this->ficheRdv->piecesAApporter = $_POST['piecesAApporter'];
            $this->ficheRdv->consignesAvantConsultation = $_POST['consignesAvantConsultation'];
            $this->ficheRdv->tarifConsultation = $_POST['tarifConsultation'];
            $this->ficheRdv->modeRemboursement = $_POST['modeRemboursement'];
            $this->ficheRdv->emailEnvoye = isset($_POST['emailEnvoye']) ? 1 : 0;
            $this->ficheRdv->calendrierAjoute = isset($_POST['calendrierAjoute']) ? 1 : 0;

            if($this->ficheRdv->create()) {
                header("Location: index.php?page=ficherdv&action=index&msg=created");
            } else {
                header("Location: index.php?page=ficherdv&action=create&error=1");
            }
        }
    }

    // Afficher les détails d'une fiche
    public function show($id) {
        $this->ficheRdv->idFiche = $id;
        $fiche = $this->ficheRdv->readOne();
        if($fiche) {
            include 'View/FrontOffice/ficherdv/view.php';
        } else {
            header("Location: index.php?page=ficherdv&action=index&error=notfound");
        }
    }

    // Afficher le formulaire d'édition
    public function edit($id) {
        $this->ficheRdv->idFiche = $id;
        if($this->ficheRdv->readOne()) {
            include 'View/FrontOffice/ficherdv/edit.php';
        } else {
            header("Location: index.php?page=ficherdv&action=index&error=notfound");
        }
    }

    // Mettre à jour une fiche
    public function update($id) {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->ficheRdv->idFiche = $id;
            $this->ficheRdv->piecesAApporter = $_POST['piecesAApporter'];
            $this->ficheRdv->consignesAvantConsultation = $_POST['consignesAvantConsultation'];
            $this->ficheRdv->tarifConsultation = $_POST['tarifConsultation'];
            $this->ficheRdv->modeRemboursement = $_POST['modeRemboursement'];

            if($this->ficheRdv->update()) {
                header("Location: index.php?page=ficherdv&action=index&msg=updated");
            } else {
                header("Location: index.php?page=ficherdv&action=edit&id=$id&error=1");
            }
        }
    }

    // Supprimer une fiche
    public function delete($id) {
        $this->ficheRdv->idFiche = $id;
        if($this->ficheRdv->delete()) {
            header("Location: index.php?page=ficherdv&action=index&msg=deleted");
        } else {
            header("Location: index.php?page=ficherdv&action=index&error=1");
        }
    }

    // Générer PDF
    public function generatePDF($id) {
        $this->ficheRdv->idFiche = $id;
        if($this->ficheRdv->genererPDF()) {
            header("Location: index.php?page=ficherdv&action=index&msg=pdf_generated");
        } else {
            header("Location: index.php?page=ficherdv&action=index&error=1");
        }
    }

    // Envoyer par email
    public function sendEmail($id) {
        $this->ficheRdv->idFiche = $id;
        if($this->ficheRdv->envoyerParEmail()) {
            header("Location: index.php?page=ficherdv&action=index&msg=email_sent");
        } else {
            header("Location: index.php?page=ficherdv&action=index&error=1");
        }
    }

    // Ajouter au calendrier
    public function addToCalendar($id) {
        $this->ficheRdv->idFiche = $id;
        if($this->ficheRdv->ajouterAuCalendrier()) {
            header("Location: index.php?page=ficherdv&action=index&msg=calendar_added");
        } else {
            header("Location: index.php?page=ficherdv&action=index&error=1");
        }
    }
}
?>