<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Interventions - MedChain</title>
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
                    <a href="#" class="dropbtn active">Bloc opératoire ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=intervention">Interventions</a>
                        <a href="index.php?page=materiel">Matériel Médical</a>
                    </div>
                </li>
                
                <li><a href="cas_usage.php">Rendez-vous</a></li>
                <li><a href="blog.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>Gestion des Interventions</h1>
            <div class="header-actions">
                <a href="index.php?page=intervention&action=stats" class="btn btn-secondary">
                    📊 Statistiques
                </a>
                <a href="index.php?page=intervention&action=create" class="btn btn-primary">
                    <i>➕</i> Nouvelle Intervention
                </a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch($_GET['msg']) {
                        case 'created': echo "Intervention créée avec succès !"; break;
                        case 'updated': echo "Intervention mise à jour !"; break;
                        case 'deleted': echo "Intervention supprimée !"; break;
                        case 'planified': echo "Intervention planifiée avec succès (DATE DEBUT = NOW()) !"; break;
                        case 'canceled': echo "Intervention annulée et archivée !"; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- Barre de recherche -->
        <div class="search-bar" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" placeholder="Rechercher dynamiquement..." style="flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <button onclick="exportPDF()" class="btn btn-secondary">📄 Exporter en PDF</button>
        </div>

        <!-- Tableau des interventions -->
        <div class="table-container" id="pdf-content">
            <table class="data-table" id="interventionTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)" style="cursor:pointer">ID ↕</th>
                        <th onclick="sortTable(1)" style="cursor:pointer">Date Début ↕</th>
                        <th onclick="sortTable(2)" style="cursor:pointer">Date Fin Prévue ↕</th>
                        <th onclick="sortTable(3)" style="cursor:pointer">Type ↕</th>
                        <th onclick="sortTable(4)" style="cursor:pointer">Niv. Urgence ↕</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($interventions)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Aucune intervention</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($interventions as $inv): ?>
                        <tr>
                            <td><?php echo $inv['idIntervention']; ?></td>
                            <td><?php echo $inv['dateHeureDebut'] ? date('d/m/Y H:i', strtotime($inv['dateHeureDebut'])) : 'Non définie'; ?></td>
                            <td><?php echo $inv['dateHeureFinPrevu'] ? date('d/m/Y H:i', strtotime($inv['dateHeureFinPrevu'])) : 'Non définie'; ?></td>
                            <td><?php echo htmlspecialchars($inv['typeIntervention']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $inv['niveauUrgence'] > 3 ? 'indisponible' : 'en-maintenance'; ?>">
                                    Urgence : <?php echo $inv['niveauUrgence']; ?>
                                </span>
                            </td>
                            <td class="actions no-print">
                                <!-- Trigger Planifier Procedure -->
                                <a href="javascript:void(0)" onclick="confirmPlanify(<?php echo $inv['idIntervention']; ?>)" 
                                   class="btn-icon" title="Planifier Démarrage (NOW)" style="color: green; text-decoration: none;">▶️</a>
                                
                                <!-- Trigger Annuler Procedure -->
                                <a href="javascript:void(0)" onclick="confirmCancel(<?php echo $inv['idIntervention']; ?>)" 
                                   class="btn-icon" title="Annuler" style="color: orange; text-decoration: none;">⛔</a>

                                <a href="index.php?page=intervention&action=edit&id=<?php echo $inv['idIntervention']; ?>" 
                                   class="btn-icon btn-edit" title="Modifier">✏️</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $inv['idIntervention']; ?>)" 
                                   class="btn-icon btn-delete" title="Supprimer (Standard)">🗑️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Formulaire caché pour l'annulation (envoi de la raison) -->
        <form id="cancelForm" method="POST" style="display:none;">
            <input type="hidden" name="raison" id="cancelRaison">
        </form>

    </main>

    <script>
        function confirmDelete(id) {
            if(confirm('Supprimer définitivement cette intervention ?')) {
                window.location.href = 'index.php?page=intervention&action=delete&id=' + id;
            }
        }
        
        function confirmPlanify(id) {
            if(confirm('Mettre la date de début à MAINTENANT (Procedure planifier) ?')) {
                window.location.href = 'index.php?page=intervention&action=planifier&id=' + id;
            }
        }
        
        function confirmCancel(id) {
            let raison = prompt('Entrez la raison de l\'annulation :');
            if(raison !== null && raison.trim() !== '') {
                let form = document.getElementById('cancelForm');
                form.action = 'index.php?page=intervention&action=annuler&id=' + id;
                document.getElementById('cancelRaison').value = raison;
                form.submit();
            } else if (raison !== null) {
                alert("La raison est requise !");
            }
        }

        // --- RECHERCHE DYNAMIQUE ---
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let table = document.getElementById("interventionTable");
            let tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let tdArray = tr[i].getElementsByTagName("td");
                let rowMatches = false;
                for(let j = 0; j < tdArray.length - 1; j++) {
                    if (tdArray[j]) {
                        let txtValue = tdArray[j].textContent || tdArray[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            rowMatches = true; break;
                        }
                    }
                }
                tr[i].style.display = rowMatches ? "" : "none";
            }
        });

        // --- TRI DYNAMIQUE ---
        let sortDir = {};
        function sortTable(n) {
            let table = document.getElementById("interventionTable");
            let switching = true;
            let dir = sortDir[n] || "asc";
            let switchcount = 0;
            while (switching) {
                switching = false;
                let rows = table.rows;
                let i, shouldSwitch = false;
                for (i = 1; i < (rows.length - 1); i++) {
                    let x = rows[i].getElementsByTagName("TD")[n];
                    let y = rows[i + 1].getElementsByTagName("TD")[n];
                    if(!x || !y) continue;
                    let cmpX = isNaN(x.innerText.replace(/[^\d.-]/g, '')) ? x.innerText.toLowerCase() : Number(x.innerText.replace(/[^\d.-]/g, ''));
                    let cmpY = isNaN(y.innerText.replace(/[^\d.-]/g, '')) ? y.innerText.toLowerCase() : Number(y.innerText.replace(/[^\d.-]/g, ''));
                    if (dir == "asc") { if (cmpX > cmpY) { shouldSwitch = true; break; } } 
                    else { if (cmpX < cmpY) { shouldSwitch = true; break; } }
                }
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true; switchcount++;
                } else if (switchcount == 0 && dir == "asc") {
                    sortDir[n] = "desc"; switching = true;
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
                margin: 1, filename: 'interventions.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
            };
            html2pdf().set(opt).from(element).save().then(() => {
                elementsToHide.forEach(el => el.style.display = '');
            });
        }
    </script>
</body>
</html>
