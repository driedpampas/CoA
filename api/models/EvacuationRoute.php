<?php

namespace Models;

class EvacuationRoute
{
    private $mysql;

    public function __construct($mysql)
    {
        $this->mysql = $mysql;
    }

    public function getByShelterId($shelterId)
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, name, shelter_id, from_latitude, from_longitude, distance_meters, estimated_minutes, status, notes,
                    ST_AsText(route_geometry) AS route_wkt
             FROM evacuation_routes
             WHERE shelter_id = ? AND status = 'active'
             ORDER BY distance_meters ASC"
            ))
        ) {
            return [false, 'Error preparing query: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('i', $shelterId)) {
            return [false, 'Error binding parameters: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Error executing query: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Error retrieving result: ' . $this->mysql->error];
        }

        $routes = [];
        while ($row = $result->fetch_assoc()) {
            $row['route_geometry'] = self::parseLineString($row['route_wkt']);
            unset($row['route_wkt']);
            $routes[] = $row;
        }

        return [true, $routes];
    }

    public function findNearestRoute($latitude, $longitude, $limit = 3)
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT er.id, er.name, er.shelter_id, er.from_latitude, er.from_longitude,
                    er.distance_meters, er.estimated_minutes, er.status,
                    s.name AS shelter_name, s.latitude AS shelter_latitude, s.longitude AS shelter_longitude,
                    ST_Distance_Sphere(er.from_geom, ST_GeomFromText(?, 4326)) AS distance_from_point,
                    ST_AsText(er.route_geometry) AS route_wkt
             FROM evacuation_routes er
             JOIN shelters s ON er.shelter_id = s.id
             WHERE er.status = 'active'
             ORDER BY distance_from_point ASC
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

        $routes = [];
        while ($row = $result->fetch_assoc()) {
            $row['distance_from_point'] = round($row['distance_from_point'], 0);
            $row['route_geometry'] = self::parseLineString($row['route_wkt']);
            unset($row['route_wkt']);
            $routes[] = $row;
        }

        return [true, $routes];
    }

    private static function parseLineString($wkt)
    {
        if ($wkt === null) {
            return [];
        }

        $coords = preg_replace('/^LINESTRING\((.*)\)$/', '$1', $wkt);
        $pairs = explode(',', $coords);
        $result = [];

        foreach ($pairs as $pair) {
            $parts = explode(' ', trim($pair));
            if (count($parts) === 2) {
                $result[] = [(float) $parts[1], (float) $parts[0]];
            }
        }

        return $result;
    }

    public function getAll()
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT er.id, er.name, er.shelter_id, er.from_latitude, er.from_longitude,
                        er.distance_meters, er.estimated_minutes, er.status, er.notes,
                        ST_AsText(er.route_geometry) AS route_wkt,
                        s.name AS shelter_name
                 FROM evacuation_routes er
                 JOIN shelters s ON er.shelter_id = s.id
                 ORDER BY er.distance_meters ASC"
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

        $routes = [];
        while ($row = $result->fetch_assoc()) {
            $row['route_geometry'] = self::parseLineString($row['route_wkt']);
            unset($row['route_wkt']);
            $routes[] = $row;
        }

        return [true, $routes];
    }

    public function create(array $data) {
        $name = $data['name'] ?? '';
        $shelterId = (int)$data['shelter_id'];
        $fromLatitude = (float)$data['from_latitude'];
        $fromLongitude = (float)$data['from_longitude'];
        $routeGeometryWkt = $data['route_geometry'] ?? '';
        $distanceMeters = isset($data['distance_meters']) ? (float)$data['distance_meters'] : null;
        $estimatedMinutes = isset($data['estimated_minutes']) ? (int)$data['estimated_minutes'] : null;
        $status = $data['status'] ?? 'active';
        $notes = $data['notes'] ?? '';

        $fromGeom = "POINT($fromLongitude $fromLatitude)";
        $stmt = $this->mysql->prepare("INSERT INTO evacuation_routes (name, shelter_id, from_latitude, from_longitude, from_geom, route_geometry, distance_meters, estimated_minutes, status, notes) VALUES (?, ?, ?, ?, ST_GeomFromText(?, 4326), ST_GeomFromText(?, 4326), ?, ?, ?, ?)");
        if (!$stmt) {
            return [false, $this->mysql->error];
        }
        $stmt->bind_param('siddssdiss', $name, $shelterId, $fromLatitude, $fromLongitude, $fromGeom, $routeGeometryWkt, $distanceMeters, $estimatedMinutes, $status, $notes);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return [false, $err];
        }
        $id = $stmt->insert_id;
        $stmt->close();
        return [true, ['id' => $id]];
    }

    public function update($id, array $data) {
        $id = (int)$id;
        $name = $data['name'] ?? '';
        $shelterId = (int)$data['shelter_id'];
        $fromLatitude = (float)$data['from_latitude'];
        $fromLongitude = (float)$data['from_longitude'];
        $routeGeometryWkt = $data['route_geometry'] ?? '';
        $distanceMeters = isset($data['distance_meters']) ? (float)$data['distance_meters'] : null;
        $estimatedMinutes = isset($data['estimated_minutes']) ? (int)$data['estimated_minutes'] : null;
        $status = $data['status'] ?? 'active';
        $notes = $data['notes'] ?? '';

        $fromGeom = "POINT($fromLongitude $fromLatitude)";
        $stmt = $this->mysql->prepare("UPDATE evacuation_routes SET name=?, shelter_id=?, from_latitude=?, from_longitude=?, from_geom=ST_GeomFromText(?, 4326), route_geometry=ST_GeomFromText(?, 4326), distance_meters=?, estimated_minutes=?, status=?, notes=? WHERE id=?");
        if (!$stmt) {
            return [false, $this->mysql->error];
        }
        $stmt->bind_param('siddssdissi', $name, $shelterId, $fromLatitude, $fromLongitude, $fromGeom, $routeGeometryWkt, $distanceMeters, $estimatedMinutes, $status, $notes, $id);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return [false, $err];
        }
        $stmt->close();
        return [true, true];
    }

    public function delete($id) {
        $id = (int)$id;
        $stmt = $this->mysql->prepare("DELETE FROM evacuation_routes WHERE id = ?");
        if (!$stmt) {
            return [false, $this->mysql->error];
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return [false, $err];
        }
        $stmt->close();
        return [true, true];
    }

    public function getPaginated($page, $pageSize, $search, $sort, $sortDir) {
        $page = max(1, (int)$page);
        $pageSize = max(1, (int)$pageSize);

        $sortMap = [
            'name' => 'er.name',
            'shelter' => 's.name',
            'distance' => 'er.distance_meters',
            'duration' => 'er.estimated_minutes',
            'status' => 'er.status',
            'created' => 'er.created_at',
            'updated' => 'er.updated_at',
        ];
        $sortColumn = $sortMap[$sort] ?? 'er.created_at';
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $whereSql = '';
        $params = [];
        $types = '';
        $searchTerm = trim((string)$search);
        if ($searchTerm !== '') {
            $likePattern = '%' . strtr($searchTerm, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']) . '%';
            $whereSql = " WHERE (er.name LIKE ? ESCAPE '\\\\' OR s.name LIKE ? ESCAPE '\\\\')";
            $params = [$likePattern, $likePattern];
            $types = 'ss';
        }

        $countSql = "SELECT COUNT(*) FROM evacuation_routes er JOIN shelters s ON er.shelter_id = s.id" . $whereSql;
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
        $total = (int)($result->fetch_row()[0] ?? 0);
        $countStmt->close();

        $totalPages = max(1, (int)ceil($total / $pageSize));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $pageSize;

        $dataSql = "SELECT er.id, er.name, er.shelter_id, er.from_latitude, er.from_longitude, er.distance_meters, er.estimated_minutes, er.status, er.notes, er.created_at, er.updated_at,
                           s.name AS shelter_name, ST_AsText(er.route_geometry) AS route_wkt
                    FROM evacuation_routes er
                    JOIN shelters s ON er.shelter_id = s.id" . $whereSql . "
                    ORDER BY {$sortColumn} {$sortDir}, er.id DESC
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
            $row['route_geometry'] = self::parseLineString($row['route_wkt']);
            unset($row['route_wkt']);
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
