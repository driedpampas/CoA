<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!($_SESSION['isLoggedIn'] ?? false) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/models/HttpClient.php';
require_once __DIR__ . '/models/EvacuationRoute.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

$routeModel = new \Models\EvacuationRoute($apiBaseUrl);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Valid record ID is required']);
    exit;
}

if ($method === 'DELETE') {
    [$ok, $res] = $routeModel->delete($id);
    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database deletion failure: ' . $res]);
    }
    exit;
}

if ($method === 'PATCH' || $method === 'PUT') {
    $allowedStatus = ['active', 'blocked', 'closed'];

    $name = trim(filter_var($input['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $shelter_id = filter_var($input['shelter_id'] ?? null, FILTER_VALIDATE_INT);
    $from_latitude = isset($input['from_latitude']) && $input['from_latitude'] !== '' ? filter_var($input['from_latitude'], FILTER_VALIDATE_FLOAT) : null;
    $from_longitude = isset($input['from_longitude']) && $input['from_longitude'] !== '' ? filter_var($input['from_longitude'], FILTER_VALIDATE_FLOAT) : null;
    $status = in_array($input['status'] ?? '', $allowedStatus, true) ? $input['status'] : 'active';
    $notes = trim(filter_var($input['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    if ($name === '') {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Route Name is required']);
        exit;
    }

    if (!$shelter_id) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Target Shelter is required']);
        exit;
    }

    if ($from_latitude === null || $from_longitude === null) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Start Latitude and Start Longitude are required']);
        exit;
    }

    $data = [
        'name' => $name,
        'shelter_id' => $shelter_id,
        'from_latitude' => $from_latitude,
        'from_longitude' => $from_longitude,
        'status' => $status,
        'notes' => $notes
    ];

    [$ok, $res] = $routeModel->update($id, $data);

    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database update failure: ' . $res]);
    }
    exit;
}

header('HTTP/1.1 405 Method Not Allowed');
header('Allow: PATCH, PUT, DELETE');
