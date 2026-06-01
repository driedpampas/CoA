<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routeLevels = array_values(array_filter(explode('/', ltrim($uri, '/')), 'strlen'));
$routeRoot = $routeLevels[0] ?? '';
$routeLeaf = preg_replace('/\.php$/', '', $routeLevels[1] ?? '');

switch ($routeRoot) {
    case 'dashboard':
    case 'api':
    case 'cap-feed':
        require __DIR__ . '/../controllers/PublicController.php';
        break;

    case 'admin':
    case 'administrator':
        if ($routeLeaf === 'submit_event') {
            require __DIR__ . '/../administrator/submit_event.php';
        } elseif ($routeLeaf === 'submit_shelter') {
            require __DIR__ . '/../administrator/submit_shelter.php';
        } elseif ($routeLeaf === 'manage_event') {
            require __DIR__ . '/../administrator/manage_event.php';
        } elseif ($routeLeaf === 'manage_shelter') {
            require __DIR__ . '/../administrator/manage_shelter.php';
        } else {
            require __DIR__ . '/../administrator/index.php';
        }
        break;

    case 'login':
    case 'register':
    case 'logout':
    case 'verify':
    case 'check-email':
    case 'forgot-password':
    case 'reset-password':
        require __DIR__ . '/../controllers/AccountsController.php';
        break;

    default:
        header('Location: dashboard');
        exit;
}
