<?php

namespace Handlers;

class Routes
{
    public static function handle($routeModel, $action, $shelterModel = null)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');
            \sendJsonResponse(null, 204);
        }

        // Mutation methods require admin and CSRF token
        if ($method !== 'GET') {
            $isAdmin = ($_SESSION['isLoggedIn'] ?? false) && ($_SESSION['role'] ?? '') === 'admin';
            if (!$isAdmin) {
                \sendJsonResponse(['error' => 'Forbidden: admin access required.'], 403);
            }

            $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
                \sendJsonResponse(['error' => 'Invalid CSRF token.'], 400);
            }
        }

        if ($method === 'GET') {
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

            // Check for pagination parameters (Admin dashboard view)
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
            $size = filter_input(INPUT_GET, 'size', FILTER_VALIDATE_INT);

            if ($page !== null && $size !== null && $page !== false && $size !== false) {
                $search = filter_input(INPUT_GET, 'q', FILTER_DEFAULT);
                $sort = filter_input(INPUT_GET, 'sort', FILTER_DEFAULT) ?? 'created';
                $dir = filter_input(INPUT_GET, 'dir', FILTER_DEFAULT) ?? 'desc';

                [$ok, $data] = $routeModel->getPaginated($page, $size, $search, $sort, $dir);
                if (!$ok) {
                    \sendJsonResponse(['error' => $data], 500);
                }
                \sendJsonResponse($data);
            }

            // Default fallback is to return all routes
            [$ok, $routes] = $routeModel->getAll();
            \sendJsonResponse($ok ? $routes : []);
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $name = trim(filter_var($input['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $shelterId = filter_var($input['shelter_id'] ?? null, FILTER_VALIDATE_INT);
            $fromLatitude = isset($input['from_latitude']) && $input['from_latitude'] !== '' ? filter_var($input['from_latitude'], FILTER_VALIDATE_FLOAT) : false;
            $fromLongitude = isset($input['from_longitude']) && $input['from_longitude'] !== '' ? filter_var($input['from_longitude'], FILTER_VALIDATE_FLOAT) : false;
            $status = $input['status'] ?? 'active';
            $notes = trim(filter_var($input['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

            if (empty($name) || !$shelterId || $fromLatitude === false || $fromLongitude === false || $fromLatitude === null || $fromLongitude === null) {
                \sendJsonResponse(['error' => 'Route Name, Shelter, Start Latitude, and Start Longitude are required.'], 400);
            }

            if (!$shelterModel) {
                \sendJsonResponse(['error' => 'Internal Server Error: shelter dependency unavailable.'], 500);
            }

            // Fetch high-fidelity geometry from OSRM
            [$okRoute, $routeDetails] = self::fetchRouteGeometry($shelterModel, $shelterId, $fromLatitude, $fromLongitude);
            if (!$okRoute) {
                \sendJsonResponse(['error' => $routeDetails], 400);
            }

            $data = [
                'name' => $name,
                'shelter_id' => $shelterId,
                'from_latitude' => $fromLatitude,
                'from_longitude' => $fromLongitude,
                'route_geometry' => $routeDetails['wkt'],
                'distance_meters' => $routeDetails['distance'],
                'estimated_minutes' => $routeDetails['duration'],
                'status' => $status,
                'notes' => $notes
            ];

            [$ok, $res] = $routeModel->create($data);
            if (!$ok) {
                \sendJsonResponse(['error' => $res], 500);
            }
            \sendJsonResponse($res, 201);
        }

        if ($method === 'PATCH') {
            if (!$action || !ctype_digit($action)) {
                \sendJsonResponse(['error' => 'Route ID is required in the URL.'], 400);
            }

            $id = (int) $action;
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $name = trim(filter_var($input['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $shelterId = filter_var($input['shelter_id'] ?? null, FILTER_VALIDATE_INT);
            $fromLatitude = isset($input['from_latitude']) && $input['from_latitude'] !== '' ? filter_var($input['from_latitude'], FILTER_VALIDATE_FLOAT) : false;
            $fromLongitude = isset($input['from_longitude']) && $input['from_longitude'] !== '' ? filter_var($input['from_longitude'], FILTER_VALIDATE_FLOAT) : false;
            $status = $input['status'] ?? 'active';
            $notes = trim(filter_var($input['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

            if (empty($name) || !$shelterId || $fromLatitude === false || $fromLongitude === false || $fromLatitude === null || $fromLongitude === null) {
                \sendJsonResponse(['error' => 'Route Name, Shelter, Start Latitude, and Start Longitude are required.'], 400);
            }

            if (!$shelterModel) {
                \sendJsonResponse(['error' => 'Internal Server Error: shelter dependency unavailable.'], 500);
            }

            // Fetch high-fidelity geometry from OSRM
            [$okRoute, $routeDetails] = self::fetchRouteGeometry($shelterModel, $shelterId, $fromLatitude, $fromLongitude);
            if (!$okRoute) {
                \sendJsonResponse(['error' => $routeDetails], 400);
            }

            $data = [
                'name' => $name,
                'shelter_id' => $shelterId,
                'from_latitude' => $fromLatitude,
                'from_longitude' => $fromLongitude,
                'route_geometry' => $routeDetails['wkt'],
                'distance_meters' => $routeDetails['distance'],
                'estimated_minutes' => $routeDetails['duration'],
                'status' => $status,
                'notes' => $notes
            ];

            [$ok, $res] = $routeModel->update($id, $data);
            if (!$ok) {
                \sendJsonResponse(['error' => $res], 500);
            }
            \sendJsonResponse(['success' => true]);
        }

        if ($method === 'DELETE') {
            if (!$action || !ctype_digit($action)) {
                \sendJsonResponse(['error' => 'Route ID is required in the URL.'], 400);
            }

            [$ok, $res] = $routeModel->delete((int) $action);
            if (!$ok) {
                \sendJsonResponse(['error' => $res], 500);
            }
            \sendJsonResponse(['success' => true]);
        }

        header('Allow: GET, POST, PATCH, DELETE');
        \sendJsonResponse(['error' => 'Method not allowed.'], 405);
    }

    private static function fetchRouteGeometry($shelterModel, $shelterId, $fromLat, $fromLng)
    {
        [$ok, $shelter] = $shelterModel->findById($shelterId);
        if (!$ok || !$shelter) {
            return [false, 'Shelter not found.'];
        }

        $toLat = (float)$shelter['latitude'];
        $toLng = (float)$shelter['longitude'];

        $url = "https://router.project-osrm.org/route/v1/driving/" .
               $fromLng . "," . $fromLat . ";" .
               $toLng . "," . $toLat . "?overview=full&geometries=geojson";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        curl_close($ch);

        if ($res === false) {
            // Fallback to straight line
            $wkt = "LINESTRING($fromLng $fromLat, $toLng $toLat)";
            return [true, [
                'wkt' => $wkt,
                'distance' => 1000,
                'duration' => 5
            ]];
        }

        $data = json_decode($res, true);
        if (empty($data['routes']) || empty($data['routes'][0]['geometry']['coordinates'])) {
            // Fallback to straight line
            $wkt = "LINESTRING($fromLng $fromLat, $toLng $toLat)";
            return [true, [
                'wkt' => $wkt,
                'distance' => 1000,
                'duration' => 5
            ]];
        }

        $routeInfo = $data['routes'][0];
        $coords = $routeInfo['geometry']['coordinates'];
        $distance = (int)$routeInfo['distance'];
        $duration = (int)ceil($routeInfo['duration'] / 60);

        $wktCoords = [];
        foreach ($coords as $coord) {
            $wktCoords[] = "{$coord[0]} {$coord[1]}";
        }
        $wkt = "LINESTRING(" . implode(", ", $wktCoords) . ")";

        return [true, [
            'wkt' => $wkt,
            'distance' => $distance,
            'duration' => $duration
        ]];
    }
}
