<?php
session_start();
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../controllers/RendezVousController.php';

$authController = new AuthController();
$isLoggedIn = $authController->isLoggedIn();

if (!$authController->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$currentUser = $authController->getCurrentUser();
if ($currentUser->getRole() !== 'patient') {
    header('Location: ../../../views/backoffice/admin-dashboard.php');
    exit;
}

$rdvController = new RendezVousController();
$pdo = config::getConnexion();

// Récupérer les médecins (pour l'exemple, tous les admin ou un rôle medecin spécifique si ajouté plus tard)
$req = $pdo->query("SELECT id_utilisateur, nom, prenom FROM utilisateur WHERE role IN ('admin', 'medecin') AND statut = 'actif'");
$medecins = $req->fetchAll(PDO::FETCH_ASSOC);

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'dateHeureDebut' => $_POST['dateHeureDebut'],
        'dateHeureFin' => $_POST['dateHeureFin'],
        'statut' => 'planifie',
        'typeConsultation' => $_POST['typeConsultation'],
        'motif' => $_POST['motif'],
        'idClient' => $currentUser->getId(),
        'idMedecin' => $_POST['idMedecin']
    ];
    
    $result = $rdvController->createRendezVous($data);
    if ($result['success']) {
        $_SESSION['success_message'] = "Rendez-vous réservé avec succès.";
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nouveau Rendez-vous — MedChain</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <style>
    :root {
      --green: #1D9E75; --green-dark: #0F6E56; --navy: #1E3A52;
      --gray-200: #E5E7EB; --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
      --radius-md: 12px;
    }
    body { font-family: 'DM Sans', sans-serif; background: #f9fafb; }
    .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
    .dashboard-sidebar { background: linear-gradient(180deg, var(--navy) 0%, #0F172A 100%); position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; overflow-y: auto; }
    .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
    .dashboard-logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .dashboard-logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
    .dashboard-logo-icon i { font-size: 18px; color: white; }
    .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; }
    .dashboard-logo-text span { color: var(--green); }
    .dashboard-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
    .dashboard-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); transition: all 0.3s; font-size: 14px; font-weight: 500; }
    .dashboard-nav-item i { font-size: 18px; width: 24px; }
    .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
    .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }
    .dashboard-nav-item.logout { margin-top: auto; margin-bottom: 20px; color: #F87171; }
    .dashboard-nav-item.logout:hover { background: rgba(248,113,113,0.1); color: #F87171; }
    .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }
    .dashboard-main { padding: 32px 40px; overflow-y: auto; width: 100%; }

    .mc-container { max-width: 800px; margin: 0 auto; padding: 0 20px; }
    .card-mc { background: white; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-sm); margin-bottom: 50px; }
    .page-title { font-family: 'Syne', sans-serif; font-weight: 700; color: var(--navy); margin-bottom: 20px; }
    .btn-mc { background: var(--green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 500; }
    .btn-mc:hover { background: var(--green-dark); color: white; }
  </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar" id="sidebar">
        <div class="dashboard-logo">
            <a href="../home/index.php">
                <div class="dashboard-logo-icon">
                    <i class="bi bi-plus-square-fill"></i>
                </div>
                <div class="dashboard-logo-text">Med<span>Chain</span></div>
            </a>
        </div>
        
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="../home/index.php" class="dashboard-nav-item">
                <i class="bi bi-house-door-fill"></i> Accueil
            </a>
            <a href="../auth/profile.php" class="dashboard-nav-item">
                <i class="bi bi-person-fill"></i> Mon Profil
            </a>
            <a href="index.php" class="dashboard-nav-item active">
                <i class="bi bi-calendar-check"></i> Mes Rendez-vous
            </a>
            <a href="../ficherdv/index.php" class="dashboard-nav-item">
                <i class="bi bi-file-medical"></i> Mes Fiches
            </a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="mc-container">
            <a href="index.php" class="btn btn-outline-secondary mb-3 mt-4" style="text-decoration: none; border: 1px solid var(--gray-200); padding: 5px 15px; border-radius: 8px; color: var(--navy); display: inline-block;">&larr; Retour</a>
    <div class="card-mc">
        <h1 class="page-title">Réserver un Rendez-vous</h1>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" id="rdvForm" novalidate>
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-person-badge text-success me-2"></i>Médecin</label>
                <select name="idMedecin" id="idMedecin" class="form-select">
                    <option value="">Sélectionnez un médecin...</option>
                    <?php foreach($medecins as $med): ?>
                        <option value="<?= $med['id_utilisateur'] ?>">Dr. <?= htmlspecialchars($med['nom'] . ' ' . $med['prenom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="text-danger mt-1 error-msg" id="err-idMedecin" style="display:none; font-size:0.875em;">Veuillez sélectionner un médecin.</div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><i class="bi bi-calendar-event text-success me-2"></i>Date & Heure Début</label>
                    <input type="datetime-local" name="dateHeureDebut" id="dateHeureDebut" class="form-control">
                    <div class="text-danger mt-1 error-msg" id="err-dateHeureDebut" style="display:none; font-size:0.875em;">Veuillez choisir une date de début.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><i class="bi bi-calendar-x text-success me-2"></i>Date & Heure Fin</label>
                    <input type="datetime-local" name="dateHeureFin" id="dateHeureFin" class="form-control">
                    <div class="text-danger mt-1 error-msg" id="err-dateHeureFin" style="display:none; font-size:0.875em;">Veuillez choisir une date de fin.</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-activity text-success me-2"></i>Type de Consultation</label>
                <select name="typeConsultation" id="typeConsultation" class="form-select">
                    <option value="">Sélectionnez un type...</option>
                    <option value="Consultation Générale">Consultation Générale</option>
                    <option value="Suivi Médical">Suivi Médical</option>
                    <option value="Urgence">Urgence</option>
                    <option value="Spécialiste">Spécialiste</option>
                </select>
                <div class="text-danger mt-1 error-msg" id="err-typeConsultation" style="display:none; font-size:0.875em;">Veuillez sélectionner le type de consultation.</div>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-card-text text-success me-2"></i>Motif</label>
                <textarea name="motif" id="motif" class="form-control" rows="4" placeholder="Décrivez brièvement le motif de votre visite..."></textarea>
                <div class="text-danger mt-1 error-msg" id="err-motif" style="display:none; font-size:0.875em;">Le motif est obligatoire (min 5 caractères).</div>
            </div>

            <div class="text-end mt-4">
                <button type="button" class="btn btn-mc" onclick="validateForm()">Confirmer la réservation</button>
            </div>
        </form>
    </div>
</div>
</main>
</div>

<script>
function validateForm() {
    let isValid = true;
    
    // Hide all error messages
    document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.remove('is-invalid'));

    const idMedecin = document.getElementById('idMedecin');
    if (!idMedecin.value) {
        document.getElementById('err-idMedecin').style.display = 'block';
        idMedecin.classList.add('is-invalid');
        isValid = false;
    }

    const dateHeureDebut = document.getElementById('dateHeureDebut');
    if (!dateHeureDebut.value) {
        document.getElementById('err-dateHeureDebut').style.display = 'block';
        dateHeureDebut.classList.add('is-invalid');
        isValid = false;
    }

    const dateHeureFin = document.getElementById('dateHeureFin');
    if (!dateHeureFin.value) {
        document.getElementById('err-dateHeureFin').style.display = 'block';
        dateHeureFin.classList.add('is-invalid');
        isValid = false;
    }

    const typeConsultation = document.getElementById('typeConsultation');
    if (!typeConsultation.value) {
        document.getElementById('err-typeConsultation').style.display = 'block';
        typeConsultation.classList.add('is-invalid');
        isValid = false;
    }

    const motif = document.getElementById('motif');
    if (motif.value.trim().length < 5) {
        document.getElementById('err-motif').style.display = 'block';
        motif.classList.add('is-invalid');
        isValid = false;
    }

    if (isValid) {
        document.getElementById('rdvForm').submit();
    }
}
</script>

</body>
</html>
