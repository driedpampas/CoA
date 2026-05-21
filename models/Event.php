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
            return [false, 'Eroare la pregatirea interogarii: ' . $this->mysql->error];
        }

        if (!$stmt->execute()) {
            return [false, 'Eroare la executarea interogarii: ' . $this->mysql->error];
        }

        if (!($result = $stmt->get_result())) {
            return [false, 'Eroare la obtinerea rezultatului: ' . $this->mysql->error];
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        return [true, $events];
    }

    public function getActiveForCapFeed()
    {
        if (
            !($stmt = $this->mysql->prepare(
                "SELECT id, event_type, title, description, severity, latitude, longitude, started_at
             FROM emergency_events
             WHERE status = 'active'
               AND severity IN ('high', 'extreme')
             ORDER BY started_at DESC"
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

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        return [true, $events];
    }
}
