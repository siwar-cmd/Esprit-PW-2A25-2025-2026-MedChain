<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Rendez-Vous - MedChain</title>
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
            <h1>Agenda des Rendez-Vous</h1>
            <div class="header-actions">
                <a href="index.php?page=rdv&action=stats" class="btn btn-secondary">📊 Stats RDV</a>
                <a href="index.php?page=rdv&action=create" class="btn btn-primary"><i>➕</i> Nouveau RDV</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch($_GET['msg']) {
                        case 'created': echo "RDV planifié avec succès !"; break;
                        case 'updated': echo "RDV mis à jour !"; break;
                        case 'deleted': echo "RDV supprimé !"; break;
                        case 'confirmed': echo "Le statut a été mis à jour : Confirmé !"; break;
                        case 'canceled': echo "Ce rendez-vous a été annulé avec succès."; break;
                        case 'postponed': echo "La date du rendez-vous a bien été reportée !"; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="search-bar" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" placeholder="Rechercher RDV..." style="flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <button onclick="exportPDF()" class="btn btn-secondary">📄 Exporter PDF</button>
        </div>

        <div class="table-container" id="pdf-content">
            <table class="data-table" id="rdvTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)" style="cursor:pointer">ID ↕</th>
                        <th onclick="sortTable(1)" style="cursor:pointer">Date Début ↕</th>
                        <th onclick="sortTable(2)" style="cursor:pointer">Type ↕</th>
                        <th onclick="sortTable(3)" style="cursor:pointer">Statut ↕</th>
                        <th onclick="sortTable(4)" style="cursor:pointer">Motif ↕</th>
                        <th class="no-print">Actions Rapides</th>
                        <th class="no-print">Édition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($rdvs)): ?>
                        <tr><td colspan="7" class="text-center">Aucun rendez-vous</td></tr>
                    <?php else: ?>
                        <?php foreach($rdvs as $r): ?>
                        <tr>
                            <td><?php echo $r['idRDV']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($r['dateHeureDebut'])); ?></td>
                            <td><?php echo htmlspecialchars($r['typeConsultation']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $r['statut'] == 'confirme' ? 'disponible' : ($r['statut'] == 'annule' ? 'indisponible' : 'en-maintenance'); ?>">
                                    <?php echo strtoupper($r['statut']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($r['motif']); ?></td>
                            <td class="no-print">
                                <?php if($r['statut'] != 'confirme' && $r['statut'] != 'annule'): ?>
                                    <a href="index.php?page=rdv&action=confirmer&id=<?php echo $r['idRDV']; ?>" class="btn-icon" style="color:green; text-decoration:none;" title="Confirmer">✅</a>
                                <?php endif; ?>
                                <?php if($r['statut'] != 'annule'): ?>
                                    <a href="javascript:void(0)" onclick="confirmCancel(<?php echo $r['idRDV']; ?>)" class="btn-icon" style="color:red; text-decoration:none;" title="Annuler">⛔</a>
                                    <a href="javascript:void(0)" onclick="confirmReport(<?php echo $r['idRDV']; ?>)" class="btn-icon" style="color:orange; text-decoration:none;" title="Reporter">🗓️</a>
                                <?php endif; ?>
                            </td>
                            <td class="actions no-print">
                                <a href="index.php?page=rdv&action=edit&id=<?php echo $r['idRDV']; ?>" class="btn-icon btn-edit" title="Modifier">✏️</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $r['idRDV']; ?>)" class="btn-icon btn-delete" title="Supprimer">🗑️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Forms cachés pour JS Prompts -->
        <form id="cancelForm" method="POST" style="display:none;"><input type="hidden" name="raison" id="cancelRaison"></form>
        <form id="reportForm" method="POST" style="display:none;"><input type="hidden" name="newDate" id="reportDate"></form>

    </main>

    <script>
        function confirmDelete(id) {
            if(confirm('Supprimer ce RDV ? Sa fiche liée sera détruite en cascade !')) { window.location.href = 'index.php?page=rdv&action=delete&id=' + id; }
        }
        function confirmCancel(id) {
            let raison = prompt("Motif de l'annulation :");
            if(raison !== null && raison.trim() !== '') {
                document.getElementById('cancelRaison').value = raison;
                let form = document.getElementById('cancelForm');
                form.action = 'index.php?page=rdv&action=annuler&id=' + id;
                form.submit();
            } else if (raison !== null) { alert("Le motif est obligatoire."); }
        }
        function confirmReport(id) {
            let newd = prompt("Nouvelle date du RDV (Format: AAAA-MM-JJ HH:MM:00) :");
            let rgx = /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/;
            if(newd !== null) {
                if(rgx.test(newd)) {
                    document.getElementById('reportDate').value = newd;
                    let form = document.getElementById('reportForm');
                    form.action = 'index.php?page=rdv&action=reporter&id=' + id;
                    form.submit();
                } else { alert("Format invalide."); }
            }
        }

        // --- RECHERCHE & TRI & PDF Moteurs standard ---
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let table = document.getElementById("rdvTable");
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
            let table = document.getElementById("rdvTable");
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
            html2pdf().set({ margin: 1, filename: 'rdv.pdf', image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 }, jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
            }).from(document.getElementById('pdf-content')).save().then(() => {
                elementsToHide.forEach(el => el.style.display = '');
            });
        }
    </script>
</body>
</html>
