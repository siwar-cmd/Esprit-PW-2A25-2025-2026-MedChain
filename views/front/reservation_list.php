<?php require BASE_PATH . '/views/templates/front/header.php'; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-family:'Syne',sans-serif;font-size:24px;font-weight:700;color:var(--navy);margin-bottom:4px;">
            <i class="bi bi-bookmark-star-fill" style="color:var(--green);"></i> Mes Réservations
        </h1>
        <p style="color:var(--gray-500);font-size:14px;">Votre liste d'attente pour les objets indisponibles.</p>
    </div>
    <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn">
        <i class="bi bi-box-seam"></i> Catalogue
    </a>
</div>

<?php if (!empty($errors['success'])): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($errors['success'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="card">
    <?php if (empty($reservations)): ?>
        <div style="text-align:center;padding:60px;color:var(--gray-500);">
            <i class="bi bi-bookmark" style="font-size:48px;opacity:.25;display:block;margin-bottom:14px;"></i>
            <p style="font-size:16px;">Vous n'avez aucune réservation en attente.</p>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>"
               class="btn" style="margin-top:16px;">
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
                        <th>Date de réservation</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td style="font-weight:600;color:var(--navy);">
                                <a href="<?php echo htmlspecialchars(routeUrl('objet', 'detail', ['office' => 'front', 'id' => (int) $r['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                                   style="color:var(--navy);">
                                    <?php echo htmlspecialchars($r['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </td>
                            <td style="color:var(--gray-500);"><?php echo htmlspecialchars($r['type_objet'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($r['date_reservation'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php
                                $statusMap = [
                                    'en_attente' => ['bg' => '#FFF7ED', 'color' => '#C2410C', 'label' => 'En attente'],
                                    'satisfaite' => ['bg' => '#F0FDF4', 'color' => '#16A34A', 'label' => 'Satisfaite'],
                                    'annulee'    => ['bg' => '#F1F5F9', 'color' => '#64748B', 'label' => 'Annulée'],
                                ];
                                $s = $statusMap[$r['statut']] ?? ['bg' => '#F1F5F9', 'color' => '#64748B', 'label' => $r['statut']];
                                ?>
                                <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:<?php echo $s['bg']; ?>;color:<?php echo $s['color']; ?>;">
                                    <?php echo $s['label']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['statut'] === 'en_attente'): ?>
                                    <a href="<?php echo htmlspecialchars(routeUrl('reservation', 'cancel', ['office' => 'front', 'id' => (int) $r['id_reservation']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-danger" style="padding:5px 12px;font-size:12px;"
                                       onclick="return confirm('Annuler cette réservation ?');">
                                        <i class="bi bi-x-lg"></i> Annuler
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--gray-500);font-size:13px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/front/footer.php'; ?>
