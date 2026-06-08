<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routeLevels = array_values(array_filter(explode('/', ltrim($uri, '/')), 'strlen'));
$routeRoot = $routeLevels[0] ?? '';
$routeLeaf = preg_replace('/\.php$/', '', $routeLevels[1] ?? '');
$route = $routeRoot;

switch ($routeRoot) {
    case 'dashboard':
    case 'cap-feed':
    case 'profile':
        require __DIR__ . '/controllers/PublicController.php';
        break;

    case 'api':
        require __DIR__ . '/../api/index.php';
        break;

    case 'admin':
        if ($routeLeaf === 'submit_event') {
            require __DIR__ . '/../admin/submit_event.php';
        } elseif ($routeLeaf === 'submit_shelter') {
            require __DIR__ . '/../admin/submit_shelter.php';
        } elseif ($routeLeaf === 'manage_event') {
            require __DIR__ . '/../admin/manage_event.php';
        } elseif ($routeLeaf === 'manage_shelter') {
            require __DIR__ . '/../admin/manage_shelter.php';
        } else {
            require __DIR__ . '/../admin/index.php';
        }
        break;

    case 'login':
    case 'register':
    case 'logout':
    case 'verify':
    case 'check-email':
    case 'forgot-password':
    case 'reset-password':
        require __DIR__ . '/controllers/AccountsController.php';
        break;

    default:
        header('Location: dashboard');
        exit;
}
