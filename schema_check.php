<?php
$pdo = new PDO('mysql:host=localhost;dbname=user', 'root', '');
$stmt = $pdo->query("DESCRIBE materiel");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($columns);
