<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Edit Object</h1>
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Back to list</a>
    </div>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars(routeUrl('objet', 'edit', ['office' => 'back', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="grid">
            <div class="form-group">
                <label for="nom_objet">Name *</label>
                <input type="text" id="nom_objet" name="nom_objet" value="<?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="type_objet">Type *</label>
                <select id="type_objet" name="type_objet">
                    <option value="">Select a type</option>
                    <?php
                    $types = ['Livre', 'Jeu de societe', 'Sport', 'Musique', 'Electronique', 'Casse-tete', 'Film'];
                    foreach ($types as $type):
                    ?>
                        <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $objet['type_objet'] === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantite">Quantity *</label>
                <input type="number" id="quantite" name="quantite" min="0" value="<?php echo htmlspecialchars((string) $objet['quantite'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="etat">Condition *</label>
                <select id="etat" name="etat">
                    <option value="">Select a condition</option>
                    <?php
                    $states = ['neuf', 'bon', 'acceptable', 'moyen', 'use'];
                    foreach ($states as $state):
                    ?>
                        <option value="<?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $objet['etat'] === $state ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($state), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($objet['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-success">Update</button>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
