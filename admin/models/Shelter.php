<?php

namespace Models;

class Shelter
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

    public function getAll()
    {
        return $this->client->request('GET', 'shelters');
    }

    public function delete($id)
    {
        return $this->client->request('DELETE', 'shelters/' . (int) $id);
    }
}
