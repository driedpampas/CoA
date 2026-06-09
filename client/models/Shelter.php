<?php

namespace ClientModels;

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

    public function getPaginated($page, $pageSize, $search, $sort, $sortDir)
    {
        $params = http_build_query([
            'page' => $page,
            'size' => $pageSize,
            'q' => $search,
            'sort' => $sort,
            'dir' => $sortDir
        ]);
        [$ok, $res] = $this->client->request('GET', 'shelters?' . $params);
        if ($ok && isset($res['rows'])) {
            return [true, $res];
        }
        return [false, $res];
    }

    public function create(array $data)
    {
        return $this->client->request('POST', 'shelters', $data);
    }

    public function update($id, array $data)
    {
        return $this->client->request('PATCH', 'shelters/' . (int) $id, $data);
    }

    public function delete($id)
    {
        return $this->client->request('DELETE', 'shelters/' . (int) $id);
    }
}
