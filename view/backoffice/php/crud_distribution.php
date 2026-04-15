<?php
require_once "../../../controller/controller_distribution.php";
require_once "../../../controller/controller_lot.php";

$controller = new controller_distribution();
$lotController = new controller_lot();

/* ======================
   ADD DISTRIBUTION
====================== */
if (isset($_POST['add'])) {

    $data = [
        "id_lot" => $_POST["id_lot"],
        "quantite_distribuee" => $_POST["qte"],
        "destinataire" => $_POST["dest"],
        "responsable" => $_POST["resp"]
    ];

    $result = $controller->ajouter($data);

    if (!$result) {
        $message = "❌ Stock insuffisant !";
    } else {
        $message = "✅ Distribution ajoutée";
    }
}

/* ======================
   DELETE DISTRIBUTION
====================== */
if (isset($_GET['delete'])) {
    $controller->supprimer($_GET['delete']);
    header("Location: crud_distribution.php");
    exit;
}

/* ======================
   LIST DATA
====================== */
$distributions = $controller->afficher();
$lots = $lotController->afficher();
?>

<h2>💊 CRUD DISTRIBUTION CONTRÔLÉE</h2>

<!-- MESSAGE -->
<?php if (isset($message)) { ?>
    <p style="color:red;">
        <?= $message ?>
    </p>
<?php } ?>

<!-- ================= ADD FORM ================= -->
<form method="POST">

    Lot:
    <select name="id_lot" required>
        <option value="">-- Choisir lot --</option>
        <?php foreach ($lots as $lot) { ?>
            <option value="<?= $lot['id_lot'] ?>">
                <?= $lot['nom_medicament'] ?> (Stock: <?= $lot['quantite_restante'] ?>)
            </option>
        <?php } ?>
    </select><br><br>

    Quantité:
    <input type="number" name="qte" required><br><br>

    Destinataire:
    <input type="text" name="dest" required><br><br>

    Responsable:
    <input type="text" name="resp" required><br><br>

    <button name="add">➕ Ajouter distribution</button>
</form>

<hr>

<!-- ================= LIST ================= -->
<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Lot ID</th>
    <th>Quantité</th>
    <th>Destinataire</th>
    <th>Responsable</th>
    <th>Date</th>
    <th>Action</th>
</tr>

<?php foreach ($distributions as $d) { ?>
<tr>
    <td><?= $d['id_distribution'] ?></td>
    <td><?= $d['id_lot'] ?></td>
    <td><?= $d['quantite_distribuee'] ?></td>
    <td><?= $d['destinataire'] ?></td>
    <td><?= $d['responsable'] ?></td>
    <td><?= $d['date_distribution'] ?></td>

    <td>
        <a href="crud_distribution.php?delete=<?= $d['id_distribution'] ?>"
           onclick="return confirm('Supprimer cette distribution ?')">
           🗑️ Delete
        </a>
    </td>
</tr>
<?php } ?>

</table>