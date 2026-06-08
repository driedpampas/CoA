<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$shelterModel = new \Models\Shelter($mysql);
$routeModel = new \Models\EvacuationRoute($mysql);
$eventModel = new \Models\Event($mysql);
$accountModel = new \Models\Account($mysql);
$notificationModel = new \Models\Notification($mysql);

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
                        if (empty($event['latitude']) || empty($event['longitude'])) return false;
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

    case 'api': {
        $resource = $routeLevels[1] ?? '';
        $action = $routeLevels[2] ?? '';

        switch ($resource) {
            case 'events':
                \Handlers\Events::handle($eventModel);
                break;
            case 'shelters':
                \Handlers\Shelters::handle($shelterModel, $action);
                break;
            case 'routes':
                \Handlers\Routes::handle($routeModel, $action);
                break;
            case 'auth':
                \Handlers\Auth::handle($accountModel, $action);
                break;
            case 'notifications':
                \Handlers\Notifications::handle($notificationModel, $action, $routeLevels[3] ?? '', $currentUserId);
                break;
            default:
                sendJsonResponse(['error' => 'Not found.'], 404);
                break;
        }
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
        $sheltersForDropdown = [];

        if ($currentUserId) {
            [$ok, $profile] = $accountModel->getProfile($currentUserId);
            $profile = $ok ? $profile : null;

            [$okS, $sheltersForDropdown] = $shelterModel->getAll();
            $sheltersForDropdown = $okS ? $sheltersForDropdown : [];

            if ($profile && !empty($profile['preferred_shelter_id'])) {
                $shelterStatus = $accountModel->getShelterStatus((int) $profile['preferred_shelter_id']);
                if (!empty($shelterStatus) && ($shelterStatus['status'] === 'full' || $shelterStatus['status'] === 'closed')) {
                    if (!empty($profile['last_latitude']) && !empty($profile['last_longitude'])) {
                        [$ok, $fallbackShelter] = $accountModel->findNearestOpenShelter(
                            $profile['last_latitude'],
                            $profile['last_longitude'],
                            (int) $profile['preferred_shelter_id']
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
            }
        }

        $isLoggedIn = !empty($_SESSION['isLoggedIn']);
        $username = $_SESSION['username'] ?? "";

        include __DIR__ . '/../views/ProfileView.php';
        break;
    }

    case 'cap-feed': {
        require __DIR__ . '/../feeds/CapFeed.php';
        exit;
    }

    default: {
        header('Location: dashboard');
        exit;
    }
}
