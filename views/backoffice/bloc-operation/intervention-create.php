<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/BlocOperationController.php';
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../models/Utilisateur.php';

$controller = new BlocOperationController();
$pdo = config::getConnexion();
$error = null;
$success = null;
$materielOptions = [];

// Récupérer la liste des patients et médecins
$stmt = $pdo->query("SELECT id_utilisateur, nom, prenom FROM utilisateur WHERE role IN ('patient', 'medecin') ORDER BY nom ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$patients = array_filter($users, function($u) {
    $stmt = $GLOBALS['pdo']->prepare("SELECT role FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$u['id_utilisateur']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['role'] === 'patient';
});

$medecins = array_filter($users, function($u) {
    $stmt = $GLOBALS['pdo']->prepare("SELECT role FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$u['id_utilisateur']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['role'] === 'medecin';
});

$materielResult = $controller->getMaterielOptions();
if ($materielResult['success']) {
    $materielOptions = $materielResult['data'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->createIntervention($_POST);
    if ($result['success']) {
        $success = $result['message'];
        $_POST = [];
    } else {
        $error = $result['message'];
    }
}

$isAdmin = $_SESSION['user_role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer Intervention - MedChain</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
        }
        
        body {
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .container-main {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            color: var(--green);
            margin-bottom: 8px;
            display: block;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input:focus, textarea:focus, select:focus {
            border-color: var(--green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(29, 158, 117, 0.1);
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn-submit, .btn-cancel {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 158, 117, 0.3);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-main">
            <h1><i class="fas fa-plus-circle"></i> Créer une Intervention</h1>
        </div>
    </div>

    <div class="container-main">
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $success ?>
            <br><small>Redirection en cours...</small>
        </div>
        <script>
            setTimeout(() => window.location.href = 'intervention-index.php', 2000);
        </script>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Type d'intervention</label>
                        <input type="text" id="type" name="type" value="<?= htmlspecialchars($_POST['type'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="salle">Salle</label>
                        <input type="text" id="salle" name="salle" value="<?= htmlspecialchars($_POST['salle'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_intervention">Date et Heure</label>
                        <input type="datetime-local" id="date_intervention" name="date_intervention" value="<?= htmlspecialchars($_POST['date_intervention'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="duree">Durée (minutes)</label>
                        <input type="text" id="duree" name="duree" value="<?= htmlspecialchars($_POST['duree'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="niveau_urgence">Niveau d'urgence (1-5)</label>
                        <select id="niveau_urgence" name="niveau_urgence">
                            <option value="1" <?= ($_POST['niveau_urgence'] ?? '') === '1' ? 'selected' : '' ?>>1 - Faible</option>
                            <option value="2" <?= ($_POST['niveau_urgence'] ?? '') === '2' ? 'selected' : '' ?>>2 - Modérée</option>
                            <option value="3" <?= ($_POST['niveau_urgence'] ?? '') === '3' ? 'selected' : '' ?>>3 - Élevée</option>
                            <option value="4" <?= ($_POST['niveau_urgence'] ?? '') === '4' ? 'selected' : '' ?>>4 - Critique</option>
                            <option value="5" <?= ($_POST['niveau_urgence'] ?? '') === '5' ? 'selected' : '' ?>>5 - Extrême</option>
                        </select>
                    </div>
                </div>

                <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label for="chirurgien">Chirurgien</label>
                    <select id="chirurgien" name="chirurgien">
                        <option value="">-- Sélectionner un chirurgien --</option>
                        <?php foreach ($medecins as $medecin): ?>
                        <option value="<?= htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']) ?>" <?= ($_POST['chirurgien'] ?? '') === ($medecin['nom'] . ' ' . $medecin['prenom']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="btn-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Créer l'Intervention
                    </button>
                    <a href="intervention-index.php" class="btn-cancel" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const type = document.getElementById('type').value.trim();
            const description = document.getElementById('description').value.trim();
            const date_intervention = document.getElementById('date_intervention').value;
            const duree = parseInt(document.getElementById('duree').value) || 0;
            const niveau_urgence = document.getElementById('niveau_urgence').value;
            const salle = document.getElementById('salle').value.trim();
            
            const chirurgienEl = document.getElementById('chirurgien');
            const chirurgien = chirurgienEl ? chirurgienEl.value : '<?= $_SESSION['user_prenom'] ?? "" ?> <?= $_SESSION['user_nom'] ?? "" ?>';

            let error = '';

            if (type === '') {
                error = "Le type d'intervention est obligatoire.";
            } else if (description === '') {
                error = "La description est obligatoire.";
            } else if (date_intervention === '') {
                error = "La date et l'heure sont obligatoires.";
            } else if (duree <= 0) {
                error = "La durée doit être un nombre positif (en minutes).";
            } else if (niveau_urgence === '') {
                error = "Le niveau d'urgence est obligatoire.";
            } else if (salle === '') {
                error = "La salle est obligatoire.";
            } else if (chirurgien.trim() === '') {
                error = "Veuillez sélectionner un chirurgien.";
            }

            if (error !== '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur de saisie',
                    text: error,
                    confirmButtonColor: '#1D9E75'
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
