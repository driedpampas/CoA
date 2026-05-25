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
