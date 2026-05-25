<?php

namespace Models;

use Resend;

class Email
{
    private static ?Resend\Client $client = null;

    private static function getClient(): Resend\Client
    {
        if (self::$client === null) {
            $config = require __DIR__ . '/../config/resend.php';
            self::$client = Resend::client($config['api_key']);
        }
        return self::$client;
    }

    private static function render(string $view, array $vars = []): string
    {
        extract($vars);
        ob_start();
        include __DIR__ . '/../views/emails/' . $view;
        return ob_get_clean();
    }

    public static function sendVerificationEmail(string $to, string $username, string $token): bool
    {
        $config = require __DIR__ . '/../config/resend.php';
        $url = $config['app_url'] . '/verify?token=' . urlencode($token);

        try {
            self::getClient()->emails->send([
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
        $config = require __DIR__ . '/../config/resend.php';
        $url = $config['app_url'] . '/reset-password?token=' . urlencode($token);

        try {
            self::getClient()->emails->send([
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
        $config = require __DIR__ . '/../config/resend.php';
        $loginUrl = $config['app_url'] . '/login';

        try {
            self::getClient()->emails->send([
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
