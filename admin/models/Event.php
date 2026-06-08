<?php

namespace Models;

class Event
{
    private $client;

    public function __construct($apiBaseUrl = null)
    {
        $this->client = new HttpClient($apiBaseUrl);
    }

    public function getPaginated($page, $pageSize, $search, $sort, $sortDir)
    {
        $params = http_build_query([
            'page' => $page,
            'size' => $pageSize,
            'q' => $search,
            'sort' => $sort,
            'dir' => $sortDir
        ]);
        [$ok, $res] = $this->client->request('GET', 'events?' . $params);
        if ($ok && isset($res['rows'])) {
            return [true, $res];
        }
        return [false, $res];
    }

    public function create(array $data)
    {
        return $this->client->request('POST', 'events', $data);
    }

    public function update($id, array $data)
    {
        return $this->client->request('PATCH', 'events/' . (int) $id, $data);
    }

    public function delete($id)
    {
        return $this->client->request('DELETE', 'events/' . (int) $id);
    }
}
