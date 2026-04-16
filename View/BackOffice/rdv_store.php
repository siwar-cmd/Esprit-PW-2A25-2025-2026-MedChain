<?php
session_start();
require_once '../../Model/rdv.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rdv = new Rdv();
    
    $rdv->dateHeureDebut = $_POST['dateHeureDebut'];
    $rdv->dateHeureFin = $_POST['dateHeureFin'];
    $rdv->statut = 'planifie';
    $rdv->typeConsultation = $_POST['typeConsultation'];
    $rdv->motif = $_POST['motif'];
    
    // Validation simple
    if(empty($rdv->dateHeureDebut) || empty($rdv->dateHeureFin) || empty($rdv->typeConsultation)) {
        header("Location: rdv_create.php?error=missing_fields");
        exit();
    }
    
    $dateDebut = strtotime($rdv->dateHeureDebut);
    $dateFin = strtotime($rdv->dateHeureFin);
    
    if($dateFin <= $dateDebut) {
        header("Location: rdv_create.php?error=date_invalid");
        exit();
    }
    
    if($dateDebut < time()) {
        header("Location: rdv_create.php?error=date_past");
        exit();
    }
    
    if($rdv->create()) {
        header("Location: rdv_list.php?msg=created");
    } else {
        header("Location: rdv_create.php?error=db_error");
    }
}
?>