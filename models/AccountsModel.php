<?php

class Account
{
    private $mysql;

    public function __construct($mysql)
    {
        $this->mysql = $mysql;
    }

    public function checkUserAndPassword($username, $password)
    {
        if (!($stmt = $this->mysql->prepare("SELECT user, pass FROM auth WHERE user = ? AND pass = ?"))) {
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('ss', $username, $password)) {
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

    public function createUser($username, $password)
    {
        if (!($stmt = $this->mysql->prepare("INSERT INTO auth (user, pass) VALUES (?, ?)"))) {
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('ss', $username, $password)) {
            return [false, 'Eroare la legarea parametrilor: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogarii: ' . $this->mysql->error];
        }

        return [true, 'User created successfully.'];
    }

}