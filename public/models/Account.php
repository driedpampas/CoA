<?php

namespace Models;

class Account
{
    private $client;

    public function __construct($apiBaseUrl = null)
    {
        $this->client = new HttpClient($apiBaseUrl);
    }

    public function resolveUsername($login)
    {
        [$ok, $res] = $this->client->request('GET', 'auth/resolve-username?login=' . urlencode($login));
        return $ok ? ($res['username'] ?? null) : null;
    }

    public function checkUserAndPassword($username, $password)
    {
        [$ok, $res] = $this->client->request('POST', 'auth/login', [
            'username' => $username,
            'password' => $password
        ]);
        return $ok;
    }

    public function isEmailVerified($username)
    {
        [$ok, $res] = $this->client->request('GET', 'auth/is-email-verified?username=' . urlencode($username));
        return $ok ? (bool) ($res['verified'] ?? false) : false;
    }

    public function getRole($username)
    {
        [$ok, $res] = $this->client->request('GET', 'auth/role?username=' . urlencode($username));
        return $ok ? ($res['role'] ?? 'user') : 'user';
    }

    public function getUserId($username)
    {
        [$ok, $res] = $this->client->request('GET', 'auth/user-id?username=' . urlencode($username));
        return $ok ? ($res['user_id'] ?? null) : null;
    }

    public function checkUserExists($username)
    {
        // DB model returns [exists_bool, error_string]
        [$ok, $res] = $this->client->request('GET', 'auth/check-username?username=' . urlencode($username));
        if ($ok) {
            return [$res['exists'] ?? false, ''];
        }
        return [false, $res];
    }

    public function createUser($username, $password, $email, $role = 'user')
    {
        // DB model returns [success_bool, message_string]
        [$ok, $res] = $this->client->request('POST', 'auth/register', [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'role' => $role
        ]);
        if ($ok) {
            return [true, $res['message'] ?? ''];
        }
        return [false, $res];
    }

    public function setVerificationToken($username)
    {
        // DB model returns [success_bool, token_or_error]
        [$ok, $res] = $this->client->request('POST', 'auth/set-verification-token', [
            'username' => $username
        ]);
        if ($ok) {
            return [true, $res['token'] ?? ''];
        }
        return [false, $res];
    }

    public function verifyEmail($token)
    {
        // DB model returns [success_bool, username_or_error]
        [$ok, $res] = $this->client->request('POST', 'auth/verify', [
            'token' => $token
        ]);
        if ($ok) {
            return [true, $res['username'] ?? ''];
        }
        return [false, $res];
    }

    public function getEmailByUsername($username)
    {
        [$ok, $res] = $this->client->request('GET', 'auth/email?username=' . urlencode($username));
        return $ok ? ($res['email'] ?? null) : null;
    }

    public function setResetToken($email)
    {
        // DB model returns [success_bool, data_array_or_error_string]
        [$ok, $res] = $this->client->request('POST', 'auth/forgot-password', [
            'email' => $email
        ]);
        if ($ok) {
            return [true, $res];
        }
        return [false, $res];
    }

    public function verifyResetToken($token)
    {
        // DB model returns [success_bool, username_or_error]
        [$ok, $res] = $this->client->request('GET', 'auth/verify-reset-token?token=' . urlencode($token));
        if ($ok) {
            return [true, $res['username'] ?? ''];
        }
        return [false, $res];
    }

    public function resetPassword($token, $newPassword)
    {
        // DB model returns [success_bool, username_or_error]
        [$ok, $res] = $this->client->request('POST', 'auth/reset-password', [
            'token' => $token,
            'password' => $newPassword
        ]);
        if ($ok) {
            return [true, $res['username'] ?? ''];
        }
        return [false, $res];
    }

    public function getProfile($userId)
    {
        // DB model returns [success_bool, profile_row_or_error]
        // Since we are forwarding the session, we can request GET /auth/profile
        // Note: the backend uses current logged in user profile, but we can pass userId if needed.
        [$ok, $res] = $this->client->request('GET', 'auth/profile');
        return [$ok, $res];
    }

    public function updateProfile($userId, $bio, $notificationRadiusKm, $preferredShelterId)
    {
        // DB model returns [success_bool, true_or_error]
        [$ok, $res] = $this->client->request('PATCH', 'auth/profile', [
            'bio' => $bio,
            'notification_radius_km' => $notificationRadiusKm,
            'preferred_shelter_id' => $preferredShelterId
        ]);
        return [$ok, $ok ? true : $res];
    }

    public function getShelterStatus($shelterId)
    {
        [$ok, $res] = $this->client->request('GET', 'shelters/status?id=' . urlencode($shelterId));
        return $ok ? $res : null;
    }

    public function findNearestOpenShelter($lat, $lng, $excludeId = null)
    {
        // DB model returns [success_bool, shelter_row_or_error]
        $url = 'shelters/nearest-open?lat=' . urlencode($lat) . '&lng=' . urlencode($lng);
        if ($excludeId !== null) {
            $url .= '&exclude_id=' . urlencode($excludeId);
        }
        [$ok, $res] = $this->client->request('GET', $url);
        return [$ok, $res];
    }
}
