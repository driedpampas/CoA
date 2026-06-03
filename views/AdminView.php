<?php
$currentPageKey = $activeTab === 'events' ? 'events_page' : 'shelters_page';
$currentSizeKey = $activeTab === 'events' ? 'events_size' : 'shelters_size';
$currentSearchKey = $activeTab === 'events' ? 'events_q' : 'shelters_q';
$currentSortKey = $activeTab === 'events' ? 'events_sort' : 'shelters_sort';
$currentDirKey = $activeTab === 'events' ? 'events_dir' : 'shelters_dir';
$currentSortField = $activeTab === 'events' ? $eventsSort : $sheltersSort;
$currentSortDir = $activeTab === 'events' ? $eventsSortDir : $sheltersSortDir;
$currentSearchValue = $activeTab === 'events' ? $eventsSearch : $sheltersSearch;
$currentPageSize = $activeTab === 'events' ? $eventsPageSize : $sheltersPageSize;
$currentEntityLabel = $activeTab === 'events' ? 'Disaster Event' : 'Shelter';
$currentEntityLabelPlural = $activeTab === 'events' ? 'Disaster Events' : 'Shelters';
$currentSearchPlaceholder = $activeTab === 'events'
    ? 'Search title or description'
    : 'Search name or address';

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

    <?php if ($successEvent || $errorEvent !== '' || $successShelter || $errorShelter !== ''): ?>
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
        </div>
    <?php endif; ?>

    <section class="admin-board panel">
        <div class="admin-tabs" role="tablist" aria-label="Admin datasets">
            <a id="events-tab" class="admin-tab <?= $activeTab === 'events' ? 'active' : '' ?>" href="<?= e(adminUrl(['tab' => 'events'])) ?>">Disaster Events</a>
            <a id="shelters-tab" class="admin-tab <?= $activeTab === 'shelters' ? 'active' : '' ?>" href="<?= e(adminUrl(['tab' => 'shelters'])) ?>">Shelters</a>
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
        addModalTitle.textContent = 'Add ' + (activeTab === 'events' ? 'Disaster Event' : 'Shelter');
        resetModalMessage();
        openModal(addModal);
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

    [addModal, descriptionModal].forEach(modal => {
        if (!modal) return;
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                if (modal === addModal) {
                    closeModal(addModal);
                } else {
                    closeDescriptionModal();
                }
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (addModal && !addModal.hidden) closeModal(addModal);
            if (descriptionModal && !descriptionModal.hidden) closeDescriptionModal();
        }
    });

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

        const targets = { 'event': '/admin/manage_event', 'shelter': '/admin/manage_shelter' };
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
        const targets = { 'event': '/admin/manage_event', 'shelter': '/admin/manage_shelter' };
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
</script>

</body>
</html>
