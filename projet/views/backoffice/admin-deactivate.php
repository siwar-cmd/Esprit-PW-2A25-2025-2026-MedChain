<?php

require_once __DIR__ . '/../../controllers/AdminController.php';

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-users.php');
    exit;
}

$user_id = $_POST['user_id'] ?? null;
if (!$user_id) {
    $_SESSION['error_message'] = 'ID utilisateur manquant';
    header('Location: admin-users.php');
    exit;
}

$adminController = new AdminController();
$result = $adminController->manageUsers('delete', null, $user_id);

if ($result['success']) {
    $_SESSION['success_message'] = $result['message'];
} else {
    $_SESSION['error_message'] = $result['message'];
}

header('Location: admin-users.php');
exit;