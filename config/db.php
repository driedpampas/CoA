<?php

$hostname = "localhost";
$username = "root";
$password = "";
$database = "coa";

$mysql = new mysqli(
    $hostname,
    $username,
    $password,
    $database
);

if (mysqli_connect_errno()) {
    die('Connection failed...');
}

$result = $mysql->query("SHOW TABLES");
if ($result && $result->num_rows === 0) {
    $schema = file_get_contents(__DIR__ . '/../schema/emergencies.sql');
    if ($schema !== false) {
        $mysql->multi_query($schema);
        while ($mysql->next_result()) {
            $mysql->store_result();
        }

        $defaultPassword = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $mysql->prepare("INSERT INTO auth (user, pass, email, email_verified) VALUES (?, ?, ?, 1)");
        if ($stmt) {
            $admin = 'admin';
            $email = 'admin@coa.local';
            $stmt->bind_param('sss', $admin, $defaultPassword, $email);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Ensure role column exists and seed admin role
$colRes = $mysql->query("SHOW COLUMNS FROM auth LIKE 'role'");
if ($colRes && $colRes->num_rows === 0) {
    $mysql->query("ALTER TABLE auth ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'user'");
}
// Ensure admin user has admin role (idempotent)
$mysql->query("UPDATE auth SET role='admin' WHERE user='admin'");

// Ensure numeric user ids exist for per-user notifications
$userIdCol = $mysql->query("SHOW COLUMNS FROM auth LIKE 'id'");
if ($userIdCol && $userIdCol->num_rows === 0) {
    $mysql->query("ALTER TABLE auth ADD COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE FIRST");
}

// Ensure user location fields exist for area-targeted notifications
$locationLatCol = $mysql->query("SHOW COLUMNS FROM auth LIKE 'last_latitude'");
if ($locationLatCol && $locationLatCol->num_rows === 0) {
    $mysql->query("ALTER TABLE auth ADD COLUMN last_latitude DECIMAL(10, 7) DEFAULT NULL");
    $mysql->query("ALTER TABLE auth ADD COLUMN last_longitude DECIMAL(10, 7) DEFAULT NULL");
    $mysql->query("ALTER TABLE auth ADD COLUMN last_location_updated_at TIMESTAMP NULL DEFAULT NULL");
}

// Ensure email verification and password reset columns exist
$emailVerifiedCol = $mysql->query("SHOW COLUMNS FROM auth LIKE 'email_verified'");
if ($emailVerifiedCol && $emailVerifiedCol->num_rows === 0) {
    $mysql->query("ALTER TABLE auth ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0");
    $mysql->query("ALTER TABLE auth ADD COLUMN verification_token VARCHAR(64) DEFAULT NULL");
    $mysql->query("ALTER TABLE auth ADD COLUMN verification_expires DATETIME DEFAULT NULL");
    $mysql->query("ALTER TABLE auth ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL");
    $mysql->query("ALTER TABLE auth ADD COLUMN reset_expires DATETIME DEFAULT NULL");
    $mysql->query("ALTER TABLE auth ADD INDEX idx_verification_token (verification_token)");
    $mysql->query("ALTER TABLE auth ADD INDEX idx_reset_token (reset_token)");
    // Mark existing admin as verified
    $mysql->query("UPDATE auth SET email_verified = 1 WHERE user = 'admin'");
}

// Ensure notifications point at individual users instead of a global inbox.
$notificationsUserCol = $mysql->query("SHOW COLUMNS FROM notifications LIKE 'user_id'");
if ($notificationsUserCol && $notificationsUserCol->num_rows === 0) {
    $mysql->query("ALTER TABLE notifications ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER id");
    $mysql->query("ALTER TABLE notifications ADD INDEX idx_notifications_user (user_id)");
    $mysql->query("ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES auth(id) ON UPDATE CASCADE ON DELETE CASCADE");
}

$uaicCheck = $mysql->query("SELECT id FROM shelters WHERE name = 'Universitatea Alexandru Ioan Cuza' LIMIT 1");
if ($uaicCheck && $uaicCheck->num_rows === 0) {
    $mysql->query("INSERT INTO shelters (name, address, latitude, longitude, geom_point, capacity, current_occupancy, shelter_type, status, contact_phone, notes)
        VALUES ('Universitatea Alexandru Ioan Cuza', 'Bulevardul Carol I 11, Iasi', 47.1756, 27.5768, ST_GeomFromText('POINT(27.5768 47.1756)', 4326), 800, 0, 'school', 'open', '0232-201-201', 'University campus shelter, main building')");
}


$profileBioCol = $mysql->query("SHOW COLUMNS FROM auth LIKE 'bio'");
if ($profileBioCol && $profileBioCol->num_rows === 0) {
    $mysql->query("ALTER TABLE auth ADD COLUMN bio TEXT DEFAULT NULL AFTER email");
}

$profileRadiusCol = $mysql->query("SHOW COLUMNS FROM auth LIKE 'notification_radius_km'");
if ($profileRadiusCol && $profileRadiusCol->num_rows === 0) {
    $mysql->query("ALTER TABLE auth ADD COLUMN notification_radius_km INT UNSIGNED NOT NULL DEFAULT 25 AFTER bio");
}

$profileShelterCol = $mysql->query("SHOW COLUMNS FROM auth LIKE 'preferred_shelter_id'");
if ($profileShelterCol && $profileShelterCol->num_rows === 0) {
    $mysql->query("ALTER TABLE auth ADD COLUMN preferred_shelter_id INT UNSIGNED DEFAULT NULL AFTER notification_radius_km");
    $mysql->query("ALTER TABLE auth ADD CONSTRAINT fk_preferred_shelter FOREIGN KEY (preferred_shelter_id) REFERENCES shelters(id) ON UPDATE CASCADE ON DELETE SET NULL");
}
