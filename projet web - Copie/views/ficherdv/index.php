<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Fiches RDV - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
                    <a href="#" class="dropbtn active">Rendez-vous ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=rdv">Agenda RDV</a>
                        <a href="index.php?page=ficherdv">Fiches de RDV</a>
                    </div>
                </li>
                <li><a href="blog.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>Fiches de Rendez-Vous</h1>
            <div class="header-actions">
                <a href="index.php?page=ficherdv&action=stats" class="btn btn-secondary">📊 Stats Fiches</a>
                <a href="index.php?page=ficherdv&action=create" class="btn btn-primary"><i>➕</i> Nouvelle Fiche</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch($_GET['msg']) {
                        case 'created': echo "Fiche RDV créée !"; break;
                        case 'updated': echo "Fiche RDV mise à jour !"; break;
                        case 'deleted': echo "Fiche RDV supprimée !"; break;
                        case 'action': echo "Action sur la Fiche exécutée avec succès !"; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="search-bar" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" placeholder="Rechercher Fiche..." style="flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <button onclick="exportPDF()" class="btn btn-secondary">📄 Exporter PDF</button>
        </div>

        <div class="table-container" id="pdf-content">
            <table class="data-table" id="ficheTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)" style="cursor:pointer">ID Fiche ↕</th>
                        <th onclick="sortTable(1)" style="cursor:pointer">RDV Relié ↕</th>
                        <th onclick="sortTable(2)" style="cursor:pointer">Date Gen. PDF ↕</th>
                        <th class="no-print">Email & Calendrier (Badges JS)</th>
                        <th>Tarif ↕</th>
                        <th class="no-print">Actions Rapides SQL</th>
                        <th class="no-print">Édition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($fiches)): ?>
                        <tr><td colspan="7" class="text-center">Aucune Fiche de RDV</td></tr>
                    <?php else: ?>
                        <?php foreach($fiches as $f): ?>
                        <tr>
                            <td>#<?php echo $f['idFiche']; ?></td>
                            <td>
                                ID: <?php echo $f['idRDV']; ?> <br>
                                <small style="color:#666;"><?php echo $f['dateHeureDebut'] ? date('d/m/Y H:i', strtotime($f['dateHeureDebut'])) : ''; ?> - <?php echo htmlspecialchars($f['typeConsultation'] ?? ''); ?></small>
                            </td>
                            <td>
                                <?php echo $f['dateGeneration'] ? date('d/m/Y', strtotime($f['dateGeneration'])) : '<span style="color:#aaa;">Jamais généré</span>'; ?>
                            </td>
                            <td class="no-print">
                                <span class="badge badge-<?php echo $f['emailEnvoye'] ? 'disponible' : 'indisponible'; ?>">
                                    📧 <?php echo $f['emailEnvoye'] ? 'Envoyé' : 'Non Envoyé'; ?>
                                </span><br>
                                <span class="badge badge-<?php echo $f['calendrierAjoute'] ? 'disponible' : 'en-maintenance'; ?>">
                                    📅 <?php echo $f['calendrierAjoute'] ? 'Ajouté' : 'Non Ajouté'; ?>
                                </span>
                            </td>
                            <td><?php echo number_format($f['tarifConsultation'], 2, ',', ' '); ?> €</td>
                            <td class="no-print">
                                <a href="index.php?page=ficherdv&action=marquerGenere&id=<?php echo $f['idFiche']; ?>" class="btn-icon" style="color:#333; text-decoration:none;" title="Set Date de Génération (CURDATE)">📄</a>
                                <?php if(!$f['emailEnvoye']): ?>
                                    <a href="index.php?page=ficherdv&action=marquerEmail&id=<?php echo $f['idFiche']; ?>" class="btn-icon" style="color:red; text-decoration:none;" title="Envoyer Email">✉️</a>
                                <?php endif; ?>
                                <?php if(!$f['calendrierAjoute']): ?>
                                    <a href="index.php?page=ficherdv&action=marquerCalendrier&id=<?php echo $f['idFiche']; ?>" class="btn-icon" style="color:orange; text-decoration:none;" title="Ajouter Calendrier">🗓️</a>
                                <?php endif; ?>
                            </td>
                            <td class="actions no-print">
                                <a href="index.php?page=ficherdv&action=edit&id=<?php echo $f['idFiche']; ?>" class="btn-icon btn-edit" title="Modifier">✏️</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $f['idFiche']; ?>)" class="btn-icon btn-delete" title="Supprimer">🗑️</a>
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
            if(confirm('Supprimer cette Fiche de RDV ?')) window.location.href = 'index.php?page=ficherdv&action=delete&id=' + id;
        }

        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let table = document.getElementById("ficheTable");
            let tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let tdArray = tr[i].getElementsByTagName("td");
                let rc = false;
                for(let j=0; j<tdArray.length-2; j++) {
                    if (tdArray[j] && (tdArray[j].textContent || tdArray[j].innerText).toUpperCase().indexOf(filter) > -1) { rc = true; break; }
                }
                tr[i].style.display = rc ? "" : "none";
            }
        });

        let sortDir = {};
        function sortTable(n) {
            let table = document.getElementById("ficheTable");
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
                    if (dir == "asc") { if (cmpX > cmpY) { shouldSwitch = true; break; } } else { if (cmpX < cmpY) { shouldSwitch = true; break; } }
                }
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true; switchcount++;
                } else if (switchcount == 0 && dir == "asc") { sortDir[n] = "desc"; switching = true; }
            }
            if(switchcount > 0) sortDir[n] = (dir == "asc") ? "desc" : "asc";
        }

        function exportPDF() {
            let elementsToHide = document.querySelectorAll('.no-print');
            elementsToHide.forEach(el => el.style.display = 'none');
            html2pdf().set({ margin: 1, filename: 'ficherdv.pdf', image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 }, jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
            }).from(document.getElementById('pdf-content')).save().then(() => {
                elementsToHide.forEach(el => el.style.display = '');
            });
        }
    </script>
</body>
</html>
