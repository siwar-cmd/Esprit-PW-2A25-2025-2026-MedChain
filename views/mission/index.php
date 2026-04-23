<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Missions - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <!-- Inclusion de html2pdf pour l'export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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
            <h1>Gestion des Missions</h1>
            <div class="header-actions">
                <a href="index.php?page=mission&action=stats" class="btn btn-secondary">
                    📊 Statistiques
                </a>
                <a href="index.php?page=mission&action=create" class="btn btn-primary">
                    <i>➕</i> Nouvelle Mission
                </a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch($_GET['msg']) {
                        case 'created': echo "Mission créée avec succès !"; break;
                        case 'updated': echo "Mission mise à jour avec succès !"; break;
                        case 'deleted': echo "Mission supprimée avec succès !"; break;
                        case 'delete_error': echo "Erreur lors de la suppression !"; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- Barre de recherche (Dynamique côté client) -->
        <div class="search-bar" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" placeholder="Rechercher dynamiquement une mission..." style="flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <button onclick="exportPDF()" class="btn btn-secondary">📄 Exporter en PDF</button>
        </div>

        <!-- Tableau des missions -->
        <div class="table-container" id="pdf-content">
            <table class="data-table" id="missionTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)" style="cursor:pointer">ID ↕</th>
                        <th onclick="sortTable(1)" style="cursor:pointer">Ambulance ↕</th>
                        <th onclick="sortTable(2)" style="cursor:pointer">Dates ↕</th>
                        <th onclick="sortTable(3)" style="cursor:pointer">Type ↕</th>
                        <th onclick="sortTable(4)" style="cursor:pointer">Départ -> Arrivée ↕</th>
                        <th onclick="sortTable(5)" style="cursor:pointer">Statut ↕</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($missions)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Aucune mission trouvée</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($missions as $mission): ?>
                        <tr>
                            <td><?php echo $mission['idMission']; ?></td>
                            <td><?php echo htmlspecialchars($mission['immatriculation_ambulance']); ?></td>
                            <td>
                                Du <?php echo date('d/m/Y', strtotime($mission['dateDebut'])); ?>
                                <?php if($mission['dateFin']) { echo "<br>Au " . date('d/m/Y', strtotime($mission['dateFin'])); } ?>
                            </td>
                            <td><?php echo htmlspecialchars($mission['typeMission']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($mission['lieuDepart']); ?> 
                                 &#10142; 
                                <?php echo htmlspecialchars($mission['lieuArrivee']); ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $mission['estTerminee'] ? 'disponible' : 'en-maintenance'; ?>">
                                    <?php echo $mission['estTerminee'] ? 'Terminée' : 'En cours'; ?>
                                </span>
                            </td>
                            <td class="actions no-print">
                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $mission['idMission']; ?>)" 
                                   class="btn-icon btn-delete" title="Supprimer">🗑️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Confirmation Suppression
        function confirmDelete(id) {
            if(confirm('Êtes-vous sûr de vouloir supprimer cette mission ?')) {
                window.location.href = 'index.php?page=mission&action=delete&id=' + id;
            }
        }

        // --- RECHERCHE DYNAMIQUE ---
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let table = document.getElementById("missionTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let tdArray = tr[i].getElementsByTagName("td");
                let rowMatches = false;
                
                for(let j = 0; j < tdArray.length - 1; j++) { // Ignore la colonne actions
                    if (tdArray[j]) {
                        let txtValue = tdArray[j].textContent || tdArray[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            rowMatches = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = rowMatches ? "" : "none";
            }
        });

        // --- TRI DYNAMIQUE ---
        let sortDir = {};
        function sortTable(n) {
            let table = document.getElementById("missionTable");
            let switching = true;
            let dir = sortDir[n] || "asc";
            let switchcount = 0;
            
            while (switching) {
                switching = false;
                let rows = table.rows;
                let i;
                let shouldSwitch = false;
                
                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    let x = rows[i].getElementsByTagName("TD")[n];
                    let y = rows[i + 1].getElementsByTagName("TD")[n];
                    if(!x || !y) continue;
                    
                    let cmpX = isNaN(x.innerHTML) ? x.innerHTML.toLowerCase() : Number(x.innerHTML);
                    let cmpY = isNaN(y.innerHTML) ? y.innerHTML.toLowerCase() : Number(y.innerHTML);
                    
                    if (dir == "asc") {
                        if (cmpX > cmpY) { shouldSwitch = true; break; }
                    } else if (dir == "desc") {
                        if (cmpX < cmpY) { shouldSwitch = true; break; }
                    }
                }
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        sortDir[n] = "desc";
                        switching = true;
                    }
                }
            }
            if(switchcount > 0) sortDir[n] = (dir == "asc") ? "desc" : "asc";
        }

        // --- EXPORT PDF ---
        function exportPDF() {
            // Masquer temporairement la colonne Actions pour l'export
            let elementsToHide = document.querySelectorAll('.no-print');
            elementsToHide.forEach(el => el.style.display = 'none');

            var element = document.getElementById('pdf-content');
            var opt = {
                margin:       1,
                filename:     'liste_missions.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
            };

            // Promise based
            html2pdf().set(opt).from(element).save().then(() => {
                // Rétablir la colonne Actions
                elementsToHide.forEach(el => el.style.display = '');
            });
        }
    </script>
</body>
</html>
