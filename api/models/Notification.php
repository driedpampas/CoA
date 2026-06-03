<?php

namespace Models;

class Notification
{
    private $mysql;

    public function __construct($mysql)
    {
        $this->mysql = $mysql;
    }

    public function getAll($limit = 50, $offset = 0)
    {
        if (!($stmt = $this->mysql->prepare(
            "SELECT id, title, message, type, severity, reference_id, is_read, created_at
             FROM notifications
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $stmt->bind_param('ii', $limit, $offset);

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return [true, $rows];
    }

    public function getUnread()
    {
        if (!($stmt = $this->mysql->prepare(
            "SELECT id, title, message, type, severity, reference_id, created_at
             FROM notifications
             WHERE is_read = 0
             ORDER BY created_at DESC"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return [true, $rows];
    }

    public function getUnreadCount()
    {
        if (!($stmt = $this->mysql->prepare(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE is_read = 0"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $row = $result->fetch_assoc();
        return [true, (int) $row['cnt']];
    }

    public function create($title, $message, $type = 'system', $severity = 'info', $referenceId = null)
    {
        if (!($stmt = $this->mysql->prepare(
            "INSERT INTO notifications (title, message, type, severity, reference_id) VALUES (?, ?, ?, ?, ?)"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $ref = $referenceId !== null ? (int) $referenceId : null;
        $stmt->bind_param('ssssi', $title, $message, $type, $severity, $ref);

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        return [true, $stmt->insert_id];
    }

    public function markAsRead($id)
    {
        if (!($stmt = $this->mysql->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = ?"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $stmt->bind_param('i', $id);

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        return [true, $stmt->affected_rows > 0];
    }

    public function markAllAsRead()
    {
        if (!($stmt = $this->mysql->prepare(
            "UPDATE notifications SET is_read = 1 WHERE is_read = 0"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        return [true, $stmt->affected_rows];
    }
}
