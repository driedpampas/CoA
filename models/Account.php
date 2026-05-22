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

        // Try to insert with role column (if present). If the column doesn't exist, fall back to the original insert.
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

        // Fallback for older schemas without role column
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

}
