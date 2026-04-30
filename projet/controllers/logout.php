<?php

session_start();

echo "<!-- Début du processus de déconnexion -->";


echo "<!-- Avant déconnexion - Session ID: " . session_id() . " -->";
echo "<!-- Données session avant: ";
print_r($_SESSION);
echo " -->";


$_SESSION = array();


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}


session_destroy();

echo "<!-- Session détruite, redirection en cours -->";


header("Location: ../views/frontoffice/home/index.php");
exit;
?>