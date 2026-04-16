<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporter Rendez-vous - MedChain</title>
    <style>
        .postpone-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .postpone-container h1 {
            color: #FF9800;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-postpone {
            background-color: #FF9800;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-back {
            background-color: #666;
            color: white;
            padding: 10px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .old-date {
            color: #f44336;
            text-decoration: line-through;
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
    <div class="postpone-container">
        <h1>🔄 Reporter le Rendez-vous</h1>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="error-message">
                <strong>⚠ Erreur :</strong> 
                <?php 
                    if($_GET['error'] == 'date_required') echo "Veuillez saisir une nouvelle date.";
                    if($_GET['error'] == 'date_invalid_format') echo "Format de date invalide. Utilisez AAAA-MM-JJ HH:MM:SS";
                    if($_GET['error'] == 'date_past') echo "La nouvelle date ne peut pas être dans le passé.";
                ?>
            </div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Rendez-vous #<?php echo $this->rdv->idRDV; ?></strong><br>
            Ancienne date: <span class="old-date"><?php echo date('d/m/Y H:i', strtotime($this->rdv->dateHeureDebut)); ?></span><br>
            Type: <?php echo htmlspecialchars($this->rdv->typeConsultation); ?>
        </div>
        
        <form method="POST" action="index.php?page=rdv&action=postpone&id=<?php echo $this->rdv->idRDV; ?>">
            <div class="form-group">
                <label for="nouvelleDate">Nouvelle date et heure *</label>
                <input type="text" id="nouvelleDate" name="nouvelleDate" placeholder="AAAA-MM-JJ HH:MM:SS">
                <small>Format: 2024-12-25 14:30:00</small>
            </div>
            
            <button type="submit" class="btn-postpone" onclick="return confirm('Confirmer le report de ce rendez-vous?')">✓ Confirmer le report</button>
            <a href="index.php?page=rdv&action=index" class="btn-back">← Retour</a>
        </form>
    </div>
</body>
</html>