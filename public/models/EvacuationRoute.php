<?php

namespace Models;

class EvacuationRoute
{
    private $client;

    public function __construct($apiBaseUrl = null)
    {
        $this->client = new HttpClient($apiBaseUrl);
    }

    public function findNearestRoute($latitude, $longitude, $limit = 3)
    {
        [$ok, $res] = $this->client->request('GET', 'routes/nearest?lat=' . urlencode($latitude) . '&lng=' . urlencode($longitude) . '&limit=' . urlencode($limit));
        return [$ok, $res];
    }
}
