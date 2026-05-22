<?php
// Expects: $csrf, $successEvent, $errorEvent, $successShelter, $errorShelter, $username
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

<main class="admin-container">
    <h1>Admin Dashboard</h1>
    <p style="text-align: center;">Welcome, <strong><?= htmlspecialchars($username) ?></strong>!</p>

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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-row">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="earthquake">Earthquake</option>
                        <option value="flood">Flood</option>
                        <option value="fire">Fire</option>
                        <option value="storm">Storm</option>
                        <option value="other">Other</option>
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
                        <option value="low">Low</option>
                        <option value="moderate" selected>Moderate</option>
                        <option value="high">High</option>
                        <option value="extreme">Extreme</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="latitude_e">Latitude (Optional)</label>
                    <input id="latitude_e" name="latitude" type="number" step="0.000001">
                </div>

                <div class="form-row">
                    <label for="longitude_e">Longitude (Optional)</label>
                    <input id="longitude_e" name="longitude" type="number" step="0.000001">
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

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
                    <input id="latitude_s" name="latitude" type="number" step="0.000001" required>
                </div>

                <div class="form-row">
                    <label for="longitude_s">Longitude</label>
                    <input id="longitude_s" name="longitude" type="number" step="0.000001" required>
                </div>

                <div class="form-row">
                    <label for="capacity">Capacity</label>
                    <input id="capacity" name="capacity" type="number" min="0" value="0">
                </div>

                <div class="form-row">
                    <label for="shelter_type">Type</label>
                    <select id="shelter_type" name="shelter_type">
                        <option value="community">Community</option>
                        <option value="school">School</option>
                        <option value="stadium">Stadium</option>
                        <option value="military">Military</option>
                        <option value="other">Other</option>
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
</main>

</body>
</html>