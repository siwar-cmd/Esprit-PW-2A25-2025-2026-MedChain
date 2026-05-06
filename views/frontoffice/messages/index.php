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

// Récupération des messages reçus
if ($role === 'patient') {
    $controller = new PatientController();
    // Le PatientController n'a pas de méthode getMessages, on va utiliser une requête directe ou étendre la classe.
    // On utilisera une requête simple ici.
    $pdo = require_once __DIR__ . '/../../../config.php';
    $pdo = config::getConnexion();
    $stmt = $pdo->prepare("SELECT m.*, u.nom as expediteur_nom, u.prenom as expediteur_prenom FROM message m JOIN utilisateur u ON u.id_utilisateur = m.id_expediteur WHERE m.id_destinataire = ? ORDER BY m.date_envoi DESC");
    $stmt->execute([$userId]);
    $messagesRecus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $medecins = (new PatientController())->getMedecins(); // pour envoyer un message
} elseif ($role === 'medecin') {
    $controller = new MedecinController();
    $messagesRecus = $controller->getMessages();
    $patients = $controller->getAllPatients(); // pour envoyer un message
} else {
    header('Location: ../home/index.php');
    exit;
}

// Envoi d'un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $destinataireId = (int)$_POST['destinataire_id'];
    $sujet = trim($_POST['sujet']);
    $corps = trim($_POST['corps']);
    if (empty($sujet) || empty($corps)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        if ($role === 'patient') {
            // Insertion directe (PatientController n'a pas de méthode sendMessage)
            $pdo = config::getConnexion();
            $stmt = $pdo->prepare("INSERT INTO message (id_expediteur, id_destinataire, sujet, corps, date_envoi, lu) VALUES (?, ?, ?, ?, NOW(), 0)");
            $ok = $stmt->execute([$userId, $destinataireId, $sujet, $corps]);
        } else {
            $result = $controller->sendMessage($destinataireId, $sujet, $corps);
            $ok = $result['success'];
        }
        if ($ok) {
            $message = "Message envoyé avec succès.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Erreur lors de l'envoi.";
        }
    }
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messagerie - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'DM Sans',sans-serif;background:#f0faf6;padding:40px 20px;}
        .container{max-width:1200px;margin:0 auto;background:#fff;border-radius:28px;padding:32px;box-shadow:0 12px 40px rgba(0,0,0,.1);}
        h1{font-family:'Syne',sans-serif;font-size:28px;color:#1E3A52;margin-bottom:24px;}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:12px;font-weight:600;border:none;cursor:pointer;}
        .btn-primary{background:linear-gradient(135deg,#1D9E75,#0F6E56);color:#fff;}
        hr{margin:24px 0;}
        .message-card{padding:16px;border:1px solid #E5E7EB;border-radius:16px;margin-bottom:16px;}
        .message-header{display:flex;justify-content:space-between;margin-bottom:8px;font-weight:600;}
        .message-sujet{color:#1E3A52;font-size:16px;}
        .message-date{color:#6B7280;font-size:12px;}
        .modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);justify-content:center;align-items:center;z-index:1000;}
        .modal-content{background:#fff;border-radius:24px;padding:24px;max-width:500px;width:90%;}
        .form-group{margin-bottom:16px;}
        label{display:block;margin-bottom:6px;font-weight:600;}
        select,input,textarea{width:100%;padding:10px;border:2px solid #E5E7EB;border-radius:12px;}
        .close{float:right;font-size:24px;cursor:pointer;}
        .alert{padding:12px;border-radius:12px;margin-bottom:20px;}
        .alert-success{background:#D1FAE5;color:#065F46;}
        .alert-error{background:#FEE2E2;color:#991B1B;}
    </style>
</head>
<body>
<div class="container">
    <h1><i class="bi bi-chat-dots-fill"></i> Messagerie sécurisée</h1>
    <button class="btn btn-primary" id="newMsgBtn"><i class="bi bi-send-fill"></i> Nouveau message</button>

    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <hr>
    <h2>📩 Messages reçus</h2>
    <?php if (empty($messagesRecus)): ?>
        <p>Aucun message pour le moment.</p>
    <?php else: ?>
        <?php foreach ($messagesRecus as $msg): ?>
        <div class="message-card">
            <div class="message-header">
                <span class="message-sujet"><?= e($msg['sujet']) ?></span>
                <span class="message-date"><?= date('d/m/Y H:i', strtotime($msg['date_envoi'])) ?></span>
            </div>
            <div><strong>De :</strong> <?= e($msg['expediteur_prenom'] ?? '') ?> <?= e($msg['expediteur_nom'] ?? '') ?></div>
            <div class="message-corps" style="margin-top:8px;"><?= nl2br(e($msg['corps'])) ?></div>
            <?php if (($msg['lu'] ?? 0) == 0): ?>
                <span class="badge" style="background:#FEF3C7;color:#92400E;font-size:11px;">Non lu</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="msgModal" class="modal">
    <div class="modal-content">
        <button class="close">&times;</button>
        <h3>Nouveau message</h3>
        <form method="POST">
            <input type="hidden" name="action" value="send">
            <div class="form-group">
                <label>Destinataire</label>
                <select name="destinataire_id" required>
                    <option value="">Sélectionnez</option>
                    <?php if ($role === 'patient'): ?>
                        <?php foreach ($medecins as $med): ?>
                            <option value="<?= $med['id_utilisateur'] ?>">Dr. <?= e($med['prenom']) ?> <?= e($med['nom']) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($patients as $pat): ?>
                            <option value="<?= $pat['id_utilisateur'] ?>"><?= e($pat['prenom']) ?> <?= e($pat['nom']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sujet</label>
                <input type="text" name="sujet" required>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="corps" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Envoyer</button>
        </form>
    </div>
</div>
<script>
    const modal = document.getElementById('msgModal');
    document.getElementById('newMsgBtn').onclick = () => modal.style.display = 'flex';
    document.querySelector('.close').onclick = () => modal.style.display = 'none';
    window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
</script>
</body>
</html>