<?php
require_once __DIR__ . '/../../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../models/HttpClient.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Shelter.php';
require_once __DIR__ . '/../models/EvacuationRoute.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Account.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

$shelterModel = new \Models\Shelter($apiBaseUrl);
$routeModel = new \Models\EvacuationRoute($apiBaseUrl);
$eventModel = new \Models\Event($apiBaseUrl);
$accountModel = new \Models\Account($apiBaseUrl);
$notificationModel = new \Models\Notification($apiBaseUrl);

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

$routeLevels = $routeLevels ?? [];
$page = $routeLevels[0] ?? 'dashboard';

switch ($page) {
    case 'dashboard': {
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

                if ($profilePreferredShelterId) {
                    $preferredShelterStatus = $accountModel->getShelterStatus($profilePreferredShelterId);
                    if (!empty($preferredShelterStatus) && ($preferredShelterStatus['status'] === 'full' || $preferredShelterStatus['status'] === 'closed')) {
                        if (!empty($profile['last_latitude']) && !empty($profile['last_longitude'])) {
                            [$okF, $fallbackShelter] = $accountModel->findNearestOpenShelter(
                                $profile['last_latitude'],
                                $profile['last_longitude'],
                                $profilePreferredShelterId
                            );
                            if ($okF && $fallbackShelter) {
                                $fallbackDistance = haversineDistanceApprox(
                                    (float) $profile['last_latitude'],
                                    (float) $profile['last_longitude'],
                                    (float) $fallbackShelter['latitude'],
                                    (float) $fallbackShelter['longitude']
                                );
                            }
                        }
                    }
                }

                if ($profileRadius !== null && $profileRadius > 0 && !empty($profile['last_latitude']) && !empty($profile['last_longitude'])) {
                    $radKm = (float) $profileRadius;
                    $radMeters = $radKm * 1000;
                    $userLat = (float) $profile['last_latitude'];
                    $userLng = (float) $profile['last_longitude'];
                    $events = array_values(array_filter($events, function ($event) use ($userLat, $userLng, $radMeters) {
                        if (empty($event['latitude']) || empty($event['longitude']))
                            return false;
                        $dLat = deg2rad((float) $event['latitude'] - $userLat);
                        $dLng = deg2rad((float) $event['longitude'] - $userLng);
                        $a = sin($dLat / 2) * sin($dLat / 2) +
                            cos(deg2rad($userLat)) * cos(deg2rad((float) $event['latitude'])) *
                            sin($dLng / 2) * sin($dLng / 2);
                        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                        return (6371 * $c) * 1000 <= $radMeters;
                    }));
                }
            }
        }

        include __DIR__ . '/../views/PublicDashboard.php';
        break;
    }



    case 'profile': {
        if (empty($_SESSION['isLoggedIn'])) {
            header('Location: login');
            exit;
        }

        $profile = null;
        $shelterStatus = null;
        $fallbackShelter = null;
        $fallbackDistance = null;
        $preferredShelterDistance = null;
        $sheltersForDropdown = [];

        if ($currentUserId) {
            [$ok, $profile] = $accountModel->getProfile($currentUserId);
            $profile = $ok ? $profile : null;

            [$okS, $sheltersForDropdown] = $shelterModel->getAll();
            $sheltersForDropdown = $okS ? $sheltersForDropdown : [];

            if ($profile) {
                if (!empty($profile['preferred_shelter_id'])) {
                    $preferredShelterId = (int) $profile['preferred_shelter_id'];
                    $shelterStatus = $accountModel->getShelterStatus($preferredShelterId);

                    if (!empty($shelterStatus) && ($shelterStatus['status'] === 'full' || $shelterStatus['status'] === 'closed')) {
                        if (!empty($profile['last_latitude']) && !empty($profile['last_longitude'])) {
                            [$ok, $fallbackShelter] = $accountModel->findNearestOpenShelter(
                                $profile['last_latitude'],
                                $profile['last_longitude'],
                                $preferredShelterId
                            );
                            if ($ok && $fallbackShelter) {
                                $fallbackDistance = haversineDistanceApprox(
                                    (float) $profile['last_latitude'],
                                    (float) $profile['last_longitude'],
                                    (float) $fallbackShelter['latitude'],
                                    (float) $fallbackShelter['longitude']
                                );
                            }
                        }
                    }

                    if (!empty($profile['last_latitude']) && !empty($profile['last_longitude']) && $preferredShelterId) {
                        [$ok, $preferredShelter] = $shelterModel->findById($preferredShelterId);
                        if ($ok && $preferredShelter) {
                            $preferredShelterDistance = haversineDistanceApprox(
                                (float) $profile['last_latitude'],
                                (float) $profile['last_longitude'],
                                (float) $preferredShelter['latitude'],
                                (float) $preferredShelter['longitude']
                            );
                        }
                    }
                }
            }
        }

        $isLoggedIn = !empty($_SESSION['isLoggedIn']);
        $username = $_SESSION['username'] ?? "";

        include __DIR__ . '/../views/ProfileView.php';
        break;
    }

    default: {
        header('Location: dashboard');
        exit;
    }
}
