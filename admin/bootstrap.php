<?php

class Bootstrap
{
    public static function boot(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!($_SESSION['isLoggedIn'] ?? false)) {
            header('Location: /login');
            exit;
        }

        // Lazy role backfill for sessions created before role was stored
        if (!isset($_SESSION['role'])) {
            try {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $userModel = new \ClientModels\Account($protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api');
                $_SESSION['role'] = $userModel->getRole($_SESSION['username'] ?? '') ?? 'user';
            } catch (\Throwable $e) {
                $_SESSION['role'] = 'user';
            }
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo 'Forbidden: admin access required.';
            exit;
        }

        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET' || $method === 'HEAD') {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
    }
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminUrl(array $overrides = []): string
{
    return '/admin?' . http_build_query(array_merge(AdminController::$queryBase ?? [], $overrides));
}

