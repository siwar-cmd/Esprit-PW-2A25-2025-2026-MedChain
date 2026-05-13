<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/BlocOperationController.php';
$controller = new BlocOperationController();

$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;

if (!empty($search)) {
    $searchData = $controller->searchIntervention($search);
    $allResults = $searchData['data'] ?? [];
    // Filter for medecin
    $interventions = array_filter($allResults, function($item) {
        $prenomNom = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
        $nomPrenom = trim(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? ''));
        return (stripos($item['chirurgien'], $prenomNom) !== false || stripos($item['chirurgien'], $nomPrenom) !== false);
    });
    $total = count($interventions);
    $interventions = array_slice($interventions, ($page - 1) * 10, 10);
} else {
    $interventionData = $controller->getInterventionList($page);
    $interventions = $interventionData['data'] ?? [];
    $total = $interventionData['total'] ?? 0;
}
$totalPages = ceil($total / 10);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Interventions – Bloc Opératoire – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../components/medecin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .badge-niveau-1 { background: #d1fae5; color: #065f46; }
    .badge-niveau-2 { background: #fef3c7; color: #92400e; }
    .badge-niveau-3 { background: #fecaca; color: #991b1b; }
    .badge-niveau-4 { background: #fee2e2; color: #7f1d1d; }
    .badge-niveau-5 { background: #7f1d1d; color: #ffffff; }
    .btn-action { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; transition: all 0.3s ease; }
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-heartbeat"></i> Interventions</h1>
                <p>Gestion du Bloc Opératoire</p>
            </div>
            <div style="display:flex;gap:12px;">
                <button id="exportPdfBtn" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Liste des Interventions</h2>
                <form method="GET" style="display:flex;gap:8px;">
                    <input type="text" name="search" class="search-input" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Chirurgien</th>
                                <th>Date</th>
                                <th>Urgence</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interventions as $intervention): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($intervention['type'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($intervention['chirurgien'] ?? '-') ?></td>
                                <td><?= !empty($intervention['date_intervention']) ? date('d/m/Y H:i', strtotime($intervention['date_intervention'])) : '-' ?></td>
                                <td>
                                    <span class="badge badge-niveau-<?= $intervention['niveau_urgence'] ?? '0' ?>">
                                        <?php
                                        $niveaux = [1 => 'Faible', 2 => 'Modérée', 3 => 'Élevée', 4 => 'Critique', 5 => 'Extrême'];
                                        echo htmlspecialchars($niveaux[$intervention['niveau_urgence'] ?? 0] ?? 'Non défini');
                                        ?>
                                    </span>
                                </td>
                                <td style="display:flex;gap:5px;">
                                    <a href="intervention-view.php?id=<?= $intervention['id'] ?>" class="btn-action" style="background: #10b981; color: white;" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="intervention-edit.php?id=<?= $intervention['id'] ?>" class="btn-action" style="background: #3b82f6; color: white;" title="Modifier"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($interventions)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding:40px; color:var(--gray-500);">
                                    Aucune intervention trouvée.
                                    <?php if(isset($interventionData['success']) && !$interventionData['success']): ?>
                                        <br><small style="color:#EF4444;">Erreur: <?= htmlspecialchars($interventionData['message'] ?? 'Inconnue') ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?= max(1, $page-1) ?>" class="page-link <?= $page<=1?'disabled':'' ?>"><i class="bi bi-chevron-left"></i></a>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= min($totalPages, $page+1) ?>" class="page-link <?= $page>=$totalPages?'disabled':'' ?>"><i class="bi bi-chevron-right"></i></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top:20px; padding:10px; background:rgba(0,0,0,0.05); border-radius:8px; font-size:11px; color:var(--gray-500);">
            <i class="bi bi-info-circle"></i> Connecté en tant que: <strong><?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?></strong> (ID: <?= $_SESSION['user_id'] ?? '?' ?>) | Résultats: <?= $total ?>
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
<script>
document.getElementById('exportPdfBtn')?.addEventListener('click', function(){
    const btn = this;
    btn.disabled = true;
    const prevText = btn.innerHTML;
    btn.innerHTML = '...';
    fetch('intervention-export-pdf.php')
        .then(r => r.text())
        .then(html => {
            const wrapper = document.createElement('div');
            wrapper.style.position = 'fixed'; wrapper.style.left = '-9999px';
            wrapper.innerHTML = html;
            document.body.appendChild(wrapper);
            const element = wrapper.querySelector('.container') || wrapper;
            const filename = 'rapport_interventions_' + new Date().getTime() + '.pdf';
            return html2pdf().from(element).set({margin:10, filename: filename, image: {type: 'jpeg', quality: 0.98}, html2canvas: {scale:2}}).outputPdf('blob')
                .then(blob => ({ blob, filename, wrapper }));
        })
        .then(({ blob, filename, wrapper }) => {
            const form = new FormData();
            form.append('file', blob, filename); form.append('filename', filename);
            return fetch('../../../controllers/upload_to_drive.php', { method: 'POST', body: form })
                .then(r => r.json()).then(data => ({ data, wrapper }));
        })
        .then(({ data, wrapper }) => {
            btn.disabled = false; btn.innerHTML = prevText;
            if (data && data.success) {
                const link = data.file['webViewLink'] ?? null;
                if (link) window.open(link, '_blank');
                else alert('Sauvegardé sur Drive');
            } else alert('Erreur Drive');
            if (wrapper && wrapper.parentNode) wrapper.parentNode.removeChild(wrapper);
        })
        .catch(err => { console.error(err); btn.disabled = false; btn.innerHTML = prevText; alert('Erreur'); });
});
</script>
</body>
</html>
