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

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$rdvController = new RendezVousController();
$rdv = $rdvController->getRendezVousById($_GET['id']);

if (!$rdv || $rdv['idClient'] != $currentUser->getId()) {
    header('Location: index.php');
    exit;
}

$pdo = config::getConnexion();
$req = $pdo->query("SELECT id_utilisateur, nom, prenom FROM utilisateur WHERE role = 'medecin' AND statut = 'actif'");
$medecins = $req->fetchAll(PDO::FETCH_ASSOC);

$errorMsg = null;
$errorField = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'dateHeureDebut' => $_POST['dateHeureDebut'],
        'typeConsultation' => $_POST['typeConsultation'],
        'motif' => $_POST['motif'],
        'idMedecin' => $_POST['idMedecin']
    ];
    
    $selectedDate = strtotime($_POST['dateHeureDebut']);
    if ($selectedDate < time()) {
        $errorMsg = "La date du rendez-vous ne peut pas être dans le passé.";
        $errorField = 'dateHeureDebut';
    } else {
        $result = $rdvController->updateRendezVous($rdv['idRDV'], $data);
        if ($result['success']) {
            $_SESSION['success_message'] = "Rendez-vous mis à jour avec succès.";
            header('Location: index.php');
            exit;
        } else {
            $errorMsg = $result['message'];
            $errorField = $result['field'] ?? 'global';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Modifier Rendez-vous — MedChain</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <style>
    :root {
      --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F0FDF9;
      --navy: #1E3A52; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-green: 0 8px 30px rgba(29,158,117,.18);
      --radius-sm: 8px; --radius-md: 12px; --radius-lg: 20px;
    }
    body { font-family: 'DM Sans', sans-serif; background: #f0faf6; }
    .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
    .dashboard-sidebar { background: linear-gradient(160deg,#fff 0%,#f0fdf9 60%,#e6faf3 100%); border-right:1px solid rgba(29,158,117,.15); position:sticky; top:0; height:100vh; display:flex; flex-direction:column; overflow-y:auto; box-shadow:4px 0 24px rgba(29,158,117,.08); }
    .sidebar-logo-zone { padding:26px 22px 20px; border-bottom:1px solid rgba(29,158,117,.12); }
    .sidebar-logo-link { display:flex; align-items:center; gap:12px; text-decoration:none; }
    .sidebar-logo-icon { width:42px; height:42px; background:linear-gradient(135deg,var(--green),var(--green-dark)); border-radius:13px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(29,158,117,.35); }
    .sidebar-logo-icon i { font-size:20px; color:white; }
    .sidebar-logo-text { font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--navy); }
    .sidebar-logo-text span { color:var(--green); }
    .sidebar-tagline { font-size:11px; color:var(--gray-500); margin-top:3px; }
    .sidebar-user-card { margin:18px 16px; background:linear-gradient(135deg,var(--green),var(--green-dark)); border-radius:var(--radius-lg); padding:18px 16px; box-shadow:var(--shadow-green); position:relative; overflow:hidden; }
    .sidebar-user-card::before { content:''; position:absolute; top:-20px; right:-20px; width:90px; height:90px; border-radius:50%; background:rgba(255,255,255,.1); }
    .sidebar-user-avatar { width:44px; height:44px; border-radius:50%; background:rgba(255,255,255,.25); border:2.5px solid rgba(255,255,255,.5); display:flex; align-items:center; justify-content:center; margin-bottom:12px; }
    .sidebar-user-avatar i { font-size:22px; color:white; }
    .sidebar-user-name { font-size:15px; font-weight:700; color:white; }
    .sidebar-user-role { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:rgba(255,255,255,.85); background:rgba(255,255,255,.18); padding:3px 10px; border-radius:20px; margin-top:4px; }
    .sidebar-health-widget { margin:0 16px 6px; background:var(--white); border:1px solid rgba(29,158,117,.15); border-radius:var(--radius-md); padding:14px 16px; }
    .sidebar-health-label { font-size:11px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
    .sidebar-health-bar-wrap { background:var(--gray-200); border-radius:6px; height:6px; overflow:hidden; }
    .sidebar-health-bar { height:100%; border-radius:6px; background:linear-gradient(90deg,var(--green),#34D399); animation:health-grow 1.2s ease-out forwards; }
    @keyframes health-grow { from{width:0} to{width:78%} }
    .sidebar-health-stats { display:flex; justify-content:space-between; margin-top:8px; }
    .sidebar-health-stat { font-size:12px; color:var(--gray-500); }
    .sidebar-health-stat strong { color:var(--green); font-weight:700; }
    .sidebar-nav { flex:1; display:flex; flex-direction:column; gap:3px; padding:12px 12px 0; }
    .sidebar-nav-section-label { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:#A0AEC0; padding:14px 12px 6px; }
    .sidebar-nav-item { display:flex; align-items:center; gap:13px; padding:11px 14px; color:var(--gray-500); text-decoration:none; border-radius:var(--radius-md); transition:all .25s; font-size:14px; font-weight:500; position:relative; }
    .sidebar-nav-item .nav-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; background:rgba(29,158,117,.08); color:var(--green); transition:all .25s; }
    .sidebar-nav-item:hover { background:rgba(29,158,117,.07); color:var(--green-dark); }
    .sidebar-nav-item:hover .nav-icon { background:rgba(29,158,117,.15); transform:scale(1.08); }
    .sidebar-nav-item.active { background:linear-gradient(90deg,rgba(29,158,117,.12),rgba(29,158,117,.04)); color:var(--green-dark); font-weight:600; }
    .sidebar-nav-item.active .nav-icon { background:linear-gradient(135deg,var(--green),var(--green-dark)); color:white; box-shadow:0 4px 12px rgba(29,158,117,.30); }
    .sidebar-nav-item.active::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:3px; border-radius:0 3px 3px 0; background:var(--green); }
    .sidebar-nav-item.logout { color:#E53E3E; margin:0 0 4px; }
    .sidebar-nav-item.logout .nav-icon { background:rgba(229,62,62,.08); color:#E53E3E; }
    .sidebar-nav-item.logout:hover { background:rgba(229,62,62,.07); }
    .sidebar-footer { padding:16px; border-top:1px solid rgba(29,158,117,.10); margin-top:auto; }
    .sidebar-footer-back { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:var(--radius-md); background:var(--green-pale); color:var(--green-dark); font-size:13px; font-weight:600; text-decoration:none; transition:all .2s; border:1px solid rgba(29,158,117,.2); }
    .sidebar-footer-back:hover { background:rgba(29,158,117,.15); transform:translateX(-3px); }
    .dashboard-main { padding:32px 40px; overflow-y:auto; width:100%; }
    @media (max-width:768px) { .dashboard-container{grid-template-columns:1fr} .dashboard-sidebar{position:fixed;left:-290px;top:0;bottom:0;width:280px;z-index:1000;transition:left .3s} .dashboard-sidebar.open{left:0} }
    .mc-container { max-width:800px; margin:0 auto; padding:0 20px; }
    .card-mc { background:white; border-radius:var(--radius-md); padding:30px; box-shadow:var(--shadow-sm); margin-bottom:50px; }
    .page-title { font-family:'Syne',sans-serif; font-weight:700; color:var(--navy); margin-bottom:20px; }
    .btn-mc { background:linear-gradient(135deg,var(--green),var(--green-dark)); color:white; border:none; padding:10px 20px; border-radius:8px; font-weight:600; box-shadow:0 3px 12px rgba(29,158,117,.30); transition:all .25s; }
    .btn-mc:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(29,158,117,.40); color:white; }
  </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar FrontOffice Moderne -->
    <aside class="dashboard-sidebar" id="sidebar">
      <div class="sidebar-logo-zone">
        <a href="../home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div>
            <div class="sidebar-logo-text">Med<span>Chain</span></div>
            <div class="sidebar-tagline">Espace Patient</div>
          </div>
        </a>
      </div>
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
        <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_prenom'] ?? 'Patient') ?> <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></div>
        <div class="sidebar-user-role"><i class="bi bi-heart-pulse-fill"></i> Patient</div>
      </div>
      <div class="sidebar-health-widget">
        <div class="sidebar-health-label"><i class="bi bi-activity" style="color:var(--green);margin-right:5px;"></i>Suivi de santé</div>
        <div class="sidebar-health-bar-wrap"><div class="sidebar-health-bar"></div></div>
        <div class="sidebar-health-stats">
          <span class="sidebar-health-stat">Profil <strong>78%</strong> complet</span>
          <span class="sidebar-health-stat" style="color:var(--green);"><i class="bi bi-shield-check"></i> Actif</span>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Navigation</div>
        <a href="../home/index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-house-door-fill"></i></span> Accueil</a>
        <a href="../auth/profile.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-person-fill"></i></span> Mon Profil</a>
        <div class="sidebar-nav-section-label">Mes Services</div>
        <a href="index.php" class="sidebar-nav-item active"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span> Mes Rendez-vous</a>
        <a href="../ficherdv/index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span> Mes Fiches Médicales</a>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout" onclick="confirmSwal(event, this, '')"><span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span> Déconnexion</a>
        <div style="margin-top:10px;"><a href="../home/index.php" class="sidebar-footer-back"><i class="bi bi-arrow-left-circle-fill"></i> Retour au site</a></div>
      </div>
    </aside>

    <main class="dashboard-main">
        <div class="mc-container">
            <a href="index.php" class="btn btn-outline-secondary mb-3 mt-4" style="text-decoration: none; border: 1px solid var(--gray-200); padding: 5px 15px; border-radius: 8px; color: var(--navy); display: inline-block;">&larr; Retour</a>
    <div class="card-mc">
        <h1 class="page-title">Modifier le Rendez-vous</h1>
        
        <?php if($errorMsg && $errorField === 'global'): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <form method="POST" id="rdvForm" novalidate>
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-person-badge text-success me-2"></i>Médecin</label>
                <select name="idMedecin" id="idMedecin" class="form-select <?= ($errorField === 'idMedecin') ? 'is-invalid' : '' ?>">
                    <option value="">Sélectionnez un médecin...</option>
                    <?php foreach($medecins as $med): ?>
                        <?php 
                            $isSelected = isset($_POST['idMedecin']) ? ($_POST['idMedecin'] == $med['id_utilisateur']) : ($rdv['idMedecin'] == $med['id_utilisateur']); 
                        ?>
                        <option value="<?= $med['id_utilisateur'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($med['nom'] . ' ' . $med['prenom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="text-danger mt-1 error-msg" id="err-idMedecin" style="display:none; font-size:0.875em;">Veuillez sélectionner un médecin.</div>
                <?php if($errorField === 'idMedecin'): ?>
                    <div class="text-danger mt-1" style="font-size:0.875em;"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label"><i class="bi bi-calendar-event text-success me-2"></i>Date & Heure Début</label>
                    <?php 
                        $valDate = isset($_POST['dateHeureDebut']) ? htmlspecialchars($_POST['dateHeureDebut']) : date('Y-m-d\TH:i', strtotime($rdv['dateHeureDebut']));
                    ?>
                    <input type="datetime-local" name="dateHeureDebut" id="dateHeureDebut" class="form-control <?= ($errorField === 'dateHeureDebut') ? 'is-invalid' : '' ?>" 
                           min="<?= date('Y-m-d\TH:i') ?>"
                           value="<?= $valDate ?>">
                    <div class="text-danger mt-1 error-msg" id="err-dateHeureDebut" style="display:none; font-size:0.875em;">Veuillez choisir une date de début.</div>
                    <?php if($errorField === 'dateHeureDebut'): ?>
                        <div class="text-danger mt-1" style="font-size:0.875em;"><?= htmlspecialchars($errorMsg) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-activity text-success me-2"></i>Type de Consultation</label>
                <?php 
                    $valType = isset($_POST['typeConsultation']) ? $_POST['typeConsultation'] : $rdv['typeConsultation'];
                ?>
                <select name="typeConsultation" id="typeConsultation" class="form-select <?= ($errorField === 'typeConsultation') ? 'is-invalid' : '' ?>">
                    <option value="">Sélectionnez un type...</option>
                    <option value="Consultation Générale" <?= $valType == 'Consultation Générale' ? 'selected' : '' ?>>Consultation Générale</option>
                    <option value="Suivi Médical" <?= $valType == 'Suivi Médical' ? 'selected' : '' ?>>Suivi Médical</option>
                    <option value="Urgence" <?= $valType == 'Urgence' ? 'selected' : '' ?>>Urgence</option>
                    <option value="Spécialiste" <?= $valType == 'Spécialiste' ? 'selected' : '' ?>>Spécialiste</option>
                </select>
                <div class="text-danger mt-1 error-msg" id="err-typeConsultation" style="display:none; font-size:0.875em;">Veuillez sélectionner le type de consultation.</div>
                <?php if($errorField === 'typeConsultation'): ?>
                    <div class="text-danger mt-1" style="font-size:0.875em;"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-card-text text-success me-2"></i>Motif</label>
                <?php 
                    $valMotif = isset($_POST['motif']) ? htmlspecialchars($_POST['motif']) : htmlspecialchars($rdv['motif']);
                ?>
                <textarea name="motif" id="motif" class="form-control <?= ($errorField === 'motif') ? 'is-invalid' : '' ?>" rows="4"><?= $valMotif ?></textarea>
                <div class="text-danger mt-1 error-msg" id="err-motif" style="display:none; font-size:0.875em;">Le motif est obligatoire (min 5 caractères).</div>
                <?php if($errorField === 'motif'): ?>
                    <div class="text-danger mt-1" style="font-size:0.875em;"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>
            </div>

            <div class="text-end mt-4">
                <button type="button" class="btn btn-mc" onclick="validateForm()">Mettre à jour</button>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>

