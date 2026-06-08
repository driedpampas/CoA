<?php

namespace Models;

class Event
{
    private $mysql;

    public function __construct($mysql)
    {
        $this->mysql = $mysql;
    }

    public function getActive()
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, event_type, title, description, severity, latitude, longitude, status, started_at
             FROM emergency_events
             WHERE status = 'active'
             ORDER BY started_at DESC"
            ))
        ) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        return [true, $events];
    }

    public function getAll()
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, event_type, title, description, severity, latitude, longitude, status, started_at
                 FROM emergency_events
                 ORDER BY started_at DESC"
            ))
        ) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        return [true, $events];
    }

    public function getAllForCapFeed()
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, event_type, title, description, severity, latitude, longitude, status, started_at
             FROM emergency_events
             ORDER BY started_at DESC"
            ))
        ) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        return [true, $events];
    }

    public function getRecentForMap($days = 1)
    {
        $days = (int) $days;
        if ($days < 1) {
            $days = 1;
        } elseif ($days > 30) {
            $days = 30;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, event_type, title, description, severity, latitude, longitude, status, started_at
             FROM emergency_events
             WHERE started_at >= ?
             ORDER BY started_at DESC"
            ))
        ) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $stmt->bind_param('s', $cutoff);

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        return [true, $events];
    }

    public function create(array $data)
    {
        $eventType = $data['event_type'] ?? 'other';
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $severity = $data['severity'] ?? 'moderate';
        $latitude = isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null;

        if ($latitude === null || $longitude === null) {
            $stmt = $this->mysql->prepare("INSERT INTO emergency_events (event_type, title, description, severity, latitude, longitude) VALUES (?, ?, ?, ?, NULL, NULL)");
            if (!$stmt) {
                return [false, 'Error preparing query: ' . $this->mysql->error];
            }
            $stmt->bind_param('ssss', $eventType, $title, $description, $severity);
        } else {
            $stmt = $this->mysql->prepare("INSERT INTO emergency_events (event_type, title, description, severity, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                return [false, 'Error preparing query: ' . $this->mysql->error];
            }
            $stmt->bind_param('ssssdd', $eventType, $title, $description, $severity, $latitude, $longitude);
        }

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return [false, 'Error executing query: ' . $err];
        }

        $id = $stmt->insert_id;
        $stmt->close();
        return [true, ['id' => $id]];
    }

    public function update($id, array $data)
    {
        $id = (int) $id;
        $eventType = $data['event_type'] ?? 'other';
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $severity = $data['severity'] ?? 'moderate';
        $status = $data['status'] ?? 'active';
        $latitude = isset($data['latitude']) && $data['latitude'] !== '' && $data['latitude'] !== null ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) && $data['longitude'] !== '' && $data['longitude'] !== null ? (float) $data['longitude'] : null;

        $resolvedAtSql = $status === 'resolved' ? 'NOW()' : 'NULL';

        if ($latitude === null || $longitude === null) {
            $stmt = $this->mysql->prepare("UPDATE emergency_events SET event_type=?, title=?, description=?, severity=?, status=?, resolved_at={$resolvedAtSql}, latitude=NULL, longitude=NULL WHERE id=?");
            if (!$stmt) {
                return [false, 'Error preparing query: ' . $this->mysql->error];
            }
            $stmt->bind_param('sssssi', $eventType, $title, $description, $severity, $status, $id);
        } else {
            $stmt = $this->mysql->prepare("UPDATE emergency_events SET event_type=?, title=?, description=?, severity=?, status=?, resolved_at={$resolvedAtSql}, latitude=?, longitude=? WHERE id=?");
            if (!$stmt) {
                return [false, 'Error preparing query: ' . $this->mysql->error];
            }
            $stmt->bind_param('sssssddi', $eventType, $title, $description, $severity, $status, $latitude, $longitude, $id);
        }

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return [false, 'Error executing query: ' . $err];
        }

        $stmt->close();
        return [true, true];
    }

    public function delete($id)
    {
        $id = (int) $id;
        $stmt = $this->mysql->prepare("DELETE FROM emergency_events WHERE id = ?");
        if (!$stmt) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return [false, 'Error executing query: ' . $err];
        }
        $stmt->close();
        return [true, true];
    }

    public function getPaginated($page, $pageSize, $search, $sort, $sortDir)
    {
        $page = max(1, (int) $page);
        $pageSize = max(1, (int) $pageSize);

        $eventSortMap = [
            'type' => 'event_type',
            'title' => 'title',
            'description' => 'description',
            'severity' => 'severity',
            'status' => 'status',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'created' => 'created_at',
            'updated' => 'updated_at',
        ];
        $sortColumn = $eventSortMap[$sort] ?? 'created_at';
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $whereSql = '';
        $params = [];
        $types = '';
        $searchTerm = trim((string) $search);
        if ($searchTerm !== '') {
            $likePattern = '%' . strtr($searchTerm, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']) . '%';
            $whereSql = " WHERE (title LIKE ? ESCAPE '\\\\' OR description LIKE ? ESCAPE '\\\\')";
            $params = [$likePattern, $likePattern];
            $types = 'ss';
        }

        $countSql = "SELECT COUNT(*) FROM emergency_events" . $whereSql;
        $countStmt = $this->mysql->prepare($countSql);
        if (!$countStmt) {
            return [false, 'Error preparing count query: ' . $this->mysql->error];
        }
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        if (!$countStmt->execute()) {
            return [false, 'Error executing count query: ' . $countStmt->error];
        }
        $result = $countStmt->get_result();
        $total = (int) ($result->fetch_row()[0] ?? 0);
        $countStmt->close();

        $totalPages = max(1, (int) ceil($total / $pageSize));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $pageSize;

        $dataSql = "SELECT id, event_type, title, description, severity, latitude, longitude, status, created_at, updated_at
                    FROM emergency_events" . $whereSql . "
                    ORDER BY {$sortColumn} {$sortDir}, id DESC
                    LIMIT ? OFFSET ?";

        $stmt = $this->mysql->prepare($dataSql);
        if (!$stmt) {
            return [false, 'Error preparing data query: ' . $this->mysql->error];
        }

        $bindTypes = $types . 'ii';
        $statementParams = array_merge($params, [$pageSize, $offset]);
        $stmt->bind_param($bindTypes, ...$statementParams);

        if (!$stmt->execute()) {
            return [false, 'Error executing data query: ' . $stmt->error];
        }

        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return [true, [
            'rows' => $rows,
            'total' => $total,
            'totalPages' => $totalPages,
            'page' => $page
        ]];
    }
}
