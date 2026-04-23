<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Fiches RDV - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container { display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; margin-top: 30px; }
        .chart-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 45%; min-width: 300px; }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo"><a href="index.php"><img src="logo.PNG" alt="MedChain Logo"></a></div>
            <ul class="nav-links">
                <li class="dropdown">
                    <a href="#" class="dropbtn">Flotte & Missions ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=ambulance">Gestion Ambulances</a>
                        <a href="index.php?page=mission">Registre Missions</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Bloc opératoire ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=intervention">Interventions</a>
                        <a href="index.php?page=materiel">Matériel Médical</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Traçabilité ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=lot">Lots Médicaments</a>
                        <a href="index.php?page=distribution">Distributions</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Rendez-vous ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=rdv">Agenda RDV</a>
                        <a href="index.php?page=ficherdv">Fiches de RDV</a>
                    </div>
                </li>
                <li><a href="loisir.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>📊 Statistiques des Fiches RDV</h1>
            <a href="index.php?page=ficherdv" class="btn btn-secondary">← Retour</a>
        </div>

        <div class="stats-container">
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Fiches par Mode de Remboursement</h3>
                <canvas id="remboursementChart"></canvas>
            </div>
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Statut des Emails Envoyés</h3>
                <canvas id="emailChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        <?php
            $lblR = []; $datR = [];
            foreach($stats['remboursements'] as $row) {
                $nom = htmlspecialchars($row['modeRemboursement'] ?? 'Non Précisé');
                if(trim($nom) == '') $nom = 'Non Précisé';
                $lblR[] = $nom;
                $datR[] = $row['total'];
            }

            $lblE = []; $datE = []; $colE = [];
            foreach($stats['emails'] as $row) {
                $lblE[] = $row['emailEnvoye'] ? 'Envoyé' : 'Non Envoyé';
                $datE[] = $row['total'];
                $colE[] = $row['emailEnvoye'] ? '#4bc0c0' : '#ff6384';
            }
        ?>

        new Chart(document.getElementById('remboursementChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($lblR); ?>,
                datasets: [{ label: 'Quantité de fiches', data: <?php echo json_encode($datR); ?>, backgroundColor: '#36a2eb' }]
            },
            options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        new Chart(document.getElementById('emailChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($lblE); ?>,
                datasets: [{ data: <?php echo json_encode($datE); ?>, backgroundColor: <?php echo json_encode($colE); ?> }]
            }
        });
    </script>
</body>
</html>
