<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-list-ul"></i> Tous les Prêts</span>
        <div style="display:flex;gap:10px;">
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'pending', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                <i class="bi bi-hourglass-split"></i> En attente
            </a>
            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirmed', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-repeat"></i> En cours
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'cancelled'): ?>
        <div class="alert alert-success" style="margin:16px 24px 0;">
            <i class="bi bi-check-circle-fill"></i> Prêt annulé avec succès.
        </div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error" style="margin:16px 24px 0;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endforeach; ?>

    <?php if (empty($prets)): ?>
        <p style="color:var(--gray-500);text-align:center;padding:40px;">Aucun prêt enregistré.</p>
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
                        <th>Retour effectif</th>
                        <th>Statut</th>
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
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_pret'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $pret['date_retour_prevue'] ? htmlspecialchars(date('d/m/Y', strtotime($pret['date_retour_prevue'])), ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td><?php echo $pret['date_retour_effective'] ? htmlspecialchars(date('d/m/Y', strtotime($pret['date_retour_effective'])), ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td>
                                <span class="status status-<?php echo htmlspecialchars($pret['statut'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($pret['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:5px;flex-wrap:wrap;">
                                    <?php if ($pret['statut'] === 'en_attente'): ?>
                                        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirm', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn btn-success" style="padding:5px 10px;font-size:11.5px;"
                                           onclick="return confirm('Confirmer ce prêt ?');">
                                            <i class="bi bi-check-lg"></i> Confirmer
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($pret['statut'] === 'en_cours'): ?>
                                        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'return', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn btn-success" style="padding:5px 10px;font-size:11.5px;"
                                           onclick="return confirm('Marquer comme retourné ?');">
                                            <i class="bi bi-box-arrow-in-left"></i> Retour
                                        </a>
                                    <?php endif; ?>
                                    <?php if (in_array($pret['statut'], ['en_attente', 'en_cours'], true)): ?>
                                        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'cancel', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn btn-danger" style="padding:5px 10px;font-size:11.5px;"
                                           onclick="return confirm('Annuler ce prêt ?');">
                                            <i class="bi bi-x-lg"></i> Annuler
                                        </a>
                                    <?php endif; ?>
                                    <!-- Timeline button — always visible -->
                                    <a href="<?php echo htmlspecialchars(routeUrl('pret', 'timeline', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-secondary" style="padding:5px 10px;font-size:11.5px;"
                                       title="Voir l'historique de ce prêt">
                                        <i class="bi bi-clock-history"></i> Historique
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
