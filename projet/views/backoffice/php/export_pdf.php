<?php
require_once "../../../controller/controller_lot.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestion_4/tcpdf/tcpdf.php';

$c = new LotController();
$lots = $c->getAll();

$pdf = new TCPDF();
$pdf->SetCreator("Gestion Medicaments");
$pdf->SetTitle("Rapport des Médicaments");
$pdf->AddPage();

$today = date('Y-m-d');
$seen = [];

// ================= TITLE =================
$html = '
<h2 style="text-align:center;color:#2c3e50;">📋 Rapport des Médicaments</h2>
<br>
';

// ================= TABLE =================
$html .= '
<table border="1" cellspacing="0" cellpadding="5">
<thead>
<tr style="background-color:#1f3a5f;color:#ffffff;">
    <th><b>Nom Médicament</b></th>
    <th><b>Quantité</b></th>
    <th><b>Expiration</b></th>
    <th><b>État</b></th>
</tr>
</thead>
<tbody>
';

foreach ($lots as $l) {

    // éviter doublons
    if (in_array($l['nom_medicament'], $seen)) {
        continue;
    }
    $seen[] = $l['nom_medicament'];

    // ================= ÉTAT (MODIFICATION UNIQUEMENT ICI) =================
    if ($l['date_expiration'] < $today) {
        $etat = "Quantité expirée";
        $color = "#e74c3c";
    }
    elseif (strtotime($l['date_expiration']) <= strtotime("+7 days")) {
        $etat = "Quantité presque expirée";
        $color = "#f39c12";
    }
    elseif ($l['quantite_restante'] < 10) {
        $etat = "Quantité faible";
        $color = "#d4a017";
    }
    else {
        $etat = "Quantité normale";
        $color = "#2ecc71";
    }

    $html .= '
    <tr>
        <td><b>'.$l['nom_medicament'].'</b></td>
        <td><b>'.$l['quantite_restante'].'</b></td>
        <td><b>'.$l['date_expiration'].'</b></td>
        <td style="color:'.$color.';"><b>'.$etat.'</b></td>
    </tr>
    ';
}

$html .= '
</tbody>
</table>
';

// ================= OUTPUT =================
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output("rapport_medicaments.pdf", "I");
?>