<?php
require_once __DIR__ . '/../../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

$shelterModel = new \ClientModels\Shelter($apiBaseUrl);
$routeModel = new \ClientModels\EvacuationRoute($apiBaseUrl);
$eventModel = new \ClientModels\Event($apiBaseUrl);
$accountModel = new \ClientModels\Account($apiBaseUrl);
$notificationModel = new \ClientModels\Notification($apiBaseUrl);

if (!empty($_SESSION['isLoggedIn']) && empty($_SESSION['user_id'])) {
    $sessionUserId = $accountModel->getUserId($_SESSION['username'] ?? '');
    if ($sessionUserId) {
        $_SESSION['user_id'] = $sessionUserId;
    }
}

$currentUserId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

function haversineDistanceApprox($lat1, $lng1, $lat2, $lng2)
{
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

function sendJsonResponse($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function getFallbackShelterInfo($profile, $preferredShelterId, $accountModel)
{
    if (!$preferredShelterId) {
        return [null, null, null];
    }

    $status = $accountModel->getShelterStatus($preferredShelterId);
    if (empty($status) || ($status['status'] !== 'full' && $status['status'] !== 'closed')) {
        return [$status, null, null];
    }

    if (empty($profile['last_latitude']) || empty($profile['last_longitude'])) {
        return [$status, null, null];
    }

    [$ok, $fallback] = $accountModel->findNearestOpenShelter(
        $profile['last_latitude'],
        $profile['last_longitude'],
        $preferredShelterId
    );
    if (!$ok || !$fallback) {
        return [$status, null, null];
    }

    $distance = haversineDistanceApprox(
        (float) $profile['last_latitude'],
        (float) $profile['last_longitude'],
        (float) $fallback['latitude'],
        (float) $fallback['longitude']
    );

    return [$status, $fallback, $distance];
}

function filterEventsByRadius($events, $profile)
{
    $radiusKm = isset($profile['notification_radius_km']) ? (float) $profile['notification_radius_km'] : null;
    if ($radiusKm === null || $radiusKm <= 0) {
        return $events;
    }

    if (empty($profile['last_latitude']) || empty($profile['last_longitude'])) {
        return $events;
    }

    $userLat = (float) $profile['last_latitude'];
    $userLng = (float) $profile['last_longitude'];
    $radMeters = $radiusKm * 1000;

    return array_values(array_filter($events, function ($event) use ($userLat, $userLng, $radMeters) {
        if (empty($event['latitude']) || empty($event['longitude'])) {
            return false;
        }

        $distance = haversineDistanceApprox($userLat, $userLng, (float) $event['latitude'], (float) $event['longitude']);
        return ($distance * 1000) <= $radMeters;
    }));
}

function getPreferredShelterDistance($profile, $preferredShelterId, $shelterModel)
{
    if (!$preferredShelterId || empty($profile['last_latitude']) || empty($profile['last_longitude'])) {
        return null;
    }

    [$ok, $preferredShelter] = $shelterModel->findById($preferredShelterId);
    if (!$ok || !$preferredShelter) {
        return null;
    }

    return haversineDistanceApprox(
        (float) $profile['last_latitude'],
        (float) $profile['last_longitude'],
        (float) $preferredShelter['latitude'],
        (float) $preferredShelter['longitude']
    );
}

function getDashboardData($currentUserId, $eventModel, $shelterModel, $notificationModel, $accountModel)
{
    [$ok, $events] = $eventModel->getActive();
    $events = $ok ? $events : [];

    [$ok, $shelters] = $shelterModel->getAll();
    $shelters = $ok ? $shelters : [];

    [$ok, $unreadCount] = $notificationModel->getUnreadCount($currentUserId);
    $unreadCount = $ok ? $unreadCount : 0;

    $isLoggedIn = !empty($_SESSION["isLoggedIn"]);
    $username = $_SESSION["username"] ?? "";
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

    $profileRadius = null;
    $profilePreferredShelterId = null;
    $preferredShelterStatus = null;
    $fallbackShelter = null;
    $fallbackDistance = null;

    if ($currentUserId) {
        [$okP, $profile] = $accountModel->getProfile($currentUserId);
        if ($okP && $profile) {
            $profileRadius = isset($profile['notification_radius_km']) ? (int) $profile['notification_radius_km'] : null;
            $profilePreferredShelterId = isset($profile['preferred_shelter_id']) && $profile['preferred_shelter_id'] !== null ? (int) $profile['preferred_shelter_id'] : null;

            [$preferredShelterStatus, $fallbackShelter, $fallbackDistance] = getFallbackShelterInfo($profile, $profilePreferredShelterId, $accountModel);
            $events = filterEventsByRadius($events, $profile);
        }
    }

    return [
        'events' => $events,
        'shelters' => $shelters,
        'unreadCount' => $unreadCount,
        'isLoggedIn' => $isLoggedIn,
        'username' => $username,
        'isAdmin' => $isAdmin,
        'profileRadius' => $profileRadius,
        'profilePreferredShelterId' => $profilePreferredShelterId,
        'preferredShelterStatus' => $preferredShelterStatus,
        'fallbackShelter' => $fallbackShelter,
        'fallbackDistance' => $fallbackDistance,
        'currentUserId' => $currentUserId,
    ];
}

function getProfileData($currentUserId, $shelterModel, $accountModel)
{
    [$okP, $profile] = $accountModel->getProfile($currentUserId);
    $profile = $okP ? $profile : null;

    [$okS, $sheltersForDropdown] = $shelterModel->getAll();
    $sheltersForDropdown = $okS ? $sheltersForDropdown : [];

    $shelterStatus = null;
    $fallbackShelter = null;
    $fallbackDistance = null;
    $preferredShelterDistance = null;

    if ($profile && !empty($profile['preferred_shelter_id'])) {
        $preferredShelterId = (int) $profile['preferred_shelter_id'];
        [$shelterStatus, $fallbackShelter, $fallbackDistance] = getFallbackShelterInfo($profile, $preferredShelterId, $accountModel);
        $preferredShelterDistance = getPreferredShelterDistance($profile, $preferredShelterId, $shelterModel);
    }

    $isLoggedIn = !empty($_SESSION['isLoggedIn']);
    $username = $_SESSION['username'] ?? "";
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

    return [
        'profile' => $profile,
        'shelterStatus' => $shelterStatus,
        'fallbackShelter' => $fallbackShelter,
        'fallbackDistance' => $fallbackDistance,
        'preferredShelterDistance' => $preferredShelterDistance,
        'sheltersForDropdown' => $sheltersForDropdown,
        'isLoggedIn' => $isLoggedIn,
        'username' => $username,
        'isAdmin' => $isAdmin,
    ];
}

$routeLevels = $routeLevels ?? [];
$page = $routeLevels[0] ?? 'dashboard';

switch ($page) {
    case 'dashboard': {
        $data = getDashboardData($currentUserId, $eventModel, $shelterModel, $notificationModel, $accountModel);
        extract($data);
        include __DIR__ . '/../views/PublicDashboard.php';
        break;
    }

    case 'profile': {
        if (empty($_SESSION['isLoggedIn'])) {
            header('Location: login');
            exit;
        }

        $data = getProfileData($currentUserId, $shelterModel, $accountModel);
        extract($data);
        include __DIR__ . '/../views/ProfileView.php';
        break;
    }

    default: {
        header('Location: dashboard');
        exit;
    }
}
