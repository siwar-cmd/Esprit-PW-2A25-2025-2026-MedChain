<?php
require_once 'model/UserModel.php';

class UserManagementController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
    }
    
    // ========== LISTER les utilisateurs (READ ALL) ==========
    public function index() {
        // Vérifier si l'utilisateur est admin
        session_start();
        if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: index.php?action=login');
            exit();
        }
        
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Récupérer les utilisateurs
        $users = $this->userModel->readAll($limit, $offset, $search);
        $totalUsers = $this->userModel->countAll($search);
        $totalPages = ceil($totalUsers / $limit);
        
        // Charger la vue
        require_once 'view/backoffice/users/list.php';
    }
    
    // ========== AJOUTER un utilisateur (CREATE) ==========
    public function create() {
        session_start();
        if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: index.php?action=login');
            exit();
        }
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom' => $_POST['nom'],
                'prenom' => $_POST['prenom'],
                'email' => $_POST['email'],
                'motdepasse' => $_POST['motdepasse'],
                'telephone' => $_POST['telephone'],
                'role' => $_POST['role']
            ];
            
            $errors = [];
            
            // Validation
            if(empty($data['nom'])) $errors[] = "Le nom est requis";
            if(empty($data['prenom'])) $errors[] = "Le prénom est requis";
            if(empty($data['email'])) $errors[] = "L'email est requis";
            if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
            if(strlen($data['motdepasse']) < 6) $errors[] = "Mot de passe minimum 6 caractères";
            if($this->userModel->emailExists($data['email'])) $errors[] = "Cet email existe déjà";
            
            if(empty($errors)) {
                if($this->userModel->create($data)) {
                    header('Location: admin.php?action=users&success=created');
                    exit();
                } else {
                    $errors[] = "Erreur lors de la création";
                }
            }
            
            require_once 'view/backoffice/users/create.php';
        } else {
            require_once 'view/backoffice/users/create.php';
        }
    }
    
    // ========== MODIFIER un utilisateur (UPDATE) ==========
    public function edit($id) {
        session_start();
        if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: index.php?action=login');
            exit();
        }
        
        $user = $this->userModel->readOne($id);
        
        if(!$user) {
            header('Location: admin.php?action=users&error=not_found');
            exit();
        }
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom' => $_POST['nom'],
                'prenom' => $_POST['prenom'],
                'email' => $_POST['email'],
                'telephone' => $_POST['telephone'],
                'role' => $_POST['role'],
                'status' => $_POST['status']
            ];
            
            $errors = [];
            
            // Validation
            if(empty($data['nom'])) $errors[] = "Le nom est requis";
            if(empty($data['prenom'])) $errors[] = "Le prénom est requis";
            if(empty($data['email'])) $errors[] = "L'email est requis";
            if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
            if($this->userModel->emailExists($data['email'], $id)) $errors[] = "Cet email est déjà utilisé";
            
            if(empty($errors)) {
                if($this->userModel->update($id, $data)) {
                    header('Location: admin.php?action=users&success=updated');
                    exit();
                } else {
                    $errors[] = "Erreur lors de la mise à jour";
                }
            }
            
            require_once 'view/backoffice/users/edit.php';
        } else {
            require_once 'view/backoffice/users/edit.php';
        }
    }
    
    // ========== SUPPRIMER un utilisateur (DELETE) ==========
    public function delete($id) {
        session_start();
        if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: index.php?action=login');
            exit();
        }
        
        // Empêcher la suppression de son propre compte
        if($id == $_SESSION['user_id']) {
            header('Location: admin.php?action=users&error=self_delete');
            exit();
        }
        
        if($this->userModel->delete($id)) {
            header('Location: admin.php?action=users&success=deleted');
        } else {
            header('Location: admin.php?action=users&error=delete_failed');
        }
        exit();
    }
    
    // ========== VOIR les détails d'un utilisateur (READ ONE) ==========
    public function view($id) {
        session_start();
        if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: index.php?action=login');
            exit();
        }
        
        $user = $this->userModel->readOne($id);
        
        if(!$user) {
            header('Location: admin.php?action=users&error=not_found');
            exit();
        }
        
        require_once 'view/backoffice/users/view.php';
    }
}
?>