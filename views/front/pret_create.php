<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<div style="max-width:680px;margin:0 auto;">

    <div style="margin-bottom:16px;">
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'detail', ['office' => 'front', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
           style="color:var(--gray-500);font-size:14px;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> Retour aux détails
        </a>
    </div>

    <!-- Object summary -->
    <div class="card" style="margin-bottom:20px;background:var(--green-pale);border-color:rgba(29,158,117,.2);">
        <div style="padding:20px 24px;display:flex;align-items:center;gap:16px;">
            <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-box-seam-fill" style="font-size:22px;color:white;"></i>
            </div>
            <div>
                <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--navy);">
                    <?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div style="font-size:13px;color:var(--gray-500);margin-top:2px;">
                    <?php echo htmlspecialchars($objet['type_objet'], ENT_QUOTES, 'UTF-8'); ?>
                    &nbsp;·&nbsp; État : <?php echo htmlspecialchars(ucfirst($objet['etat']), ENT_QUOTES, 'UTF-8'); ?>
                    &nbsp;·&nbsp; Quantité : <?php echo (int) $objet['quantite']; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan request form -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-hand-index-thumb-fill"></i> Demande de Prêt</span>
        </div>

        <?php foreach ($errors as $error): ?>
            <?php
            // Detect quota error for special styling
            $isQuota = isset($result['quota']) && $result['quota'] === true
                       || strpos($error, 'Quota') !== false;
            ?>
            <div style="margin:16px 24px 0;padding:14px 18px;border-radius:var(--radius-md);display:flex;align-items:flex-start;gap:12px;
                        <?php echo $isQuota
                            ? 'background:#FFF7ED;border-left:4px solid #F59E0B;color:#92400E;'
                            : 'background:#fef2f2;border-left:4px solid #EF4444;color:#b91c1c;'; ?>">
                <i class="bi <?php echo $isQuota ? 'bi-exclamation-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"
                   style="font-size:18px;flex-shrink:0;margin-top:1px;"></i>
                <div>
                    <strong><?php echo $isQuota ? 'Quota atteint' : 'Erreur'; ?></strong><br>
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($isQuota): ?>
                        <div style="margin-top:8px;">
                            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'myLoans', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
                               style="font-size:13px;font-weight:600;color:#92400E;text-decoration:underline;">
                                Voir mes prêts actifs →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <form method="POST" id="pretForm"
              action="<?php echo htmlspecialchars(routeUrl('pret', 'create', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="id_objet" value="<?php echo (int) $objet['id_objet']; ?>">

            <div style="padding:24px;display:flex;flex-direction:column;gap:18px;">

                <div class="form-group" style="margin:0;">
                    <label for="motif_emprunt">Motif de l'emprunt *</label>
                    <textarea id="motif_emprunt" name="motif_emprunt" rows="3"
                              placeholder="Décrivez brièvement pourquoi vous souhaitez emprunter cet objet..."><?php echo htmlspecialchars($_POST['motif_emprunt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <div class="form-error" id="err_motif"></div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                    <div class="form-group" style="margin:0;">
                        <label for="date_pret">Date de prêt</label>
                        <input type="date" id="date_pret" name="date_pret"
                               value="<?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
                               readonly style="background:#f8fafc;cursor:not-allowed;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="date_retour_prevue">Date de retour prévue *</label>
                        <input type="date" id="date_retour_prevue" name="date_retour_prevue"
                               value="<?php echo htmlspecialchars($_POST['date_retour_prevue'] ?? date('Y-m-d', strtotime('+7 days')), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-error" id="err_date"></div>
                    </div>
                </div>

                <div style="background:#f8fafc;border-radius:var(--radius-md);padding:14px 16px;font-size:13px;color:var(--gray-500);display:flex;align-items:flex-start;gap:10px;">
                    <i class="bi bi-info-circle-fill" style="color:var(--green);margin-top:1px;flex-shrink:0;"></i>
                    <span>Votre demande sera examinée par l'administration. La durée maximale de prêt est de 30 jours.
                    Vous ne pouvez pas avoir plus de <strong>2 prêts actifs</strong> simultanément.</span>
                </div>

            </div>

            <div class="actions">
                <button type="submit" class="btn btn-success" id="submitBtn">
                    <i class="bi bi-send-fill"></i> Envoyer la demande
                </button>
                <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

</div>

<script>
(function () {
    const minReturn = new Date();
    minReturn.setDate(minReturn.getDate() + 1);
    const minReturnStr = minReturn.toISOString().split('T')[0];

    document.getElementById('pretForm').addEventListener('submit', function (e) {
        let valid = true;

        const motif = document.getElementById('motif_emprunt').value.trim();
        const date  = document.getElementById('date_retour_prevue').value.trim();

        document.getElementById('err_motif').textContent = '';
        document.getElementById('err_date').textContent  = '';

        if (motif === '') {
            document.getElementById('err_motif').textContent = 'Le motif d\'emprunt est obligatoire.';
            valid = false;
        } else if (motif.length > 500) {
            document.getElementById('err_motif').textContent = 'Le motif ne doit pas dépasser 500 caractères.';
            valid = false;
        }

        if (date === '') {
            document.getElementById('err_date').textContent = 'La date de retour prévue est obligatoire.';
            valid = false;
        } else if (date < minReturnStr) {
            document.getElementById('err_date').textContent = 'La date de retour doit être au moins demain.';
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            // Scroll to first error
            const firstErr = document.querySelector('.form-error:not(:empty)');
            if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();
</script>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
