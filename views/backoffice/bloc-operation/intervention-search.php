<?php
session_start();

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/BlocOperationController.php';

$controller = new BlocOperationController();
$searchTerm = $_POST['search_term'] ?? '';
$results = [];

if (!empty($searchTerm)) {
    $searchData = $controller->searchIntervention($searchTerm);
    $results = $searchData['data'] ?? [];
}

$isAdmin = $_SESSION['user_role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de Recherche - Interventions</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .search-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .result-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid var(--green);
            transition: all 0.3s ease;
        }

        .result-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .result-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 10px;
        }

        .result-meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .result-meta-item {
            color: #6b7280;
        }

        .result-meta-item strong {
            color: #1f2937;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 5px;
        }

        .badge-planifiee { background: #e0e7ff; color: #3730a3; }
        .badge-en_cours { background: #fef3c7; color: #92400e; }
        .badge-terminee { background: #d1fae5; color: #065f46; }
        .badge-annulee { background: #fee2e2; color: #7f1d1d; }

        .btn-group-result {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }

        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: #10b981;
            color: white;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .result-meta {
                grid-template-columns: 1fr;
            }

            .btn-group-result {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-main">
            <h1><i class="fas fa-search"></i> Résultats de Recherche</h1>
        </div>
    </div>

    <div class="container-main">
        <div class="search-info">
            <p style="margin: 0; color: #6b7280;">
                <strong><?= count($results) ?></strong> résultat(s) trouvé(s) pour : <strong><?= htmlspecialchars($searchTerm) ?></strong>
            </p>
        </div>

        <?php if (empty($results)): ?>
            <div class="no-results">
                <i class="fas fa-inbox"></i>
                <h2>Aucun résultat</h2>
                <p>Votre recherche n'a retourné aucune intervention.</p>
                <a href="intervention-index.php" style="color: var(--green); text-decoration: none; font-weight: 600;">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($results as $item): ?>
            <div class="result-card">
                <div class="result-title"><?= htmlspecialchars($item['type'] ?? '-') ?></div>
                
                <div class="result-meta">
                    <div class="result-meta-item">
                        <strong>Chirurgien</strong><br><?= htmlspecialchars($item['chirurgien'] ?? '-') ?>
                    </div>
                    <div class="result-meta-item">
                        <strong>Date</strong><br><?= !empty($item['date_intervention']) ? date('d/m/Y H:i', strtotime($item['date_intervention'])) : '-' ?>
                    </div>
                    <div class="result-meta-item">
                        <strong>Niveau d'urgence</strong><br>
                        <?php
                        $niveaux = [1 => 'Faible', 2 => 'Modérée', 3 => 'Élevée', 4 => 'Critique', 5 => 'Extrême'];
                        $niveau = $item['niveau_urgence'] ?? 0;
                        echo htmlspecialchars($niveaux[$niveau] ?? 'Non défini');
                        ?>
                    </div>
                </div>

                <?php if ($item['description']): ?>
                <p style="color: #6b7280; font-size: 14px; margin: 10px 0;">
                    <?= htmlspecialchars(substr($item['description'], 0, 100)) ?>...
                </p>
                <?php endif; ?>

                <div class="btn-group-result">
                    <a href="intervention-view.php?id=<?= $item['id'] ?>" class="btn-small btn-view">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                    <a href="intervention-edit.php?id=<?= $item['id'] ?>" class="btn-small btn-edit">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <?php if ($isAdmin): ?>
                    <button class="btn-small btn-delete" onclick="deleteIntervention(<?= $item['id'] ?>)">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div style="margin-top: 30px; text-align: center;">
                <a href="intervention-index.php" style="color: var(--green); text-decoration: none; font-weight: 600;">
                    <i class="fas fa-arrow-left"></i> Retour à la liste complète
                </a>
            </div>
        <?php endif; ?>
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
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire('Erreur!', d.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
