<?php
session_start();
require_once '../../Model/rdv.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    
    $rdv = new Rdv();
    $rdv->idRDV = $id;
    $rdv->dateHeureDebut = $_POST['dateHeureDebut'];
    $rdv->dateHeureFin = $_POST['dateHeureFin'];
    $rdv->typeConsultation = $_POST['typeConsultation'];
    $rdv->motif = $_POST['motif'];
    
    if($rdv->update()) {
        header("Location: rdv_list.php?msg=updated");
    } else {
        header("Location: rdv_edit.php?id=$id&error=db_error");
    }
}
?>