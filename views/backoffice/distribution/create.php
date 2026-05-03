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

// Fetch lots for the select dropdown
$lotsData = $lotController->getAllLotMedicaments();
$lots = $lotsData['success'] ? $lotsData['lots'] : [];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id_lot' => $_POST['id_lot'],
        'date_distribution' => $_POST['date_distribution'],
        'quantite_distribuee' => $_POST['quantite_distribuee'],
        'patient' => $_POST['patient'],
        'responsable' => $_POST['responsable']
    ];
    
    $result = $distController->createDistribution($data);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: medecin-index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvelle Distribution - Médecin</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Ajouter une Distribution</h1>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Lot de Médicament</label>
                <select name="id_lot" required>
                    <option value="">Sélectionnez un lot</option>
                    <?php foreach ($lots as $lot): ?>
                        <option value="<?= $lot['id_lot'] ?>" <?= (isset($_POST['id_lot']) && $_POST['id_lot'] == $lot['id_lot']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lot['nom_medicament']) ?> (Restant: <?= $lot['quantite_restante'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Date de Distribution</label>
                <input type="date" name="date_distribution" required value="<?= htmlspecialchars($_POST['date_distribution'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label>Quantité Distribuée</label>
                <input type="number" name="quantite_distribuee" required min="1" value="<?= htmlspecialchars($_POST['quantite_distribuee'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Patient</label>
                <input type="text" name="patient" required value="<?= htmlspecialchars($_POST['patient'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Responsable</label>
                <input type="text" name="responsable" required value="<?= htmlspecialchars($_POST['responsable'] ?? ($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'])) ?>">
            </div>
            <div style="display:flex; gap: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="medecin-index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </form>
    </div>
</body>
</html>
