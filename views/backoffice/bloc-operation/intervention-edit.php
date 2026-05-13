<?php
session_start();

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/BlocOperationController.php';
require_once __DIR__ . '/../../../config.php';

$controller = new BlocOperationController();
$id = $_GET['id'] ?? null;
$materielOptions = [];

if (!$id) {
    $redirect = ($_SESSION['user_role'] === 'admin') ? 'intervention-index.php' : 'medecin-intervention-index.php';
    header("Location: $redirect");
    exit;
}

$interventionData = $controller->getInterventionById($id);
if (!$interventionData['success']) {
    $redirect = ($_SESSION['user_role'] === 'admin') ? 'intervention-index.php' : 'medecin-intervention-index.php';
    header("Location: $redirect");
    exit;
}

$intervention = $interventionData['data'];
$error = null;
$success = null;

// Récupérer les listes
$pdo = config::getConnexion();
$stmt = $pdo->query("SELECT id_utilisateur, nom, prenom, role FROM utilisateur WHERE role IN ('patient', 'medecin') ORDER BY nom ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$materielResult = $controller->getMaterielOptions();
if ($materielResult['success']) {
    $materielOptions = $materielResult['data'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->updateIntervention($id, $_POST);
    if ($result['success']) {
        $success = $result['message'];
        // Recharger les données
        $interventionData = $controller->getInterventionById($id);
        $intervention = $interventionData['data'];
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
    <title>Modifier Intervention - MedChain</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .form-section h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 20px;
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

        .btn-submit, .btn-cancel, .btn-delete {
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

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
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
            <h1><i class="fas fa-edit"></i> Modifier Intervention</h1>
        </div>
    </div>

    <div class="container-main">
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <!-- Informations Générales -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Informations Générales</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="type">Type d'intervention</label>
                            <input type="text" id="type" name="type" value="<?= htmlspecialchars($intervention['type'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="salle">Salle</label>
                            <input type="text" id="salle" name="salle" value="<?= htmlspecialchars($intervention['salle'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?= htmlspecialchars($intervention['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_intervention">Date et Heure</label>
                            <input type="datetime-local" id="date_intervention" name="date_intervention" value="<?= !empty($intervention['date_intervention']) ? htmlspecialchars(substr($intervention['date_intervention'], 0, 16)) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="duree">Durée (minutes)</label>
                            <input type="text" id="duree" name="duree" value="<?= htmlspecialchars($intervention['duree'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="niveau_urgence">Niveau d'urgence (1-5)</label>
                            <select id="niveau_urgence" name="niveau_urgence">
                                <option value="1" <?= ($intervention['niveau_urgence'] ?? '') === '1' ? 'selected' : '' ?>>1 - Faible</option>
                                <option value="2" <?= ($intervention['niveau_urgence'] ?? '') === '2' ? 'selected' : '' ?>>2 - Modérée</option>
                                <option value="3" <?= ($intervention['niveau_urgence'] ?? '') === '3' ? 'selected' : '' ?>>3 - Élevée</option>
                                <option value="4" <?= ($intervention['niveau_urgence'] ?? '') === '4' ? 'selected' : '' ?>>4 - Critique</option>
                                <option value="5" <?= ($intervention['niveau_urgence'] ?? '') === '5' ? 'selected' : '' ?>>5 - Extrême</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="duree">Durée (minutes)</label>
                            <input type="text" id="duree" name="duree" value="<?= htmlspecialchars($intervention['duree'] ?? '') ?>">
                        </div>
                    </div>

                    <?php if ($isAdmin): ?>
                    <div class="form-group">
                        <label for="chirurgien">Chirurgien</label>
                        <select id="chirurgien" name="chirurgien">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($users as $user):
                                if ($user['role'] === 'medecin'):
                                    $fullname = $user['nom'] . ' ' . $user['prenom']; ?>
                                    <option value="<?= htmlspecialchars($fullname) ?>" <?= ($intervention['chirurgien'] ?? '') === $fullname ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($fullname) ?>
                                    </option>
                                <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Résultats et Notes -->
                <div class="form-section">
                    <h3><i class="fas fa-stethoscope"></i> Résultats et Notes</h3>
                    
                    <div class="form-group">
                        <label for="resultat_intervention">Résultat de l'Intervention</label>
                        <textarea id="resultat_intervention" name="resultat_intervention"><?= htmlspecialchars($intervention['resultat_intervention'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes_medecin">Notes du Médecin</label>
                        <textarea id="notes_medecin" name="notes_medecin"><?= htmlspecialchars($intervention['notes_medecin'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="btn-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Enregistrer les Modifications
                    </button>
                    <?php $backUrl = ($_SESSION['user_role'] === 'admin') ? 'intervention-index.php' : 'medecin-intervention-index.php'; ?>
                    <a href="<?= $backUrl ?>" class="btn-cancel" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <?php if ($isAdmin): ?>
                    <button type="button" class="btn-delete" onclick="deleteIntervention(<?= $id ?>)">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function deleteIntervention(id) {
            Swal.fire({
                title: 'Êtes-vous sûr?',
                text: "Cette action est irréversible!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, supprimer!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api-intervention.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id })
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            Swal.fire('Supprimée!', 'L\'intervention a été supprimée.', 'success');
                            <?php $redirectUrl = ($_SESSION['user_role'] === 'admin') ? 'intervention-index.php' : 'medecin-intervention-index.php'; ?>
                            setTimeout(() => window.location.href = '<?= $redirectUrl ?>', 1500);
                        } else {
                            Swal.fire('Erreur!', d.message, 'error');
                        }
                    });
                }
            });
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const type = document.getElementById('type').value.trim();
            const description = document.getElementById('description').value.trim();
            const date_intervention = document.getElementById('date_intervention').value;
            const duree = parseInt(document.getElementById('duree').value) || 0;
            const niveau_urgence = document.getElementById('niveau_urgence').value;
            const salle = document.getElementById('salle').value.trim();
            const resultat_intervention = document.getElementById('resultat_intervention').value.trim();
            const notes_medecin = document.getElementById('notes_medecin').value.trim();
            
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
            } else if (resultat_intervention === '') {
                error = "Le résultat de l'intervention est obligatoire.";
            } else if (notes_medecin === '') {
                error = "Les notes du médecin sont obligatoires.";
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
</body>
</html>
