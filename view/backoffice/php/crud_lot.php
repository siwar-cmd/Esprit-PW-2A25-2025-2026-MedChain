<?php
require_once __DIR__ . "/../../../controller/controller_lot.php";

$controller = new controller_lot();

/* ================= ADD ================= */
$errors = [];
$old = [];

if (isset($_POST['add'])) {

    $old = $_POST;

    $result = $controller->ajouter([
        "nom_medicament" => $_POST["nom"],
        "type_medicament" => $_POST["type"],
        "date_fabrication" => $_POST["df"],
        "date_expiration" => $_POST["de"],
        "quantite_initial" => $_POST["qte"],
        "description" => $_POST["desc"]
    ]);

    if (is_array($result)) {
        $errors = $result;

        // 🔥 KEEP MODAL OPEN AFTER ERROR (IMPORTANT FIX)
        $openModal = true;
        $edit = null; // ensure it's treated as ADD mode
    } else {
        header("Location: crud_lot.php");
        exit;
    }
}

/* ================= DELETE ================= */
if (isset($_POST['delete'])) {
    $controller->supprimer($_POST['delete']);
    header("Location: crud_lot.php");
    exit;
}

/* ================= GET FOR EDIT ================= */
$edit = null;
$openModal = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $openModal = true;
}

if (isset($_GET['edit'])) {
    $edit = $controller->getById($_GET['edit']);
    $openModal = true;
}

/* ================= UPDATE ================= */
if (isset($_POST['update'])) {
    $controller->modifier($_POST['id'], [
        "nom_medicament" => $_POST["nom"],
        "type_medicament" => $_POST["type"],
        "date_fabrication" => $_POST["df"],
        "date_expiration" => $_POST["de"],
        "quantite_initial" => $_POST["qte"],
        "description" => $_POST["desc"]
    ]);

    header("Location: crud_lot.php");
    exit;
}

$lots = $controller->afficher();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CRUD LOT</title>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .error {
            color: red;
            font-size: 12px;
            margin-top: 2px;
        }
    </style>
</head>

<body>

<h2>💊 Gestion des Lots Médicaments</h2>

<!-- ================= ADD BUTTON (IMPORTANT RESTORED) ================= -->
<div style="text-align:center; margin-bottom:20px;">
    <button class="btn btn-add" onclick="openModal()">
        ➕ Ajouter Lot
    </button>
</div>

<!-- ================= TABLE ================= -->
<table>
<tr>
    <th>ID</th>
    <th>Nom</th>
    <th>Type</th>
    <th>Date Fabrication</th>
    <th>Date Expiration</th>
    <th>Quantité Initiale</th>
    <th>Quantité Restante</th>
    <th>Description</th>
    <th>Actions</th>
</tr>

<?php foreach ($lots as $lot) { ?>
<tr>
    <td><?= $lot['id_lot'] ?></td>
    <td><?= $lot['nom_medicament'] ?></td>
    <td><?= $lot['type_medicament'] ?></td>
    <td><?= $lot['date_fabrication'] ?></td>
    <td><?= $lot['date_expiration'] ?></td>
    <td><?= $lot['quantite_initial'] ?></td>
    <td><?= $lot['quantite_restante'] ?></td>
    <td><?= $lot['description'] ?></td>

    <td>
        <a href="crud_lot.php?edit=<?= $lot['id_lot'] ?>" class="btn btn-add">✏️ Edit</a>
        <button class="btn btn-delete"
                onclick="openDeleteModal(<?= $lot['id_lot'] ?>)">
            🗑️ Delete
        </button>
    </td>
</tr>
<?php } ?>

</table>

<!-- ================= MODAL ================= -->
<div id="modal" class="modal">
    <div class="modal-content">

        <h3><?= $edit ? "✏️ Modifier Lot" : "➕ Ajouter Lot" ?></h3>

        <form method="POST">

            <input type="hidden" name="id" value="<?= $edit['id_lot'] ?? '' ?>">

            <!-- NOM -->
            <label>Nom médicament</label>
            <input type="text" name="nom"
                value="<?= $edit['nom_medicament'] ?? $old['nom'] ?? '' ?>">
            <div class="error"><?= $errors['nom'] ?? '' ?></div>

            <!-- TYPE -->
            <label>Type médicament</label>
            <input type="text" name="type"
                value="<?= $edit['type_medicament'] ?? $old['type'] ?? '' ?>">
            <div class="error"><?= $errors['type'] ?? '' ?></div>

            <!-- DF -->
            <label>Date fabrication</label>
            <input type="date" name="df"
                value="<?= $edit['date_fabrication'] ?? $old['df'] ?? '' ?>">
            <div class="error"><?= $errors['df'] ?? '' ?></div>

            <!-- DE -->
            <label>Date expiration</label>
            <input type="date" name="de"
                value="<?= $edit['date_expiration'] ?? $old['de'] ?? '' ?>">
            <div class="error"><?= $errors['de'] ?? '' ?></div>

            <!-- QTE -->
            <label>Quantité</label>
            <input type="number" name="qte"
                value="<?= $edit['quantite_initial'] ?? $old['qte'] ?? '' ?>">
            <div class="error"><?= $errors['qte'] ?? '' ?></div>

            <!-- DESC -->
            <label>Description</label>
            <textarea name="desc"><?= $edit['description'] ?? $old['desc'] ?? '' ?></textarea>

            <br><br>

            <?php if ($edit) { ?>
                <button name="update" class="btn btn-add">✏️ Update</button>
            <?php } else { ?>
                <button name="add" class="btn btn-add">💾 Save</button>
            <?php } ?>

            <button type="button" class="btn btn-delete" onclick="closeModal()">Close</button>

        </form>

    </div>
</div>

<!-- ================= DELETE MODAL ================= -->
<div id="deleteModal" class="modal">
    <div class="modal-content">

        <h3>⚠️ Confirmation de suppression</h3>

        <p>Are you sure you want to delete this lot ?</p>

        <form method="POST">
            <input type="hidden" name="delete" id="deleteId">

            <button type="submit" class="btn btn-delete">
                Yes, Delete
            </button>

            <button type="button" class="btn btn-add" onclick="closeDeleteModal()">
                Cancel
            </button>
        </form>

    </div>
</div>

<?php if ($openModal) { ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("modal").style.display = "block";
});
</script>
<?php } ?>

<script src="../js/script.js"></script>

</body>
</html>