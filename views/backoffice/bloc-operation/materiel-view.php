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
    header('Location: materiel-index.php');
    exit;
}

$materielData = $controller->getMaterielById($id);
if (!$materielData['success']) {
    header('Location: materiel-index.php');
    exit;
}

$materiel = $materielData['data'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Matériel - MedChain</title>
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

        .detail-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-item h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .detail-item p {
            font-size: 18px;
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

        .badge-disponible {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-en_maintenance {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-hors_service {
            background: #fee2e2;
            color: #7f1d1d;
        }

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
            <h1><i class="fas fa-info-circle"></i> Détails Matériel</h1>
        </div>
    </div>

    <div class="container-main">
        <div class="detail-card">
            <div class="detail-row">
                <div class="detail-item">
                    <h3>ID Matériel</h3>
                    <p><?= htmlspecialchars($materiel['id_materiel']) ?></p>
                </div>
                <div class="detail-item">
                    <h3>Catégorie</h3>
                    <p><?= htmlspecialchars($materiel['categorie'] ?? '-') ?></p>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-item">
                    <h3>Disponibilité</h3>
                    <p><?= htmlspecialchars($materiel['disponibilite'] ?? '-') ?></p>
                </div>
                <div class="detail-item">
                    <h3>Statut Stérilisation</h3>
                    <p><?= htmlspecialchars($materiel['statutSterilisation'] ?? '-') ?></p>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-item">
                    <h3>Intervention Associée</h3>
                    <?php if (!empty($materiel['id_intervention'])): ?>
                        <p>
                            <span style="background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 16px; font-size: 14px; font-weight: 600;">
                                #<?= $materiel['id_intervention'] ?> - <?= htmlspecialchars($materiel['intervention_type'] ?? 'N/A') ?>
                            </span>
                        </p>
                        <?php if (!empty($materiel['intervention_chirurgien'])): ?>
                        <p style="margin-top: 8px; color: #6b7280; font-size: 14px;">
                            <i class="fas fa-user-md"></i> <?= htmlspecialchars($materiel['intervention_chirurgien']) ?>
                        </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color: #9ca3af;">Aucune intervention associée</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-item">
                    <h3>Utilisations (max)</h3>
                    <p><?= htmlspecialchars($materiel['nombreUtilisationsMax'] ?? 0) ?></p>
                </div>
                <div class="detail-item">
                    <h3>Utilisations (actuelles)</h3>
                    <p><?= htmlspecialchars($materiel['nombreUtilisationsActuelles'] ?? 0) ?></p>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-item">
                    <h3>Créé le</h3>
                    <p><?= !empty($materiel['created_at']) ? date('d/m/Y à H:i', strtotime($materiel['created_at'])) : '-' ?></p>
                </div>
            </div>

            <?php if (!empty($materiel['description'])): ?>
            <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #e5e7eb;">
                <h3 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 15px; font-weight: 600;">Description</h3>
                <p style="color: #4b5563; line-height: 1.6;">
                    <?= htmlspecialchars($materiel['description']) ?>
                </p>
            </div>
            <?php endif; ?>

            <div class="btn-group">
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="materiel-edit.php?id=<?= $materiel['idMateriel'] ?? '' ?>" class="btn-action btn-edit">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                <?php endif; ?>
                <?php $retourUrl = 'materiel-index.php'; ?>
                <a href="<?= $retourUrl ?>" class="btn-action btn-back">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
