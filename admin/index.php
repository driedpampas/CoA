<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizePageSize($value, array $allowed, int $default): int
{
    $value = filter_var($value, FILTER_VALIDATE_INT);
    if (!$value || !in_array($value, $allowed, true)) {
        return $default;
    }

    return $value;
}

function escapeLikeTerm(string $term): string
{
    return '%' . strtr($term, [
        '\\' => '\\\\',
        '%' => '\\%',
        '_' => '\\_',
    ]) . '%';
}

function buildSearchFilter(array $columns, string $term): array
{
    $term = trim($term);
    if ($term === '') {
        return ['', [], ''];
    }

    $clauses = [];
    foreach ($columns as $column) {
        $clauses[] = "{$column} LIKE ? ESCAPE '\\\\'";
    }

    $params = array_fill(0, count($columns), escapeLikeTerm($term));
    $types = str_repeat('s', count($columns));

    return [' WHERE (' . implode(' OR ', $clauses) . ')', $params, $types];
}

function resolveSort(string $requestedField, string $requestedDir, array $allowedSorts, string $defaultField): array
{
    if (!array_key_exists($requestedField, $allowedSorts)) {
        $requestedField = $defaultField;
    }

    $direction = strtolower($requestedDir) === 'asc' ? 'ASC' : 'DESC';

    return [$requestedField, $direction, $allowedSorts[$requestedField]];
}

function formatTimestamp($value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $time = strtotime((string) $value);
    if ($time === false) {
        return e($value);
    }

    return e(date('Y-m-d H:i:s', $time));
}

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
            $total = (int) ($result->fetch_row()[0] ?? 0);
        }
        $countStmt->close();
    }

    $totalPages = max(1, (int) ceil($total / $pageSize));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $pageSize;

    $stmt = $mysql->prepare($dataSql);
    if (!$stmt) {
        return [[], $total, $totalPages, $page, false];
    }

    $bindTypes = $types . 'ii';
    $statementParams = array_merge($params, [$pageSize, $offset]);
    if (!$bindStatementParams($stmt, $bindTypes, $statementParams)) {
        $stmt->close();
        return [[], $total, $totalPages, $page, false];
    }

    if (!$stmt->execute() || !($result = $stmt->get_result())) {
        $stmt->close();
        return [[], $total, $totalPages, $page, false];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    return [$rows, $total, $totalPages, $page, true];
}

if (!($_SESSION['isLoggedIn'] ?? false)) {
    header('Location: /login');
    exit;
}

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

$csrf = e($_SESSION['csrf_token']);
$username = e($_SESSION['username'] ?? '');

$successEvent = isset($_GET['success_event']);
$errorEvent = isset($_GET['error_event']) ? e(urldecode((string) $_GET['error_event'])) : '';
$successShelter = isset($_GET['success_shelter']);
$errorShelter = isset($_GET['error_shelter']) ? e(urldecode((string) $_GET['error_shelter'])) : '';

$activeTab = ($_GET['tab'] ?? 'events') === 'shelters' ? 'shelters' : 'events';
$pageSizes = [5, 10, 25, 50, 100];
$defaultPageSize = 10;

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

$eventsPageSize = normalizePageSize($_GET['events_size'] ?? $defaultPageSize, $pageSizes, $defaultPageSize);
$sheltersPageSize = normalizePageSize($_GET['shelters_size'] ?? $defaultPageSize, $pageSizes, $defaultPageSize);

$eventsSearch = trim((string) ($_GET['events_q'] ?? ''));
$sheltersSearch = trim((string) ($_GET['shelters_q'] ?? ''));

$eventsPage = max(1, (int) ($_GET['events_page'] ?? 1));
$sheltersPage = max(1, (int) ($_GET['shelters_page'] ?? 1));

$eventSortMap = [
    'type' => 'event_type',
    'title' => 'title',
    'description' => 'description',
    'severity' => 'severity',
    'status' => 'status',
    'latitude' => 'latitude',
    'longitude' => 'longitude',
    'created' => 'created_at',
    'updated' => 'updated_at',
];

$shelterSortMap = [
    'name' => 'name',
    'address' => 'address',
    'type' => 'shelter_type',
    'capacity' => 'capacity',
    'status' => 'status',
    'phone' => 'contact_phone',
    'notes' => 'notes',
    'created' => 'created_at',
    'updated' => 'updated_at',
];

[$eventsSort, $eventsSortDir, $eventsSortColumn] = resolveSort(
    (string) ($_GET['events_sort'] ?? 'created'),
    (string) ($_GET['events_dir'] ?? 'desc'),
    $eventSortMap,
    'created'
);

[$sheltersSort, $sheltersSortDir, $sheltersSortColumn] = resolveSort(
    (string) ($_GET['shelters_sort'] ?? 'created'),
    (string) ($_GET['shelters_dir'] ?? 'desc'),
    $shelterSortMap,
    'created'
);

[$eventWhereSql, $eventParams, $eventTypes] = buildSearchFilter(['title', 'description'], $eventsSearch);
[$shelterWhereSql, $shelterParams, $shelterTypes] = buildSearchFilter(['name', 'address'], $sheltersSearch);

[$events, $eventCount, $eventTotalPages, $eventsPage, $eventQueryOk] = fetchPaginatedAdminRows(
    $mysql,
    "SELECT COUNT(*) FROM emergency_events{$eventWhereSql}",
    "SELECT id, event_type, title, description, severity, latitude, longitude, status, created_at, updated_at
     FROM emergency_events{$eventWhereSql}
     ORDER BY {$eventsSortColumn} {$eventsSortDir}, id DESC
     LIMIT ? OFFSET ?",
    $eventsPage,
    $eventsPageSize,
    $eventTypes,
    $eventParams
);

foreach ($events as &$row) {
    $row['event_type'] = e($row['event_type'] ?? '');
    $row['title'] = e($row['title'] ?? '');
    $row['description'] = e($row['description'] ?? '');
    $row['severity'] = e($row['severity'] ?? '');
    $row['status'] = e($row['status'] ?? '');
    $row['created_at'] = formatTimestamp($row['created_at'] ?? '');
    $row['updated_at'] = formatTimestamp($row['updated_at'] ?? '');
    $row['latitude_val'] = $row['latitude'] !== null ? e((string) $row['latitude']) : '';
    $row['longitude_val'] = $row['longitude'] !== null ? e((string) $row['longitude']) : '';
    $row['latitude_disp'] = $row['latitude'] !== null ? $row['latitude_val'] : 'None';
    $row['longitude_disp'] = $row['longitude'] !== null ? $row['longitude_val'] : 'None';
}
unset($row);
$hasEvents = !empty($events);

[$shelters, $shelterCount, $shelterTotalPages, $sheltersPage, $shelterQueryOk] = fetchPaginatedAdminRows(
    $mysql,
    "SELECT COUNT(*) FROM shelters{$shelterWhereSql}",
    "SELECT id, name, address, latitude, longitude, capacity, shelter_type, status, contact_phone, notes, created_at, updated_at
     FROM shelters{$shelterWhereSql}
     ORDER BY {$sheltersSortColumn} {$sheltersSortDir}, id DESC
     LIMIT ? OFFSET ?",
    $sheltersPage,
    $sheltersPageSize,
    $shelterTypes,
    $shelterParams
);

foreach ($shelters as &$row) {
    $row['name'] = e($row['name'] ?? '');
    $row['address'] = e($row['address'] ?? '');
    $row['shelter_type'] = e($row['shelter_type'] ?? '');
    $row['status'] = e($row['status'] ?? '');
    $row['capacity'] = e((string) ($row['capacity'] ?? 0));
    $row['latitude'] = e((string) ($row['latitude'] ?? ''));
    $row['longitude'] = e((string) ($row['longitude'] ?? ''));
    $row['contact_phone'] = e($row['contact_phone'] ?? '');
    $row['notes'] = e($row['notes'] ?? '');
    $row['created_at'] = formatTimestamp($row['created_at'] ?? '');
    $row['updated_at'] = formatTimestamp($row['updated_at'] ?? '');
}
unset($row);
$hasShelters = !empty($shelters);

$adminQueryBase = [
    'tab' => $activeTab,
    'events_page' => $eventsPage,
    'shelters_page' => $sheltersPage,
    'events_size' => $eventsPageSize,
    'shelters_size' => $sheltersPageSize,
    'events_q' => $eventsSearch,
    'shelters_q' => $sheltersSearch,
    'events_sort' => $eventsSort,
    'events_dir' => $eventsSortDir,
    'shelters_sort' => $sheltersSort,
    'shelters_dir' => $sheltersSortDir,
];

function adminUrl(array $overrides = []): string
{
    global $adminQueryBase;

    return '/admin?' . http_build_query(array_merge($adminQueryBase, $overrides));
}

include __DIR__ . '/views/AdminView.php';
