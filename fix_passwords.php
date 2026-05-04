<?php
require_once __DIR__ . '/config.php';
$db = config::getConnexion();
$hash = password_hash('password', PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE utilisateur SET mot_de_passe = :hash WHERE email IN ('admin@medchain.tn', 'patient@medchain.tn', 'medecin@medchain.tn')");
$stmt->execute([':hash' => $hash]);
echo "Updated " . $stmt->rowCount() . " users with hash: " . $hash . "\n";

// Verify
$check = $db->query("SELECT id_utilisateur, email, role, mot_de_passe FROM utilisateur");
foreach ($check->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $ok = password_verify('password', $row['mot_de_passe']) ? 'OK' : 'FAIL';
    echo "  {$row['email']} ({$row['role']}) — verify: {$ok}\n";
}
