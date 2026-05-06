<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();

$startDate  = $_GET['start_date']  ?? date('Y-m-01');
$endDate    = $_GET['end_date']    ?? date('Y-m-d');
$reportType = $_GET['type']        ?? 'statistics';

$dashboardData = $adminController->dashboard();
$stats         = $dashboardData['stats'] ?? [];
$usersResult   = $adminController->getAllUsers();
$allUsers      = $usersResult['success'] ? $usersResult['users'] : [];

$totalUsers        = $stats['total']         ?? 0;
$newThisMonth      = $stats['new_this_month'] ?? 0;
$activeUsers       = 0;
$totalDoctors      = 0;
$pendingDoctorsCount = 0;

$rolesSummary = [
    'admin'   => ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0],
    'medecin' => ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0],
    'user'    => ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0],
    'patient' => ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0],
];

foreach ($allUsers as $user) {
    $role   = $user['role']   ?? 'user';
    $status = $user['statut'] ?? 'inactif';
    if (!isset($rolesSummary[$role])) {
        $rolesSummary[$role] = ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0];
    }
    $rolesSummary[$role]['total']++;
    if (isset($rolesSummary[$role][$status])) {
        $rolesSummary[$role][$status]++;
    }
    if ($role === 'medecin') {
        $totalDoctors++;
        if ($status === 'en_attente') $pendingDoctorsCount++;
    }
    if ($status === 'actif') $activeUsers++;
}

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function fmt($n) { return number_format((int)$n, 0, ',', ' '); }
$generatedAt = date('d/m/Y à H:i');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport Statistiques – MedChain</title>
<style>
  /* ── Reset & base ── */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #fff; }

  /* ── Print-only trigger: hide controls ── */
  @media print {
    .no-print { display: none !important; }
    body { margin: 0; }
    .page-break { page-break-before: always; }
  }

  /* ── Screen controls bar ── */
  .controls {
    position: fixed; top: 0; left: 0; right: 0; z-index: 999;
    background: #1E3A52; color: #fff;
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 24px; gap: 12px;
  }
  .controls .logo { display: flex; align-items: center; gap: 8px; font-size: 18px; font-weight: 700; }
  .controls .logo span { color: #1D9E75; }
  .controls-actions { display: flex; gap: 10px; }
  .btn { padding: 8px 18px; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .2s; }
  .btn-green  { background: #1D9E75; color: #fff; }
  .btn-green:hover { background: #0F6E56; }
  .btn-outline { background: transparent; border: 2px solid rgba(255,255,255,.4); color: #fff; }
  .btn-outline:hover { background: rgba(255,255,255,.1); }

  /* ── Document wrapper ── */
  .document { max-width: 900px; margin: 72px auto 40px; padding: 0 24px; }

  /* ── Header band ── */
  .doc-header {
    background: linear-gradient(135deg, #1E3A52 0%, #0F172A 100%);
    border-radius: 14px; padding: 30px 36px; color: #fff;
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 28px;
  }
  .doc-header-left h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
  .doc-header-left p  { font-size: 13px; color: #94a3b8; }
  .doc-header-right   { text-align: right; font-size: 12px; color: #94a3b8; line-height: 1.8; }
  .doc-header-right strong { color: #fff; }
  .medchain-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #1D9E75; padding: 4px 12px; border-radius: 20px;
    font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 10px;
  }

  /* ── Section title ── */
  .section-title {
    font-size: 15px; font-weight: 700; color: #1E3A52;
    border-left: 4px solid #1D9E75; padding-left: 12px;
    margin: 28px 0 16px;
  }

  /* ── KPI cards row ── */
  .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 10px; }
  .kpi-card {
    border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px;
    display: flex; align-items: center; gap: 14px;
  }
  .kpi-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
  }
  .kpi-icon.blue   { background: #eff6ff; color: #3b82f6; }
  .kpi-icon.green  { background: #f0fdf4; color: #22c55e; }
  .kpi-icon.teal   { background: #f0fdfa; color: #14b8a6; }
  .kpi-icon.orange { background: #fff7ed; color: #f97316; }
  .kpi-val  { font-size: 24px; font-weight: 700; color: #1e293b; line-height: 1; }
  .kpi-lbl  { font-size: 11px; color: #64748b; margin-top: 3px; }

  /* ── Table ── */
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  thead tr { background: #1E3A52; color: #fff; }
  thead th { padding: 11px 14px; text-align: left; font-weight: 600; font-size: 12px; }
  tbody tr:nth-child(even) { background: #f8fafc; }
  tbody tr:hover { background: #f0fdf4; }
  tbody td { padding: 11px 14px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
  .badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
  }
  .badge-admin   { background: #fef2f2; color: #dc2626; }
  .badge-medecin { background: #eff6ff; color: #2563eb; }
  .badge-user    { background: #f0fdf4; color: #16a34a; }
  .badge-patient { background: #fefce8; color: #ca8a04; }
  .badge-actif   { background: #f0fdf4; color: #16a34a; }
  .badge-inactif { background: #f1f5f9; color: #64748b; }
  .badge-attente { background: #fff7ed; color: #c2410c; }
  .num-cell { font-weight: 700; color: #1e293b; }

  /* ── Progress bar ── */
  .progress-wrap { display: flex; align-items: center; gap: 10px; }
  .progress-bg   { flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
  .progress-fill { height: 100%; border-radius: 4px; background: #1D9E75; }
  .progress-pct  { font-size: 11px; font-weight: 600; color: #64748b; min-width: 36px; text-align: right; }

  /* ── Users table ── */
  .user-name { font-weight: 600; color: #1e293b; }
  .user-email { font-size: 11px; color: #64748b; }

  /* ── Footer ── */
  .doc-footer {
    margin-top: 40px; padding-top: 16px; border-top: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: center;
    font-size: 11px; color: #94a3b8;
  }
  .doc-footer strong { color: #1D9E75; }
</style>
</head>
<body>

<!-- ── Controls bar (screen only) ── -->
<div class="controls no-print">
  <div class="logo">&#43; Med<span>Chain</span> — Export PDF</div>
  <div class="controls-actions">
    <a href="admin-reports-statistics.php" class="btn btn-outline">&#8592; Retour</a>
    <button class="btn btn-green" onclick="window.print()">&#128438; Imprimer / Enregistrer en PDF</button>
  </div>
</div>

<!-- ── Document ── -->
<div class="document">

  <!-- Header band -->
  <div class="doc-header">
    <div class="doc-header-left">
      <div class="medchain-badge">&#43; MedChain</div>
      <h1>Rapport Statistiques</h1>
      <p>Analyse complète des données du système</p>
    </div>
    <div class="doc-header-right">
      <strong>Généré le</strong><br><?= $generatedAt ?><br><br>
      <strong>Période</strong><br>
      <?= e(date('d/m/Y', strtotime($startDate))) ?> → <?= e(date('d/m/Y', strtotime($endDate))) ?><br><br>
      <strong>Administrateur</strong><br>
      <?= e(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Admin')) ?>
    </div>
  </div>

  <!-- KPIs -->
  <div class="section-title">Vue d'ensemble</div>
  <div class="kpi-row">
    <div class="kpi-card">
      <div class="kpi-icon blue">&#128101;</div>
      <div>
        <div class="kpi-val"><?= fmt($totalUsers) ?></div>
        <div class="kpi-lbl">Total utilisateurs</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon green">&#10003;</div>
      <div>
        <div class="kpi-val"><?= fmt($activeUsers) ?></div>
        <div class="kpi-lbl">Utilisateurs actifs</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon teal">&#43;</div>
      <div>
        <div class="kpi-val"><?= fmt($newThisMonth) ?></div>
        <div class="kpi-lbl">Nouveaux ce mois</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon orange">&#8987;</div>
      <div>
        <div class="kpi-val"><?= fmt($pendingDoctorsCount) ?></div>
        <div class="kpi-lbl">En attente</div>
      </div>
    </div>
  </div>

  <!-- Répartition par rôle -->
  <div class="section-title">Répartition par Rôle</div>
  <table>
    <thead>
      <tr>
        <th>Rôle</th>
        <th>Total</th>
        <th>Actifs</th>
        <th>Inactifs</th>
        <th>En attente</th>
        <th>Part</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rolesSummary as $role => $rs):
        if ($rs['total'] === 0) continue;
        $pct = $totalUsers > 0 ? round($rs['total'] / $totalUsers * 100) : 0;
      ?>
      <tr>
        <td><span class="badge badge-<?= e($role) ?>"><?= ucfirst(e($role)) ?></span></td>
        <td class="num-cell"><?= fmt($rs['total']) ?></td>
        <td><span class="badge badge-actif"><?= fmt($rs['actif'] ?? 0) ?></span></td>
        <td><span class="badge badge-inactif"><?= fmt($rs['inactif'] ?? 0) ?></span></td>
        <td><span class="badge badge-attente"><?= fmt($rs['en_attente'] ?? 0) ?></span></td>
        <td>
          <div class="progress-wrap">
            <div class="progress-bg"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
            <span class="progress-pct"><?= $pct ?>%</span>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Liste des utilisateurs récents -->
  <?php
    $recentUsers = array_slice($allUsers, 0, 20);
  ?>
  <?php if (!empty($recentUsers)): ?>
  <div class="section-title" style="margin-top:32px;">Derniers utilisateurs inscrits (20)</div>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Nom / Email</th>
        <th>Rôle</th>
        <th>Statut</th>
        <th>Date inscription</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recentUsers as $i => $u): ?>
      <tr>
        <td style="color:#94a3b8;"><?= $u['id_utilisateur'] ?></td>
        <td>
          <div class="user-name"><?= e($u['prenom'] . ' ' . $u['nom']) ?></div>
          <div class="user-email"><?= e($u['email']) ?></div>
        </td>
        <td><span class="badge badge-<?= e($u['role']) ?>"><?= ucfirst(e($u['role'])) ?></span></td>
        <td><span class="badge badge-<?= e($u['statut']) ?>"><?= ucfirst(e($u['statut'])) ?></span></td>
        <td><?= date('d/m/Y', strtotime($u['date_inscription'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- Footer -->
  <div class="doc-footer">
    <span>Rapport généré par <strong>MedChain Admin</strong> — <?= $generatedAt ?></span>
    <span>Période : <?= e(date('d/m/Y', strtotime($startDate))) ?> → <?= e(date('d/m/Y', strtotime($endDate))) ?></span>
  </div>

</div><!-- /document -->

<script>
// Auto-trigger print dialog if ?print=1 is passed
const params = new URLSearchParams(window.location.search);
if (params.get('print') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
}
</script>
</body>
</html>