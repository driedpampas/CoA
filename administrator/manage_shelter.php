<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!($_SESSION['isLoggedIn'] ?? false) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

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
    echo json_encode(['error' => 'Valid ID required']);
    exit;
}

if ($method === 'DELETE') {
    $stmt = $mysql->prepare("DELETE FROM shelters WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database deletion failure']);
    }
    $stmt->close();
    exit;
}

if ($method === 'PATCH' || $method === 'PUT') {
    $name = trim(filter_var($input['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $address = trim(filter_var($input['address'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $latitude = isset($input['latitude']) ? filter_var($input['latitude'], FILTER_VALIDATE_FLOAT) : false;
    $longitude = isset($input['longitude']) ? filter_var($input['longitude'], FILTER_VALIDATE_FLOAT) : false;
    $capacity = isset($input['capacity']) && $input['capacity'] !== '' ? filter_var($input['capacity'], FILTER_VALIDATE_INT) : 0;
    $allowedTypes = ['stadium','school','military','community','other'];
    $allowedStatus = ['open', 'full', 'closed'];
    $shelter_type = in_array($input['shelter_type'] ?? 'community', $allowedTypes, true) ? $input['shelter_type'] : 'community';
    $status = in_array($input['status'] ?? 'open', $allowedStatus, true) ? $input['status'] : 'open';
    $contact_phone = trim(filter_var($input['contact_phone'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $notes = trim(filter_var($input['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    if ($name === '' || $address === '' || $latitude === false || $longitude === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    $capacity = ($capacity === false) ? 0 : max(0, $capacity);

    $point = sprintf('POINT(%F %F)', $longitude, $latitude);
    $stmt = $mysql->prepare("UPDATE shelters SET name=?, address=?, latitude=?, longitude=?, geom_point=ST_GeomFromText(?, 4326), capacity=?, shelter_type=?, status=?, contact_phone=?, notes=? WHERE id=?");
    $stmt->bind_param('ssddsissssi', $name, $address, $latitude, $longitude, $point, $capacity, $shelter_type, $status, $contact_phone, $notes, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database update failure']);
    }
    $stmt->close();
    exit;
}

header('HTTP/1.1 405 Method Not Allowed');
