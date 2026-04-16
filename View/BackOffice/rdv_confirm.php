<?php
session_start();
require_once '../../Model/rdv.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$rdv = new Rdv();
$rdv->idRDV = $id;

if($rdv->confirmer()) {
    header("Location: rdv_list.php?msg=confirmed");
} else {
    header("Location: rdv_list.php?error=1");
}
?>