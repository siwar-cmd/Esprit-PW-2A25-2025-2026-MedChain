<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-star-fill"></i> Gestion des Avis</span>
        <span style="font-size:13px;color:var(--gray-500);"><?php echo count($avis); ?> avis au total</span>
    </div>

    <?php if (!empty($errors['success'])): ?>
        <div class="alert alert-success" style="margin:16px 24px 0;">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($errors['success'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($avis)): ?>
        <div style="text-align:center;padding:48px;color:var(--gray-500);">
            <i class="bi bi-star" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;"></i>
            Aucun avis pour le moment.
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Objet</th>
                        <th>Patient</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avis as $a): ?>
                        <tr>
                            <td style="font-weight:600;color:var(--navy);">
                                <?php echo htmlspecialchars($a['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td><?php echo htmlspecialchars($a['auteur_nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span style="color:#F59E0B;font-size:16px;letter-spacing:1px;">
                                    <?php echo str_repeat('★', (int)$a['note']) . str_repeat('☆', 5 - (int)$a['note']); ?>
                                </span>
                                <span style="font-size:12px;color:var(--gray-500);margin-left:4px;"><?php echo (int)$a['note']; ?>/5</span>
                            </td>
                            <td style="max-width:280px;">
                                <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;color:var(--gray-700);"
                                      title="<?php echo htmlspecialchars($a['commentaire'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($a['commentaire'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap;font-size:13px;">
                                <?php echo htmlspecialchars(date('d/m/Y', strtotime($a['date_avis'])), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <a href="<?php echo htmlspecialchars(routeUrl('avis', 'delete', ['office' => 'back', 'id' => (int) $a['id_avis']]), ENT_QUOTES, 'UTF-8'); ?>"
                                   class="btn btn-danger" style="padding:5px 12px;font-size:12px;"
                                   onclick="return confirm('Supprimer cet avis définitivement ?');">
                                    <i class="bi bi-trash-fill"></i> Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
