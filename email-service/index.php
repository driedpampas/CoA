<?php

$mysqli = require_once __DIR__ . '/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed. Only POST is supported.']);
    exit;
}

// Read and decode JSON payload
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit;
}

$template = $input['template'] ?? '';
$to = $input['to'] ?? '';
$vars = $input['vars'] ?? [];

// Validate fields
if (empty($template) || empty($to) || !is_array($vars)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields: template, to, or vars.']);
    exit;
}

if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid email address.']);
    exit;
}

$validTemplates = ['verification', 'reset-password', 'welcome'];
if (!in_array($template, $validTemplates, true)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid template. Supported templates: ' . implode(', ', $validTemplates)]);
    exit;
}

// Insert the job into the queue
$stmt = $mysqli->prepare("INSERT INTO jobs (template, to_email, payload) VALUES (?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database prepare statement failed: ' . $mysqli->error]);
    exit;
}

$payloadJson = json_encode($vars);
$stmt->bind_param('sss', $template, $to, $payloadJson);

if ($stmt->execute()) {
    $jobId = $mysqli->insert_id;
    $stmt->close();

    http_response_code(202); // Accepted
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'queued',
        'id' => $jobId,
        'message' => 'Email enqueued successfully.'
    ]);
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database execution failed: ' . $stmt->error]);
    $stmt->close();
}
