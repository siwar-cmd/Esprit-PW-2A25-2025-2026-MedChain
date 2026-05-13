<?php
$pdo = new PDO('mysql:host=localhost;dbname=user', 'root', '');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $stmt = $pdo->query("DESCRIBE $table");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
