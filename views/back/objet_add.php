<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card" style="max-width:760px;">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-plus-circle-fill"></i> Ajouter un Objet Loisir</span>
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $key => $error): ?>
            <?php if (is_int($key)): ?>
                <div class="alert alert-error" style="margin:16px 24px 0;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars(routeUrl('objet', 'add', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="grid">
            <div class="form-group">
                <label for="nom_objet">Nom de l'objet *</label>
                <input type="text" id="nom_objet" name="nom_objet"
                       value="<?php echo htmlspecialchars($_POST['nom_objet'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Ex: Jeu d'échecs">
                <?php if (!empty($errors['nom_objet'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['nom_objet'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="type_objet">Type *</label>
                <select id="type_objet" name="type_objet">
                    <option value="">Sélectionner un type</option>
                    <?php
                    $types = ['Livre', 'Jeu de societe', 'Sport', 'Musique', 'Electronique', 'Casse-tete', 'Film'];
                    $selectedType = $_POST['type_objet'] ?? '';
                    foreach ($types as $type):
                    ?>
                        <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $selectedType === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['type_objet'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['type_objet'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="id_categorie">Catégorie</label>
                <select id="id_categorie" name="id_categorie">
                    <option value="">-- Aucune catégorie --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int) $cat['id_categorie']; ?>"
                                <?php echo (isset($_POST['id_categorie']) && (int)$_POST['id_categorie'] === (int)$cat['id_categorie']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nom_categorie'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantite">Quantité *</label>
                <input type="number" id="quantite" name="quantite" min="0" max="9999"
                       value="<?php echo htmlspecialchars((string) ($_POST['quantite'] ?? 1), ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (!empty($errors['quantite'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['quantite'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="etat">État *</label>
                <select id="etat" name="etat">
                    <option value="">Sélectionner un état</option>
                    <?php
                    $states = ['neuf' => 'Neuf', 'bon' => 'Bon', 'acceptable' => 'Acceptable', 'moyen' => 'Moyen', 'use' => 'Usé'];
                    $selectedState = $_POST['etat'] ?? '';
                    foreach ($states as $val => $label):
                    ?>
                        <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $selectedState === $val ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['etat'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['etat'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Description optionnelle de l'objet..."><?php echo htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <?php if (!empty($errors['description'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn"><i class="bi bi-floppy-fill"></i> Enregistrer</button>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
