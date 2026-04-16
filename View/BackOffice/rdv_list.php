<?php
session_start();
require_once '../../Model/rdv.php';

$rdv = new Rdv();
$stmt = $rdv->readAll();

// Simulation de données pour les KPI
$dashboard_data = [
    'dossiers_actifs' => 133,
    'partages_donnees' => 314,
    'alertes_critiques' => 30,
    'nouveaux_patients' => 19,
    'activites_hebdo' => [12, 19, 15, 25, 22, 30, 28],
    'jours' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
];

// Liens de navigation
$nav_items = [
    'backoffice_rdv.php' => ['icon' => '🏠', 'text' => 'Accueil', 'active' => false],
    'rdv_list.php' => ['icon' => '📅', 'text' => 'Rendez-vous', 'active' => true],
    'partage.php' => ['icon' => '🏥', 'text' => 'Intervention', 'active' => false],
    'alertes.php' => ['icon' => '💊', 'text' => 'Traçabilité des médicaments', 'active' => false],
    'rapports.php' => ['icon' => '🚑', 'text' => 'Ambulances et missions', 'active' => false],
    'utilisateurs.php' => ['icon' => '👥', 'text' => 'Utilisateurs', 'active' => false],
    'parametres.php' => ['icon' => '⚙️', 'text' => 'Paramètres', 'active' => false],
    '../../index.php' => ['icon' => '🚪', 'text' => 'Retour FrontOffice', 'active' => false]
];

// KPI Cards
$kpi_cards = [
    [
        'lien' => 'rdv_list.php',
        'image' => 'suivi.PNG',
        'titre' => 'Dossiers Actifs',
        'valeur' => $dashboard_data['dossiers_actifs'],
        'classe' => ''
    ],
    [
        'lien' => 'partage.php',
        'image' => 'partage.PNG',
        'titre' => 'Partages Données',
        'valeur' => $dashboard_data['partages_donnees'],
        'classe' => ''
    ],
    [
        'lien' => 'alertes.php',
        'image' => 'alerte.PNG',
        'titre' => 'Alertes Critiques',
        'valeur' => $dashboard_data['alertes_critiques'],
        'classe' => 'text-alert'
    ],
    [
        'lien' => 'rdv_list.php',
        'image' => 'nouv patient.PNG',
        'titre' => 'Nouveaux Patients',
        'valeur' => $dashboard_data['nouveaux_patients'],
        'classe' => ''
    ]
];

// Fonction pour formater les données pour JavaScript
function formatDataForChart($data) {
    return json_encode($data);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedChain | Gestion des Rendez-vous</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eef2f4;
        }
        th {
            background-color: #f8fafb;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background-color: #f8fafb;
        }
        .status-planifie { color: #2196F3; font-weight: bold; }
        .status-confirme { color: #1D9E75; font-weight: bold; }
        .status-annule { color: #e74c3c; font-weight: bold; }
        .status-reporte { color: #FF9800; font-weight: bold; }
        .btn-action {
            padding: 5px 10px;
            margin: 2px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        .btn-view { background: #2196F3; color: white; }
        .btn-edit { background: #FF9800; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-confirm { background: #1D9E75; color: white; }
        .btn-cancel { background: #9C27B0; color: white; }
        .btn-postpone { background: #FF5722; color: white; }
        .btn-add {
            background: linear-gradient(135deg, #1D9E75 0%, #0F6E56 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29, 158, 117, 0.3);
        }
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .filter-bar input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .filter-bar select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
        }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="app-wrapper">
        
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="logo.PNG" alt="MedChain" class="img-logo">
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <?php foreach ($nav_items as $url => $item): ?>
                    <li <?php echo $item['active'] ? 'class="active"' : ''; ?>>
                        <a href="<?php echo $url; ?>" class="nav-item">
                            <?php echo $item['icon']; ?> <?php echo htmlspecialchars($item['text']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>

        <main class="content-main">
            
            <header class="content-header">
                <h1>Gestion des Rendez-vous</h1>
                <p>Gérez tous les rendez-vous médicaux</p>
            </header>

            <section class="kpi-container">
                <?php foreach ($kpi_cards as $card): ?>
                <a href="<?php echo $card['lien']; ?>" class="card-kpi">
                    <img src="<?php echo $card['image']; ?>" alt="">
                    <div class="kpi-txt">
                        <h3><?php echo htmlspecialchars($card['titre']); ?></h3>
                        <p class="<?php echo $card['classe']; ?>"><?php echo $card['valeur']; ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </section>

            <?php if(isset($_GET['msg'])): ?>
                <div class="message message-success">
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
                <div class="message message-error">
                    ⚠ Une erreur est survenue. Veuillez réessayer.
                </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div class="filter-bar" style="margin: 0; flex: 1;">
                    <input type="text" id="searchInput" placeholder="Rechercher par motif ou type..." onkeyup="filterTable()">
                    <select id="statusFilter" onchange="filterTable()">
                        <option value="">Tous les statuts</option>
                        <option value="planifie">Planifié</option>
                        <option value="confirme">Confirmé</option>
                        <option value="annule">Annulé</option>
                        <option value="reporte">Reporté</option>
                    </select>
                </div>
                <a href="rdv_create.php" class="btn-add">+ Nouveau Rendez-vous</a>
            </div>

            <div class="table-container">
                <table id="rdvTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Statut</th>
                            <th>Type consultation</th>
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
                                    $statusLabels = [
                                        'planifie' => '📅 Planifié',
                                        'confirme' => '✅ Confirmé',
                                        'annule' => '❌ Annulé',
                                        'reporte' => '🔄 Reporté'
                                    ];
                                    echo $statusLabels[$row['statut']] ?? $row['statut'];
                                ?>
                            </td>
                            <td><?php echo $row['typeConsultation']; ?></td>
                            <td><?php echo substr($row['motif'] ?? '', 0, 50); ?>...</td>
                            <td>
                                <a href="rdv_view.php?id=<?php echo $row['idRDV']; ?>" class="btn-action btn-view">👁 Voir</a>
                                <a href="rdv_edit.php?id=<?php echo $row['idRDV']; ?>" class="btn-action btn-edit">✏ Modifier</a>
                                <?php if($row['statut'] == 'planifie'): ?>
                                    <a href="rdv_confirm.php?id=<?php echo $row['idRDV']; ?>" class="btn-action btn-confirm" onclick="return confirm('Confirmer ce rendez-vous?')">✅ Confirmer</a>
                                <?php endif; ?>
                                <?php if($row['statut'] != 'annule' && $row['statut'] != 'reporte'): ?>
                                    <a href="rdv_cancel.php?id=<?php echo $row['idRDV']; ?>" class="btn-action btn-cancel">❌ Annuler</a>
                                    <a href="rdv_postpone.php?id=<?php echo $row['idRDV']; ?>" class="btn-action btn-postpone">🔄 Reporter</a>
                                <?php endif; ?>
                                <a href="rdv_delete.php?id=<?php echo $row['idRDV']; ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer définitivement ce rendez-vous?')">🗑 Supprimer</a>
                             </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
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