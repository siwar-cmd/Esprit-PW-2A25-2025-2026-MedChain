<?php

class AdminController
{
    public function dashboard(): void
    {
        $objetController = new ObjetController();
        $pretController = new PretController();

        $totalObjets = $objetController->countAllObjects();
        $pendingCount = $pretController->countLoansByStatus('en_attente');
        $confirmedCount = $pretController->countLoansByStatus('en_cours');
        $returnedCount = $pretController->countLoansByStatus('termine');
        $recentPrets = $pretController->getRecentPendingLoans(5);

        require BASE_PATH . '/views/back/admin_dashboard.php';
    }
}
