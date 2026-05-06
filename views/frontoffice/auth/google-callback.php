<?php
/**
 * google-callback.php
 * Chemin : projet/views/frontoffice/auth/google-callback.php
 */
session_start();
require_once __DIR__ . '/../../../controllers/OAuthController.php';

$oauthController = new OAuthController();

if (isset($_GET['error'])) {
    $errorMsg = urlencode('Connexion Google annulée ou refusée.');
    header('Location: login.php?oauth_error=' . $errorMsg);
    exit;
}

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (empty($code) || empty($state)) {
    header('Location: login.php?oauth_error=' . urlencode('Paramètres manquants.'));
    exit;
}

$result = $oauthController->handleGoogleCallback($code, $state);

if ($result['success']) {
    $role = $_SESSION['user_role'] ?? 'patient';
    if ($role === 'admin') {
        header('Location: ../../backoffice/admin-dashboard.php');
    } else {
        header('Location: profile.php?oauth_success=1&provider=google');
    }
    exit;
} else {
    $errorMsg = urlencode($result['message'] ?? 'Erreur lors de la connexion Google.');
    header('Location: login.php?oauth_error=' . $errorMsg);
    exit;
}