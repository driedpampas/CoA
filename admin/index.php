<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/AdminEventController.php';
require_once __DIR__ . '/controllers/AdminShelterController.php';
require_once __DIR__ . '/controllers/AdminRouteController.php';

Bootstrap::boot();

$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', ltrim($uri, '/')), 'strlen'));
// $segments[0] === 'admin'
$resource = $segments[1] ?? '';
$id       = isset($segments[2]) && ctype_digit($segments[2]) ? (int) $segments[2] : null;
$method   = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json; charset=utf-8');

switch ($resource) {
    case '':
        header_remove('Content-Type');
        AdminController::dashboard();
        break;

    case 'events':
        switch ($method) {
            case 'GET':
                header_remove('Content-Type');
                AdminController::dashboard();
                break;
            case 'POST':
                header_remove('Content-Type');
                AdminEventController::create();
                break;
            case 'PATCH':
                if ($id === null) { http_response_code(400); echo json_encode(['error' => 'Event ID required in URL.']); exit; }
                AdminEventController::update($id);
                break;
            case 'DELETE':
                if ($id === null) { http_response_code(400); echo json_encode(['error' => 'Event ID required in URL.']); exit; }
                AdminEventController::delete($id);
                break;
            default:
                http_response_code(405);
                header('Allow: GET, POST, PATCH, DELETE');
                echo json_encode(['error' => 'Method not allowed.']);
        }
        break;

    case 'shelters':
        switch ($method) {
            case 'GET':
                header_remove('Content-Type');
                AdminController::dashboard();
                break;
            case 'POST':
                header_remove('Content-Type');
                AdminShelterController::create();
                break;
            case 'PATCH':
                if ($id === null) { http_response_code(400); echo json_encode(['error' => 'Shelter ID required in URL.']); exit; }
                AdminShelterController::update($id);
                break;
            case 'DELETE':
                if ($id === null) { http_response_code(400); echo json_encode(['error' => 'Shelter ID required in URL.']); exit; }
                AdminShelterController::delete($id);
                break;
            default:
                http_response_code(405);
                header('Allow: GET, POST, PATCH, DELETE');
                echo json_encode(['error' => 'Method not allowed.']);
        }
        break;

    case 'routes':
        switch ($method) {
            case 'GET':
                header_remove('Content-Type');
                AdminController::dashboard();
                break;
            case 'POST':
                header_remove('Content-Type');
                AdminRouteController::create();
                break;
            case 'PATCH':
                if ($id === null) { http_response_code(400); echo json_encode(['error' => 'Route ID required in URL.']); exit; }
                AdminRouteController::update($id);
                break;
            case 'DELETE':
                if ($id === null) { http_response_code(400); echo json_encode(['error' => 'Route ID required in URL.']); exit; }
                AdminRouteController::delete($id);
                break;
            default:
                http_response_code(405);
                header('Allow: GET, POST, PATCH, DELETE');
                echo json_encode(['error' => 'Method not allowed.']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found.']);
}
