<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Médicaments Sensibles</title>

    <style>
        body {
            font-family: Arial;
            background: linear-gradient(to right, #1e3c72, #2a5298);
            color: white;
            text-align: center;
            padding-top: 100px;
        }

        h1 {
            margin-bottom: 50px;
        }

        .btn {
            display: inline-block;
            margin: 20px;
            padding: 15px 30px;
            background: orange;
            color: black;
            text-decoration: none;
            font-size: 18px;
            border-radius: 10px;
            transition: 0.3s;
        }

        .btn:hover {
            background: darkorange;
        }

        .btn2 {
            background: #28a745;
        }

        .btn3 {
            background: #007bff;
        }
    </style>
</head>
<body>

    <h1>💊 Gestion des Médicaments Sensibles</h1>

    <!-- 🔹 CRUD LOT -->
    <a class="btn btn2" href="view/backoffice/php/crud_lot.php">
        💊 Gestion des Lots
    </a>
    <!-- 🔹 FRONT OFFICE VIEW -->
<a class="btn btn" href="view/frontoffice/php/afficher_medicaments.php">
    👁️ Afficher Médicaments
</a>

    <!-- 🔹 CRUD DISTRIBUTION -->
    <a class="btn btn3" href="view/backoffice/php/crud_distribution.php">
        📦 Gestion des Distributions
    </a>

</body>
</html>