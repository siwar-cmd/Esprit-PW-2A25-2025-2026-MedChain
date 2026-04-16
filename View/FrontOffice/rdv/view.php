<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Rendez-vous - MedChain</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .details-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .details-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .details-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .details-content {
            padding: 30px;
        }
        .detail-row {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
            width: 200px;
            color: #555;
        }
        .detail-value {
            flex: 1;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        .status-planifie { background: #2196F3; color: white; }
        .status-confirme { background: #4CAF50; color: white; }
        .status-annule { background: #f44336; color: white; }
        .status-reporte { background: #FF9800; color: white; }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            justify-content: center;
        }
        .btn {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        .btn-edit { background: #FF9800; color: white; }
        .btn-back { background: #666; color: white; }
        .btn-confirm { background: #4CAF50; color: white; }
        .btn-cancel { background: #9C27B0; color: white; }
        .btn-postpone { background: #FF5722; color: white; }
        .btn-delete { background: #f44336; color: white; }
    </style>
</head>
<body>
    <div class="details-container">
        <div class="details-header">
            <h1>📋 Détails du Rendez-vous</h1>
            <p>ID: #<?php echo $this->rdv->idRDV; ?></p>
        </div>
        
        <div class="details-content">
            <div class="detail-row">
                <div class="detail-label">📅 Date et heure de début :</div>
                <div class="detail-value"><?php echo date('d/m/Y à H:i', strtotime($this->rdv->dateHeureDebut)); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">⏰ Date et heure de fin :</div>
                <div class="detail-value"><?php echo date('d/m/Y à H:i', strtotime($this->rdv->dateHeureFin)); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">📌 Statut :</div>
                <div class="detail-value">
                    <span class="status-badge status-<?php echo $this->rdv->statut; ?>">
                        <?php 
                            $statusLabels = [
                                'planifie' => '📅 Planifié',
                                'confirme' => '✅ Confirmé',
                                'annule' => '❌ Annulé',
                                'reporte' => '🔄 Reporté'
                            ];
                            echo $statusLabels[$this->rdv->statut] ?? $this->rdv->statut;
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">🏥 Type de consultation :</div>
                <div class="detail-value"><?php echo htmlspecialchars($this->rdv->typeConsultation); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">📝 Motif :</div>
                <div class="detail-value"><?php echo nl2br(htmlspecialchars($this->rdv->motif ?: 'Non spécifié')); ?></div>
            </div>
            
            <div class="actions">
                <a href="index.php?page=rdv&action=edit&id=<?php echo $this->rdv->idRDV; ?>" class="btn btn-edit">✏ Modifier</a>
                <a href="index.php?page=rdv&action=index" class="btn btn-back">← Retour</a>
                <?php if($this->rdv->statut == 'planifie'): ?>
                    <a href="index.php?page=rdv&action=confirm&id=<?php echo $this->rdv->idRDV; ?>" class="btn btn-confirm" onclick="return confirm('Confirmer ce rendez-vous?')">✅ Confirmer</a>
                <?php endif; ?>
                <?php if($this->rdv->statut != 'annule' && $this->rdv->statut != 'reporte'): ?>
                    <a href="index.php?page=rdv&action=cancel&id=<?php echo $this->rdv->idRDV; ?>" class="btn btn-cancel">❌ Annuler</a>
                    <a href="index.php?page=rdv&action=postpone&id=<?php echo $this->rdv->idRDV; ?>" class="btn btn-postpone">🔄 Reporter</a>
                <?php endif; ?>
                <a href="index.php?page=rdv&action=delete&id=<?php echo $this->rdv->idRDV; ?>" class="btn btn-delete" onclick="return confirm('Supprimer définitivement ce rendez-vous?')">🗑 Supprimer</a>
            </div>
        </div>
    </div>
</body>
</html>