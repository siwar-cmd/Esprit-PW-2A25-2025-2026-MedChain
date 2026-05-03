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
    <link rel="stylesheet" href="../components/medecin.css">
    <style>
        .form-container { max-width: 600px; margin: 0 auto; background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--navy); }
        input[type="text"], input[type="date"], input[type="number"], select { width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-family: inherit; }
        .error { color: #EF4444; margin-bottom: 20px; padding: 10px; background: #FEF2F2; border-radius: 8px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Ajouter une Distribution</h1>
                <p>Création d'une nouvelle distribution de médicament</p>
            </div>
        </div>
        <div class="form-container">
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" id="distForm" novalidate>
            <div class="form-group">
                <label>Lot de Médicament</label>
                <select name="id_lot" id="id_lot">
                    <option value="">Sélectionnez un lot</option>
                    <?php foreach ($lots as $lot): ?>
                        <option value="<?= $lot['id_lot'] ?>" <?= (isset($_POST['id_lot']) && $_POST['id_lot'] == $lot['id_lot']) ? 'selected' : '' ?> data-restant="<?= $lot['quantite_restante'] ?>">
                            <?= htmlspecialchars($lot['nom_medicament']) ?> (Restant: <?= $lot['quantite_restante'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Date de Distribution</label>
                <input type="date" name="date_distribution" id="date_distribution" value="<?= htmlspecialchars($_POST['date_distribution'] ?? date('Y-m-d')) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Quantité Distribuée</label>
                <input type="number" name="quantite_distribuee" id="quantite_distribuee" value="<?= htmlspecialchars($_POST['quantite_distribuee'] ?? '') ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Patient</label>
                <input type="text" name="patient" id="patient" value="<?= htmlspecialchars($_POST['patient'] ?? '') ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Responsable</label>
                <input type="text" name="responsable" id="responsable" value="<?= htmlspecialchars($_POST['responsable'] ?? ($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'])) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div style="display:flex; gap: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="medecin-index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('distForm').addEventListener('submit', function(e) {
        let valid = true;
        
        document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

        const lotSelect = document.getElementById('id_lot');
        if (lotSelect.value === '') {
            showError('id_lot', 'Veuillez sélectionner un lot de médicament.');
            valid = false;
        }

        const dateDist = document.getElementById('date_distribution').value;
        if (dateDist === '') {
            showError('date_distribution', 'La date de distribution est obligatoire.');
            valid = false;
        }

        const quantite = document.getElementById('quantite_distribuee').value;
        const maxRestant = lotSelect.options[lotSelect.selectedIndex]?.getAttribute('data-restant');
        if (quantite === '' || isNaN(quantite) || parseInt(quantite) <= 0) {
            showError('quantite_distribuee', 'La quantité distribuée doit être un nombre positif.');
            valid = false;
        } else if (lotSelect.value !== '' && parseInt(quantite) > parseInt(maxRestant)) {
            showError('quantite_distribuee', 'La quantité dépasse le stock disponible (' + maxRestant + ').');
            valid = false;
        }

        const patient = document.getElementById('patient').value.trim();
        if (patient === '') {
            showError('patient', 'Le nom du patient est obligatoire.');
            valid = false;
        } else if (patient.length < 3) {
            showError('patient', 'Le nom du patient doit contenir au moins 3 caractères.');
            valid = false;
        }

        const responsable = document.getElementById('responsable').value.trim();
        if (responsable === '') {
            showError('responsable', 'Le nom du responsable est obligatoire.');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
        }
    });

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorEl = field.nextElementSibling;
        errorEl.textContent = message;
        errorEl.style.display = 'block';
    }
    </script>
        </form>
        </div>
    </main>
</div>
</body>
</html>
