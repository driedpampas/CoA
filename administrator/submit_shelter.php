<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!($_SESSION['isLoggedIn'] ?? false) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST', true, 405);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    header('Location: /admin?error_shelter=' . urlencode('Invalid CSRF token'));
    exit;
}

$name = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$address = trim(filter_var($_POST['address'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$latitude = isset($_POST['latitude']) ? filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT) : false;
$longitude = isset($_POST['longitude']) ? filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT) : false;
$capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? filter_var($_POST['capacity'], FILTER_VALIDATE_INT) : 0;
$allowedTypes = ['stadium','school','military','community','other'];
$shelter_type = in_array($_POST['shelter_type'] ?? 'community', $allowedTypes, true) ? $_POST['shelter_type'] : 'community';
$contact_phone = trim(filter_var($_POST['contact_phone'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$notes = trim(filter_var($_POST['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

if ($name === '' || $address === '' || $latitude === false || $longitude === false) {
    header('Location: /admin?error_shelter=' . urlencode('Missing required fields'));
    exit;
}

$capacity = ($capacity === false) ? 0 : max(0, $capacity);

$stmt = $mysql->prepare("INSERT INTO shelters (name, address, latitude, longitude, geom_point, capacity, current_occupancy, shelter_type, status, contact_phone, notes) VALUES (?, ?, ?, ?, ST_GeomFromText(?, 4326), ?, 0, ?, 'open', ?, ?)");
if (!$stmt) {
    header('Location: /admin?error_shelter=' . urlencode('DB prepare failed'));
    exit;
}

$point = sprintf('POINT(%F %F)', $longitude, $latitude);

$stmt->bind_param('ssddsisss', $name, $address, $latitude, $longitude, $point, $capacity, $shelter_type, $contact_phone, $notes);

$ok = $stmt->execute();
$error = $stmt->error ?? '';
$stmt->close();

if (!$ok) {
    header('Location: /admin?error_shelter=' . urlencode('DB insert failed: ' . $error));
    exit;
}

header('Location: /admin?success_shelter=1');
exit;
