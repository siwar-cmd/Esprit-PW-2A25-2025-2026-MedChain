<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Ambulances - MedChain</title>
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
                <li><a href="#">Bloc Opératoire</a></li>
            
                <li><a href="#">Rendez-vous</a></li>
                <li><a href="#">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>Gestion des Ambulances</h1>
            <div class="header-actions">
                <a href="index.php?page=ambulance&action=stats" class="btn btn-secondary">
                    📊 Statistiques
                </a>
                <a href="index.php?page=ambulance&action=create" class="btn btn-primary">
                    <i>➕</i> Nouvelle Ambulance
                </a>
            </div>
        </div>

        <!-- Message de notification -->
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch($_GET['msg']) {
                        case 'created': echo "Ambulance créée avec succès !"; break;
                        case 'updated': echo "Ambulance mise à jour avec succès !"; break;
                        case 'deleted': echo "Ambulance supprimée avec succès !"; break;
                        case 'delete_error': echo "Impossible de supprimer: Ambulance liée à une mission !"; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- Barre de recherche JS & PDF -->
        <div class="search-bar" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" placeholder="Rechercher dynamiquement une ambulance..." style="flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <button onclick="exportPDF()" class="btn btn-secondary">📄 Exporter en PDF</button>
        </div>

        <!-- Tableau des ambulances -->
        <div class="table-container" id="pdf-content">
            <table class="data-table" id="ambulanceTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)" style="cursor:pointer">ID ↕</th>
                        <th onclick="sortTable(1)" style="cursor:pointer">Immatriculation ↕</th>
                        <th onclick="sortTable(2)" style="cursor:pointer">Modèle ↕</th>
                        <th onclick="sortTable(3)" style="cursor:pointer">Statut ↕</th>
                        <th onclick="sortTable(4)" style="cursor:pointer">Capacité ↕</th>
                        <th onclick="sortTable(5)" style="cursor:pointer">Disponibilité ↕</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($ambulances)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Aucune ambulance trouvée</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($ambulances as $ambulance): ?>
                        <tr>
                            <td><?php echo $ambulance['idAmbulance']; ?></td>
                            <td><?php echo htmlspecialchars($ambulance['immatriculation']); ?></td>
                            <td><?php echo htmlspecialchars($ambulance['modele']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ambulance['statut'])); ?>">
                                    <?php echo htmlspecialchars($ambulance['statut']); ?>
                                </span>
                            </td>
                            <td><?php echo $ambulance['capacite']; ?> places</td>
                            <td>
                                <span class="badge badge-<?php echo $ambulance['estDisponible'] ? 'disponible' : 'indisponible'; ?>">
                                    <?php echo $ambulance['estDisponible'] ? 'Disponible' : 'Indisponible'; ?>
                                </span>
                            </td>
                            <td class="actions no-print">
                                <a href="index.php?page=ambulance&action=show&id=<?php echo $ambulance['idAmbulance']; ?>" 
                                   class="btn-icon btn-view" title="Voir">👁️</a>
                                <a href="index.php?page=ambulance&action=edit&id=<?php echo $ambulance['idAmbulance']; ?>" 
                                   class="btn-icon btn-edit" title="Modifier">✏️</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $ambulance['idAmbulance']; ?>)" 
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
        function confirmDelete(id) {
            if(confirm('Êtes-vous sûr de vouloir supprimer cette ambulance ?')) {
                window.location.href = 'index.php?page=ambulance&action=delete&id=' + id;
            }
        }

        // --- RECHERCHE DYNAMIQUE ---
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let table = document.getElementById("ambulanceTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let tdArray = tr[i].getElementsByTagName("td");
                let rowMatches = false;
                
                for(let j = 0; j < tdArray.length - 1; j++) { // Ignore Actions
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
            let table = document.getElementById("ambulanceTable");
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
                    
                    let cmpX = isNaN(x.innerHTML.trim().split(' ')[0]) ? x.innerHTML.toLowerCase() : Number(x.innerHTML.trim().split(' ')[0]);
                    let cmpY = isNaN(y.innerHTML.trim().split(' ')[0]) ? y.innerHTML.toLowerCase() : Number(y.innerHTML.trim().split(' ')[0]);
                    
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
            let elementsToHide = document.querySelectorAll('.no-print');
            elementsToHide.forEach(el => el.style.display = 'none');

            var element = document.getElementById('pdf-content');
            var opt = {
                margin:       1,
                filename:     'liste_ambulances.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                elementsToHide.forEach(el => el.style.display = '');
            });
        }
    </script>
</body>
</html>