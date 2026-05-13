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
        if (!($stmt = $this->mysql->prepare(
            "SELECT id, name, shelter_id, from_latitude, from_longitude, distance_meters, estimated_minutes, status, notes
             FROM evacuation_routes
             WHERE shelter_id = ? AND status = 'active'
             ORDER BY distance_meters ASC"
        ))) {
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('i', $shelterId)) {
            return [false, 'Eroare la legarea parametrilor: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogarii: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Eroare la obtinerea rezultatului: ' . $this->mysql->error];
        }

        $routes = [];
        while ($row = $result->fetch_assoc()) {
            $routes[] = $row;
        }

        return [true, $routes];
    }

    public function findNearestRoute($latitude, $longitude, $limit = 3)
    {
        if (!($stmt = $this->mysql->prepare(
            "SELECT er.id, er.name, er.shelter_id, er.from_latitude, er.from_longitude,
                    er.distance_meters, er.estimated_minutes, er.status,
                    s.name AS shelter_name,
                    ST_Distance_Sphere(er.from_geom, ST_GeomFromText(?, 4326)) AS distance_from_point
             FROM evacuation_routes er
             JOIN shelters s ON er.shelter_id = s.id
             WHERE er.status = 'active'
             ORDER BY distance_from_point ASC
             LIMIT ?"
        ))) {
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        $point = "POINT($longitude $latitude)";

        if (!$stmt->bind_param('si', $point, $limit)) {
            return [false, 'Eroare la legarea parametrilor: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogarii: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Eroare la obtinerea rezultatului: ' . $this->mysql->error];
        }

        $routes = [];
        while ($row = $result->fetch_assoc()) {
            $row['distance_from_point'] = round($row['distance_from_point'], 0);
            $routes[] = $row;
        }

        return [true, $routes];
    }
}
