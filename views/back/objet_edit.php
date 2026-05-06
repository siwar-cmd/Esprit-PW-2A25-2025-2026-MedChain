<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card" style="max-width:760px;">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-pencil-fill"></i> Modifier l'Objet — <?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?></span>
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error" style="margin:16px 24px 0;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars(routeUrl('objet', 'edit', ['office' => 'back', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="grid">
            <div class="form-group">
                <label for="nom_objet">Nom de l'objet *</label>
                <input type="text" id="nom_objet" name="nom_objet"
                       value="<?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="type_objet">Type *</label>
                <select id="type_objet" name="type_objet">
                    <option value="">Sélectionner un type</option>
                    <?php
                    $types = ['Livre', 'Jeu de societe', 'Sport', 'Musique', 'Electronique', 'Casse-tete', 'Film'];
                    foreach ($types as $type):
                    ?>
                        <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $objet['type_objet'] === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_categorie">Catégorie</label>
                <select id="id_categorie" name="id_categorie">
                    <option value="">-- Aucune catégorie --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int) $cat['id_categorie']; ?>"
                                <?php echo (int)($objet['id_categorie'] ?? 0) === (int)$cat['id_categorie'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nom_categorie'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantite">Quantité *</label>
                <input type="number" id="quantite" name="quantite" min="0" max="9999"
                       value="<?php echo htmlspecialchars((string) $objet['quantite'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="etat">État *</label>
                <select id="etat" name="etat">
                    <option value="">Sélectionner un état</option>
                    <?php
                    $states = ['neuf' => 'Neuf', 'bon' => 'Bon', 'acceptable' => 'Acceptable', 'moyen' => 'Moyen', 'use' => 'Usé'];
                    foreach ($states as $val => $label):
                    ?>
                        <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $objet['etat'] === $val ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($objet['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn"><i class="bi bi-floppy-fill"></i> Mettre à jour</button>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
