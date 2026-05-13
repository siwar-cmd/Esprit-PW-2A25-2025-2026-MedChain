<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-bookmark-star-fill"></i> Listes d'Attente — Réservations</span>
        <span style="font-size:13px;color:var(--gray-500);"><?php echo count($reservations); ?> entrée(s)</span>
    </div>

    <?php if (!empty($errors['success'])): ?>
        <div class="alert alert-success" style="margin:16px 24px 0;">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($errors['success'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($reservations)): ?>
        <div style="text-align:center;padding:48px;color:var(--gray-500);">
            <i class="bi bi-bookmark" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;"></i>
            Aucune réservation en attente.
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Objet</th>
                        <th>Type</th>
                        <th>Patient</th>
                        <th>Position</th>
                        <th>Date réservation</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r): ?>
                        <?php
                        $statusMap = [
                            'en_attente' => ['bg' => '#FFF7ED', 'color' => '#C2410C', 'label' => 'En attente'],
                            'satisfaite' => ['bg' => '#F0FDF4', 'color' => '#16A34A', 'label' => 'Satisfaite'],
                            'annulee'    => ['bg' => '#F1F5F9', 'color' => '#64748B', 'label' => 'Annulée'],
                        ];
                        $s = $statusMap[$r['statut']] ?? ['bg' => '#F1F5F9', 'color' => '#64748B', 'label' => $r['statut']];
                        ?>
                        <tr>
                            <td style="font-weight:600;color:var(--navy);">
                                <?php echo htmlspecialchars($r['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="color:var(--gray-500);"><?php echo htmlspecialchars($r['type_objet'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['patient_nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:rgba(29,158,117,.1);color:var(--green);font-weight:700;font-size:13px;">
                                    <?php echo (int) $r['position_queue']; ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap;font-size:13px;">
                                <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($r['date_reservation'])), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:<?php echo $s['bg']; ?>;color:<?php echo $s['color']; ?>;">
                                    <?php echo $s['label']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo htmlspecialchars(routeUrl('reservation', 'delete', ['office' => 'back', 'id' => (int) $r['id_reservation']]), ENT_QUOTES, 'UTF-8'); ?>"
                                   class="btn btn-danger" style="padding:5px 12px;font-size:12px;"
                                   onclick="return confirm('Supprimer cette réservation ?');">
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
