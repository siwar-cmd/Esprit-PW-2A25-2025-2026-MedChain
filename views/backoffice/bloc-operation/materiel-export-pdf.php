<?php
session_start();

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/BlocOperationController.php';

$controller = new BlocOperationController();
$materielData = $controller->getMaterielList(1, 1000);
$materiels = $materielData['data'] ?? [];

// Compter par disponibilité
$countByStatus = [];
foreach ($materiels as $m) {
    $status = $m['disponibilite'] ?? 'Non défini';
    $countByStatus[$status] = ($countByStatus[$status] ?? 0) + 1;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport du Matériel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #1D9E75;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #1D9E75;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        table thead {
            background-color: #1D9E75;
            color: white;
        }
        
        table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #ddd;
        }
        
        table td {
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        table tbody tr:hover {
            background-color: #f0faf6;
        }
        
        .summary {
            margin-bottom: 30px;
            background: #f9fafb;
            border-left: 4px solid #1D9E75;
            padding: 20px;
            border-radius: 4px;
        }
        
        .summary h2 {
            color: #1D9E75;
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            font-weight: 600;
            color: #374151;
        }
        
        .summary-value {
            color: #1D9E75;
            font-weight: bold;
        }
        
        .print-only {
            display: none;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .no-print {
                display: none !important;
            }
            
            .print-only {
                display: block;
            }
            
            .container {
                padding: 0;
            }
            
            table {
                page-break-inside: avoid;
            }
            
            tr {
                page-break-inside: avoid;
            }
        }
        
        .btn-print {
            background: #1D9E75;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .btn-print:hover {
            background: #0F6E56;
        }
        
        .no-print {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button id="btnPrint" class="btn-print" onclick="window.print()">
                🖨️ Imprimer / Sauvegarder en PDF
            </button>
            <button id="btnSaveDrive" class="btn-print" style="background:#1967d2; margin-left:10px;" title="Enregistrer sur Google Drive">
                <i style="margin-right:6px">⬆️</i> Sauvegarder sur Google Drive
            </button>
        </div>
        
        <div class="header">
            <h1>📋 Rapport du Matériel</h1>
            <p>Généré le <?= date('d/m/Y à H:i:s') ?></p>
        </div>
        
        <?php if (!empty($materiels)): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Nom</th>
                    <th style="width: 12%;">Catégorie</th>
                    <th style="width: 18%;">Intervention</th>
                    <th style="width: 13%;">Disponibilité</th>
                    <th style="width: 12%;">Stérilisation</th>
                    <th style="width: 15%;">Utilisations (max)</th>
                    <th style="width: 15%;">Utilisations (act.)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materiels as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nom'] ?? $item['id_materiel'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['categorie'] ?? '-') ?></td>
                    <td><?= !empty($item['id_intervention']) ? '#' . $item['id_intervention'] . ' - ' . htmlspecialchars($item['intervention_type'] ?? '') : '-' ?></td>
                    <td><?= htmlspecialchars($item['disponibilite'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['statutSterilisation'] ?? '-') ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($item['nombreUtilisationsMax'] ?? 0) ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($item['nombreUtilisationsActuelles'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="summary">
            <h2>📊 Résumé Statistique</h2>
            
            <div class="summary-item">
                <span class="summary-label">Total matériel:</span>
                <span class="summary-value"><?= count($materiels) ?></span>
            </div>
            
            <?php foreach ($countByStatus as $status => $count): ?>
            <div class="summary-item">
                <span class="summary-label">● <?= htmlspecialchars($status) ?></span>
                <span class="summary-value"><?= $count ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; color: #999;">
            <p style="font-size: 18px;">Aucun matériel trouvé</p>
        </div>
        <?php endif; ?>
        
        <div class="no-print" style="margin-top: 40px; text-align: center;">
            <?php $retourUrl = ($_SESSION['user_role'] === 'medecin') ? 'medecin-materiel-index.php' : 'materiel-index.php'; ?>
            <a href="<?= $retourUrl ?>" style="color: #1D9E75; text-decoration: none; font-weight: 600;">
                ← Retour à la liste
            </a>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
    <script>
        (function(){
            const btn = document.getElementById('btnSaveDrive');
            btn.addEventListener('click', function(){
                btn.disabled = true;
                btn.textContent = 'Génération...';
                const element = document.querySelector('.container');
                const filename = 'rapport_materiel_' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.pdf';
                html2pdf().from(element).set({margin:10, filename: filename, image: {type: 'jpeg', quality: 0.98}, html2canvas: {scale: 2}}).outputPdf('blob').then(function(blob){
                    const form = new FormData();
                    form.append('file', blob, filename);
                    form.append('filename', filename);
                    fetch('../../../controllers/upload_to_drive.php', { method: 'POST', body: form }).then(r => r.json()).then(data => {
                        btn.disabled = false;
                        if (data && data.success) {
                            const link = data.file['webViewLink'] ?? null;
                            if (link) {
                                alert('Fichier enregistré sur Google Drive. Ouvrir: ' + link);
                                window.open(link, '_blank');
                            } else {
                                alert('Fichier enregistré sur Google Drive. ID: ' + (data.file.id ?? 'inconnu'));
                            }
                        } else {
                            console.error(data);
                            alert('Échec de l\'enregistrement sur Google Drive. Voir console pour détails.');
                        }
                    }).catch(err => {
                        console.error(err);
                        alert('Erreur lors de l\'upload vers Drive');
                        btn.disabled = false;
                    }).finally(()=>{ btn.textContent = 'Sauvegarder sur Google Drive'; });
                });
            });
        })();
    </script>
</body>
</html>
