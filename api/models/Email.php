<?php

namespace Models;

use Resend;

class Email
{
    private static ?Resend\Client $client = null;

    private static function getConfig(): ?array
    {
        $path = __DIR__ . '/../../config/resend.php';
        if (!is_file($path)) {
            return null;
        }

        $config = require $path;
        return is_array($config) ? $config : null;
    }

    private static function getClient(): ?Resend\Client
    {
        $config = self::getConfig();
        if ($config === null || empty($config['api_key'])) {
            return null;
        }

        if (self::$client === null) {
            self::$client = Resend::client($config['api_key']);
        }
        return self::$client;
    }

    private static function render(string $view, array $vars = []): string
    {
        extract($vars);
        ob_start();
        include __DIR__ . '/../../public/views/emails/' . $view;
        return ob_get_clean();
    }

    public static function sendVerificationEmail(string $to, string $username, string $token): bool
    {
        $config = self::getConfig();
        if ($config === null || empty($config['app_url']) || empty($config['from'])) {
            error_log('Resend verification email skipped: missing config.');
            return false;
        }

        $url = $config['app_url'] . '/verify?token=' . urlencode($token);
        $client = self::getClient();
        if ($client === null) {
            error_log('Resend verification email skipped: client unavailable.');
            return false;
        }

        try {
            $client->emails->send([
                'from' => $config['from'],
                'to' => $to,
                'subject' => 'Verify your COA account',
                'html' => self::render('verification.php', compact('username', 'url')),
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('Resend verification email error: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendPasswordResetEmail(string $to, string $username, string $token): bool
    {
        $config = self::getConfig();
        if ($config === null || empty($config['app_url']) || empty($config['from'])) {
            error_log('Resend password reset email skipped: missing config.');
            return false;
        }

        $url = $config['app_url'] . '/reset-password?token=' . urlencode($token);
        $client = self::getClient();
        if ($client === null) {
            error_log('Resend password reset email skipped: client unavailable.');
            return false;
        }

        try {
            $client->emails->send([
                'from' => $config['from'],
                'to' => $to,
                'subject' => 'Reset your COA password',
                'html' => self::render('reset-password.php', compact('username', 'url')),
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('Resend password reset email error: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendWelcomeEmail(string $to, string $username): bool
    {
        $config = self::getConfig();
        if ($config === null || empty($config['app_url']) || empty($config['from'])) {
            error_log('Resend welcome email skipped: missing config.');
            return false;
        }

        $loginUrl = $config['app_url'] . '/login';
        $client = self::getClient();
        if ($client === null) {
            error_log('Resend welcome email skipped: client unavailable.');
            return false;
        }

        try {
            $client->emails->send([
                'from' => $config['from'],
                'to' => $to,
                'subject' => 'Welcome to COA',
                'html' => self::render('welcome.php', compact('username', 'loginUrl')),
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('Resend welcome email error: ' . $e->getMessage());
            return false;
        }
    }
}
