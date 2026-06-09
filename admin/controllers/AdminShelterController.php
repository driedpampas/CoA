<?php

class AdminShelterController
{
    public static function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST', true, 405);
            exit;
        }

        $name         = trim(filter_var($_POST['name']    ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $address      = trim(filter_var($_POST['address'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $latitudeRaw  = $_POST['latitude']  ?? '';
        $longitudeRaw = $_POST['longitude'] ?? '';
        $latitude     = $latitudeRaw  !== '' ? filter_var($latitudeRaw,  FILTER_VALIDATE_FLOAT) : false;
        $longitude    = $longitudeRaw !== '' ? filter_var($longitudeRaw, FILTER_VALIDATE_FLOAT) : false;

        $capacityRaw = $_POST['capacity'] ?? '';
        $capacity = $capacityRaw === '' ? 0 : filter_var($capacityRaw, FILTER_VALIDATE_INT);
        if ($capacity === false) {
            self::respond(['success' => false, 'error' => 'Capacity must be a whole number'], 400, true);
        }

        $allowedTypes = ['stadium', 'school', 'military', 'community', 'other'];
        $shelter_type  = in_array($_POST['shelter_type'] ?? 'community', $allowedTypes, true) ? $_POST['shelter_type'] : 'community';
        $contact_phone = trim(filter_var($_POST['contact_phone'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $notes         = trim(filter_var($_POST['notes']         ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if ($name === '' || $address === '' || $latitude === false || $longitude === false) {
            self::respond(['success' => false, 'error' => 'Missing required fields'], 400, true);
        }

        [$ok, $res] = self::model()->create([
            'name'          => $name,
            'address'       => $address,
            'latitude'      => $latitude,
            'longitude'     => $longitude,
            'capacity'      => $capacity,
            'shelter_type'  => $shelter_type,
            'contact_phone' => $contact_phone,
            'notes'         => $notes,
        ]);

        if (!$ok) {
            self::respond(['success' => false, 'error' => $res], 500, true);
        }

        self::respond(['success' => true], 200, false);
    }

    public static function update(int $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $name         = trim(filter_var($input['name']    ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $address      = trim(filter_var($input['address'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $latitude     = isset($input['latitude'])  ? filter_var($input['latitude'],  FILTER_VALIDATE_FLOAT) : false;
        $longitude    = isset($input['longitude']) ? filter_var($input['longitude'], FILTER_VALIDATE_FLOAT) : false;
        $capacity     = isset($input['capacity']) && $input['capacity'] !== '' ? filter_var($input['capacity'], FILTER_VALIDATE_INT) : 0;
        $allowedTypes  = ['stadium', 'school', 'military', 'community', 'other'];
        $allowedStatus = ['open', 'full', 'closed'];
        $shelter_type  = in_array($input['shelter_type'] ?? 'community', $allowedTypes,  true) ? $input['shelter_type'] : 'community';
        $status        = in_array($input['status']       ?? 'open',      $allowedStatus, true) ? $input['status']       : 'open';
        $contact_phone = trim(filter_var($input['contact_phone'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $notes         = trim(filter_var($input['notes']         ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if ($name === '' || $address === '' || $latitude === false || $longitude === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        [$ok, $res] = self::model()->update($id, [
            'name'          => $name,
            'address'       => $address,
            'latitude'      => $latitude,
            'longitude'     => $longitude,
            'capacity'      => $capacity === false ? 0 : max(0, $capacity),
            'shelter_type'  => $shelter_type,
            'status'        => $status,
            'contact_phone' => $contact_phone,
            'notes'         => $notes,
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

    private static function model(): \ClientModels\Shelter
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return new \ClientModels\Shelter($protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api');
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
            header('Location: /admin?tab=shelters&success_shelter=1');
            exit;
        }

        header('Location: /admin?tab=shelters&error_shelter=' . urlencode($payload['error'] ?? 'Unknown error'));
        exit;
    }
}
