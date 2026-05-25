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

$notifTable = $mysql->query("SHOW TABLES LIKE 'notifications'");
if ($notifTable && $notifTable->num_rows === 0) {
    $mysql->query("CREATE TABLE IF NOT EXISTS notifications (
        id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
        title           VARCHAR(255)        NOT NULL,
        message         TEXT                NOT NULL,
        type            ENUM('event', 'shelter', 'system') NOT NULL DEFAULT 'system',
        severity        ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'info',
        reference_id    INT UNSIGNED        DEFAULT NULL,
        is_read         TINYINT(1)          NOT NULL DEFAULT 0,
        created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_notifications_read (is_read),
        INDEX idx_notifications_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$uaicCheck = $mysql->query("SELECT id FROM shelters WHERE name = 'Universitatea Alexandru Ioan Cuza' LIMIT 1");
if ($uaicCheck && $uaicCheck->num_rows === 0) {
    $mysql->query("INSERT INTO shelters (name, address, latitude, longitude, geom_point, capacity, current_occupancy, shelter_type, status, contact_phone, notes)
        VALUES ('Universitatea Alexandru Ioan Cuza', 'Bulevardul Carol I 11, Iasi', 47.1756, 27.5768, ST_GeomFromText('POINT(27.5768 47.1756)', 4326), 800, 0, 'school', 'open', '0232-201-201', 'University campus shelter, main building')");
}

$earthquakeCheck = $mysql->query("SELECT id FROM emergency_events WHERE title = 'Earthquake near Holboca' LIMIT 1");
if ($earthquakeCheck && $earthquakeCheck->num_rows === 0) {
    $mysql->query("INSERT INTO emergency_events (event_type, title, description, severity, latitude, longitude, status, started_at)
        VALUES ('earthquake', 'Earthquake near Holboca', 'Magnitude 5.2 earthquake detected near Holboca, approximately 20km northeast of Iasi city center. Tremors felt across the entire metropolitan area. Buildings inspected for structural damage.', 'extreme', 47.3500, 27.6500, 'active', DATE_SUB(NOW(), INTERVAL 15 MINUTE))");
}
