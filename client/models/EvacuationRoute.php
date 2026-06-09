<?php

namespace ClientModels;

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

    public function getPaginated($page, $pageSize, $search, $sort, $sortDir)
    {
        $params = http_build_query([
            'page' => $page,
            'size' => $pageSize,
            'q' => $search,
            'sort' => $sort,
            'dir' => $sortDir
        ]);
        [$ok, $res] = $this->client->request('GET', 'routes?' . $params);
        if ($ok && isset($res['rows'])) {
            return [true, $res];
        }
        return [false, $res];
    }

    public function create(array $data)
    {
        return $this->client->request('POST', 'routes', $data);
    }

    public function update($id, array $data)
    {
        return $this->client->request('PATCH', 'routes/' . (int) $id, $data);
    }

    public function delete($id)
    {
        return $this->client->request('DELETE', 'routes/' . (int) $id);
    }
}
