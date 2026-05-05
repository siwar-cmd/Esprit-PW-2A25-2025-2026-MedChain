<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-tags-fill"></i> Gestion des Catégories</span>
        <a href="<?php echo htmlspecialchars(routeUrl('categorie', 'add', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn">
            <i class="bi bi-plus-lg"></i> Ajouter une catégorie
        </a>
    </div>

    <?php if (!empty($errors['success'])): ?>
        <div class="alert alert-success" style="margin:16px 24px 0;">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($errors['success'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors['error'])): ?>
        <div class="alert alert-error" style="margin:16px 24px 0;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($errors['error'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($categories)): ?>
        <div style="text-align:center;padding:48px;color:var(--gray-500);">
            <i class="bi bi-tags" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;"></i>
            Aucune catégorie pour le moment.
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Icône</th>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Objets liés</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <div style="width:36px;height:36px;border-radius:8px;background:rgba(29,158,117,.1);display:flex;align-items:center;justify-content:center;">
                                    <i class="bi <?php echo htmlspecialchars($cat['icone'], ENT_QUOTES, 'UTF-8'); ?>" style="font-size:18px;color:var(--green);"></i>
                                </div>
                            </td>
                            <td style="font-weight:600;color:var(--navy);">
                                <?php echo htmlspecialchars($cat['nom_categorie'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="color:var(--gray-500);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?php echo htmlspecialchars($cat['description'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <?php
                                $db    = Database::getInstance()->getConnection();
                                $count = $db->prepare('SELECT COUNT(*) FROM objet_loisir WHERE id_categorie = :id');
                                $count->execute([':id' => $cat['id_categorie']]);
                                echo (int) $count->fetchColumn();
                                ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:8px;">
                                    <a href="<?php echo htmlspecialchars(routeUrl('categorie', 'edit', ['office' => 'back', 'id' => (int) $cat['id_categorie']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-secondary" style="padding:5px 12px;font-size:12px;">
                                        <i class="bi bi-pencil-fill"></i> Modifier
                                    </a>
                                    <a href="<?php echo htmlspecialchars(routeUrl('categorie', 'delete', ['office' => 'back', 'id' => (int) $cat['id_categorie']]), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-danger" style="padding:5px 12px;font-size:12px;"
                                       onclick="return confirm('Supprimer cette catégorie ?');">
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
