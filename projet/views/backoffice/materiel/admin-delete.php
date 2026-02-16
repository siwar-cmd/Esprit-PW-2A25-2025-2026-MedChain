<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header('Location: ../../frontoffice/auth/login.php'); exit; }
require_once __DIR__ . '/../../../controllers/MaterielController.php';

$id = $_GET['id'] ?? null;
if (!$id) { 
    $_SESSION['error_message'] = 'ID matériel manquant';
    header('Location: admin-index.php'); 
    exit; 
}

try {
    $ctrl = new MaterielController();
    $result = $ctrl->deleteMateriel($id);
    $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Erreur: ' . $e->getMessage();
}

header('Location: admin-index.php');
exit;
