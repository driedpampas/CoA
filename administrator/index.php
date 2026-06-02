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

$pageSize = 5;
$eventsPage = max(1, (int)($_GET['events_page'] ?? 1));
$sheltersPage = max(1, (int)($_GET['shelters_page'] ?? 1));

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

function fetchPaginatedAdminRows($mysql, $countSql, $dataSql, $page, $pageSize, $types = '', $params = [])
{
    $bindStatementParams = function ($stmt, $types, &$params) {
        if ($types === '') {
            return true;
        }

        $args = [$types];
        foreach ($params as $index => &$value) {
            $args[$index + 1] = &$value;
        }
        unset($value);

        return $stmt->bind_param(...$args);
    };

    $total = 0;
    $countStmt = $mysql->prepare($countSql);
    if ($countStmt) {
        if ($types !== '' && !empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        if ($countStmt->execute() && ($result = $countStmt->get_result())) {
            $total = (int)($result->fetch_row()[0] ?? 0);
        }
    }

    $totalPages = max(1, (int)ceil($total / $pageSize));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $pageSize;

    $stmt = $mysql->prepare($dataSql);
    if (!$stmt) {
        return [[], $total, $totalPages, $page, false];
    }

    $bindTypes = $types . 'ii';
    $statementParams = array_merge($params, [$pageSize, $offset]);
    if (!$bindStatementParams($stmt, $bindTypes, $statementParams)) {
        return [[], $total, $totalPages, $page, false];
    }

    if (!$stmt->execute() || !($result = $stmt->get_result())) {
        return [[], $total, $totalPages, $page, false];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return [$rows, $total, $totalPages, $page, true];
}

//Fetch data
[$events, $eventCount, $eventTotalPages, $eventsPage, $eventQueryOk] = fetchPaginatedAdminRows(
    $mysql,
    "SELECT COUNT(*) FROM emergency_events",
    "SELECT id, event_type, title, description, severity, latitude, longitude, status
     FROM emergency_events
     ORDER BY created_at DESC, id DESC
     LIMIT ? OFFSET ?",
    $eventsPage,
    $pageSize
);

foreach ($events as &$row) {
    $row['event_type'] = htmlspecialchars($row['event_type'] ?? '');
    $row['title'] = htmlspecialchars($row['title'] ?? '');
    $row['description'] = htmlspecialchars($row['description'] ?? '');
    $row['severity'] = htmlspecialchars($row['severity'] ?? '');
    $row['status'] = htmlspecialchars($row['status'] ?? '');

    $row['latitude_val'] = $row['latitude'] !== null ? htmlspecialchars((string)$row['latitude']) : '';
    $row['longitude_val'] = $row['longitude'] !== null ? htmlspecialchars((string)$row['longitude']) : '';

    $row['latitude_disp'] = $row['latitude'] !== null ? $row['latitude_val'] : 'None';
    $row['longitude_disp'] = $row['longitude'] !== null ? $row['longitude_val'] : 'None';
}
unset($row);
$hasEvents = !empty($events);

[$shelters, $shelterCount, $shelterTotalPages, $sheltersPage, $shelterQueryOk] = fetchPaginatedAdminRows(
    $mysql,
    "SELECT COUNT(*) FROM shelters",
    "SELECT id, name, address, latitude, longitude, capacity, shelter_type, status, contact_phone, notes
     FROM shelters
     ORDER BY created_at DESC, id DESC
     LIMIT ? OFFSET ?",
    $sheltersPage,
    $pageSize
);

foreach ($shelters as &$row) {
    $row['name'] = htmlspecialchars($row['name'] ?? '');
    $row['address'] = htmlspecialchars($row['address'] ?? '');
    $row['shelter_type'] = htmlspecialchars($row['shelter_type'] ?? '');
    $row['status'] = htmlspecialchars($row['status'] ?? '');
    $row['capacity'] = htmlspecialchars((string)($row['capacity'] ?? 0));
    $row['latitude'] = htmlspecialchars((string)($row['latitude'] ?? ''));
    $row['longitude'] = htmlspecialchars((string)($row['longitude'] ?? ''));
    $row['contact_phone'] = htmlspecialchars($row['contact_phone'] ?? '');
    $row['notes'] = htmlspecialchars($row['notes'] ?? '');
}
unset($row);
$hasShelters = !empty($shelters);

include __DIR__ . '/../views/AdminView.php';
