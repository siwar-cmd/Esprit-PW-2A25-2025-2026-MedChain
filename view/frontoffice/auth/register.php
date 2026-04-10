<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: ../profile.php');
    exit();
}

// Configuration
define('BASE_PATH', dirname(__DIR__));
define('MODEL_PATH', BASE_PATH . '/model/UserModel.php');

$errors = [];
$form_data = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!file_exists(MODEL_PATH)) {
        $errors[] = "Erreur de configuration. Contactez l'administrateur.";
    } else {
        require_once MODEL_PATH;
        
        if (!class_exists('UserModel')) {
            $errors[] = "Erreur technique. Veuillez réessayer.";
        } else {
            $userModel = new UserModel();
            
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            $telephone = trim($_POST['telephone'] ?? '');
            
            $form_data = compact('nom', 'prenom', 'email', 'telephone');
            
            // Validation du nom
            if (empty($nom)) {
                $errors[] = "Le nom est requis";
            } elseif (strlen($nom) < 2) {
                $errors[] = "Le nom doit contenir au moins 2 caractères";
            } elseif (strlen($nom) > 50) {
                $errors[] = "Le nom ne doit pas dépasser 50 caractères";
            } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $nom)) {
                $errors[] = "Le nom ne doit contenir que des lettres";
            }
            
            // Validation du prénom
            if (empty($prenom)) {
                $errors[] = "Le prénom est requis";
            } elseif (strlen($prenom) < 2) {
                $errors[] = "Le prénom doit contenir au moins 2 caractères";
            } elseif (strlen($prenom) > 50) {
                $errors[] = "Le prénom ne doit pas dépasser 50 caractères";
            } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $prenom)) {
                $errors[] = "Le prénom ne doit contenir que des lettres";
            }
            
            // Validation de l'email
            if (empty($email)) {
                $errors[] = "L'email est requis";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'email n'est pas valide";
            } elseif (strlen($email) > 100) {
                $errors[] = "L'email ne doit pas dépasser 100 caractères";
            } elseif (method_exists($userModel, 'emailExists') && $userModel->emailExists($email)) {
                $errors[] = "Cet email est déjà utilisé";
            }
            
            // Validation du mot de passe
            if (empty($password)) {
                $errors[] = "Le mot de passe est requis";
            } elseif (strlen($password) < 8) {
                $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
            } elseif (strlen($password) > 255) {
                $errors[] = "Le mot de passe est trop long";
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $errors[] = "Le mot de passe doit contenir au moins une majuscule";
            } elseif (!preg_match('/[a-z]/', $password)) {
                $errors[] = "Le mot de passe doit contenir au moins une minuscule";
            } elseif (!preg_match('/[0-9]/', $password)) {
                $errors[] = "Le mot de passe doit contenir au moins un chiffre";
            } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
                $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
            }
            
            // Confirmation du mot de passe
            if ($password !== $password_confirm) {
                $errors[] = "Les mots de passe ne correspondent pas";
            }
            
            // Validation du téléphone (optionnel)
            if (!empty($telephone)) {
                $telephone_clean = preg_replace('/[^0-9+]/', '', $telephone);
                if (strlen($telephone_clean) < 8 || strlen($telephone_clean) > 15) {
                    $errors[] = "Le numéro de téléphone n'est pas valide";
                }
            }
            
            // Inscription
            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $userData = [
                    'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                    'prenom' => htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8'),
                    'email' => filter_var($email, FILTER_SANITIZE_EMAIL),
                    'password' => $hashedPassword,
                    'telephone' => !empty($telephone) ? htmlspecialchars($telephone) : null,
                    'role' => 'user',
                    'status' => 'actif'
                ];
                
                if (method_exists($userModel, 'register')) {
                    $userId = $userModel->register($userData);
                    if ($userId) {
                        $success_message = "Inscription réussie ! Redirection vers la connexion...";
                        header("refresh:2;url=login.php?success=registered");
                        exit();
                    } else {
                        $errors[] = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
                        error_log("Erreur d'inscription pour l'email: " . $email);
                    }
                } else {
                    $errors[] = "Erreur technique. Méthode d'inscription non disponible.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* ===== Charte MedChain ===== */
        :root {
            --primary: #1D9E75;
            --secondary: #0F6E56;
            --accent: #9FE1CB;
            --neutral: #888780;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0faf6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== Top Bar ===== */
        .top-bar {
            background: var(--secondary);
            color: #fff;
            font-size: 13px;
            padding: 8px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar a { color: #fff; text-decoration: none; margin-right: 18px; transition: color 0.3s; }
        .top-bar a:hover { color: var(--accent); }
        .top-bar .social-links a { margin-right: 0; margin-left: 14px; }

        /* ===== Brand Bar ===== */
        .brand-bar {
            background: #fff;
            padding: 12px 5%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        .brand-logo span { color: var(--secondary); }
        .brand-bar a.back-link {
            font-size: 14px;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .brand-bar a.back-link:hover { color: var(--primary); }

        /* ===== Main Layout ===== */
        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 15px 50px;
        }

        .auth-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(15,110,86,0.12);
            width: 100%;
            max-width: 560px;
            overflow: hidden;
        }

        /* ===== Card Header ===== */
        .auth-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            text-align: center;
            padding: 30px 30px 26px;
        }

        .auth-header .auth-icon {
            width: 58px;
            height: 58px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 22px;
        }

        .auth-header h3 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .auth-header p { opacity: 0.88; font-size: 14px; margin: 0; }

        /* ===== Card Body ===== */
        .auth-body { padding: 32px 36px 28px; }

        /* ===== Step indicator ===== */
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ddd;
            transition: background 0.3s;
        }

        .step-dot.active { background: var(--primary); width: 24px; border-radius: 4px; }

        /* ===== Form Controls ===== */
        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 5px;
        }

        .required-star { color: #e74c3c; margin-left: 2px; }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(29,158,117,0.15);
            outline: none;
        }

        .input-group .form-control { border-right: none; border-radius: 12px 0 0 12px; }
        .input-group .input-group-text {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-left: none;
            border-radius: 0 12px 12px 0;
            cursor: pointer;
            color: var(--neutral);
            transition: color 0.3s;
        }
        .input-group .input-group-text:hover { color: var(--primary); }
        .input-group:focus-within .input-group-text { border-color: var(--primary); }

        /* ===== Password strength ===== */
        .password-strength-bar {
            height: 5px;
            border-radius: 5px;
            margin-top: 6px;
            transition: all 0.3s;
            background: #e2e8f0;
        }

        .strength-fill {
            height: 100%;
            border-radius: 5px;
            transition: all 0.4s;
            width: 0%;
        }

        .requirements {
            font-size: 12px;
            margin-top: 8px;
            background: #f8fffe;
            border-radius: 10px;
            padding: 10px 14px;
            border: 1px solid #e2f5ef;
        }

        .requirements ul { list-style: none; padding: 0; margin: 0; }
        .requirements li { margin-bottom: 3px; transition: color 0.3s; color: #999; }
        .requirements li.valid { color: var(--primary); }
        .requirements li.invalid { color: #e74c3c; }

        /* ===== Checkbox ===== */
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label { font-size: 13px; color: #555; }

        /* ===== Submit Button ===== */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 13px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
            letter-spacing: 0.3px;
            margin-top: 4px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(29,158,117,0.35);
        }

        /* ===== Links ===== */
        .auth-links { text-align: center; margin-top: 20px; font-size: 14px; color: #888; }
        .auth-links a { color: var(--primary); text-decoration: none; font-weight: 500; transition: color 0.3s; }
        .auth-links a:hover { color: var(--secondary); }

        /* ===== Alerts ===== */
        .alert { border-radius: 12px; font-size: 14px; border: none; margin-bottom: 18px; }
        .alert-danger { background: #fff0f0; color: #c0392b; }
        .alert-success { background: #f0faf6; color: var(--secondary); }

        /* ===== Section Divider ===== */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2f5ef;
        }

        /* ===== Footer ===== */
        .auth-footer {
            background: #0a2e24;
            color: #8fa89f;
            text-align: center;
            padding: 14px;
            font-size: 13px;
        }
        .auth-footer a { color: var(--accent); text-decoration: none; }

        /* ===== Invalid feedback ===== */
        .invalid-feedback { font-size: 12px; }
        .was-validated .form-control:invalid,
        .form-control.is-invalid { border-color: #e74c3c; }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div>
            <a href="tel:+21670000000"><i class="fas fa-phone-alt"></i> +216 70 000 000</a>
            <a href="mailto:contact@medchain.com"><i class="fas fa-envelope"></i> contact@medchain.com</a>
        </div>
        <div class="social-links">
            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        </div>
    </div>

    <!-- Brand Bar -->
    <div class="brand-bar">
        <a href="../index.php" class="brand-logo">Med<span>Chain</span></a>
        <a href="../home/index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Retour à l'accueil</a>
    </div>

    <!-- Auth Form -->
    <div class="auth-wrapper">
        <div class="auth-card">

            <div class="auth-header">
                <div class="auth-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Créer un compte</h3>
                <p>Rejoignez MedChain et sécurisez votre santé</p>
            </div>

            <div class="auth-body">

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Veuillez corriger les erreurs suivantes :</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm" novalidate>

                    <!-- Identité -->
                    <div class="section-divider">Identité</div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom <span class="required-star">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom"
                                   placeholder="Votre nom"
                                   value="<?php echo htmlspecialchars($form_data['nom'] ?? ''); ?>"
                                   required>
                            <div class="invalid-feedback">Nom requis (min. 2 caractères)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom <span class="required-star">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom"
                                   placeholder="Votre prénom"
                                   value="<?php echo htmlspecialchars($form_data['prenom'] ?? ''); ?>"
                                   required>
                            <div class="invalid-feedback">Prénom requis (min. 2 caractères)</div>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="section-divider">Contact</div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="required-star">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="exemple@email.com"
                               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                               required>
                        <div class="invalid-feedback">Veuillez entrer un email valide</div>
                    </div>

                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone <span style="color:#999; font-weight:400;">(optionnel)</span></label>
                        <input type="tel" class="form-control" id="telephone" name="telephone"
                               placeholder="+216 XX XXX XXX"
                               value="<?php echo htmlspecialchars($form_data['telephone'] ?? ''); ?>">
                    </div>

                    <!-- Sécurité -->
                    <div class="section-divider">Sécurité</div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe <span class="required-star">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="••••••••" required>
                            <span class="input-group-text" onclick="togglePass('password', 'eye1')">
                                <i class="fas fa-eye" id="eye1"></i>
                            </span>
                        </div>
                        <div class="password-strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="requirements" id="passwordRequirements">
                            <ul>
                                <li id="req-length">✓ Minimum 8 caractères</li>
                                <li id="req-upper">✓ Au moins une majuscule</li>
                                <li id="req-lower">✓ Au moins une minuscule</li>
                                <li id="req-number">✓ Au moins un chiffre</li>
                                <li id="req-special">✓ Au moins un caractère spécial</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">Confirmer le mot de passe <span class="required-star">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                   placeholder="••••••••" required>
                            <span class="input-group-text" onclick="togglePass('password_confirm', 'eye2')">
                                <i class="fas fa-eye" id="eye2"></i>
                            </span>
                        </div>
                        <small id="passwordMatchMsg" class="mt-1 d-block"></small>
                    </div>

                    <!-- CGU -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            J'accepte les <a href="#" style="color:var(--primary);">conditions d'utilisation</a> <span class="required-star">*</span>
                        </label>
                        <div class="invalid-feedback">Vous devez accepter les conditions d'utilisation</div>
                    </div>

                    <button type="submit" name="submit" class="btn-submit">
                        <i class="fas fa-user-plus me-2"></i>Créer mon compte
                    </button>

                </form>

                <div class="auth-links">
                    Déjà inscrit ? <a href="login.php">Se connecter</a>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="auth-footer">
        &copy; <?php echo date('Y'); ?> MedChain — <a href="../home/index.php">Retour à l'accueil</a>
    </div>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle affichage mot de passe
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Force du mot de passe
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const reqLength  = document.getElementById('req-length');
        const reqUpper   = document.getElementById('req-upper');
        const reqLower   = document.getElementById('req-lower');
        const reqNumber  = document.getElementById('req-number');
        const reqSpecial = document.getElementById('req-special');

        const strengthColors = ['#e74c3c', '#e74c3c', '#f39c12', '#27ae60', '#1D9E75'];
        const strengthWidths  = ['0%', '25%', '50%', '75%', '100%'];

        function updatePasswordStrength() {
            const p = passwordInput.value;
            const checks = [
                p.length >= 8,
                /[A-Z]/.test(p),
                /[a-z]/.test(p),
                /[0-9]/.test(p),
                /[^a-zA-Z0-9]/.test(p)
            ];

            reqLength.className  = checks[0] ? 'valid' : (p.length ? 'invalid' : '');
            reqUpper.className   = checks[1] ? 'valid' : (p.length ? 'invalid' : '');
            reqLower.className   = checks[2] ? 'valid' : (p.length ? 'invalid' : '');
            reqNumber.className  = checks[3] ? 'valid' : (p.length ? 'invalid' : '');
            reqSpecial.className = checks[4] ? 'valid' : (p.length ? 'invalid' : '');

            const strength = checks.filter(Boolean).length;
            strengthFill.style.width = strengthWidths[strength] || '0%';
            strengthFill.style.backgroundColor = strengthColors[strength] || '#e2e8f0';

            checkPasswordMatch();
        }

        passwordInput.addEventListener('input', updatePasswordStrength);

        // Correspondance mot de passe
        const confirmInput = document.getElementById('password_confirm');
        const matchMsg = document.getElementById('passwordMatchMsg');

        function checkPasswordMatch() {
            if (!confirmInput.value) {
                matchMsg.textContent = '';
                confirmInput.setCustomValidity('');
                return;
            }
            if (confirmInput.value !== passwordInput.value) {
                matchMsg.innerHTML = '❌ Les mots de passe ne correspondent pas';
                matchMsg.style.color = '#e74c3c';
                confirmInput.setCustomValidity('mismatch');
            } else {
                matchMsg.innerHTML = '✓ Les mots de passe correspondent';
                matchMsg.style.color = '#1D9E75';
                confirmInput.setCustomValidity('');
            }
        }

        confirmInput.addEventListener('input', checkPasswordMatch);

        // Validation Bootstrap
        (function() {
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })();

        // Auto-fermeture alertes
        setTimeout(function() {
            document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
                setTimeout(() => new bootstrap.Alert(alert).close(), 5000);
            });
        }, 1000);
    </script>
</body>
</html>