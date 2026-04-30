<?php
require_once "../../../controllers/controller_distribution.php";
require_once "../../../tcpdf/tcpdf.php";

$c = new DistributionController();
$data = $c->list();

$pdf = new TCPDF();
$pdf->AddPage();

$html = '
<h2>Liste des Distributions</h2>
<table border="1" cellpadding="5">
<tr>
<th>ID</th>
<th>Lot</th>
<th>Date</th>
<th>Quantité</th>
<th>Patient</th>
<th>Responsable</th>
</tr>
';

foreach ($data as $d) {
    $html .= '
    <tr>
        <td>'.$d['id_distribution'].'</td>
        <td>'.$d['id_lot'].'</td>
        <td>'.$d['date_distribution'].'</td>
        <td>'.$d['quantite_distribuee'].'</td>
        <td>'.$d['patient'].'</td>
        <td>'.$d['responsable'].'</td>
    </tr>';
}

$html .= '</table>';

$pdf->writeHTML($html);
$pdf->Output("distributions.pdf", "I");