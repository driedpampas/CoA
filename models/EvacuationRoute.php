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
}
