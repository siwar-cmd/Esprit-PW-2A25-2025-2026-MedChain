<?php
session_start();

if (!isset($_SESSION['user_role'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once "../../../controllers/controller_distribution.php";

$c = new DistributionController();

/* ================= SEARCH ================= */
$search = $_GET['search'] ?? null;

$data = $search ? $c->search($search) : $c->list();

/* ================= STATISTICS ================= */
$stats = [];

foreach ($data as $d) {
    $key = $d['patient'];
    $stats[$key] = ($stats[$key] ?? 0) + $d['quantite_distribuee'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Distributions - MedChain</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --green:#1D9E75;
            --green-dark:#0F6E56;
            --navy:#1E3A52;
            --white:#fff;
        }

        body {
            font-family:'DM Sans', sans-serif;
            background:#f5faf7;
        }

        .dashboard-container {
            display:grid;
            grid-template-columns:260px 1fr;
            min-height:100vh;
        }

        /* ================= SIDEBAR ================= */
        .dashboard-sidebar {
            background:linear-gradient(180deg, var(--navy), #0F172A);
            color:white;
            height:100vh;
            position:sticky;
            top:0;
        }

        .logo {
            padding:25px;
            font-family:'Syne';
            font-size:22px;
            font-weight:700;
        }

        .logo span { color:var(--green); }

        .nav {
            padding:10px;
        }

        .nav a {
            display:flex;
            gap:10px;
            padding:12px 16px;
            color:#cbd5e1;
            text-decoration:none;
            border-radius:10px;
        }

        .nav a:hover {
            background:rgba(255,255,255,0.08);
            color:white;
        }

        .nav a.active {
            background:rgba(29,158,117,0.2);
            color:var(--green);
        }

        /* ================= MAIN ================= */
        .dashboard-main {
            padding:30px;
        }

        .card {
            background:white;
            padding:20px;
            border-radius:12px;
            margin-bottom:20px;
        }

        .topbar {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        }

        input {
            padding:10px;
            border:1px solid #ddd;
            border-radius:8px;
            width:250px;
        }

        .btn {
            padding:10px 14px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            text-decoration:none;
            color:white;
            background:var(--green);
        }

        .pdf-btn {
            background:#3b82f6;
        }

        table {
            width:100%;
            border-collapse:collapse;
        }

        th {
            background:var(--green);
            color:white;
            padding:10px;
        }

        td {
            padding:10px;
            border-bottom:1px solid #eee;
        }
    </style>
</head>

<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="dashboard-sidebar">
        <div class="logo">Med<span>Chain</span></div>

        <div class="nav">
            <a href="admin-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="crud_distribution.php" class="active"><i class="bi bi-arrow-left-right"></i> Distributions</a>
            <a href="lots.php"><i class="bi bi-box-seam"></i> Lots</a>
            <a href="../auth/profile.php"><i class="bi bi-person"></i> Profil</a>
            <a href="../../../controllers/logout.php" style="color:#ef4444;"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="dashboard-main">

        <!-- TOP BAR -->
        <div class="card topbar">
            <h2>📦 Distributions</h2>

            <form method="GET">
                <input type="text" name="search" placeholder="Search patient..."
                       value="<?= htmlspecialchars($search ?? '') ?>">
                <button class="btn">Search</button>
            </form>

            <!-- FIXED PDF BUTTON -->
            <a href="export_distribution_pdf.php" class="btn pdf-btn">
                Export PDF
            </a>
        </div>

        <!-- TABLE -->
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Lot</th>
                        <th>Date</th>
                        <th>Quantité</th>
                        <th>Patient</th>
                        <th>Responsable</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="6" style="text-align:center;">No data</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $d): ?>
                        <tr>
                            <td><?= $d['id_distribution'] ?></td>
                            <td><?= $d['id_lot'] ?></td>
                            <td><?= $d['date_distribution'] ?></td>
                            <td><?= $d['quantite_distribuee'] ?></td>
                            <td><?= htmlspecialchars($d['patient']) ?></td>
                            <td><?= htmlspecialchars($d['responsable']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- CHART -->
        <div class="card">
            <h3>📊 Statistiques par patient</h3>
            <canvas id="chart"></canvas>
        </div>

    </main>
</div>

<script>
const ctx = document.getElementById('chart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($stats)) ?>,
        datasets: [{
            label: 'Quantité',
            data: <?= json_encode(array_values($stats)) ?>,
            backgroundColor: '#1D9E75'
        }]
    }
});
</script>

</body>
</html>