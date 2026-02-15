<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Distributions - MedChain</title>
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
                    <a href="#" class="dropbtn active">Traçabilité ⬇</a>
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
                <li><a href="blog.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>📊 Statistiques des Transferts</h1>
            <a href="index.php?page=distribution" class="btn btn-secondary">← Retour</a>
        </div>

        <div class="stats-container">
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Médicaments les plus distribués (Nb Transferts)</h3>
                <canvas id="medChart"></canvas>
            </div>
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Volume consommé par Destinataire</h3>
                <canvas id="destChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        <?php
            $lblM = []; $datM = [];
            foreach($stats['meds'] as $row) {
                // If the lot was deleted but distribution remains, nomMedicament could be null.
                $lblM[] = htmlspecialchars($row['nomMedicament'] ?? 'Médicament Inconnu / Supprimé');
                $datM[] = $row['nbDistributions'];
            }

            $lblD = []; $datD = [];
            foreach($stats['destinataires'] as $row) {
                $lblD[] = htmlspecialchars($row['destinataire']);
                $datD[] = $row['total'];
            }
        ?>

        new Chart(document.getElementById('medChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($lblM); ?>,
                datasets: [{ data: <?php echo json_encode($datM); ?> }]
            }
        });

        new Chart(document.getElementById('destChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($lblD); ?>,
                datasets: [{ label: 'Quantité totale reçue', data: <?php echo json_encode($datD); ?>, backgroundColor: '#4bc0c0' }]
            },
            options: { indexAxis: 'y', scales: { x: { beginAtZero: true } } }
        });
    </script>
</body>
</html>
