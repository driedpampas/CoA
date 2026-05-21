<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <header class="dashboard-header">
        <h1>Dashboard</h1>
        <nav>
            <a href="dashboard">Dashboard</a>
            <a href="cap-feed" target="_blank">CAP Feed</a>
            <?php if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            } ?>
            <?php if (!empty($_SESSION["isLoggedIn"])): ?>
                <a class="logged-in" href="login">Logged in as <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <?php else: ?>
                <a href="login">Login</a>
            <?php endif; ?>
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
                                    <p><?php echo htmlspecialchars(mb_strimwidth($event['description'] ?? '', 0, 100, '...')); ?>
                                    </p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="panel">
                    <h2>Nearby Shelters</h2>
                    <div class="location-controls">
                        <button id="locateBtn" class="btn">Gaseste adaposturi</button>
                        <span id="locationStatus" class="status-text">Se detecteaza locatia...</span>
                    </div>
                    <div id="shelterList" class="shelter-list">
                        <?php foreach ($shelters as $shelter): ?>
                            <div class="shelter-item" data-lat="<?php echo htmlspecialchars($shelter['latitude']); ?>"
                                data-lng="<?php echo htmlspecialchars($shelter['longitude']); ?>"
                                data-id="<?php echo htmlspecialchars($shelter['id']); ?>">
                                <strong><?php echo htmlspecialchars($shelter['name']); ?></strong>
                                <span class="badge badge-status-<?php echo htmlspecialchars($shelter['status']); ?>">
                                    <?php echo htmlspecialchars($shelter['status']); ?>
                                </span>
                                <small><?php echo htmlspecialchars($shelter['address']); ?></small>
                                <small>Capacity:
                                    <?php echo htmlspecialchars($shelter['current_occupancy'] . ' / ' . $shelter['capacity']); ?></small>
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