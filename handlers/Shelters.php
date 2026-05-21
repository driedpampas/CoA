<?php

namespace Handlers;

class Shelters
{
    public static function handle($shelterModel, $action)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

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
}
