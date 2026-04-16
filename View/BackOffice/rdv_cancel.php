<?php
session_start();
require_once '../../Model/rdv.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $motif = $_POST['motif_annulation'];
    
    $rdv = new Rdv();
    $rdv->idRDV = $id;
    
    if($rdv->annuler($motif)) {
        header("Location: rdv_list.php?msg=cancelled");
    } else {
        header("Location: rdv_list.php?error=1");
    }
} else {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Annuler Rendez-vous</title>
        <link rel="stylesheet" href="backoffice-style.css">
        <style>
            .cancel-container {
                max-width: 500px;
                margin: 100px auto;
                background: white;
                padding: 30px;
                border-radius: 15px;
            }
            textarea {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            .btn-submit {
                background: #e74c3c;
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
        </style>
    </head>
    <body>
        <div class="cancel-container">
            <h1>❌ Annuler le Rendez-vous</h1>
            <form method="POST">
                <label>Motif d'annulation :</label>
                <textarea name="motif_annulation" rows="4" required></textarea>
                <button type="submit" class="btn-submit">Confirmer l'annulation</button>
                <a href="rdv_list.php" class="btn-back">Retour</a>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>