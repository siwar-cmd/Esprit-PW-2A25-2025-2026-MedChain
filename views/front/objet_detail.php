<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<?php
$typeIcons = [
    'Livre'          => 'bi-book-fill',
    'Jeu de societe' => 'bi-puzzle-fill',
    'Sport'          => 'bi-trophy-fill',
    'Musique'        => 'bi-music-note-beamed',
    'Electronique'   => 'bi-laptop-fill',
    'Casse-tete'     => 'bi-grid-3x3-gap-fill',
    'Film'           => 'bi-camera-video-fill',
];
$icon = $typeIcons[$objet['type_objet']] ?? 'bi-box-seam-fill';

function renderStars(float $note, bool $interactive = false): string {
    $html = '<span style="color:#F59E0B;font-size:18px;letter-spacing:2px;">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= round($note) ? '★' : '☆';
    }
    $html .= '</span>';
    return $html;
}
?>

<div style="margin-bottom:16px;">
    <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
       style="color:var(--gray-500);font-size:14px;display:inline-flex;align-items:center;gap:6px;">
        <i class="bi bi-arrow-left"></i> Retour au catalogue
    </a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'avis_added'): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Votre avis a été publié avec succès !</div>
<?php endif; ?>
<?php if (isset($_GET['success']) && $_GET['success'] === 'reserved'): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Vous avez été ajouté(e) à la liste d'attente !</div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'already_reserved'): ?>
    <div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i> Vous êtes déjà sur la liste d'attente pour cet objet.</div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'already_reviewed'): ?>
    <div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i> Vous avez déjà laissé un avis pour cet objet.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;align-items:start;">
    <!-- Left card: icon + rating summary -->
    <div class="card" style="text-align:center;padding:36px 24px;">
        <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="bi <?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>" style="font-size:34px;color:white;"></i>
        </div>
        <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--navy);margin-bottom:8px;">
            <?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>
        </h2>
        <span class="status status-<?php echo htmlspecialchars($objet['disponibilite'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo match($objet['disponibilite']) {
                'disponible' => 'Disponible',
                'reserve'    => 'Réservé',
                default      => 'Indisponible',
            }; ?>
        </span>

        <?php if ($averageNote !== null): ?>
            <div style="margin-top:16px;">
                <?php echo renderStars($averageNote); ?>
                <div style="font-size:13px;color:var(--gray-500);margin-top:4px;">
                    <?php echo $averageNote; ?>/5 — <?php echo count($avis); ?> avis
                </div>
            </div>
        <?php else: ?>
            <div style="margin-top:16px;font-size:13px;color:var(--gray-500);">Aucun avis pour le moment</div>
        <?php endif; ?>

        <?php if ($reservationCount > 0): ?>
            <div style="margin-top:12px;font-size:12.5px;color:#C2410C;background:#FFF7ED;padding:6px 12px;border-radius:20px;display:inline-block;">
                <i class="bi bi-people-fill"></i> <?php echo $reservationCount; ?> personne(s) en attente
            </div>
        <?php endif; ?>
    </div>

    <!-- Right card: details + action button -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-info-circle-fill"></i> Informations</span>
        </div>
        <div style="padding:24px;display:grid;grid-template-columns:1fr 1fr;gap:18px;">
            <div>
                <div style="font-size:12px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Type</div>
                <div style="font-size:15px;font-weight:600;color:var(--navy);"><?php echo htmlspecialchars($objet['type_objet'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div>
                <div style="font-size:12px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">État</div>
                <div style="font-size:15px;font-weight:600;color:var(--navy);"><?php echo htmlspecialchars(ucfirst($objet['etat']), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div>
                <div style="font-size:12px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Quantité disponible</div>
                <div style="font-size:22px;font-weight:700;color:<?php echo (int)$objet['quantite'] > 0 ? 'var(--green)' : '#DC2626'; ?>;">
                    <?php echo (int) $objet['quantite']; ?>
                </div>
            </div>
            <?php if (!empty($objet['description'])): ?>
                <div style="grid-column:1/-1;">
                    <div style="font-size:12px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Description</div>
                    <p style="font-size:14.5px;color:var(--gray-700);line-height:1.65;margin:0;">
                        <?php echo htmlspecialchars($objet['description'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div style="padding:0 24px 24px;display:flex;flex-direction:column;gap:10px;">
            <?php if ($objet['disponibilite'] === 'disponible' && (int) $objet['quantite'] > 0): ?>
                <a href="<?php echo htmlspecialchars(routeUrl('pret', 'create', ['office' => 'front', 'objet_id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn btn-success" style="justify-content:center;padding:12px;">
                    <i class="bi bi-hand-index-thumb-fill"></i> Faire une demande de prêt
                </a>
            <?php elseif (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo htmlspecialchars(routeUrl('reservation', 'create', ['office' => 'front', 'objet_id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn" style="justify-content:center;padding:12px;background:linear-gradient(135deg,#F59E0B,#D97706);">
                    <i class="bi bi-bookmark-plus-fill"></i> Réserver (liste d'attente)
                </a>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="bi bi-slash-circle-fill"></i> Cet objet est actuellement indisponible.
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id']) && $canReview && !$alreadyReviewed): ?>
                <a href="<?php echo htmlspecialchars(routeUrl('avis', 'create', ['office' => 'front', 'objet_id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn btn-secondary" style="justify-content:center;">
                    <i class="bi bi-star-fill" style="color:#F59E0B;"></i> Laisser un avis
                </a>
            <?php elseif ($alreadyReviewed): ?>
                <div style="font-size:13px;color:var(--gray-500);text-align:center;">
                    <i class="bi bi-check-circle-fill" style="color:var(--green);"></i> Vous avez déjà laissé un avis
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Reviews section ── -->
<div class="card" style="margin-top:28px;">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-chat-square-text-fill"></i>
            Avis des patients
            <?php if ($averageNote !== null): ?>
                <span style="font-size:14px;font-weight:400;color:var(--gray-500);margin-left:8px;">
                    <?php echo renderStars($averageNote); ?> <?php echo $averageNote; ?>/5
                </span>
            <?php endif; ?>
        </span>
    </div>

    <?php if (empty($avis)): ?>
        <div style="text-align:center;padding:40px;color:var(--gray-500);">
            <i class="bi bi-chat-square" style="font-size:40px;opacity:.25;display:block;margin-bottom:12px;"></i>
            <p>Aucun avis pour le moment. Soyez le premier à partager votre expérience !</p>
        </div>
    <?php else: ?>
        <div style="padding:8px 0;">
            <?php foreach ($avis as $a): ?>
                <div style="padding:20px 24px;border-bottom:1px solid var(--gray-200);">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;flex-shrink:0;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;color:var(--navy);font-size:14px;">
                                    <?php echo htmlspecialchars($a['auteur_nom'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div style="font-size:12px;color:var(--gray-500);">
                                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($a['date_avis'])), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </div>
                        <?php echo renderStars((float) $a['note']); ?>
                    </div>
                    <?php if (!empty($a['commentaire'])): ?>
                        <p style="margin:12px 0 0 48px;font-size:14px;color:var(--gray-700);line-height:1.6;">
                            <?php echo nl2br(htmlspecialchars($a['commentaire'], ENT_QUOTES, 'UTF-8')); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
