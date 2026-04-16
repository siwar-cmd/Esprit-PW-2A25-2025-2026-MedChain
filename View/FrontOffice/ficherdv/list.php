<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiches Rendez-vous - MedChain</title>
    <style>
        .container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-add {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn-back {
            background-color: #666;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #2196F3;
            color: white;
        }
        .btn-action {
            padding: 5px 10px;
            margin: 2px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
            font-size: 12px;
        }
        .btn-view { background-color: #2196F3; color: white; }
        .btn-edit { background-color: #FF9800; color: white; }
        .btn-delete { background-color: #f44336; color: white; }
        .btn-pdf { background-color: #f44336; color: white; }
        .btn-email { background-color: #4CAF50; color: white; }
        .btn-calendar { background-color: #9C27B0; color: white; }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success { background-color: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Fiches de Rendez-vous</h1>
            <div>
                <a href="index.php?page=ficherdv&action=create" class="btn-add">+ Nouvelle Fiche</a>
                <a href="index.php" class="btn-back">Retour Accueil</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="message success">
                <?php 
                    if($_GET['msg'] == 'created') echo "✓ Fiche créée avec succès!";
                    if($_GET['msg'] == 'updated') echo "✓ Fiche modifiée avec succès!";
                    if($_GET['msg'] == 'deleted') echo "✓ Fiche supprimée avec succès!";
                    if($_GET['msg'] == 'pdf_generated') echo "✓ PDF généré avec succès!";
                    if($_GET['msg'] == 'email_sent') echo "✓ Email envoyé avec succès!";
                    if($_GET['msg'] == 'calendar_added') echo "✓ Calendrier mis à jour!";
                ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID Fiche</th>
                    <th>ID RDV</th>
                    <th>Date RDV</th>
                    <th>Type Consultation</th>
                    <th>Statut</th>
                    <th>Date Génération</th>
                    <th>Tarif (DT)</th>
                    <th>Email</th>
                    <th>Calendrier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['idFiche']; ?></td>
                    <td><?php echo $row['idRDV']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['dateHeureDebut'])); ?></td>
                    <td><?php echo $row['typeConsultation']; ?></td>
                    <td><?php echo $row['statut']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['dateGeneration'])); ?></td>
                    <td><?php echo number_format($row['tarifConsultation'], 2); ?> DT</td>
                    <td><?php echo $row['emailEnvoye'] ? '✅' : '❌'; ?></td>
                    <td><?php echo $row['calendrierAjoute'] ? '✅' : '❌'; ?></td>
                    <td>
                        <a href="index.php?page=ficherdv&action=show&id=<?php echo $row['idFiche']; ?>" class="btn-action btn-view">👁 Voir</a>
                        <a href="index.php?page=ficherdv&action=edit&id=<?php echo $row['idFiche']; ?>" class="btn-action btn-edit">✏ Modifier</a>
                        <a href="index.php?page=ficherdv&action=generatePDF&id=<?php echo $row['idFiche']; ?>" class="btn-action btn-pdf">📄 PDF</a>
                        <a href="index.php?page=ficherdv&action=sendEmail&id=<?php echo $row['idFiche']; ?>" class="btn-action btn-email">📧 Email</a>
                        <a href="index.php?page=ficherdv&action=addToCalendar&id=<?php echo $row['idFiche']; ?>" class="btn-action btn-calendar">📅 Calendrier</a>
                        <a href="index.php?page=ficherdv&action=delete&id=<?php echo $row['idFiche']; ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer cette fiche?')">🗑 Supprimer</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>