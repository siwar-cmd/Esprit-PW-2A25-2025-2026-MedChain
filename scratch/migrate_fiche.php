<?php
require_once __DIR__ . '/../config.php';

try {
    $pdo = config::getConnexion();
    
    $sql = "ALTER TABLE ficherendezvous 
            ADD COLUMN IF NOT EXISTS tensionArterielle VARCHAR(20) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS poids FLOAT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS taille INT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS temperature FLOAT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS examenClinique TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS diagnostic TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS prescription TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS examensComplementaires TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS observations TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS prochainRDV DATE DEFAULT NULL";
            
    $pdo->exec($sql);
    echo "Migration successful: ficherendezvous table updated.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
