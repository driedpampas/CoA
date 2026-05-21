<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COA - Panou Public</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <header class="dashboard-header">
        <h1>COA Emergency Dashboard</h1>
        <nav>
            <a href="index.php?page=dashboard">Dashboard</a>
            <a href="index.php?page=cap-feed" target="_blank">CAP Feed</a>
        </nav>
    </header>

    <main class="dashboard-main">
        <div class="dashboard-content">
            <div class="sidebar">
                <section class="panel">
                    <h2>Active Events</h2>
                    <?php if (empty($events)): ?>
                        <p class="empty-state">No active emergencies.</p>
                    <?php else: ?>
                        <ul class="event-list">
                            <?php foreach ($events as $event): ?>
                                <li class="event-item severity-<?php echo htmlspecialchars($event['severity']); ?>"
                                    data-lat="<?php echo htmlspecialchars($event['latitude']); ?>"
                                    data-lng="<?php echo htmlspecialchars($event['longitude']); ?>">
                                    <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                    <span class="badge badge-<?php echo htmlspecialchars($event['event_type']); ?>">
                                        <?php echo htmlspecialchars($event['event_type']); ?>
                                    </span>
                                    <span class="badge badge-severity-<?php echo htmlspecialchars($event['severity']); ?>">
                                        <?php echo htmlspecialchars($event['severity']); ?>
                                    </span>
                                    <small><?php echo htmlspecialchars($event['started_at']); ?></small>
                                    <p><?php echo htmlspecialchars(mb_strimwidth($event['description'] ?? '', 0, 100, '...')); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="panel">
                    <h2>Nearby Shelters</h2>
                    <div class="location-controls">
                        <button id="locateBtn" class="btn">Find nearest shelters</button>
                        <span id="locationStatus" class="status-text"></span>
                    </div>
                    <div id="shelterList" class="shelter-list">
                        <?php foreach ($shelters as $shelter): ?>
                            <div class="shelter-item"
                                 data-lat="<?php echo htmlspecialchars($shelter['latitude']); ?>"
                                 data-lng="<?php echo htmlspecialchars($shelter['longitude']); ?>"
                                 data-id="<?php echo htmlspecialchars($shelter['id']); ?>">
                                <strong><?php echo htmlspecialchars($shelter['name']); ?></strong>
                                <span class="badge badge-status-<?php echo htmlspecialchars($shelter['status']); ?>">
                                    <?php echo htmlspecialchars($shelter['status']); ?>
                                </span>
                                <small><?php echo htmlspecialchars($shelter['address']); ?></small>
                                <small>Capacity: <?php echo htmlspecialchars($shelter['current_occupancy'] . ' / ' . $shelter['capacity']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="map-container">
                <div id="map"></div>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var eventsData = <?php echo json_encode($events, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        var sheltersData = <?php echo json_encode($shelters, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    </script>
    <script src="dashboard.js"></script>
</body>

</html>
