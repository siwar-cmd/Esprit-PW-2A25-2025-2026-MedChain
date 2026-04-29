<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traçabilité - Distributions - MedChain</title>
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
            <h1>Distributions Contrôlées</h1>
            <div class="header-actions">
                <a href="index.php?page=distribution&action=stats" class="btn btn-secondary">📊 Stats</a>
                <a href="index.php?page=distribution&action=create" class="btn btn-primary"><i>➕</i> Nouvelle Transfert</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch($_GET['msg']) {
                        case 'created': echo "Distribution enregistrée avec succès (Stock déduit) !"; break;
                        case 'updated': echo "Distribution mise à jour (Stocks réajustés) !"; break;
                        case 'deleted': echo "Distribution annulée (Quantité restituée au Lot Mère) !"; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="search-bar" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" placeholder="Rechercher Médicament, Dest..." style="flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <button onclick="exportPDF()" class="btn btn-secondary">📄 Exporter PDF</button>
        </div>

        <div class="table-container" id="pdf-content">
            <table class="data-table" id="distTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)" style="cursor:pointer">Ref. Trans. ↕</th>
                        <th onclick="sortTable(1)" style="cursor:pointer">Source (Lot Mère) ↕</th>
                        <th onclick="sortTable(2)" style="cursor:pointer">Qté Distribuée ↕</th>
                        <th onclick="sortTable(3)" style="cursor:pointer">Date & Heure ↕</th>
                        <th onclick="sortTable(4)" style="cursor:pointer">Destinataire / Service ↕</th>
                        <th class="no-print">Édition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($distributions)): ?>
                        <tr><td colspan="6" class="text-center">Aucune distribution enregistrée</td></tr>
                    <?php else: ?>
                        <?php foreach($distributions as $d): ?>
                        <tr>
                            <td>#<?php echo $d['idDistribution']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($d['nomMedicament'] ?? 'Médicament Inconnu'); ?></strong><br>
                                <small style="color:#666;">(Lot: <?php echo htmlspecialchars($d['numeroLot'] ?? '???'); ?>)</small>
                            </td>
                            <td><span style="color:red;">-<?php echo $d['quantite']; ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($d['dateDistribution'])); ?></td>
                            <td><?php echo htmlspecialchars($d['destinataire']); ?></td>
                            <td class="actions no-print">
                                <!-- tooltip rappelant que ca reverse le stock -->
                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $d['idDistribution']; ?>)" class="btn-icon btn-delete" title="Restituer">🔙</a>
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
            if(confirm('Annuler cette distribution ? La quantité sera reversée au Lot Mère.')) {
                window.location.href = 'index.php?page=distribution&action=delete&id=' + id;
            }
        }

        // --- RECHERCHE & TRI & PDF Moteurs standard ---
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let table = document.getElementById("distTable");
            let tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let tdArray = tr[i].getElementsByTagName("td");
                let rc = false;
                for(let j=0; j<tdArray.length-1; j++) {
                    if (tdArray[j] && (tdArray[j].textContent || tdArray[j].innerText).toUpperCase().indexOf(filter) > -1) { rc = true; break; }
                }
                tr[i].style.display = rc ? "" : "none";
            }
        });

        let sortDir = {};
        function sortTable(n) {
            let table = document.getElementById("distTable");
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
            html2pdf().set({ margin: 1, filename: 'distributions.pdf', image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 }, jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
            }).from(document.getElementById('pdf-content')).save().then(() => {
                elementsToHide.forEach(el => el.style.display = '');
            });
        }
    </script>
</body>
</html>
