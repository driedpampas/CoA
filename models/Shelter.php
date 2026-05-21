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
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogarii: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Eroare la obtinerea rezultatului: ' . $this->mysql->error];
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
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        if (!$stmt->bind_param('i', $id)) {
            return [false, 'Eroare la legarea parametrilor: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogarii: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Eroare la obtinerea rezultatului: ' . $this->mysql->error];
        }

        if ($result->num_rows === 0) {
            return [false, 'Adapostul nu a fost gasit.'];
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
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        $point = "POINT($longitude $latitude)";

        if (!$stmt->bind_param('sid', $point, $point, $radiusMeters)) {
            return [false, 'Eroare la legarea parametrilor: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogarii: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Eroare la obtinerea rezultatului: ' . $this->mysql->error];
        }

        $shelters = [];
        while ($row = $result->fetch_assoc()) {
            $row['distance_meters'] = round($row['distance_meters'], 0);
            $shelters[] = $row;
        }

        return [true, $shelters];
    }
}
