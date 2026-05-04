<?php
declare(strict_types=1);

/**
 * MedChain — Front Controller / Router
 *
 * Single entry point for the entire application.
 * URL format:  /midchaine/index.php?office=back&controller=objet&action=list&id=5
 *
 * Responsibilities:
 *  1. Boot (session, constants, autoloader)
 *  2. Register all models & controllers
 *  3. Dispatch to the correct controller method
 */

// ── 1. Bootstrap ─────────────────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', '0');          // off in production; errors go to log
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

define('BASE_PATH', __DIR__);
define('VIEWS_PATH',       BASE_PATH . '/user/projet/views');
define('VIEWS_FRONT',      VIEWS_PATH . '/frontoffice');
define('VIEWS_BACK',       VIEWS_PATH . '/backoffice');
define('VIEWS_TEMPLATES',  BASE_PATH . '/views/templates');
define('APP_ENTRY_URL',    str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/midchaine/index.php')) . '/index.php');

// ── 2. Helpers ────────────────────────────────────────────────────────────────

function routeUrl(string $controller = 'objet', string $action = 'list', array $params = []): string
{
    $query = array_merge(
        ['office' => $params['office'] ?? 'front', 'controller' => $controller, 'action' => $action],
        $params
    );
    return APP_ENTRY_URL . '?' . http_build_query($query);
}

function redirectToRoute(string $controller, string $action, array $params = []): never
{
    header('Location: ' . routeUrl($controller, $action, $params));
    exit;
}

// ── 3. Core ───────────────────────────────────────────────────────────────────
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/BaseController.php';

// ── 4. Models ─────────────────────────────────────────────────────────────────
require_once BASE_PATH . '/models/Utilisateur.php';
require_once BASE_PATH . '/models/Categorie.php';
require_once BASE_PATH . '/models/ObjetLoisir.php';
require_once BASE_PATH . '/models/Pret.php';
require_once BASE_PATH . '/models/RendezVous.php';
require_once BASE_PATH . '/models/FicheRendezVous.php';
require_once BASE_PATH . '/models/Ambulance.php';
require_once BASE_PATH . '/models/Mission.php';

// ── 5. Services ───────────────────────────────────────────────────────────────
require_once BASE_PATH . '/services/EmailService.php';

// ── 6. Controllers ────────────────────────────────────────────────────────────
require_once BASE_PATH . '/controllers/AuthController.php';
require_once BASE_PATH . '/controllers/AdminController.php';
require_once BASE_PATH . '/controllers/UtilisateurController.php';
require_once BASE_PATH . '/controllers/ObjetController.php';
require_once BASE_PATH . '/controllers/PretController.php';
require_once BASE_PATH . '/controllers/RendezVousController.php';
require_once BASE_PATH . '/controllers/FicheRendezVousController.php';
require_once BASE_PATH . '/controllers/AmbulanceMissionController.php';
require_once BASE_PATH . '/controllers/ProfileController.php';
require_once BASE_PATH . '/controllers/PasswordController.php';

// ── 7. Parse request ─────────────────────────────────────────────────────────
$office     = (isset($_GET['office']) && $_GET['office'] === 'back') ? 'back' : 'front';
$controller = strtolower(trim($_GET['controller'] ?? ($office === 'back' ? 'admin' : 'objet')));
$action     = trim($_GET['action'] ?? ($office === 'back' ? 'dashboard' : 'list'));
$id         = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ── 8. Dispatch ───────────────────────────────────────────────────────────────
try {
    switch ($controller) {

        // ────────────────────────────── AUTH ─────────────────────────────────
        case 'auth':
            $ctrl = new AuthController();
            match ($action) {
                'login'    => $ctrl->login(),
                'register' => $ctrl->register(),
                'logout'   => $ctrl->logout(),
                default    => redirectToRoute('auth', 'login')
            };
            break;

        // ────────────────────────────── ADMIN ────────────────────────────────
        case 'admin':
            if ($office !== 'back') {
                redirectToRoute('objet', 'list', ['office' => 'front']);
            }
            $ctrl = new AdminController();
            match ($action) {
                'dashboard'   => $ctrl->dashboard(),
                'users'       => $ctrl->listUsers(),
                'createUser'  => $ctrl->createUser(),
                'editUser'    => $ctrl->editUser($id),
                'deleteUser'  => $ctrl->deleteUser($id),
                'activateUser'=> $ctrl->activateUser($id),
                'deactivate'  => $ctrl->deactivateUser($id),
                'stats'       => $ctrl->statistics(),
                'exportExcel' => $ctrl->exportUsersToExcel(),
                default       => $ctrl->dashboard()
            };
            break;

        // ────────────────────────────── UTILISATEUR ───────────────────────────
        case 'utilisateur':
            $ctrl = new UtilisateurController();
            match ($action) {
                'profile' => $ctrl->profile(),
                'edit'    => $ctrl->editProfile(),
                default   => redirectToRoute('utilisateur', 'profile')
            };
            break;

        // ────────────────────────────── PASSWORD ─────────────────────────────
        case 'password':
            $ctrl = new PasswordController();
            match ($action) {
                'reset'   => $ctrl->resetRequest(),
                'confirm' => $ctrl->resetConfirm(),
                'change'  => $ctrl->changePassword(),
                default   => redirectToRoute('auth', 'login')
            };
            break;

        // ────────────────────────────── OBJET ────────────────────────────────
        case 'objet':
            $ctrl = new ObjetController();
            if ($office === 'back') {
                match ($action) {
                    'list'   => $ctrl->listBack(),
                    'add'    => ($_SERVER['REQUEST_METHOD'] === 'POST' ? $ctrl->addBack() : $ctrl->addFormBack()),
                    'edit'   => ($id > 0
                        ? ($_SERVER['REQUEST_METHOD'] === 'POST' ? $ctrl->editBack($id) : $ctrl->editFormBack($id))
                        : redirectToRoute('objet', 'list', ['office' => 'back'])),
                    'delete' => ($id > 0 ? $ctrl->deleteBack($id) : redirectToRoute('objet', 'list', ['office' => 'back'])),
                    default  => $ctrl->listBack()
                };
            } else {
                match ($action) {
                    'list'   => $ctrl->listFront(),
                    'detail' => ($id > 0 ? $ctrl->detailFront($id) : redirectToRoute('objet', 'list', ['office' => 'front'])),
                    default  => $ctrl->listFront()
                };
            }
            break;

        // ────────────────────────────── PRET ─────────────────────────────────
        case 'pret':
            $ctrl = new PretController();
            if ($office === 'back') {
                match ($action) {
                    'pending'   => $ctrl->pendingBack(),
                    'confirmed' => $ctrl->confirmedBack(),
                    'list'      => $ctrl->listBack(),
                    'confirm'   => ($id > 0 ? $ctrl->confirmBack($id) : redirectToRoute('pret', 'pending', ['office' => 'back'])),
                    'reject'    => ($id > 0 ? $ctrl->rejectBack($id) : redirectToRoute('pret', 'pending', ['office' => 'back'])),
                    'cancel'    => ($id > 0 ? $ctrl->cancelBack($id) : redirectToRoute('pret', 'list', ['office' => 'back'])),
                    'return'    => ($id > 0 ? $ctrl->returnBack($id) : redirectToRoute('pret', 'confirmed', ['office' => 'back'])),
                    default     => $ctrl->listBack()
                };
            } else {
                match ($action) {
                    'create'  => $ctrl->createFront(),
                    'myLoans' => $ctrl->myLoansFront(),
                    'cancel'  => ($id > 0 ? $ctrl->cancelFront($id) : redirectToRoute('pret', 'myLoans', ['office' => 'front'])),
                    'return'  => ($id > 0 ? $ctrl->returnFront($id) : redirectToRoute('pret', 'myLoans', ['office' => 'front'])),
                    default   => $ctrl->myLoansFront()
                };
            }
            break;

        // ────────────────────────────── RENDEZVOUS ───────────────────────────
        case 'rendezvous':
            $ctrl = new RendezVousController();
            if ($office === 'back') {
                match ($action) {
                    'list'   => $ctrl->listBack(),
                    'create' => $ctrl->createBack(),
                    'edit'   => ($id > 0 ? $ctrl->editBack($id) : redirectToRoute('rendezvous', 'list', ['office' => 'back'])),
                    'delete' => ($id > 0 ? $ctrl->deleteBack($id) : redirectToRoute('rendezvous', 'list', ['office' => 'back'])),
                    default  => $ctrl->listBack()
                };
            } else {
                match ($action) {
                    'list'   => $ctrl->listFront(),
                    'create' => $ctrl->createFront(),
                    'cancel' => ($id > 0 ? $ctrl->cancelFront($id) : redirectToRoute('rendezvous', 'list', ['office' => 'front'])),
                    default  => $ctrl->listFront()
                };
            }
            break;

        // ────────────────────────────── FICHE RDV ───────────────────────────
        case 'ficherdv':
            $ctrl = new FicheRendezVousController();
            match ($action) {
                'view'   => ($id > 0 ? $ctrl->view($id) : redirectToRoute('rendezvous', 'list')),
                'create' => $ctrl->create(),
                'edit'   => ($id > 0 ? $ctrl->edit($id) : redirectToRoute('rendezvous', 'list')),
                default  => redirectToRoute('rendezvous', 'list')
            };
            break;

        // ────────────────────────────── AMBULANCE / MISSION ─────────────────
        case 'ambulance':
            $ctrl = new AmbulanceMissionController();
            if ($office === 'back') {
                match ($action) {
                    'list'         => $ctrl->listAmbulances(),
                    'create'       => $ctrl->createAmbulance(),
                    'edit'         => ($id > 0 ? $ctrl->editAmbulance($id) : redirectToRoute('ambulance', 'list', ['office' => 'back'])),
                    'delete'       => ($id > 0 ? $ctrl->deleteAmbulance($id) : redirectToRoute('ambulance', 'list', ['office' => 'back'])),
                    'missions'     => $ctrl->listMissions(),
                    'createMission'=> $ctrl->createMission(),
                    'editMission'  => ($id > 0 ? $ctrl->editMission($id) : redirectToRoute('ambulance', 'missions', ['office' => 'back'])),
                    'deleteMission'=> ($id > 0 ? $ctrl->deleteMission($id) : redirectToRoute('ambulance', 'missions', ['office' => 'back'])),
                    default        => $ctrl->listAmbulances()
                };
            } else {
                redirectToRoute('objet', 'list', ['office' => 'front']);
            }
            break;

        // ────────────────────────────── DEFAULT ──────────────────────────────
        default:
            if ($office === 'back') {
                redirectToRoute('admin', 'dashboard', ['office' => 'back']);
            } else {
                redirectToRoute('objet', 'list', ['office' => 'front']);
            }
    }

} catch (Throwable $e) {
    error_log('[MedChain] Unhandled exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erreur — MedChain</title>
        <style>
            *{box-sizing:border-box;margin:0;padding:0}
            body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh}
            .card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:40px;max-width:640px;width:90%;box-shadow:0 25px 50px rgba(0,0,0,.5)}
            h1{color:#f87171;font-size:1.5rem;margin-bottom:12px}
            p{color:#94a3b8;line-height:1.6;margin-bottom:16px}
            code{background:#0f172a;border:1px solid #334155;border-radius:6px;padding:12px 16px;display:block;font-size:.85rem;color:#7dd3fc;white-space:pre-wrap;word-break:break-all}
            a{color:#38bdf8;text-decoration:none}
        </style>
    </head>
    <body>
        <div class="card">
            <h1>⚠ Erreur interne</h1>
            <p>Une erreur s'est produite. Veuillez contacter l'administrateur.</p>
            <?php if (ini_get('display_errors')): ?>
                <code><?php echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'); ?></code>
            <?php endif; ?>
            <p style="margin-top:20px"><a href="/midchaine/index.php">← Retour à l'accueil</a></p>
        </div>
    </body>
    </html>
    <?php
}
