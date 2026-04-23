<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Matériel - MedChain</title>
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
            <h1>📊 Statistiques du Matériel Chirurgical</h1>
            <a href="index.php?page=materiel" class="btn btn-secondary">← Retour</a>
        </div>

        <div class="stats-container">
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Disponibilité Globale</h3>
                <canvas id="dispoChart"></canvas>
            </div>
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Niveau de Stérilisation</h3>
                <canvas id="sterilChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        <?php
            $lblD = []; $datD = []; $colD = [];
            foreach($stats['disponibilites'] as $row) {
                $lblD[] = $row['disponibilite'];
                $datD[] = $row['total'];
                $colD[] = $row['disponibilite'] == 'disponible' ? 'rgba(75, 192, 192, 0.6)' : 'rgba(255, 99, 132, 0.6)';
            }
            $lblS = []; $datS = []; $colS = [];
            foreach($stats['sterilisations'] as $row) {
                $lblS[] = $row['statutSterilisation'];
                $datS[] = $row['total'];
                $colS[] = $row['statutSterilisation'] == 'sterilise' ? 'rgba(54, 162, 235, 0.6)' : 'rgba(255, 206, 86, 0.6)';
            }
        ?>

        new Chart(document.getElementById('dispoChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($lblD); ?>,
                datasets: [{ data: <?php echo json_encode($datD); ?>, backgroundColor: <?php echo json_encode($colD); ?> }]
            }
        });

        new Chart(document.getElementById('sterilChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($lblS); ?>,
                datasets: [{ data: <?php echo json_encode($datS); ?>, backgroundColor: <?php echo json_encode($colS); ?> }]
            }
        });
    </script>
</body>
</html>
