<?php

namespace ClientModels;

class Notification
{
    private $client;

    public function __construct($apiBaseUrl = null)
    {
        $this->client = new HttpClient($apiBaseUrl);
    }

    public function getUnreadCount($userId = null)
    {
        [$ok, $res] = $this->client->request('GET', 'notifications/unread-count');
        return [$ok, $res['count'] ?? 0];
    }
}
