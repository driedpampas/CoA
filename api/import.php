<?php
set_time_limit(60);

require_once __DIR__ . '/../config/db.php';
if (!isset($mysql) || !($mysql instanceof mysqli)) {
    echo "Error: Database connection not available.\n";
    exit(3);
}

//Fetching information that is at most 24h old
$timeWindow = '-1 day';
$startTime = date('Y-m-d\TH:i:s', strtotime($timeWindow));

$apiUrl = "https://earthquake.usgs.gov/fdsnws/event/1/query?format=text&starttime=" . urlencode($startTime) . "&minmagnitude=4.0";

if (php_sapi_name() !== 'cli')
    echo "<pre>";
echo "Fetching data (since $startTime)\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    CURLOPT_HEADER => false,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);

//In case nothing is found
if ($httpCode === 204 || ($httpCode === 200 && trim($body) === '')) {
    echo "Import finished. No new events found (HTTP 204).\n";
    if (php_sapi_name() !== 'cli')
        echo "</pre>";
    return 0;
}

if ($body === false || $httpCode !== 200) {
    echo "Network Error. HTTP Status: {$httpCode}. cURL Error: {$curlErr}\n";
    exit(5);
}

//Parsing data
$fp = fopen('php://temp', 'r+');
fwrite($fp, $body);
rewind($fp);

$delimiter = '|';
$header = fgetcsv($fp, 0, $delimiter);

if ($header === false) {
    echo "Error: Invalid data structure received.\n";
    exit(6);
}

$header[0] = ltrim($header[0], '#');
$cols = array_map(function ($c) {
    return strtolower(trim($c));
}, $header);

function colIndex($name, $cols)
{
    $i = array_search($name, $cols);
    return $i === false ? -1 : $i;
}

//Mapping according to the database format
$idx_time = colIndex('time', $cols);
$idx_lat = colIndex('latitude', $cols);
$idx_lon = colIndex('longitude', $cols);
$idx_depth = colIndex('depth/km', $cols);
$idx_mag = colIndex('magnitude', $cols);
$idx_type = colIndex('magtype', $cols);
$idx_place = colIndex('eventlocationname', $cols);

//Deleting data older than 24h
$deleteSql = "DELETE FROM emergency_events 
              WHERE event_type = 'earthquake' 
              AND started_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)";

if (!$mysql->query($deleteSql)) {
    echo "Warning: Failed to clear out old data: " . $mysql->error . "\n";
} else {
    echo "Cleanup complete.\n";
}

//Inserting new data
$insertSql = "INSERT INTO emergency_events (event_type, title, description, severity, latitude, longitude, status, started_at)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $mysql->prepare($insertSql);

$inserted = 0;
$skipped = 0;
$errors = 0;
$mysql->begin_transaction();

while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
    $latRaw = $idx_lat >= 0 ? trim($row[$idx_lat]) : '';
    $lonRaw = $idx_lon >= 0 ? trim($row[$idx_lon]) : '';

    if ($latRaw === '' || $lonRaw === '' || !is_numeric($latRaw) || !is_numeric($lonRaw)) {
        $skipped++;
        continue;
    }

    $lat = (float) $latRaw;
    $lon = (float) $lonRaw;

    $timeRaw = $idx_time >= 0 ? trim($row[$idx_time]) : '';
    $magRaw = $idx_mag >= 0 ? trim($row[$idx_mag]) : '';
    $depthRaw = $idx_depth >= 0 ? trim($row[$idx_depth]) : '';
    $typeRaw = $idx_type >= 0 ? trim($row[$idx_type]) : '';
    $placeRaw = $idx_place >= 0 ? trim($row[$idx_place]) : '';

    $startedAt = date('Y-m-d H:i:s', strtotime($timeRaw));

    $mag = is_numeric($magRaw) ? (float) $magRaw : null;
    if ($mag === null)
        $severity = 'moderate';
    else if ($mag < 4.0)
        $severity = 'low';
    else if ($mag < 5.0)
        $severity = 'moderate';
    else if ($mag < 6.0)
        $severity = 'high';
    else
        $severity = 'extreme';

    $magText = $mag !== null ? "M{$mag}" : 'M?';
    $location = ($placeRaw !== '') ? $placeRaw : 'Romania';
    $title = "Earthquake {$magText} — {$location}";

    $description = "Depth: {$depthRaw} km; Type: {$typeRaw}; Coordinates: {$lat}, {$lon}";
    $eventType = 'earthquake';
    $status = 'active';

    try {
        $stmt->bind_param('ssssddss', $eventType, $title, $description, $severity, $lat, $lon, $status, $startedAt);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        //Skipping duplicates
        if ($e->getCode() == 1062) {
            $skipped++;
            continue;
        }

        //Other SQL errors: log and count
        echo "SQL Error: " . $e->getMessage() . "\n";
        $errors++;
        continue;
    }

    $inserted++;
}

$mysql->commit();
fclose($fp);
$stmt->close();

echo "Sync Completed Successfully!\n";
echo "New: {$inserted} | Skipped/Duplicates: {$skipped} | Errors: {$errors}\n";
if (php_sapi_name() !== 'cli')
    echo "</pre>";