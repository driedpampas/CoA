<?php
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
    <p style="text-align: center;">Welcome, <strong><?= $username ?></strong>!</p>

    <div class="forms">
        <section class="panel">
            <h2>Report New Disaster</h2>

            <?php if ($successEvent): ?>
                <div class="msg success">Disaster event recorded successfully!</div>
            <?php endif; ?>

            <?php if ($errorEvent !== ''): ?>
                <div class="msg error"><?= $errorEvent ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/submit_event">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="form-row">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <?php foreach($eventTypeOptions as $opt): ?>
                            <option value="<?= $opt['value'] ?>"><?= $opt['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" required>
                </div>

                <div class="form-row">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <label for="severity">Severity</label>
                    <select id="severity" name="severity">
                        <?php foreach($severityOptions as $opt): ?>
                            <option value="<?= $opt['value'] ?>" <?= $opt['value'] === 'moderate' ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="latitude_e">Latitude (Optional)</label>
                    <input id="latitude_e" name="latitude" type="number" step="0.001">
                </div>

                <div class="form-row">
                    <label for="longitude_e">Longitude (Optional)</label>
                    <input id="longitude_e" name="longitude" type="number" step="0.001">
                </div>

                <div class="form-row">
                    <button type="submit">Report Event</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>Add New Shelter</h2>

            <?php if ($successShelter): ?>
                <div class="msg success">Shelter added successfully!</div>
            <?php endif; ?>

            <?php if ($errorShelter !== ''): ?>
                <div class="msg error"><?= $errorShelter ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/submit_shelter">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="form-row">
                    <label for="name">Shelter Name</label>
                    <input id="name" name="name" type="text" required>
                </div>

                <div class="form-row">
                    <label for="address">Address</label>
                    <input id="address" name="address" type="text" required>
                </div>

                <div class="form-row">
                    <label for="latitude_s">Latitude</label>
                    <input id="latitude_s" name="latitude" type="number" step="0.001" required>
                </div>

                <div class="form-row">
                    <label for="longitude_s">Longitude</label>
                    <input id="longitude_s" name="longitude" type="number" step="0.001" required>
                </div>

                <div class="form-row">
                    <label for="capacity">Capacity</label>
                    <input id="capacity" name="capacity" type="number" min="0" value="0">
                </div>

                <div class="form-row">
                    <label for="shelter_type">Type</label>
                    <select id="shelter_type" name="shelter_type">
                        <?php foreach($shelterTypeOptions as $opt): ?>
                            <option value="<?= $opt['value'] ?>"><?= $opt['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="contact_phone">Contact Phone</label>
                    <input id="contact_phone" name="contact_phone" type="text">
                </div>

                <div class="form-row">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <button type="submit">Add Shelter</button>
                </div>
            </form>
        </section>
    </div>

    <div class="management-tables">
        <section class="panel execution-panel">
            <h2>Manage Disaster Events</h2>
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$hasEvents): ?>
                        <tr><td colspan="8" style="text-align:center;">No events recorded.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($events as $e): ?>
                        <tr data-id="<?= $e['id'] ?>">
                            <td data-label="Type">
                                <span class="mode-view"><?= $e['event_type'] ?></span>
                                <select class="mode-edit field-event_type" style="display:none;">
                                    <?php foreach($eventTypeOptions as $opt): ?>
                                        <option value="<?= $opt['value'] ?>" <?= $e['event_type'] === $opt['value'] ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td data-label="Title">
                                <span class="mode-view"><?= $e['title'] ?></span>
                                <input type="text" class="mode-edit field-title" value="<?= $e['title'] ?>" style="display:none;">
                            </td>
                            <td data-label="Description">
                                <div class="desc-container">
                                    <div class="desc-text"><?= $e['description'] ?></div>
                                    <?php if (strlen($e['description']) > 50):?>
                                        <button class="toggle-btn" onclick="toggleDescription(this)">View More</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Severity">
                                <span class="mode-view"><?= $e['severity'] ?></span>
                                <select class="mode-edit field-severity" style="display:none;">
                                    <?php foreach($severityOptions as $opt): ?>
                                        <option value="<?= $opt['value'] ?>" <?= $e['severity'] === $opt['value'] ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td data-label="Status">
                                <span class="mode-view"><?= $e['status'] ?></span>
                                <select class="mode-edit field-status" style="display:none;">
                                    <?php foreach($eventStatusOptions as $opt): ?>
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
                            <td data-label="Actions">
                                <button class="btn-action-edit mode-view" onclick="toggleEditRow(this)">Edit</button>
                                <button class="btn-action-delete mode-view" onclick="dispatchDelete('event', <?= $e['id'] ?>)">Delete</button>

                                <button class="btn-action-save mode-edit" style="display:none;" onclick="dispatchUpdate('event', <?= $e['id'] ?>, this)">Save</button>
                                <button class="btn-action-cancel mode-edit" style="display:none;" onclick="toggleEditRow(this)">Cancel</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel execution-panel">
            <h2>Manage Shelter Locations</h2>
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Phone</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$hasShelters): ?>
                        <tr><td colspan="8" style="text-align:center;">No shelter locations setup.</td></tr>
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
                                    <?php foreach($shelterTypeOptions as $opt): ?>
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
                                    <?php foreach($shelterStatusOptions as $opt): ?>
                                        <option value="<?= $opt['value'] ?>" <?= $s['status'] === $opt['value'] ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td data-label="Phone">
                                <span class="mode-view"><?= $s['contact_phone'] ?></span>
                                <input type="text" class="mode-edit field-contact_phone" value="<?= $s['contact_phone'] ?>" style="display:none;">
                            </td>
                            <td data-label="Notes">
                                <span class="mode-view"><?= $s['notes'] ?></span>
                                <textarea class="mode-edit field-notes" style="display:none;"><?= $s['notes'] ?></textarea>
                            </td>
                            <td data-label="Actions">
                                <button class="btn-action-edit mode-view" onclick="toggleEditRow(this)">Edit</button>
                                <button class="btn-action-delete mode-view" onclick="dispatchDelete('shelter', <?= $s['id'] ?>)">Delete</button>

                                <button class="btn-action-save mode-edit" style="display:none;" onclick="dispatchUpdate('shelter', <?= $s['id'] ?>, this)">Save</button>
                                <button class="btn-action-cancel mode-edit" style="display:none;" onclick="toggleEditRow(this)">Cancel</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<script>
    const GLOBAL_CSRF_TOKEN = '<?= $csrf ?>';

    // Mobile Header Toggle
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

    // Interactive Rows Toggle
    function toggleEditRow(button) {
        const tr = button.closest('tr');
        const views = tr.querySelectorAll('.mode-view');
        const edits = tr.querySelectorAll('.mode-edit');
        const activeState = (edits[0].style.display === 'none');

        views.forEach(el => el.style.display = activeState ? 'none' : '');
        edits.forEach(el => el.style.display = activeState ? '' : 'none');
    }

    // Delete Request
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
                if(data.success) window.location.reload();
                else alert('Operation failed: ' + (data.error || 'Unknown Error'));
            })
            .catch(() => alert('Network processing failure.'));
    }

    // Update Request
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
                if(data.success) window.location.reload();
                else alert('Update aborted: ' + (data.error || 'Validation error'));
            })
            .catch(() => alert('Network update error.'));
    }

    // View More Toggle
    function toggleDescription(btn) {
        const container = btn.previousElementSibling;
        container.classList.toggle('expanded');

        if (container.classList.contains('expanded')) {
            btn.textContent = 'View Less';
        } else {
            btn.textContent = 'View More';
        }
    }
</script>

</body>
</html>
