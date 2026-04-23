<?php
$query = $_GET;
$query['office'] = 'back';
$query['controller'] = $query['controller'] ?? 'admin';
$query['action'] = $query['action'] ?? 'dashboard';

header('Location: ../../../index1.php?' . http_build_query($query), true, 302);
exit;
