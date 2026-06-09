<?php

require_once __DIR__ . '/../vendor/autoload.php';

$configPath = __DIR__ . '/config/resend.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Error: Config file not found at {$configPath}\n");
    exit(1);
}
$config = require $configPath;

$mysqli = require_once __DIR__ . '/db.php';

echo "Email worker started. Polling MySQL coa_emails queue...\n";

$subjects = [
    'verification' => 'Verify your COA account',
    'reset-password' => 'Reset your COA password',
    'welcome' => 'Welcome to COA',
];

while (true) {
    try {
        // Query for next pending or failed job with attempts < 3
        $stmt = $mysqli->prepare("SELECT * FROM jobs 
            WHERE status = 'pending' 
               OR (status = 'failed' AND attempts < 3) 
            ORDER BY id ASC LIMIT 1");

        if (!$stmt) {
            throw new Exception("Failed to prepare fetch statement: " . $mysqli->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $job = $result->fetch_assoc();
        $stmt->close();

        if (!$job) {
            sleep(2);
            continue;
        }

        $jobId = (int) $job['id'];
        $template = $job['template'];
        $to = $job['to_email'];
        $vars = json_decode($job['payload'], true) ?: [];

        // Mark as processing and increment attempts
        $updateStmt = $mysqli->prepare("UPDATE jobs SET status = 'processing', attempts = attempts + 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception("Failed to prepare update statement: " . $mysqli->error);
        }
        $updateStmt->bind_param('i', $jobId);
        $updateStmt->execute();
        $updateStmt->close();

        echo "[" . date('Y-m-d H:i:s') . "] Processing Job #{$jobId} (Template: {$template}, To: {$to})\n";

        // Render template
        $templatePath = __DIR__ . "/templates/{$template}.php";
        if (!is_file($templatePath)) {
            throw new Exception("Template file not found at: {$templatePath}");
        }

        // Construct template URLs dynamically using config
        $appUrl = rtrim($config['app_url'] ?? 'http://coa.local', '/');
        if ($template === 'verification') {
            $token = $vars['token'] ?? '';
            $vars['url'] = $appUrl . '/verify?token=' . urlencode($token);
        } elseif ($template === 'reset-password') {
            $token = $vars['token'] ?? '';
            $vars['url'] = $appUrl . '/reset-password?token=' . urlencode($token);
        } elseif ($template === 'welcome') {
            $vars['loginUrl'] = $appUrl . '/login';
        }

        extract($vars);
        ob_start();
        include $templatePath;
        $html = ob_get_clean();

        // Initialize Resend Client
        if (empty($config['api_key'])) {
            throw new Exception("Resend API key is missing from configuration.");
        }

        $client = Resend::client($config['api_key']);
        $subject = $subjects[$template] ?? 'Notification from COA';

        // Send email
        $client->emails->send([
            'from' => $config['from'] ?? 'COA <noreply@syu.nl.eu.org>',
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
        ]);

        // Mark as completed
        $successStmt = $mysqli->prepare("UPDATE jobs SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if ($successStmt) {
            $successStmt->bind_param('i', $jobId);
            $successStmt->execute();
            $successStmt->close();
        }

        echo "[" . date('Y-m-d H:i:s') . "] Job #{$jobId} completed successfully.\n";

    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
        fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] Job #{$jobId} failed: {$errorMsg}\n");

        if (isset($jobId)) {
            try {
                $failStmt = $mysqli->prepare("UPDATE jobs SET status = 'failed', last_error = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                if ($failStmt) {
                    $failStmt->bind_param('si', $errorMsg, $jobId);
                    $failStmt->execute();
                    $failStmt->close();
                }
            } catch (Exception $dbEx) {
                fwrite(STDERR, "Database error during fail-logging: {$dbEx->getMessage()}\n");
            }
        }
        sleep(2);
    }
}
