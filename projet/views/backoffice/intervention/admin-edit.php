<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header('Location: ../../frontoffice/auth/login.php'); exit; }
require_once __DIR__ . '/../../../controllers/InterventionController.php';
$ctrl = new InterventionController();
$errors = [];
$id = $_GET['id'] ?? null;
if (!$id) { header('Location: admin-index.php'); exit; }
$data = $ctrl->getInterventionById($id);
if (!$data) { $_SESSION['error_message'] = 'Intervention non trouvée'; header('Location: admin-index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $result = $ctrl->updateIntervention($id, $data);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: admin-index.php'); exit;
    } else {
        $errors = $result['errors'] ?? [$result['message'] ?? 'Erreur'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Intervention - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#fff;--radius-md:12px;--radius-lg:20px;--radius-xl:28px;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-green:0 8px 30px rgba(29,158,117,.22)}
        body{font-family:'DM Sans',sans-serif;background:linear-gradient(145deg,#f0faf6,#e8f7f1,#ddf3ea);min-height:100vh}
        .dashboard-container{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
        .dashboard-sidebar{background:linear-gradient(180deg,var(--navy),#0F172A);position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto}
        .dashboard-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:20px}
        .dashboard-logo a{display:flex;align-items:center;gap:10px;text-decoration:none}
        .dashboard-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center}
        .dashboard-logo-icon i{font-size:18px;color:white}
        .dashboard-logo-text{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:white}
        .dashboard-logo-text span{color:var(--green)}
        .dashboard-nav{flex:1;display:flex;flex-direction:column;gap:4px;padding:0 12px}
        .dashboard-nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#94A3B8;text-decoration:none;border-radius:var(--radius-md);transition:all .3s;font-size:14px;font-weight:500}
        .dashboard-nav-item i{font-size:18px;width:24px}
        .dashboard-nav-item:hover{background:rgba(255,255,255,0.1);color:white}
        .dashboard-nav-item.active{background:rgba(29,158,117,0.2);color:var(--green)}
        .dashboard-nav-item.logout{margin-top:auto;margin-bottom:20px;color:#F87171}
        .dashboard-nav-title{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748B;padding:16px 16px 8px;font-weight:600}
        .nav-dropdown{position:relative}.nav-dropdown-menu{display:none;flex-direction:column;gap:2px;padding:4px 0 4px 20px}.nav-dropdown:hover .nav-dropdown-menu{display:flex}.nav-dropdown-menu a{font-size:13px;padding:8px 16px}
        .dashboard-main{padding:32px 40px;overflow-y:auto}
        .card{background:var(--white);border-radius:var(--radius-xl);border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);overflow:hidden;max-width:700px;margin:0 auto}
        .card-header{padding:24px;border-bottom:1px solid var(--gray-200)}
        .card-header h2{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:10px}
        .card-header h2 i{color:var(--green)}
        .card-body{padding:24px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;margin-bottom:8px;font-weight:600;font-size:14px;color:var(--navy)}
        .form-group label i{color:var(--green);margin-right:6px}
        .form-control{width:100%;padding:12px 16px;border:2px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:'DM Sans',sans-serif;transition:border-color .3s}
        .form-control:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px rgba(29,158,117,.15)}
        select.form-control{appearance:auto}textarea.form-control{resize:vertical;min-height:80px}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .btn{padding:12px 24px;border-radius:var(--radius-md);font-size:14px;font-weight:600;cursor:pointer;transition:all .3s;border:none;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .btn-primary{background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;box-shadow:0 3px 12px rgba(29,158,117,.30)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:var(--shadow-green)}
        .btn-outline{background:transparent;border:2px solid var(--gray-200);color:var(--navy)}
        .btn-outline:hover{border-color:var(--green);color:var(--green)}
        .form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:24px;padding-top:24px;border-top:1px solid var(--gray-200)}
        .alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C;padding:14px 18px;border-radius:var(--radius-md);margin-bottom:20px}
        .is-invalid{border-color:#EF4444 !important;background-color:#FEF2F2}
        .error-message{color:#B91C1C;font-size:12px;margin-top:6px;display:none}
        @media(max-width:768px){.dashboard-container{grid-template-columns:1fr}.dashboard-sidebar{display:none}.form-row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="dashboard-logo"><a href="../admin-dashboard.php"><div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div><div class="dashboard-logo-text">Med<span>Chain</span></div></a></div>
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="../admin-dashboard.php" class="dashboard-nav-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="../admin-users.php" class="dashboard-nav-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
            <div class="nav-dropdown"><a href="#" class="dashboard-nav-item active"><i class="bi bi-hospital"></i> Bloc Opératoire <i class="bi bi-chevron-down" style="font-size:12px;margin-left:auto"></i></a><div class="nav-dropdown-menu"><a href="admin-index.php" class="dashboard-nav-item active"><i class="bi bi-heart-pulse"></i> Interventions</a><a href="../materiel/admin-index.php" class="dashboard-nav-item"><i class="bi bi-tools"></i> Matériel</a></div></div>
            <a href="../rendezvous/admin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
            <div class="dashboard-nav-title">Gestion</div>
            <a href="../../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <div class="card">
            <div class="card-header"><h2><i class="bi bi-pencil-square"></i> Modifier l'Intervention #<?= $id ?></h2></div>
            <div class="card-body">
                <?php if (!empty($errors)): ?><div class="alert-error"><i class="bi bi-exclamation-triangle-fill"></i> <?= implode('<br>', $errors) ?></div><?php endif; ?>
                <form method="POST" data-form-type="intervention">
                    <div class="form-row">
                        <div class="form-group"><label><i class="bi bi-tag"></i> Type</label><input type="text" name="type" class="form-control" value="<?= htmlspecialchars($data['type']) ?>"></div>
                        <div class="form-group"><label><i class="bi bi-calendar-event"></i> Date</label><input type="text" name="date_intervention" class="form-control" value="<?= htmlspecialchars($data['date_intervention']) ?>" placeholder="YYYY-MM-DD"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><i class="bi bi-clock"></i> Durée (min)</label><input type="text" name="duree" class="form-control" value="<?= htmlspecialchars($data['duree']) ?>"></div>
                        <div class="form-group"><label><i class="bi bi-exclamation-triangle"></i> Niveau d'urgence</label>
                            <select name="niveau_urgence" class="form-control">
                                <option value="">-- Sélectionner --</option>
                                <?php for($i=1;$i<=5;$i++): $l=[1=>'Faible',2=>'Modéré',3=>'Élevé',4=>'Urgent',5=>'Critique']; ?>
                                <option value="<?=$i?>" <?=$data['niveau_urgence']==$i?'selected':''?>><?=$i?> - <?=$l[$i]?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><i class="bi bi-person"></i> Chirurgien</label><input type="text" name="chirurgien" class="form-control" value="<?= htmlspecialchars($data['chirurgien']) ?>"></div>
                        <div class="form-group"><label><i class="bi bi-door-open"></i> Salle</label><input type="text" name="salle" class="form-control" value="<?= htmlspecialchars($data['salle'] ?? '') ?>"></div>
                    </div>
                    <div class="form-group"><label><i class="bi bi-card-text"></i> Description</label><textarea name="description" class="form-control"><?= htmlspecialchars($data['description'] ?? '') ?></textarea></div>
                    <div class="form-actions">
                        <a href="admin-index.php" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Retour</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="../../assets/js/validation.js"></script>
</body>
</html>
