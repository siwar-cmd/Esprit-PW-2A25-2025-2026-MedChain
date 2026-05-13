<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<div style="max-width:520px;margin:0 auto;">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-search"></i> Rechercher mes prêts</span>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                <i class="bi bi-box-seam"></i> Catalogue
            </a>
        </div>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error" style="margin:16px 24px 0;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endforeach; ?>

        <div style="padding:24px;">
            <p style="color:var(--gray-500);font-size:14px;margin-bottom:20px;">
                Entrez votre nom complet pour afficher l'historique de vos prêts.
            </p>

            <form method="GET" action="<?php echo htmlspecialchars(APP_ENTRY_URL, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="office" value="front">
                <input type="hidden" name="controller" value="pret">
                <input type="hidden" name="action" value="myLoans">

                <div class="form-group">
                    <label for="patient">Votre nom complet</label>
                    <input type="text" id="patient" name="patient"
                           value="<?php echo htmlspecialchars($_GET['patient'] ?? (isset($_SESSION['user_prenom']) ? trim($_SESSION['user_prenom'] . ' ' . ($_SESSION['user_nom'] ?? '')) : ''), ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="Prénom Nom">
                </div>

                <button type="submit" class="btn" style="width:100%;justify-content:center;">
                    <i class="bi bi-search"></i> Rechercher mes prêts
                </button>
            </form>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
