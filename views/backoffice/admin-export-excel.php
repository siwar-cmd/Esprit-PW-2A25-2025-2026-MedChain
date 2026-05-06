<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();
$filters = [];
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (!empty($_GET['role'])) {
    $filters['role'] = $_GET['role'];
}
if (!empty($_GET['statut'])) {
    $filters['statut'] = $_GET['statut'];
}
$adminController->exportUsersToExcel($filters);
?>