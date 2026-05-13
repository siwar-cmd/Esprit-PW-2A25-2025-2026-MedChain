<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<div style="max-width:600px;margin:0 auto;">

    <div style="margin-bottom:16px;">
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'detail', ['office' => 'front', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
           style="color:var(--gray-500);font-size:14px;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> Retour aux détails
        </a>
    </div>

    <!-- Object summary -->
    <div class="card" style="margin-bottom:20px;background:var(--green-pale);border-color:rgba(29,158,117,.2);">
        <div style="padding:18px 22px;display:flex;align-items:center;gap:14px;">
            <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-box-seam-fill" style="font-size:20px;color:white;"></i>
            </div>
            <div>
                <div style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--navy);">
                    <?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div style="font-size:13px;color:var(--gray-500);">
                    <?php echo htmlspecialchars($objet['type_objet'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-star-fill" style="color:#F59E0B;"></i> Laisser un avis</span>
        </div>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error" style="margin:16px 24px 0;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endforeach; ?>

        <form method="POST" action="<?php echo htmlspecialchars(routeUrl('avis', 'create', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="id_objet" value="<?php echo (int) $objet['id_objet']; ?>">

            <div style="padding:24px;display:flex;flex-direction:column;gap:20px;">

                <!-- Star rating selector -->
                <div>
                    <label style="display:block;font-size:13.5px;font-weight:600;color:var(--navy);margin-bottom:10px;">
                        Note * <span style="font-weight:400;color:var(--gray-500);">(1 à 5 étoiles)</span>
                    </label>
                    <div style="display:flex;gap:8px;" id="star-container">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label style="cursor:pointer;font-size:32px;color:#D1D5DB;transition:color .15s;" id="star-label-<?php echo $i; ?>">
                                <input type="radio" name="note" value="<?php echo $i; ?>"
                                       style="display:none;"
                                       <?php echo (isset($_POST['note']) && (int)$_POST['note'] === $i) ? 'checked' : ''; ?>>
                                ★
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group" style="margin:0;">
                    <label for="commentaire">Commentaire *</label>
                    <textarea id="commentaire" name="commentaire" rows="5"
                              placeholder="Partagez votre expérience avec cet objet..."
                              style="resize:vertical;"><?php echo htmlspecialchars($_POST['commentaire'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Minimum 5 caractères, maximum 1000.</div>
                </div>

                <div style="background:#f8fafc;border-radius:var(--radius-md);padding:12px 16px;font-size:13px;color:var(--gray-500);display:flex;gap:8px;">
                    <i class="bi bi-info-circle-fill" style="color:var(--green);flex-shrink:0;margin-top:1px;"></i>
                    Votre avis sera visible par tous les utilisateurs. Vous ne pouvez laisser qu'un seul avis par objet emprunté.
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-send-fill"></i> Publier l'avis
                </button>
                <a href="<?php echo htmlspecialchars(routeUrl('objet', 'detail', ['office' => 'front', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const labels = document.querySelectorAll('#star-container label');
    labels.forEach((lbl, idx) => {
        // Restore checked state on load
        const input = lbl.querySelector('input');
        if (input.checked) highlightUpTo(idx + 1);

        lbl.addEventListener('mouseover', () => highlightUpTo(idx + 1));
        lbl.addEventListener('mouseout',  () => {
            const checked = document.querySelector('#star-container input:checked');
            highlightUpTo(checked ? parseInt(checked.value) : 0);
        });
        lbl.addEventListener('click', () => {
            input.checked = true;
            highlightUpTo(idx + 1);
        });
    });

    function highlightUpTo(n) {
        labels.forEach((l, i) => {
            l.style.color = i < n ? '#F59E0B' : '#D1D5DB';
        });
    }
})();
</script>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
