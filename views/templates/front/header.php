<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leisure Object Loans</title>
    <style>
       /* RESET */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* GLOBAL */
body {
    font-family: 'Inter', Arial, sans-serif;
    line-height: 1.6;
    color: #1e293b;
    background: #f1f5f9;
}

/* CONTAINER */
.container {
    max-width: 1200px;
    margin: auto;
    padding: 24px;
}

/* HEADER */
header {
    background: linear-gradient(135deg, #0f766e, #0d9488);
    color: #fff;
    padding: 20px 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.header-content {
    max-width: 1200px;
    margin: auto;
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

/* NAV */
nav ul {
    list-style: none;
    display: flex;
    gap: 10px;
}

nav a {
    color: #fff;
    text-decoration: none;
    padding: 10px 14px;
    border-radius: 8px;
    transition: 0.3s;
}

nav a:hover {
    background: rgba(255,255,255,0.15);
}

/* BUTTONS */
.btn {
    padding: 10px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 600;
    transition: all 0.25s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* COLORS */
.btn-primary { background: #0f766e; color: #fff; }
.btn-primary:hover { background: #115e59; }

.btn-success { background: #10b981; color: #fff; }
.btn-success:hover { background: #059669; }

.btn-danger { background: #ef4444; color: #fff; }
.btn-danger:hover { background: #dc2626; }

.btn-secondary { background: #334155; color: #fff; }
.btn-secondary:hover { background: #1e293b; }

/* ALERTS */
.alert {
    padding: 14px 16px;
    margin: 20px 0;
    border-radius: 10px;
    font-weight: 500;
}

.alert-success {
    background: #ecfdf5;
    border-left: 5px solid #10b981;
    color: #065f46;
}

.alert-error {
    background: #fef2f2;
    border-left: 5px solid #ef4444;
    color: #991b1b;
}

/* CARDS */
.card {
    background: #fff;
    border-radius: 16px;
    padding: 26px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.05);
    margin: 24px 0;
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-3px);
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

/* FORM */
.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5f5;
    border-radius: 8px;
    transition: 0.2s;
}

input:focus, select:focus, textarea:focus {
    border-color: #0f766e;
    box-shadow: 0 0 0 3px rgba(15,118,110,0.15);
    outline: none;
}

/* TABLE */
.table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}

.table th {
    background: #f1f5f9;
    font-weight: 600;
}

.table th, .table td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.table tr:hover {
    background: #f8fafc;
}

/* STATUS BADGES */
.status {
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-disponible, .status-termine {
    background: #d1fae5;
    color: #065f46;
}

.status-indisponible, .status-annule {
    background: #fee2e2;
    color: #991b1b;
}

.status-en_attente {
    background: #fef3c7;
    color: #92400e;
}

.status-en_cours {
    background: #dbeafe;
    color: #1d4ed8;
}

/* JOKE BOX */
.joke-box {
    text-align: center;
}

.joke-box form {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
}

.joke-box select {
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #ccc;
}

.joke-box button {
    padding: 10px 16px;
    background: #0f766e;
    color: white;
    border-radius: 10px;
    font-weight: 600;
    transition: 0.3s;
}

.joke-box button:hover {
    background: #14b8a6;
}

/* FOOTER */
footer {
    background: #0f172a;
    color: #fff;
    text-align: center;
    padding: 24px;
    margin-top: 40px;
}
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Leisure Object Loans</div>
            <nav>
                <ul>
                    <li><a href="<?php echo htmlspecialchars(routeUrl('joke', 'index', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>">Jokes</a></li>
                    <li><a href="<?php echo htmlspecialchars(routeUrl('objet', 'list', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>">Objects</a></li>
                    <li><a href="<?php echo htmlspecialchars(routeUrl('pret', 'myLoans', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>">My Loans</a></li>
                    <li><a href="<?php echo htmlspecialchars(routeUrl('admin', 'dashboard', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>">Back Office</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
