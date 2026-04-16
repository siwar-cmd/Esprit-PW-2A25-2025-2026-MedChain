<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Rendez-vous - MedChain</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .rdv-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .rdv-header {
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
        .rdv-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .rdv-table th, .rdv-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .rdv-table th {
            background-color: #4CAF50;
            color: white;
        }
        .rdv-table tr:hover {
            background-color: #f5f5f5;
        }
        .status-planifie { color: #2196F3; font-weight: bold; }
        .status-confirme { color: #4CAF50; font-weight: bold; }
        .status-annule { color: #f44336; font-weight: bold; }
        .status-reporte { color: #FF9800; font-weight: bold; }
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
        .btn-confirm { background-color: #4CAF50; color: white; }
        .btn-cancel { background-color: #9C27B0; color: white; }
        .btn-postpone { background-color: #FF5722; color: white; }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .filter-bar {
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .filter-bar input {
            padding: 8px;
            width: 300px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="rdv-container">
        <div class="rdv-header">
            <h1>Gestion des Rendez-vous</h1>
            <div>
                <a href="index.php?page=rdv&action=create" class="btn-add">+ Nouveau Rendez-vous</a>
                <a href="index.php" class="btn-back">Retour Accueil</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="message success">
                <?php 
                    if($_GET['msg'] == 'created') echo "✓ Rendez-vous créé avec succès!";
                    if($_GET['msg'] == 'updated') echo "✓ Rendez-vous modifié avec succès!";
                    if($_GET['msg'] == 'deleted') echo "✓ Rendez-vous supprimé avec succès!";
                    if($_GET['msg'] == 'confirmed') echo "✓ Rendez-vous confirmé avec succès!";
                    if($_GET['msg'] == 'cancelled') echo "✓ Rendez-vous annulé avec succès!";
                    if($_GET['msg'] == 'postponed') echo "✓ Rendez-vous reporté avec succès!";
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="message error">
                ⚠ Une erreur est survenue. Veuillez réessayer.
            </div>
        <?php endif; ?>

        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="Rechercher par motif ou type..." onkeyup="filterTable()">
            <select id="statusFilter" onchange="filterTable()">
                <option value="">Tous les statuts</option>
                <option value="planifie">Planifié</option>
                <option value="confirme">Confirmé</option>
                <option value="annule">Annulé</option>
                <option value="reporte">Reporté</option>
            </select>
        </div>

        <table class="rdv-table" id="rdvTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date et heure de début</th>
                    <th>Date et heure de fin</th>
                    <th>Statut</th>
                    <th>Type de consultation</th>
                    <th>Motif</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['idRDV']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['dateHeureDebut'])); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['dateHeureFin'])); ?></td>
                    <td class="status-<?php echo $row['statut']; ?>">
                        <?php 
                            $statusLabel = [
                                'planifie' => '📅 Planifié',
                                'confirme' => '✅ Confirmé',
                                'annule' => '❌ Annulé',
                                'reporte' => '🔄 Reporté'
                            ];
                            echo $statusLabel[$row['statut']] ?? $row['statut'];
                        ?>
                    </td>
                    <td><?php echo $row['typeConsultation']; ?></td>
                    <td><?php echo substr($row['motif'] ?? '', 0, 50); ?>...</td>
                    <td>
                        <a href="index.php?page=rdv&action=show&id=<?php echo $row['idRDV']; ?>" class="btn-action btn-view">👁 Voir</a>
                        <a href="index.php?page=rdv&action=edit&id=<?php echo $row['idRDV']; ?>" class="btn-action btn-edit">✏ Modifier</a>
                        <?php if($row['statut'] == 'planifie'): ?>
                            <a href="index.php?page=rdv&action=confirm&id=<?php echo $row['idRDV']; ?>" class="btn-action btn-confirm" onclick="return confirm('Confirmer ce rendez-vous?')">✅ Confirmer</a>
                        <?php endif; ?>
                        <?php if($row['statut'] != 'annule' && $row['statut'] != 'reporte'): ?>
                            <a href="index.php?page=rdv&action=cancel&id=<?php echo $row['idRDV']; ?>" class="btn-action btn-cancel">❌ Annuler</a>
                            <a href="index.php?page=rdv&action=postpone&id=<?php echo $row['idRDV']; ?>" class="btn-action btn-postpone">🔄 Reporter</a>
                        <?php endif; ?>
                        <a href="index.php?page=rdv&action=delete&id=<?php echo $row['idRDV']; ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer ce rendez-vous? Cette action est irréversible.')">🗑 Supprimer</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        function filterTable() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toLowerCase();
            var statusFilter = document.getElementById("statusFilter").value;
            var table = document.getElementById("rdvTable");
            var tr = table.getElementsByTagName("tr");

            for (var i = 1; i < tr.length; i++) {
                var tdMotif = tr[i].getElementsByTagName("td")[5];
                var tdType = tr[i].getElementsByTagName("td")[4];
                var tdStatus = tr[i].getElementsByTagName("td")[3];
                
                if (tdMotif && tdType) {
                    var motifValue = tdMotif.textContent || tdMotif.innerText;
                    var typeValue = tdType.textContent || tdType.innerText;
                    var statusValue = tdStatus.textContent || tdStatus.innerText;
                    
                    var matchesSearch = motifValue.toLowerCase().indexOf(filter) > -1 || 
                                       typeValue.toLowerCase().indexOf(filter) > -1;
                    var matchesStatus = statusFilter === "" || statusValue.toLowerCase().indexOf(statusFilter) > -1;
                    
                    if (matchesSearch && matchesStatus) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>