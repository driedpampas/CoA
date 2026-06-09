<?php
// Database Helper for Dedicated Email Microservice Database (MySQL)

$host = 'localhost';
$user = 'root';
$pass = '';

// 1. Establish connection to MySQL server
$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'MySQL connection failed: ' . $mysqli->connect_error]);
    exit;
}

// 2. Ensure the dedicated coa_emails database exists
if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS coa_emails")) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to create database coa_emails: ' . $mysqli->error]);
    exit;
}

// 3. Select the dedicated database
if (!$mysqli->select_db('coa_emails')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to select database coa_emails: ' . $mysqli->error]);
    exit;
}

// 4. Ensure the jobs table exists
$tableQuery = "CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template VARCHAR(64) NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jobs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$mysqli->query($tableQuery)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to create jobs table: ' . $mysqli->error]);
    exit;
}

return $mysqli;
