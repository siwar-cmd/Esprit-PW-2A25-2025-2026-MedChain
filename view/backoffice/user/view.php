<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de l'utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Détails de l'utilisateur</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">ID :</div>
                            <div class="col-md-8"><?php echo $user['id']; ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Nom complet :</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Email :</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Téléphone :</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($user['telephone'] ?: 'Non renseigné'); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Rôle :</div>
                            <div class="col-md-8">
                                <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Statut :</div>
                            <div class="col-md-8">
                                <span class="badge <?php echo $user['status'] == 'actif' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $user['status']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Date de création :</div>
                            <div class="col-md-8"><?php echo date('d/m/Y H:i', strtotime($user['date_creation'])); ?></div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="admin.php?action=users" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Retour à la liste
                            </a>
                            <div>
                                <a href="admin.php?action=edit_user&id=<?php echo $user['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="admin.php?action=delete_user&id=<?php echo $user['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>