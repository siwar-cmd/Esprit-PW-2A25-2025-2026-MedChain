<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header('Location: ../../frontoffice/auth/login.php'); exit; }
require_once __DIR__ . '/../../../controllers/InterventionController.php';
$ctrl = new InterventionController();
$interventions = $ctrl->getAllInterventions();

// Export PDF via HTML
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="interventions_' . date('Y-m-d') . '.pdf"');
// Fallback: export as HTML file that can be printed as PDF
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="interventions_' . date('Y-m-d') . '.html"');
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Interventions - Export</title>
<style>
body{font-family:Arial,sans-serif;margin:20px}
h1{color:#1E3A52;text-align:center;margin-bottom:5px}
p.date{text-align:center;color:#6B7280;margin-bottom:20px}
table{width:100%;border-collapse:collapse;margin-top:10px}
th{background:#1D9E75;color:white;padding:10px;text-align:left;font-size:13px}
td{padding:8px 10px;border-bottom:1px solid #E5E7EB;font-size:12px}
tr:nth-child(even){background:#F8FAFC}
.footer{text-align:center;margin-top:30px;color:#6B7280;font-size:11px}
</style></head><body>
<h1>Liste des Interventions — MedChain</h1>
<p class="date">Généré le <?= date('d/m/Y à H:i') ?></p>
<table>
<tr><th>ID</th><th>Type</th><th>Date</th><th>Durée</th><th>Urgence</th><th>Chirurgien</th><th>Salle</th><th>Description</th></tr>
<?php foreach($interventions as $i): ?>
<tr>
<td><?=$i['id']?></td>
<td><?=htmlspecialchars($i['type'])?></td>
<td><?=date('d/m/Y',strtotime($i['date_intervention']))?></td>
<td><?=$i['duree']?> min</td>
<td><?php $l=[1=>'Faible',2=>'Modéré',3=>'Élevé',4=>'Urgent',5=>'Critique']; echo $l[$i['niveau_urgence']]??$i['niveau_urgence']; ?></td>
<td><?=htmlspecialchars($i['chirurgien'])?></td>
<td><?=htmlspecialchars($i['salle']??'-')?></td>
<td><?=htmlspecialchars($i['description']??'-')?></td>
</tr>
<?php endforeach; ?>
</table>
<p class="footer">Total: <?=count($interventions)?> intervention(s) — MedChain © <?=date('Y')?></p>
</body></html>
