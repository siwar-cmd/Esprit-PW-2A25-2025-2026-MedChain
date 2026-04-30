<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once "../../../controllers/controller_lot.php";

$c = new LotController();

$errors = [];
$edit = null;

// ================= EDIT =================
if (isset($_GET['edit'])) {
    $edit = $c->getById($_GET['edit']);
}

// ================= DELETE =================
if (isset($_GET['delete'])) {
    $c->delete($_GET['delete']);
    $_SESSION['success_message'] = "Lot supprimé avec succès.";
    header("Location: crud_lot.php");
    exit;
}

// ================= VALIDATION =================
function validate($d, &$errors) {
    $ok = true;

    if (empty($d['nom_medicament'])) {
        $errors['nom_medicament'] = "Nom du médicament obligatoire";
        $ok = false;
    }

    if (empty($d['type_medicament'])) {
        $errors['type_medicament'] = "Type du médicament obligatoire";
        $ok = false;
    }

    if (empty($d['date_fabrication'])) {
        $errors['date_fabrication'] = "Date de fabrication obligatoire";
        $ok = false;
    }

    if (empty($d['date_expiration'])) {
        $errors['date_expiration'] = "Date d'expiration obligatoire";
        $ok = false;
    }

    if (!empty($d['date_fabrication']) && !empty($d['date_expiration'])) {
        if ($d['date_fabrication'] >= $d['date_expiration']) {
            $errors['date_expiration'] = "La date d'expiration doit être postérieure à la date de fabrication";
            $ok = false;
        }
    }

    if ($d['quantite_initial'] === '' || !is_numeric($d['quantite_initial']) || $d['quantite_initial'] < 0) {
        $errors['quantite_initial'] = "Quantité initiale invalide";
        $ok = false;
    }

    // ✅ نتحقق من quantite_restante كان موجودة (update فقط)
    if (isset($d['quantite_restante'])) {
        if ($d['quantite_restante'] === '' || !is_numeric($d['quantite_restante']) || $d['quantite_restante'] < 0) {
            $errors['quantite_restante'] = "Quantité restante invalide";
            $ok = false;
        }
    }

    return $ok;
}

// ================= ADD =================
if (isset($_POST['add'])) {
    if (validate($_POST, $errors)) {
        $quantite = (int)$_POST['quantite_initial'];

        $l = new LotMedicament(
            null,
            $_POST['nom_medicament'],
            $_POST['type_medicament'],
            $_POST['date_fabrication'],
            $_POST['date_expiration'],
            $quantite,           // quantite_initial
            $quantite,           // quantite_restante = same as initial
            $_POST['description'] ?? ''
        );

        $c->add($l);
        $_SESSION['success_message'] = "Lot ajouté avec succès.";
        header("Location: crud_lot.php");
        exit;
    }
}

// ================= UPDATE =================
if (isset($_POST['update'])) {
    if (validate($_POST, $errors)) {
        $l = new LotMedicament(
            $_POST['id_lot'],
            $_POST['nom_medicament'],
            $_POST['type_medicament'],
            $_POST['date_fabrication'],
            $_POST['date_expiration'],
            $_POST['quantite_initial'],
            $_POST['quantite_restante'],
            $_POST['description'] ?? ''
        );

        $c->update($_POST['id_lot'], $l);
        $_SESSION['success_message'] = "Lot modifié avec succès.";
        header("Location: crud_lot.php");
        exit;
    }
}

// ================= SEARCH =================
$search = $_GET['search'] ?? null;
$lots = $c->getAll($search);

// ================= STAT DATA =================
$typeCounts = [];
foreach ($lots as $l) {
    $type = $l['type_medicament'] ?? 'Non défini';
    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Lots - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
            --navy: #1E3A52;
            --gray-500: #6B7280;
            --gray-200: #E5E7EB;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --radius-md: 12px;
            --radius-lg: 20px;
            --radius-xl: 28px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
            min-height: 100vh;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        .dashboard-sidebar {
            background: linear-gradient(180deg, var(--navy) 0%, #0F172A 100%);
            position: sticky; top: 0; height: 100vh;
            display: flex; flex-direction: column; overflow-y: auto;
        }

        .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .dashboard-logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .dashboard-logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .dashboard-logo-icon i { font-size: 18px; color: white; }
        .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; }
        .dashboard-logo-text span { color: var(--green); }

        .dashboard-nav { flex: 1; padding: 0 12px; }
        .dashboard-nav-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            color: #94A3B8; text-decoration: none; border-radius: var(--radius-md);
            transition: all 0.3s; font-size: 14px; font-weight: 500;
        }
        .dashboard-nav-item i { font-size: 18px; width: 24px; }
        .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }
        .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }

        .dashboard-main { padding: 32px 40px; overflow-y: auto; }

        .dashboard-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;
        }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 700; color: var(--navy); }

        .card {
            background: var(--white);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(29,158,117,.15);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 32px;
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--gray-200);
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-body { padding: 24px; }

        .form-box input, .form-box textarea {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px;
        }
        .error { color: #ef4444; font-size: 13px; margin-top: 4px; display: block; }

        .table { width: 100%; border-collapse: collapse; }
        .table th {
            background: #F8FAFC; padding: 14px 16px; text-align: left; font-weight: 600; color: #64748B;
        }
        .table td { padding: 14px 16px; border-bottom: 1px solid #eee; }
        .table tr:hover { background: #f8fafc; }

        .actions a {
            padding: 6px 12px; border-radius: 6px; color: white; text-decoration: none; font-size: 13px;
        }
        .edit { background: #f59e0b; }
        .delete { background: #ef4444; }

        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: #F0FDF4; border-left: 4px solid #22C55E; color: #166534; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-logo">
            <a href="admin-dashboard.php">
                <div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
                <div class="dashboard-logo-text">Med<span>Chain</span></div>
            </a>
        </div>
        
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="admin-dashboard.php" class="dashboard-nav-item">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="crud_distribution.php" class="dashboard-nav-item">
                <i class="bi bi-arrow-left-right"></i> Distributions
            </a>
            <a href="crud_lot.php" class="dashboard-nav-item active">
                <i class="bi bi-box-seam"></i> Lots de Médicaments
            </a>

            <div class="dashboard-nav-title mt-4">Gestion</div>
            <?php if (in_array($_SESSION['user_role'], ['admin', 'medecin'])): ?>
                <a href="crud_lot.php" class="dashboard-nav-item active">
                    <i class="bi bi-capsule-pill"></i> Médicaments Sensibles
                </a>
            <?php endif; ?>

            <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item">
                <i class="bi bi-person-circle"></i> Mon profil
            </a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Gestion des Lots de Médicaments</h1>
                <p>Gérez vos lots de médicaments sensibles</p>
            </div>
            <div class="user-info">
                <div class="user-avatar"><i class="bi bi-person-fill"></i></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] ?? 'Admin') ?></div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-plus-circle"></i> <?= $edit ? 'Modifier' : 'Nouveau' ?> Lot</h2>
            </div>
            <div class="card-body">
                <div class="form-box">
                    <form method="POST" novalidate>
                        <input type="hidden" name="id_lot" value="<?= $edit['id_lot'] ?? '' ?>">

                        <label>Nom du Médicament</label>
                        <input type="text" name="nom_medicament" value="<?= htmlspecialchars($_POST['nom_medicament'] ?? $edit['nom_medicament'] ?? '') ?>" >
                        <span class="error"><?= $errors['nom_medicament'] ?? '' ?></span>

                        <label>Type de Médicament</label>
                        <input type="text" name="type_medicament" value="<?= htmlspecialchars($_POST['type_medicament'] ?? $edit['type_medicament'] ?? '') ?>" >
                        <span class="error"><?= $errors['type_medicament'] ?? '' ?></span>

                        <label>Date de Fabrication</label>
                        <input type="date" name="date_fabrication" value="<?= $_POST['date_fabrication'] ?? $edit['date_fabrication'] ?? '' ?>" >
                        <span class="error"><?= $errors['date_fabrication'] ?? '' ?></span>

                        <label>Date d'Expiration</label>
                        <input type="date" name="date_expiration" value="<?= $_POST['date_expiration'] ?? $edit['date_expiration'] ?? '' ?>" >
                        <span class="error"><?= $errors['date_expiration'] ?? '' ?></span>

                        <label>Quantité Initiale</label>
                        <input type="number" name="quantite_initial" value="<?= $_POST['quantite_initial'] ?? $edit['quantite_initial'] ?? '' ?>" >
                        <span class="error"><?= $errors['quantite_initial'] ?? '' ?></span>



                        <label>Description</label>
                        <textarea name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? $edit['description'] ?? '') ?></textarea>

                        <?php if ($edit): ?>
                            <button type="submit" name="update" style="margin-top:15px; padding:12px 24px; background:#1D9E75; color:white; border:none; border-radius:8px; cursor:pointer;">Modifier le Lot</button>
                        <?php else: ?>
                            <button type="submit" name="add" style="margin-top:15px; padding:12px 24px; background:#1D9E75; color:white; border:none; border-radius:8px; cursor:pointer;">Ajouter le Lot</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-box-seam"></i> Liste des Lots de Médicaments</h2>
                <div>
                    <button onclick="openModal()" class="btn" style="background:#10b981; color:white; border:none; padding:8px 16px; border-radius:8px; cursor:pointer;">
                        <i class="bi bi-graph-up"></i> Statistiques
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Search -->
                <form method="GET" style="margin-bottom:20px;">
                    <input type="text" name="search" placeholder="Rechercher par nom de médicament..." 
                           value="<?= htmlspecialchars($search ?? '') ?>" style="padding:10px; width:300px; border-radius:8px; border:1px solid #ddd;">
                    <button type="submit" style="padding:10px 16px;">Rechercher</button>
                    <?php if (!empty($search)): ?>
                        <a href="crud_lot.php"><button type="button">Réinitialiser</button></a>
                    <?php endif; ?>
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Fabrication</th>
                            <th>Expiration</th>
                            <th>Quantité Initiale</th>
                            <th>Quantité Restante</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lots)): ?>
                            <tr><td colspan="8" style="text-align:center; padding:40px;">Aucun lot trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lots as $l): ?>
                            <tr>
                                <td><?= $l['id_lot'] ?></td>
                                <td><?= htmlspecialchars($l['nom_medicament']) ?></td>
                                <td><?= htmlspecialchars($l['type_medicament']) ?></td>
                                <td><?= $l['date_fabrication'] ?></td>
                                <td><?= $l['date_expiration'] ?></td>
                                <td><?= $l['quantite_initial'] ?></td>
                                <td><?= $l['quantite_restante'] ?></td>
                                <td class="actions">
                                    <a href="?edit=<?= $l['id_lot'] ?>" class="edit">Modifier</a>
                                    <a href="?delete=<?= $l['id_lot'] ?>" class="delete" >Supprimer</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Statistics Modal -->
<div id="statModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:white; padding:25px; border-radius:16px; width:700px; max-width:95%;">
        <button onclick="closeModal()" style="float:right; background:red; color:white; border:none; padding:8px 12px; border-radius:6px; cursor:pointer;">Fermer</button>
        <h3 style="margin-bottom:20px;">Statistiques par Type de Médicament</h3>
        <canvas id="myChart" height="100"></canvas>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true });

    let chartInstance = null;

    function openModal() {
        document.getElementById("statModal").style.display = "flex";
        
        const ctx = document.getElementById('myChart');
        
        if (chartInstance) chartInstance.destroy();

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($typeCounts)) ?>,
                datasets: [{
                    label: 'Nombre de Lots',
                    data: <?= json_encode(array_values($typeCounts)) ?>,
                    backgroundColor: '#1D9E75',
                    borderColor: '#0F6E56',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function closeModal() {
        document.getElementById("statModal").style.display = "none";
    }
</script>
</body>
</html>