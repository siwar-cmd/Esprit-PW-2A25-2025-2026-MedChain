<?php
require_once __DIR__ . '/../../../controller/controller_lot.php';

$controller = new controller_lot();
$lots = $controller->afficher();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste Médicaments</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
            text-align: center;
        }

        h2 {
            margin-top: 30px;
        }

        table {
            margin: 30px auto;
            border-collapse: collapse;
            width: 90%;
            background: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background: #007bff;
            color: white;
        }
    </style>
</head>

<body>

<h2>💊 Liste des Médicaments</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Type</th>
        <th>Date Fabrication</th>
        <th>Date Expiration</th>
        <th>Quantité</th>
        <th>Description</th>
    </tr>

    <?php foreach ($lots as $lot) { ?>
    <tr>
        <td><?= $lot['id_lot'] ?></td>
        <td><?= $lot['nom_medicament'] ?></td>
        <td><?= $lot['type_medicament'] ?></td>
        <td><?= $lot['date_fabrication'] ?></td>
        <td><?= $lot['date_expiration'] ?></td>
        <td><?= $lot['quantite_restante'] ?></td>
        <td><?= $lot['description'] ?></td>
    </tr>
    <?php } ?>

</table>

</body>
</html>