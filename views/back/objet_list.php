<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-box-seam-fill"></i> Gestion des Objets Loisir</span>
        <a href="<?php echo htmlspecialchars(routeUrl('objet', 'add', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn">
            <i class="bi bi-plus-lg"></i> Ajouter un objet
        </a>
    </div>

    <?php
    $messages  = [
        'added'              => 'Objet ajouté avec succès.',
        'updated'            => 'Objet mis à jour avec succès.',
        'deleted'            => 'Objet supprimé avec succès.',
        'image_regenerated'  => 'Image régénérée avec succès.',
    ];
    $errorsMap = [
        'linked_to_loans' => 'Cet objet est lié à un ou plusieurs prêts et ne peut pas être supprimé.',
        'not_found'       => "L'objet demandé est introuvable.",
    ];
    ?>

    <?php if (isset($_GET['success'], $messages[$_GET['success']])): ?>
        <div class="alert alert-success" style="margin:16px 24px 0;">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo htmlspecialchars($messages[$_GET['success']], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'], $errorsMap[$_GET['error']])): ?>
        <div class="alert alert-error" style="margin:16px 24px 0;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo htmlspecialchars($errorsMap[$_GET['error']], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($objets)): ?>
        <p style="color:var(--gray-500);text-align:center;padding:40px;">Aucun objet enregistré pour le moment.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Quantité</th>
                        <th>État</th>
                        <th>Disponibilité</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($objets as $objet): ?>
                        <?php
                        $fallback = '/projet/public/assets/images/default-object.jpg';
                        $imgSrc   = !empty($objet['image_url']) ? $objet['image_url'] : $fallback;
                        ?>
                        <tr>
                            <!-- Image thumbnail -->
                            <td style="width:72px;">
                                <img src="<?php echo htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?>"
                                     style="width:60px;height:60px;object-fit:cover;border-radius:10px;
                                            border:2px solid var(--gray-200);background:#f8fafc;"
                                     onerror="this.src='<?php echo $fallback; ?>'">
                            </td>

                            <td style="color:var(--gray-500);font-size:13px;">#<?php echo (int) $objet['id_objet']; ?></td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($objet['nom_objet'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($objet['type_objet'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span style="font-weight:700;color:<?php echo (int)$objet['quantite'] > 0 ? 'var(--green)' : '#DC2626'; ?>;">
                                    <?php echo (int) $objet['quantite']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(ucfirst($objet['etat']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="status status-<?php echo htmlspecialchars($objet['disponibilite'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo $objet['disponibilite'] === 'disponible' ? 'Disponible' : 'Indisponible'; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <!-- Edit -->
                                    <a href="<?php echo htmlspecialchars(routeUrl('objet', 'edit', ['office' => 'back', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-secondary" style="padding:5px 10px;font-size:12px;">
                                        <i class="bi bi-pencil-fill"></i> Modifier
                                    </a>

                                    <!-- Regenerate Image -->
                                    <a href="<?php echo htmlspecialchars(routeUrl('objet', 'regenerateImage', ['office' => 'back', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn" style="padding:5px 10px;font-size:12px;background:linear-gradient(135deg,#6366F1,#4F46E5);"
                                       title="Régénérer l'image via Unsplash"
                                       onclick="return confirm('Régénérer l\'image pour cet objet ?');">
                                        <i class="bi bi-arrow-clockwise"></i> Image
                                    </a>

                                    <!-- Delete -->
                                    <a href="<?php echo htmlspecialchars(routeUrl('objet', 'delete', ['office' => 'back', 'id' => (int) $objet['id_objet']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-danger" style="padding:5px 10px;font-size:12px;"
                                       onclick="return confirm('Supprimer cet objet définitivement ?');">
                                        <i class="bi bi-trash-fill"></i> Supprimer
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
