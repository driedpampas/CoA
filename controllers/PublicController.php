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

function sendJsonResponse($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

$routeLevels = $routeLevels ?? [];
$page = $routeLevels[0] ?? 'dashboard';

switch ($page) {
    case 'dashboard': {
        [$ok, $events] = $eventModel->getActive();
        $events = $ok ? $events : [];

        [$ok, $shelters] = $shelterModel->getAll();
        $shelters = $ok ? $shelters : [];

        $isLoggedIn = !empty($_SESSION["isLoggedIn"]);
        $username = $_SESSION["username"] ?? "";

        $isAdmin = ($_SESSION['role'] ?? '') === 'admin';
        include __DIR__ . '/../views/PublicDashboard.php';
        break;
    }

    case 'api': {
        $resource = $routeLevels[1] ?? '';
        $action = $routeLevels[2] ?? '';

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
            default:
                sendJsonResponse(['error' => 'Not found.'], 404);
                break;
        }
        break;
    }

    case 'cap-feed': {
        require __DIR__ . '/../feeds/CapFeed.php';
        exit;
    }

    default: {
        header('Location: dashboard');
        exit;
    }
}
