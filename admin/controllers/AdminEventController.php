<?php

require_once __DIR__ . '/../../public/models/HttpClient.php';
require_once __DIR__ . '/../../public/models/Event.php';

class AdminEventController
{
    public static function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST', true, 405);
            exit;
        }

        $eventModel = self::model();

        $allowedTypes = ['earthquake', 'flood', 'fire', 'storm', 'other'];
        $allowedSev   = ['low', 'moderate', 'high', 'extreme'];

        $event_type  = in_array($_POST['event_type'] ?? 'other', $allowedTypes, true) ? $_POST['event_type'] : 'other';
        $title       = trim(filter_var($_POST['title']       ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $description = trim(filter_var($_POST['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $severity    = in_array($_POST['severity'] ?? 'moderate', $allowedSev, true) ? $_POST['severity'] : 'moderate';

        $latitudeProvided  = array_key_exists('latitude',  $_POST) && $_POST['latitude']  !== '';
        $longitudeProvided = array_key_exists('longitude', $_POST) && $_POST['longitude'] !== '';
        $latitude  = $latitudeProvided  ? filter_var($_POST['latitude'],  FILTER_VALIDATE_FLOAT) : null;
        $longitude = $longitudeProvided ? filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT) : null;

        if ($title === '') {
            self::respond(['success' => false, 'error' => 'Title is required'], 400, 'event');
        }
        if ($latitudeProvided xor $longitudeProvided) {
            self::respond(['success' => false, 'error' => 'Latitude and longitude must be provided together'], 400, 'event');
        }
        if (($latitudeProvided && $latitude === false) || ($longitudeProvided && $longitude === false)) {
            self::respond(['success' => false, 'error' => 'Invalid latitude or longitude'], 400, 'event');
        }

        [$ok, $res] = $eventModel->create([
            'event_type'  => $event_type,
            'title'       => $title,
            'description' => $description,
            'severity'    => $severity,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
        ]);

        if (!$ok) {
            self::respond(['success' => false, 'error' => $res], 500, 'event');
        }

        self::respond(['success' => true, 'id' => $res['id'] ?? null], 200, 'event', true);
    }

    public static function update(int $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $allowedTypes  = ['earthquake', 'flood', 'fire', 'storm', 'other'];
        $allowedSev    = ['low', 'moderate', 'high', 'extreme'];
        $allowedStatus = ['active', 'resolved'];

        $event_type  = in_array($input['event_type'] ?? '', $allowedTypes,  true) ? $input['event_type'] : 'other';
        $title       = trim(filter_var($input['title']       ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $description = trim(filter_var($input['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $severity    = in_array($input['severity'] ?? '', $allowedSev,    true) ? $input['severity'] : 'moderate';
        $status      = in_array($input['status']   ?? '', $allowedStatus, true) ? $input['status']   : 'active';
        $latitude    = isset($input['latitude'])  && $input['latitude']  !== '' ? filter_var($input['latitude'],  FILTER_VALIDATE_FLOAT) : null;
        $longitude   = isset($input['longitude']) && $input['longitude'] !== '' ? filter_var($input['longitude'], FILTER_VALIDATE_FLOAT) : null;

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Title is required']);
            exit;
        }

        [$ok, $res] = self::model()->update($id, [
            'event_type'  => $event_type,
            'title'       => $title,
            'description' => $description,
            'severity'    => $severity,
            'status'      => $status,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
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

    private static function model(): \Models\Event
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return new \Models\Event($protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api');
    }

    private static function respond(array $payload, int $status, string $entity, bool $success = false): void
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

        if ($success) {
            header('Location: /admin?tab=' . $entity . 's&success_' . $entity . '=1');
            exit;
        }

        header('Location: /admin?tab=' . $entity . 's&error_' . $entity . '=' . urlencode($payload['error'] ?? 'Unknown error'));
        exit;
    }
}
