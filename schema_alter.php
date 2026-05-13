<?php
require_once __DIR__ . '/config.php';
$pdo = config::getConnexion();
try {
    $pdo->exec("ALTER TABLE materiel ADD COLUMN date_utilisation_debut DATETIME NULL");
    $pdo->exec("ALTER TABLE materiel ADD COLUMN date_utilisation_fin DATETIME NULL");
    echo "Columns added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
