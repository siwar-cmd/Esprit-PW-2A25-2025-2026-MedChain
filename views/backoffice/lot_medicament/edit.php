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
    <link rel="stylesheet" href="../components/admin.css">
    <style>
        .form-container { max-width: 600px; margin: 0 auto; background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--navy); }
        input[type="text"], input[type="date"], input[type="number"], textarea { width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-family: inherit; }
        .error { color: #EF4444; margin-bottom: 20px; padding: 10px; background: #FEF2F2; border-radius: 8px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-admin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Modifier un Lot de Médicament</h1>
                <p>Mise à jour des informations du lot</p>
            </div>
        </div>
        <div class="form-container">
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" id="lotForm" novalidate>
            <div class="form-group">
                <label>Nom du Médicament</label>
                <input type="text" name="nom_medicament" id="nom_medicament" value="<?= htmlspecialchars($lot['nom_medicament']) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Type de Médicament</label>
                <input type="text" name="type_medicament" id="type_medicament" placeholder="Ex: Comprimé, Sirop, Injection..." value="<?= htmlspecialchars($lot['type_medicament']) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Date de Fabrication</label>
                <input type="date" name="date_fabrication" id="date_fabrication" value="<?= htmlspecialchars($lot['date_fabrication']) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Date d'Expiration</label>
                <input type="date" name="date_expiration" id="date_expiration" value="<?= htmlspecialchars($lot['date_expiration']) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
            </div>
            <div class="form-group">
                <label>Quantité Initiale (Attention: ne peut être inférieure à la quantité déjà distribuée)</label>
                <input type="number" name="quantite_initial" id="quantite_initial" value="<?= htmlspecialchars($lot['quantite_initial']) ?>">
                <small class="error-msg" style="color:red; display:none;"></small>
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

    <script>
    document.getElementById('lotForm').addEventListener('submit', function(e) {
        let valid = true;
        
        // Hide all errors
        document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

        const nom = document.getElementById('nom_medicament').value.trim();
        if (nom === '') {
            showError('nom_medicament', 'Le nom du médicament est obligatoire.');
            valid = false;
        } else if (nom.length < 3) {
            showError('nom_medicament', 'Le nom doit contenir au moins 3 caractères.');
            valid = false;
        }

        const type = document.getElementById('type_medicament').value.trim();
        if (type === '') {
            showError('type_medicament', 'Le type de médicament est obligatoire.');
            valid = false;
        }

        const dateFab = document.getElementById('date_fabrication').value;
        if (dateFab === '') {
            showError('date_fabrication', 'La date de fabrication est obligatoire.');
            valid = false;
        }

        const dateExp = document.getElementById('date_expiration').value;
        if (dateExp === '') {
            showError('date_expiration', 'La date d\'expiration est obligatoire.');
            valid = false;
        } else if (dateFab !== '' && new Date(dateExp) <= new Date(dateFab)) {
            showError('date_expiration', 'La date d\'expiration doit être postérieure à la date de fabrication.');
            valid = false;
        }

        const quantite = document.getElementById('quantite_initial').value;
        if (quantite === '' || isNaN(quantite) || parseInt(quantite) <= 0) {
            showError('quantite_initial', 'La quantité initiale doit être un nombre positif.');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
        } else {
            // Confirmation avant modification
            e.preventDefault();
            Swal.fire({
                title: 'Confirmer la modification ?',
                text: "Les informations du lot seront mises à jour.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1D9E75',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Oui, enregistrer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('lotForm').submit();
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
