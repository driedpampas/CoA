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
require_once __DIR__ . '/models/Event.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

$eventModel = new \Models\Event($apiBaseUrl);

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
    [$ok, $res] = $eventModel->delete($id);
    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database deletion failure: ' . $res]);
    }
    exit;
}

if ($method === 'PATCH') {
    $allowedTypes = ['earthquake','flood','fire','storm','other'];
    $allowedSev = ['low','moderate','high','extreme'];
    $allowedStatus = ['active', 'resolved'];

    $event_type = in_array($input['event_type'] ?? '', $allowedTypes, true) ? $input['event_type'] : 'other';
    $title = trim(filter_var($input['title'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $description = trim(filter_var($input['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $severity = in_array($input['severity'] ?? '', $allowedSev, true) ? $input['severity'] : 'moderate';
    $status = in_array($input['status'] ?? '', $allowedStatus, true) ? $input['status'] : 'active';
    $latitude = isset($input['latitude']) && $input['latitude'] !== '' ? filter_var($input['latitude'], FILTER_VALIDATE_FLOAT) : null;
    $longitude = isset($input['longitude']) && $input['longitude'] !== '' ? filter_var($input['longitude'], FILTER_VALIDATE_FLOAT) : null;

    if ($title === '') {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Title is required']);
        exit;
    }

    $data = [
        'event_type' => $event_type,
        'title' => $title,
        'description' => $description,
        'severity' => $severity,
        'status' => $status,
        'latitude' => $latitude,
        'longitude' => $longitude,
    ];

    [$ok, $res] = $eventModel->update($id, $data);

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
