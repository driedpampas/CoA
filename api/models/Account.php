<?php

namespace Models;

class Account
{
    private $mysql;

    public function __construct($mysql)
    {
        $this->mysql = $mysql;
    }

    public function checkUserAndPassword($username, $password)
    {
        if (!($stmt = $this->mysql->prepare("SELECT pass FROM auth WHERE user = ?"))) {
            return false;
        }

        if (!$stmt->bind_param('s', $username)) {
            return false;
        }

        if (!$stmt->execute()) {
            return false;
        }

        if (!($result = $stmt->get_result())) {
            return false;
        }

        if ($result->num_rows === 0) {
            return false;
        }

        $row = $result->fetch_assoc();
        return password_verify($password, $row['pass']);
    }

    public function getRole($username)
    {
        if (!($stmt = $this->mysql->prepare("SELECT role FROM auth WHERE user = ?"))) {
            return 'user';
        }

        if (!$stmt->bind_param('s', $username)) {
            return 'user';
        }

        if (!$stmt->execute()) {
            return 'user';
        }

        if (!($result = $stmt->get_result())) {
            return 'user';
        }

        if ($result->num_rows === 0) {
            return 'user';
        }

        $row = $result->fetch_assoc();
        return $row['role'] ?? 'user';
    }

    public function getUserId($username)
    {
        if (!($stmt = $this->mysql->prepare("SELECT id FROM auth WHERE user = ?"))) {
            return null;
        }

        if (!$stmt->bind_param('s', $username)) {
            return null;
        }

        if (!$stmt->execute()) {
            return null;
        }

        if (!($result = $stmt->get_result())) {
            return null;
        }

        if ($result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        return isset($row['id']) ? (int) $row['id'] : null;
    }

    public function resolveUsername($login)
    {
        if (str_contains($login, '@')) {
            if (!($stmt = $this->mysql->prepare("SELECT user FROM auth WHERE email = ?"))) {
                return null;
            }
            if (!$stmt->bind_param('s', $login)) {
                return null;
            }
        } else {
            if (!($stmt = $this->mysql->prepare("SELECT user FROM auth WHERE user = ?"))) {
                return null;
            }
            if (!$stmt->bind_param('s', $login)) {
                return null;
            }
        }

        if (!$stmt->execute()) {
            return null;
        }

        if (!($result = $stmt->get_result())) {
            return null;
        }

        if ($result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        return $row['user'];
    }

    public function checkUserExists($username)
    {
        if (!($stmt = $this->mysql->prepare("SELECT user FROM auth WHERE user = ?"))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('s', $username)) {
            return [false, 'Error binding parameters: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($pass_result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        if ($pass_result->num_rows > 0) {
            return [true, ''];
        }

        return [false, ''];
    }

    public function createUser($username, $password, $email, $role = 'user')
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($hashedPassword === false) {
            return [false, 'Error hashing password.'];
        }

        $stmt = $this->mysql->prepare("INSERT INTO auth (user, pass, email, role) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            if (!$stmt->bind_param('ssss', $username, $hashedPassword, $email, $role)) {
                return [false, 'Error binding parameters: ' . $this->mysql->error];
            }

            if (!$stmt->execute()) {
                return [false, 'Error executing query: ' . $this->mysql->error];
            }

            return [true, 'User created successfully.'];
        }

        if (!($stmt = $this->mysql->prepare("INSERT INTO auth (user, pass, email) VALUES (?, ?, ?)"))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('sss', $username, $hashedPassword, $email)) {
            return [false, 'Error binding parameters: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        return [true, 'User created successfully.'];
    }

    public function setVerificationToken($username)
    {
        $token = bin2hex(random_bytes(32));

        if (!($stmt = $this->mysql->prepare("UPDATE auth SET verification_token = ?, verification_expires = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE user = ?"))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('ss', $token, $username)) {
            return [false, 'Error binding parameters: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        return [true, $token];
    }

    public function verifyEmail($token)
    {
        if (!($stmt = $this->mysql->prepare("SELECT user FROM auth WHERE verification_token = ? AND verification_expires > NOW()"))) {
            return [false, 'Eroare la pregătirea interogării.'];
        }

        if (!$stmt->bind_param('s', $token)) {
            return [false, 'Eroare la legarea parametrilor.'];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogării.'];
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return [false, 'Link de verificare invalid sau expirat.'];
        }

        $row = $result->fetch_assoc();
        $username = $row['user'];

        if (!($stmt = $this->mysql->prepare("UPDATE auth SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE user = ?"))) {
            return [false, 'Eroare la actualizarea contului.'];
        }

        if (!$stmt->bind_param('s', $username)) {
            return [false, 'Eroare la legarea parametrilor.'];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogării.'];
        }

        return [true, $username];
    }

    public function isEmailVerified($username)
    {
        if (!($stmt = $this->mysql->prepare("SELECT email_verified FROM auth WHERE user = ?"))) {
            return false;
        }

        if (!$stmt->bind_param('s', $username)) {
            return false;
        }

        if (!$stmt->execute()) {
            return false;
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }

        $row = $result->fetch_assoc();
        return (bool) $row['email_verified'];
    }

    public function getEmailByUsername($username)
    {
        if (!($stmt = $this->mysql->prepare("SELECT email FROM auth WHERE user = ?"))) {
            return null;
        }

        if (!$stmt->bind_param('s', $username)) {
            return null;
        }

        if (!$stmt->execute()) {
            return null;
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        return $row['email'];
    }

    public function updateLocation($userId, $latitude, $longitude)
    {
        if (!($stmt = $this->mysql->prepare(
            "UPDATE auth
             SET last_latitude = ?, last_longitude = ?, last_location_updated_at = NOW()
             WHERE id = ?"
        ))) {
            return [false, 'Eroare la pregătirea interogării.'];
        }

        $userId = (int) $userId;
        if (!$stmt->bind_param('ddi', $latitude, $longitude, $userId)) {
            return [false, 'Eroare la legarea parametrilor.'];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogării.'];
        }

        return [true, true];
    }

    public function getLocationByUserId($userId)
    {
        if (!($stmt = $this->mysql->prepare(
            "SELECT last_latitude, last_longitude
             FROM auth
             WHERE id = ?"
        ))) {
            return [false, 'Eroare la pregătirea interogării.'];
        }

        $userId = (int) $userId;
        if (!$stmt->bind_param('i', $userId)) {
            return [false, 'Eroare la legarea parametrilor.'];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogării.'];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Eroare la preluarea rezultatelor.'];
        }

        $row = $result->fetch_assoc();
        if (!$row || $row['last_latitude'] === null || $row['last_longitude'] === null) {
            return [true, null];
        }

        return [true, [
            'latitude' => (float) $row['last_latitude'],
            'longitude' => (float) $row['last_longitude'],
        ]];
    }

    public function getUsersInRadius($latitude, $longitude, $radius = 1)
    {
        if (!($stmt = $this->mysql->prepare(
            "SELECT id
             FROM auth
             WHERE last_latitude IS NOT NULL
               AND last_longitude IS NOT NULL
               AND SQRT(
                    POW(last_latitude - ?, 2) +
                    POW(last_longitude - ?, 2)
               ) <= ?"
        ))) {
            return [false, 'Eroare la pregătirea interogării.'];
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        $radius = (float) $radius;
        if (!$stmt->bind_param('ddd', $latitude, $longitude, $radius)) {
            return [false, 'Eroare la legarea parametrilor.'];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogării.'];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Eroare la preluarea rezultatelor.'];
        }

        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }

        return [true, $ids];
    }

    public function setResetToken($email)
    {
        if (!($stmt = $this->mysql->prepare("SELECT user FROM auth WHERE email = ?"))) {
            return [false, 'Eroare la pregătirea interogării.'];
        }

        if (!$stmt->bind_param('s', $email)) {
            return [false, 'Eroare la legarea parametrilor.'];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogării.'];
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return [false, 'Nu există un cont cu această adresă de email.'];
        }

        $row = $result->fetch_assoc();
        $username = $row['user'];

        $token = bin2hex(random_bytes(32));

        if (!($stmt = $this->mysql->prepare("UPDATE auth SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE user = ?"))) {
            return [false, 'Eroare la pregătirea interogării.'];
        }

        if (!$stmt->bind_param('ss', $token, $username)) {
            return [false, 'Eroare la legarea parametrilor.'];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogării.'];
        }

        return [true, ['token' => $token, 'username' => $username, 'email' => $email]];
    }

    public function verifyResetToken($token)
    {
        if (!($stmt = $this->mysql->prepare("SELECT user FROM auth WHERE reset_token = ? AND reset_expires > NOW()"))) {
            return [false, 'Eroare la pregătirea interogării.'];
        }

        if (!$stmt->bind_param('s', $token)) {
            return [false, 'Eroare la legarea parametrilor.'];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogării.'];
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return [false, 'Link de resetare invalid sau expirat.'];
        }

        $row = $result->fetch_assoc();
        return [true, $row['user']];
    }

    public function resetPassword($token, $newPassword)
    {
        [$ok, $usernameOrError] = $this->verifyResetToken($token);

        if (!$ok) {
            return [false, $usernameOrError];
        }

        $username = $usernameOrError;
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if ($hashedPassword === false) {
            return [false, 'Eroare la hashuirea parolei.'];
        }

        if (!($stmt = $this->mysql->prepare("UPDATE auth SET pass = ?, reset_token = NULL, reset_expires = NULL WHERE user = ?"))) {
            return [false, 'Eroare la pregătirea interogării.'];
        }

        if (!$stmt->bind_param('ss', $hashedPassword, $username)) {
            return [false, 'Eroare la legarea parametrilor.'];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogării.'];
        }

        return [true, $username];
    }

}
