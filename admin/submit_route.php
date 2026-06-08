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
        header('Location: /admin?tab=routes&success_route=1');
        exit;
    }

    header('Location: /admin?tab=routes&error_route=' . urlencode($payload['error'] ?? 'Unknown error'));
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
require_once __DIR__ . '/models/EvacuationRoute.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

$routeModel = new \Models\EvacuationRoute($apiBaseUrl);

$name = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$shelter_id = filter_var($_POST['shelter_id'] ?? null, FILTER_VALIDATE_INT);
$from_latitude = isset($_POST['from_latitude']) && $_POST['from_latitude'] !== '' ? filter_var($_POST['from_latitude'], FILTER_VALIDATE_FLOAT) : null;
$from_longitude = isset($_POST['from_longitude']) && $_POST['from_longitude'] !== '' ? filter_var($_POST['from_longitude'], FILTER_VALIDATE_FLOAT) : null;
$status = $_POST['status'] ?? 'active';
$notes = trim(filter_var($_POST['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

if (empty($name)) {
    respondAdminSubmit(['success' => false, 'error' => 'Route Name is required'], 400);
}

if (!$shelter_id) {
    respondAdminSubmit(['success' => false, 'error' => 'Target Shelter is required'], 400);
}

if ($from_latitude === null || $from_longitude === null || $from_latitude === false || $from_longitude === false) {
    respondAdminSubmit(['success' => false, 'error' => 'Start Latitude and Start Longitude are required and must be valid numbers'], 400);
}

$data = [
    'name' => $name,
    'shelter_id' => $shelter_id,
    'from_latitude' => $from_latitude,
    'from_longitude' => $from_longitude,
    'status' => $status,
    'notes' => $notes
];

[$ok, $res] = $routeModel->create($data);

if (!$ok) {
    respondAdminSubmit(['success' => false, 'error' => $res], 500);
}

respondAdminSubmit(['success' => true, 'id' => $res['id'] ?? null], 200, true);
