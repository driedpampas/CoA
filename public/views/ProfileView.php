<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <meta name="color-scheme" content="light dark">
    <script>
        {
            const theme = localStorage.getItem("theme") || "system";
            if (theme === "dark" || (theme === "system" && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
                document.documentElement.setAttribute("data-theme", "dark");
            } else {
                document.documentElement.setAttribute("data-theme", "light");
            }
        }
    </script>
    <link rel="stylesheet" href="dashboard.css">
    <script src="theme.js" defer></script>
</head>
<body>
    <header class="dashboard-header">
        <div class="header-top">
            <div class="header-brand">
                <h1>My Profile</h1>
                <button id="themeToggle" class="theme-toggle" aria-label="Toggle Theme">
                    <svg class="sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <svg class="moon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>
            </div>
            <button id="menuToggle" class="menu-toggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <nav id="headerNav">

            <a href="dashboard">Dashboard</a>
            <?php if (!empty($isAdmin)): ?>
                <a href="admin">Admin</a>
            <?php endif; ?>
            <?php if ($isLoggedIn): ?>
                <a href="logout">Logout</a>
            <?php else: ?>
                <a href="login">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="dashboard-main">
        <div class="profile-container">
            <div class="profile-header">
                <h1>Profile Settings</h1>
                <p>Manage your personal information and notification preferences.</p>
            </div>

            <?php if ($shelterStatus && ($shelterStatus['status'] === 'full' || $shelterStatus['status'] === 'closed')): ?>
                <div class="notice notice-critical">
                    <strong>Your preferred shelter (<?php echo htmlspecialchars($shelterStatus['name']); ?>) is currently <?php echo htmlspecialchars($shelterStatus['status']); ?>.</strong>
                    <?php if ($preferredShelterDistance !== null): ?>
                        <p style="margin-top:0.5rem;">It is approx. <?php echo htmlspecialchars(number_format($preferredShelterDistance, 1)); ?> km away from your last known location.</p>
                    <?php endif; ?>
                    <?php if ($fallbackShelter): ?>
                        <p style="margin-top:0.5rem;">
                            nearest open alternative: <strong><?php echo htmlspecialchars($fallbackShelter['name']); ?></strong>
                            (<?php echo htmlspecialchars($fallbackShelter['address']); ?>)
                            — approx. <?php echo htmlspecialchars(number_format($fallbackDistance, 1)); ?> km away,
                            capacity <?php echo htmlspecialchars($fallbackShelter['current_occupancy'] . ' / ' . $fallbackShelter['capacity']); ?>.
                        </p>
                    <?php else: ?>
                        <p style="margin-top:0.5rem;">No open shelters found nearby. Please try again later.</p>
                    <?php endif; ?>
                </div>
            <?php elseif ($shelterStatus && $shelterStatus['status'] === 'open'): ?>
                <div class="notice" style="background:#e8f5e9;border-left-color:#2e7d32;color:#1b5e20;">
                    Preferred shelter: <strong><?php echo htmlspecialchars($shelterStatus['name']); ?></strong> — open (capacity <?php echo htmlspecialchars($shelterStatus['current_occupancy'] . ' / ' . $shelterStatus['capacity']); ?>).
                    <?php if ($preferredShelterDistance !== null): ?>
                        approx. <?php echo htmlspecialchars(number_format($preferredShelterDistance, 1)); ?> km away from your last known location.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($profile): ?>
                <div class="profile-readonly">
                    <strong>Username:</strong> <?php echo htmlspecialchars($profile['user']); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?><br>
                    <strong>Role:</strong> <?php echo htmlspecialchars($profile['role']); ?>
                </div>
            <?php endif; ?>

            <form id="profileForm">
                <div class="form-group">
                    <label for="bio">About Me</label>
                    <textarea id="bio" name="bio" placeholder="Tell us a bit about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="notification_radius_km">Notification radius (km)</label>
                    <input type="number" id="notification_radius_km" name="notification_radius_km"
                           value="<?php echo htmlspecialchars($profile['notification_radius_km'] ?? 25); ?>"
                           min="1" max="500" step="1">
                    <div class="hint">Only disasters within this distance will be shown on the dashboard.</div>
                </div>

                <div class="form-group">
                    <label for="preferred_shelter_id">Preferred shelter</label>
                    <select id="preferred_shelter_id" name="preferred_shelter_id">
                        <option value="">— None —</option>
                        <?php foreach ($sheltersForDropdown as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['id']); ?>"
                                <?php echo (!empty($profile['preferred_shelter_id']) && (int) $profile['preferred_shelter_id'] === (int) $s['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?> (<?php echo htmlspecialchars($s['status']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn">Save changes</button>
                    <a href="dashboard" class="btn" style="background:#9e9e9e;text-decoration:none;display:inline-flex;align-items:center;">Cancel</a>
                </div>
            </form>

            <p id="profileStatus" style="margin-top:1rem;font-size:0.9rem;"></p>
        </div>
    </main>

    <script>
        var profileInitial = <?php echo json_encode($profile, JSON_HEX_TAG | JSON_HEX_AMP); ?>;

        document.getElementById('profileForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var statusEl = document.getElementById('profileStatus');
            statusEl.textContent = 'Saving...';
            statusEl.style.color = '#1565c0';

            fetch('api/auth/profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bio: document.getElementById('bio').value,
                    notification_radius_km: document.getElementById('notification_radius_km').value,
                    preferred_shelter_id: document.getElementById('preferred_shelter_id').value || null,
                }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) {
                        statusEl.textContent = 'Error: ' + data.error;
                        statusEl.style.color = '#c62828';
                        return;
                    }
                    statusEl.textContent = 'Saved.';
                    statusEl.style.color = '#2e7d32';
                    if (data.profile) {
                        profileInitial = data.profile;
                    }
                })
                .catch(function () {
                    statusEl.textContent = 'Error saving profile.';
                    statusEl.style.color = '#c62828';
                });
        });

        var menuToggle = document.getElementById('menuToggle');
        var headerNav = document.getElementById('headerNav');
        if (menuToggle && headerNav) {
            menuToggle.addEventListener('click', function () {
                menuToggle.classList.toggle('open');
                headerNav.classList.toggle('open');
            });
            headerNav.addEventListener('click', function (e) {
                if (e.target.tagName === 'A') {
                    menuToggle.classList.remove('open');
                    headerNav.classList.remove('open');
                }
            });
        }
    </script>
</body>
</html>
