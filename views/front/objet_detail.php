<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Object Details</h1>
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Back to objects</a>
    </div>

    <div class="grid">
        <div>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($objet['type_objet'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Condition:</strong> <?php echo htmlspecialchars($objet['etat'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Quantity:</strong> <?php echo (int) $objet['quantite']; ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($objet['description'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="margin-top: 12px;">
                <span class="status status-<?php echo htmlspecialchars($objet['disponibilite'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(ucfirst($objet['disponibilite']), ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </p>
        </div>

        <div>
            <?php if ($objet['disponibilite'] === 'disponible' && (int) $objet['quantite'] > 0): ?>
                <a href="<?php echo htmlspecialchars(routeUrl('pret', 'create', ['office' => 'front', 'objet_id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">Request this object</a>
            <?php else: ?>
                <div class="alert alert-error">This object is currently unavailable.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
