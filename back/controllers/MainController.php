<?php
require_once __DIR__ . '/../config/database.php';

class MainController {
    public function dashboard() {
        $stats = $this->getDashboardStats();
        $recentActivities = $this->getRecentActivities();
        
        $pageTitle = 'Dashboard';
        $currentPage = 'dashboard';
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/layout/sidebar.php';
        
        require_once __DIR__ . '/../views/dashboard/index.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    private function getDashboardStats() {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            $stats = [];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM pret");
            $stats['total_prets'] = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM pret WHERE statut = 'en_cours'");
            $stats['prets_en_cours'] = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM pret WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()");
            $stats['prets_en_retard'] = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM objet_loisir WHERE quantite > 0");
            $stats['objets_disponibles'] = $stmt->fetch()['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            return [
                'total_prets' => 0,
                'prets_en_cours' => 0,
                'prets_en_retard' => 0,
                'objets_disponibles' => 0
            ];
        }
    }

    private function getRecentActivities() {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->query("
                SELECT 'Objet ajouté' as description, 'Admin' as user, created_at as date
                FROM objet_loisir 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                ORDER BY created_at DESC 
                LIMIT 3
            ");
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
