<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Loan Request</h1>
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'detail', ['office' => 'front', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Back to details</a>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <div class="card" style="background: #f8fafc;">
        <h2 style="margin-bottom: 12px;"><?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><strong>Type:</strong> <?php echo htmlspecialchars($objet['type_objet'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Condition:</strong> <?php echo htmlspecialchars($objet['etat'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($objet['description'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <form method="POST" action="<?php echo htmlspecialchars(routeUrl('pret', 'create', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="id_objet" value="<?php echo (int) $objet['id_objet']; ?>">
        <input type="hidden" name="date_pret" value="<?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Patient ID is now pulled from the session automatically -->
        <div class="form-group">
            <label>Patient</label>
            <input type="text"
                   value="<?php echo htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                   disabled
                   style="background: #e2e8f0; cursor: not-allowed;">
            <small style="color: #64748b;">Your identity is linked automatically.</small>
        </div>

        <div class="form-group">
            <label for="motif_emprunt">Reason for loan (optional)</label>
            <textarea id="motif_emprunt" name="motif_emprunt" rows="3" placeholder="E.g. Relaxation during hospital stay..."><?php echo htmlspecialchars($_POST['motif_emprunt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-success">Send request</button>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'detail', ['office' => 'front', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
