<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Pending Loan Requests</h1>
        <div class="actions">
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirmed', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Active loans</a>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">All loans</a>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'confirmed'): ?>
        <div class="alert alert-success">Loan confirmed successfully.</div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if (empty($prets)): ?>
        <p>No pending requests.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Object</th>
                    <th>Type</th>
                    <th>Request date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prets as $pret): ?>
                    <tr>
                        <td><?php echo (int) $pret['id_pret']; ?></td>
                        <td><?php echo htmlspecialchars($pret['nom_patient'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pret['objet_nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pret['objet_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_pret'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <div class="actions">
                                <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirm', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success" onclick="return confirm('Confirm this loan?');">Confirm</a>
                                <a href="<?php echo htmlspecialchars(routeUrl('pret', 'cancel', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger" onclick="return confirm('Cancel this request?');">Cancel</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
