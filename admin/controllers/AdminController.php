<?php

class AdminController
{
    public static array $queryBase = [];

    public static function dashboard(): void
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

        $eventModel   = new \ClientModels\Event($apiBaseUrl);
        $shelterModel = new \ClientModels\Shelter($apiBaseUrl);
        $routeModel   = new \ClientModels\EvacuationRoute($apiBaseUrl);

        $csrf     = htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8');
        $username = htmlspecialchars((string) ($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8');

        $successEvent   = isset($_GET['success_event']);
        $errorEvent     = isset($_GET['error_event'])   ? self::e(urldecode((string) $_GET['error_event']))   : '';
        $successShelter = isset($_GET['success_shelter']);
        $errorShelter   = isset($_GET['error_shelter']) ? self::e(urldecode((string) $_GET['error_shelter'])) : '';
        $successRoute   = isset($_GET['success_route']);
        $errorRoute     = isset($_GET['error_route'])   ? self::e(urldecode((string) $_GET['error_route']))   : '';

        $allowedTabs = ['events', 'shelters', 'routes'];
        if (!isset($_GET['tab']) || !in_array($_GET['tab'], $allowedTabs, true)) {
            header('Location: /admin?tab=events');
            exit;
        }
        $activeTab = $_GET['tab'];

        $pageSizes       = [5, 10, 25, 50, 100];
        $defaultPageSize = 10;

        $eventTypeOptions = [
            ['value' => 'earthquake', 'label' => 'Earthquake'],
            ['value' => 'flood',      'label' => 'Flood'],
            ['value' => 'fire',       'label' => 'Fire'],
            ['value' => 'storm',      'label' => 'Storm'],
            ['value' => 'other',      'label' => 'Other'],
        ];
        $severityOptions = [
            ['value' => 'low',      'label' => 'Low'],
            ['value' => 'moderate', 'label' => 'Moderate'],
            ['value' => 'high',     'label' => 'High'],
            ['value' => 'extreme',  'label' => 'Extreme'],
        ];
        $eventStatusOptions = [
            ['value' => 'active',   'label' => 'Active'],
            ['value' => 'resolved', 'label' => 'Resolved'],
        ];
        $shelterTypeOptions = [
            ['value' => 'community', 'label' => 'Community'],
            ['value' => 'school',    'label' => 'School'],
            ['value' => 'stadium',   'label' => 'Stadium'],
            ['value' => 'military',  'label' => 'Military'],
            ['value' => 'other',     'label' => 'Other'],
        ];
        $shelterStatusOptions = [
            ['value' => 'open',   'label' => 'Open'],
            ['value' => 'full',   'label' => 'Full'],
            ['value' => 'closed', 'label' => 'Closed'],
        ];

        $eventsPageSize   = self::normalizePageSize($_GET['events_size']   ?? $defaultPageSize, $pageSizes, $defaultPageSize);
        $sheltersPageSize = self::normalizePageSize($_GET['shelters_size'] ?? $defaultPageSize, $pageSizes, $defaultPageSize);
        $routesPageSize   = self::normalizePageSize($_GET['routes_size']   ?? $defaultPageSize, $pageSizes, $defaultPageSize);

        $eventsSearch   = trim((string) ($_GET['events_q']   ?? ''));
        $sheltersSearch = trim((string) ($_GET['shelters_q'] ?? ''));
        $routesSearch   = trim((string) ($_GET['routes_q']   ?? ''));

        $eventsPage   = max(1, (int) ($_GET['events_page']   ?? 1));
        $sheltersPage = max(1, (int) ($_GET['shelters_page'] ?? 1));
        $routesPage   = max(1, (int) ($_GET['routes_page']   ?? 1));

        $eventSortMap = [
            'type'        => 'event_type',
            'title'       => 'title',
            'description' => 'description',
            'severity'    => 'severity',
            'status'      => 'status',
            'latitude'    => 'latitude',
            'longitude'   => 'longitude',
            'created'     => 'created_at',
            'updated'     => 'updated_at',
        ];
        $shelterSortMap = [
            'name'    => 'name',
            'address' => 'address',
            'type'    => 'shelter_type',
            'capacity'=> 'capacity',
            'status'  => 'status',
            'phone'   => 'contact_phone',
            'notes'   => 'notes',
            'created' => 'created_at',
            'updated' => 'updated_at',
        ];
        $routeSortMap = [
            'name'     => 'name',
            'shelter'  => 'shelter',
            'distance' => 'distance',
            'duration' => 'duration',
            'status'   => 'status',
            'created'  => 'created',
            'updated'  => 'updated',
        ];

        [$eventsSort,   $eventsSortDir,   ] = self::resolveSort($_GET['events_sort']   ?? 'created', $_GET['events_dir']   ?? 'desc', $eventSortMap,   'created');
        [$sheltersSort, $sheltersSortDir, ] = self::resolveSort($_GET['shelters_sort'] ?? 'created', $_GET['shelters_dir'] ?? 'desc', $shelterSortMap, 'created');
        [$routesSort,   $routesSortDir,   ] = self::resolveSort($_GET['routes_sort']   ?? 'created', $_GET['routes_dir']   ?? 'desc', $routeSortMap,   'created');

        [$okE, $eventData] = $eventModel->getPaginated($eventsPage, $eventsPageSize, $eventsSearch, $eventsSort, $eventsSortDir);
        $events          = $okE ? ($eventData['rows']       ?? []) : [];
        $eventTotalPages = $okE ? ($eventData['totalPages'] ?? 1)  : 1;
        $eventsPage      = $okE ? ($eventData['page']       ?? 1)  : 1;

        foreach ($events as &$row) {
            $row['event_type']     = self::e($row['event_type']  ?? '');
            $row['title']          = self::e($row['title']       ?? '');
            $row['description']    = self::e($row['description'] ?? '');
            $row['severity']       = self::e($row['severity']    ?? '');
            $row['status']         = self::e($row['status']      ?? '');
            $row['created_at']     = self::formatTimestamp($row['created_at'] ?? '');
            $row['updated_at']     = self::formatTimestamp($row['updated_at'] ?? '');
            $row['latitude_val']   = $row['latitude']  !== null ? self::e((string) $row['latitude'])  : '';
            $row['longitude_val']  = $row['longitude'] !== null ? self::e((string) $row['longitude']) : '';
            $row['latitude_disp']  = $row['latitude']  !== null ? $row['latitude_val']  : 'None';
            $row['longitude_disp'] = $row['longitude'] !== null ? $row['longitude_val'] : 'None';
        }
        unset($row);
        $hasEvents = !empty($events);

        [$okS, $shelterData] = $shelterModel->getPaginated($sheltersPage, $sheltersPageSize, $sheltersSearch, $sheltersSort, $sheltersSortDir);
        $shelters          = $okS ? ($shelterData['rows']       ?? []) : [];
        $shelterTotalPages = $okS ? ($shelterData['totalPages'] ?? 1)  : 1;
        $sheltersPage      = $okS ? ($shelterData['page']       ?? 1)  : 1;

        foreach ($shelters as &$row) {
            $row['name']          = self::e($row['name']          ?? '');
            $row['address']       = self::e($row['address']       ?? '');
            $row['shelter_type']  = self::e($row['shelter_type']  ?? '');
            $row['status']        = self::e($row['status']        ?? '');
            $row['capacity']      = self::e((string) ($row['capacity']  ?? 0));
            $row['latitude']      = self::e((string) ($row['latitude']  ?? ''));
            $row['longitude']     = self::e((string) ($row['longitude'] ?? ''));
            $row['contact_phone'] = self::e($row['contact_phone'] ?? '');
            $row['notes']         = self::e($row['notes']         ?? '');
            $row['created_at']    = self::formatTimestamp($row['created_at'] ?? '');
            $row['updated_at']    = self::formatTimestamp($row['updated_at'] ?? '');
        }
        unset($row);
        $hasShelters = !empty($shelters);

        [$okR, $routeData] = $routeModel->getPaginated($routesPage, $routesPageSize, $routesSearch, $routesSort, $routesSortDir);
        $routes          = $okR ? ($routeData['rows']       ?? []) : [];
        $routeTotalPages = $okR ? ($routeData['totalPages'] ?? 1)  : 1;
        $routesPage      = $okR ? ($routeData['page']       ?? 1)  : 1;

        foreach ($routes as &$row) {
            $row['name']               = self::e($row['name']               ?? '');
            $row['shelter_id']         = (int) ($row['shelter_id']          ?? 0);
            $row['shelter_name']       = self::e($row['shelter_name']       ?? '');
            $row['from_latitude']      = self::e((string) ($row['from_latitude']      ?? ''));
            $row['from_longitude']     = self::e((string) ($row['from_longitude']     ?? ''));
            $row['distance_meters']    = self::e((string) ($row['distance_meters']    ?? ''));
            $row['estimated_minutes']  = self::e((string) ($row['estimated_minutes']  ?? ''));
            $row['status']             = self::e($row['status']             ?? '');
            $row['notes']              = self::e($row['notes']              ?? '');
            $row['created_at']         = self::formatTimestamp($row['created_at'] ?? '');
            $row['updated_at']         = self::formatTimestamp($row['updated_at'] ?? '');
        }
        unset($row);
        $hasRoutes = !empty($routes);

        [$okAllShelters, $allSheltersData] = $shelterModel->getAll();
        $sheltersForDropdown = $okAllShelters ? $allSheltersData : [];
        foreach ($sheltersForDropdown as &$row) {
            $row['id']   = (int) ($row['id']   ?? 0);
            $row['name'] = self::e($row['name'] ?? '');
        }
        unset($row);

        $adminQueryBase = [
            'tab'          => $activeTab,
            'events_page'  => $eventsPage,
            'shelters_page'=> $sheltersPage,
            'routes_page'  => $routesPage,
            'events_size'  => $eventsPageSize,
            'shelters_size'=> $sheltersPageSize,
            'routes_size'  => $routesPageSize,
            'events_q'     => $eventsSearch,
            'shelters_q'   => $sheltersSearch,
            'routes_q'     => $routesSearch,
            'events_sort'  => $eventsSort,
            'events_dir'   => $eventsSortDir,
            'shelters_sort'=> $sheltersSort,
            'shelters_dir' => $sheltersSortDir,
            'routes_sort'  => $routesSort,
            'routes_dir'   => $routesSortDir,
        ];

        self::$queryBase = $adminQueryBase;

        $currentPageKey = $activeTab . '_page';
        $currentSizeKey = $activeTab . '_size';
        $currentSearchKey = $activeTab . '_q';
        $currentSortKey = $activeTab . '_sort';
        $currentDirKey = $activeTab . '_dir';
        $currentSortField = $activeTab === 'events' ? $eventsSort : ($activeTab === 'shelters' ? $sheltersSort : $routesSort);
        $currentSortDir = $activeTab === 'events' ? $eventsSortDir : ($activeTab === 'shelters' ? $sheltersSortDir : $routesSortDir);
        $currentSearchValue = $activeTab === 'events' ? $eventsSearch : ($activeTab === 'shelters' ? $sheltersSearch : $routesSearch);
        $currentPageSize = $activeTab === 'events' ? $eventsPageSize : ($activeTab === 'shelters' ? $sheltersPageSize : $routesPageSize);

        if ($activeTab === 'events') {
            $currentEntityLabel = 'Disaster Event';
            $currentEntityLabelPlural = 'Disaster Events';
            $currentSearchPlaceholder = 'Search title or description';
        } elseif ($activeTab === 'shelters') {
            $currentEntityLabel = 'Shelter';
            $currentEntityLabelPlural = 'Shelters';
            $currentSearchPlaceholder = 'Search name or address';
        } else {
            $currentEntityLabel = 'Evacuation Route';
            $currentEntityLabelPlural = 'Evacuation Routes';
            $currentSearchPlaceholder = 'Search name or shelter';
        }

        $adminUrl = static function (array $overrides = []) use ($adminQueryBase): string {
            return '/admin?' . http_build_query(array_merge($adminQueryBase, $overrides));
        };

        $sortLink = static function (string $tab, string $label, string $field, string $currentField, string $currentDir) use ($adminUrl): string {
            $nextDir = ($currentField === $field && strtolower($currentDir) === 'asc') ? 'desc' : 'asc';
            $icon = '';
            if ($currentField === $field) {
                $icon = $currentDir === 'ASC'
                    ? ' <span class="sort-indicator">&#9650;</span>'
                    : ' <span class="sort-indicator">&#9660;</span>';
            }
            $href = htmlspecialchars($adminUrl(['tab' => $tab, $tab . '_page' => 1, $tab . '_sort' => $field, $tab . '_dir' => $nextDir]), ENT_QUOTES, 'UTF-8');
            return '<a class="sort-link" href="' . $href . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $icon . '</a>';
        };

        include __DIR__ . '/../views/AdminView.php';
    }

    private static function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private static function normalizePageSize($value, array $allowed, int $default): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if (!$value || !in_array($value, $allowed, true)) {
            return $default;
        }
        return $value;
    }

    private static function resolveSort(string $requestedField, string $requestedDir, array $allowedSorts, string $defaultField): array
    {
        if (!array_key_exists($requestedField, $allowedSorts)) {
            $requestedField = $defaultField;
        }
        $direction = strtolower($requestedDir) === 'asc' ? 'ASC' : 'DESC';
        return [$requestedField, $direction, $allowedSorts[$requestedField]];
    }

    private static function formatTimestamp($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $time = strtotime((string) $value);
        if ($time === false) {
            return self::e($value);
        }
        return self::e(date('Y-m-d H:i:s', $time));
    }
}
