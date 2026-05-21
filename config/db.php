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
    die('Conexiunea a esuat...');
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
        $stmt = $mysql->prepare("INSERT INTO auth (user, pass, email) VALUES (?, ?, ?)");
        if ($stmt) {
            $admin = 'admin';
            $email = 'admin@coa.local';
            $stmt->bind_param('sss', $admin, $defaultPassword, $email);
            $stmt->execute();
            $stmt->close();
        }
    }
}
