<?php
session_start();

if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Non authentifié"]);
    exit;
}

require_once __DIR__ . '/../../../controllers/BlocOperationController.php';

$controller = new BlocOperationController();

// Lire le corps JSON si présent
$input = file_get_contents('php://input');
$data = !empty($input) ? json_decode($input, true) : $_POST;

$action = $data['action'] ?? $_GET['action'] ?? null;

header('Content-Type: application/json');

switch ($action) {
    case 'create':
        echo json_encode($controller->createIntervention($data));
        break;
    
    case 'update':
        echo json_encode($controller->updateIntervention($data['id'], $data));
        break;
    
    case 'delete':
        echo json_encode($controller->deleteIntervention($data['id']));
        break;
    
    case 'search':
        $keyword = $_GET['keyword'] ?? '';
        echo json_encode($controller->searchIntervention($keyword));
        break;
    
    case 'stats':
        echo json_encode($controller->getInterventionStats());
        break;
    
    case 'list':
        $page = $_GET['page'] ?? 1;
        echo json_encode($controller->getInterventionList($page));
        break;
    
    case 'get':
        echo json_encode($controller->getInterventionById($data['id']));
        break;
    
    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Action invalide"]);
}
