<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<?php
$filterStatut  = $_GET['statut'] ?? '';
$statusOptions = [
    ''           => 'Tous les prêts',
    'en_attente' => 'En attente',
    'en_cours'   => 'En cours',
    'termine'    => 'Terminé',
    'annule'     => 'Annulé',
];
$statusColors = [
    'en_attente' => ['bg' => '#FFF7ED', 'color' => '#C2410C'],
    'en_cours'   => ['bg' => '#EFF6FF', 'color' => '#1D4ED8'],
    'termine'    => ['bg' => '#F0FDF4', 'color' => '#16A34A'],
    'annule'     => ['bg' => '#F1F5F9', 'color' => '#64748B'],
    'en_retard'  => ['bg' => '#FEF2F2', 'color' => '#DC2626'],
];

$counts   = ['en_attente' => 0, 'en_cours' => 0, 'termine' => 0, 'annule' => 0];
foreach ($prets as $p) {
    if (isset($counts[$p['statut']])) $counts[$p['statut']]++;
}
$totalAll = count($prets);
?>

<!-- Page heading -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-family:'Syne',sans-serif;font-size:24px;font-weight:700;color:var(--navy);margin-bottom:4px;">
            <i class="bi bi-bookmark-check-fill" style="color:var(--green);"></i> Mes Prêts
        </h1>
        <p style="color:var(--gray-500);font-size:14px;">
            Bonjour <strong><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></strong> — voici l'historique de vos emprunts.
        </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn">
            <i class="bi bi-plus-lg"></i> Nouvel emprunt
        </a>
        <a href="<?php echo htmlspecialchars(routeUrl('reservation', 'myList', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
            <i class="bi bi-bookmark-star-fill"></i> Mes réservations
        </a>
    </div>
</div>

<!-- Summary stat cards -->
<div class="stats-mini">
    <?php
    $summaryItems = [
        ['label' => 'Total',      'count' => $totalAll,             'icon' => 'bi-list-ul',           'bg' => 'rgba(29,158,117,.1)',  'color' => 'var(--green)'],
        ['label' => 'En attente', 'count' => $counts['en_attente'], 'icon' => 'bi-hourglass-split',   'bg' => 'rgba(194,65,12,.1)',   'color' => '#C2410C'],
        ['label' => 'En cours',   'count' => $counts['en_cours'],   'icon' => 'bi-arrow-repeat',      'bg' => 'rgba(29,78,216,.1)',   'color' => '#1D4ED8'],
        ['label' => 'Terminés',   'count' => $counts['termine'],    'icon' => 'bi-check-circle-fill', 'bg' => 'rgba(22,163,74,.1)',   'color' => '#16A34A'],
        ['label' => 'Annulés',    'count' => $counts['annule'],     'icon' => 'bi-x-circle-fill',     'bg' => 'rgba(100,116,139,.1)', 'color' => '#64748B'],
    ];
    foreach ($summaryItems as $item):
    ?>
    <div class="stat-mini-card">
        <div style="width:40px;height:40px;border-radius:var(--radius-md);background:<?php echo $item['bg']; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi <?php echo $item['icon']; ?>" style="font-size:18px;color:<?php echo $item['color']; ?>;"></i>
        </div>
        <div>
            <div style="font-size:22px;font-weight:700;color:var(--navy);line-height:1;"><?php echo $item['count']; ?></div>
            <div style="font-size:12px;color:var(--gray-500);margin-top:2px;"><?php echo $item['label']; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Flash messages from session (renewal feedback) -->
<?php if (!empty($flash)): ?>
    <?php if ($flash['type'] === 'success'): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php else: ?>
        <div style="padding:13px 18px;border-radius:var(--radius-md);margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;background:#FFF7ED;border-left:4px solid #F59E0B;color:#92400E;">
            <i class="bi bi-exclamation-circle-fill" style="font-size:18px;flex-shrink:0;margin-top:1px;"></i>
            <div>
                <strong>Renouvellement impossible</strong><br>
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Standard query-string alerts -->
<?php if (isset($_GET['success']) && $_GET['success'] === 'cancelled'): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Demande annulée avec succès.</div>
<?php endif; ?>
<?php if (isset($_GET['success']) && $_GET['success'] === 'returned'): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Objet retourné avec succès. Merci !</div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endforeach; ?>

<!-- Filter bar -->
<div class="card" style="margin-bottom:20px;">
    <div style="padding:14px 22px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <i class="bi bi-funnel-fill" style="color:var(--green);font-size:15px;"></i>
        <span style="font-size:14px;font-weight:600;color:var(--navy);">Filtrer :</span>
        <form method="GET" action="<?php echo htmlspecialchars(APP_ENTRY_URL, ENT_QUOTES, 'UTF-8'); ?>" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="office" value="front">
            <input type="hidden" name="controller" value="pret">
            <input type="hidden" name="action" value="myLoans">
            <select name="statut" onchange="this.form.submit()"
                    style="padding:7px 13px;border:1.5px solid var(--gray-200);border-radius:var(--radius-md);font-size:13.5px;font-family:inherit;color:var(--navy);background:#fff;cursor:pointer;">
                <?php foreach ($statusOptions as $val => $label): ?>
                    <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $filterStatut === $val ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($filterStatut !== ''): ?>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'myLoans', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
               style="font-size:13px;color:var(--gray-500);display:inline-flex;align-items:center;gap:4px;">
                <i class="bi bi-x-circle"></i> Réinitialiser
            </a>
        <?php endif; ?>
        <span style="margin-left:auto;font-size:13px;color:var(--gray-500);">
            <?php echo count($prets); ?> résultat<?php echo count($prets) !== 1 ? 's' : ''; ?>
        </span>
    </div>
</div>

<!-- Loans table / empty state -->
<div class="card">
    <?php if (empty($prets)): ?>
        <div style="text-align:center;padding:64px 24px;color:var(--gray-500);">
            <div style="width:72px;height:72px;border-radius:50%;background:var(--green-light);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="bi bi-inbox" style="font-size:32px;color:var(--green);"></i>
            </div>
            <p style="font-size:17px;font-weight:600;color:var(--navy);margin-bottom:8px;">
                <?php echo $filterStatut !== '' ? 'Aucun prêt avec ce statut.' : "Vous n'avez aucun prêt pour le moment."; ?>
            </p>
            <p style="font-size:14px;margin-bottom:24px;">
                <?php echo $filterStatut !== '' ? 'Essayez un autre filtre.' : 'Parcourez le catalogue et faites votre première demande !'; ?>
            </p>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn">
                <i class="bi bi-box-seam-fill"></i> Voir le catalogue
            </a>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Objet</th>
                        <th>Type</th>
                        <th>Motif</th>
                        <th>Date prêt</th>
                        <th>Retour prévu</th>
                        <th>Retour effectif</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prets as $pret):
                        $sc     = $statusColors[$pret['statut']] ?? ['bg' => '#F1F5F9', 'color' => '#64748B'];
                        $isLate = $pret['statut'] === 'en_cours'
                            && !empty($pret['date_retour_prevue'])
                            && strtotime($pret['date_retour_prevue']) < time();
                    ?>
                        <tr <?php echo $isLate ? 'style="background:#FFF7ED;"' : ''; ?>>
                            <td style="font-weight:600;color:var(--navy);">
                                <?php echo htmlspecialchars($pret['objet_nom'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="color:var(--gray-500);font-size:13px;">
                                <?php echo htmlspecialchars($pret['objet_type'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="max-width:160px;">
                                <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--gray-500);font-size:13px;"
                                      title="<?php echo htmlspecialchars($pret['motif_emprunt'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($pret['motif_emprunt'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap;">
                                <?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_pret'])), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="white-space:nowrap;">
                                <?php if ($pret['date_retour_prevue']): ?>
                                    <span style="<?php echo $isLate ? 'color:#C2410C;font-weight:600;' : ''; ?>">
                                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_retour_prevue'])), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($isLate): ?>
                                            <i class="bi bi-exclamation-triangle-fill" style="color:#C2410C;" title="En retard"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td style="white-space:nowrap;">
                                <?php echo $pret['date_retour_effective']
                                    ? htmlspecialchars(date('d/m/Y', strtotime($pret['date_retour_effective'])), ENT_QUOTES, 'UTF-8')
                                    : '—'; ?>
                            </td>
                            <td>
                                <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>;">
                                    <?php echo htmlspecialchars($pret['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:5px;flex-wrap:wrap;">
                                    <?php if ($pret['statut'] === 'en_attente'): ?>
                                        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'cancel', ['office' => 'front', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn btn-danger" style="padding:5px 10px;font-size:11.5px;"
                                           onclick="return confirm('Annuler cette demande ?');">
                                            <i class="bi bi-x-lg"></i> Annuler
                                        </a>

                                    <?php elseif ($pret['statut'] === 'en_cours'): ?>
                                        <!-- Return button -->
                                        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'return', ['office' => 'front', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn btn-success" style="padding:5px 10px;font-size:11.5px;"
                                           onclick="return confirm('Confirmer le retour de cet objet ?');">
                                            <i class="bi bi-box-arrow-in-left"></i> Retourner
                                        </a>
                                        <!-- Renew button -->
                                        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'renew', ['office' => 'front', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn" style="padding:5px 10px;font-size:11.5px;background:linear-gradient(135deg,#6366F1,#4F46E5);"
                                           onclick="return confirm('Renouveler ce prêt de 3 jours supplémentaires ?');">
                                            <i class="bi bi-arrow-clockwise"></i> +3 jours
                                        </a>

                                    <?php elseif ($pret['statut'] === 'termine'): ?>
                                        <?php
                                        $avisModel       = new AvisObjet();
                                        $alreadyReviewed = $avisModel->hasReviewed((int)$_SESSION['user_id'], (int)$pret['id_objet']);
                                        ?>
                                        <?php if (!$alreadyReviewed): ?>
                                            <a href="<?php echo htmlspecialchars(routeUrl('avis', 'create', ['office' => 'front', 'objet_id' => (int) $pret['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                                               class="btn" style="padding:5px 10px;font-size:11.5px;background:linear-gradient(135deg,#F59E0B,#D97706);">
                                                <i class="bi bi-star-fill"></i> Avis
                                            </a>
                                        <?php else: ?>
                                            <span style="color:var(--gray-500);font-size:12px;">
                                                <i class="bi bi-check-circle-fill" style="color:var(--green);"></i> Avis donné
                                            </span>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <span style="color:var(--gray-500);font-size:13px;">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
