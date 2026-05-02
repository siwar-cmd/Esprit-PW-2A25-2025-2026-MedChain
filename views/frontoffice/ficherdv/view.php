<?php
session_start();
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';

$authController = new AuthController();
if (!$authController->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$currentUser = $authController->getCurrentUser();
if ($currentUser->getRole() !== 'patient') {
    header('Location: ../../../views/backoffice/admin-dashboard.php');
    exit;
}

$ficheController = new FicheRendezVousController();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$fiche = $ficheController->getFicheById($_GET['id']);

// Vérifier que la fiche appartient bien au patient connecté
$pdo = config::getConnexion();
$stmt = $pdo->prepare("SELECT idClient FROM rendezvous WHERE idRDV = ?");
$stmt->execute([$fiche['idRDV']]);
$rdv = $stmt->fetch();

if (!$fiche || $rdv['idClient'] != $currentUser->getId()) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Fiche Médicale #<?= $fiche['idFiche'] ?> - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        :root {
            --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F0FDF9;
            --navy: #1E3A52; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-green: 0 8px 30px rgba(29,158,117,.18);
            --radius-md: 12px; --radius-lg: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; min-height: 100vh; }
        
        .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        
        /* Sidebar FrontOffice */
        .dashboard-sidebar { background: linear-gradient(160deg,#fff 0%,#f0fdf9 60%,#e6faf3 100%); border-right:1px solid rgba(29,158,117,.15); position:sticky; top:0; height:100vh; display:flex; flex-direction:column; overflow-y:auto; box-shadow:4px 0 24px rgba(29,158,117,.08); }
        .sidebar-logo-zone { padding:26px 22px 20px; border-bottom:1px solid rgba(29,158,117,.12); }
        .sidebar-logo-link { display:flex; align-items:center; gap:12px; text-decoration:none; }
        .sidebar-logo-icon { width:42px; height:42px; background:linear-gradient(135deg,var(--green),var(--green-dark)); border-radius:13px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(29,158,117,.35); }
        .sidebar-logo-icon i { font-size:20px; color:white; }
        .sidebar-logo-text { font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--navy); }
        .sidebar-logo-text span { color:var(--green); }
        .sidebar-user-card { margin:18px 16px; background:linear-gradient(135deg,var(--green),var(--green-dark)); border-radius:var(--radius-lg); padding:18px 16px; box-shadow:var(--shadow-green); position:relative; overflow:hidden; color: white; }
        .sidebar-user-avatar { width:44px; height:44px; border-radius:50%; background:rgba(255,255,255,.25); border:2.5px solid rgba(255,255,255,.5); display:flex; align-items:center; justify-content:center; margin-bottom:12px; }
        .sidebar-user-name { font-size:15px; font-weight:700; }
        .sidebar-user-role { display:inline-flex; align-items:center; gap:5px; font-size:11px; opacity: 0.9; background:rgba(255,255,255,0.18); padding:3px 10px; border-radius:20px; margin-top:4px; }
        .sidebar-nav { flex:1; display:flex; flex-direction:column; gap:3px; padding:12px; }
        .sidebar-nav-item { display:flex; align-items:center; gap:13px; padding:11px 14px; color:var(--gray-500); text-decoration:none; border-radius:var(--radius-md); transition:all .25s; font-size:14px; font-weight:500; }
        .sidebar-nav-item .nav-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:rgba(29,158,117,.08); color:var(--green); }
        .sidebar-nav-item:hover, .sidebar-nav-item.active { background:rgba(29,158,117,.07); color:var(--green-dark); }
        .sidebar-nav-item.active .nav-icon { background:linear-gradient(135deg,var(--green),var(--green-dark)); color:white; }
        
        .dashboard-main { padding: 32px 40px; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--navy); }
        
        .view-card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); overflow: hidden; max-width: 900px; }
        .view-header { padding: 30px; background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; display: flex; justify-content: space-between; align-items: flex-start; }
        .view-header-title h2 { font-family: 'Syne', sans-serif; font-size: 24px; margin-bottom: 10px; }
        .view-header-title p { opacity: 0.8; font-size: 14px; }
        
        .view-content { padding: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .view-section { margin-bottom: 30px; }
        .view-section-title { font-size: 14px; font-weight: 700; color: var(--green); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .view-section-title i { font-size: 18px; }
        
        .info-item { margin-bottom: 15px; }
        .info-label { font-size: 12px; color: var(--gray-500); font-weight: 600; margin-bottom: 4px; }
        .info-value { font-size: 15px; color: var(--navy); font-weight: 500; }
        .info-value.bold { font-weight: 700; }
        
        .constantes-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; background: var(--green-pale); padding: 20px; border-radius: 15px; border: 1px solid rgba(29,158,117,0.1); }
        .constante-item { text-align: center; }
        .constante-val { font-size: 18px; font-weight: 700; color: var(--green-dark); }
        .constante-lbl { font-size: 11px; color: var(--gray-500); }
        
        .full-width { grid-column: 1 / -1; }
        .text-block { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--gray-200); line-height: 1.6; }
        
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.3s; }
        .btn-primary { background: var(--green); color: white; }
        .btn-secondary { background: #f1f5f9; color: var(--navy); }
        
        @media print {
            .dashboard-sidebar, .btn, .dashboard-header { display: none !important; }
            .dashboard-container { display: block; }
            .dashboard-main { padding: 0; }
            .view-card { box-shadow: none; border: 1px solid #eee; max-width: none; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
      <div class="sidebar-logo-zone">
        <a href="../home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div class="sidebar-logo-text">Med<span>Chain</span></div>
        </a>
      </div>
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
        <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></div>
        <div class="sidebar-user-role">Patient</div>
      </div>
      <nav class="sidebar-nav">
        <a href="../home/index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-house-door-fill"></i></span> Accueil</a>
        <a href="../rendezvous/index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span> Mes Rendez-vous</a>
        <a href="index.php" class="sidebar-nav-item active"><span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span> Mes Fiches Médicales</a>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout" onclick="confirmSwal(event, this, '')"><span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span> Déconnexion</a>
      </div>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Ma Fiche Médicale</h1>
            <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-printer"></i> Imprimer</button>
        </div>

        <div class="view-card">
            <div class="view-header">
                <div class="view-header-title">
                    <h2>Résumé de Consultation</h2>
                    <p><i class="bi bi-calendar3"></i> Consultation du <?= date('d/m/Y à H:i', strtotime($fiche['dateHeureDebut'])) ?></p>
                </div>
                <div style="background:rgba(255,255,255,0.2); padding:8px 15px; border-radius:10px; font-weight:700;">Dr. <?= htmlspecialchars($fiche['medecin_nom']) ?></div>
            </div>
            
            <div class="view-content">
                <!-- Section Constantes -->
                <div class="view-section">
                    <h3 class="view-section-title"><i class="bi bi-activity"></i> Mes Constantes</h3>
                    <div class="constantes-grid">
                        <div class="constante-item">
                            <div class="constante-val"><?= $fiche['tensionArterielle'] ?: '--' ?></div>
                            <div class="constante-lbl">Tension</div>
                        </div>
                        <div class="constante-item">
                            <div class="constante-val"><?= $fiche['poids'] ? $fiche['poids'] . ' kg' : '--' ?></div>
                            <div class="constante-lbl">Mon Poids</div>
                        </div>
                        <div class="constante-item">
                            <div class="constante-val"><?= $fiche['temperature'] ? $fiche['temperature'] . ' °C' : '--' ?></div>
                            <div class="constante-lbl">Température</div>
                        </div>
                        <div class="constante-item">
                            <div class="constante-val"><?= $fiche['taille'] ? $fiche['taille'] . ' cm' : '--' ?></div>
                            <div class="constante-lbl">Taille</div>
                        </div>
                    </div>
                </div>

                <!-- Section Diagnostic -->
                <div class="view-section full-width">
                    <h3 class="view-section-title"><i class="bi bi-clipboard2-pulse"></i> Conclusion du Docteur</h3>
                    <div class="text-block" style="border-left: 5px solid var(--green); font-weight: 500;">
                        <?= nl2br(htmlspecialchars($fiche['diagnostic'] ?: 'Aucune conclusion spécifiée')) ?>
                    </div>
                </div>

                <!-- Section Prescription -->
                <div class="view-section full-width">
                    <h3 class="view-section-title"><i class="bi bi-capsule"></i> Mon Traitement</h3>
                    <div class="text-block" style="background: var(--green-pale); border-color: rgba(29,158,117,0.2);">
                        <?= nl2br(htmlspecialchars($fiche['prescription'] ?: 'Aucun traitement prescrit')) ?>
                    </div>
                </div>

                <!-- Section Suivi -->
                <div class="view-section">
                    <h3 class="view-section-title"><i class="bi bi-calendar-event"></i> Prochain RDV conseillé</h3>
                    <div class="info-value bold" style="color: var(--green-dark);">
                        <?= $fiche['prochainRDV'] ? date('d/m/Y', strtotime($fiche['prochainRDV'])) : 'À définir selon besoin' ?>
                    </div>
                </div>

                <!-- Section Examens complémentaires -->
                <div class="view-section">
                    <h3 class="view-section-title"><i class="bi bi-card-checklist"></i> Examens à faire</h3>
                    <div class="info-value"><?= htmlspecialchars($fiche['examensComplementaires'] ?: 'Aucun examen demandé') ?></div>
                </div>

                <!-- Section Consignes -->
                <div class="view-section full-width">
                    <h3 class="view-section-title"><i class="bi bi-info-circle"></i> Consignes & Préparations</h3>
                    <div class="info-item">
                        <div class="info-label">Consignes du médecin</div>
                        <div class="info-value"><?= htmlspecialchars($fiche['consignesAvantConsultation'] ?: '-') ?></div>
                    </div>
                </div>
            </div>
            
            <div style="padding: 20px 30px; background: #f8fafc; border-top: 1px solid var(--gray-200); text-align: right;">
                <a href="index.php" class="btn btn-secondary">Retour à l'historique</a>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
