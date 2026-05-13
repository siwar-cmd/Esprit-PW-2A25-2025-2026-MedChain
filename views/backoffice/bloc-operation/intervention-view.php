<?php
session_start();

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/BlocOperationController.php';

$controller = new BlocOperationController();
$id = $_GET['id'] ?? null;

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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Intervention - MedChain</title>
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

        .detail-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .detail-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-section:last-child {
            border-bottom: none;
        }

        .detail-section h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 20px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 20px;
        }

        .detail-item h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .detail-item p {
            font-size: 16px;
            color: #1f2937;
            margin: 0;
            font-weight: 500;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-planifiee { background: #e0e7ff; color: #3730a3; }
        .badge-en_cours { background: #fef3c7; color: #92400e; }
        .badge-terminee { background: #d1fae5; color: #065f46; }
        .badge-annulee { background: #fee2e2; color: #7f1d1d; }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-action {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 158, 117, 0.3);
            color: white;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background: #5a6268;
            color: white;
        }

        .text-content {
            color: #4b5563;
            line-height: 1.6;
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
        }

        @media (max-width: 768px) {
            .detail-row {
                grid-template-columns: 1fr;
                gap: 20px;
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
            <h1><i class="fas fa-info-circle"></i> Détails Intervention</h1>
        </div>
    </div>

    <div class="container-main">
        <div class="detail-card">
            <!-- Informations Générales -->
            <div class="detail-section">
                <h3><i class="fas fa-info-circle"></i> Informations Générales</h3>
                
                <div class="detail-row">
                    <div class="detail-item">
                        <h4>Type</h4>
                        <p><?= htmlspecialchars($intervention['type'] ?? '-') ?></p>
                    </div>
                    <div class="detail-item">
                        <h4>Chirurgien</h4>
                        <p><?= htmlspecialchars($intervention['chirurgien'] ?? '-') ?></p>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-item">
                        <h4>Date de l'Intervention</h4>
                        <p><?= !empty($intervention['date_intervention']) ? date('d/m/Y H:i', strtotime($intervention['date_intervention'])) : '-' ?></p>
                    </div>
                    <div class="detail-item">
                        <h4>Durée</h4>
                        <p><?= htmlspecialchars($intervention['duree'] ?? '-') ?> minutes</p>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-item">
                        <h4>Niveau d'urgence</h4>
                        <p>
                            <?php
                            $niveaux = [1 => 'Faible', 2 => 'Modérée', 3 => 'Élevée', 4 => 'Critique', 5 => 'Extrême'];
                            $niveau = $intervention['niveau_urgence'] ?? 0;
                            echo htmlspecialchars($niveaux[$niveau] ?? 'Non défini');
                            ?>
                        </p>
                    </div>
                    <div class="detail-item">
                        <h4>Salle</h4>
                        <p><?= htmlspecialchars($intervention['salle'] ?? '-') ?></p>
                    </div>
                </div>

                <?php if (!empty($intervention['description'])): ?>
                <div style="margin-top: 15px;">
                    <h4 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 8px; font-weight: 600;">Description</h4>
                    <div class="text-content">
                        <?= htmlspecialchars($intervention['description']) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Patient -->
            <div class="detail-section">
                <h3><i class="fas fa-user-injured"></i> Patient</h3>
                
                <div class="detail-item">
                    <h4>Patient</h4>
                    <?php $patientAssigned = !empty($intervention['patient_id']); ?>
                    <p><?= $patientAssigned ? 'Assigné' : 'Non assigné' ?></p>
                </div>
            </div>

            <!-- Résultats et Notes -->
            <?php if (!empty($intervention['resultat_intervention']) || !empty($intervention['notes_medecin'])): ?>
            <div class="detail-section">
                <h3><i class="fas fa-stethoscope"></i> Résultats et Notes</h3>
                
                <?php if (!empty($intervention['resultat_intervention'])): ?>
                <div style="margin-bottom: 20px;">
                    <h4 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 8px; font-weight: 600;">Résultat de l'Intervention</h4>
                    <div class="text-content">
                        <?= htmlspecialchars($intervention['resultat_intervention'] ?? '') ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($intervention['notes_medecin'])): ?>
                <div>
                    <h4 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 8px; font-weight: 600;">Notes du Médecin</h4>
                    <div class="text-content">
                        <?= htmlspecialchars($intervention['notes_medecin'] ?? '') ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Métadonnées -->
            <div class="detail-section">
                <h3><i class="fas fa-clock"></i> Métadonnées</h3>
                
                <div class="detail-row">
                    <div class="detail-item">
                        <h4>Créé le</h4>
                        <p><?= !empty($intervention['created_at']) ? date('d/m/Y à H:i', strtotime($intervention['created_at'])) : '-' ?></p>
                    </div>
                    <div class="detail-item">
                        <h4>Dernier modification</h4>
                        <p><?= !empty($intervention['updated_at']) ? date('d/m/Y à H:i', strtotime($intervention['updated_at'])) : '-' ?></p>
                    </div>
                </div>
            </div>
            <div class="btn-group">
                <?php $editId = $intervention['id'] ?? null; ?>
                <?php if ($editId && in_array($_SESSION['user_role'], ['admin', 'medecin'])): ?>
                <a href="intervention-edit.php?id=<?= htmlspecialchars($editId) ?>" class="btn-action btn-edit">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <?php endif; ?>
                <?php $backUrl = ($_SESSION['user_role'] === 'admin') ? 'intervention-index.php' : 'medecin-intervention-index.php'; ?>
                <a href="<?= $backUrl ?>" class="btn-action btn-back">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
