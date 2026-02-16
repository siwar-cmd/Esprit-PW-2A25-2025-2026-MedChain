<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') { header('Location: ../auth/login.php'); exit; }
require_once __DIR__ . '/../../../controllers/InterventionAnnuleeController.php';
$ctrl = new InterventionAnnuleeController();
$interventions = $ctrl->getInterventionsNonAnnulees();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $ctrl->addAnnulation($_POST);
    if ($result['success']) {
        $success = true;
    } else {
        $errors = $result['errors'] ?? [$result['message'] ?? 'Erreur'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuler une Intervention - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--green:#1D9E75;--green-dark:#0F6E56;--green-deep:#094D3C;--green-light:#E8F7F2;--navy:#1E3A52;--gray-500:#6B7280;--gray-200:#E5E7EB;--white:#fff;--radius-md:12px;--radius-lg:20px;--radius-xl:28px;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-lg:0 12px 40px rgba(0,0,0,.10);--shadow-green:0 8px 30px rgba(29,158,117,.22)}
        body{font-family:'DM Sans',sans-serif;background:linear-gradient(145deg,#f0faf6 0%,#e8f7f1 50%,#ddf3ea 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;position:relative;overflow-x:hidden}
        body::before{content:'';position:fixed;top:-120px;right:-120px;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(29,158,117,.10) 0%,transparent 70%);pointer-events:none}
        body::after{content:'';position:fixed;bottom:-80px;left:-80px;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(29,158,117,.07) 0%,transparent 70%);pointer-events:none}
        .container{max-width:560px;width:100%;position:relative;z-index:2}
        .card{background:var(--white);border-radius:var(--radius-xl);padding:0;box-shadow:var(--shadow-lg);border:1px solid rgba(29,158,117,.15);overflow:hidden;transition:transform .3s,box-shadow .3s}
        .card:hover{transform:translateY(-4px);box-shadow:var(--shadow-green)}
        .card-header{padding:32px 32px 24px;border-bottom:1px solid var(--gray-200);text-align:center}
        .logo{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:20px}
        .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-green)}
        .logo-icon i{font-size:22px;color:white}
        .logo-text{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--navy)}.logo-text span{color:var(--green)}
        .card-header h2{font-family:'Syne',sans-serif;font-size:24px;font-weight:700;color:var(--navy);margin-bottom:6px}
        .card-header p{color:var(--gray-500);font-size:14px}
        .card-body{padding:32px}
        .form-group{margin-bottom:22px}
        .form-group label{display:block;margin-bottom:8px;font-weight:600;font-size:14px;color:var(--navy)}
        .form-group label i{color:var(--green);margin-right:6px}
        .form-control{width:100%;padding:14px 16px;border:2px solid var(--gray-200);border-radius:var(--radius-md);font-size:15px;font-family:'DM Sans',sans-serif;transition:all .3s}
        .form-control:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px rgba(29,158,117,.15)}
        select.form-control{appearance:auto}
        textarea.form-control{resize:vertical;min-height:100px}
        .btn-submit{width:100%;padding:14px 24px;background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;border:none;border-radius:var(--radius-md);font-size:16px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 3px 12px rgba(29,158,117,.30)}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(29,158,117,.40)}
        .alert-error{background:#FEF2F2;border-left:4px solid #EF4444;color:#B91C1C;padding:16px 20px;border-radius:var(--radius-md);margin-bottom:24px;display:flex;align-items:flex-start;gap:12px;animation:slideIn .4s}
        @keyframes slideIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        .back-link{text-align:center;margin-top:20px}
        .back-link a{display:inline-flex;align-items:center;gap:8px;color:var(--gray-500);text-decoration:none;font-size:14px;transition:color .2s}
        .back-link a:hover{color:var(--green)}
        .is-invalid{border-color:#EF4444 !important;background-color:#FEF2F2}
        .error-message{color:#B91C1C;font-size:12px;margin-top:6px;display:none}

        /* Success overlay */
        .success-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:9999;animation:fadeIn .3s}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .success-modal{background:var(--white);border-radius:var(--radius-xl);padding:48px 40px;text-align:center;max-width:440px;width:90%;box-shadow:var(--shadow-lg);animation:popIn .4s ease}
        @keyframes popIn{from{opacity:0;transform:scale(.8)}to{opacity:1;transform:scale(1)}}
        .success-icon{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;margin:0 auto 24px;box-shadow:var(--shadow-green)}
        .success-icon i{font-size:40px;color:white}
        .success-modal h3{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--navy);margin-bottom:12px}
        .success-modal p{color:var(--gray-500);font-size:15px;line-height:1.6;margin-bottom:28px}
        .btn-ok{padding:12px 40px;background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;border:none;border-radius:var(--radius-md);font-size:15px;font-weight:600;cursor:pointer;transition:all .3s;font-family:'DM Sans',sans-serif}
        .btn-ok:hover{transform:translateY(-2px);box-shadow:var(--shadow-green)}

        @media(max-width:560px){.card-body{padding:24px}.card-header{padding:24px 24px 20px}}
    </style>
</head>
<body>

<?php if($success): ?>
<div class="success-overlay" id="successOverlay">
    <div class="success-modal">
        <div class="success-icon"><i class="bi bi-check-lg"></i></div>
        <h3>Succès !</h3>
        <p>Intervention annulée est terminée</p>
        <button class="btn-ok" onclick="document.getElementById('successOverlay').remove()">OK</button>
    </div>
</div>
<?php endif; ?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <div class="logo"><div class="logo-icon"><i class="bi bi-plus-square-fill"></i></div><div class="logo-text">Med<span>Chain</span></div></div>
            <h2>Annuler une Intervention</h2>
            <p>Signalez l'annulation d'une intervention programmée</p>
        </div>
        <div class="card-body">
            <?php if(!empty($errors)):?>
                <div class="alert-error"><i class="bi bi-exclamation-triangle-fill"></i><div><?=implode('<br>',$errors)?></div></div>
            <?php endif;?>

            <form method="POST" id="annulationForm" data-form-type="intervention-annulee">
                <div class="form-group">
                    <label><i class="bi bi-heart-pulse"></i> Intervention</label>
                    <select name="idIntervention" class="form-control">
                        <option value="">-- Sélectionnez une intervention --</option>
                        <?php foreach($interventions as $i): ?>
                            <option value="<?=$i['id']?>" <?=(isset($_POST['idIntervention'])&&$_POST['idIntervention']==$i['id'])?'selected':''?>>
                                #<?=$i['id']?> — <?=htmlspecialchars($i['type'])?> (<?=date('d/m/Y',strtotime($i['date_intervention']))?>) — Dr. <?=htmlspecialchars($i['chirurgien'])?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="bi bi-chat-text"></i> Raison de l'annulation</label>
                    <textarea name="raison" class="form-control" placeholder="Décrivez la raison de l'annulation..."><?=htmlspecialchars($_POST['raison']??'')?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-x-circle"></i> Confirmer l'annulation
                </button>
            </form>
        </div>
    </div>
    <div class="back-link">
        <a href="../auth/profile.php"><i class="bi bi-arrow-left"></i> Retour à mon profil</a>
    </div>
</div>

<script src="../../assets/js/validation.js"></script>
</body>
</html>
