<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$shelterModel = new \Models\Shelter($mysql);
$routeModel = new \Models\EvacuationRoute($mysql);
$eventModel = new \Models\Event($mysql);
$accountModel = new \Models\Account($mysql);
$notificationModel = new \Models\Notification($mysql);

function sendJsonResponse($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routeLevels = array_values(array_filter(explode('/', ltrim($uri, '/')), 'strlen'));

$resource = $routeLevels[1] ?? '';
$action = $routeLevels[2] ?? '';
$identifier = $routeLevels[3] ?? '';

switch ($resource) {
    case 'events':
        \Handlers\Events::handle($eventModel);
        break;

    case 'shelters':
        \Handlers\Shelters::handle($shelterModel, $action);
        break;

    case 'routes':
        \Handlers\Routes::handle($routeModel, $action);
        break;

    case 'auth':
        \Handlers\Auth::handle($accountModel, $action);
        break;

    case 'notifications':
        \Handlers\Notifications::handle($notificationModel, $action, $identifier);
        break;

    default:
        sendJsonResponse(['error' => 'Not found.'], 404);
}
