<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Rendez-vous - MedChain</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-container h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
        .btn-back {
            background-color: #666;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        .btn-back:hover {
            background-color: #555;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .required:after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>📅 Nouveau Rendez-vous</h1>
        
        <?php if(isset($_GET['error'])): ?>
    <div class="error-message">
        <strong>⚠ Erreur détectée :</strong><br>
        <?php 
            $error = $_GET['error'];
            
            // Afficher l'erreur exacte
            if($error == 'date_debut_required') {
                echo "• La date et heure de début est obligatoire.";
            }
            elseif($error == 'date_fin_required') {
                echo "• La date et heure de fin est obligatoire.";
            }
            elseif($error == 'type_required') {
                echo "• Le type de consultation est obligatoire.";
            }
            elseif($error == 'date_debut_invalid_format') {
                echo "• Le format de la date de début est invalide.<br>";
                echo "  Format attendu: AAAA-MM-JJ HH:MM:SS (exemple: 2024-12-25 14:30:00)";
            }
            elseif($error == 'date_fin_invalid_format') {
                echo "• Le format de la date de fin est invalide.<br>";
                echo "  Format attendu: AAAA-MM-JJ HH:MM:SS (exemple: 2024-12-25 15:30:00)";
            }
            elseif($error == 'date_invalid_order') {
                echo "• La date de fin doit être postérieure à la date de début.";
            }
            elseif($error == 'date_past') {
                echo "• La date de début ne peut pas être dans le passé.";
            }
            elseif($error == 'db_error') {
                echo "• Erreur lors de l'enregistrement dans la base de données.";
            }
            elseif(strpos($error, ',') !== false) {
                // Plusieurs erreurs
                $errors = explode(',', $error);
                foreach($errors as $err) {
                    switch($err) {
                        case 'date_debut_required':
                            echo "• La date et heure de début est obligatoire.<br>";
                            break;
                        case 'date_fin_required':
                            echo "• La date et heure de fin est obligatoire.<br>";
                            break;
                        case 'type_required':
                            echo "• Le type de consultation est obligatoire.<br>";
                            break;
                        case 'date_debut_invalid_format':
                            echo "• Format de date de début invalide.<br>";
                            break;
                        case 'date_fin_invalid_format':
                            echo "• Format de date de fin invalide.<br>";
                            break;
                        case 'date_invalid_order':
                            echo "• La date de fin doit être après la date de début.<br>";
                            break;
                        case 'date_past':
                            echo "• La date ne peut pas être dans le passé.<br>";
                            break;
                        default:
                            echo "• Erreur: " . $err . "<br>";
                    }
                }
            }
            else {
                echo "• " . urldecode($error);
            }
        ?>
    </div>
<?php endif; ?>

        <form method="POST" action="index.php?page=rdv&action=create">
            <div class="form-group">
                <label for="dateHeureDebut" class="required">Date et heure de début</label>
                <input type="text" id="dateHeureDebut" name="dateHeureDebut" placeholder="AAAA-MM-JJ HH:MM:SS" value="<?php echo isset($_POST['dateHeureDebut']) ? htmlspecialchars($_POST['dateHeureDebut']) : ''; ?>">
                <small>Format: 2024-12-25 14:30:00</small>
            </div>

            <div class="form-group">
                <label for="dateHeureFin" class="required">Date et heure de fin</label>
                <input type="text" id="dateHeureFin" name="dateHeureFin" placeholder="AAAA-MM-JJ HH:MM:SS" value="<?php echo isset($_POST['dateHeureFin']) ? htmlspecialchars($_POST['dateHeureFin']) : ''; ?>">
                <small>Format: 2024-12-25 15:30:00</small>
            </div>

            <div class="form-group">
                <label for="typeConsultation" class="required">Type de consultation</label>
                <select id="typeConsultation" name="typeConsultation">
                    <option value="">Sélectionnez un type</option>
                    <option value="Consultation générale" <?php echo (isset($_POST['typeConsultation']) && $_POST['typeConsultation'] == 'Consultation générale') ? 'selected' : ''; ?>>🏥 Consultation générale</option>
                    <option value="Consultation spécialiste" <?php echo (isset($_POST['typeConsultation']) && $_POST['typeConsultation'] == 'Consultation spécialiste') ? 'selected' : ''; ?>>👨‍⚕️ Consultation spécialiste</option>
                    <option value="Téléconsultation" <?php echo (isset($_POST['typeConsultation']) && $_POST['typeConsultation'] == 'Téléconsultation') ? 'selected' : ''; ?>>💻 Téléconsultation</option>
                    <option value="Urgence" <?php echo (isset($_POST['typeConsultation']) && $_POST['typeConsultation'] == 'Urgence') ? 'selected' : ''; ?>>🚨 Urgence</option>
                    <option value="Contrôle" <?php echo (isset($_POST['typeConsultation']) && $_POST['typeConsultation'] == 'Contrôle') ? 'selected' : ''; ?>>📋 Contrôle</option>
                    <option value="Suivi post-opératoire" <?php echo (isset($_POST['typeConsultation']) && $_POST['typeConsultation'] == 'Suivi post-opératoire') ? 'selected' : ''; ?>>🏥 Suivi post-opératoire</option>
                </select>
            </div>

            <div class="form-group">
                <label for="motif">Motif de la consultation</label>
                <textarea id="motif" name="motif" rows="4" placeholder="Décrivez le motif de votre consultation..."><?php echo isset($_POST['motif']) ? htmlspecialchars($_POST['motif']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-submit">✓ Créer le rendez-vous</button>
                <a href="index.php?page=rdv&action=index" class="btn-back">← Retour à la liste</a>
            </div>
        </form>
    </div>
</body>
</html>