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
    header('Location: /admin?error_event=' . urlencode('Invalid CSRF token'));
    exit;
}

$allowedTypes = ['earthquake','flood','fire','storm','other'];
$allowedSev = ['low','moderate','high','extreme'];
$event_type = in_array($_POST['event_type'] ?? '', $allowedTypes, true) ? $_POST['event_type'] : 'other';
$title = trim(filter_var($_POST['title'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$description = trim(filter_var($_POST['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$severity = in_array($_POST['severity'] ?? '', $allowedSev, true) ? $_POST['severity'] : 'moderate';

$latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT) : null;
$longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT) : null;

if ($title === '') {
    header('Location: /admin?error_event=' . urlencode('Title is required'));
    exit;
}

if (($latitude !== null && $latitude === false) || ($longitude !== null && $longitude === false)) {
    header('Location: /admin?error_event=' . urlencode('Invalid latitude or longitude'));
    exit;
}

if ($latitude === null || $longitude === null) {
    $stmt = $mysql->prepare("INSERT INTO emergency_events (event_type, title, description, severity, latitude, longitude) VALUES (?, ?, ?, ?, NULL, NULL)");
    if (!$stmt) {
        header('Location: /admin?error_event=' . urlencode('DB prepare failed'));
        exit;
    }
    $stmt->bind_param('ssss', $event_type, $title, $description, $severity);
} else {
    $stmt = $mysql->prepare("INSERT INTO emergency_events (event_type, title, description, severity, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        header('Location: /admin?error_event=' . urlencode('DB prepare failed'));
        exit;
    }
    $stmt->bind_param('ssssdd', $event_type, $title, $description, $severity, $latitude, $longitude);
}

$ok = $stmt->execute();
$error = $stmt->error ?? '';
$stmt->close();

if (!$ok) {
    header('Location: /admin?error_event=' . urlencode('DB insert failed: ' . $error));
    exit;
}

header('Location: /admin?success_event=1');
exit;
