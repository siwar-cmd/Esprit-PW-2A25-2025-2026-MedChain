<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Search My Loans</h1>
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Browse objects</a>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <p>Enter the patient name to display the corresponding loan history.</p>

    <form method="GET" action="<?php echo htmlspecialchars(routeUrl('pret', 'myLoans', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="office" value="front">
        <input type="hidden" name="controller" value="pret">
        <input type="hidden" name="action" value="myLoans">

        <div class="form-group">
            <label for="patient">Patient name</label>
            <input type="text" id="patient" name="patient" value="<?php echo htmlspecialchars($_GET['patient'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <button type="submit" class="btn">Search</button>
    </form>
</div>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
