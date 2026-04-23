<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));

    if (!defined('APP_ENTRY_URL')) {
        define('APP_ENTRY_URL', '../../index1.php');
    }

    if (!function_exists('routeUrl')) {
        function routeUrl(string $controller = 'objet', string $action = 'list', array $params = []): string
        {
            $query = array_merge(
                [
                    'office' => $params['office'] ?? 'front',
                    'controller' => $controller,
                    'action' => $action,
                ],
                $params
            );

            return APP_ENTRY_URL . '?' . http_build_query($query);
        }
    }
}

require BASE_PATH . '/views/templates/back/header.php';
?>

<div class="card">
    <h1 class="card-title">Administration Dashboard</h1>

    <div class="stats-grid" style="margin-top: 20px;">
        <div class="stat-card">
            <div class="stat-number"><?php echo (int) $totalObjets; ?></div>
            <div class="stat-label">Total objects</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo (int) $pendingCount; ?></div>
            <div class="stat-label">Pending loans</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo (int) $confirmedCount; ?></div>
            <div class="stat-label">Active loans</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo (int) $returnedCount; ?></div>
            <div class="stat-label">Returned loans</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Latest pending requests</h2>
        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'pending', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn">Manage requests</a>
    </div>

    <?php if (empty($recentPrets)): ?>
        <p>No pending requests at the moment.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>Patient</th>
                    <th>Object</th>
                    <th>Request date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentPrets as $pret): ?>
                    <tr>
                        <td><?php echo (int) $pret['id_pret']; ?></td>
                        <td><?php echo htmlspecialchars($pret['nom_patient'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pret['nom_objet'] ?? 'Unknown object', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_pret'])), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
