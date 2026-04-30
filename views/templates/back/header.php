<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Leisure Loans</title>
    <style>
/* ===== RESET ===== */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* ===== BODY ===== */
body {
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
    line-height: 1.6;
    color: #1e293b;
    background: #f1f5f9;
}

/* ===== CONTAINER ===== */
.container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 28px;
}

/* ===== HEADER ===== */
header {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #fff;
    padding: 20px 0;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.header-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 0.5px;
}

/* ===== NAV ===== */
.admin-nav {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
}

.admin-nav ul {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    list-style: none;
    display: flex;
    gap: 12px;
}

.admin-nav a {
    padding: 12px 16px;
    border-radius: 10px;
    font-weight: 600;
    color: #334155;
    text-decoration: none;
    transition: all 0.2s ease;
}

.admin-nav a:hover {
    background: #2563eb;
    color: #fff;
}

/* ===== BUTTONS ===== */
.btn {
    padding: 10px 18px;
    border-radius: 10px;
    border: none;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.25s ease;
    font-weight: 600;
}

.btn:hover {
    transform: translateY(-2px);
    opacity: 0.95;
}

/* COLORS */
.btn { background: #2563eb; color: #fff; }
.btn-success { background: #16a34a; color: #fff; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-secondary { background: #64748b; color: #fff; }

/* ===== ALERTS ===== */
.alert {
    padding: 14px 18px;
    margin: 20px 0;
    border-radius: 10px;
    font-weight: 500;
}

.alert-success {
    background: #ecfdf5;
    color: #065f46;
    border-left: 6px solid #10b981;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border-left: 6px solid #ef4444;
}

/* ===== CARD ===== */
.card {
    background: #ffffff;
    border-radius: 16px;
    padding: 26px;
    margin: 26px 0;
    box-shadow: 0 12px 30px rgba(0,0,0,0.06);
    transition: 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 700;
}

/* ===== FORM ===== */
.form-group {
    margin-bottom: 18px;
}

label {
    font-weight: 600;
    margin-bottom: 6px;
    display: block;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #cbd5f5;
    font-size: 0.95rem;
    transition: 0.2s;
}

input:focus, select:focus, textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
    outline: none;
}

/* ===== TABLE ===== */
.table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
}

.table th {
    background: #f1f5f9;
    font-weight: 700;
    color: #1e293b;
}

.table th, .table td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.table tr:hover {
    background: #f8fafc;
}

/* ===== STATUS ===== */
.status {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
}

.status-disponible,
.status-termine {
    background: #dcfce7;
    color: #166534;
}

.status-indisponible,
.status-annule {
    background: #fee2e2;
    color: #991b1b;
}

.status-en_attente {
    background: #fef9c3;
    color: #854d0e;
}

.status-en_cours {
    background: #dbeafe;
    color: #1d4ed8;
}

/* ===== STATS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 18px;
}

.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    text-align: center;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2563eb;
}

.stat-label {
    margin-top: 8px;
    color: #64748b;
}

/* ===== FOOTER ===== */
footer {
    background: #0f172a;
    color: #fff;
    text-align: center;
    padding: 28px;
    margin-top: 40px;
}
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
