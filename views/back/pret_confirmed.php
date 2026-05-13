<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-arrow-repeat"></i> Prêts en Cours</span>
        <div style="display:flex;gap:10px;">
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'pending', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                <i class="bi bi-hourglass-split"></i> En attente
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                <i class="bi bi-list-ul"></i> Tous les prêts
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'returned'): ?>
        <div class="alert alert-success" style="margin:16px 24px 0;"><i class="bi bi-check-circle-fill"></i> Objet marqué comme retourné avec succès.</div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error" style="margin:16px 24px 0;"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if (empty($prets)): ?>
        <div style="text-align:center;padding:48px;color:var(--gray-500);">
            <i class="bi bi-inbox" style="font-size:48px;opacity:.3;display:block;margin-bottom:12px;"></i>
            Aucun prêt en cours pour le moment.
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
                        <th>Date prêt</th>
                        <th>Retour prévu</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prets as $pret): ?>
                        <?php
                        $isLate = $pret['date_retour_prevue']
                            && strtotime($pret['date_retour_prevue']) < time();
                        ?>
                        <tr <?php echo $isLate ? 'style="background:#FFF7ED;"' : ''; ?>>
                            <td style="color:var(--gray-500);font-size:13px;">#<?php echo (int) $pret['id_pret']; ?></td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($pret['nom_patient'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($pret['objet_nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="color:var(--gray-500);"><?php echo htmlspecialchars($pret['objet_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_pret'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if ($pret['date_retour_prevue']): ?>
                                    <span style="<?php echo $isLate ? 'color:#C2410C;font-weight:600;' : ''; ?>">
                                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_retour_prevue'])), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($isLate): ?> <i class="bi bi-exclamation-triangle-fill" title="En retard"></i><?php endif; ?>
                                    </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'return', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-success" style="padding:6px 12px;font-size:12.5px;"
                                       onclick="return confirm('Marquer cet objet comme retourné ?');">
                                        <i class="bi bi-box-arrow-in-left"></i> Retour
                                    </a>
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'cancel', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-danger" style="padding:6px 12px;font-size:12.5px;"
                                       onclick="return confirm('Annuler ce prêt en cours ?');">
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
