<?php
session_start();
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../controllers/PatientController.php';
require_once __DIR__ . '/../../../controllers/MedecinController.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$role = $user->getRole();
$userId = $user->getId();
$message = '';
$error = '';

// --- PATIENT ---
if ($role === 'patient') {
    $controller = new PatientController();

    // Création d'un rendez-vous
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
        $data = [
            'id_medecin' => (int)$_POST['medecin_id'],
            'date_rdv'   => $_POST['date_rdv'],
            'heure'      => $_POST['heure'],
            'motif'      => $_POST['motif'] ?? ''
        ];
        $result = $controller->createRdv($userId, $data);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }

    $medecins = $controller->getMedecins();
    $rdvs = $controller->getAllRdv($userId);
}
// --- MÉDECIN ---
elseif ($role === 'medecin') {
    $controller = new MedecinController();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rdv_id'], $_POST['action'])) {
        $rdvId = (int)$_POST['rdv_id'];
        if ($_POST['action'] === 'accept') {
            $controller->acceptRdv($rdvId);
            $message = 'Rendez-vous accepté';
        } elseif ($_POST['action'] === 'refuse') {
            $controller->refuseRdv($rdvId);
            $message = 'Rendez-vous refusé';
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $rdvs = $controller->getAllRdv();
} else {
    header('Location: ../home/index.php');
    exit;
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes rendez-vous - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'DM Sans',sans-serif;background:#f0faf6;padding:40px 20px;}
        .container{max-width:1200px;margin:0 auto;background:#fff;border-radius:28px;padding:32px;box-shadow:0 12px 40px rgba(0,0,0,.1);}
        h1{font-family:'Syne',sans-serif;font-size:28px;color:#1E3A52;margin-bottom:24px;}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:12px;font-weight:600;border:none;cursor:pointer;}
        .btn-primary{background:linear-gradient(135deg,#1D9E75,#0F6E56);color:#fff;}
        table{width:100%;border-collapse:collapse;margin-top:20px;}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #E5E7EB;}
        th{background:#F8FAFC;color:#1E3A52;}
        .badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;}
        .badge.confirme{background:#D1FAE5;color:#065F46;}
        .badge.en_attente{background:#FEF3C7;color:#92400E;}
        .badge.refuse{background:#FEE2E2;color:#991B1B;}
        .alert{padding:12px;border-radius:12px;margin-bottom:20px;}
        .alert-success{background:#D1FAE5;color:#065F46;}
        .alert-error{background:#FEE2E2;color:#991B1B;}
        .modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);justify-content:center;align-items:center;z-index:1000;}
        .modal-content{background:#fff;border-radius:24px;padding:24px;max-width:500px;width:90%;}
        .form-group{margin-bottom:16px;}
        label{display:block;margin-bottom:6px;font-weight:600;}
        select,input,textarea{width:100%;padding:10px;border:2px solid #E5E7EB;border-radius:12px;}
        .close{float:right;font-size:24px;cursor:pointer;}
    </style>
</head>
<body>
<div class="container">
    <h1><i class="bi bi-calendar-check-fill"></i> Mes rendez-vous</h1>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($role === 'patient'): ?>
        <button class="btn btn-primary" id="newRdvBtn"><i class="bi bi-plus-lg"></i> Prendre un rendez-vous</button>
        <hr>
    <?php endif; ?>

    <h2>📅 Liste des rendez-vous</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Heure</th><th><?= $role === 'patient' ? 'Médecin' : 'Patient' ?></th><th>Motif</th><th>Statut</th><?php if ($role === 'medecin'): ?><th>Actions</th><?php endif; ?></tr>
        </thead>
        <tbody>
            <?php if (empty($rdvs)): ?>
                <tr><td colspan="6">Aucun rendez-vous trouvé.<?php if ($role === 'patient'): ?> Cliquez sur "Prendre un rendez-vous" pour en créer un.<?php endif; ?></td></tr>
            <?php else: ?>
                <?php foreach ($rdvs as $rdv): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></td>
                    <td><?= e($rdv['heure']) ?></td>
                    <td><?= $role === 'patient' ? (e($rdv['medecin_prenom'] ?? '') . ' ' . e($rdv['medecin_nom'] ?? '')) : (e($rdv['patient_prenom'] ?? '') . ' ' . e($rdv['patient_nom'] ?? '')) ?></td>
                    <td><?= e($rdv['motif'] ?? '-') ?></td>
                    <td><span class="badge <?= e($rdv['statut']) ?>"><?= e($rdv['statut']) ?></span></td>
                    <?php if ($role === 'medecin' && $rdv['statut'] === 'en_attente'): ?>
                    <td>
                        <form method="POST" style="display:inline-block">
                            <input type="hidden" name="rdv_id" value="<?= $rdv['id_rdv'] ?>">
                            <button type="submit" name="action" value="accept" class="btn" style="background:#22C55E;color:#fff;padding:6px 12px;">Accepter</button>
                            <button type="submit" name="action" value="refuse" class="btn" style="background:#EF4444;color:#fff;padding:6px 12px;">Refuser</button>
                        </form>
                    </td>
                    <?php elseif ($role === 'medecin'): ?>
                    <td>-</td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($role === 'patient'): ?>
<div id="rdvModal" class="modal">
    <div class="modal-content">
        <button class="close">&times;</button>
        <h3>Nouveau rendez-vous</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Médecin</label>
                <select name="medecin_id" required>
                    <option value="">Sélectionnez un médecin</option>
                    <?php foreach ($medecins as $med): ?>
                        <option value="<?= $med['id_utilisateur'] ?>">Dr. <?= e($med['prenom']) ?> <?= e($med['nom']) ?> (<?= e($med['specialite'] ?? 'Généraliste') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date_rdv" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Heure</label>
                <select name="heure" required>
                    <option value="">Sélectionnez une heure</option>
                    <?php for ($h=8; $h<18; $h++): ?>
                        <option value="<?= sprintf('%02d:00', $h) ?>"><?= sprintf('%02d:00', $h) ?></option>
                        <option value="<?= sprintf('%02d:30', $h) ?>"><?= sprintf('%02d:30', $h) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Motif</label>
                <textarea name="motif" rows="2" placeholder="Raison de la consultation..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Demander</button>
        </form>
    </div>
</div>
<script>
    const modal = document.getElementById('rdvModal');
    document.getElementById('newRdvBtn').onclick = () => modal.style.display = 'flex';
    document.querySelector('.close').onclick = () => modal.style.display = 'none';
    window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
</script>
<?php endif; ?>
</body>
</html>