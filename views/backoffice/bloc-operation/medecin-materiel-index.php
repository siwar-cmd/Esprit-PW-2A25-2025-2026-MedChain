<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/BlocOperationController.php';
$controller = new BlocOperationController();

$page = $_GET['page'] ?? 1;
$materielData = $controller->getMaterielList($page);
$materiels = $materielData['data'] ?? [];
$total = $materielData['total'] ?? 0;
$totalPages = ceil($total / 10);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Matériel – Bloc Opératoire – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../components/medecin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-tools"></i> Matériel</h1>
                <p>Inventaire du Bloc Opératoire</p>
            </div>
            <button id="exportPdfBtn" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Liste du Matériel</h2>
                <form method="POST" action="materiel-search.php" style="display:flex;gap:8px;">
                    <input type="text" name="search_term" class="search-input" placeholder="ID, catégorie..." required>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Matériel</th>
                                <th>Catégorie</th>
                                <th>Intervention</th>
                                <th>Disponibilité</th>
                                <th>Stérilisation</th>
                                <th>Utilisations</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materiels as $materiel): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($materiel['id_materiel']) ?></strong></td>
                                <td><?= htmlspecialchars($materiel['categorie'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($materiel['id_intervention'])): ?>
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                            #<?= $materiel['id_intervention'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $materiel['disponibilite']=='Disponible'?'bg-success':'bg-danger' ?> text-white"><?= htmlspecialchars($materiel['disponibilite'] ?? '-') ?></span></td>
                                <td><?= htmlspecialchars($materiel['statutSterilisation'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($materiel['nombreUtilisationsActuelles'] ?? 0) ?>/<?= htmlspecialchars($materiel['nombreUtilisationsMax'] ?? 0) ?></td>
                                <td>
                                    <a href="materiel-view.php?id=<?= $materiel['idMateriel'] ?>" class="btn btn-primary btn-sm" title="Voir"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
<script>
document.getElementById('exportPdfBtn')?.addEventListener('click', function(){
    const btn = this; btn.disabled = true; const prevText = btn.innerHTML; btn.innerHTML = '...';
    fetch('materiel-export-pdf.php')
        .then(r => r.text())
        .then(html => {
            const wrapper = document.createElement('div');
            wrapper.style.position = 'fixed'; wrapper.style.left = '-9999px';
            wrapper.innerHTML = html;
            document.body.appendChild(wrapper);
            const element = wrapper.querySelector('.container') || wrapper;
            const filename = 'rapport_materiel_' + new Date().getTime() + '.pdf';
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
