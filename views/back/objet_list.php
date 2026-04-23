<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Object Management</h1>
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'add', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn">Add object</a>
    </div>

    <?php
    $messages = [
        'added' => 'Object added successfully.',
        'updated' => 'Object updated successfully.',
        'deleted' => 'Object deleted successfully.',
    ];
    $errorsMap = [
        'linked_to_loans' => 'This object is linked to at least one loan and cannot be deleted.',
        'not_found' => 'The requested object was not found.',
    ];
    ?>

    <?php if (isset($_GET['success'], $messages[$_GET['success']])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($messages[$_GET['success']], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'], $errorsMap[$_GET['error']])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($errorsMap[$_GET['error']], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (empty($objets)): ?>
        <p>No objects found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Condition</th>
                    <th>Availability</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($objets as $objet): ?>
                    <tr>
                        <td><?php echo (int) $objet['id_objet']; ?></td>
                        <td><?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($objet['type_objet'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $objet['quantite']; ?></td>
                        <td><?php echo htmlspecialchars($objet['etat'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="status status-<?php echo htmlspecialchars($objet['disponibilite'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars(ucfirst($objet['disponibilite']), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($objet['description'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <div class="actions">
                                <a href="<?php echo htmlspecialchars(routeUrl('objet', 'edit', ['office' => 'back', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn">Edit</a>
                                <a href="<?php echo htmlspecialchars(routeUrl('objet', 'delete', ['office' => 'back', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger" onclick="return confirm('Delete this object?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
