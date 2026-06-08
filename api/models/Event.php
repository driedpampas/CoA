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
}
