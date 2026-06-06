<?php

namespace Models;

class Notification
{
    private $mysql;

    public function __construct($mysql)
    {
        $this->mysql = $mysql;
    }

    public function getAll($userId = null, $limit = 50, $offset = 0)
    {
        if ($userId === null) {
            return [true, []];
        }

        if (!($stmt = $this->mysql->prepare(
            "SELECT id, user_id, title, message, type, severity, reference_id, is_read, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $userId = (int) $userId;
        $stmt->bind_param('iii', $userId, $limit, $offset);

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

    public function getUnread($userId = null)
    {
        if ($userId === null) {
            return [true, []];
        }

        if (!($stmt = $this->mysql->prepare(
            "SELECT id, user_id, title, message, type, severity, reference_id, created_at
             FROM notifications
             WHERE user_id = ? AND is_read = 0
             ORDER BY created_at DESC"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $userId = (int) $userId;
        $stmt->bind_param('i', $userId);

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

    public function getUnreadCount($userId = null)
    {
        if ($userId === null) {
            return [true, 0];
        }

        if (!($stmt = $this->mysql->prepare(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $userId = (int) $userId;
        $stmt->bind_param('i', $userId);

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
        return $this->createForUser(null, $title, $message, $type, $severity, $referenceId);
    }

    public function createForUser($userId, $title, $message, $type = 'system', $severity = 'info', $referenceId = null)
    {
        $ref = $referenceId !== null ? (int) $referenceId : null;

        if ($userId === null) {
            $sql = $ref === null
                ? "INSERT INTO notifications (user_id, title, message, type, severity, reference_id)
                   VALUES (NULL, ?, ?, ?, ?, NULL)"
                : "INSERT INTO notifications (user_id, title, message, type, severity, reference_id)
                   VALUES (NULL, ?, ?, ?, ?, ?)";

            if (!($stmt = $this->mysql->prepare($sql))) {
                return [false, 'Error preparing query: ' . $this->mysql->error];
            }

            if ($ref === null) {
                $stmt->bind_param('ssss', $title, $message, $type, $severity);
            } else {
                $stmt->bind_param('ssssi', $title, $message, $type, $severity, $ref);
            }
        } else {
            $sql = $ref === null
                ? "INSERT INTO notifications (user_id, title, message, type, severity, reference_id)
                   VALUES (?, ?, ?, ?, ?, NULL)"
                : "INSERT INTO notifications (user_id, title, message, type, severity, reference_id)
                   VALUES (?, ?, ?, ?, ?, ?)";

            if (!($stmt = $this->mysql->prepare($sql))) {
                return [false, 'Error preparing query: ' . $this->mysql->error];
            }

            $userId = (int) $userId;
            if ($ref === null) {
                $stmt->bind_param('issss', $userId, $title, $message, $type, $severity);
            } else {
                $stmt->bind_param('issssi', $userId, $title, $message, $type, $severity, $ref);
            }
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        return [true, $stmt->insert_id];
    }

    public function createForUsers(array $userIds, $title, $message, $type = 'system', $severity = 'info', $referenceId = null)
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if (empty($userIds)) {
            return [true, 0];
        }

        $ref = $referenceId !== null ? (int) $referenceId : null;
        $sql = $ref === null
            ? "INSERT INTO notifications (user_id, title, message, type, severity, reference_id)
               VALUES (?, ?, ?, ?, ?, NULL)"
            : "INSERT INTO notifications (user_id, title, message, type, severity, reference_id)
               VALUES (?, ?, ?, ?, ?, ?)";

        if (!($stmt = $this->mysql->prepare($sql))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $inserted = 0;

        $this->mysql->begin_transaction();
        try {
            foreach ($userIds as $userId) {
                if ($ref === null) {
                    $stmt->bind_param('issss', $userId, $title, $message, $type, $severity);
                } else {
                    $stmt->bind_param('issssi', $userId, $title, $message, $type, $severity, $ref);
                }
                if (!$stmt->execute()) {
                    throw new \RuntimeException($stmt->error ?: $this->mysql->error);
                }
                $inserted++;
            }
            $this->mysql->commit();
        } catch (\Throwable $e) {
            $this->mysql->rollback();
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        return [true, $inserted];
    }

    public function hasReferenceForUser($userId, $referenceId)
    {
        if ($userId === null || $referenceId === null) {
            return [true, false];
        }

        if (!($stmt = $this->mysql->prepare(
            "SELECT id
             FROM notifications
             WHERE user_id = ? AND reference_id = ? AND type = 'event'
             LIMIT 1"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $userId = (int) $userId;
        $referenceId = (int) $referenceId;
        if (!$stmt->bind_param('ii', $userId, $referenceId)) {
            return [false, 'Error binding parameters: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        return [true, $result->num_rows > 0];
    }

    public function markAsRead($userId, $id)
    {
        if ($userId === null) {
            return [true, false];
        }

        if (!($stmt = $this->mysql->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $id = (int) $id;
        $userId = (int) $userId;
        $stmt->bind_param('ii', $id, $userId);

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        return [true, $stmt->affected_rows > 0];
    }

    public function markAllAsRead($userId)
    {
        if ($userId === null) {
            return [true, 0];
        }

        if (!($stmt = $this->mysql->prepare(
            "UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND user_id = ?"
        ))) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $userId = (int) $userId;
        $stmt->bind_param('i', $userId);

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        return [true, $stmt->affected_rows];
    }
}
