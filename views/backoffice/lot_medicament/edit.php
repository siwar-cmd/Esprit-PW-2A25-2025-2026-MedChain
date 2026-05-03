<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/LotMedicamentController.php';

$lotController = new LotMedicamentController();
$error = '';

if (!isset($_GET['id'])) {
    header('Location: admin-index.php');
    exit;
}

$id = $_GET['id'];
$lot = $lotController->getLotMedicamentById($id);

if (!$lot) {
    header('Location: admin-index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom_medicament' => $_POST['nom_medicament'],
        'type_medicament' => $_POST['type_medicament'],
        'date_fabrication' => $_POST['date_fabrication'],
        'date_expiration' => $_POST['date_expiration'],
        'quantite_initial' => $_POST['quantite_initial'],
        'description' => $_POST['description'] ?? ''
    ];
    
    $result = $lotController->updateLotMedicament($id, $data);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: admin-index.php');
        exit;
    } else {
        $error = $result['message'];
        // restore data on error
        $lot = array_merge($lot, $data);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Lot - Admin</title>
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
        input[type="text"], input[type="date"], input[type="number"], textarea { width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-family: inherit; }
        .btn { padding: 12px 24px; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; color: white; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--green); }
        .btn-secondary { background: #6B7280; }
        .error { color: #EF4444; margin-bottom: 20px; padding: 10px; background: #FEF2F2; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Modifier un Lot de Médicament</h1>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nom du Médicament</label>
                <input type="text" name="nom_medicament" required value="<?= htmlspecialchars($lot['nom_medicament']) ?>">
            </div>
            <div class="form-group">
                <label>Type de Médicament</label>
                <input type="text" name="type_medicament" required value="<?= htmlspecialchars($lot['type_medicament']) ?>">
            </div>
            <div class="form-group">
                <label>Date de Fabrication</label>
                <input type="date" name="date_fabrication" required value="<?= htmlspecialchars($lot['date_fabrication']) ?>">
            </div>
            <div class="form-group">
                <label>Date d'Expiration</label>
                <input type="date" name="date_expiration" required value="<?= htmlspecialchars($lot['date_expiration']) ?>">
            </div>
            <div class="form-group">
                <label>Quantité Initiale</label>
                <input type="number" name="quantite_initial" required min="1" value="<?= htmlspecialchars($lot['quantite_initial']) ?>">
                <small style="color:var(--gray-500); display:block; margin-top:5px;">Quantité restante actuellement calculée: <?= $lot['quantite_restante'] ?? $lot['quantite_initial'] ?></small>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($lot['description']) ?></textarea>
            </div>
            <div style="display:flex; gap: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                <a href="admin-index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>
