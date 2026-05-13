<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/LotMedicamentController.php';
$ctrl = new LotMedicamentController();

$search = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $res = $ctrl->createLotMedicament($_POST);
    } elseif ($_POST['action'] === 'update') {
        $res = $ctrl->updateLotMedicament($_POST['id_lot'], $_POST);
    } elseif ($_POST['action'] === 'delete') {
        $res = $ctrl->deleteLotMedicament($_POST['id_lot']);
    }
    
    if (isset($res)) {
        $_SESSION[$res['success'] ? 'success_message' : 'error_message'] = $res['message'];
    }
    header('Location: admin-index.php'); exit;
}

$result = $ctrl->getAllLotMedicaments(['search' => $search]);
$lots = $result['lots'] ?? [];
$stats = $ctrl->getStats();

$success = $_SESSION['success_message'] ?? null; unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Stock & Pharmacie – MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="../components/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .badge { padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-danger { background: #fee2e2; color: #ef4444; }
        .badge-warning { background: #ffedd5; color: #f97316; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: white; padding: 24px; border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); display: flex; align-items: center; gap: 16px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-icon.blue { background: #e0f2fe; color: #0284c7; }
        .stat-icon.green { background: #dcfce7; color: #16a34a; }
        .stat-icon.red { background: #fee2e2; color: #ef4444; }
        
        .search-bar { display: flex; gap: 12px; margin-bottom: 24px; }
        .search-input { flex: 1; padding: 10px 16px; border: 1px solid var(--gray-200); border-radius: 12px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include '../components/sidebar-admin.php'; ?>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Lots de Médicaments</h1>
                <p>Gestion du stock et de la pharmacie centrale</p>
            </div>
            <div style="display:flex; gap:12px;" class="no-print">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg"></i> Nouveau Lot
                </button>
                <a href="stats.php" class="btn btn-primary" style="background:#2563eb; text-decoration:none; display:flex; align-items:center;">
                    <i class="bi bi-bar-chart-line" style="margin-right:8px;"></i> Statistiques
                </a>
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
                </button>
            </div>
        </div>

        <style>
            @media print {
                .no-print, .dashboard-sidebar { display: none !important; }
                .dashboard-main { margin-left: 0 !important; padding: 0 !important; }
                .card { border: none !important; box-shadow: none !important; }
                .dashboard-header h1 { font-size: 24px; }
            }
        </style>

        <?php if($success): ?>
            <script>Swal.fire({ title: 'Succès', text: <?= json_encode($success) ?>, icon: 'success', confirmButtonColor: '#1D9E75' });</script>
        <?php endif; ?>
        <?php if($error): ?>
            <script>Swal.fire({ title: 'Erreur', text: <?= json_encode($error) ?>, icon: 'error', confirmButtonColor: '#EF4444' });</script>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
                <div><h3 style="font-family:Syne;"><?= $stats['total_lots'] ?></h3><p style="font-size:12px;color:var(--gray-500);">Total Lots</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-pills"></i></div>
                <div><h3 style="font-family:Syne;"><?= $stats['sum_restante'] ?></h3><p style="font-size:12px;color:var(--gray-500);">Unités Disponibles</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
                <div><h3 style="font-family:Syne;"><?= $stats['expires'] ?></h3><p style="font-size:12px;color:var(--gray-500);">Lots Expirés</p></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Inventaire en temps réel</h2>
                <form method="GET" class="search-bar" style="margin-bottom:0;">
                    <input type="text" name="search" class="search-input" placeholder="Rechercher un médicament..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Médicament</th>
                            <th>Type</th>
                            <th>Quantité Initial</th>
                            <th>Restant</th>
                            <th>Expiration</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($lots)): ?>
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-500);">Aucun lot trouvé.</td></tr>
                        <?php else: foreach ($lots as $l): 
                            $isExpired = strtotime($l['date_expiration']) < time();
                            $isLow = $l['quantite_restante'] < ($l['quantite_initial'] * 0.2);
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($l['nom_medicament']) ?></strong></td>
                                <td><?= htmlspecialchars($l['type_medicament']) ?></td>
                                <td><?= $l['quantite_initial'] ?></td>
                                <td>
                                    <span style="font-weight:600; color:<?= $isLow ? '#f97316' : 'inherit' ?>;">
                                        <?= $l['quantite_restante'] ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($l['date_expiration'])) ?></td>
                                <td>
                                    <?php if($isExpired): ?>
                                        <span class="badge badge-danger">Expiré</span>
                                    <?php elseif($isLow): ?>
                                        <span class="badge badge-warning">Stock Faible</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Valide</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <button class="btn btn-sm btn-primary" style="padding:4px 8px; background:#f59e0b;" onclick='editLot(<?= json_encode($l) ?>)'><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-danger" style="padding:4px 8px; background:#ef4444;" onclick="deleteLot(<?= $l['id_lot'] ?>)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function openCreateModal() {
    Swal.fire({
        title: '<span style="font-family:Syne; font-weight:700;">Ajouter un Nouveau Lot</span>',
        html: `
            <form id="addLotForm" method="POST" style="text-align:left; font-family:DM Sans;">
                <input type="hidden" name="action" value="create">
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Nom du Médicament *</label>
                    <input type="text" name="nom_medicament" class="swal2-input" placeholder="Ex: Panadol, Morphine..." style="width:100%; margin:0;" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Type *</label>
                        <select name="type_medicament" class="swal2-select" style="width:100%; margin:0;" required>
                            <option value="comprimé">Comprimé</option>
                            <option value="sirop">Sirop</option>
                            <option value="injection">Injection</option>
                            <option value="pommade">Pommade</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Quantité Initiale *</label>
                        <input type="number" name="quantite_initial" class="swal2-input" placeholder="Ex: 100" style="width:100%; margin:0;" required min="1">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Fabrication *</label>
                        <input type="date" name="date_fabrication" class="swal2-input" style="width:100%; margin:0;" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Expiration *</label>
                        <input type="date" name="date_expiration" class="swal2-input" style="width:100%; margin:0;" required>
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Description</label>
                    <textarea name="description" class="swal2-textarea" style="width:100%; margin:0; height:80px;" placeholder="Notes additionnelles..."></textarea>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Enregistrer le lot',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#1D9E75',
        cancelButtonColor: '#6B7280',
        preConfirm: () => {
            const form = document.getElementById('addLotForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('addLotForm').submit();
        }
    });
}

function deleteLot(id) {
    Swal.fire({
        title: 'Supprimer ce lot ?',
        text: "Cette action supprimera également les distributions associées.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id_lot" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function editLot(lot) {
    Swal.fire({
        title: '<span style="font-family:Syne; font-weight:700;">Modifier le Lot</span>',
        html: `
            <form id="editLotForm" method="POST" style="text-align:left; font-family:DM Sans;">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_lot" value="${lot.id_lot}">
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Nom du Médicament *</label>
                    <input type="text" name="nom_medicament" value="${lot.nom_medicament}" class="swal2-input" style="width:100%; margin:0;" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Type *</label>
                        <select name="type_medicament" class="swal2-select" style="width:100%; margin:0;" required>
                            <option value="comprimé" ${lot.type_medicament === 'comprimé' ? 'selected' : ''}>Comprimé</option>
                            <option value="sirop" ${lot.type_medicament === 'sirop' ? 'selected' : ''}>Sirop</option>
                            <option value="injection" ${lot.type_medicament === 'injection' ? 'selected' : ''}>Injection</option>
                            <option value="pommade" ${lot.type_medicament === 'pommade' ? 'selected' : ''}>Pommade</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Quantité Initiale *</label>
                        <input type="number" name="quantite_initial" value="${lot.quantite_initial}" class="swal2-input" style="width:100%; margin:0;" required min="1">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Fabrication *</label>
                        <input type="date" name="date_fabrication" value="${lot.date_fabrication}" class="swal2-input" style="width:100%; margin:0;" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Expiration *</label>
                        <input type="date" name="date_expiration" value="${lot.date_expiration}" class="swal2-input" style="width:100%; margin:0;" required>
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px;">Description</label>
                    <textarea name="description" class="swal2-textarea" style="width:100%; margin:0; height:80px;">${lot.description || ''}</textarea>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Mettre à jour',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6B7280',
        preConfirm: () => {
            const form = document.getElementById('editLotForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('editLotForm').submit();
        }
    });
}
</script>

</body>
</html>
