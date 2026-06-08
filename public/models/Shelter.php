<?php

namespace Models;

class Shelter
{
    private $client;

    public function __construct($apiBaseUrl = null)
    {
        $this->client = new HttpClient($apiBaseUrl);
    }

    public function getAll()
    {
        [$ok, $res] = $this->client->request('GET', 'shelters');
        return [$ok, $res];
    }

    public function findById($id)
    {
        [$ok, $res] = $this->client->request('GET', 'shelters/' . urlencode($id));
        return [$ok, $res];
    }

    public function findNearest($latitude, $longitude, $limit = 5)
    {
        [$ok, $res] = $this->client->request('GET', 'shelters/nearest?lat=' . urlencode($latitude) . '&lng=' . urlencode($longitude) . '&limit=' . urlencode($limit));
        return [$ok, $res];
    }
}
