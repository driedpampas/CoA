<?php

namespace Handlers;

class Routes
{
    public static function handle($routeModel, $action)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        if ($action === 'nearest') {
            $lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
            $lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);

            if ($lat === false || $lng === false || $lat === null || $lng === null) {
                \sendJsonResponse(['error' => 'Invalid or missing lat/lng parameters.'], 400);
            }

            [$ok, $routes] = $routeModel->findNearestRoute($lat, $lng, 3);
            \sendJsonResponse($ok ? $routes : []);
        }

        if ($action !== '' && ctype_digit($action)) {
            [$ok, $routes] = $routeModel->getByShelterId((int) $action);
            \sendJsonResponse($ok ? $routes : []);
        }

        sendJsonResponse(['error' => 'Missing action. Use nearest?lat=X&lng=Y or {shelterId}.'], 400);
    }
}
