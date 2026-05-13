<?php require BASE_PATH . '/views/templates/front/header.php'; ?>
<style>
    /* Collapse the empty <main class="container my-5"> the header opens.
       Without this, its min-height:70vh + Bootstrap my-5 margins create
       a large blank white gap above the sidebar layout. */
    main.container.my-5 {
        min-height: 0 !important;
        margin:     0 !important;
        padding:    0 !important;
        display:    none !important;
    }
</style>

<?php /* Close the <main> opened by the front header, then open the flex layout */ ?>
</main>
<div class="dashboard-container">
<?php require BASE_PATH . '/views/templates/front/_sidebar.php'; ?>
<div class="dashboard-main">

<?php
$errorMessages = ['not_found' => "L'objet demandé est introuvable."];
$filterCat     = isset($_GET['categorie']) ? (int) $_GET['categorie'] : 0;
$typeIcons = [
    'Livre'          => 'bi-book-fill',
    'Jeu de societe' => 'bi-puzzle-fill',
    'Sport'          => 'bi-trophy-fill',
    'Musique'        => 'bi-music-note-beamed',
    'Electronique'   => 'bi-laptop-fill',
    'Casse-tete'     => 'bi-grid-3x3-gap-fill',
    'Film'           => 'bi-camera-video-fill',
];

// Guard against fatal redeclaration if this view is ever included twice
if (!function_exists('resolveIcon')) {
    function resolveIcon(array $objet, array $typeIcons): string {
        return !empty($objet['categorie_icone'])
            ? $objet['categorie_icone']
            : ($typeIcons[$objet['type_objet']] ?? 'bi-box-seam-fill');
    }
}
?>

<!-- ══════════════════════════════════════════════════════════════
     SECTION 1 — RECOMMENDATIONS (only for logged-in users,
     only when no search/filter is active)
══════════════════════════════════════════════════════════════ -->
<?php if (!empty($recommendations)): ?>
    <?php
    $firstRec    = $recommendations[0];
    $isCategory  = $firstRec['rec_type'] === 'category';
    $recCatName  = $firstRec['rec_cat_name'] ?? null;
    ?>

    <div style="margin-bottom:28px;">
        

        <!-- Section header -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
            <div>
                <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--navy);margin:0;display:flex;align-items:center;gap:8px;">
                    <span style="font-size:22px;">✨</span> Recommandé pour vous
                </h2>
                <p style="font-size:13px;color:var(--gray-500);margin:4px 0 0;">
                    <?php if ($isCategory && $recCatName): ?>
                        Basé sur votre historique — catégorie
                        <strong style="color:var(--green);"><?php echo htmlspecialchars($recCatName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php else: ?>
                        Les objets les plus empruntés par la communauté
                    <?php endif; ?>
                </p>
            </div>
            <!-- Badge showing the algorithm used -->
            <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;
                         <?php echo $isCategory
                             ? 'background:rgba(99,102,241,.1);color:#4F46E5;'
                             : 'background:rgba(245,158,11,.1);color:#B45309;'; ?>">
                <i class="bi <?php echo $isCategory ? 'bi-person-heart' : 'bi-fire'; ?>"></i>
                <?php echo $isCategory ? 'Personnalisé' : 'Populaire'; ?>
            </span>
        </div>

        <!-- Recommendation cards -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
            <?php foreach ($recommendations as $rec):
                $recFallback = '/midchaine/public/assets/images/default-object.jpg';
                $recImgSrc   = !empty($rec['image_url']) ? $rec['image_url'] : $recFallback;
            ?>
                <div style="background:var(--white);border-radius:var(--radius-lg);overflow:hidden;
                            border:1.5px solid <?php echo $isCategory ? 'rgba(99,102,241,.25)' : 'rgba(245,158,11,.25)'; ?>;
                            box-shadow:var(--shadow-sm);transition:all .3s;display:flex;flex-direction:column;"
                     onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,.10)'"
                     onmouseout="this.style.transform='';this.style.boxShadow='var(--shadow-sm)'">

                    <!-- Unsplash image (replaces old icon block) -->
                    <img src="<?php echo htmlspecialchars($recImgSrc, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars($rec['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>"
                         style="width:100%;height:180px;object-fit:cover;
                                border-top-left-radius:12px;border-top-right-radius:12px;
                                display:block;background:#f1f5f9;"
                         onerror="this.src='<?php echo $recFallback; ?>'">

                    <!-- Coloured top-accent strip -->
                    <div style="height:4px;background:<?php echo $isCategory
                        ? 'linear-gradient(90deg,#6366F1,#8B5CF6)'
                        : 'linear-gradient(90deg,#F59E0B,#EF4444)'; ?>;"></div>

                    <!-- Meta info -->
                    <div style="padding:14px 16px 10px;">
                        <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:var(--navy);
                                    margin-bottom:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?php echo htmlspecialchars($rec['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <?php if (!empty($rec['nom_categorie'])): ?>
                            <div style="font-size:11.5px;color:var(--gray-500);margin-bottom:6px;display:flex;align-items:center;gap:4px;">
                                <i class="bi bi-tags-fill" style="color:var(--green);font-size:10px;"></i>
                                <?php echo htmlspecialchars($rec['nom_categorie'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex;gap:10px;font-size:12px;color:var(--gray-500);flex-wrap:wrap;">
                            <span><i class="bi bi-star-fill" style="color:#F59E0B;"></i> <?php echo htmlspecialchars(ucfirst($rec['etat']), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span><i class="bi bi-stack" style="color:var(--green);"></i> <?php echo (int) $rec['quantite']; ?> dispo.</span>
                            <?php if (!empty($rec['borrow_count']) && (int)$rec['borrow_count'] > 0): ?>
                                <span><i class="bi bi-people-fill" style="color:#6366F1;"></i> <?php echo (int) $rec['borrow_count']; ?> emprunt<?php echo (int)$rec['borrow_count'] > 1 ? 's' : ''; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div style="padding:12px 16px;border-top:1px solid var(--gray-200);display:flex;gap:8px;">
                        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'detail', ['office' => 'front', 'id' => (int) $rec['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                           style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;
                                  padding:7px 10px;border-radius:var(--radius-sm);font-size:12px;font-weight:600;
                                  background:#f1f5f9;color:var(--navy);text-decoration:none;transition:background .2s;"
                           onmouseover="this.style.background='var(--gray-200)'"
                           onmouseout="this.style.background='#f1f5f9'">
                            <i class="bi bi-eye"></i> Voir
                        </a>
                        <?php if ((int) $rec['quantite'] > 0): ?>
                            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'create', ['office' => 'front', 'objet_id' => (int) $rec['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                               style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;
                                      padding:7px 10px;border-radius:var(--radius-sm);font-size:12px;font-weight:600;
                                      background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff;
                                      text-decoration:none;box-shadow:0 2px 8px rgba(29,158,117,.25);transition:all .2s;"
                               onmouseover="this.style.transform='translateY(-1px)'"
                               onmouseout="this.style.transform=''">
                                <i class="bi bi-hand-index-thumb-fill"></i> Emprunter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Divider between recommendations and full catalogue -->
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;">
        <div style="flex:1;height:1px;background:var(--gray-200);"></div>
        <span style="font-size:12px;font-weight:600;color:var(--gray-500);white-space:nowrap;letter-spacing:.05em;text-transform:uppercase;">
            <i class="bi bi-grid-fill" style="color:var(--green);"></i> Tous les objets
        </span>
        <div style="flex:1;height:1px;background:var(--gray-200);"></div>
    </div>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════════
     SECTION 2 — FULL CATALOGUE
══════════════════════════════════════════════════════════════ -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-box-seam-fill"></i> Catalogue des Objets Loisir</span>
        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'myLoans', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
            <i class="bi bi-bookmark-check"></i> Mes prêts
        </a>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'requested'): ?>
        <div class="alert alert-success" style="margin:16px 24px 0;">
            <i class="bi bi-check-circle-fill"></i> Votre demande de prêt a été envoyée avec succès !
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'], $errorMessages[$_GET['error']])): ?>
        <div class="alert alert-error" style="margin:16px 24px 0;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo htmlspecialchars($errorMessages[$_GET['error']], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <?php foreach ($errors ?? [] as $error): ?>
        <div class="alert alert-error" style="margin:16px 24px 0;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endforeach; ?>

    <!-- Category filter pills -->
    <?php if (!empty($categories)): ?>
        <div style="padding:16px 24px 0;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <span style="font-size:13px;font-weight:600;color:var(--gray-500);margin-right:4px;">
                <i class="bi bi-funnel-fill" style="color:var(--green);"></i> Catégorie :
            </span>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
               style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;
                      <?php echo $filterCat === 0 ? 'background:var(--green);color:#fff;' : 'background:#f1f5f9;color:var(--navy);'; ?>">
                <i class="bi bi-grid-fill"></i> Tous
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?php echo htmlspecialchars(
                        APP_ENTRY_URL . '?' . http_build_query([
                            'office'     => 'front',
                            'controller' => 'objet',
                            'action'     => 'list',
                            'categorie'  => (int) $cat['id_categorie'],
                            'search'     => $_GET['search'] ?? '',
                        ]),
                        ENT_QUOTES, 'UTF-8'); ?>"
                   style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;
                          <?php echo $filterCat === (int)$cat['id_categorie'] ? 'background:var(--green);color:#fff;' : 'background:#f1f5f9;color:var(--navy);'; ?>">
                    <i class="bi <?php echo htmlspecialchars($cat['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                    <?php echo htmlspecialchars($cat['nom_categorie'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Search bar -->
    <form method="GET" action="<?php echo htmlspecialchars(APP_ENTRY_URL, ENT_QUOTES, 'UTF-8'); ?>" style="padding:14px 24px 0;">
        <input type="hidden" name="office" value="front">
        <input type="hidden" name="controller" value="objet">
        <input type="hidden" name="action" value="list">
        <?php if ($filterCat > 0): ?>
            <input type="hidden" name="categorie" value="<?php echo $filterCat; ?>">
        <?php endif; ?>
        <div style="display:flex;gap:10px;max-width:480px;">
            <input type="text" name="search" class="form-control"
                   placeholder="Rechercher un objet..."
                   value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn" style="white-space:nowrap;">
                <i class="bi bi-search"></i> Rechercher
            </button>
            <?php if (!empty($_GET['search'])): ?>
                <a href="<?php echo htmlspecialchars(
                        $filterCat > 0
                            ? APP_ENTRY_URL . '?' . http_build_query(['office'=>'front','controller'=>'objet','action'=>'list','categorie'=>$filterCat])
                            : routeUrl('objet', 'list', ['office' => 'front']),
                        ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn btn-secondary" style="white-space:nowrap;">
                    <i class="bi bi-x-lg"></i> Effacer
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($objets)): ?>
        <div style="text-align:center;padding:60px;color:var(--gray-500);">
            <i class="bi bi-box-seam" style="font-size:52px;opacity:.25;display:block;margin-bottom:14px;"></i>
            <p style="font-size:16px;">Aucun objet trouvé.</p>
        </div>
    <?php else: ?>
        <div class="objects-grid">
            <?php foreach ($objets as $objet):
                $fallback = '/midchaine/public/assets/images/default-object.jpg';
                $imgSrc   = !empty($objet['image_url']) ? $objet['image_url'] : $fallback;
            ?>
                <div class="object-card">
                    <!-- Unsplash image replacing the old icon container -->
                    <img src="<?php echo htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>"
                         style="width:100%;height:180px;object-fit:cover;
                                border-top-left-radius:12px;border-top-right-radius:12px;
                                display:block;background:#f1f5f9;"
                         onerror="this.src='<?php echo $fallback; ?>'">
                    <div class="object-card-body">
                        <div class="object-card-title">
                            <?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <?php if (!empty($objet['nom_categorie'])): ?>
                            <div class="object-card-meta">
                                <i class="bi bi-tags-fill"></i>
                                <?php echo htmlspecialchars($objet['nom_categorie'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="object-card-meta">
                            <i class="bi bi-tag-fill"></i>
                            <?php echo htmlspecialchars($objet['type_objet'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="object-card-meta">
                            <i class="bi bi-star-fill"></i>
                            État : <?php echo htmlspecialchars(ucfirst($objet['etat']), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="object-card-meta">
                            <i class="bi bi-stack"></i>
                            Quantité : <strong><?php echo (int) $objet['quantite']; ?></strong>
                        </div>
                        <?php if (!empty($objet['description'])): ?>
                            <p style="font-size:13px;color:var(--gray-500);margin-top:4px;line-height:1.5;">
                                <?php echo htmlspecialchars(mb_strimwidth($objet['description'], 0, 90, '…'), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        <?php endif; ?>
                        <div style="margin-top:8px;">
                            <span class="status status-<?php echo htmlspecialchars($objet['disponibilite'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo $objet['disponibilite'] === 'disponible' ? 'Disponible' : 'Indisponible'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="object-card-footer">
                        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'detail', ['office' => 'front', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                           class="btn btn-secondary" style="flex:1;justify-content:center;">
                            <i class="bi bi-eye"></i> Détails
                        </a>
                        <?php if ($objet['disponibilite'] === 'disponible' && (int) $objet['quantite'] > 0): ?>
                            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'create', ['office' => 'front', 'objet_id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                               class="btn btn-success" style="flex:1;justify-content:center;">
                                <i class="bi bi-hand-index-thumb-fill"></i> Faire un prêt
                            </a>
                        <?php elseif (isset($_SESSION['user_id'])): ?>
                            <a href="<?php echo htmlspecialchars(routeUrl('reservation', 'create', ['office' => 'front', 'objet_id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                               class="btn" style="flex:1;justify-content:center;background:linear-gradient(135deg,#F59E0B,#D97706);">
                                <i class="bi bi-bookmark-plus-fill"></i> Réserver
                            </a>
                        <?php else: ?>
                            <span class="btn btn-secondary" style="flex:1;justify-content:center;opacity:.5;cursor:not-allowed;">
                                <i class="bi bi-slash-circle"></i> Indisponible
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Close mc-front-content and mc-front-layout, then re-open <main> so the
// front footer's closing </main> tag stays balanced.
?>
</div><!-- /.dashboard-main -->
</div><!-- /.dashboard-container -->
<main style="display:none;"><!-- placeholder closed by footer -->
<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
