<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-hourglass-split"></i> Demandes de Prêt en Attente</span>
        <div style="display:flex;gap:10px;">
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirmed', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-repeat"></i> Prêts en cours
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                <i class="bi bi-list-ul"></i> Tous les prêts
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'confirmed'): ?>
        <div class="alert alert-success" style="margin:16px 24px 0;"><i class="bi bi-check-circle-fill"></i> Prêt confirmé avec succès.</div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error" style="margin:16px 24px 0;"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if (empty($prets)): ?>
        <div style="text-align:center;padding:48px;color:var(--gray-500);">
            <i class="bi bi-inbox" style="font-size:48px;opacity:.3;display:block;margin-bottom:12px;"></i>
            Aucune demande en attente pour le moment.
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Objet</th>
                        <th>Type</th>
                        <th>Motif</th>
                        <th>Date demande</th>
                        <th>Retour prévu</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prets as $pret): ?>
                        <tr>
                            <td style="color:var(--gray-500);font-size:13px;">#<?php echo (int) $pret['id_pret']; ?></td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($pret['nom_patient'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($pret['objet_nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="color:var(--gray-500);"><?php echo htmlspecialchars($pret['objet_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--gray-500);">
                                <?php echo htmlspecialchars($pret['motif_emprunt'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_pret'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $pret['date_retour_prevue'] ? htmlspecialchars(date('d/m/Y', strtotime($pret['date_retour_prevue'])), ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirm', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-success" style="padding:6px 12px;font-size:12.5px;"
                                       onclick="return confirm('Confirmer ce prêt ?');">
                                        <i class="bi bi-check-lg"></i> Confirmer
                                    </a>
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'cancel', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-danger" style="padding:6px 12px;font-size:12.5px;"
                                       onclick="return confirm('Annuler cette demande ?');">
                                        <i class="bi bi-x-lg"></i> Annuler
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
