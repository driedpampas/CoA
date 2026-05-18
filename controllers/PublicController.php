<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$shelterModel = new \Models\Shelter($mysql);
$routeModel   = new \Models\EvacuationRoute($mysql);
$eventModel   = new \Models\Event($mysql);

$page = $_GET['page'] ?? 'dashboard';

switch ($page) {
    case 'dashboard': {
        [$ok, $events] = $eventModel->getActive();
        $events = $ok ? $events : [];

        [$ok, $shelters] = $shelterModel->getAll();
        $shelters = $ok ? $shelters : [];

        include '../views/PublicDashboard.php';
        break;
    }

    case 'api-events': {
        header('Content-Type: application/json; charset=utf-8');
        [$ok, $events] = $eventModel->getActive();
        echo json_encode($ok ? $events : []);
        exit;
    }

    case 'api-shelters': {
        header('Content-Type: application/json; charset=utf-8');
        [$ok, $shelters] = $shelterModel->getAll();
        echo json_encode($ok ? $shelters : []);
        exit;
    }

    case 'api-nearest': {
        header('Content-Type: application/json; charset=utf-8');
        $lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
        $lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);

        if ($lat === false || $lng === false || $lat === null || $lng === null) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid or missing lat/lng parameters."]);
            exit;
        }

        [$ok, $shelters] = $shelterModel->findNearest($lat, $lng, 5);
        echo json_encode($ok ? $shelters : []);
        exit;
    }

    case 'cap-feed': {
        require __DIR__ . '/../feeds/CapFeed.php';
        exit;
    }

    default: {
        header('Location: PublicController.php?page=dashboard');
        exit;
    }
}
