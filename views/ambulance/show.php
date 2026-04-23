<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Ambulance - MedChain</title>
    <link rel="stylesheet" href="style.css">
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
            <h1>Détails de l'Ambulance</h1>
            <div>
<a href="index.php?page=ambulance" class="btn btn-secondary">← Retour</a>
            </div>
        </div>

        <div class="details-card">
            <div class="details-section">
                <h3>Informations Générales</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>ID :</label>
                        <span><?php echo $this->ambulance->idAmbulance; ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Immatriculation :</label>
                        <span><?php echo htmlspecialchars($this->ambulance->immatriculation); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Modèle :</label>
                        <span><?php echo htmlspecialchars($this->ambulance->modele); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Statut :</label>
                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $this->ambulance->statut)); ?>">
                            <?php echo htmlspecialchars($this->ambulance->statut); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <label>Capacité :</label>
                        <span><?php echo $this->ambulance->capacite; ?> places</span>
                    </div>
                    <div class="detail-item">
                        <label>Disponibilité :</label>
                        <span class="badge badge-<?php echo $this->ambulance->estDisponible ? 'disponible' : 'indisponible'; ?>">
                            <?php echo $this->ambulance->estDisponible ? '✓ Disponible' : '✗ Indisponible'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
<!-- Ajoutez ceci dans la section des messages -->
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'delete_error'): ?>
    <div class="alert alert-danger">
        ⚠️ Impossible de supprimer cette ambulance car elle a des missions associées.
    </div>
<?php endif; ?>