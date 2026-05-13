<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/BlocOperationController.php';

$controller = new BlocOperationController();
$error = null;
$success = null;

// Charger les interventions pour le dropdown
$interventionOptions = $controller->getInterventionOptions();
$interventions = $interventionOptions['data'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->createMateriel($_POST);
    if ($result['success']) {
        $success = $result['message'];
        $_POST = [];
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Matériel - MedChain</title>
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
            <h1><i class="fas fa-plus-circle"></i> Ajouter un Matériel</h1>
        </div>
    </div>

    <div class="container-main">
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $success ?>
            <br><small>Matériel ajouté avec succès ! La carte 3D se met à jour automatiquement...</small>
            <div style=\"margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;\">
                <a href=\"../../../admin_map.html\" target=\"_blank\" class=\"btn btn-primary btn-sm\" style=\"padding: 8px 12px; background: #1D9E75; color: white; text-decoration: none; border-radius: 6px;\">
                    <i class=\"fas fa-cube\"></i> Voir sur la Carte 3D
                </a>
                <a href=\"materiel-index.php\" class=\"btn btn-secondary btn-sm\" style=\"padding: 8px 12px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px;\">
                    <i class=\"fas fa-list\"></i> Retour à la Liste
                </a>
            </div>
        </div>
        <script>
            setTimeout(() => window.location.href = 'materiel-index.php', 4000);
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
                        <label for="id_materiel">ID du Matériel</label>
                        <input type="text" id="id_materiel" name="id_materiel" value="<?= htmlspecialchars($_POST['id_materiel'] ?? '') ?>" placeholder="ex: VEN-001">
                    </div>
                    <div class="form-group">
                        <label for="categorie">Catégorie (ex: wheelchair, bed, ventilator)</label>
                        <select id="categorie" name="categorie">
                            <option value="wheelchair" <?= ($_POST['categorie'] ?? '') === 'wheelchair' ? 'selected' : '' ?>>Fauteuil roulant (wheelchair)</option>
                            <option value="bed" <?= ($_POST['categorie'] ?? '') === 'bed' ? 'selected' : '' ?>>Lit d'hôpital (bed)</option>
                            <option value="ventilator" <?= ($_POST['categorie'] ?? '') === 'ventilator' ? 'selected' : '' ?>>Respirateur (ventilator)</option>
                            <option value="scanner" <?= ($_POST['categorie'] ?? '') === 'scanner' ? 'selected' : '' ?>>Scanner / IRM (scanner)</option>
                            <option value="monitor" <?= ($_POST['categorie'] ?? '') === 'monitor' ? 'selected' : '' ?>>Moniteur Patient (monitor)</option>
                            <option value="defibrillator" <?= ($_POST['categorie'] ?? '') === 'defibrillator' ? 'selected' : '' ?>>Défibrillateur (defibrillator)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_intervention"><i class="fas fa-procedures"></i> Intervention Associée</label>
                    <select id="id_intervention" name="id_intervention">
                        <option value="">-- Aucune intervention --</option>
                        <?php foreach ($interventions as $interv): ?>
                        <option value="<?= $interv['id'] ?>" <?= ($_POST['id_intervention'] ?? '') == $interv['id'] ? 'selected' : '' ?>>
                            #<?= $interv['id'] ?> - <?= htmlspecialchars($interv['type']) ?> (<?= htmlspecialchars($interv['chirurgien']) ?> - <?= date('d/m/Y', strtotime($interv['date_intervention'])) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="bloc">Bloc / Emplacement (Requis pour la carte 3D)</label>
                    <select id="bloc" name="bloc">
                        <option value="" disabled selected>Sélectionner un bloc</option>
                        <option value="ER" <?= ($_POST['bloc'] ?? '') === 'ER' ? 'selected' : '' ?>>Urgences (ER)</option>
                        <option value="ICU" <?= ($_POST['bloc'] ?? '') === 'ICU' ? 'selected' : '' ?>>Soins Intensifs (ICU)</option>
                        <option value="Radiology" <?= ($_POST['bloc'] ?? '') === 'Radiology' ? 'selected' : '' ?>>Radiologie</option>
                        <option value="Lab" <?= ($_POST['bloc'] ?? '') === 'Lab' ? 'selected' : '' ?>>Laboratoire (Lab)</option>
                        <option value="Hall" <?= ($_POST['bloc'] ?? '') === 'Hall' ? 'selected' : '' ?>>Hall principal (Hall)</option>
                        <option value="Storage" <?= ($_POST['bloc'] ?? '') === 'Storage' ? 'selected' : '' ?>>Stockage (Storage)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="disponibilite">Disponibilité</label>
                        <select id="disponibilite" name="disponibilite">
                            <option value="en_stock" <?= ($_POST['disponibilite'] ?? '') === 'en_stock' ? 'selected' : '' ?>>En stock</option>
                            <option value="utilise" <?= ($_POST['disponibilite'] ?? '') === 'utilise' ? 'selected' : '' ?>>Utilisé</option>
                            <option value="indisponible" <?= ($_POST['disponibilite'] ?? '') === 'indisponible' ? 'selected' : '' ?>>Indisponible</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="statutSterilisation">Statut Stérilisation</label>
                        <select id="statutSterilisation" name="statutSterilisation">
                            <option value="sterilise" <?= ($_POST['statutSterilisation'] ?? '') === 'sterilise' ? 'selected' : '' ?>>Stérilisé</option>
                            <option value="non_sterilise" <?= ($_POST['statutSterilisation'] ?? '') === 'non_sterilise' ? 'selected' : '' ?>>Non stérilisé</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombreUtilisationsMax">Nombre d'utilisations max</label>
                        <input type="text" id="nombreUtilisationsMax" name="nombreUtilisationsMax" value="<?= htmlspecialchars($_POST['nombreUtilisationsMax'] ?? 0) ?>">
                    </div>
                    <div class="form-group">
                        <label for="nombreUtilisationsActuelles">Nombre d'utilisations actuelles</label>
                        <input type="text" id="nombreUtilisationsActuelles" name="nombreUtilisationsActuelles" value="<?= htmlspecialchars($_POST['nombreUtilisationsActuelles'] ?? 0) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_utilisation_debut">Date de début d'utilisation (Optionnel)</label>
                        <input type="datetime-local" id="date_utilisation_debut" name="date_utilisation_debut" value="<?= htmlspecialchars($_POST['date_utilisation_debut'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_utilisation_fin">Date de fin d'utilisation (Optionnel)</label>
                        <input type="datetime-local" id="date_utilisation_fin" name="date_utilisation_fin" value="<?= htmlspecialchars($_POST['date_utilisation_fin'] ?? '') ?>">
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Créer le Matériel
                    </button>
                    <a href="materiel-index.php" class="btn-cancel" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const id_materiel = document.getElementById('id_materiel').value.trim();
            const categorie = document.getElementById('categorie').value;
            const bloc = document.getElementById('bloc').value;
            const description = document.getElementById('description').value.trim();
            const disponibilite = document.getElementById('disponibilite').value;
            const statutSterilisation = document.getElementById('statutSterilisation').value;
            const max = parseInt(document.getElementById('nombreUtilisationsMax').value) || 0;
            const actuelles = parseInt(document.getElementById('nombreUtilisationsActuelles').value) || 0;
            const date_debut = document.getElementById('date_utilisation_debut').value;
            const date_fin = document.getElementById('date_utilisation_fin').value;

            let error = '';

            if (id_materiel === '') {
                error = "L'ID du matériel est obligatoire.";
            } else if (categorie === '') {
                error = "Veuillez sélectionner une catégorie.";
            } else if (bloc === '') {
                error = "Veuillez sélectionner un bloc.";
            } else if (description === '') {
                error = "La description est obligatoire.";
            } else if (disponibilite === '') {
                error = "La disponibilité est obligatoire.";
            } else if (statutSterilisation === '') {
                error = "Le statut de stérilisation est obligatoire.";
            } else if (max < 0 || actuelles < 0) {
                error = "Les nombres d'utilisations doivent être positifs.";
            } else if (max > 0 && actuelles > max) {
                error = "Les utilisations actuelles ne peuvent pas dépasser le maximum.";
            } else if (date_debut && date_fin && new Date(date_fin) <= new Date(date_debut)) {
                error = "La date de fin doit être ultérieure à la date de début.";
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

        // Déclencher la mise à jour de la carte 3D après création du matériel
        document.addEventListener('DOMContentLoaded', () => {
            // Vérifier si le formulaire a été soumis avec succès (message de succès affiché)
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                try {
                    // Envoyer un signal localStorage pour rafraîchir la carte 3D
                    localStorage.setItem('materielAdded', new Date().getTime().toString());
                    
                    // Si une fenêtre de carte 3D est ouverte, lui signaler
                    if (window.opener && window.opener.fetchMachines) {
                        window.opener.fetchMachines();
                    }
                } catch (e) {
                    console.log('localStorage non disponible');
                }
            }
        });
    </script>
</body>
</html>
