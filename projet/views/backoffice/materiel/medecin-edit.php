<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') { header('Location: ../../frontoffice/auth/login.php'); exit; }
require_once __DIR__ . '/../../../controllers/MaterielController.php';
$ctrl = new MaterielController();
$errors = [];
$id = $_GET['id'] ?? null;
if (!$id) { header('Location: medecin-index.php'); exit; }
$data = $ctrl->getMaterielById($id);
if (!$data) { $_SESSION['error_message'] = 'Matériel non trouvé'; header('Location: medecin-index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $result = $ctrl->updateMateriel($id, $data);
    if ($result['success']) { $_SESSION['success_message'] = $result['message']; header('Location: medecin-index.php'); exit; }
    else { $errors = $result['errors'] ?? [$result['message']]; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Matériel - Médecin - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}:root{--green:#1D9E75;--green-dark:#0F6E56;--navy:#1E3A52;--gray-200:#E5E7EB;--white:#fff;--radius-md:12px;--radius-xl:28px;--shadow-sm:0 1px 3px rgba(0,0,0,.08)}
        body{font-family:'DM Sans',sans-serif;background:linear-gradient(145deg,#f0faf6,#e8f7f1,#ddf3ea);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px}
        .card{background:var(--white);border-radius:var(--radius-xl);border:1px solid rgba(29,158,117,.15);box-shadow:var(--shadow-sm);overflow:hidden;max-width:650px;width:100%}
        .card-header{padding:24px;border-bottom:1px solid var(--gray-200)}.card-header h2{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:10px}.card-header h2 i{color:var(--green)}
        .card-body{padding:24px}
        .form-group{margin-bottom:20px}.form-group label{display:block;margin-bottom:8px;font-weight:600;font-size:14px;color:var(--navy)}.form-group label i{color:var(--green);margin-right:6px}
        .form-control{width:100%;padding:12px 16px;border:2px solid var(--gray-200);border-radius:var(--radius-md);font-size:14px;font-family:'DM Sans',sans-serif;transition:border-color .3s}
        .form-control:focus{outline:none;border-color:var(--green)}select.form-control{appearance:auto}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .btn{padding:12px 24px;border-radius:var(--radius-md);font-size:14px;font-weight:600;cursor:pointer;transition:all .3s;border:none;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .btn-primary{background:linear-gradient(135deg,var(--green),var(--green-dark));color:white}.btn-primary:hover{transform:translateY(-2px)}
        .btn-outline{background:transparent;border:2px solid var(--gray-200);color:var(--navy)}.btn-outline:hover{border-color:var(--green);color:var(--green)}
        .form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:24px;padding-top:24px;border-top:1px solid var(--gray-200)}
        .alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C;padding:14px 18px;border-radius:var(--radius-md);margin-bottom:20px}
        .is-invalid{border-color:#EF4444 !important;background-color:#FEF2F2}
        .error-message{color:#B91C1C;font-size:12px;margin-top:6px;display:none}
        @media(max-width:768px){.form-row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="card">
    <div class="card-header"><h2><i class="bi bi-pencil-square"></i> Modifier Matériel #<?=$id?></h2></div>
    <div class="card-body">
        <?php if(!empty($errors)):?><div class="alert-error"><?=implode('<br>',$errors)?></div><?php endif;?>
        <form method="POST" data-form-type="materiel">
            <div class="form-row">
                <div class="form-group"><label><i class="bi bi-box"></i> Nom</label><input type="text" name="nom" class="form-control" value="<?=htmlspecialchars($data['nom']??'')?>"></div>
                <div class="form-group"><label><i class="bi bi-tag"></i> Catégorie</label><input type="text" name="categorie" class="form-control" value="<?=htmlspecialchars($data['categorie']??'')?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><i class="bi bi-check-circle"></i> Disponibilité</label>
                    <select name="disponibilite" class="form-control"><option value="">-- Sélectionner --</option><option <?=($data['disponibilite']??'')=='Disponible'?'selected':''?>>Disponible</option><option <?=($data['disponibilite']??'')=='Indisponible'?'selected':''?>>Indisponible</option><option value="En maintenance" <?=($data['disponibilite']??'')=='En maintenance'?'selected':''?>>En maintenance</option></select>
                </div>
                <div class="form-group"><label><i class="bi bi-shield-check"></i> Stérilisation</label>
                    <select name="statutSterilisation" class="form-control"><option value="">-- Sélectionner --</option><option <?=($data['statutSterilisation']??'')=='Stérilisé'?'selected':''?>>Stérilisé</option><option value="Non stérilisé" <?=($data['statutSterilisation']??'')=='Non stérilisé'?'selected':''?>>Non stérilisé</option><option value="En cours" <?=($data['statutSterilisation']??'')=='En cours'?'selected':''?>>En cours</option></select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><i class="bi bi-arrow-up-circle"></i> Utilisations Max</label><input type="text" name="nombreUtilisationsMax" class="form-control" value="<?=htmlspecialchars($data['nombreUtilisationsMax']??0)?>" placeholder="0"></div>
                <div class="form-group"><label><i class="bi bi-arrow-repeat"></i> Utilisations Actuelles</label><input type="text" name="nombreUtilisationsActuelles" class="form-control" value="<?=htmlspecialchars($data['nombreUtilisationsActuelles']??0)?>" placeholder="0"></div>
            </div>
            <div class="form-actions">
                <a href="medecin-index.php" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Retour</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>
<script src="../../assets/js/validation.js"></script>
</body>
</html>
