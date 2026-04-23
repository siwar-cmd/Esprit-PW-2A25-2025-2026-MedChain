<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">All Loans</h1>
        <div class="actions">
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'pending', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Pending loans</a>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirmed', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Active loans</a>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'cancelled'): ?>
        <div class="alert alert-success">Loan cancelled successfully.</div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if (empty($prets)): ?>
        <p>No loans found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Object</th>
                    <th>Type</th>
                    <th>Loan date</th>
                    <th>Return date</th>
                    <th>Status</th>
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
                        <td><?php echo htmlspecialchars($pret['date_retour_effective'] ? date('d/m/Y', strtotime($pret['date_retour_effective'])) : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="status status-<?php echo htmlspecialchars($pret['statut'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($pret['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if ($pret['statut'] === 'en_attente'): ?>
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirm', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success" onclick="return confirm('Confirm this loan?');">Confirm</a>
                                <?php endif; ?>
                                <?php if ($pret['statut'] === 'en_cours'): ?>
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'return', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success" onclick="return confirm('Return this object?');">Return</a>
                                <?php endif; ?>
                                <?php if (in_array($pret['statut'], ['en_attente', 'en_cours'], true)): ?>
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'cancel', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger" onclick="return confirm('Cancel this loan?');">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
