<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traçabilité - Lots Médicaments - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .alerte-peremption {
            animation: clignoter 1.5s infinite;
            background-color: #ffcccc !important;
            border-left: 5px solid red;
        }
        @keyframes clignoter { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
    </style>
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
                    <a href="#" class="dropbtn active">Traçabilité ⬇</a>
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
                <li><a href="blog.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>Stock Mère (Lots)</h1>
            <div class="header-actions">
                <a href="index.php?page=lot&action=stats" class="btn btn-secondary">📊 Stats Lots</a>
                <a href="index.php?page=lot&action=create" class="btn btn-primary"><i>➕</i> Ajouter Lot</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch($_GET['msg']) {
                        case 'created': echo "Lot enregistré avec succès !"; break;
                        case 'updated': echo "Lot mis à jour !"; break;
                        case 'deleted': echo "Lot supprimé !"; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="search-bar" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" placeholder="Rechercher Médicament, Num..." style="flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <button onclick="exportPDF()" class="btn btn-secondary">📄 Exporter PDF</button>
        </div>

        <div class="table-container" id="pdf-content">
            <table class="data-table" id="lotTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)" style="cursor:pointer">ID ↕</th>
                        <th onclick="sortTable(1)" style="cursor:pointer">Médicament ↕</th>
                        <th onclick="sortTable(2)" style="cursor:pointer">N° Lot ↕</th>
                        <th onclick="sortTable(3)" style="cursor:pointer">Qté Restante ↕</th>
                        <th onclick="sortTable(4)" style="cursor:pointer">Date Péremption ↕</th>
                        <th class="no-print">État (Calculé)</th>
                        <th class="no-print">Édition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($lots)): ?>
                        <tr><td colspan="7" class="text-center">Aucun lot de médicament</td></tr>
                    <?php else: ?>
                        <?php foreach($lots as $l): ?>
                        <tr class="<?php echo $l['estPerime'] ? 'alerte-peremption' : ''; ?>">
                            <td>#<?php echo $l['idLot']; ?></td>
                            <td><?php echo htmlspecialchars($l['nomMedicament']); ?></td>
                            <td><?php echo htmlspecialchars($l['numeroLot']); ?></td>
                            <td><strong><?php echo $l['quantite']; ?></strong> unit.</td>
                            <td><?php echo date('d/m/Y', strtotime($l['datePeremption'])); ?></td>
                            <td class="no-print">
                                <?php if($l['estPerime']): ?>
                                    <span class="badge badge-indisponible">⚠️ PÉRIMÉ</span>
                                <?php else: ?>
                                    <span class="badge badge-disponible">✅ VALIDE</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions no-print">
                                <a href="index.php?page=lot&action=edit&id=<?php echo $l['idLot']; ?>" class="btn-icon btn-edit" title="Modifier">✏️</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $l['idLot']; ?>)" class="btn-icon btn-delete" title="Supprimer">🗑️</a>
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
            if(confirm('Supprimer ce Lot ?')) { window.location.href = 'index.php?page=lot&action=delete&id=' + id; }
        }

        // --- RECHERCHE & TRI & PDF Moteurs standard ---
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let table = document.getElementById("lotTable");
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
            let table = document.getElementById("lotTable");
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
            html2pdf().set({ margin: 1, filename: 'lots.pdf', image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 }, jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
            }).from(document.getElementById('pdf-content')).save().then(() => {
                elementsToHide.forEach(el => el.style.display = '');
            });
        }
    </script>
</body>
</html>
