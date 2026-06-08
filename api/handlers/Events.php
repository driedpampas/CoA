<?php

namespace Handlers;

class Events
{
    public static function handle($eventModel)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
            $size = filter_input(INPUT_GET, 'size', FILTER_VALIDATE_INT);

            if ($page !== null && $size !== null && $page !== false && $size !== false) {
                $search = filter_input(INPUT_GET, 'q', FILTER_DEFAULT);
                $sort = filter_input(INPUT_GET, 'sort', FILTER_DEFAULT) ?? 'created';
                $dir = filter_input(INPUT_GET, 'dir', FILTER_DEFAULT) ?? 'desc';

                [$ok, $data] = $eventModel->getPaginated($page, $size, $search, $sort, $dir);
                if (!$ok) {
                    \sendJsonResponse(['error' => $data], 500);
                }
                \sendJsonResponse($data);
            }

            $days = filter_input(INPUT_GET, 'days', FILTER_VALIDATE_INT, [
                'options' => [
                    'min_range' => 1,
                    'max_range' => 30,
                ],
            ]);

            if ($days === false) {
                \sendJsonResponse(['error' => 'Interval invalid.'], 400);
            }

            if ($days !== null) {
                [$ok, $events] = $eventModel->getRecentForMap($days);
                \sendJsonResponse($ok ? $events : []);
            }

            [$ok, $events] = $eventModel->getActive();
            \sendJsonResponse($ok ? $events : []);
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

            [$ok, $res] = $eventModel->create($input);
            if (!$ok) {
                \sendJsonResponse(['error' => $res], 500);
            }
            \sendJsonResponse($res, 201);
        }

        if ($method === 'PATCH' || $method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $id = $input['id'] ?? null;
            if (!$id) {
                \sendJsonResponse(['error' => 'ID is required.'], 400);
            }

            [$ok, $res] = $eventModel->update($id, $input);
            if (!$ok) {
                \sendJsonResponse(['error' => $res], 500);
            }
            \sendJsonResponse(['success' => true]);
        }

        if ($method === 'DELETE') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $id = $input['id'] ?? null;
            if (!$id) {
                \sendJsonResponse(['error' => 'ID is required.'], 400);
            }

            [$ok, $res] = $eventModel->delete($id);
            if (!$ok) {
                \sendJsonResponse(['error' => $res], 500);
            }
            \sendJsonResponse(['success' => true]);
        }

        header('Allow: GET, POST, PATCH, PUT, DELETE');
        \sendJsonResponse(['error' => 'Method not allowed.'], 405);
    }
}
