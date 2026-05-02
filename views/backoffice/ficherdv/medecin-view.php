<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';
$ficheController = new FicheRendezVousController();
$userId = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: medecin-index.php");
    exit;
}

$fiche = $ficheController->getFicheById($_GET['id']);

if (!$fiche) {
    header("Location: medecin-index.php");
    exit;
}

// Stats for sidebar
require_once __DIR__ . '/../../../controllers/RendezVousController.php';
$rdvController = new RendezVousController();
$stats = $rdvController->getStats('medecin', $userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Fiche #<?= $fiche['idFiche'] ?> - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        :root {
            --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F0FDF9;
            --navy: #1E3A52; --gray-700: #374151; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --shadow-green: 0 8px 30px rgba(29,158,117,.18);
            --radius-sm: 8px; --radius-md: 12px; --radius-lg: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: #f0faf6; color: var(--gray-700); min-height: 100vh; }
        
        .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        
        /* Sidebar */
        .dashboard-sidebar { background: linear-gradient(160deg, #ffffff 0%, #f0fdf9 60%, #e6faf3 100%); border-right: 1px solid rgba(29,158,117,.15); position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; box-shadow: 4px 0 24px rgba(29,158,117,.08); }
        .sidebar-logo-zone { padding: 26px 22px 20px; border-bottom: 1px solid rgba(29,158,117,.12); }
        .sidebar-logo-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar-logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(29,158,117,.35); color: white; }
        .sidebar-logo-text { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--navy); letter-spacing: -.3px; }
        .sidebar-logo-text span { color: var(--green); }
        .sidebar-tagline { font-size: 11px; color: var(--gray-500); margin-top: 3px; letter-spacing: .03em; }
        
        .sidebar-user-card { margin: 18px 16px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-lg); padding: 18px 16px; box-shadow: var(--shadow-green); color: white; }
        .sidebar-user-avatar { width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,.25); border: 2.5px solid rgba(255,255,255,.5); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .sidebar-user-name { font-size: 15px; font-weight: 700; }
        .sidebar-user-role { font-size: 11px; opacity: 0.9; margin-top: 4px; display: flex; align-items: center; gap: 5px; }

        .sidebar-stats-widget { margin: 0 16px 12px; padding: 12px; background: white; border-radius: 12px; border: 1px solid rgba(29,158,117,0.1); }
        .sidebar-stats-label { font-size: 11px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; margin-bottom: 8px; }
        .sidebar-stats-row { display: flex; justify-content: space-between; }
        .sidebar-stat-num { font-size: 16px; font-weight: 700; color: var(--green); }
        .sidebar-stat-lbl { font-size: 10px; color: var(--gray-500); }

        .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 3px; padding: 12px; }
        .sidebar-nav-item { display: flex; align-items: center; gap: 13px; padding: 11px 14px; color: var(--gray-500); text-decoration: none; border-radius: 12px; transition: all 0.25s; font-size: 14px; font-weight: 500; }
        .sidebar-nav-item i { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(29,158,117,.08); color: var(--green); }
        .sidebar-nav-item:hover, .sidebar-nav-item.active { background: rgba(29,158,117,.07); color: var(--green-dark); }
        .sidebar-nav-item.active i { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; }
        
        .sidebar-footer { padding: 16px; border-top: 1px solid rgba(29,158,117,.10); margin-top: auto; }
        .sidebar-footer-back { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 12px; background: var(--green-pale); color: var(--green-dark); font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid rgba(29,158,117,.2); }

        .dashboard-main { padding: 32px 40px; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--navy); }
        
        .view-card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); overflow: hidden; max-width: 1000px; }
        .view-header { padding: 30px; background: linear-gradient(135deg, var(--navy), #2C4B64); color: white; display: flex; justify-content: space-between; align-items: flex-start; }
        .view-header-title h2 { font-family: 'Syne', sans-serif; font-size: 24px; margin-bottom: 10px; }
        .view-header-title p { opacity: 0.8; font-size: 14px; }
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .badge-green { background: var(--green); color: white; }
        
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
        
        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.25s; }
        .btn-primary { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; }
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
        <a href="../../frontoffice/home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div>
            <div class="sidebar-logo-text">Med<span>Chain</span></div>
            <div class="sidebar-tagline">Espace Médecin</div>
          </div>
        </a>
      </div>
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-badge-fill"></i></div>
        <div class="sidebar-user-name">Dr. <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></div>
        <div class="sidebar-user-role"><i class="bi bi-heart-pulse-fill"></i> Médecin</div>
      </div>
      <div class="sidebar-stats-widget">
        <div class="sidebar-stats-label">Mes statistiques</div>
        <div class="sidebar-stats-row">
          <div><div class="sidebar-stat-num"><?= $stats['total'] ?? 0 ?></div><div class="sidebar-stat-lbl">Consultations</div></div>
          <div><div class="sidebar-stat-num"><?= $stats['ce_mois'] ?? 0 ?></div><div class="sidebar-stat-lbl">Ce mois</div></div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <a href="../rendezvous/medecin-index.php" class="sidebar-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
        <a href="medecin-index.php" class="sidebar-nav-item active"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout" onclick="confirmSwal(event, this, 'Déconnexion ?', 'Voulez-vous vraiment vous déconnecter ?')"><i class="bi bi-box-arrow-left"></i> Déconnexion</a>
        <div style="margin-top:10px;"><a href="../../frontoffice/home/index.php" class="sidebar-footer-back"><i class="bi bi-arrow-left"></i> Retour au site</a></div>
      </div>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Détails de la Fiche</h1>
            <div style="display:flex; gap:10px;">
                <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-printer"></i> Imprimer</button>
                <a href="medecin-edit.php?id=<?= $fiche['idFiche'] ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Modifier</a>
            </div>
        </div>

        <div class="view-card">
            <div class="view-header">
                <div class="view-header-title">
                    <h2>Fiche Médicale #<?= $fiche['idFiche'] ?></h2>
                    <p><i class="bi bi-activity"></i> Type de consultation : <?= htmlspecialchars($fiche['typeConsultation']) ?></p>
                </div>
                <div class="badge badge-green"><?= htmlspecialchars($fiche['modeConsultation']) ?></div>
            </div>
            
            <div class="view-content">
                <!-- Section Patient & RDV -->
                <div class="view-section">
                    <h3 class="view-section-title"><i class="bi bi-person-circle"></i> Patient & Rendez-vous</h3>
                    <div class="info-item">
                        <div class="info-label">Patient</div>
                        <div class="info-value bold"><?= htmlspecialchars($fiche['patient_nom'] . ' ' . $fiche['patient_prenom']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date de consultation</div>
                        <div class="info-value"><?= date('d/m/Y à H:i', strtotime($fiche['dateHeureDebut'])) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Motif de visite</div>
                        <div class="info-value"><?= htmlspecialchars($fiche['motifPrincipal'] ?: $fiche['motif']) ?></div>
                    </div>
                </div>

                <!-- Section Constantes -->
                <div class="view-section">
                    <h3 class="view-section-title"><i class="bi bi-activity"></i> Constantes Vitales</h3>
                    <div class="constantes-grid">
                        <div class="constante-item">
                            <div class="constante-val"><?= $fiche['tensionArterielle'] ?: '--' ?></div>
                            <div class="constante-lbl">Tension (mmHg)</div>
                        </div>
                        <div class="constante-item">
                            <div class="constante-val"><?= $fiche['poids'] ? $fiche['poids'] . ' kg' : '--' ?></div>
                            <div class="constante-lbl">Poids</div>
                        </div>
                        <div class="constante-item">
                            <div class="constante-val"><?= $fiche['taille'] ? $fiche['taille'] . ' cm' : '--' ?></div>
                            <div class="constante-lbl">Taille</div>
                        </div>
                        <div class="constante-item">
                            <div class="constante-val"><?= $fiche['temperature'] ? $fiche['temperature'] . ' °C' : '--' ?></div>
                            <div class="constante-lbl">Température</div>
                        </div>
                    </div>
                </div>

                <!-- Section Antécédents & Allergies -->
                <div class="view-section">
                    <h3 class="view-section-title"><i class="bi bi-shield-exclamation"></i> Alertes Médicales</h3>
                    <div class="info-item">
                        <div class="info-label">Antécédents</div>
                        <div class="info-value"><?= htmlspecialchars($fiche['antecedents'] ?: 'Aucun signalé') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Allergies</div>
                        <div class="info-value" style="color: <?= $fiche['allergies'] ? '#EF4444' : 'inherit' ?>; font-weight: <?= $fiche['allergies'] ? '700' : 'normal' ?>;">
                            <?= htmlspecialchars($fiche['allergies'] ?: 'Aucune signalée') ?>
                        </div>
                    </div>
                </div>

                <!-- Section Administrative -->
                <div class="view-section">
                    <h3 class="view-section-title"><i class="bi bi-cash-stack"></i> Administratif</h3>
                    <div class="info-item">
                        <div class="info-label">Tarif Consultation</div>
                        <div class="info-value bold"><?= $fiche['tarifConsultation'] ?> TND</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Statut Paiement</div>
                        <div class="info-value">
                            <span style="color: <?= $fiche['statutPaiement'] === 'Total' ? 'var(--green)' : '#F59E0B' ?>; font-weight:700;">
                                <?= htmlspecialchars($fiche['statutPaiement']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Section Prescription -->
                <div class="view-section full-width">
                    <h3 class="view-section-title"><i class="bi bi-capsule"></i> Prescription / Traitement</h3>
                    <div class="text-block" style="font-family: monospace; font-size: 14px; background: #fafafa;">
                        <?= nl2br(htmlspecialchars($fiche['prescription'] ?: 'Aucune prescription')) ?>
                    </div>
                </div>

                <!-- Section Examens complémentaires -->
                <div class="view-section">
                    <h3 class="view-section-title"><i class="bi bi-card-checklist"></i> Examens demandés</h3>
                    <div class="info-value"><?= htmlspecialchars($fiche['examensComplementaires'] ?: 'Aucun') ?></div>
                </div>

                <!-- Section Suivi -->
                <div class="view-section">
                    <h3 class="view-section-title"><i class="bi bi-calendar-event"></i> Suivi recommandé</h3>
                    <div class="info-value bold">
                        <?= $fiche['prochainRDV'] ? date('d/m/Y', strtotime($fiche['prochainRDV'])) : 'À définir' ?>
                    </div>
                </div>

                <!-- Section Pièces à apporter -->
                <div class="view-section full-width">
                    <h3 class="view-section-title"><i class="bi bi-info-circle"></i> Préparations</h3>
                    <div class="info-item">
                        <div class="info-label">Pièces à apporter</div>
                        <div class="info-value"><?= htmlspecialchars($fiche['piecesAApporter'] ?: '-') ?></div>
                    </div>
                </div>
            </div>
            
            <div style="padding: 20px 30px; background: #f8fafc; border-top: 1px solid var(--gray-200); text-align: right;">
                <a href="medecin-index.php" class="btn btn-secondary">Retour à la liste</a>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
