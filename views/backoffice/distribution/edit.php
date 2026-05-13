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

if ($distribution['statut'] !== 'En attente') {
    $_SESSION['error_message'] = "Impossible de modifier une distribution déjà traitée (" . $distribution['statut'] . ").";
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
    <link rel="stylesheet" href="../components/medecin.css">
    <style>
        .form-container { max-width: 600px; margin: 0 auto; background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--navy); }
        input[type="text"], input[type="date"], input[type="number"], select { width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-family: inherit; }
        .readonly-field { background-color: #F3F4F6; cursor: not-allowed; }
        .error { color: #EF4444; margin-bottom: 20px; padding: 10px; background: #FEF2F2; border-radius: 8px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Modifier une Distribution</h1>
                <p>Mise à jour des informations</p>
            </div>
        </div>
        <div class="form-container">
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" id="distForm" novalidate>
            <div class="form-group">
                <label>Lot de Médicament (Non modifiable)</label>
                <input type="text" class="readonly-field" readonly value="<?= htmlspecialchars($distribution['nom_medicament']) ?>">
            </div>
            <div class="form-group">
                <label>Date de Distribution</label>
                <input type="date" name="date_distribution" id="date_distribution" value="<?= htmlspecialchars($distribution['date_distribution']) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Quantité Distribuée</label>
                <input type="number" name="quantite_distribuee" id="quantite_distribuee" data-max="<?= $availableQuantity ?>" value="<?= htmlspecialchars($distribution['quantite_distribuee']) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
                <small style="color:#6B7280; display:block; margin-top:5px;">Quantité maximum autorisée (Restant + actuelle) : <?= $availableQuantity ?></small>
            </div>
            <div class="form-group">
                <label>Patient</label>
                <input type="text" name="patient" id="patient" value="<?= htmlspecialchars($distribution['patient']) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Responsable</label>
                <input type="text" name="responsable" id="responsable" value="<?= htmlspecialchars($distribution['responsable']) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div style="display:flex; gap: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                <a href="medecin-index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annuler</a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('distForm').addEventListener('submit', function(e) {
        let valid = true;
        
        document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

        const dateDist = document.getElementById('date_distribution').value;
        if (dateDist === '') {
            showError('date_distribution', 'La date de distribution est obligatoire.');
            valid = false;
        }

        const quantiteInput = document.getElementById('quantite_distribuee');
        const quantite = quantiteInput.value;
        const maxRestant = quantiteInput.getAttribute('data-max');
        if (quantite === '' || isNaN(quantite) || parseInt(quantite) <= 0) {
            showError('quantite_distribuee', 'La quantité distribuée doit être un nombre positif.');
            valid = false;
        } else if (parseInt(quantite) > parseInt(maxRestant)) {
            showError('quantite_distribuee', 'La quantité dépasse le stock maximum autorisé (' + maxRestant + ').');
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
        } else {
            // Confirmation avant modification
            e.preventDefault();
            Swal.fire({
                title: 'Confirmer la modification ?',
                text: "Les informations de distribution seront mises à jour.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1D9E75',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Oui, enregistrer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('distForm').submit();
                }
            });
        }
    });

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorEl = field.nextElementSibling;
        errorEl.textContent = message;
        errorEl.style.display = 'block';
    }

    <?php if ($error): ?>
    Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: '<?= addslashes($error) ?>'
    });
    <?php endif; ?>
    </script>
        </form>
        </div>
    </main>
</div>
</body>
</html>
