<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

//redirecting to the login page if not logged in
if (!($_SESSION['isLoggedIn'] ?? false)) {
    header('Location: /login');
    exit;
}

//did this to not have to logout then log back in for changes to apply
if (($_SESSION['role'] ?? '') !== 'admin') {
    try {
        $userModel = new \Models\Account($mysql);
        $role = $userModel->getRole($_SESSION['username'] ?? '');
        $_SESSION['role'] = $role ?? 'user';
    } catch (\Throwable $e) {
        // ignore and treat as non-admin
        $_SESSION['role'] = $_SESSION['role'] ?? 'user';
    }
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden: admin access required.';
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$successEvent = isset($_GET['success_event']);
$errorEvent = isset($_GET['error_event']) ? htmlspecialchars(urldecode($_GET['error_event'])) : '';
$successShelter = isset($_GET['success_shelter']);
$errorShelter = isset($_GET['error_shelter']) ? htmlspecialchars(urldecode($_GET['error_shelter'])) : '';
$username = $_SESSION['username'] ?? '';

include __DIR__ . '/../views/AdminView.php';
