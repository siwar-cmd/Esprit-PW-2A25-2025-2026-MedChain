<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuler Rendez-vous - MedChain</title>
    <style>
        .cancel-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .cancel-container h1 {
            color: #f44336;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }
        .btn-cancel {
            background-color: #f44336;
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
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: left;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <h1>❌ Annuler le Rendez-vous</h1>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="error-message">
                <strong>⚠ Erreur :</strong> 
                <?php 
                    if($_GET['error'] == 'motif_required') echo "Veuillez saisir un motif d'annulation.";
                ?>
            </div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Rendez-vous #<?php echo $this->rdv->idRDV; ?></strong><br>
            Date: <?php echo date('d/m/Y H:i', strtotime($this->rdv->dateHeureDebut)); ?><br>
            Type: <?php echo htmlspecialchars($this->rdv->typeConsultation); ?>
        </div>
        
        <form method="POST" action="index.php?page=rdv&action=cancel&id=<?php echo $this->rdv->idRDV; ?>">
            <div class="form-group">
                <label for="motif_annulation">Motif d'annulation *</label>
                <textarea id="motif_annulation" name="motif_annulation" rows="4" placeholder="Veuillez expliquer la raison de l'annulation..."></textarea>
            </div>
            
            <button type="submit" class="btn-cancel" onclick="return confirm('Confirmer l\'annulation de ce rendez-vous?')">✓ Confirmer l'annulation</button>
            <a href="index.php?page=rdv&action=index" class="btn-back">← Retour</a>
        </form>
    </div>
</body>
</html>