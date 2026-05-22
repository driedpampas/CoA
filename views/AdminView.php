<?php
// Expects: $csrf, $successEvent, $errorEvent, $successShelter, $errorShelter, $username
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/public/dashboard.css">
    <style>
        .admin-container {max-width:1100px;margin:24px auto;padding:16px}
        .forms {display:flex;gap:24px;flex-wrap:wrap}
        .panel {flex:1;min-width:320px;background:#fff;padding:16px;border:1px solid #e6e6e6;border-radius:6px}
        .panel h2{margin-top:0}
        .form-row{margin-bottom:12px}
        label{display:block;margin-bottom:4px;font-weight:600}
        input[type=text], input[type=number], textarea, select{width:100%;padding:8px;border:1px solid #ccc;border-radius:4px}
        .msg{padding:8px;border-radius:4px;margin-bottom:12px}
        .msg.success{background:#e6ffed;border:1px solid #b2f0c4}
        .msg.error{background:#ffe6e6;border:1px solid #f0b2b2}
    </style>
</head>
<body>
<header style="padding:12px 24px;background:#222;color:#fff;display:flex;justify-content:space-between;align-items:center">
    <h1 style="margin:0;font-size:18px">Admin Dashboard</h1>
    <div>Logged in as <?php echo htmlspecialchars($username); ?> | <a href="/logout" style="color:#fff;">Logout</a></div>
</header>

<main class="admin-container">
    <div class="forms">
        <section class="panel">
            <h2>Report New Disaster</h2>

            <?php if ($successEvent): ?>
                <div class="msg success">Event reported successfully.</div>
            <?php endif; ?>
            <?php if ($errorEvent): ?>
                <div class="msg error"><?php echo $errorEvent; ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/submit_event">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                <div class="form-row">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type" required>
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
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>

                <div class="form-row">
                    <label for="severity">Severity</label>
                    <select id="severity" name="severity" required>
                        <option value="low">Low</option>
                        <option value="moderate" selected>Moderate</option>
                        <option value="high">High</option>
                        <option value="extreme">Extreme</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="number" step="0.000001">
                </div>

                <div class="form-row">
                    <label for="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="number" step="0.000001">
                </div>

                <div class="form-row">
                    <button type="submit">Report Disaster</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>Add New Shelter</h2>

            <?php if ($successShelter): ?>
                <div class="msg success">Shelter added successfully.</div>
            <?php endif; ?>
            <?php if ($errorShelter): ?>
                <div class="msg error"><?php echo $errorShelter; ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/submit_shelter">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                <div class="form-row">
                    <label for="name">Name</label>
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