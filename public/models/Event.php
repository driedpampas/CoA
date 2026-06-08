<?php

namespace Models;

class Event
{
    private $client;

    public function __construct($apiBaseUrl = null)
    {
        $this->client = new HttpClient($apiBaseUrl);
    }

    public function getActive()
    {
        [$ok, $res] = $this->client->request('GET', 'events');
        return [$ok, $res];
    }

    public function getRecentForMap($days = 1)
    {
        [$ok, $res] = $this->client->request('GET', 'events?days=' . urlencode($days));
        return [$ok, $res];
    }
}
