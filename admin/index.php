<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/models/HttpClient.php';
require_once __DIR__ . '/models/Event.php';
require_once __DIR__ . '/models/Shelter.php';
require_once __DIR__ . '/models/EvacuationRoute.php';
require_once __DIR__ . '/models/Account.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

$eventModel = new \Models\Event($apiBaseUrl);
$shelterModel = new \Models\Shelter($apiBaseUrl);
$routeModel = new \Models\EvacuationRoute($apiBaseUrl);
$userModel = new \Models\Account($apiBaseUrl);

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

// Decoupled from direct database access

if (!($_SESSION['isLoggedIn'] ?? false)) {
    header('Location: /login');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    try {
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
$successRoute = isset($_GET['success_route']);
$errorRoute = isset($_GET['error_route']) ? e(urldecode((string) $_GET['error_route'])) : '';

$allowedTabs = ['events', 'shelters', 'routes'];
if (!isset($_GET['tab']) || !in_array($_GET['tab'], $allowedTabs, true)) {
    header('Location: /admin?tab=events');
    exit;
}
$activeTab = $_GET['tab'];
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
$routesPageSize = normalizePageSize($_GET['routes_size'] ?? $defaultPageSize, $pageSizes, $defaultPageSize);

$eventsSearch = trim((string) ($_GET['events_q'] ?? ''));
$sheltersSearch = trim((string) ($_GET['shelters_q'] ?? ''));
$routesSearch = trim((string) ($_GET['routes_q'] ?? ''));

$eventsPage = max(1, (int) ($_GET['events_page'] ?? 1));
$sheltersPage = max(1, (int) ($_GET['shelters_page'] ?? 1));
$routesPage = max(1, (int) ($_GET['routes_page'] ?? 1));

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

$routeSortMap = [
    'name' => 'name',
    'shelter' => 'shelter',
    'distance' => 'distance',
    'duration' => 'duration',
    'status' => 'status',
    'created' => 'created',
    'updated' => 'updated',
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

[$routesSort, $routesSortDir, $routesSortColumn] = resolveSort(
    (string) ($_GET['routes_sort'] ?? 'created'),
    (string) ($_GET['routes_dir'] ?? 'desc'),
    $routeSortMap,
    'created'
);

[$okE, $eventData] = $eventModel->getPaginated($eventsPage, $eventsPageSize, $eventsSearch, $eventsSort, $eventsSortDir);
$eventQueryOk = $okE;
$events = $okE ? ($eventData['rows'] ?? []) : [];
$eventCount = $okE ? ($eventData['total'] ?? 0) : 0;
$eventTotalPages = $okE ? ($eventData['totalPages'] ?? 1) : 1;
$eventsPage = $okE ? ($eventData['page'] ?? 1) : 1;

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

[$okS, $shelterData] = $shelterModel->getPaginated($sheltersPage, $sheltersPageSize, $sheltersSearch, $sheltersSort, $sheltersSortDir);
$shelterQueryOk = $okS;
$shelters = $okS ? ($shelterData['rows'] ?? []) : [];
$shelterCount = $okS ? ($shelterData['total'] ?? 0) : 0;
$shelterTotalPages = $okS ? ($shelterData['totalPages'] ?? 1) : 1;
$sheltersPage = $okS ? ($shelterData['page'] ?? 1) : 1;

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

[$okR, $routeData] = $routeModel->getPaginated($routesPage, $routesPageSize, $routesSearch, $routesSort, $routesSortDir);
$routeQueryOk = $okR;
$routes = $okR ? ($routeData['rows'] ?? []) : [];
$routeCount = $okR ? ($routeData['total'] ?? 0) : 0;
$routeTotalPages = $okR ? ($routeData['totalPages'] ?? 1) : 1;
$routesPage = $okR ? ($routeData['page'] ?? 1) : 1;

foreach ($routes as &$row) {
    $row['name'] = e($row['name'] ?? '');
    $row['shelter_id'] = (int)($row['shelter_id'] ?? 0);
    $row['shelter_name'] = e($row['shelter_name'] ?? '');
    $row['from_latitude'] = e((string)($row['from_latitude'] ?? ''));
    $row['from_longitude'] = e((string)($row['from_longitude'] ?? ''));
    $row['distance_meters'] = e((string)($row['distance_meters'] ?? ''));
    $row['estimated_minutes'] = e((string)($row['estimated_minutes'] ?? ''));
    $row['status'] = e($row['status'] ?? '');
    $row['notes'] = e($row['notes'] ?? '');
    $row['created_at'] = formatTimestamp($row['created_at'] ?? '');
    $row['updated_at'] = formatTimestamp($row['updated_at'] ?? '');
}
unset($row);
$hasRoutes = !empty($routes);

[$okAllShelters, $allSheltersData] = $shelterModel->getAll();
$sheltersForDropdown = $okAllShelters ? $allSheltersData : [];
foreach ($sheltersForDropdown as &$row) {
    $row['id'] = (int)($row['id'] ?? 0);
    $row['name'] = e($row['name'] ?? '');
}
unset($row);

$adminQueryBase = [
    'tab' => $activeTab,
    'events_page' => $eventsPage,
    'shelters_page' => $sheltersPage,
    'routes_page' => $routesPage,
    'events_size' => $eventsPageSize,
    'shelters_size' => $sheltersPageSize,
    'routes_size' => $routesPageSize,
    'events_q' => $eventsSearch,
    'shelters_q' => $sheltersSearch,
    'routes_q' => $routesSearch,
    'events_sort' => $eventsSort,
    'events_dir' => $eventsSortDir,
    'shelters_sort' => $sheltersSort,
    'shelters_dir' => $sheltersSortDir,
    'routes_sort' => $routesSort,
    'routes_dir' => $routesSortDir,
];

function adminUrl(array $overrides = []): string
{
    global $adminQueryBase;

    return '/admin?' . http_build_query(array_merge($adminQueryBase, $overrides));
}

include __DIR__ . '/views/AdminView.php';
