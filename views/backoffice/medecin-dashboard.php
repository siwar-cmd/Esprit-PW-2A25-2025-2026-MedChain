<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../controllers/AmbulanceMissionController.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/RendezVousController.php';

$auth  = new AuthController();
$user  = $auth->getCurrentUser();
$userId = $_SESSION['user_id'] ?? null;
$userName = $user ? $user->getPrenom().' '.$user->getNom() : 'Médecin';
$initials = $user ? strtoupper(substr($user->getPrenom(),0,1).substr($user->getNom(),0,1)) : 'DR';

$ctrl         = new AmbulanceMissionController();
$rdvCtrl      = new RendezVousController();
$ambStats     = $ctrl->getAmbulanceStats();
$missionStats = $ctrl->getMissionStats();
$rdvStats     = $rdvCtrl->getStats('medecin', $userId);
$today        = date('l d F Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Espace Médecin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="components/medecin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include 'components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <header class="dashboard-header">
            <div>
                <h1>Espace Médecin</h1>
                <p><?= $today ?></p>
            </div>
            <div class="topbar-right">
                <span class="badge bg-light text-dark border"><i class="bi bi-eye"></i> Mode lecture</span>
            </div>
        </header>

        <div class="welcome-card" style="background: linear-gradient(135deg, var(--green-dark) 0%, var(--green) 100%); border-radius: var(--radius-lg); padding: 28px; color: white; display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; box-shadow: 0 10px 30px rgba(29, 158, 117, 0.2);">
            <div>
                <h2 style="font-family: 'Syne', sans-serif; font-size: 24px; margin-bottom: 8px;">Bonjour, Dr. <?= htmlspecialchars($userName) ?> 👋</h2>
                <p style="opacity: 0.9;">Bienvenue sur votre espace médecin MedChain. Toutes vos informations en un coup d'œil.</p>
            </div>
            <i class="bi bi-heart-pulse-fill" style="font-size: 48px; opacity: 0.2;"></i>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="stat-content"><h3><?= $rdvStats['total'] ?? 0 ?></h3><p>Rendez-vous</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;"><i class="bi bi-truck"></i></div>
                <div class="stat-content"><h3><?= $ambStats['available'] ?></h3><p>Ambulances Libres</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"><i class="bi bi-geo-alt"></i></div>
                <div class="stat-content"><h3><?= $missionStats['ongoing'] ?></h3><p>Missions en cours</p></div>
            </div>
        </div>

        <div class="charts-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
            <div class="card">
                <div class="card-header"><h2><i class="bi bi-pie-chart-fill"></i> État de la Flotte</h2></div>
                <div class="card-body" style="display: flex; justify-content: center; padding: 24px;">
                    <div style="width: 250px;"><canvas id="flotteChart"></canvas></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2><i class="bi bi-bar-chart-line-fill"></i> Activité des Missions</h2></div>
                <div class="card-body" style="padding: 24px;">
                    <div style="height: 250px;"><canvas id="missionsChart"></canvas></div>
                </div>
            </div>
        </div>

        <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
            <a href="rendezvous/medecin-index.php" class="card" style="text-decoration: none; color: inherit; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="card-body" style="padding: 24px;">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(29, 158, 117, 0.1); color: var(--green); display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class="bi bi-calendar-check"></i></div>
                        <h3 style="font-family: 'Syne', sans-serif;">Rendez-vous</h3>
                    </div>
                    <p style="color: var(--gray-500); font-size: 14px;">Consultez vos consultations planifiées avec vos patients.</p>
                </div>
            </a>
            <a href="ficherdv/medecin-index.php" class="card" style="text-decoration: none; color: inherit; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="card-body" style="padding: 24px;">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); color: #3B82F6; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class="bi bi-file-earmark-medical"></i></div>
                        <h3 style="font-family: 'Syne', sans-serif;">Fiches Médicales</h3>
                    </div>
                    <p style="color: var(--gray-500); font-size: 14px;">Accédez aux fiches de rendez-vous et consignes pré-consultation.</p>
                </div>
            </a>
        </div>
    </main>
</div>

<script>
// Charts initialization
const ctxFlotte = document.getElementById('flotteChart').getContext('2d');
new Chart(ctxFlotte, {
    type: 'doughnut',
    data: {
        labels: ['Libres', 'En Mission'],
        datasets: [{
            data: [<?= $ambStats['available'] ?>, <?= $ambStats['total'] - $ambStats['available'] ?>],
            backgroundColor: ['#1D9E75', '#F59E0B'],
            borderWidth: 0
        }]
    },
    options: { cutout: '75%', plugins: { legend: { position: 'bottom' } } }
});

const ctxMissions = document.getElementById('missionsChart').getContext('2d');
new Chart(ctxMissions, {
    type: 'bar',
    data: {
        labels: ['Missions'],
        datasets: [
            { label: 'Terminées', data: [<?= $missionStats['completed'] ?? 0 ?>], backgroundColor: '#3B82F6', borderRadius: 8 },
            { label: 'En cours', data: [<?= $missionStats['ongoing'] ?>], backgroundColor: '#F43F5E', borderRadius: 8 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>
