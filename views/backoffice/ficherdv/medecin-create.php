<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';
require_once __DIR__ . '/../../../controllers/RendezVousController.php';
require_once __DIR__ . '/../../../config.php';

$ficheController = new FicheRendezVousController();
$rdvController = new RendezVousController();
$userId = $_SESSION['user_id'];
$pdo = config::getConnexion();

$errors = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idRDV = $_POST['idRDV'] ?? '';
    $motifPrincipal = $_POST['motifPrincipal'] ?? '';

    if (empty($idRDV)) {
        $errors['idRDV'] = "Le rendez-vous concerné est obligatoire.";
    }
    if (empty(trim($motifPrincipal))) {
        $errors['motifPrincipal'] = "Le motif principal est obligatoire.";
    }

    if (empty($errors)) {
        $consignes = $_POST['consignes'] ?? [];
        if (!empty($_POST['autre_consigne'])) {
            $consignes[] = "Autre : " . $_POST['autre_consigne'];
        }
        $consignes_str = implode(', ', $consignes);

        $data = [
            'idRDV' => $idRDV,
            'dateGeneration' => date('Y-m-d'),
            'piecesAApporter' => $_POST['piecesAApporter'] ?? '',
            'consignesAvantConsultation' => $consignes_str,
            'tarifConsultation' => $_POST['tarifConsultation'] ?? 0,
            'modeRemboursement' => '', 
            'emailEnvoye' => 0,
            'calendrierAjoute' => 0,
            'antecedents' => $_POST['antecedents'] ?? '',
            'allergies' => $_POST['allergies'] ?? '',
            'motifPrincipal' => $motifPrincipal,
            'modeConsultation' => $_POST['modeConsultation'] ?? 'Présentiel',
            'statutPaiement' => $_POST['statutPaiement'] ?? 'En attente',
            'tensionArterielle' => $_POST['tensionArterielle'] ?? '',
            'poids' => $_POST['poids'] !== '' ? (float)$_POST['poids'] : null,
            'taille' => $_POST['taille'] !== '' ? (int)$_POST['taille'] : null,
            'temperature' => $_POST['temperature'] !== '' ? (float)$_POST['temperature'] : null,
            'examenClinique' => $_POST['examenClinique'] ?? '',
            'diagnostic' => $_POST['diagnostic'] ?? '',
            'prescription' => $_POST['prescription'] ?? '',
            'examensComplementaires' => $_POST['examensComplementaires'] ?? '',
            'observations' => $_POST['observations'] ?? '',
            'prochainRDV' => $_POST['prochainRDV'] !== '' ? $_POST['prochainRDV'] : null
        ];

        $result = $ficheController->createFiche($data);

        if ($result['success']) {
            $_SESSION['success_message'] = "Fiche créée avec succès";
            header("Location: medecin-index.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Récupérer l'ID du rendez-vous depuis l'URL
$idRDV_get = $_GET['idRDV'] ?? null;

if (!$idRDV_get) {
    header('Location: ../rendezvous/medecin-index.php');
    exit;
}

// Vérifier que le RDV appartient au médecin et n'a pas encore de fiche
$stmt = $pdo->prepare("
    SELECT r.idRDV, r.dateHeureDebut, u.nom, u.prenom 
    FROM rendezvous r 
    JOIN utilisateur u ON r.idClient = u.id_utilisateur 
    LEFT JOIN ficherendezvous f ON r.idRDV = f.idRDV 
    WHERE r.idRDV = ? AND r.idMedecin = ? AND f.idFiche IS NULL
");
$stmt->execute([$idRDV_get, $userId]);
$rdv_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rdv_info) {
    // Soit le RDV n'existe pas, soit il n'appartient pas au médecin, soit il a déjà une fiche
    $_SESSION['error_message'] = "Rendez-vous invalide ou fiche déjà existante.";
    header('Location: ../rendezvous/medecin-index.php');
    exit;
}

$stats = $rdvController->getStats('medecin', $userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Fiche - Médecin - MedChain</title>
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
        .dashboard-header { margin-bottom: 32px; }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--navy); }
        
        .card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); padding: 32px; max-width: 900px;}
        
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--navy); font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 15px; transition: all 0.2s; }
        .form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }
        
        .checkbox-container { display: flex; gap: 24px; align-items: center; flex-wrap: wrap; padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid var(--gray-200); }
        .checkbox-item { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; }
        .radio-container { display: flex; gap: 20px; flex-wrap: wrap; }
        .radio-item { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; }

        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.25s; }
        .btn-primary { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; box-shadow: 0 4px 12px rgba(29,158,117,0.2); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(29,158,117,0.3); }
        .btn-secondary { background: #f1f5f9; color: var(--navy); }
        
        .alert-error { background: #FEF2F2; color: #EF4444; padding: 15px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #EF4444; display: flex; align-items: center; gap: 10px; }
        .field-error { color: #EF4444; font-size: 12px; margin-top: 6px; font-weight: 500; }
        .is-invalid { border-color: #EF4444 !important; }
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
            <h1>Créer une Fiche Médicale</h1>
            <p>Remplissez les informations de la consultation</p>
        </div>

        <?php if($error): ?>
            <div class="alert-error"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Rendez-vous concerné</label>
                    <div style="background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--gray-200); color: var(--navy); font-weight: 600;">
                        <i class="bi bi-calendar-event" style="margin-right: 8px; color: var(--green);"></i>
                        <?= date('d/m/Y H:i', strtotime($rdv_info['dateHeureDebut'])) ?> - <?= htmlspecialchars($rdv_info['nom'] . ' ' . $rdv_info['prenom']) ?>
                    </div>
                    <input type="hidden" name="idRDV" value="<?= $rdv_info['idRDV'] ?>">
                </div>

                <div class="form-group">
                    <label>Motif principal <span style="color:red">*</span></label>
                    <input type="text" name="motifPrincipal" class="form-control <?= isset($errors['motifPrincipal']) ? 'is-invalid' : '' ?>" placeholder="ex: Douleur abdominale" value="<?= htmlspecialchars($_POST['motifPrincipal'] ?? '') ?>">
                    <?php if(isset($errors['motifPrincipal'])): ?><span class="field-error"><?= $errors['motifPrincipal'] ?></span><?php endif; ?>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                  <div class="form-group"><label>Antécédents</label><input type="text" name="antecedents" class="form-control" placeholder="ex: Diabète type 2"></div>
                  <div class="form-group"><label>Allergies</label><input type="text" name="allergies" class="form-control" placeholder="ex: Pénicilline"></div>
                </div>

                <div style="background: var(--green-pale); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(29,158,117,0.1);">
                    <h3 style="font-size: 16px; color: var(--green-dark); margin-bottom: 15px; font-family: 'Syne', sans-serif;"><i class="bi bi-activity"></i> Constantes Vitales</h3>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
                        <div class="form-group" style="margin-bottom:0;"><label>Tension Artérielle</label><input type="text" name="tensionArterielle" class="form-control" placeholder="ex: 12/8"></div>
                        <div class="form-group" style="margin-bottom:0;"><label>Poids (kg)</label><input type="number" step="0.1" name="poids" class="form-control" placeholder="ex: 75.5"></div>
                        <div class="form-group" style="margin-bottom:0;"><label>Taille (cm)</label><input type="number" name="taille" class="form-control" placeholder="ex: 175"></div>
                        <div class="form-group" style="margin-bottom:0;"><label>Température (°C)</label><input type="number" step="0.1" name="temperature" class="form-control" placeholder="ex: 37.2"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Examen Clinique</label>
                    <textarea name="examenClinique" class="form-control" rows="3" placeholder="Notes sur l'examen physique..."></textarea>
                </div>

                <div class="form-group">
                    <label>Diagnostic / Hypothèse</label>
                    <textarea name="diagnostic" class="form-control" rows="2" placeholder="Conclusion médicale..."></textarea>
                </div>

                <div class="form-group">
                    <label>Prescription / Traitement</label>
                    <textarea name="prescription" class="form-control" rows="3" placeholder="Médicaments, doses, durée..."></textarea>
                </div>

                <div class="form-group">
                    <label>Examens complémentaires demandés</label>
                    <input type="text" name="examensComplementaires" class="form-control" placeholder="ex: Radio thorax, Bilan sanguin complet">
                </div>

                <div class="form-group">
                    <label>Observations & Notes</label>
                    <textarea name="observations" class="form-control" rows="2" placeholder="Remarques additionnelles..."></textarea>
                </div>

                <div class="form-group">
                    <label>Prochain rendez-vous recommandé</label>
                    <input type="date" name="prochainRDV" class="form-control">
                </div>

                <div class="form-group">
                    <label>Consignes avant consultation</label>
                    <div class="checkbox-container">
                        <label class="checkbox-item"><input type="checkbox" name="consignes[]" value="A jeun"> A jeun</label>
                        <label class="checkbox-item"><input type="checkbox" name="consignes[]" value="Boire eau"> Boire eau</label>
                        <label class="checkbox-item"><input type="checkbox" name="consignes[]" value="Arrêter aspirine"> Arrêter aspirine</label>
                        <div style="display:flex; align-items:center; gap:8px; flex:1;">
                          <label class="checkbox-item"><input type="checkbox" id="check_autre"> Autre :</label>
                          <input type="text" name="autre_consigne" id="autre_consigne" class="form-control" placeholder="Précisez..." style="padding: 6px 12px;">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Pièces à apporter</label>
                    <input type="text" name="piecesAApporter" class="form-control" placeholder="ex: Analyse sanguine, Échographie">
                </div>

                <div style="display:grid; grid-template-columns: 150px 1fr; gap:30px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--gray-200); margin-bottom:24px;">
                    <div class="form-group" style="margin-bottom:0;"><label>Tarif (TND)</label><input type="number" step="0.5" name="tarifConsultation" class="form-control" placeholder="80.0"></div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Paiement</label>
                        <div class="radio-container">
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="En attente" checked> En attente</label>
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="Payé partiellement"> Payé partiellement</label>
                            <label class="radio-item"><input type="radio" name="statutPaiement" value="Total"> Total</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mode de consultation</label>
                    <div class="radio-container">
                        <label class="radio-item"><input type="radio" name="modeConsultation" value="Présentiel" checked> Présentiel</label>
                        <label class="radio-item"><input type="radio" name="modeConsultation" value="Téléconsultation"> Téléconsultation</label>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Créer la fiche</button>
                    <a href="../rendezvous/medecin-index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </main>
</div>
<script>
    document.getElementById('autre_consigne').addEventListener('input', function() {
        document.getElementById('check_autre').checked = (this.value.trim() !== '');
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
