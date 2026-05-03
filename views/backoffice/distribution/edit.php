<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/DistributionController.php';
require_once __DIR__ . '/../../../controllers/LotMedicamentController.php';

$distController = new DistributionController();
$lotController = new LotMedicamentController();

if (!isset($_GET['id'])) {
    header('Location: medecin-index.php');
    exit;
}

$id = $_GET['id'];
$distribution = $distController->getDistributionById($id);

if (!$distribution) {
    header('Location: medecin-index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'date_distribution' => $_POST['date_distribution'],
        'quantite_distribuee' => $_POST['quantite_distribuee'],
        'patient' => $_POST['patient'],
        'responsable' => $_POST['responsable']
    ];
    
    $result = $distController->updateDistribution($id, $data);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: medecin-index.php');
        exit;
    } else {
        $error = $result['message'];
        $distribution = array_merge($distribution, $data);
    }
}

// Fetch the specific lot to show its info
$lot = $lotController->getLotMedicamentById($distribution['id_lot']);
$availableQuantity = $lot['quantite_restante'] + $distribution['quantite_distribuee'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Distribution - Médecin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green: #1D9E75;
            --navy: #1E3A52;
            --gray-200: #E5E7EB;
            --white: #ffffff;
            --radius-md: 12px;
            --radius-lg: 20px;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        h1 { color: var(--navy); margin-bottom: 20px; font-family: 'Syne', sans-serif; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--navy); }
        input[type="text"], input[type="date"], input[type="number"], select { width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-family: inherit; }
        .btn { padding: 12px 24px; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; color: white; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--green); }
        .btn-secondary { background: #6B7280; }
        .error { color: #EF4444; margin-bottom: 20px; padding: 10px; background: #FEF2F2; border-radius: 8px; }
        .readonly-field { background: #F3F4F6; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Modifier une Distribution</h1>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Lot de Médicament (Non modifiable)</label>
                <input type="text" class="readonly-field" readonly value="<?= htmlspecialchars($distribution['nom_medicament']) ?>">
            </div>
            <div class="form-group">
                <label>Date de Distribution</label>
                <input type="date" name="date_distribution" required value="<?= htmlspecialchars($distribution['date_distribution']) ?>">
            </div>
            <div class="form-group">
                <label>Quantité Distribuée</label>
                <input type="number" name="quantite_distribuee" required min="1" max="<?= $availableQuantity ?>" value="<?= htmlspecialchars($distribution['quantite_distribuee']) ?>">
                <small style="color:#6B7280; display:block; margin-top:5px;">Quantité maximum autorisée (Restant + actuelle) : <?= $availableQuantity ?></small>
            </div>
            <div class="form-group">
                <label>Patient</label>
                <input type="text" name="patient" required value="<?= htmlspecialchars($distribution['patient']) ?>">
            </div>
            <div class="form-group">
                <label>Responsable</label>
                <input type="text" name="responsable" required value="<?= htmlspecialchars($distribution['responsable']) ?>">
            </div>
            <div style="display:flex; gap: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                <a href="medecin-index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>
