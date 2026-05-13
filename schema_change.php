<?php
$pdo = new PDO('mysql:host=localhost;dbname=user', 'root', '');
$pdo->exec('ALTER TABLE materiel CHANGE nom id_materiel VARCHAR(100) NULL;');
echo "OK\n";
