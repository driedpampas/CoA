<?php

namespace Models;

class Shelter
{
    private $mysql;

    public function __construct($mysql)
    {
        $this->mysql = $mysql;
    }

    public function getAll()
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, name, address, latitude, longitude, capacity, current_occupancy, shelter_type, status, contact_phone, notes
             FROM shelters WHERE status != 'closed'"
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

        $shelters = [];
        while ($row = $result->fetch_assoc()) {
            $shelters[] = $row;
        }

        return [true, $shelters];
    }

    public function findById($id)
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, name, address, latitude, longitude, capacity, current_occupancy, shelter_type, status, contact_phone, notes
             FROM shelters WHERE id = ?"
            ))
        ) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('i', $id)) {
            return [false, 'Error binding parameters: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        if ($result->num_rows === 0) {
            return [false, 'Shelter not found.'];
        }

        return [true, $result->fetch_assoc()];
    }

    public function findNearest($latitude, $longitude, $limit = 5)
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, name, address, latitude, longitude, capacity, current_occupancy, shelter_type, status, contact_phone,
                    ST_Distance_Sphere(geom_point, ST_GeomFromText(?, 4326)) AS distance_meters
             FROM shelters
             WHERE status = 'open'
             ORDER BY distance_meters ASC
             LIMIT ?"
            ))
        ) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $point = "POINT($longitude $latitude)";

        if (!$stmt->bind_param('si', $point, $limit)) {
            return [false, 'Error binding parameters: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $shelters = [];
        while ($row = $result->fetch_assoc()) {
            $row['distance_meters'] = round($row['distance_meters'], 0);
            $shelters[] = $row;
        }

        return [true, $shelters];
    }

    public function findWithinRadius($latitude, $longitude, $radiusMeters = 10000)
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, name, address, latitude, longitude, capacity, current_occupancy, shelter_type, status, contact_phone,
                    ST_Distance_Sphere(geom_point, ST_GeomFromText(?, 4326)) AS distance_meters
             FROM shelters
             WHERE status = 'open'
               AND ST_Distance_Sphere(geom_point, ST_GeomFromText(?, 4326)) <= ?
             ORDER BY distance_meters ASC"
            ))
        ) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $point = "POINT($longitude $latitude)";

        if (!$stmt->bind_param('sid', $point, $point, $radiusMeters)) {
            return [false, 'Error binding parameters: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $shelters = [];
        while ($row = $result->fetch_assoc()) {
            $row['distance_meters'] = round($row['distance_meters'], 0);
            $shelters[] = $row;
        }

        return [true, $shelters];
    }

    public function create(array $data)
    {
        $name = $data['name'] ?? '';
        $address = $data['address'] ?? '';
        $latitude = isset($data['latitude']) ? (float) $data['latitude'] : 0.0;
        $longitude = isset($data['longitude']) ? (float) $data['longitude'] : 0.0;
        $capacity = isset($data['capacity']) ? (int) $data['capacity'] : 0;
        $shelterType = $data['shelter_type'] ?? 'community';
        $contactPhone = $data['contact_phone'] ?? '';
        $notes = $data['notes'] ?? '';

        $point = sprintf('POINT(%F %F)', $longitude, $latitude);
        $stmt = $this->mysql->prepare("INSERT INTO shelters (name, address, latitude, longitude, geom_point, capacity, current_occupancy, shelter_type, status, contact_phone, notes) VALUES (?, ?, ?, ?, ST_GeomFromText(?, 4326), ?, 0, ?, 'open', ?, ?)");
        if (!$stmt) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $stmt->bind_param('ssddsisss', $name, $address, $latitude, $longitude, $point, $capacity, $shelterType, $contactPhone, $notes);

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
        $name = $data['name'] ?? '';
        $address = $data['address'] ?? '';
        $latitude = isset($data['latitude']) ? (float) $data['latitude'] : 0.0;
        $longitude = isset($data['longitude']) ? (float) $data['longitude'] : 0.0;
        $capacity = isset($data['capacity']) ? (int) $data['capacity'] : 0;
        $shelterType = $data['shelter_type'] ?? 'community';
        $status = $data['status'] ?? 'open';
        $contactPhone = $data['contact_phone'] ?? '';
        $notes = $data['notes'] ?? '';

        $point = sprintf('POINT(%F %F)', $longitude, $latitude);
        $stmt = $this->mysql->prepare("UPDATE shelters SET name=?, address=?, latitude=?, longitude=?, geom_point=ST_GeomFromText(?, 4326), capacity=?, shelter_type=?, status=?, contact_phone=?, notes=? WHERE id=?");
        if (!$stmt) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        $stmt->bind_param('ssddsissssi', $name, $address, $latitude, $longitude, $point, $capacity, $shelterType, $status, $contactPhone, $notes, $id);

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
        $stmt = $this->mysql->prepare("DELETE FROM shelters WHERE id = ?");
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

        $shelterSortMap = [
            'name' => 'name',
            'address' => 'address',
            'type' => 'shelter_type',
            'capacity' => 'capacity',
            'status' => 'status',
            'phone' => 'contact_phone',
            'notes' => 'notes',
            'created' => 'created_at',
            'updated' => 'updated_at',
        ];
        $sortColumn = $shelterSortMap[$sort] ?? 'created_at';
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $whereSql = '';
        $params = [];
        $types = '';
        $searchTerm = trim((string) $search);
        if ($searchTerm !== '') {
            $likePattern = '%' . strtr($searchTerm, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']) . '%';
            $whereSql = " WHERE (name LIKE ? ESCAPE '\\\\' OR address LIKE ? ESCAPE '\\\\')";
            $params = [$likePattern, $likePattern];
            $types = 'ss';
        }

        $countSql = "SELECT COUNT(*) FROM shelters" . $whereSql;
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

        $dataSql = "SELECT id, name, address, latitude, longitude, capacity, current_occupancy, shelter_type, status, contact_phone, notes, created_at, updated_at
                    FROM shelters" . $whereSql . "
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
