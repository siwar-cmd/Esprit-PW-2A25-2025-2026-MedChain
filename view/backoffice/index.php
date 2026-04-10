<?php
session_start();

// Initialiser utilisateurs (session)
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        ["id" => 1, "nom" => "Sophie", "email" => "sophie@mail.com", "role" => "Admin"],
        ["id" => 2, "nom" => "Ali", "email" => "ali@mail.com", "role" => "Médecin"],
        ["id" => 3, "nom" => "Mariam", "email" => "mariam@mail.com", "role" => "Utilisateur"]
    ];
}

$users = &$_SESSION['users'];

// Ajouter
if (isset($_POST['add_user'])) {
    $users[] = [
        "id" => count($users) + 1,
        "nom" => $_POST['nom'],
        "email" => $_POST['email'],
        "role" => $_POST['role']
    ];
}

// Supprimer
if (isset($_POST['delete_user'])) {
    foreach ($users as $key => $user) {
        if ($user['id'] == $_POST['id']) {
            unset($users[$key]);
        }
    }
}

// Pré-remplir modification
$editUser = null;
if (isset($_POST['edit_user'])) {
    foreach ($users as $user) {
        if ($user['id'] == $_POST['id']) {
            $editUser = $user;
        }
    }
}

// Mettre à jour
if (isset($_POST['update_user'])) {
    foreach ($users as &$user) {
        if ($user['id'] == $_POST['id']) {
            $user['nom'] = $_POST['nom'];
            $user['email'] = $_POST['email'];
            $user['role'] = $_POST['role'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Backoffice Utilisateurs</title>

<style>
:root {
    --main-green: #1D9E75;
    --dark-green: #0F6E56;
    --sidebar-bg: #1A1D21;
    --light-bg: #f8fafb;
    --border-color: #eef2f4;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', sans-serif;
    background: var(--light-bg);
}

.app-wrapper { display: flex; }

/* SIDEBAR */
.sidebar {
    width: 260px;
    background: var(--sidebar-bg);
    height: 100vh;
    position: fixed;
    padding: 20px 0;
}

.img-logo {
    max-width: 160px;
    display: block;
    margin: 0 auto 40px;
}

.nav-item {
    display: flex;
    padding: 15px 25px;
    color: #A9B1BD;
    text-decoration: none;
    border-left: 4px solid transparent;
}

.nav-item:hover, .active .nav-item {
    background: rgba(29,158,117,0.1);
    color: #fff;
    border-left-color: var(--main-green);
}

/* MAIN */
.content-main {
    margin-left: 260px;
    padding: 30px;
}

.panel {
    background: #fff;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 20px;
}

/* FORM */
form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

form input, form select {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

form button {
    background: var(--main-green);
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
}

/* TABLE */
.table-custom {
    width: 100%;
    border-collapse: collapse;
}

.table-custom th, .table-custom td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

/* BADGES */
.badge {
    background: #e6f5f0;
    color: var(--main-green);
    padding: 5px 10px;
    border-radius: 20px;
}

/* BUTTONS */
.btn-edit {
    background: #3498db;
    color: white;
    border: none;
    padding: 5px 8px;
    border-radius: 5px;
}

.btn-delete {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 5px 8px;
    border-radius: 5px;
}
</style>

</head>

<body>

<div class="app-wrapper">

<aside class="sidebar">
    <img src="templete_back/logo.PNG" class="img-logo">
    <nav>
        <ul>
            <li><a href="#" class="nav-item">🏠 Dashboard</a></li>
            <li class="active"><a href="#" class="nav-item">👥 Utilisateurs</a></li>
        </ul>
    </nav>
</aside>

<main class="content-main">

<h1>Gestion des Utilisateurs</h1>

<!-- FORM -->
<section class="panel">
<h3><?= $editUser ? "Modifier utilisateur" : "Ajouter utilisateur" ?></h3>

<form method="POST">
    <input type="hidden" name="id" value="<?= $editUser['id'] ?? '' ?>">

    <input type="text" name="nom" placeholder="Nom"
        value="<?= $editUser['nom'] ?? '' ?>" required>

    <input type="email" name="email" placeholder="Email"
        value="<?= $editUser['email'] ?? '' ?>" required>

    <select name="role">
        <option <?= ($editUser && $editUser['role']=="Admin")?"selected":"" ?>>Admin</option>
        <option <?= ($editUser && $editUser['role']=="Médecin")?"selected":"" ?>>Médecin</option>
        <option <?= ($editUser && $editUser['role']=="Utilisateur")?"selected":"" ?>>Utilisateur</option>
    </select>

    <?php if ($editUser): ?>
        <button name="update_user">Modifier</button>
    <?php else: ?>
        <button name="add_user">Ajouter</button>
    <?php endif; ?>
</form>
</section>

<!-- TABLE -->
<section class="panel">
<h3>Liste des utilisateurs</h3>

<table class="table-custom">
<thead>
<tr>
    <th>ID</th>
    <th>Nom</th>
    <th>Email</th>
    <th>Rôle</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach ($users as $user): ?>
<tr>
    <td><?= $user['id'] ?></td>
    <td><?= $user['nom'] ?></td>
    <td><?= $user['email'] ?></td>
    <td><span class="badge"><?= $user['role'] ?></span></td>

    <td>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <button name="edit_user" class="btn-edit">✏️</button>
        </form>

        <form method="POST" style="display:inline;">
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <button name="delete_user" class="btn-delete">🗑️</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</section>

</main>
</div>

</body>
</html>