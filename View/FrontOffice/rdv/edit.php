<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Rendez-vous - MedChain</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-container h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn-submit {
            background-color: #FF9800;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn-back {
            background-color: #666;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        .info-box {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        small {
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>✏ Modifier Rendez-vous #<?php echo $this->rdv->idRDV; ?></h1>
        
        <div class="info-box">
            <strong>Statut actuel :</strong> 
            <?php 
                $statusLabels = [
                    'planifie' => '📅 Planifié',
                    'confirme' => '✅ Confirmé',
                    'annule' => '❌ Annulé',
                    'reporte' => '🔄 Reporté'
                ];
                echo $statusLabels[$this->rdv->statut] ?? $this->rdv->statut;
            ?>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="error-message">
                <strong>⚠ Erreur :</strong> 
                <?php 
                    if(strpos($_GET['error'], 'date_debut_required') !== false) echo "La date de début est obligatoire.<br>";
                    if(strpos($_GET['error'], 'date_fin_required') !== false) echo "La date de fin est obligatoire.<br>";
                    if(strpos($_GET['error'], 'type_required') !== false) echo "Le type de consultation est obligatoire.<br>";
                    if(strpos($_GET['error'], 'date_invalid_order') !== false) echo "La date de fin doit être après la date de début.<br>";
                    if($_GET['error'] == 'db_error') echo "Erreur lors de la modification.";
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=rdv&action=edit&id=<?php echo $this->rdv->idRDV; ?>">
            <div class="form-group">
                <label for="dateHeureDebut">Date et heure de début</label>
                <input type="text" id="dateHeureDebut" name="dateHeureDebut" 
                       value="<?php echo $this->rdv->dateHeureDebut; ?>" placeholder="AAAA-MM-JJ HH:MM:SS">
                <small>Format: 2024-12-25 14:30:00</small>
            </div>

            <div class="form-group">
                <label for="dateHeureFin">Date et heure de fin</label>
                <input type="text" id="dateHeureFin" name="dateHeureFin" 
                       value="<?php echo $this->rdv->dateHeureFin; ?>" placeholder="AAAA-MM-JJ HH:MM:SS">
                <small>Format: 2024-12-25 15:30:00</small>
            </div>

            <div class="form-group">
                <label for="typeConsultation">Type de consultation</label>
                <select id="typeConsultation" name="typeConsultation">
                    <option value="">Sélectionnez un type</option>
                    <option value="Consultation générale" <?php echo $this->rdv->typeConsultation == 'Consultation générale' ? 'selected' : ''; ?>>🏥 Consultation générale</option>
                    <option value="Consultation spécialiste" <?php echo $this->rdv->typeConsultation == 'Consultation spécialiste' ? 'selected' : ''; ?>>👨‍⚕️ Consultation spécialiste</option>
                    <option value="Téléconsultation" <?php echo $this->rdv->typeConsultation == 'Téléconsultation' ? 'selected' : ''; ?>>💻 Téléconsultation</option>
                    <option value="Urgence" <?php echo $this->rdv->typeConsultation == 'Urgence' ? 'selected' : ''; ?>>🚨 Urgence</option>
                    <option value="Contrôle" <?php echo $this->rdv->typeConsultation == 'Contrôle' ? 'selected' : ''; ?>>📋 Contrôle</option>
                </select>
            </div>

            <div class="form-group">
                <label for="motif">Motif de la consultation</label>
                <textarea id="motif" name="motif" rows="4"><?php echo htmlspecialchars($this->rdv->motif); ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-submit">💾 Enregistrer les modifications</button>
                <a href="index.php?page=rdv&action=index" class="btn-back">← Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>