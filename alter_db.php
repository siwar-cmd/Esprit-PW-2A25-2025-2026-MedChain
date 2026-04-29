<?php
require_once __DIR__ . '/config.php';
$pdo = config::getConnexion();
$queries = [
    "ALTER TABLE ficherendezvous ADD COLUMN antecedents VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE ficherendezvous ADD COLUMN allergies VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE ficherendezvous ADD COLUMN motifPrincipal VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE ficherendezvous ADD COLUMN modeConsultation VARCHAR(50) DEFAULT 'Présentiel'",
    "ALTER TABLE ficherendezvous ADD COLUMN statutPaiement VARCHAR(50) DEFAULT 'En attente'"
];
foreach ($queries as $q) {
    try {
        $pdo->exec($q);
        echo "Success: $q\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
