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
        header('Location: /admin?success_shelter=1');
        exit;
    }

    header('Location: /admin?error_shelter=' . urlencode($payload['error'] ?? 'Unknown error'));
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
require_once __DIR__ . '/models/Shelter.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

$shelterModel = new \Models\Shelter($apiBaseUrl);

$name = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$address = trim(filter_var($_POST['address'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$latitudeRaw = $_POST['latitude'] ?? '';
$longitudeRaw = $_POST['longitude'] ?? '';
$latitude = $latitudeRaw !== '' ? filter_var($latitudeRaw, FILTER_VALIDATE_FLOAT) : false;
$longitude = $longitudeRaw !== '' ? filter_var($longitudeRaw, FILTER_VALIDATE_FLOAT) : false;

$capacityRaw = $_POST['capacity'] ?? '';
if ($capacityRaw === '') {
    $capacity = 0;
} else {
    $capacity = filter_var($capacityRaw, FILTER_VALIDATE_INT);
    if ($capacity === false) {
        respondAdminSubmit(['success' => false, 'error' => 'Capacity must be a whole number'], 400);
    }
}

$allowedTypes = ['stadium', 'school', 'military', 'community', 'other'];
$shelter_type = in_array($_POST['shelter_type'] ?? 'community', $allowedTypes, true) ? $_POST['shelter_type'] : 'community';
$contact_phone = trim(filter_var($_POST['contact_phone'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$notes = trim(filter_var($_POST['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

if ($name === '' || $address === '' || $latitude === false || $longitude === false) {
    respondAdminSubmit(['success' => false, 'error' => 'Missing required fields'], 400);
}

$data = [
    'name' => $name,
    'address' => $address,
    'latitude' => $latitude,
    'longitude' => $longitude,
    'capacity' => $capacity,
    'shelter_type' => $shelter_type,
    'contact_phone' => $contact_phone,
    'notes' => $notes,
];

[$ok, $res] = $shelterModel->create($data);

if (!$ok) {
    respondAdminSubmit(['success' => false, 'error' => $res], 500);
}

respondAdminSubmit(['success' => true], 200, true);
