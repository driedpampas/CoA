<?php

namespace ClientModels;

class HttpClient
{
    private $apiBaseUrl;

    public function __construct($apiBaseUrl = null)
    {
        if ($apiBaseUrl === null) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $this->apiBaseUrl = $protocol . '://' . $host . '/api';
        } else {
            // Support passing relative '/api' or full URL
            if (str_starts_with($apiBaseUrl, '/')) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $this->apiBaseUrl = $protocol . '://' . $host . $apiBaseUrl;
            } else {
                $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
            }
        }
    }

    public function request($method, $path, $data = null)
    {
        $url = $this->apiBaseUrl . '/' . ltrim($path, '/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Forward user session cookie
        if (isset($_COOKIE['PHPSESSID'])) {
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $_COOKIE['PHPSESSID']);
        }

        // Set headers
        $headers = [];
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }

        // Forward CSRF token header if present in current request
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SESSION['csrf_token'] ?? null;
        if ($csrfToken) {
            $headers[] = 'X-CSRF-TOKEN: ' . $csrfToken;
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Disable SSL verification for local requests
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Prevent session locking deadlock
        $sessionActive = (session_status() === PHP_SESSION_ACTIVE);
        if ($sessionActive) {
            session_write_close();
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($sessionActive) {
            session_start();
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return [true, $decoded];
        }

        return [false, $decoded['error'] ?? 'API request failed with code ' . $httpCode];
    }
}
