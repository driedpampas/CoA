<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$shelterModel = new \Models\Shelter($mysql);
$routeModel = new \Models\EvacuationRoute($mysql);
$eventModel = new \Models\Event($mysql);

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

        include '../views/PublicDashboard.php';
        break;
    }

    case 'api': {
        $resource = $routeLevels[1] ?? '';
        $action = $routeLevels[2] ?? '';

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        switch ($resource) {
            case 'events': {
                [$ok, $events] = $eventModel->getActive();
                sendJsonResponse($ok ? $events : []);
            }

            case 'shelters': {
                if ($action === 'nearest') {
                    $lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
                    $lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);

                    if ($lat === false || $lng === false || $lat === null || $lng === null) {
                        sendJsonResponse(['error' => 'Invalid or missing lat/lng parameters.'], 400);
                    }

                    [$ok, $shelters] = $shelterModel->findNearest($lat, $lng, 5);
                    sendJsonResponse($ok ? $shelters : []);
                }

                if ($action !== '' && ctype_digit($action)) {
                    [$ok, $shelter] = $shelterModel->findById((int) $action);

                    if (!$ok) {
                        sendJsonResponse(['error' => $shelter], 404);
                    }

                    sendJsonResponse($shelter);
                }

                [$ok, $shelters] = $shelterModel->getAll();
                sendJsonResponse($ok ? $shelters : []);
            }

            default:
                sendJsonResponse(['error' => 'Not found.'], 404);
        }
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
