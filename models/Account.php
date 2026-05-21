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

    public function checkUserExists($username)
    {
        if (!($stmt = $this->mysql->prepare("SELECT user FROM auth WHERE user = ?"))) {
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('s', $username)) {
            return [false, 'Eroare la legarea parametrilor: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogarii: ' . $this->mysql->error];
        }

        if (!($pass_result = $stmt->get_result())) {
            return [false, 'Eroare la obtinerea rezultatului: ' . $this->mysql->error];
        }

        if ($pass_result->num_rows > 0) {
            return [true, ''];
        }

        return [false, ''];
    }

    public function createUser($username, $password, $email)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($hashedPassword === false) {
            return [false, 'Eroare la hashing-ul parolei.'];
        }

        if (!($stmt = $this->mysql->prepare("INSERT INTO auth (user, pass, email) VALUES (?, ?, ?)"))) {
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('sss', $username, $hashedPassword, $email)) {
            return [false, 'Eroare la legarea parametrilor: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogarii: ' . $this->mysql->error];
        }

        return [true, 'User created successfully.'];
    }

}
