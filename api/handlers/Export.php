<?php

namespace Handlers;

class Export
{
    private static $mimeTypes = [
        'csv' => 'text/csv; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
    ];

    public static function handle($eventModel, $shelterModel, $routeModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'json';

        if (!array_key_exists($format, self::$mimeTypes)) {
            $format = 'json';
        }

        $resource = isset($_GET['resource']) ? strtolower($_GET['resource']) : 'all';

        switch ($resource) {
            case 'events':
                self::export($eventModel, 'emergency_events', $format);
                break;
            case 'shelters':
                self::export($shelterModel, 'shelters', $format);
                break;
            case 'routes':
                self::export($routeModel, 'evacuation_routes', $format);
                break;
            case 'all':
                self::exportAll($eventModel, $shelterModel, $routeModel, $format);
                break;
            default:
                \sendJsonResponse(['error' => 'Invalid resource. Use events, shelters, routes, or all.'], 400);
        }
    }

    private static function export($model, $label, $format)
    {
        [$ok, $data] = $model->getAll();

        if (!$ok) {
            \sendJsonResponse(['error' => $data], 500);
        }

        self::output($data, $label, $format);
    }

    private static function exportAll($eventModel, $shelterModel, $routeModel, $format)
    {
        [, $events] = $eventModel->getAll();
        [, $shelters] = $shelterModel->getAll();
        [, $routes] = $routeModel->getAll();

        $all = [
            'events' => $events,
            'shelters' => $shelters,
            'routes' => $routes,
        ];

        self::output($all, 'coa_export', $format);
    }

    private static function output($data, $label, $format)
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = $label . '_' . $timestamp . '.' . $format;

        header('Content-Type: ' . self::$mimeTypes[$format]);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache');
        header('Pragma: no-cache');

        if ($format === 'csv') {
            self::writeCsv($data);
        } else {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    private static function writeCsv($data)
    {
        $fp = fopen('php://output', 'w');

        if (is_array($data) && isset($data[0])) {
            $headers = array_keys($data[0]);
            fputcsv($fp, $headers);

            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
        } else if (is_array($data) && isset($data['events']) && isset($data['shelters']) && isset($data['routes'])) {
            foreach (['events', 'shelters', 'routes'] as $section) {
                if (isset($data[$section]) && is_array($data[$section]) && isset($data[$section][0])) {
                    fputcsv($fp, ['--- ' . $section . ' ---']);
                    $headers = array_keys($data[$section][0]);
                    fputcsv($fp, $headers);

                    foreach ($data[$section] as $row) {
                        fputcsv($fp, $row);
                    }
                }
            }
        } else {
            fputcsv($fp, []);
        }

        fclose($fp);
    }
}
