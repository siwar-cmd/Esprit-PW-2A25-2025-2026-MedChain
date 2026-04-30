<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once "../../../controllers/controller_distribution.php";
require_once "../../../controllers/controller_lot.php";

$c = new DistributionController();
$lotC = new LotController();

$errors = [
    "id_lot" => "",
    "date_distribution" => "",
    "quantite_distribuee" => "",
    "patient" => ""
];

$edit = null;

// Get only available lots (with remaining quantity > 0)
$availableLots = $lotC->getAvailableLots();

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {
    $c->delete($_GET['delete']);
    $_SESSION['success_message'] = "Distribution supprimée avec succès.";
    header("Location: crud_distribution.php?reset=1");
    exit;
}



function validateDistribution($d, &$errors, $lotC) {
    $ok = true;

    if (empty($d['id_lot']) || $d['id_lot'] <= 0) {
        $errors['id_lot'] = "Médicament obligatoire";
        $ok = false;
    }

    if (empty($d['date_distribution'])) {
        $errors['date_distribution'] = "Date obligatoire";
        $ok = false;
    }

    if (!isset($d['quantite_distribuee']) || !is_numeric($d['quantite_distribuee']) || $d['quantite_distribuee'] <= 0) {
        $errors['quantite_distribuee'] = "Quantité invalide";
        $ok = false;
    }

    if (empty(trim($d['patient'])) || strlen(trim($d['patient'])) < 3) {
        $errors['patient'] = "Nom patient invalide";
        $ok = false;
    }

    // check stock
    if (!empty($d['id_lot']) && !empty($d['quantite_distribuee'])) {
        $lot = $lotC->getById($d['id_lot']);
        if ($lot && $d['quantite_distribuee'] > $lot['quantite_restante']) {
            $errors['quantite_distribuee'] =
                "Stock insuffisant (reste: " . $lot['quantite_restante'] . ")";
            $ok = false;
        }
    }

    return $ok;
}
/* ================= EDIT LOAD ================= */
if (isset($_GET['edit'])) {
    $edit = $c->getById($_GET['edit']);
    if ($edit && isset($edit['date_distribution'])) {
        $edit['date_distribution'] = date('Y-m-d', strtotime($edit['date_distribution']));
    }
}
// ================= SEARCH =================
$search = $_GET['search'] ?? null;

if ($search && !isset($_GET['reset'])) {
    $data = $c->search($search);
} else {
    $data = $c->list();
}

// ================= STAT DATA =================
$stats = [];

foreach ($data as $d) {
    $lot = $d['id_lot'];
    $stats[$lot] = ($stats[$lot] ?? 0) + $d['quantite_distribuee'];
}
/* ================= UPDATE ================= */
if (isset($_POST['update'])) {

    $data = $_POST;

    if (validateDistribution($data, $errors, $lotC)) {

        $responsable = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'];

        $c->update((int)$_POST['id'], [
            "id_lot" => (int)$_POST['id_lot'],
            "date_distribution" => $_POST['date_distribution'],
            "quantite_distribuee" => (int)$_POST['quantite_distribuee'],
            "patient" => trim($_POST['patient']),
            "responsable" => $responsable
        ]);

        $_SESSION['success_message'] = "Distribution modifiée avec succès.";
        header("Location: crud_distribution.php");
        exit;
    }
}

/* ================= ADD ================= */
if (isset($_POST['add'])) {

    if (validateDistribution($_POST, $errors, $lotC)) {

        $responsable = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'];

        $c->add([
            "id_lot" => (int)$_POST['id_lot'],
            "date_distribution" => $_POST['date_distribution'],
            "quantite_distribuee" => (int)$_POST['quantite_distribuee'],
            "patient" => trim($_POST['patient']),
            "responsable" => $responsable
        ]);

        $_SESSION['success_message'] = "Distribution ajoutée avec succès.";
        header("Location: crud_distribution.php");
        exit;
    }
}

/* ================= DATA ================= */

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Distributions - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
            --navy: #1E3A52;
            --gray-500: #6B7280;
            --white: #ffffff;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
            min-height: 100vh;
        }

        .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }

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
.table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 12px;
    overflow: hidden;
}

.table thead {
    background: linear-gradient(135deg, #1D9E75, #0F6E56);
    color: white;
}

.table th, .table td {
    padding: 14px 16px;
    text-align: left;
}

.table tbody tr {
    border-bottom: 1px solid #eee;
    transition: 0.2s;
}

.table tbody tr:hover {
    background: #f0faf6;
    transform: scale(1.01);
}

.actions a {
    padding: 6px 12px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    font-size: 13px;
}

.edit { background: #f59e0b; }
.delete { background: #ef4444; }
        .dashboard-nav { flex: 1; padding: 0 12px; }
        .dashboard-nav-item { 
            display: flex; align-items: center; gap: 12px; padding: 12px 16px; 
            color: #94A3B8; text-decoration: none; border-radius: 10px; transition: all 0.3s; 
        }
        .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }

        .dashboard-main { padding: 32px 40px; overflow-y: auto; }

        .card { 
            background: var(--white); border-radius: 20px; 
            border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); 
            overflow: hidden; margin-bottom: 30px; 
        }
        .card-header { padding: 20px 28px; background: #f8fafc; border-bottom: 1px solid #eee; }
        .card-body { padding: 30px; }

        .form-box label { display: block; font-weight: 600; color: var(--navy); margin-bottom: 8px; }
        .form-box input, .form-box select {
            width: 100%; padding: 12px 16px; border: 1.5px solid #e5e7eb; 
            border-radius: 10px; font-size: 15px;
        }
        .form-box input:focus, .form-box select:focus {
            border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,0.12);
        }
        .error { color: #ef4444; font-size: 13.5px; margin-top: 5px; display: block;  }

        .btn-primary {
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white; padding: 14px 32px; border: none; border-radius: 10px; 
            font-weight: 600; cursor: pointer; margin-top: 20px;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(29,158,117,0.35); }

        .alert { padding: 14px 20px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: #F0FDF4; border-left: 4px solid #22C55E; color: #166534; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar" id="sidebar">
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
            <a href="crud_lot.php" class="dashboard-nav-item">
                <i class="bi bi-box-seam"></i> Lots de Médicaments
            </a>
            <a href="crud_distribution.php" class="dashboard-nav-item active">
                <i class="bi bi-arrow-left-right"></i> Distributions
            </a>
            
            <div class="dashboard-nav-title mt-4">Gestion</div>
            <?php if (in_array($_SESSION['user_role'], ['admin', 'medecin'])): ?>
                <a href="crud_lot.php" class="dashboard-nav-item">
                    <i class="bi bi-box-seam"></i> Lots de Médicaments
                </a>
                <a href="crud_distribution.php" class="dashboard-nav-item active">
                    <i class="bi bi-arrow-left-right"></i> Distributions
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
                <h1>Gestion des Distributions</h1>
                <p>Contrôle et suivi des médicaments distribués</p>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-capsule-pill"></i> Nouvelle Distribution</h2>
            </div>
            <div class="card-body">
                <div class="form-box">
                    <form method="POST" novalidate>
<?php if ($edit): ?>
    <input type="hidden" name="id" value="<?= $edit['id_distribution'] ?>">

    <button type="submit" name="update" class="btn-primary">
        Modifier la Distribution
    </button>
<?php else: ?>
    <button type="submit" name="add" class="btn-primary">
        Ajouter la Distribution
    </button>
<?php endif; ?>
                        <label>Medicament <span style="color:red;">*</span></label>
                        <select name="id_lot" >
                            <option value="">-- Sélectionner un lot disponible --</option>
                            <?php foreach ($availableLots as $lot): ?>
<option value="<?= $lot['id_lot'] ?>"
    <?= (isset($edit['id_lot']) && $edit['id_lot'] == $lot['id_lot']) ? 'selected' : '' ?>>
    <?= htmlspecialchars($lot['nom_medicament']) ?> 
    (Restant: <?= $lot['quantite_restante'] ?>)
</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error"><?= $errors['id_lot'] ?></div>

<label>Date de Distribution <span style="color:red;">*</span></label>
<input type="date" name="date_distribution"
       value="<?= $_POST['date_distribution'] ?? $edit['date_distribution'] ?? date('Y-m-d') ?>">

<label>Quantité Distribuée <span style="color:red;">*</span></label>
<input type="number" name="quantite_distribuee" min="1"
       value="<?= $_POST['quantite_distribuee'] ?? $edit['quantite_distribuee'] ?? '' ?>">

<label>Nom du Patient <span style="color:red;">*</span></label>
<input type="text" name="patient"
       value="<?= $_POST['patient'] ?? $edit['patient'] ?? '' ?>">

<label>Responsable</label>
<input type="text"
       value="<?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?>"
       disabled>

                        
                    </form>
                </div>
            </div>
        </div>
<form method="GET" style="margin-bottom:20px;">
    <input type="text" name="search" placeholder="Rechercher par patient ou ID lot..." 
           value="<?= htmlspecialchars($search ?? '') ?>"
           style="padding:10px; width:300px; border-radius:8px; border:1px solid #ddd;">
    
    <button type="submit" style="padding:10px 16px; background:#1D9E75; color:white; border:none; border-radius:8px;">
        Rechercher
    </button>

    <?php if (!empty($search)): ?>
<a href="crud_distribution.php" style="margin-left:10px; text-decoration:none;">
    <button type="button">Reset</button>
</a>
    <?php endif; ?>
</form>
        <!-- Table -->
        <div class="card">

            <div class="card-header">
                <h2><i class="bi bi-box-seam"></i> Liste des Distributions</h2>
                <div>
                    <button onclick="openModal()" class="btn" style="background:#10b981; color:white; border:none; padding:8px 16px; border-radius:8px; cursor:pointer;">
                        <i class="bi bi-graph-up"></i> Statistiques
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Lot</th>
                                <th>Date</th>
                                <th>Quantité</th>
                                <th>Patient</th>
                                <th>Responsable</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <tr><td colspan="7" style="text-align:center; padding:40px;">Aucune distribution trouvée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($data as $d): ?>
                                <tr>
                                    <td><?= $d['id_distribution'] ?></td>
                                    <td><?= $d['id_lot'] ?></td>
                                    <td><?= $d['date_distribution'] ?></td>
                                    <td><?= $d['quantite_distribuee'] ?></td>
                                    <td><?= htmlspecialchars($d['patient']) ?></td>
                                    <td><?= htmlspecialchars($d['responsable']) ?></td>
                                    <td class="actions">
                                        <a class="edit" href="?edit=<?= $d['id_distribution'] ?>">Modifier</a>
                                        <a class="delete" href="?delete=<?= $d['id_distribution'] ?>" 
                                          >Supprimer</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="statModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center;">
    <div style="background:white; padding:25px; border-radius:16px; width:700px;">
        <button onclick="closeModal()" style="float:right; background:red; color:white;">X</button>
        <h3>Statistiques des distributions</h3>
        <canvas id="myChart"></canvas>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true });
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let chart;

function openModal() {
    document.getElementById("statModal").style.display = "flex";

    const ctx = document.getElementById('myChart');

    if (chart) chart.destroy();

    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($stats)) ?>,
            datasets: [{
                label: 'Quantité distribuée',
                data: <?= json_encode(array_values($stats)) ?>,
                backgroundColor: '#1D9E75'
            }]
        }
    });
}

function closeModal() {
    document.getElementById("statModal").style.display = "none";
}
</script>
</body>
</html>