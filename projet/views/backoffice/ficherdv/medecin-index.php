<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';

$ficheController = new FicheRendezVousController();
$userId = $_SESSION['user_id'];

$search = $_GET['search'] ?? '';
$filters = ['search' => $search];

// Handle delete
if (isset($_POST['delete_id'])) {
    $ficheController->deleteFiche($_POST['delete_id']);
    $_SESSION['success_message'] = "Fiche supprimée avec succès";
    header("Location: medecin-index.php");
    exit;
}

$ficheData = $ficheController->getAllFiches($filters, 'medecin', $userId);
$fiches = $ficheData['success'] ? $ficheData['fiches'] : [];

$stats = $ficheController->getStats('medecin', $userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Fiches Médicales - Médecin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green: #1D9E75; --green-dark: #0F6E56; --navy: #1E3A52;
            --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --radius-md: 12px; --radius-lg: 20px;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; min-height: 100vh; }
        .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .dashboard-sidebar { background: #0F172A; height: 100vh; position: sticky; top: 0; display: flex; flex-direction: column; }
        .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; text-decoration: none;}
        .dashboard-logo-text span { color: var(--green); }
        .dashboard-nav { padding: 20px 12px; display: flex; flex-direction: column; gap: 5px; }
        .dashboard-nav-item { padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .dashboard-nav-item:hover, .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }
        
        .dashboard-main { padding: 32px 40px; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--navy); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--white); border-radius: var(--radius-lg); padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: var(--shadow-sm); border: 1px solid rgba(29,158,117,.15); }
        .stat-icon { width: 52px; height: 52px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 24px; background: rgba(29,158,117,0.1); color: var(--green); }
        .stat-content h3 { font-size: 28px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .stat-content p { font-size: 13px; color: var(--gray-500); }
        
        .card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); overflow: hidden; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 24px; }
        
        .table { width: 100%; border-collapse: collapse; text-align: left; }
        .table th { background: #F8FAFC; padding: 12px 16px; color: #64748B; border-bottom: 1px solid var(--gray-200); }
        .table td { padding: 16px; border-bottom: 1px solid var(--gray-200); }
        
        .search-form { display: flex; gap: 10px; }
        .search-input { padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 8px; }
        .btn { padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;}
        .btn-primary { background: var(--green); color: white; }
        .btn-secondary { background: var(--gray-500); color: white; }
        .btn-danger { background: #FEE2E2; color: #EF4444; border: 1px solid #EF4444; }
        .btn-outline { background: white; border: 1px solid var(--green); color: var(--green); }

        .alert-success { background: #DCFCE7; color: #16A34A; padding: 15px; border-radius: 8px; margin-bottom: 20px; }

        @media print {
            .dashboard-sidebar, .search-form, .btn, .actions-col { display: none !important; }
            .dashboard-container { display: block; }
            .dashboard-main { padding: 0; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="dashboard-logo">
            <a href="#" class="dashboard-logo-text">Med<span>Chain</span></a>
        </div>
        <nav class="dashboard-nav">
            <a href="../rendezvous/medecin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
            <a href="medecin-index.php" class="dashboard-nav-item active"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item" style="color: #F87171; margin-top: auto;"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Mes Fiches Médicales</h1>
                <p>Vue Médecin</p>
            </div>
            <div>
                <a href="medecin-create.php" class="btn btn-primary me-2"><i class="bi bi-plus-lg"></i> Nouvelle Fiche</a>
                <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-file-pdf"></i> Exporter PDF</button>
            </div>
        </div>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-file-earmark-medical"></i></div>
                <div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total Fiches</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #E0F2FE; color: #0284C7;"><i class="bi bi-calendar-plus"></i></div>
                <div class="stat-content"><h3><?= $stats['ce_mois'] ?></h3><p>Fiches ce mois</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #FEF3C7; color: #D97706;"><i class="bi bi-envelope"></i></div>
                <div class="stat-content"><h3><?= $stats['emails_sent'] ?></h3><p>Emails envoyés</p></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Liste des Fiches</h2>
                <form class="search-form" method="GET">
                    <input type="text" name="search" id="searchInput" class="search-input" placeholder="Rechercher patient, motif..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable()">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body">
                <table class="table" id="ficheTable">
                    <thead>
                        <tr>
                            <th style="cursor:pointer" onclick="sortTable(0, 'ficheTable')">ID Fiche <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(1, 'ficheTable')">Date Création <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(2, 'ficheTable')">Date RDV <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(3, 'ficheTable')">Patient <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th style="cursor:pointer" onclick="sortTable(4, 'ficheTable')">Tarif <i class="bi bi-arrow-down-up" style="font-size:10px;"></i></th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fiches as $fiche): ?>
                        <tr>
                            <td>#<?= $fiche['idFiche'] ?></td>
                            <td><?= date('d/m/Y', strtotime($fiche['dateGeneration'])) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($fiche['dateHeureDebut'])) ?></td>
                            <td><?= htmlspecialchars($fiche['patient_nom'] . ' ' . $fiche['patient_prenom']) ?></td>
                            <td><?= $fiche['tarifConsultation'] ? $fiche['tarifConsultation'] . ' TND' : '-' ?></td>
                            <td class="actions-col" style="display: flex; gap: 5px;">
                                <a href="medecin-edit.php?id=<?= $fiche['idFiche'] ?>" class="btn btn-outline"><i class="bi bi-pencil"></i></a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette fiche ?');">
                                    <input type="hidden" name="delete_id" value="<?= $fiche['idFiche'] ?>">
                                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($fiches)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Aucune fiche trouvée.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function filterTable() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("ficheTable");
    tr = table.getElementsByTagName("tr");

        for (i = 1; i < tr.length; i++) {
            tr[i].style.display = "none";
            td = tr[i].getElementsByTagName("td");
            for (var j = 0; j < td.length; j++) {
                if (td[j] && !td[j].classList.contains('actions-col')) {
                    txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        break;
                    }
                }
            }
        }
    }

function sortTable(n, tableId) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById(tableId);
    switching = true;
    dir = "asc"; 
    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];
            if (dir == "asc") {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == "desc") {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount ++;
        } else {
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}
</script>
</body>
</html>
