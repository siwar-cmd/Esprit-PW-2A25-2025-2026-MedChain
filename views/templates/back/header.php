<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Leisure Loans</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; line-height: 1.5; color: #1f2937; background: #f3f4f6; }
        .container { max-width: 1240px; margin: 0 auto; padding: 24px; }
        header { background: linear-gradient(135deg, #1f2937 0%, #374151 100%); color: #fff; padding: 18px 0; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1); }
        .header-content { max-width: 1240px; margin: 0 auto; padding: 0 24px; display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .logo { font-size: 1.35rem; font-weight: 700; }
        .admin-nav { background: #e5e7eb; border-bottom: 1px solid #d1d5db; }
        .admin-nav ul { max-width: 1240px; margin: 0 auto; padding: 0 24px; list-style: none; display: flex; flex-wrap: wrap; gap: 10px; }
        .admin-nav a { display: inline-block; padding: 14px 16px; color: #111827; text-decoration: none; border-radius: 8px; font-weight: 600; }
        .admin-nav a:hover { background: #d1d5db; }
        .btn { display: inline-block; padding: 10px 16px; border-radius: 8px; border: none; text-decoration: none; cursor: pointer; font-size: 0.95rem; background: #2563eb; color: #fff; }
        .btn:hover { background: #1d4ed8; }
        .btn-success { background: #059669; }
        .btn-success:hover { background: #047857; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #4b5563; }
        .btn-secondary:hover { background: #374151; }
        .alert { padding: 14px 16px; margin: 18px 0; border-radius: 8px; border-left: 5px solid; }
        .alert-success { background: #ecfdf5; border-color: #10b981; color: #065f46; }
        .alert-error { background: #fef2f2; border-color: #ef4444; color: #991b1b; }
        .card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06); margin: 24px 0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
        .card-title { font-size: 1.6rem; color: #111827; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input, select, textarea { width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; }
        .table th, .table td { padding: 14px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .table th { background: #f9fafb; color: #111827; }
        .table tr:hover { background: #f9fafb; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 700; }
        .status-disponible, .status-termine { background: #d1fae5; color: #065f46; }
        .status-indisponible, .status-annule { background: #fee2e2; color: #991b1b; }
        .status-en_attente { background: #fef3c7; color: #92400e; }
        .status-en_cours { background: #dbeafe; color: #1d4ed8; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; }
        .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: 700; color: #2563eb; }
        .stat-label { margin-top: 8px; color: #4b5563; }
        footer { background: #111827; color: #fff; text-align: center; padding: 24px 0; margin-top: 32px; }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Leisure Loan Administration</div>
            <a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Open Front Office</a>
        </div>
    </header>

    <nav class="admin-nav">
        <ul>
            <li><a href="<?php echo htmlspecialchars(routeUrl('admin', 'dashboard', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a></li>
            <li><a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>">Objects</a></li>
            <li><a href="<?php echo htmlspecialchars(routeUrl('pret', 'pending', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>">Pending Loans</a></li>
            <li><a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirmed', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>">Active Loans</a></li>
            <li><a href="<?php echo htmlspecialchars(routeUrl('pret', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>">All Loans</a></li>
        </ul>
    </nav>

    <main class="container">
