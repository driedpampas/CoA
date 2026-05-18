<?php
$page = $_GET['page'] ?? '';

switch ($page) {
    case 'dashboard':
    case 'api-events':
    case 'api-shelters':
    case 'api-nearest':
    case 'cap-feed':
        require __DIR__ . '/../controllers/PublicController.php';
        break;

    case 'login':
    case 'register':
    case 'logout':
    case 'checkusername':
        require __DIR__ . '/../controllers/AccountsController.php';
        break;

    default:
        header('Location: index.php?page=dashboard');
        exit;
}
