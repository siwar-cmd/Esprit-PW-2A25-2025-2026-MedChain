<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<?php
$statusConfig = [
    ''                   => ['icon' => 'bi-plus-circle-fill',    'color' => '#6366F1', 'bg' => '#EEF2FF', 'label' => 'Créé'],
    'en_attente'         => ['icon' => 'bi-hourglass-split',     'color' => '#C2410C', 'bg' => '#FFF7ED', 'label' => 'En attente'],
    'en_cours'           => ['icon' => 'bi-arrow-repeat',        'color' => '#1D4ED8', 'bg' => '#EFF6FF', 'label' => 'En cours'],
    'en_cours_renouvele' => ['icon' => 'bi-arrow-clockwise',     'color' => '#6366F1', 'bg' => '#EEF2FF', 'label' => 'Renouvelé (+3j)'],
    'termine'            => ['icon' => 'bi-check-circle-fill',   'color' => '#16A34A', 'bg' => '#F0FDF4', 'label' => 'Terminé'],
    'annule'             => ['icon' => 'bi-x-circle-fill',       'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'Annulé'],
    'en_retard'          => ['icon' => 'bi-exclamation-triangle-fill', 'color' => '#DC2626', 'bg' => '#FEF2F2', 'label' => 'En retard'],
];
?>

<div style="margin-bottom:20px;">
    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
       style="color:var(--gray-500);font-size:14px;display:inline-flex;align-items:center;gap:6px;">
        <i class="bi bi-arrow-left"></i> Retour à la liste
    </a>
</div>

<!-- Loan summary card -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-clock-history"></i>
            Historique du Prêt #<?php echo (int) $pret['id_pret']; ?>
        </span>
        <span class="status status-<?php echo htmlspecialchars($pret['statut'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php
            $labels = ['en_attente'=>'En attente','en_cours'=>'En cours','termine'=>'Terminé','annule'=>'Annulé','en_retard'=>'En retard'];
            echo htmlspecialchars($labels[$pret['statut']] ?? $pret['statut'], ENT_QUOTES, 'UTF-8');
            ?>
        </span>
    </div>
    <div style="padding:20px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
        <div>
            <div style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Patient</div>
            <div style="font-weight:600;color:var(--navy);"><?php echo htmlspecialchars($pret['nom_patient'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div>
            <div style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Objet</div>
            <div style="font-weight:600;color:var(--navy);"><?php echo htmlspecialchars($pret['objet_nom'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div>
            <div style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Date de prêt</div>
            <div style="font-weight:600;color:var(--navy);"><?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_pret'])), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div>
            <div style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Retour prévu</div>
            <div style="font-weight:600;color:var(--navy);">
                <?php echo $pret['date_retour_prevue'] ? htmlspecialchars(date('d/m/Y', strtotime($pret['date_retour_prevue'])), ENT_QUOTES, 'UTF-8') : '—'; ?>
            </div>
        </div>
        <?php if ($pret['date_retour_effective']): ?>
        <div>
            <div style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Retour effectif</div>
            <div style="font-weight:600;color:var(--navy);"><?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_retour_effective'])), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Timeline -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-diagram-3-fill"></i> Chronologie des statuts</span>
        <span style="font-size:13px;color:var(--gray-500);"><?php echo count($timeline); ?> événement(s)</span>
    </div>

    <?php if (empty($timeline)): ?>
        <div style="text-align:center;padding:48px;color:var(--gray-500);">
            <i class="bi bi-clock" style="font-size:40px;opacity:.25;display:block;margin-bottom:12px;"></i>
            Aucun historique enregistré pour ce prêt.
        </div>
    <?php else: ?>
        <div style="padding:28px 32px;">
            <div style="position:relative;">
                <!-- Vertical line -->
                <div style="position:absolute;left:19px;top:0;bottom:0;width:2px;background:var(--gray-200);z-index:0;"></div>

                <?php foreach ($timeline as $i => $entry):
                    $nouveau = $entry['nouveau_statut'];
                    $ancien  = $entry['ancien_statut'];
                    $cfg     = $statusConfig[$nouveau] ?? ['icon' => 'bi-circle-fill', 'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => $nouveau];
                    $isLast  = $i === count($timeline) - 1;
                ?>
                    <div style="position:relative;display:flex;gap:18px;align-items:flex-start;margin-bottom:<?php echo $isLast ? '0' : '28px'; ?>;z-index:1;">
                        <!-- Icon dot -->
                        <div style="width:40px;height:40px;border-radius:50%;background:<?php echo $cfg['bg']; ?>;border:2px solid <?php echo $cfg['color']; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;z-index:2;">
                            <i class="bi <?php echo $cfg['icon']; ?>" style="font-size:16px;color:<?php echo $cfg['color']; ?>;"></i>
                        </div>

                        <!-- Content -->
                        <div style="flex:1;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-md);padding:14px 18px;box-shadow:var(--shadow-sm);">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
                                <div>
                                    <div style="font-weight:700;color:var(--navy);font-size:14px;">
                                        <?php echo htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if ($ancien !== ''): ?>
                                        <div style="font-size:12px;color:var(--gray-500);margin-top:3px;">
                                            <span style="background:#f1f5f9;padding:2px 8px;border-radius:4px;">
                                                <?php echo htmlspecialchars($ancien, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                            <i class="bi bi-arrow-right" style="font-size:10px;margin:0 4px;"></i>
                                            <span style="background:<?php echo $cfg['bg']; ?>;color:<?php echo $cfg['color']; ?>;padding:2px 8px;border-radius:4px;font-weight:600;">
                                                <?php echo htmlspecialchars($nouveau, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align:right;flex-shrink:0;">
                                    <div style="font-size:13px;font-weight:600;color:var(--navy);">
                                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($entry['date_change'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div style="font-size:12px;color:var(--gray-500);">
                                        <?php echo htmlspecialchars(date('H:i', strtotime($entry['date_change'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($entry['changed_by_nom'])): ?>
                                <div style="margin-top:8px;font-size:12px;color:var(--gray-500);display:flex;align-items:center;gap:5px;">
                                    <i class="bi bi-person-fill" style="color:var(--green);"></i>
                                    Par : <?php echo htmlspecialchars($entry['changed_by_nom'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php elseif ($entry['changed_by'] === null): ?>
                                <div style="margin-top:8px;font-size:12px;color:var(--gray-500);">
                                    <i class="bi bi-robot"></i> Système automatique
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
