<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ObjetLoisir.php';

$objetLoisir = new ObjetLoisir();
$editMode = false;
$objet = null;
$error = '';

if (isset($_GET['id'])) {
    $editMode = true;
    $objet = $objetLoisir->afficherObjet($_GET['id']);
    $pageTitle = 'Modifier l\'Objet';
} else {
    $pageTitle = 'Nouvel Objet';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom_objet' => $_POST['nom_objet'] ?? '',
        'type_objet' => $_POST['type_objet'] ?? '',
        'quantite' => $_POST['quantite'] ?? 0,
        'etat' => $_POST['etat'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];
    
    if ($editMode) {
        // Update existing object
        $id_objet = $_GET['id'];
        if ($objetLoisir->modifierObjet($id_objet, $data)) {
            header('Location: objets.php?success=updated');
            exit;
        } else {
            $error = 'Erreur lors de la modification';
        }
    } else {
        // Create new object
        if ($objetLoisir->ajouterObjet($data)) {
            header('Location: objets.php?success=added');
            exit;
        } else {
            $error = 'Erreur lors de l\'ajout';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Système de Gestion des Loisirs</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="assets/logo.PNG" alt="Loisirs Management">
                    <span>Loisirs</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
                <a href="objets.php" class="nav-item active">
                    <span class="nav-icon"><i class="fas fa-box"></i></span>
                    <span>Objets Loisirs</span>
                </a>
                <a href="prets.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-hand-holding-usd"></i></span>
                    <span>Gestion des Prêts</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Rechercher...">
                    </div>
                </div>
                <div class="header-right">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="user-menu">
                        <div class="user-avatar">JD</div>
                    </div>
                </div>
            </header>

            <div class="content">
                <h1 class="page-title"><?= $pageTitle ?></h1>
                <p class="page-subtitle"><?= $editMode ? 'Modifiez les informations de l\'objet' : 'Ajoutez un nouvel objet de loisirs' ?></p>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nom_objet" class="form-label">Nom de l'objet *</label>
                                    <input type="text" id="nom_objet" name="nom_objet" class="form-input" 
                                           value="<?= htmlspecialchars($objet['nom_objet'] ?? '') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="type_objet" class="form-label">Type d'objet *</label>
                                    <select id="type_objet" name="type_objet" class="form-select" required>
                                        <option value="">Sélectionner un type</option>
                                        <option value="Jeu de société" <?= ($objet['type_objet'] ?? '') === 'Jeu de société' ? 'selected' : '' ?>>Jeu de société</option>
                                        <option value="Livre" <?= ($objet['type_objet'] ?? '') === 'Livre' ? 'selected' : '' ?>>Livre</option>
                                        <option value="Sport" <?= ($objet['type_objet'] ?? '') === 'Sport' ? 'selected' : '' ?>>Sport</option>
                                        <option value="Musique" <?= ($objet['type_objet'] ?? '') === 'Musique' ? 'selected' : '' ?>>Musique</option>
                                        <option value="Casse-tête" <?= ($objet['type_objet'] ?? '') === 'Casse-tête' ? 'selected' : '' ?>>Casse-tête</option>
                                        <option value="Film" <?= ($objet['type_objet'] ?? '') === 'Film' ? 'selected' : '' ?>>Film</option>
                                        <option value="Électronique" <?= ($objet['type_objet'] ?? '') === 'Électronique' ? 'selected' : '' ?>>Électronique</option>
                                        <option value="Autre" <?= ($objet['type_objet'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="quantite" class="form-label">Quantité *</label>
                                    <input type="number" id="quantite" name="quantite" class="form-input" 
                                           value="<?= htmlspecialchars($objet['quantite'] ?? 1) ?>" min="1" required>
                                </div>

                                <div class="form-group">
                                    <label for="etat" class="form-label">État *</label>
                                    <select id="etat" name="etat" class="form-select" required>
                                        <option value="">Sélectionner un état</option>
                                        <option value="bon" <?= ($objet['etat'] ?? '') === 'bon' ? 'selected' : '' ?>>Bon</option>
                                        <option value="moyen" <?= ($objet['etat'] ?? '') === 'moyen' ? 'selected' : '' ?>>Moyen</option>
                                        <option value="mauvais" <?= ($objet['etat'] ?? '') === 'mauvais' ? 'selected' : '' ?>>Mauvais</option>
                                    </select>
                                </div>

                                <div class="form-group full-width">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea id="description" name="description" class="form-textarea" rows="4"><?= htmlspecialchars($objet['description'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="objets.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Retour
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?= $editMode ? 'Mettre à jour' : 'Ajouter' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });
        });
    </script>
</body>
</html>

<style>
/* Actions Bar */
.actions-bar {
    background: var(--white);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.actions-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.actions-subtitle {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.actions-buttons {
    display: flex;
    gap: 1rem;
}

/* Form Styles */
.form {
    max-width: 800px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-input,
.form-select,
.form-textarea {
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

/* Card */
.card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.card-body {
    padding: 2rem;
}

/* Alert */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border-left: 4px solid;
    font-weight: 500;
}

.alert-danger {
    background: #fee2e2;
    color: var(--danger-color);
    border-left-color: var(--danger-color);
}

/* Buttons */
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.btn-primary {
    background: var(--primary-color);
    color: var(--white);
}

.btn-primary:hover {
    background: #1e40af;
}

.btn-secondary {
    background: var(--white);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--light-bg);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

/* Content */
.content {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .content {
        padding: 1rem;
    }
}
</style>
