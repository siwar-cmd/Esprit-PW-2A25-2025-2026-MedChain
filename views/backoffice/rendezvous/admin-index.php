<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/RendezVousController.php';
$rdvController = new RendezVousController();
$search = $_GET['search'] ?? '';
$filters = ['search' => $search];
$rdvData = $rdvController->getAllRendezVous($filters, 'admin');
$rendezvous = $rdvData['success'] ? $rdvData['rdvs'] : [];

$items_per_page = 5;
$total_items = count($rendezvous);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;
$paginated_rdv = array_slice($rendezvous, $offset, $items_per_page);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Rendez-vous – Admin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="../components/admin.css">
<style>
.status-badge{padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;}
.status-planifie{background:#E0F2FE;color:#0284C7;}
.status-termine{background:#DCFCE7;color:#16A34A;}
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-admin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Tous les Rendez-vous</h1>
            <button class="btn-primary" style="background:var(--green);color:#fff;padding:8px 16px;border-radius:8px;border:none;" onclick="window.print()">Exporter PDF</button>
        </div>
        <div class="card">
            <div class="card-header"><h2>Liste Globale</h2></div>
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead><tr><th>Patient</th><th>Médecin</th><th>Date</th><th>Heure</th><th>Statut</th></tr></thead>
                    <tbody>
                        <?php foreach ($paginated_rdv as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(($r['client_prenom'] ?? '') . ' ' . ($r['client_nom'] ?? '')) ?></strong></td>
                            <td>Dr. <?= htmlspecialchars(($r['medecin_prenom'] ?? '') . ' ' . ($r['medecin_nom'] ?? '')) ?></td>
                            <td><?= isset($r['dateHeureDebut']) ? date('d/m/Y', strtotime($r['dateHeureDebut'])) : '—' ?></td>
                            <td><?= isset($r['dateHeureDebut']) ? date('H:i', strtotime($r['dateHeureDebut'])) : '—' ?></td>
                            <td><span class="status-badge status-<?= $r['statut'] ?>"><?= ucfirst($r['statut']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
