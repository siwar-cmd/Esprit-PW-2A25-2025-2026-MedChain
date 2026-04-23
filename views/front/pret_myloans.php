<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">My Loans - <?php echo htmlspecialchars($nomPatient, ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="actions">
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'myLoans', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Search again</a>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Browse objects</a>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'cancelled'): ?>
        <div class="alert alert-success">Loan cancelled successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'returned'): ?>
        <div class="alert alert-success">Object returned successfully.</div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if (empty($prets)): ?>
        <p>No loans found for this patient.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
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
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'cancel', ['office' => 'front', 'id' => (int) $pret['id_pret'], 'patient' => $nomPatient]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger" onclick="return confirm('Cancel this request?');">Cancel</a>
                                <?php endif; ?>
                                <?php if ($pret['statut'] === 'en_cours'): ?>
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'return', ['office' => 'front', 'id' => (int) $pret['id_pret'], 'patient' => $nomPatient]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success" onclick="return confirm('Return this object?');">Return</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
