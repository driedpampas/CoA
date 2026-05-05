<?php

class Account
{
    private $mysql;

    public function __construct($mysql)
    {
        $this->mysql = $mysql;
    }

    public function checkUserExists($username, $password)
    {
        if (!($stmt = $this->mysql->prepare("SELECT user, pass FROM auth WHERE user = ? AND pass = ?"))) {
            die('Eroare la pregatirea interogarii: ' . $this->mysql->error);
        }

        if (!$stmt->bind_param('ss', $username, $password)) {
            die('Eroare la legarea parametrilor: ' . $this->mysql->error);
        }

        if (!$stmt->execute()) {
            die('Eroare la executarea interogarii: ' . $this->mysql->error);
        }

        if (!($pass_result = $stmt->get_result())) {
            die('Eroare la obtinerea rezultatului: ' . $this->mysql->error);
        }

        if ($pass_result->num_rows > 0) {
            return true;
        }

        return false;
    }
}