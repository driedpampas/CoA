<?php

namespace Models;

class Email
{
    private static function sendToService(string $template, string $to, array $vars): bool
    {
        $serviceUrl = getenv('EMAIL_SERVICE_URL') ?: 'http://localhost:3000';
        $url = rtrim($serviceUrl, '/') . '/';

        $payload = [
            'template' => $template,
            'to' => $to,
            'vars' => $vars,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log('Email service connection error: ' . $curlError);
            return false;
        }

        if ($httpCode !== 202) {
            error_log('Email service returned unexpected status: ' . $httpCode . ' Response: ' . $response);
            return false;
        }

        return true;
    }

    public static function sendVerificationEmail(string $to, string $username, string $token): bool
    {
        return self::sendToService('verification', $to, [
            'username' => $username,
            'token' => $token,
        ]);
    }

    public static function sendPasswordResetEmail(string $to, string $username, string $token): bool
    {
        return self::sendToService('reset-password', $to, [
            'username' => $username,
            'token' => $token,
        ]);
    }

    public static function sendWelcomeEmail(string $to, string $username): bool
    {
        return self::sendToService('welcome', $to, [
            'username' => $username,
        ]);
    }
}
