<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-pencil-fill"></i> Modifier la Catégorie</span>
        <a href="<?php echo htmlspecialchars(routeUrl('categorie', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <?php if (!empty($errors) && is_array($errors)): ?>
        <?php foreach ($errors as $key => $msg): ?>
            <?php if (is_int($key)): ?>
                <div class="alert alert-error" style="margin:16px 24px 0;">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" id="catForm"
          action="<?php echo htmlspecialchars(routeUrl('categorie', 'edit', ['office' => 'back', 'id' => (int) $categorie['id_categorie']]), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="grid">
            <div class="form-group" style="grid-column:1/-1;">
                <label for="nom_categorie">Nom de la catégorie *</label>
                <input type="text" id="nom_categorie" name="nom_categorie"
                       value="<?php echo htmlspecialchars($categorie['nom_categorie'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-error" id="err_nom"></div>
                <?php if (!empty($errors['nom_categorie'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['nom_categorie'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label for="icone">Icône Bootstrap Icons *
                    <span style="font-size:11px;font-weight:400;color:var(--gray-500);">(ex: bi-book-fill)</span>
                </label>
                <div style="display:flex;gap:10px;align-items:center;">
                    <input type="text" id="icone" name="icone"
                           value="<?php echo htmlspecialchars($categorie['icone'], ENT_QUOTES, 'UTF-8'); ?>"
                           style="flex:1;">
                    <div id="iconPreview" style="width:40px;height:40px;border-radius:8px;background:rgba(29,158,117,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi <?php echo htmlspecialchars($categorie['icone'], ENT_QUOTES, 'UTF-8'); ?>" style="font-size:20px;color:var(--green);"></i>
                    </div>
                </div>
                <div class="form-error" id="err_icone"></div>
                <?php if (!empty($errors['icone'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['icone'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($categorie['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn"><i class="bi bi-floppy-fill"></i> Mettre à jour</button>
            <a href="<?php echo htmlspecialchars(routeUrl('categorie', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<script>
(function () {
    const iconeInput  = document.getElementById('icone');
    const iconPreview = document.getElementById('iconPreview');

    iconeInput.addEventListener('input', function () {
        iconPreview.innerHTML = '<i class="bi ' + this.value.trim() + '" style="font-size:20px;color:var(--green);"></i>';
    });

    document.getElementById('catForm').addEventListener('submit', function (e) {
        let valid = true;
        const nom   = document.getElementById('nom_categorie').value.trim();
        const icone = iconeInput.value.trim();

        document.getElementById('err_nom').textContent   = '';
        document.getElementById('err_icone').textContent = '';

        if (nom === '') {
            document.getElementById('err_nom').textContent = 'Le nom de la catégorie est obligatoire.';
            valid = false;
        } else if (nom.length < 2 || nom.length > 100) {
            document.getElementById('err_nom').textContent = 'Le nom doit contenir entre 2 et 100 caractères.';
            valid = false;
        }

        if (icone === '') {
            document.getElementById('err_icone').textContent = 'L\'icône est obligatoire.';
            valid = false;
        } else if (!/^bi-[a-z0-9\-]+$/.test(icone)) {
            document.getElementById('err_icone').textContent = 'Format invalide (ex: bi-book-fill).';
            valid = false;
        }

        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
