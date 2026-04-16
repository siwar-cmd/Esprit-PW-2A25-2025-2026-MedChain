<?php
session_start();
require_once '../../Model/rdv.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $nouvelleDate = $_POST['nouvelleDate'];
    
    $rdv = new Rdv();
    $rdv->idRDV = $id;
    
    if($rdv->reporter($nouvelleDate)) {
        header("Location: rdv_list.php?msg=postponed");
    } else {
        header("Location: rdv_list.php?error=1");
    }
} else {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $rdv = new Rdv();
    $rdv->idRDV = $id;
    $rdv->readOne();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Reporter Rendez-vous</title>
        <link rel="stylesheet" href="backoffice-style.css">
        <style>
            .postpone-container {
                max-width: 500px;
                margin: 100px auto;
                background: white;
                padding: 30px;
                border-radius: 15px;
            }
            input {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            .btn-submit {
                background: #FF9800;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
            }
            .btn-back {
                background: #666;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 8px;
            }
            .old-date {
                color: #e74c3c;
                text-decoration: line-through;
            }
        </style>
    </head>
    <body>
        <div class="postpone-container">
            <h1>🔄 Reporter le Rendez-vous</h1>
            <p>Ancienne date : <span class="old-date"><?php echo $rdv->dateHeureDebut; ?></span></p>
            <form method="POST">
                <label>Nouvelle date :</label>
                <input type="text" name="nouvelleDate" placeholder="AAAA-MM-JJ HH:MM:SS" required>
                <small>Format: 2024-12-25 14:30:00</small><br><br>
                <button type="submit" class="btn-submit">Confirmer le report</button>
                <a href="rdv_list.php" class="btn-back">Retour</a>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>