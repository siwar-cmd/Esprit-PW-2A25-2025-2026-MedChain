<?php
require_once __DIR__ . '/../models/ObjetLoisir.php';
require_once __DIR__ . '/../models/Pret.php';

class LoisirsController {
    private $objetLoisir;
    private $pret;

    public function __construct() {
        $this->objetLoisir = new ObjetLoisir();
        $this->pret = new Pret();
    }

    public function handleRequest() {
        $page = $_GET['page'] ?? 'dashboard';
        $action = $_GET['action'] ?? 'dashboard';

        if ($page !== 'loisirs') {
            return false;
        }

        switch ($action) {
            case 'dashboard':
                $this->showDashboard();
                break;
            case 'objets':
                $this->showObjets();
                break;
            case 'add_objet':
                $this->showAddObjet();
                break;
            case 'edit_objet':
                $this->showEditObjet();
                break;
            case 'save_objet':
                $this->saveObjet();
                break;
            case 'delete_objet':
                $this->deleteObjet();
                break;
            case 'view_objet':
                $this->viewObjet();
                break;
            case 'prets':
                $this->showPrets();
                break;
            case 'add_pret':
                $this->showAddPret();
                break;
            case 'save_pret':
                $this->savePret();
                break;
            case 'view_pret':
                $this->viewPret();
                break;
            case 'confirm_pret':
                $this->confirmPret();
                break;
            case 'cancel_pret':
                $this->cancelPret();
                break;
            case 'return_pret':
                $this->returnPret();
                break;
            case 'pret':
                $this->quickPret();
                break;
            default:
                $this->showDashboard();
                break;
        }
        return true;
    }

    private function showDashboard() {
        require_once __DIR__ . '/../views/loisirs_dashboard.php';
    }

    private function showObjets() {
        require_once __DIR__ . '/../views/loisirs_objets.php';
    }

    private function showAddObjet() {
        require_once __DIR__ . '/../views/loisirs_form_objet.php';
    }

    private function showEditObjet() {
        $_GET['action'] = 'edit';
        require_once __DIR__ . '/../views/loisirs_form_objet.php';
    }

    private function saveObjet() {
        $id = $_POST['id_objet'] ?? null;
        $nom_objet = $_POST['nom_objet'] ?? '';
        $type_objet = $_POST['type_objet'] ?? '';
        $quantite = $_POST['quantite'] ?? 1;
        $etat = $_POST['etat'] ?? 'bon';
        $disponibilite = $_POST['disponibilite'] ?? 'disponible';
        $description = $_POST['description'] ?? null;

        if ($id) {
            $success = $this->objetLoisir->modifierObjet($id, $nom_objet, $type_objet, $quantite, $etat, $disponibilite, $description);
        } else {
            $success = $this->objetLoisir->ajouterObjet($nom_objet, $type_objet, $quantite, $etat, $disponibilite, $description);
        }

        if ($success) {
            header('Location: index.php?page=loisirs&action=objets&success=' . ($id ? 'updated' : 'added'));
        } else {
            header('Location: index.php?page=loisirs&action=objets&error=save_failed');
        }
        exit;
    }

    private function deleteObjet() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $success = $this->objetLoisir->supprimerObjet($id);
            if ($success) {
                header('Location: index.php?page=loisirs&action=objets&success=deleted');
            } else {
                header('Location: index.php?page=loisirs&action=objets&error=delete_failed');
            }
        }
        exit;
    }

    private function viewObjet() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $objet = $this->objetLoisir->afficherObjet($id);
            if ($objet) {
                echo '<div class="loisirs-form">';
                echo '<div class="loisirs-header"><h2>?? Détails de l\'objet</h2></div>';
                echo '<table class="loisirs-table">';
                echo '<tr><th>ID:</th><td>' . $objet['id_objet'] . '</td></tr>';
                echo '<tr><th>Nom:</th><td>' . htmlspecialchars($objet['nom_objet']) . '</td></tr>';
                echo '<tr><th>Type:</th><td>' . htmlspecialchars($objet['type_objet']) . '</td></tr>';
                echo '<tr><th>Quantité:</th><td>' . $objet['quantite'] . '</td></tr>';
                echo '<tr><th>État:</th><td>' . htmlspecialchars($objet['etat']) . '</td></tr>';
                echo '<tr><th>Disponibilité:</th><td><span class="status-badge status-' . $objet['disponibilite'] . '">' . htmlspecialchars($objet['disponibilite']) . '</span></td></tr>';
                echo '<tr><th>Description:</th><td>' . htmlspecialchars($objet['description'] ?? 'N/A') . '</td></tr>';
                echo '</table>';
                echo '<div class="form-actions">';
                echo '<a href="index.php?page=loisirs&action=edit_objet&id=' . $objet['id_objet'] . '" class="btn-loisirs btn-primary-loisirs">Modifier</a>';
                echo '<a href="index.php?page=loisirs&action=delete_objet&id=' . $objet['id_objet'] . '" class="btn-loisirs btn-secondary-loisirs" onclick="return confirm(\'Êtes-vous sûr?\')">Supprimer</a>';
                echo '<a href="index.php?page=loisirs&action=objets" class="btn-loisirs btn-secondary-loisirs">Retour</a>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger">Objet non trouvé</div>';
            }
        }
    }

    private function showPrets() {
        require_once __DIR__ . '/../views/loisirs_prets.php';
    }

    private function showAddPret() {
        require_once __DIR__ . '/../views/loisirs_form_pret.php';
    }

    private function savePret() {
        $id_objet = $_POST['id_objet'] ?? '';
        $nom_patient = $_POST['nom_patient'] ?? '';
        $date_pret = $_POST['date_pret'] ?? '';
        $date_retour_prevue = $_POST['date_retour_prevue'] ?? '';

        $result = $this->pret->creerPret($id_objet, $nom_patient, $date_pret, $date_retour_prevue);

        if ($result) {
            header('Location: index.php?page=loisirs&action=prets&success=created');
        } else {
            header('Location: index.php?page=loisirs&action=add_pret&error=create_failed');
        }
        exit;
    }

    private function viewPret() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $pret = $this->pret->afficherPret($id);
            if ($pret) {
                echo '<div class="loisirs-form">';
                echo '<div class="loisirs-header"><h2>?? Détails du prêt</h2></div>';
                echo '<table class="loisirs-table">';
                echo '<tr><th>ID Prêt:</th><td>' . $pret['id_pret'] . '</td></tr>';
                echo '<tr><th>Objet:</th><td>' . htmlspecialchars($pret['nom_objet']) . '</td></tr>';
                echo '<tr><th>Type:</th><td>' . htmlspecialchars($pret['type_objet']) . '</td></tr>';
                echo '<tr><th>Patient:</th><td>' . htmlspecialchars($pret['nom_patient']) . '</td></tr>';
                echo '<tr><th>Date de prêt:</th><td>' . date('d/m/Y', strtotime($pret['date_pret'])) . '</td></tr>';
                echo '<tr><th>Retour prévu:</th><td>' . date('d/m/Y', strtotime($pret['date_retour_prevue'])) . '</td></tr>';
                echo '<tr><th>Statut:</th><td><span class="status-badge status-' . $pret['statut'] . '">' . htmlspecialchars($pret['statut']) . '</span></td></tr>';
                echo '</table>';
                echo '<div class="form-actions">';
                if ($pret['statut'] === 'en_attente') {
                    echo '<a href="index.php?page=loisirs&action=confirm_pret&id=' . $pret['id_pret'] . '" class="btn-loisirs btn-primary-loisirs">Confirmer</a>';
                    echo '<a href="index.php?page=loisirs&action=cancel_pret&id=' . $pret['id_pret'] . '" class="btn-loisirs btn-secondary-loisirs">Annuler</a>';
                } elseif ($pret['statut'] === 'en_cours') {
                    echo '<a href="index.php?page=loisirs&action=return_pret&id=' . $pret['id_pret'] . '" class="btn-loisirs btn-primary-loisirs">Retour</a>';
                    echo '<a href="index.php?page=loisirs&action=cancel_pret&id=' . $pret['id_pret'] . '" class="btn-loisirs btn-secondary-loisirs">Annuler</a>';
                }
                echo '<a href="index.php?page=loisirs&action=prets" class="btn-loisirs btn-secondary-loisirs">Retour</a>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger">Prêt non trouvé</div>';
            }
        }
    }

    private function confirmPret() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $success = $this->pret->confirmerPret($id);
            if ($success) {
                header('Location: index.php?page=loisirs&action=prets&success=confirmed');
            } else {
                header('Location: index.php?page=loisirs&action=prets&error=confirm_failed');
            }
        }
        exit;
    }

    private function cancelPret() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $success = $this->pret->annulerPret($id);
            if ($success) {
                header('Location: index.php?page=loisirs&action=prets&success=cancelled');
            } else {
                header('Location: index.php?page=loisirs&action=prets&error=cancel_failed');
            }
        }
        exit;
    }

    private function returnPret() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $success = $this->pret->retournerObjet($id);
            if ($success) {
                header('Location: index.php?page=loisirs&action=prets&success=returned');
            } else {
                header('Location: index.php?page=loisirs&action=prets&error=return_failed');
            }
        }
        exit;
    }

    private function quickPret() {
        $id_objet = $_GET['id'] ?? null;
        if ($id_objet) {
            $_GET['action'] = 'add_pret';
            echo '<input type="hidden" name="preselected_objet" value="' . $id_objet . '">';
            $this->showAddPret();
        }
    }
}
?>
