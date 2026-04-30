<?php
session_start();

if (!isset($_SESSION['user_role'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once "../../../controllers/controller_lot.php";
require_once "../../../tcpdf/tcpdf.php";

$c = new LotController();
$lots = $c->getAll(); // you can add search later if needed

// ================= PDF INIT =================
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('MedChain');
$pdf->SetAuthor('MedChain System');
$pdf->SetTitle('Liste des Lots Médicaments');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->AddPage();

// ================= TITLE =================
$html = '
<h2 style="text-align:center;color:#1D9E75;">Liste des Lots de Médicaments</h2>
<br>
<table border="1" cellpadding="6">
    <thead>
        <tr style="background-color:#1D9E75;color:white;">
            <th><b>ID</b></th>
            <th><b>Nom Médicament</b></th>
            <th><b>Type</b></th>
            <th><b>Date Fabrication</b></th>
            <th><b>Date Expiration</b></th>
            <th><b>Quantité Initiale</b></th>
            <th><b>Quantité Restante</b></th>
        </tr>
    </thead>
    <tbody>
';

foreach ($lots as $l) {
    $html .= '
        <tr>
            <td>'.$l['id_lot'].'</td>
            <td>'.htmlspecialchars($l['nom_medicament']).'</td>
            <td>'.htmlspecialchars($l['type_medicament']).'</td>
            <td>'.$l['date_fabrication'].'</td>
            <td>'.$l['date_expiration'].'</td>
            <td>'.$l['quantite_initial'].'</td>
            <td>'.$l['quantite_restante'].'</td>
        </tr>
    ';
}

$html .= '
    </tbody>
</table>
';

// ================= OUTPUT =================
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('lots_medicamenteux.pdf', 'I');