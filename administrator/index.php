<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

//Redirecting to the login page if not logged in
if (!($_SESSION['isLoggedIn'] ?? false)) {
    header('Location: /login');
    exit;
}

//Update role without re-login cuz it pmo
if (($_SESSION['role'] ?? '') !== 'admin') {
    try {
        $userModel = new \Models\Account($mysql);
        $role = $userModel->getRole($_SESSION['username'] ?? '');
        $_SESSION['role'] = $role ?? 'user';
    } catch (\Throwable $e) {
        $_SESSION['role'] = $_SESSION['role'] ?? 'user';
    }
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden: admin access required.';
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

//Data preparations
$csrf = htmlspecialchars($_SESSION['csrf_token']);
$username = htmlspecialchars($_SESSION['username'] ?? '');

$successEvent = isset($_GET['success_event']);
$errorEvent = isset($_GET['error_event']) ? htmlspecialchars(urldecode($_GET['error_event'])) : '';
$successShelter = isset($_GET['success_shelter']);
$errorShelter = isset($_GET['error_shelter']) ? htmlspecialchars(urldecode($_GET['error_shelter'])) : '';

//dropdown options (it looks nice i think i hope lol)
$eventTypeOptions = [
    ['value' => 'earthquake', 'label' => 'Earthquake'],
    ['value' => 'flood', 'label' => 'Flood'],
    ['value' => 'fire', 'label' => 'Fire'],
    ['value' => 'storm', 'label' => 'Storm'],
    ['value' => 'other', 'label' => 'Other']
];

$severityOptions = [
    ['value' => 'low', 'label' => 'Low'],
    ['value' => 'moderate', 'label' => 'Moderate'],
    ['value' => 'high', 'label' => 'High'],
    ['value' => 'extreme', 'label' => 'Extreme']
];

$eventStatusOptions = [
    ['value' => 'active', 'label' => 'Active'],
    ['value' => 'resolved', 'label' => 'Resolved']
];

$shelterTypeOptions = [
    ['value' => 'community', 'label' => 'Community'],
    ['value' => 'school', 'label' => 'School'],
    ['value' => 'stadium', 'label' => 'Stadium'],
    ['value' => 'military', 'label' => 'Military'],
    ['value' => 'other', 'label' => 'Other']
];

$shelterStatusOptions = [
    ['value' => 'open', 'label' => 'Open'],
    ['value' => 'full', 'label' => 'Full'],
    ['value' => 'closed', 'label' => 'Closed']
];

//Fetch data
$events = [];
$eventQuery = $mysql->query("SELECT id, event_type, title, description, severity, latitude, longitude, status FROM emergency_events ORDER BY id DESC");
if ($eventQuery) {
    while ($row = $eventQuery->fetch_assoc()) {
        $row['event_type'] = htmlspecialchars($row['event_type'] ?? '');
        $row['title'] = htmlspecialchars($row['title'] ?? '');
        $row['description'] = htmlspecialchars($row['description'] ?? '');
        $row['severity'] = htmlspecialchars($row['severity'] ?? '');
        $row['status'] = htmlspecialchars($row['status'] ?? '');

        $row['latitude_val'] = $row['latitude'] !== null ? htmlspecialchars((string)$row['latitude']) : '';
        $row['longitude_val'] = $row['longitude'] !== null ? htmlspecialchars((string)$row['longitude']) : '';

        $row['latitude_disp'] = $row['latitude'] !== null ? $row['latitude_val'] : 'None';
        $row['longitude_disp'] = $row['longitude'] !== null ? $row['longitude_val'] : 'None';

        $events[] = $row;
    }
}
$hasEvents = !empty($events);

$shelters = [];
$shelterQuery = $mysql->query("SELECT id, name, address, latitude, longitude, capacity, shelter_type, status, contact_phone, notes FROM shelters ORDER BY id DESC");
if ($shelterQuery) {
    while ($row = $shelterQuery->fetch_assoc()) {
        $row['name'] = htmlspecialchars($row['name'] ?? '');
        $row['address'] = htmlspecialchars($row['address'] ?? '');
        $row['shelter_type'] = htmlspecialchars($row['shelter_type'] ?? '');
        $row['status'] = htmlspecialchars($row['status'] ?? '');
        $row['capacity'] = htmlspecialchars((string)($row['capacity'] ?? 0));
        $row['latitude'] = htmlspecialchars((string)($row['latitude'] ?? ''));
        $row['longitude'] = htmlspecialchars((string)($row['longitude'] ?? ''));
        $row['contact_phone'] = htmlspecialchars($row['contact_phone'] ?? '');
        $row['notes'] = htmlspecialchars($row['notes'] ?? '');

        $shelters[] = $row;
    }
}
$hasShelters = !empty($shelters);

include __DIR__ . '/../views/AdminView.php';
