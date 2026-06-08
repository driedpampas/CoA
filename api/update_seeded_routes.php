<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

$routes = [
    [
        'name' => 'Bahlui -> Sala Polivalenta Route',
        'shelter_id' => 1,
        'from_lat' => 47.1500,
        'from_lng' => 27.5950,
        'to_lat' => 47.1550,
        'to_lng' => 27.5980,
        'status' => 'active',
        'notes' => 'Main route from the flooded area'
    ],
    [
        'name' => 'Nicolina -> Student Complex Route',
        'shelter_id' => 6,
        'from_lat' => 47.1460,
        'from_lng' => 27.6020,
        'to_lat' => 47.1530,
        'to_lng' => 27.6120,
        'status' => 'blocked',
        'notes' => 'Route blocked due to the fire'
    ],
    [
        'name' => 'Copou -> CFR Stadium Route',
        'shelter_id' => 2,
        'from_lat' => 47.1780,
        'from_lng' => 27.5680,
        'to_lat' => 47.1630,
        'to_lng' => 27.5750,
        'status' => 'active',
        'notes' => 'Secondary route through the Copou area'
    ],
    [
        'name' => 'City Center -> Military Circle Route',
        'shelter_id' => 4,
        'from_lat' => 47.1610,
        'from_lng' => 27.5880,
        'to_lat' => 47.1600,
        'to_lng' => 27.5820,
        'status' => 'active',
        'notes' => 'Short, in the city center'
    ],
    [
        'name' => 'Tatarasi -> Community Center Route',
        'shelter_id' => 7,
        'from_lat' => 47.1710,
        'from_lng' => 27.5800,
        'to_lat' => 47.1700,
        'to_lng' => 27.5950,
        'status' => 'active',
        'notes' => 'Accessible from the gas leak area'
    ]
];

$sqlLines = [];

foreach ($routes as $route) {
    $url = "https://router.project-osrm.org/route/v1/driving/" .
           $route['from_lng'] . "," . $route['from_lat'] . ";" .
           $route['to_lng'] . "," . $route['to_lat'] . "?overview=full&geometries=geojson";
    
    echo "Fetching route: {$route['name']}...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $res = curl_exec($ch);
    if ($res === false) {
        echo "Error fetching OSRM route: " . curl_error($ch) . "\n";
        continue;
    }
    
    $data = json_decode($res, true);
    curl_close($ch);
    
    if (empty($data['routes']) || empty($data['routes'][0]['geometry']['coordinates'])) {
        echo "Invalid route response from OSRM.\n";
        continue;
    }
    
    $routeInfo = $data['routes'][0];
    $coords = $routeInfo['geometry']['coordinates'];
    $distance = (int)$routeInfo['distance'];
    $duration = (int)ceil($routeInfo['duration'] / 60);
    
    // Format coordinate list for LINESTRING WKT
    $wktCoords = [];
    foreach ($coords as $coord) {
        $wktCoords[] = "{$coord[0]} {$coord[1]}"; // "lng lat"
    }
    $wkt = "LINESTRING(" . implode(", ", $wktCoords) . ")";
    
    // Update the database
    $stmt = $mysql->prepare("UPDATE evacuation_routes SET route_geometry = ST_GeomFromText(?, 4326), distance_meters = ?, estimated_minutes = ? WHERE name = ?");
    if (!$stmt) {
        echo "Error preparing update: " . $mysql->error . "\n";
        continue;
    }
    
    $stmt->bind_param('sdis', $wkt, $distance, $duration, $route['name']);
    if (!$stmt->execute()) {
        echo "Error executing update: " . $stmt->error . "\n";
        continue;
    }
    $stmt->close();
    
    echo "Successfully updated database for {$route['name']}: {$distance}m, {$duration}min\n";
    
    // Format sql seed line
    $sqlLines[] = "('{$route['name']}', {$route['shelter_id']}, {$route['from_lat']}, {$route['from_lng']}, ST_GeomFromText('POINT({$route['from_lng']} {$route['from_lat']})', 4326), ST_GeomFromText('{$wkt}', 4326), {$distance}, {$duration}, '{$route['status']}', '{$route['notes']}')";
}

// Print seed SQL values block
echo "\n=== SQL VALUES BLOCK FOR seed.sql ===\n";
echo "INSERT INTO evacuation_routes (name, shelter_id, from_latitude, from_longitude, from_geom, route_geometry, distance_meters, estimated_minutes, status, notes) VALUES\n";
echo implode(",\n", $sqlLines) . ";\n";
