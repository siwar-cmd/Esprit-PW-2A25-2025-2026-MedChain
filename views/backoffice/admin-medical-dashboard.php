<?php
session_start();
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'medecin')) {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

// Inclusion de votre fichier config (sans le modifier)
require_once __DIR__ . '/../../config.php';

$pdo = config::getConnexion(); // utilisation de votre classe

$userRole = $_SESSION['user_role'];
$userId   = $_SESSION['user_id'];

// Récupérer tous les patients
if ($userRole === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE role = 'patient'");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Médecin : pour l'exemple on prend tous les patients
    // (vous pourrez plus tard limiter à ses propres patients)
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE role = 'patient'");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalPatients = count($patients);
$totalConsultations = array_sum(array_column($patients, 'nb_consultations'));
$patientsActifs = count(array_filter($patients, fn($p) => $p['statut'] === 'actif'));

$selectedPatientId = $_GET['patient_id'] ?? ($patients[0]['id_utilisateur'] ?? 0);
$selectedPatient = null;
foreach ($patients as $p) {
    if ($p['id_utilisateur'] == $selectedPatientId) {
        $selectedPatient = $p;
        break;
    }
}

// Seuils
$seuils = [
    'tension_sys' => ['min' => 90, 'max' => 140],
    'tension_dia' => ['min' => 60, 'max' => 90],
    'glycemie'    => ['min' => 0.7, 'max' => 1.26],
    'poids'       => ['min' => 40, 'max' => 200]
];

$alerts = [];
if ($selectedPatient) {
    if ($selectedPatient['tension_systolique'] && $selectedPatient['tension_diastolique']) {
        $sys = $selectedPatient['tension_systolique'];
        $dia = $selectedPatient['tension_diastolique'];
        if ($sys > $seuils['tension_sys']['max'] || $dia > $seuils['tension_dia']['max'])
            $alerts[] = "⚠️ Tension élevée : {$sys}/{$dia} mmHg";
        elseif ($sys < $seuils['tension_sys']['min'] || $dia < $seuils['tension_dia']['min'])
            $alerts[] = "⚠️ Tension basse : {$sys}/{$dia} mmHg";
    }
    if ($selectedPatient['glycemie']) {
        $g = $selectedPatient['glycemie'];
        if ($g > $seuils['glycemie']['max'])
            $alerts[] = "⚠️ Glycémie élevée : {$g} g/L";
        elseif ($g < $seuils['glycemie']['min'])
            $alerts[] = "⚠️ Glycémie basse : {$g} g/L";
    }
    if ($selectedPatient['poids'] && $selectedPatient['poids'] > 120)
        $alerts[] = "⚠️ Poids élevé : {$selectedPatient['poids']} kg";
}

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard médical - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;--gray-700:#374151;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#fff;--red:#EF4444;--amber:#F59E0B;--blue:#3B82F6;}
        body{font-family:'DM Sans',sans-serif;background:#f0faf6}
        .dashboard-container{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
        .dashboard-sidebar{background:linear-gradient(180deg,var(--navy) 0%,#0F172A 100%);position:sticky;top:0;height:100vh;overflow-y:auto}
        .dashboard-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:20px}
        .dashboard-logo a{display:flex;align-items:center;gap:10px;text-decoration:none}
        .dashboard-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:12px;display:flex;align-items:center;justify-content:center}
        .dashboard-logo-icon i{font-size:18px;color:#fff}
        .dashboard-logo-text{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#fff}
        .dashboard-logo-text span{color:var(--green)}
        .dashboard-nav{flex:1;display:flex;flex-direction:column;gap:4px;padding:0 12px}
        .dashboard-nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#94A3B8;text-decoration:none;border-radius:12px;transition:all .3s;font-size:14px}
        .dashboard-nav-item i{font-size:18px;width:24px}
        .dashboard-nav-item:hover{background:rgba(255,255,255,.1);color:#fff}
        .dashboard-nav-item.active{background:rgba(29,158,117,.2);color:var(--green)}
        .dashboard-nav-item.logout{margin-top:auto;margin-bottom:20px;color:#F87171}
        .dashboard-nav-title{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748B;padding:16px 16px 8px;font-weight:600}
        .dashboard-main{padding:32px 40px;overflow-y:auto}
        .page-header{margin-bottom:28px}
        .page-header h1{font-family:'Syne',sans-serif;font-size:28px;color:var(--navy)}
        .card{background:#fff;border-radius:20px;border:1px solid var(--gray-200);box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:24px;overflow:hidden}
        .card-header{padding:16px 22px;border-bottom:1px solid var(--gray-200);font-weight:600;display:flex;justify-content:space-between;align-items:center}
        .card-body{padding:22px}
        .alert-box{padding:14px 18px;border-radius:14px;margin-bottom:20px;display:flex;align-items:center;gap:12px}
        .alert-danger{background:#FEF2F2;border-left:4px solid var(--red);color:#B91C1C}
        .alert-warning{background:#FFFBEB;border-left:4px solid var(--amber);color:#B45309}
        .alert-success{background:#F0FDF4;border-left:4px solid #22C55E;color:#166534}
        .stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
        .stat-card{background:#fff;border-radius:20px;padding:20px;border:1px solid var(--gray-200);text-align:center}
        .stat-number{font-family:'Syne',sans-serif;font-size:32px;font-weight:800;color:var(--navy)}
        .stat-label{color:var(--gray-500);margin-top:6px}
        .metrics-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
        .metric{background:#F8FAFC;border-radius:16px;padding:16px;text-align:center}
        .metric-value{font-size:28px;font-weight:700;color:var(--navy)}
        .metric-label{font-size:12px;color:var(--gray-500);margin-top:4px}
        .metric-unit{font-size:11px;color:var(--gray-400)}
        select.form-select{padding:8px 12px;border-radius:12px;border:1px solid var(--gray-200);background:#fff}
        .alert-close{background:none;border:none;font-size:20px;margin-left:auto;cursor:pointer}
        @media(max-width:900px){.dashboard-container{grid-template-columns:1fr}.dashboard-sidebar{display:none}.dashboard-main{padding:20px}}
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="dashboard-logo">
            <a href="admin-dashboard.php">
                <div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
                <div class="dashboard-logo-text">Med<span>Chain</span></div>
            </a>
        </div>
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="admin-dashboard.php" class="dashboard-nav-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="admin-users.php" class="dashboard-nav-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
            <div class="dashboard-nav-title">Médical</div>
            <a href="admin-medecin.php" class="dashboard-nav-item"><i class="bi bi-heart-pulse-fill"></i> Médecins</a>
            <a href="admin-patient.php" class="dashboard-nav-item"><i class="bi bi-person-lines-fill"></i> Patients</a>
            <a href="admin-medical-dashboard.php" class="dashboard-nav-item active"><i class="bi bi-activity"></i> Suivi santé avancé</a>
            <div class="dashboard-nav-title">Gestion</div>
            <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
            <a href="../../controllers/logout.php" class="dashboard-nav-item logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <div class="page-header">
            <h1><i class="bi bi-heart-pulse-fill" style="color:var(--green)"></i> Dashboard médical intelligent</h1>
        </div>

        <?php if (!empty($alerts)): ?>
            <?php foreach ($alerts as $alert): ?>
                <div class="alert-box alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= e($alert) ?>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert-box alert-success">
                <i class="bi bi-check-circle-fill"></i> Aucune anomalie détectée pour le patient sélectionné.
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= e($totalPatients) ?></div>
                <div class="stat-label">Patients suivis</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= e($totalConsultations) ?></div>
                <div class="stat-label">Consultations totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= e($patientsActifs) ?></div>
                <div class="stat-label">Patients actifs</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-person-circle"></i> Sélectionner un patient</span>
                <select id="patientSelect" class="form-select" onchange="location.href='?patient_id='+this.value">
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= e($p['id_utilisateur']) ?>" <?= ($selectedPatientId == $p['id_utilisateur']) ? 'selected' : '' ?>>
                            <?= e($p['prenom'] . ' ' . $p['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($selectedPatient): ?>
            <div class="card">
                <div class="card-header"><span><i class="bi bi-heart-fill"></i> Dernières mesures de santé</span></div>
                <div class="card-body">
                    <div class="metrics-grid">
                        <div class="metric">
                            <div class="metric-value"><?= e($selectedPatient['poids'] ?? '—') ?> <span class="metric-unit">kg</span></div>
                            <div class="metric-label">Poids</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?= e($selectedPatient['tension_systolique'] ?? '—') ?> / <?= e($selectedPatient['tension_diastolique'] ?? '—') ?></div>
                            <div class="metric-label">Tension (mmHg)</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?= e($selectedPatient['glycemie'] ?? '—') ?> <span class="metric-unit">g/L</span></div>
                            <div class="metric-label">Glycémie</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?= e($selectedPatient['nb_consultations'] ?? 0) ?></div>
                            <div class="metric-label">Consultations</div>
                        </div>
                    </div>
                    <?php if (!empty($selectedPatient['date_derniere_mesure'])): ?>
                        <div class="text-muted text-center mt-3" style="font-size:12px">
                            📅 Dernière mise à jour : <?= date('d/m/Y H:i', strtotime($selectedPatient['date_derniere_mesure'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert-box alert-warning">Aucun patient trouvé dans la base.</div>
        <?php endif; ?>
    </main>
</div>
<script>
    document.querySelectorAll('.alert-close').forEach(btn => btn.addEventListener('click', () => btn.closest('.alert-box').remove()));
    setTimeout(() => document.querySelectorAll('.alert-box').forEach(a => a.remove()), 8000);
</script>
</body>
</html>