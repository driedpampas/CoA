<?php

namespace Handlers;

class Import
{
    public static function handle($eventModel, $shelterModel, $accountModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        // Check admin authentication
        $isAdmin = ($_SESSION['isLoggedIn'] ?? false) && ($_SESSION['role'] ?? '') === 'admin';
        if (!$isAdmin) {
            \sendJsonResponse(['error' => 'Forbidden: admin access required.'], 403);
        }

        // Check CSRF — accept either the header (JS fetch) or the POST field (form fallback)
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            \sendJsonResponse(['error' => 'Invalid CSRF token.'], 400);
        }

        $resource = isset($_GET['resource']) ? strtolower($_GET['resource']) : '';
        if (!in_array($resource, ['events', 'shelters'], true)) {
            \sendJsonResponse(['error' => 'Invalid resource type. Use events or shelters.'], 400);
        }

        if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            \sendJsonResponse(['error' => 'No file uploaded or upload error occurred.'], 400);
        }

        $fileInfo = $_FILES['import_file'];
        $tmpPath = $fileInfo['tmp_name'];
        $filename = $fileInfo['name'];
        $fileSize = $fileInfo['size'];

        if ($fileSize > 5 * 1024 * 1024) {
            \sendJsonResponse(['error' => 'File size exceeds 5MB limit.'], 400);
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        if ($ext === 'json') {
            $content = file_get_contents($tmpPath);
            $data = json_decode($content, true);
            if (!is_array($data)) {
                \sendJsonResponse(['error' => 'Invalid JSON structure.'], 400);
            }

            // If it's a single object, wrap it in an array
            if (isset($data['event_type']) || isset($data['name']) || isset($data['title'])) {
                $data = [$data];
            }

            foreach ($data as $index => $item) {
                if (!is_array($item)) {
                    $skippedCount++;
                    $errors[] = "Row {$index}: Item is not an object.";
                    continue;
                }

                if ($resource === 'events') {
                    if (empty($item['title'])) {
                        $skippedCount++;
                        $errors[] = "Row {$index}: Missing title.";
                        continue;
                    }
                    [$ok, $res] = $eventModel->create($item);
                    if ($ok) {
                        $importedCount++;
                    } else {
                        $skippedCount++;
                        $errors[] = "Row {$index}: " . $res;
                    }
                } else {
                    if (empty($item['name']) || empty($item['address']) || !isset($item['latitude']) || !isset($item['longitude'])) {
                        $skippedCount++;
                        $errors[] = "Row {$index}: Missing required fields (name, address, latitude, longitude).";
                        continue;
                    }
                    [$ok, $res] = $shelterModel->create($item);
                    if ($ok) {
                        $importedCount++;
                    } else {
                        $skippedCount++;
                        $errors[] = "Row {$index}: " . $res;
                    }
                }
            }
        } elseif ($ext === 'csv') {
            $fp = fopen($tmpPath, 'r');
            if (!$fp) {
                \sendJsonResponse(['error' => 'Failed to open uploaded file.'], 500);
            }

            // Read BOM if present
            $bom = fread($fp, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($fp);
            }

            $headers = fgetcsv($fp);
            if (!$headers) {
                fclose($fp);
                \sendJsonResponse(['error' => 'CSV file is empty.'], 400);
            }

            // Normalize headers
            $headers = array_map(function ($h) {
                return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
            }, $headers);

            $rowCount = 0;
            while (($row = fgetcsv($fp)) !== false) {
                $rowCount++;
                
                // Skip empty rows
                if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                    continue;
                }

                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                }
                
                $item = array_combine(array_slice($headers, 0, count($row)), array_slice($row, 0, count($headers)));
                
                if ($resource === 'events') {
                    $eventData = [
                        'event_type' => $item['event_type'] ?? $item['type'] ?? 'other',
                        'title' => $item['title'] ?? '',
                        'description' => $item['description'] ?? '',
                        'severity' => $item['severity'] ?? 'moderate',
                        'latitude' => isset($item['latitude']) && $item['latitude'] !== '' ? (float)$item['latitude'] : null,
                        'longitude' => isset($item['longitude']) && $item['longitude'] !== '' ? (float)$item['longitude'] : null,
                        'status' => $item['status'] ?? 'active'
                    ];

                    if (empty($eventData['title'])) {
                        $skippedCount++;
                        $errors[] = "Row {$rowCount}: Missing title.";
                        continue;
                    }

                    [$ok, $res] = $eventModel->create($eventData);
                    if ($ok) {
                        $importedCount++;
                    } else {
                        $skippedCount++;
                        $errors[] = "Row {$rowCount}: " . $res;
                    }
                } else {
                    $shelterData = [
                        'name' => $item['name'] ?? '',
                        'address' => $item['address'] ?? '',
                        'latitude' => isset($item['latitude']) && $item['latitude'] !== '' ? (float)$item['latitude'] : null,
                        'longitude' => isset($item['longitude']) && $item['longitude'] !== '' ? (float)$item['longitude'] : null,
                        'capacity' => isset($item['capacity']) && $item['capacity'] !== '' ? (int)$item['capacity'] : 0,
                        'current_occupancy' => isset($item['current_occupancy']) && $item['current_occupancy'] !== '' ? (int)$item['current_occupancy'] : 0,
                        'shelter_type' => $item['shelter_type'] ?? $item['type'] ?? 'community',
                        'status' => $item['status'] ?? 'open',
                        'contact_phone' => $item['contact_phone'] ?? $item['phone'] ?? null,
                        'notes' => $item['notes'] ?? null
                    ];

                    if (empty($shelterData['name']) || empty($shelterData['address']) || $shelterData['latitude'] === null || $shelterData['longitude'] === null) {
                        $skippedCount++;
                        $errors[] = "Row {$rowCount}: Missing required fields (name, address, latitude, longitude).";
                        continue;
                    }

                    [$ok, $res] = $shelterModel->create($shelterData);
                    if ($ok) {
                        $importedCount++;
                    } else {
                        $skippedCount++;
                        $errors[] = "Row {$rowCount}: " . $res;
                    }
                }
            }
            fclose($fp);
        } else {
            \sendJsonResponse(['error' => 'Unsupported file format. Please upload CSV or JSON.'], 400);
        }

        \sendJsonResponse([
            'success' => true,
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'errors' => $errors
        ]);
    }
}
