<?php

class AdminRouteController
{
    public static function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST', true, 405);
            exit;
        }

        $name          = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $shelter_id    = filter_var($_POST['shelter_id']    ?? null, FILTER_VALIDATE_INT);
        $from_latitude  = isset($_POST['from_latitude'])  && $_POST['from_latitude']  !== '' ? filter_var($_POST['from_latitude'],  FILTER_VALIDATE_FLOAT) : null;
        $from_longitude = isset($_POST['from_longitude']) && $_POST['from_longitude'] !== '' ? filter_var($_POST['from_longitude'], FILTER_VALIDATE_FLOAT) : null;
        $status        = $_POST['status'] ?? 'active';
        $notes         = trim(filter_var($_POST['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if ($name === '') {
            self::respond(['success' => false, 'error' => 'Route Name is required'], 400, true);
        }
        if (!$shelter_id) {
            self::respond(['success' => false, 'error' => 'Target Shelter is required'], 400, true);
        }
        if ($from_latitude === null || $from_longitude === null || $from_latitude === false || $from_longitude === false) {
            self::respond(['success' => false, 'error' => 'Start Latitude and Start Longitude are required and must be valid numbers'], 400, true);
        }

        [$ok, $res] = self::model()->create([
            'name'           => $name,
            'shelter_id'     => $shelter_id,
            'from_latitude'  => $from_latitude,
            'from_longitude' => $from_longitude,
            'status'         => $status,
            'notes'          => $notes,
        ]);

        if (!$ok) {
            self::respond(['success' => false, 'error' => $res], 500, true);
        }

        self::respond(['success' => true, 'id' => $res['id'] ?? null], 200, false);
    }

    public static function update(int $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $allowedStatus  = ['active', 'blocked', 'closed'];
        $name           = trim(filter_var($input['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $shelter_id     = filter_var($input['shelter_id']    ?? null, FILTER_VALIDATE_INT);
        $from_latitude  = isset($input['from_latitude'])  && $input['from_latitude']  !== '' ? filter_var($input['from_latitude'],  FILTER_VALIDATE_FLOAT) : null;
        $from_longitude = isset($input['from_longitude']) && $input['from_longitude'] !== '' ? filter_var($input['from_longitude'], FILTER_VALIDATE_FLOAT) : null;
        $status         = in_array($input['status'] ?? '', $allowedStatus, true) ? $input['status'] : 'active';
        $notes          = trim(filter_var($input['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Route Name is required']);
            exit;
        }
        if (!$shelter_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Target Shelter is required']);
            exit;
        }
        if ($from_latitude === null || $from_longitude === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Start Latitude and Start Longitude are required']);
            exit;
        }

        [$ok, $res] = self::model()->update($id, [
            'name'           => $name,
            'shelter_id'     => $shelter_id,
            'from_latitude'  => $from_latitude,
            'from_longitude' => $from_longitude,
            'status'         => $status,
            'notes'          => $notes,
        ]);

        http_response_code($ok ? 200 : 500);
        echo json_encode($ok ? ['success' => true] : ['error' => 'Update failure: ' . $res]);
    }

    public static function delete(int $id): void
    {
        [$ok, $res] = self::model()->delete($id);
        http_response_code($ok ? 200 : 500);
        echo json_encode($ok ? ['success' => true] : ['error' => 'Deletion failure: ' . $res]);
    }

    private static function model(): \ClientModels\EvacuationRoute
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return new \ClientModels\EvacuationRoute($protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api');
    }

    private static function respond(array $payload, int $status, bool $isError): void
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $wantsJson = stripos($accept, 'application/json') !== false || strtolower($xrw) === 'xmlhttprequest';

        if ($wantsJson) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload, JSON_UNESCAPED_SLASHES);
            exit;
        }

        if (!$isError) {
            header('Location: /admin?tab=routes&success_route=1');
            exit;
        }

        header('Location: /admin?tab=routes&error_route=' . urlencode($payload['error'] ?? 'Unknown error'));
        exit;
    }
}
