<?php
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

$sortLink = function (string $tab, string $label, string $field, string $currentField, string $currentDir) {
    $nextDir = ($currentField === $field && strtolower($currentDir) === 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($currentField === $field) {
        $icon = $currentDir === 'ASC' ? ' <span class="sort-indicator">&#9650;</span>' : ' <span class="sort-indicator">&#9660;</span>';
    }

    return '<a class="sort-link" href="' . e(adminUrl([
        'tab' => $tab,
        $tab . '_page' => 1,
        $tab . '_sort' => $field,
        $tab . '_dir' => $nextDir,
    ])) . '">' . e($label) . $icon . '</a>';
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>

<body>

<header class="admin-header">
    <div class="header-top">
        <h1>Admin</h1>
        <button id="menuToggle" class="menu-toggle" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    <nav id="headerNav">
        <a href="dashboard">Dashboard</a>
        <a href="login">Logout</a>
    </nav>
</header>

<main class="admin-container">
    <p class="welcome-line">Welcome, <strong><?= $username ?></strong>!</p>

    <?php if ($successEvent || $errorEvent !== '' || $successShelter || $errorShelter !== '' || $successRoute || $errorRoute !== ''): ?>
        <div class="flash-stack" aria-live="polite">
            <?php if ($successEvent): ?>
                <div class="msg success">Disaster event recorded successfully.</div>
            <?php endif; ?>
            <?php if ($errorEvent !== ''): ?>
                <div class="msg error"><?= $errorEvent ?></div>
            <?php endif; ?>
            <?php if ($successShelter): ?>
                <div class="msg success">Shelter added successfully.</div>
            <?php endif; ?>
            <?php if ($errorShelter !== ''): ?>
                <div class="msg error"><?= $errorShelter ?></div>
            <?php endif; ?>
            <?php if ($successRoute): ?>
                <div class="msg success">Evacuation route added successfully.</div>
            <?php endif; ?>
            <?php if ($errorRoute !== ''): ?>
                <div class="msg error"><?= $errorRoute ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <section class="admin-board panel">
        <div class="admin-tabs" role="tablist" aria-label="Admin datasets">
            <a id="events-tab" class="admin-tab <?= $activeTab === 'events' ? 'active' : '' ?>" href="<?= e(adminUrl(['tab' => 'events'])) ?>">Disaster Events</a>
            <a id="shelters-tab" class="admin-tab <?= $activeTab === 'shelters' ? 'active' : '' ?>" href="<?= e(adminUrl(['tab' => 'shelters'])) ?>">Shelters</a>
            <a id="routes-tab" class="admin-tab <?= $activeTab === 'routes' ? 'active' : '' ?>" href="<?= e(adminUrl(['tab' => 'routes'])) ?>">Evacuation Routes</a>
        </div>

        <div class="admin-toolbar">
            <form class="toolbar-group toolbar-search" method="get" action="<?= e(adminUrl([$currentPageKey => 1])) ?>">
                <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
                <input
                    type="search"
                    name="<?= e($currentSearchKey) ?>"
                    value="<?= e($currentSearchValue) ?>"
                    placeholder="<?= e($currentSearchPlaceholder) ?>"
                    aria-label="<?= e($currentSearchPlaceholder) ?>"
                >
                <button type="submit">Search</button>
            </form>

            <form class="toolbar-group toolbar-size" method="get" action="<?= e(adminUrl([$currentPageKey => 1])) ?>">
                <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
                <label for="pageSizeSelect">Entries</label>
                <select id="pageSizeSelect" name="<?= e($currentSizeKey) ?>" onchange="this.form.submit()">
                    <?php foreach ($pageSizes as $size): ?>
                        <option value="<?= $size ?>" <?= (int) $currentPageSize === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <div class="actions-dropdown">
                <button type="button" class="btn-actions-toggle" id="actionsMenuToggle">
                    <span class="icon-menu">&#9881;</span> Tools
                </button>
                <div class="actions-dropdown-content" id="actionsMenuContent">
                    <button type="button" class="dropdown-item btn-import-item" data-open-import-modal>
                        <span class="icon-import">&#128197;</span> Import Data
                    </button>
                    <a href="/api/export?resource=<?= e($activeTab) ?>&format=csv" class="dropdown-item btn-export-item" download>
                        <span class="icon-export">&#128196;</span> Export CSV
                    </a>
                    <a href="/api/export?resource=<?= e($activeTab) ?>&format=json" class="dropdown-item btn-export-item" download>
                        <span class="icon-export">&#123;..&#125;</span> Export JSON
                    </a>
                </div>
            </div>
            <button type="button" class="btn-add" data-open-add-modal>Add</button>
        </div>

        <div class="tab-panels">
            <section class="tab-panel <?= $activeTab === 'events' ? 'active' : '' ?>" id="events-panel" role="tabpanel" aria-labelledby="events-tab">
                <div class="panel-header-row">
                    <h2>Historical Disaster Events</h2>
                    <p>Browse the events archive, search for specific events and sort them in a specific order</p>
                </div>
                <div class="table-responsive">
                    <table class="dashboard-table">
                        <thead>
                        <tr>
                            <th><?= $sortLink('events', 'Type', 'type', $eventsSort, $eventsSortDir) ?></th>
                            <th><?= $sortLink('events', 'Title', 'title', $eventsSort, $eventsSortDir) ?></th>
                            <th><?= $sortLink('events', 'Description', 'description', $eventsSort, $eventsSortDir) ?></th>
                            <th><?= $sortLink('events', 'Severity', 'severity', $eventsSort, $eventsSortDir) ?></th>
                            <th><?= $sortLink('events', 'Status', 'status', $eventsSort, $eventsSortDir) ?></th>
                            <th><?= $sortLink('events', 'Latitude', 'latitude', $eventsSort, $eventsSortDir) ?></th>
                            <th><?= $sortLink('events', 'Longitude', 'longitude', $eventsSort, $eventsSortDir) ?></th>
                            <th><?= $sortLink('events', 'Created', 'created', $eventsSort, $eventsSortDir) ?></th>
                            <th><?= $sortLink('events', 'Updated', 'updated', $eventsSort, $eventsSortDir) ?></th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$hasEvents): ?>
                            <tr><td colspan="10" class="empty-state-cell">No events recorded.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($events as $e): ?>
                            <tr data-id="<?= $e['id'] ?>">
                                <td data-label="Type">
                                    <span class="mode-view"><?= $e['event_type'] ?></span>
                                    <select class="mode-edit field-event_type" style="display:none;">
                                        <?php foreach ($eventTypeOptions as $opt): ?>
                                            <option value="<?= $opt['value'] ?>" <?= $e['event_type'] === $opt['value'] ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="Title">
                                    <span class="mode-view"><?= $e['title'] ?></span>
                                    <input type="text" class="mode-edit field-title" value="<?= $e['title'] ?>" style="display:none;">
                                </td>
                                <td data-label="Description">
                                    <?php if (trim(html_entity_decode($e['description'], ENT_QUOTES, 'UTF-8')) === ''): ?>
                                        <span class="muted-text">No description</span>
                                    <?php else: ?>
                                        <div class="description-cell">
                                            <div class="mode-view desc-text"><?= $e['description'] ?></div>
                                            <textarea class="mode-edit field-description" style="display:none;"><?= htmlspecialchars($e['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                            <button
                                                type="button"
                                                class="view-more-btn mode-view"
                                                data-open-description
                                                data-title="<?= $e['title'] ?>"
                                                data-description="<?= $e['description'] ?>"
                                            >View more</button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Severity">
                                    <span class="mode-view"><?= $e['severity'] ?></span>
                                    <select class="mode-edit field-severity" style="display:none;">
                                        <?php foreach ($severityOptions as $opt): ?>
                                            <option value="<?= $opt['value'] ?>" <?= $e['severity'] === $opt['value'] ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="Status">
                                    <span class="mode-view"><?= $e['status'] ?></span>
                                    <select class="mode-edit field-status" style="display:none;">
                                        <?php foreach ($eventStatusOptions as $opt): ?>
                                            <option value="<?= $opt['value'] ?>" <?= $e['status'] === $opt['value'] ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="Latitude">
                                    <span class="mode-view"><?= $e['latitude_disp'] ?></span>
                                    <input type="number" step="0.001" class="mode-edit field-latitude" value="<?= $e['latitude_val'] ?>" style="display:none;">
                                </td>
                                <td data-label="Longitude">
                                    <span class="mode-view"><?= $e['longitude_disp'] ?></span>
                                    <input type="number" step="0.001" class="mode-edit field-longitude" value="<?= $e['longitude_val'] ?>" style="display:none;">
                                </td>
                                <td data-label="Created">
                                    <span class="mode-view timestamp-cell"><?php $parts = preg_split('/\s+/', $e['created_at'], 2); echo htmlspecialchars($parts[0]); if (!empty($parts[1])) { echo '<br>' . htmlspecialchars($parts[1]); } ?></span>
                                </td>
                                <td data-label="Updated">
                                    <span class="mode-view timestamp-cell"><?php $parts = preg_split('/\s+/', $e['updated_at'], 2); echo htmlspecialchars($parts[0]); if (!empty($parts[1])) { echo '<br>' . htmlspecialchars($parts[1]); } ?></span>
                                </td>
                                <td data-label="Actions">
                                    <button class="btn-action-edit mode-view" type="button" onclick="toggleEditRow(this)">Edit</button>
                                    <button class="btn-action-delete mode-view" type="button" onclick="dispatchDelete('event', <?= $e['id'] ?>)">Delete</button>

                                    <button class="btn-action-save mode-edit" type="button" style="display:none;" onclick="dispatchUpdate('event', <?= $e['id'] ?>, this)">Save</button>
                                    <button class="btn-action-cancel mode-edit" type="button" style="display:none;" onclick="toggleEditRow(this)">Cancel</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($eventTotalPages > 1): ?>
                    <div class="pagination-bar">
                        <span>Page <?= $eventsPage ?> of <?= $eventTotalPages ?></span>
                        <div class="pagination-links">
                            <?php if ($eventsPage > 1): ?>
                                <a href="<?= e(adminUrl(['tab' => 'events', 'events_page' => $eventsPage - 1])) ?>">Previous</a>
                            <?php endif; ?>
                            <?php if ($eventsPage < $eventTotalPages): ?>
                                <a href="<?= e(adminUrl(['tab' => 'events', 'events_page' => $eventsPage + 1])) ?>">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="tab-panel <?= $activeTab === 'shelters' ? 'active' : '' ?>" id="shelters-panel" role="tabpanel" aria-labelledby="shelters-tab">
                <div class="panel-header-row">
                    <h2>Historical Shelters</h2>
                    <p>Browse through the different shelters, search a specific one or sort them in any order</p>
                </div>
                <div class="table-responsive">
                    <table class="dashboard-table">
                        <thead>
                        <tr>
                            <th><?= $sortLink('shelters', 'Name', 'name', $sheltersSort, $sheltersSortDir) ?></th>
                            <th><?= $sortLink('shelters', 'Address', 'address', $sheltersSort, $sheltersSortDir) ?></th>
                            <th><?= $sortLink('shelters', 'Type', 'type', $sheltersSort, $sheltersSortDir) ?></th>
                            <th><?= $sortLink('shelters', 'Capacity', 'capacity', $sheltersSort, $sheltersSortDir) ?></th>
                            <th><?= $sortLink('shelters', 'Status', 'status', $sheltersSort, $sheltersSortDir) ?></th>
                            <th><?= $sortLink('shelters', 'Phone', 'phone', $sheltersSort, $sheltersSortDir) ?></th>
                            <th><?= $sortLink('shelters', 'Notes', 'notes', $sheltersSort, $sheltersSortDir) ?></th>
                            <th><?= $sortLink('shelters', 'Created', 'created', $sheltersSort, $sheltersSortDir) ?></th>
                            <th><?= $sortLink('shelters', 'Updated', 'updated', $sheltersSort, $sheltersSortDir) ?></th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$hasShelters): ?>
                            <tr><td colspan="10" class="empty-state-cell">No shelter locations setup.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($shelters as $s): ?>
                            <tr data-id="<?= $s['id'] ?>">
                                <td data-label="Name">
                                    <span class="mode-view"><?= $s['name'] ?></span>
                                    <input type="text" class="mode-edit field-name" value="<?= $s['name'] ?>" style="display:none;">
                                </td>
                                <td data-label="Address">
                                    <span class="mode-view"><?= $s['address'] ?></span>
                                    <input type="text" class="mode-edit field-address" value="<?= $s['address'] ?>" style="display:none;">
                                </td>
                                <td data-label="Type">
                                    <span class="mode-view"><?= $s['shelter_type'] ?></span>
                                    <select class="mode-edit field-shelter_type" style="display:none;">
                                        <?php foreach ($shelterTypeOptions as $opt): ?>
                                            <option value="<?= $opt['value'] ?>" <?= $s['shelter_type'] === $opt['value'] ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="Capacity">
                                    <span class="mode-view"><?= $s['capacity'] ?></span>
                                    <input type="number" class="mode-edit field-capacity" value="<?= $s['capacity'] ?>" style="display:none;">
                                    <input type="hidden" class="field-latitude" value="<?= $s['latitude'] ?>">
                                    <input type="hidden" class="field-longitude" value="<?= $s['longitude'] ?>">
                                </td>
                                <td data-label="Status">
                                    <span class="mode-view"><?= $s['status'] ?></span>
                                    <select class="mode-edit field-status" style="display:none;">
                                        <?php foreach ($shelterStatusOptions as $opt): ?>
                                            <option value="<?= $opt['value'] ?>" <?= $s['status'] === $opt['value'] ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="Phone">
                                    <span class="mode-view"><?= $s['contact_phone'] ?></span>
                                    <input type="text" class="mode-edit field-contact_phone" value="<?= $s['contact_phone'] ?>" style="display:none;">
                                </td>
                                <td data-label="Notes">
                                    <span class="mode-view note-cell"><?= $s['notes'] ?></span>
                                    <textarea class="mode-edit field-notes" style="display:none;"><?= $s['notes'] ?></textarea>
                                </td>
                                <td data-label="Created">
                                    <span class="mode-view timestamp-cell"><?= $s['created_at'] ?></span>
                                </td>
                                <td data-label="Updated">
                                    <span class="mode-view timestamp-cell"><?= $s['updated_at'] ?></span>
                                </td>
                                <td data-label="Actions">
                                    <button class="btn-action-edit mode-view" type="button" onclick="toggleEditRow(this)">Edit</button>
                                    <button class="btn-action-delete mode-view" type="button" onclick="dispatchDelete('shelter', <?= $s['id'] ?>)">Delete</button>

                                    <button class="btn-action-save mode-edit" type="button" style="display:none;" onclick="dispatchUpdate('shelter', <?= $s['id'] ?>, this)">Save</button>
                                    <button class="btn-action-cancel mode-edit" type="button" style="display:none;" onclick="toggleEditRow(this)">Cancel</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($shelterTotalPages > 1): ?>
                    <div class="pagination-bar">
                        <span>Page <?= $sheltersPage ?> of <?= $shelterTotalPages ?></span>
                        <div class="pagination-links">
                            <?php if ($sheltersPage > 1): ?>
                                <a href="<?= e(adminUrl(['tab' => 'shelters', 'shelters_page' => $sheltersPage - 1])) ?>">Previous</a>
                            <?php endif; ?>
                            <?php if ($sheltersPage < $shelterTotalPages): ?>
                                <a href="<?= e(adminUrl(['tab' => 'shelters', 'shelters_page' => $sheltersPage + 1])) ?>">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="tab-panel <?= $activeTab === 'routes' ? 'active' : '' ?>" id="routes-panel" role="tabpanel" aria-labelledby="routes-tab">
                <div class="panel-header-row">
                    <h2>Historical Evacuation Routes</h2>
                    <p>Browse through evacuation routes, search by route or shelter, and sort by name, shelter, distance, or duration</p>
                </div>
                <div class="table-responsive">
                    <table class="dashboard-table">
                        <thead>
                        <tr>
                            <th><?= $sortLink('routes', 'Route Name', 'name', $routesSort, $routesSortDir) ?></th>
                            <th><?= $sortLink('routes', 'Target Shelter', 'shelter', $routesSort, $routesSortDir) ?></th>
                            <th>Start Lat</th>
                            <th>Start Lng</th>
                            <th><?= $sortLink('routes', 'Distance (m)', 'distance', $routesSort, $routesSortDir) ?></th>
                            <th><?= $sortLink('routes', 'Duration (min)', 'duration', $routesSort, $routesSortDir) ?></th>
                            <th><?= $sortLink('routes', 'Status', 'status', $routesSort, $routesSortDir) ?></th>
                            <th>Notes</th>
                            <th><?= $sortLink('routes', 'Created', 'created', $routesSort, $routesSortDir) ?></th>
                            <th><?= $sortLink('routes', 'Updated', 'updated', $routesSort, $routesSortDir) ?></th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$hasRoutes): ?>
                            <tr><td colspan="11" class="empty-state-cell">No evacuation routes setup.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($routes as $r): ?>
                            <tr data-id="<?= $r['id'] ?>">
                                <td data-label="Route Name">
                                    <span class="mode-view"><?= $r['name'] ?></span>
                                    <input type="text" class="mode-edit field-name" value="<?= $r['name'] ?>" style="display:none;">
                                </td>
                                <td data-label="Target Shelter">
                                    <span class="mode-view"><?= $r['shelter_name'] ?></span>
                                    <select class="mode-edit field-shelter_id" style="display:none;">
                                        <?php foreach ($sheltersForDropdown as $sh): ?>
                                            <option value="<?= $sh['id'] ?>" <?= $r['shelter_id'] === $sh['id'] ? 'selected' : '' ?>><?= $sh['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="Start Lat">
                                    <span class="mode-view"><?= $r['from_latitude'] ?></span>
                                    <input type="number" step="0.000001" class="mode-edit field-from_latitude" value="<?= $r['from_latitude'] ?>" style="display:none;">
                                </td>
                                <td data-label="Start Lng">
                                    <span class="mode-view"><?= $r['from_longitude'] ?></span>
                                    <input type="number" step="0.000001" class="mode-edit field-from_longitude" value="<?= $r['from_longitude'] ?>" style="display:none;">
                                </td>
                                <td data-label="Distance (m)">
                                    <span class="mode-view"><?= $r['distance_meters'] ?></span>
                                </td>
                                <td data-label="Duration (min)">
                                    <span class="mode-view"><?= $r['estimated_minutes'] ?></span>
                                </td>
                                <td data-label="Status">
                                    <span class="mode-view"><?= $r['status'] ?></span>
                                    <select class="mode-edit field-status" style="display:none;">
                                        <option value="active" <?= $r['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="blocked" <?= $r['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                        <option value="closed" <?= $r['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                    </select>
                                </td>
                                <td data-label="Notes">
                                    <span class="mode-view note-cell"><?= $r['notes'] ?></span>
                                    <textarea class="mode-edit field-notes" style="display:none;"><?= $r['notes'] ?></textarea>
                                </td>
                                <td data-label="Created">
                                    <span class="mode-view timestamp-cell"><?= $r['created_at'] ?></span>
                                </td>
                                <td data-label="Updated">
                                    <span class="mode-view timestamp-cell"><?= $r['updated_at'] ?></span>
                                </td>
                                <td data-label="Actions">
                                    <button class="btn-action-edit mode-view" type="button" onclick="toggleEditRow(this)">Edit</button>
                                    <button class="btn-action-delete mode-view" type="button" onclick="dispatchDelete('route', <?= $r['id'] ?>)">Delete</button>

                                    <button class="btn-action-save mode-edit" type="button" style="display:none;" onclick="dispatchUpdate('route', <?= $r['id'] ?>, this)">Save</button>
                                    <button class="btn-action-cancel mode-edit" type="button" style="display:none;" onclick="toggleEditRow(this)">Cancel</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($routeTotalPages > 1): ?>
                    <div class="pagination-bar">
                        <span>Page <?= $routesPage ?> of <?= $routeTotalPages ?></span>
                        <div class="pagination-links">
                            <?php if ($routesPage > 1): ?>
                                <a href="<?= e(adminUrl(['tab' => 'routes', 'routes_page' => $routesPage - 1])) ?>">Previous</a>
                            <?php endif; ?>
                            <?php if ($routesPage < $routeTotalPages): ?>
                                <a href="<?= e(adminUrl(['tab' => 'routes', 'routes_page' => $routesPage + 1])) ?>">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</main>

<div id="addModal" class="modal-overlay" hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="addModalTitle">
        <button type="button" class="modal-close" data-close-modal aria-label="Close add dialog">&times;</button>
        <h2 id="addModalTitle">Add <?= e($currentEntityLabel) ?></h2>
        <p class="modal-subtitle">Submit a new historical record for the selected tab.</p>

        <div class="modal-message" data-modal-message hidden></div>

        <form class="modal-form <?= $activeTab === 'events' ? 'active' : '' ?>" data-entity-form="events" method="post" action="/admin/submit_event">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="form-row">
                <label for="modal_event_type">Event Type</label>
                <select id="modal_event_type" name="event_type">
                    <?php foreach ($eventTypeOptions as $opt): ?>
                        <option value="<?= $opt['value'] ?>"><?= $opt['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="modal_event_title">Title</label>
                <input id="modal_event_title" name="title" type="text" required>
            </div>

            <div class="form-row">
                <label for="modal_event_description">Description</label>
                <textarea id="modal_event_description" name="description" rows="4"></textarea>
            </div>

            <div class="form-row">
                <label for="modal_event_severity">Severity</label>
                <select id="modal_event_severity" name="severity">
                    <?php foreach ($severityOptions as $opt): ?>
                        <option value="<?= $opt['value'] ?>" <?= $opt['value'] === 'moderate' ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grid-two">
                <div class="form-row">
                    <label for="modal_event_latitude">Latitude</label>
                    <input id="modal_event_latitude" name="latitude" type="number" step="0.001">
                </div>
                <div class="form-row">
                    <label for="modal_event_longitude">Longitude</label>
                    <input id="modal_event_longitude" name="longitude" type="number" step="0.001">
                </div>
            </div>

            <button type="submit" class="modal-submit">Add Event</button>
        </form>

        <form class="modal-form <?= $activeTab === 'shelters' ? 'active' : '' ?>" data-entity-form="shelters" method="post" action="/admin/submit_shelter">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="form-row">
                <label for="modal_shelter_name">Shelter Name</label>
                <input id="modal_shelter_name" name="name" type="text" required>
            </div>

            <div class="form-row">
                <label for="modal_shelter_address">Address</label>
                <input id="modal_shelter_address" name="address" type="text" required>
            </div>

            <div class="form-grid-two">
                <div class="form-row">
                    <label for="modal_shelter_latitude">Latitude</label>
                    <input id="modal_shelter_latitude" name="latitude" type="number" step="0.001" required>
                </div>
                <div class="form-row">
                    <label for="modal_shelter_longitude">Longitude</label>
                    <input id="modal_shelter_longitude" name="longitude" type="number" step="0.001" required>
                </div>
            </div>

            <div class="form-row">
                <label for="modal_shelter_capacity">Capacity</label>
                <input id="modal_shelter_capacity" name="capacity" type="number" min="0" value="0">
            </div>

            <div class="form-row">
                <label for="modal_shelter_type">Type</label>
                <select id="modal_shelter_type" name="shelter_type">
                    <?php foreach ($shelterTypeOptions as $opt): ?>
                        <option value="<?= $opt['value'] ?>"><?= $opt['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="modal_shelter_phone">Contact Phone</label>
                <input id="modal_shelter_phone" name="contact_phone" type="text">
            </div>

            <div class="form-row">
                <label for="modal_shelter_notes">Notes</label>
                <textarea id="modal_shelter_notes" name="notes" rows="4"></textarea>
            </div>

            <button type="submit" class="modal-submit">Add Shelter</button>
        </form>

        <form class="modal-form <?= $activeTab === 'routes' ? 'active' : '' ?>" data-entity-form="routes" method="post" action="/admin/submit_route.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="form-row">
                <label for="modal_route_name">Route Name</label>
                <input id="modal_route_name" name="name" type="text" required>
            </div>

            <div class="form-row">
                <label for="modal_route_shelter_id">Target Shelter</label>
                <select id="modal_route_shelter_id" name="shelter_id" required>
                    <option value="">-- Select Target Shelter --</option>
                    <?php foreach ($sheltersForDropdown as $sh): ?>
                        <option value="<?= $sh['id'] ?>"><?= $sh['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grid-two">
                <div class="form-row">
                    <label for="modal_route_latitude">Start Latitude</label>
                    <input id="modal_route_latitude" name="from_latitude" type="number" step="0.000001" required>
                </div>
                <div class="form-row">
                    <label for="modal_route_longitude">Start Longitude</label>
                    <input id="modal_route_longitude" name="from_longitude" type="number" step="0.000001" required>
                </div>
            </div>

            <div class="form-row">
                <label for="modal_route_status">Status</label>
                <select id="modal_route_status" name="status">
                    <option value="active" selected>Active</option>
                    <option value="blocked">Blocked</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <div class="form-row">
                <label for="modal_route_notes">Notes</label>
                <textarea id="modal_route_notes" name="notes" rows="4"></textarea>
            </div>

            <button type="submit" class="modal-submit">Add Route</button>
        </form>
    </div>
</div>

<div id="importModal" class="modal-overlay" hidden>
    <div class="modal-card modal-card--compact" role="dialog" aria-modal="true" aria-labelledby="importModalTitle">
        <button type="button" class="modal-close" data-close-import aria-label="Close import dialog">&times;</button>
        <h2 id="importModalTitle">Import <?= e($currentEntityLabelPlural) ?></h2>
        <p class="modal-subtitle">Upload a CSV or JSON file containing <?= e($activeTab) ?> data to import.</p>

        <div class="modal-message" data-import-message hidden></div>

        <form class="modal-form active" id="importForm" method="post" enctype="multipart/form-data" action="/api/import?resource=<?= e($activeTab) ?>">
            <div class="form-row">
                <label for="import_file">Choose File (.csv, .json)</label>
                <input id="import_file" name="import_file" type="file" accept=".csv,.json" required>
            </div>
            
            <div style="font-size: 13px; color: var(--muted); margin-bottom: 20px; text-align: left; line-height: 1.45; border: 1px solid var(--panel-border); padding: 10px; background: #fafafa;">
                <strong>Required CSV Headers:</strong><br>
                <?php if ($activeTab === 'events'): ?>
                    <code>title</code> (req), <code>description</code>, <code>event_type</code> (earthquake/flood/fire/storm/other), <code>severity</code> (low/moderate/high/extreme), <code>latitude</code>, <code>longitude</code>, <code>status</code> (active/resolved)
                <?php else: ?>
                    <code>name</code> (req), <code>address</code> (req), <code>latitude</code> (req), <code>longitude</code> (req), <code>capacity</code>, <code>current_occupancy</code>, <code>shelter_type</code> (community/school/stadium/military/other), <code>status</code> (open/full/closed), <code>contact_phone</code>, <code>notes</code>
                <?php endif; ?>
            </div>

            <button type="submit" class="modal-submit">Import Data</button>
        </form>
    </div>
</div>

<div id="descriptionModal" class="modal-overlay" hidden>
    <div class="modal-card modal-card--compact" role="dialog" aria-modal="true" aria-labelledby="descriptionModalTitle">
        <button type="button" class="modal-close" data-close-description aria-label="Close description dialog">&times;</button>
        <h2 id="descriptionModalTitle">Description</h2>
        <div id="descriptionModalBody" class="description-modal-body"></div>
    </div>
</div>

<script>
    const GLOBAL_CSRF_TOKEN = '<?= $csrf ?>';

    var menuToggle = document.getElementById("menuToggle");
    var headerNav = document.getElementById("headerNav");
    if (menuToggle && headerNav) {
        menuToggle.addEventListener("click", () => {
            menuToggle.classList.toggle("open");
            headerNav.classList.toggle("open");
        });
        headerNav.addEventListener("click", (e) => {
            if (e.target.tagName === "A") {
                menuToggle.classList.remove("open");
                headerNav.classList.remove("open");
            }
        });
    }

    const addModal = document.getElementById('addModal');
    const descriptionModal = document.getElementById('descriptionModal');
    const addModalTitle = document.getElementById('addModalTitle');
    const addForms = Array.from(document.querySelectorAll('[data-entity-form]'));
    const addMessage = document.querySelector('[data-modal-message]');
    const descriptionTitle = document.getElementById('descriptionModalTitle');
    const descriptionBody = document.getElementById('descriptionModalBody');
    const activeTab = '<?= $activeTab ?>';
    const addButton = document.querySelector('[data-open-add-modal]');

    function openModal(modal) {
        if (!modal) return;
        modal.hidden = false;
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    }

    function showModalMessage(text, type) {
        if (!addMessage) return;
        addMessage.hidden = false;
        addMessage.textContent = text;
        addMessage.className = 'modal-message ' + (type === 'success' ? 'success' : 'error');
    }

    function resetModalMessage() {
        if (!addMessage) return;
        addMessage.hidden = true;
        addMessage.textContent = '';
        addMessage.className = 'modal-message';
    }

    function openAddModal() {
        addForms.forEach(form => {
            form.classList.toggle('active', form.dataset.entityForm === activeTab);
        });
        addModalTitle.textContent = 'Add ' + (activeTab === 'events' ? 'Disaster Event' : (activeTab === 'shelters' ? 'Shelter' : 'Evacuation Route'));
        resetModalMessage();
        openModal(addModal);
    }

    const importModal = document.getElementById('importModal');
    const importButton = document.querySelector('[data-open-import-modal]');
    const importMessage = document.querySelector('[data-import-message]');
    const importForm = document.getElementById('importForm');

    function openImportModal() {
        resetImportMessage();
        openModal(importModal);
    }

    if (importButton) {
        importButton.addEventListener('click', openImportModal);
    }

    function resetImportMessage() {
        if (importMessage) {
            importMessage.hidden = true;
            importMessage.textContent = '';
            importMessage.className = 'modal-message';
        }
    }

    function showImportMessage(text, type) {
        if (!importMessage) return;
        importMessage.hidden = false;
        importMessage.textContent = text;
        importMessage.className = 'modal-message ' + (type === 'success' ? 'success' : 'error');
    }

    if (addButton) {
        addButton.addEventListener('click', openAddModal);
    }

    function closeDescriptionModal() {
        if (descriptionTitle) {
            descriptionTitle.textContent = 'Description';
        }
        if (descriptionBody) {
            descriptionBody.textContent = '';
        }
        closeModal(descriptionModal);
    }

    document.querySelectorAll('[data-close-modal]').forEach(btn => btn.addEventListener('click', () => closeModal(addModal)));
    document.querySelectorAll('[data-close-description]').forEach(btn => btn.addEventListener('click', closeDescriptionModal));
    document.querySelectorAll('[data-close-import]').forEach(btn => btn.addEventListener('click', () => closeModal(importModal)));

    [addModal, descriptionModal, importModal].forEach(modal => {
        if (!modal) return;
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                if (modal === addModal) {
                    closeModal(addModal);
                } else if (modal === importModal) {
                    closeModal(importModal);
                } else {
                    closeDescriptionModal();
                }
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (addModal && !addModal.hidden) closeModal(addModal);
            if (importModal && !importModal.hidden) closeModal(importModal);
            if (descriptionModal && !descriptionModal.hidden) closeDescriptionModal();
        }
    });

    if (importForm) {
        importForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitButton = importForm.querySelector('.modal-submit');
            const previousText = submitButton ? submitButton.textContent : 'Import Data';
            
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Importing...';
            }
            resetImportMessage();

            try {
                const response = await fetch(importForm.action, {
                    method: 'POST',
                    body: new FormData(importForm),
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': GLOBAL_CSRF_TOKEN
                    }
                });

                const data = await parseResponseData(response);
                if (!response.ok || !data || data.success !== true) {
                    const message = data && data.error ? data.error : 'The server rejected the import request.';
                    showImportMessage(message, 'error');
                    return;
                }

                let msg = `Successfully imported ${data.imported} records.`;
                if (data.skipped > 0) {
                    msg += ` Skipped ${data.skipped} records.`;
                }
                showImportMessage(msg, 'success');
                window.setTimeout(() => window.location.reload(), 1500);
            } catch (error) {
                showImportMessage('Network error while performing import.', 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = previousText;
                }
            }
        });
    }

    document.addEventListener('click', (event) => {
        const descButton = event.target.closest('[data-open-description]');
        if (descButton) {
            const title = descButton.dataset.title || 'Description';
            const description = descButton.dataset.description || '';
            descriptionTitle.textContent = title;
            descriptionBody.textContent = description;
            openModal(descriptionModal);
        }
    });

    function toggleEditRow(button) {
        const tr = button.closest('tr');
        if (!tr) return;
        const views = tr.querySelectorAll('.mode-view');
        const edits = tr.querySelectorAll('.mode-edit');
        const activeState = (edits[0].style.display === 'none');

        views.forEach(el => el.style.display = activeState ? 'none' : '');
        edits.forEach(el => el.style.display = activeState ? '' : 'none');
    }

    function parseResponseData(response) {
        return response.json().catch(() => null);
    }

    async function submitAddForm(form) {
        const endpoint = form.action;
        const submitButton = form.querySelector('.modal-submit');
        const previousText = submitButton ? submitButton.textContent : '';
        const controller = new AbortController();
        const timeoutMs = 12000;
        const timer = setTimeout(() => controller.abort(), timeoutMs);

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }
        resetModalMessage();

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            });

            const data = await parseResponseData(response);
            if (!response.ok || !data || data.success !== true) {
                const message = data && data.error ? data.error : 'The server rejected the request.';
                showModalMessage(message, 'error');
                return;
            }

            showModalMessage('Saved successfully. Refreshing data...', 'success');
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            const message = error && error.name === 'AbortError'
                ? 'The server did not respond in time.'
                : 'Network error while saving the record.';
            showModalMessage(message, 'error');
        } finally {
            clearTimeout(timer);
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = previousText;
            }
        }
    }

    addForms.forEach(form => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitAddForm(form);
        });
    });

    function dispatchDelete(entityType, recordId) {
        if (!confirm(`Are you absolutely sure you want to delete this ${entityType}?`)) return;

        const targets = { 'event': '/admin/manage_event', 'shelter': '/admin/manage_shelter', 'route': '/admin/manage_route.php' };
        fetch(targets[entityType], {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': GLOBAL_CSRF_TOKEN
            },
            body: JSON.stringify({ id: recordId })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) window.location.reload();
                else alert('Operation failed: ' + (data.error || 'Unknown Error'));
            })
            .catch(() => alert('Network processing failure.'));
    }

    function dispatchUpdate(entityType, recordId, button) {
        const tr = button.closest('tr');
        const targets = { 'event': '/admin/manage_event', 'shelter': '/admin/manage_shelter', 'route': '/admin/manage_route.php' };
        let payload = { id: recordId };

        const inputs = tr.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const match = input.className.match(/field-(\w+)/);
            if (match && match[1]) {
                payload[match[1]] = input.value;
            }
        });

        fetch(targets[entityType], {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': GLOBAL_CSRF_TOKEN
            },
            body: JSON.stringify(payload)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) window.location.reload();
                else alert('Update aborted: ' + (data.error || 'Validation error'));
            })
            .catch(() => alert('Network update error.'));
    }

    const actionsMenuToggle = document.getElementById('actionsMenuToggle');
    const actionsMenuContent = document.getElementById('actionsMenuContent');
    if (actionsMenuToggle && actionsMenuContent) {
        actionsMenuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            actionsMenuContent.classList.toggle('show');
        });
        document.addEventListener('click', (e) => {
            if (!actionsMenuToggle.contains(e.target)) {
                actionsMenuContent.classList.remove('show');
            }
        });
    }
</script>

</body>
</html>
