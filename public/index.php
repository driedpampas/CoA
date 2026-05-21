<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routeLevels = array_values(array_filter(explode('/', ltrim($uri, '/')), 'strlen'));
$route = implode('/', $routeLevels);

switch ($routeLevels[0] ?? '') {
    case 'dashboard':
    case 'api':
    case 'cap-feed':
        require __DIR__ . '/../controllers/PublicController.php';
        break;

    case 'login':
    case 'register':
    case 'logout':
        require __DIR__ . '/../controllers/AccountsController.php';
        break;

    default:
        header('Location: dashboard');
        exit;
}
