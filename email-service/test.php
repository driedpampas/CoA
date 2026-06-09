<?php
// Test Script to verify the main app can send emails to the service

require_once __DIR__ . '/../vendor/autoload.php';

echo "Sending welcome email request via Email model...\n";
$ok = \Models\Email::sendWelcomeEmail('test@example.com', 'TestUser');

if ($ok) {
    echo "SUCCESS: Email request successfully forwarded to HTTP service and enqueued.\n";
} else {
    echo "FAILURE: Failed to send email request to HTTP service.\n";
    exit(1);
}
