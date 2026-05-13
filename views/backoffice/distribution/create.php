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
        'responsable' => $_POST['responsable'],
        'face_image' => $_POST['face_image'] ?? ''
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../components/medecin.css">
    <style>
        .form-container { max-width: 600px; margin: 0 auto; background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--navy); }
        input[type="text"], input[type="date"], input[type="number"], select { width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-family: inherit; }
        .error { color: #EF4444; margin-bottom: 20px; padding: 10px; background: #FEF2F2; border-radius: 8px; }
        .face-capture-container { margin-bottom: 25px; text-align: center; border: 2px dashed var(--gray-200); padding: 20px; border-radius: var(--radius-lg); background: #F9FAFB; }
        #video-preview { width: 100%; max-width: 400px; border-radius: 12px; background: #000; display: none; margin: 0 auto 15px; }
        #canvas-capture { display: none; }
        #captured-preview { width: 100%; max-width: 400px; border-radius: 12px; display: none; margin: 0 auto 15px; border: 3px solid var(--green); }
        .btn-capture { background: var(--navy); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-bottom: 10px; transition: all 0.3s; }
        .btn-capture:hover { opacity: 0.9; transform: translateY(-2px); }
        .verify-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 10px; }
        .verify-pending { background: #FEF3C7; color: #92400E; }
        .verify-success { background: #D1FAE5; color: #065F46; }
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

            <!-- Face Verification Section -->
            <div class="face-capture-container">
                <label><i class="fas fa-user-shield"></i> Vérification Faciale Obligatoire</label>
                <div id="verify-status" class="verify-badge verify-pending">Photo requise</div>
                
                <video id="video-preview" autoplay playsinline></video>
                <img id="captured-preview" src="" alt="Capture">
                <canvas id="canvas-capture" width="640" height="480"></canvas>
                
                <input type="hidden" name="face_image" id="face_image">
                
                <div>
                    <button type="button" id="start-camera" class="btn-capture"><i class="fas fa-camera"></i> Activer la caméra</button>
                    <button type="button" id="take-photo" class="btn-capture" style="display:none;"><i class="fas fa-check-circle"></i> Capturer le visage</button>
                    <button type="button" id="retake-photo" class="btn-capture" style="display:none; background:#6B7280;"><i class="fas fa-redo"></i> Recommencer</button>
                </div>
                <small class="error-msg" id="face-error" style="color:red; display:none;"></small>
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

    // Camera Logic
    const video = document.getElementById('video-preview');
    const canvas = document.getElementById('canvas-capture');
    const capturedImg = document.getElementById('captured-preview');
    const faceInput = document.getElementById('face_image');
    const btnStart = document.getElementById('start-camera');
    const btnTake = document.getElementById('take-photo');
    const btnRetake = document.getElementById('retake-photo');
    const statusBadge = document.getElementById('verify-status');

    btnStart.addEventListener('click', async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
            video.srcObject = stream;
            video.style.display = 'block';
            btnStart.style.display = 'none';
            btnTake.style.display = 'inline-block';
            statusBadge.textContent = "Caméra active...";
        } catch (err) {
            console.error(err);
            alert("Impossible d'accéder à la caméra. Vérifiez les permissions.");
        }
    });

    btnTake.addEventListener('click', () => {
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
        
        faceInput.value = dataUrl;
        capturedImg.src = dataUrl;
        
        video.style.display = 'none';
        capturedImg.style.display = 'block';
        btnTake.style.display = 'none';
        btnRetake.style.display = 'inline-block';
        
        statusBadge.textContent = "Visage capturé !";
        statusBadge.className = "verify-badge verify-success";
        
        // Stop stream
        const stream = video.srcObject;
        const tracks = stream.getTracks();
        tracks.forEach(track => track.stop());
    });

    btnRetake.addEventListener('click', () => {
        faceInput.value = "";
        capturedImg.style.display = 'none';
        btnRetake.style.display = 'none';
        btnStart.click();
        statusBadge.textContent = "Photo requise";
        statusBadge.className = "verify-badge verify-pending";
    });

    // Add face validation to submit
    document.getElementById('distForm').addEventListener('submit', function(e) {
        if (faceInput.value === "") {
            document.getElementById('face-error').textContent = "Veuillez capturer votre visage avant de valider.";
            document.getElementById('face-error').style.display = "block";
            e.preventDefault();
        }
    });
    </script>
    </main>
</div>
</body>
</html>
