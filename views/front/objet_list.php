<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Available Objects</h1>
        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'myLoans', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Check my loans</a>
    </div>

    <?php
    $errorMessages = [
        'not_found' => 'The requested object was not found.',
    ];
    ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'requested'): ?>
        <div class="alert alert-success">Loan request sent successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'], $errorMessages[$_GET['error']])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($errorMessages[$_GET['error']], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors ?? [])): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="GET" action="">
        <div style="display: flex; gap: 10px; margin: 20px 0;">
            <input 
                type="text" 
                name="search" 
                placeholder="Search by object name..."
                value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                class="form-control"
            >
            <button type="submit" class="btn">Search</button>
        </div>
    </form>

    <?php if (empty($objets)): ?>
        <p>No objects are available right now.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($objets as $objet): ?>
                <div class="card" style="margin: 0;">
                    <h2 style="margin-bottom: 12px;"><?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($objet['type_objet'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Condition:</strong> <?php echo htmlspecialchars($objet['etat'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Quantity:</strong> <?php echo (int) $objet['quantite']; ?></p>
                    <p style="margin-top: 10px;">
                        <span class="status status-<?php echo htmlspecialchars($objet['disponibilite'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars(ucfirst($objet['disponibilite']), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </p>

                    <div class="actions" style="margin-top: 16px;">
                        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'detail', ['office' => 'front', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn">Details</a>
                        <?php if ($objet['disponibilite'] === 'disponible' && (int) $objet['quantite'] > 0): ?>
                            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'create', ['office' => 'front', 'objet_id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">Request loan</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
