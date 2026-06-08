<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <header class="dashboard-header">
        <div class="header-top">
            <div class="header-brand">
                <h1>Dashboard</h1>
                <div class="notification-wrapper">
                    <button id="notificationBell" class="notification-bell" aria-label="Notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span id="notificationBadge" class="notification-badge<?php echo $unreadCount > 0 ? '' : ' hidden'; ?>"><?php echo $unreadCount; ?></span>
                    </button>
                    <div id="notificationDropdown" class="notification-dropdown hidden">
                        <div class="notification-header">
                            <strong>Notifications</strong>
                            <button id="markAllRead" class="mark-all-read">Mark all read</button>
                        </div>
                        <div id="notificationList" class="notification-list">
                            <p class="empty-state">No notifications.</p>
                        </div>
                    </div>
                </div>
            </div>
            <button id="menuToggle" class="menu-toggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <nav id="headerNav">
            <a href="cap-feed" target="_blank">CAP Feed</a>
            <?php if (!empty($isAdmin)): ?>
                <a href="admin">Admin</a>
            <?php endif; ?>
            <?php if ($isLoggedIn): ?>
                <a class="logged-in" href="profile"><?php echo htmlspecialchars($username); ?></a>
                <a href="logout">Logout</a>
            <?php else: ?>
                <a href="login">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="dashboard-main">
        <div class="dashboard-content">
            <div class="sidebar">
                <div class="sidebar-tabs">
                    <button class="sidebar-tab active" data-target="panel-events">Events</button>
                    <button class="sidebar-tab" data-target="shelterPanel">Shelters</button>
                    <button class="sidebar-tab" data-target="routesPanel">Routes</button>
                </div>
                <section class="panel" id="shelterPanel" style="display:none;">
                    <h2>Nearby Shelters</h2>
                    <div class="location-controls">
                        <button id="locateBtn" class="btn">Find shelters</button>
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
                                <span class="badge badge-type-<?php echo htmlspecialchars($shelter['shelter_type']); ?>">
                                    <?php echo htmlspecialchars($shelter['shelter_type']); ?>
                                </span>
                                <small><?php echo htmlspecialchars($shelter['address']); ?></small>
                                <small>Capacity:
                                    <?php echo htmlspecialchars($shelter['current_occupancy'] . ' / ' . $shelter['capacity']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="panel" id="routesPanel" style="display:none;">
                    <h2>Evacuation Routes</h2>
                    <div id="routeList" class="route-list">
                        <p class="empty-state">Detect your location to see evacuation routes.</p>
                    </div>
                </section>

                <section class="panel" id="panel-events">
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
            </div>

            <div class="map-container">
                <div id="map"></div>
                <div id="layerControls" class="layer-controls">
                    <button id="layerToggle" class="layer-toggle" type="button">Layers ▾</button>
                    <div id="layerContent" class="layer-content">
                        <label><input type="checkbox" id="toggleEvents" checked> Events</label>
                        <label><input type="checkbox" id="toggleShelters" checked> Shelters</label>
                        <label><input type="checkbox" id="toggleUser" checked> My Location</label>
                        <label><input type="checkbox" id="toggleRoutes" checked> Routes</label>
                        <label class="map-window-control" for="eventWindowDays">
                            <span>Time range</span>
                            <input id="eventWindowDays" type="number" min="1" max="30" step="1" value="1">
                            <small>days back, up to 30</small>
                        </label>
                    </div>
                </div>
                <button id="centerOnMe" class="map-btn center-on-me-btn" title="Center on my location">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M12 2v4M12 18v4M2 12h4M18 12h4" />
                    </svg>
                </button>
                <div id="locationBanner" class="location-banner location-banner--hidden">
                    <span id="locationSpinner" class="location-spinner"></span>
                    <span id="locationBannerText">Obtaining location...</span>
                    <button class="location-banner-close"
                        onclick="document.querySelector('#locationBanner').classList.add('location-banner--hidden')">&times;</button>
                </div>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var eventsData = <?php echo json_encode($events, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        var sheltersData = <?php echo json_encode($shelters, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        var unreadNotificationCount = <?php echo (int) $unreadCount; ?>;
        var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        var currentUserId = <?php echo $currentUserId !== null ? (int) $currentUserId : 'null'; ?>;
        var profileRadius = <?php echo $profileRadius !== null ? (int) $profileRadius : 'null'; ?>;
        var preferredShelterStatus = <?php echo json_encode($preferredShelterStatus); ?>;
        var fallbackShelter = <?php echo json_encode($fallbackShelter); ?>;
        var fallbackDistance = <?php echo $fallbackDistance !== null ? json_encode($fallbackDistance) : 'null'; ?>;
    </script>
    <script src="dashboard.js"></script>
</body>

</html>
