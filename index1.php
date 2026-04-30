<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

define('BASE_PATH', __DIR__);
define('APP_ENTRY_URL', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index1.php'));

function routeUrl(string $controller = 'objet', string $action = 'list', array $params = []): string
{
    $query = array_merge(
        [
            'office' => $params['office'] ?? 'front',
            'controller' => $controller,
            'action' => $action,
        ],
        $params
    );

    return APP_ENTRY_URL . '?' . http_build_query($query);
}

function redirectToRoute(string $controller, string $action, array $params = []): void
{
    header('Location: ' . routeUrl($controller, $action, $params));
    exit;
}

require_once BASE_PATH . '/models/config.php';
require_once BASE_PATH . '/models/Database.php';
require_once BASE_PATH . '/models/ObjetLoisir.php';
require_once BASE_PATH . '/models/Pret.php';
require_once BASE_PATH . '/controllers/AdminController.php';
require_once BASE_PATH . '/controllers/ObjetController.php';
require_once BASE_PATH . '/controllers/PretController.php';
require_once BASE_PATH . '/controllers/JokeController.php';

$office = isset($_GET['office']) && $_GET['office'] === 'back' ? 'back' : 'front';
$controller = trim($_GET['controller'] ?? ($office === 'back' ? 'admin' : 'objet'));
$action = trim($_GET['action'] ?? ($office === 'back' ? 'dashboard' : 'list'));
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$adminController = new AdminController();
$objetController = new ObjetController();
$pretController = new PretController();
$jokeController = new JokeController();

try {
    switch ($controller) {
        case 'admin':
            if ($office !== 'back') {
                redirectToRoute('objet', 'list', ['office' => 'front']);
            }

            $adminController->dashboard();
            break;

        case 'objet':
            switch ($action) {
                case 'list':
                    $office === 'back' ? $objetController->listBack() : $objetController->listFront();
                    break;

                case 'detail':
                    if ($office !== 'front' || $id <= 0) {
                        redirectToRoute('objet', 'list', ['office' => 'front']);
                    }

                    $objetController->detailFront($id);
                    break;

                case 'add':
                    if ($office !== 'back') {
                        redirectToRoute('objet', 'list', ['office' => 'front']);
                    }

                    $_SERVER['REQUEST_METHOD'] === 'POST'
                        ? $objetController->addBack()
                        : $objetController->addFormBack();
                    break;

                case 'edit':
                    if ($office !== 'back' || $id <= 0) {
                        redirectToRoute('objet', 'list', ['office' => 'back']);
                    }

                    $_SERVER['REQUEST_METHOD'] === 'POST'
                        ? $objetController->editBack($id)
                        : $objetController->editFormBack($id);
                    break;

                case 'delete':
                    if ($office !== 'back' || $id <= 0) {
                        redirectToRoute('objet', 'list', ['office' => 'back']);
                    }

                    $objetController->deleteBack($id);
                    break;

                default:
                    redirectToRoute('objet', 'list', ['office' => $office]);
            }
            break;

        case 'pret':
            switch ($action) {
                case 'pending':
                    if ($office !== 'back') {
                        redirectToRoute('pret', 'myLoans', ['office' => 'front']);
                    }

                    $pretController->pendingBack();
                    break;

                case 'confirmed':
                    if ($office !== 'back') {
                        redirectToRoute('pret', 'myLoans', ['office' => 'front']);
                    }

                    $pretController->confirmedBack();
                    break;

                case 'list':
                    if ($office !== 'back') {
                        redirectToRoute('objet', 'list', ['office' => 'front']);
                    }

                    $pretController->listBack();
                    break;

                case 'confirm':
                    if ($office !== 'back' || $id <= 0) {
                        redirectToRoute('pret', 'pending', ['office' => 'back']);
                    }

                    $pretController->confirmBack($id);
                    break;

                case 'cancel':
                    if ($id <= 0) {
                        redirectToRoute('pret', $office === 'back' ? 'list' : 'myLoans', ['office' => $office]);
                    }

                    $office === 'back'
                        ? $pretController->cancelBack($id)
                        : $pretController->cancelFront($id);
                    break;

                case 'return':
                    if ($id <= 0) {
                        redirectToRoute('pret', $office === 'back' ? 'confirmed' : 'myLoans', ['office' => $office]);
                    }

                    $office === 'back'
                        ? $pretController->returnBack($id)
                        : $pretController->returnFront($id);
                    break;

                case 'create':
                    if ($office !== 'front') {
                        redirectToRoute('objet', 'list', ['office' => 'back']);
                    }

                    $pretController->createFront();
                    break;

                case 'myLoans':
                    if ($office !== 'front') {
                        redirectToRoute('pret', 'list', ['office' => 'back']);
                    }

                    $pretController->myLoansFront();
                    break;

                default:
                    redirectToRoute('objet', 'list', ['office' => $office]);
            }
            break;
        
        case 'joke':
            if ($office !== 'front') {
                redirectToRoute('objet', 'list', ['office' => 'front']);
            }

            $jokeController->index();
            break;

        default:
            redirectToRoute($office === 'back' ? 'admin' : 'objet', $office === 'back' ? 'dashboard' : 'list', ['office' => $office]);
    }
} catch (Throwable $exception) {
    http_response_code(500);
    $errorMessage = APP_ENV === 'development'
        ? $exception->getMessage()
        : 'An unexpected error occurred.';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Application Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; background: #f7fafc; color: #1a202c; }
            .error-box { max-width: 760px; margin: 60px auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06); }
            h1 { margin-top: 0; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>Application Error</h1>
            <p><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </body>
    </html>
    <?php
}
