<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function wantsJsonResponse(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    return stripos($accept, 'application/json') !== false || strtolower($requestedWith) === 'xmlhttprequest';
}

function respondAdminSubmit(array $payload, int $status, bool $success = false): void
{
    if (wantsJsonResponse()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($success) {
        header('Location: /admin?success_event=1');
        exit;
    }

    header('Location: /admin?error_event=' . urlencode($payload['error'] ?? 'Unknown error'));
    exit;
}

if (!($_SESSION['isLoggedIn'] ?? false) || ($_SESSION['role'] ?? '') !== 'admin') {
    respondAdminSubmit(['success' => false, 'error' => 'Forbidden'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST', true, 405);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    respondAdminSubmit(['success' => false, 'error' => 'Invalid CSRF token'], 400);
}

require_once __DIR__ . '/models/HttpClient.php';
require_once __DIR__ . '/models/Event.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

$eventModel = new \Models\Event($apiBaseUrl);

$allowedTypes = ['earthquake', 'flood', 'fire', 'storm', 'other'];
$allowedSev = ['low', 'moderate', 'high', 'extreme'];

$event_type = in_array($_POST['event_type'] ?? 'other', $allowedTypes, true) ? $_POST['event_type'] : 'other';
$title = trim(filter_var($_POST['title'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$description = trim(filter_var($_POST['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$severity = in_array($_POST['severity'] ?? 'moderate', $allowedSev, true) ? $_POST['severity'] : 'moderate';

$latitudeProvided = array_key_exists('latitude', $_POST) && $_POST['latitude'] !== '';
$longitudeProvided = array_key_exists('longitude', $_POST) && $_POST['longitude'] !== '';
$latitude = $latitudeProvided ? filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT) : null;
$longitude = $longitudeProvided ? filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT) : null;

if ($title === '') {
    respondAdminSubmit(['success' => false, 'error' => 'Title is required'], 400);
}

if (($latitudeProvided xor $longitudeProvided)) {
    respondAdminSubmit(['success' => false, 'error' => 'Latitude and longitude must be provided together'], 400);
}

if (($latitudeProvided && $latitude === false) || ($longitudeProvided && $longitude === false)) {
    respondAdminSubmit(['success' => false, 'error' => 'Invalid latitude or longitude'], 400);
}

$data = [
    'event_type' => $event_type,
    'title' => $title,
    'description' => $description,
    'severity' => $severity,
    'latitude' => $latitude,
    'longitude' => $longitude,
];

[$ok, $res] = $eventModel->create($data);

if (!$ok) {
    respondAdminSubmit(['success' => false, 'error' => $res], 500);
}

respondAdminSubmit(['success' => true, 'id' => $res['id'] ?? null], 200, true);
