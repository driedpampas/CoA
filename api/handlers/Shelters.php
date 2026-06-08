<?php

namespace Handlers;

class Shelters
{
    public static function handle($shelterModel, $action, $accountModel = null)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            if ($action === 'nearest') {
                $lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
                $lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);

                if ($lat === false || $lng === false || $lat === null || $lng === null) {
                    \sendJsonResponse(['error' => 'Invalid or missing lat/lng parameters.'], 400);
                }

                [$ok, $shelters] = $shelterModel->findNearest($lat, $lng, 5);
                \sendJsonResponse($ok ? $shelters : []);
            }

            if ($action === 'nearest-open') {
                $lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
                $lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);
                $excludeId = filter_input(INPUT_GET, 'exclude_id', FILTER_VALIDATE_INT);

                if ($lat === false || $lng === false || $lat === null || $lng === null) {
                    \sendJsonResponse(['error' => 'Invalid or missing lat/lng parameters.'], 400);
                }

                [$ok, $res] = $accountModel->findNearestOpenShelter($lat, $lng, $excludeId);
                if (!$ok) {
                    \sendJsonResponse(['error' => $res], 500);
                }
                \sendJsonResponse($res);
            }

            if ($action === 'status') {
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id) {
                    \sendJsonResponse(['error' => 'Invalid or missing id.'], 400);
                }
                $res = $accountModel->getShelterStatus($id);
                \sendJsonResponse($res);
            }

            if ($action !== '' && ctype_digit($action)) {
                [$ok, $shelter] = $shelterModel->findById((int) $action);

                if (!$ok) {
                    \sendJsonResponse(['error' => $shelter], 404);
                }

                \sendJsonResponse($shelter);
            }

            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
            $size = filter_input(INPUT_GET, 'size', FILTER_VALIDATE_INT);

            if ($page !== null && $size !== null && $page !== false && $size !== false) {
                $search = filter_input(INPUT_GET, 'q', FILTER_DEFAULT);
                $sort = filter_input(INPUT_GET, 'sort', FILTER_DEFAULT) ?? 'created';
                $dir = filter_input(INPUT_GET, 'dir', FILTER_DEFAULT) ?? 'desc';

                [$ok, $data] = $shelterModel->getPaginated($page, $size, $search, $sort, $dir);
                if (!$ok) {
                    \sendJsonResponse(['error' => $data], 500);
                }
                \sendJsonResponse($data);
            }

            [$ok, $shelters] = $shelterModel->getAll();
            \sendJsonResponse($ok ? $shelters : []);
        }

        // Check admin authentication
        $isAdmin = ($_SESSION['isLoggedIn'] ?? false) && ($_SESSION['role'] ?? '') === 'admin';
        if (!$isAdmin) {
            \sendJsonResponse(['error' => 'Forbidden: admin access required.'], 403);
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            [$ok, $res] = $shelterModel->create($input);
            if (!$ok) {
                \sendJsonResponse(['error' => $res], 500);
            }
            \sendJsonResponse($res, 201);
        }

        if ($method === 'PATCH') {
            if (!$action || !ctype_digit($action)) {
                \sendJsonResponse(['error' => 'Shelter ID is required in the URL.'], 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            [$ok, $res] = $shelterModel->update((int) $action, $input);
            if (!$ok) {
                \sendJsonResponse(['error' => $res], 500);
            }
            \sendJsonResponse(['success' => true]);
        }

        if ($method === 'DELETE') {
            if (!$action || !ctype_digit($action)) {
                \sendJsonResponse(['error' => 'Shelter ID is required in the URL.'], 400);
            }

            [$ok, $res] = $shelterModel->delete((int) $action);
            if (!$ok) {
                \sendJsonResponse(['error' => $res], 500);
            }
            \sendJsonResponse(['success' => true]);
        }

        header('Allow: GET, POST, PATCH, DELETE');
        \sendJsonResponse(['error' => 'Method not allowed.'], 405);
    }
}
