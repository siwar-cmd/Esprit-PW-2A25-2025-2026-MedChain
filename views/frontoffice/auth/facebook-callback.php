<?php
/**
 * facebook-callback.php
 * Chemin : projet/views/frontoffice/auth/facebook-callback.php
 * 
 * Reçoit le code d'autorisation de Facebook et finalise la connexion.
 */
require_once __DIR__ . '/../../../controllers/OAuthController.php';

$oauthController = new OAuthController();

// Cas d'erreur renvoyée par Facebook
if (isset($_GET['error']) || isset($_GET['error_code'])) {
    $errorMsg = urlencode('Connexion Facebook annulée ou refusée.');
    header('Location: login.php?oauth_error=' . $errorMsg);
    exit;
}

// Paramètres attendus
$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (empty($code) || empty($state)) {
    header('Location: login.php?oauth_error=' . urlencode('Paramètres manquants.'));
    exit;
}

// Traitement OAuth
$result = $oauthController->handleFacebookCallback($code, $state);

if ($result['success']) {
    $role = $_SESSION['user_role'] ?? 'patient';
    if ($role === 'admin') {
        header('Location: ../../backoffice/admin-dashboard.php');
    } else {
        header('Location: profile.php?oauth_success=1&provider=facebook');
    }
    exit;
} else {
    $errorMsg = urlencode($result['message'] ?? 'Erreur lors de la connexion Facebook.');
    header('Location: login.php?oauth_error=' . $errorMsg);
    exit;
}