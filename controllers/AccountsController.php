<?php
require_once '../config/db.php';
require_once '../models/AccountsModel.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userModel = new Account($mysql);

$isLoggedIn = false;
$username = "";
$error = false;
$errorMessage = "";

function login($username, $password)
{
    global $userModel;

    if ($userModel->checkUserAndPassword($username, $password)) {
        session_regenerate_id(true);
        $_SESSION["isLoggedIn"] = true;
        $_SESSION["username"] = $username;
        setcookie("autologin", "1", time() + 3600, "/");
        header("Location: ../controllers/AccountsController.php");
        exit;
    } else {
        header("Location: ../controllers/AccountsController.php?error=1");
        exit;
    }
}

function register($username, $password)
{
    global $userModel;

    [$success, $errorMessage] = $userModel->checkUserExists($username);

    if ($success) {
        $_SESSION["errorMessage"] = $errorMessage;
        header("Location: ../controllers/AccountsController.php?page=register&error=1");
        exit;
    }

    [$success, $errorMessage] = $userModel->createUser($username, $password);

    if (!$success) {
        $_SESSION["errorMessage"] = $errorMessage;
        header("Location: ../controllers/AccountsController.php?page=register&error=1");
        exit;
    }

    header("Location: ../controllers/AccountsController.php?page=login");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $error = false;
    switch ($_POST["action"] ?? "") {
        case "login": {
            if (!isset($_POST["username"]) || !isset($_POST["password"])) {
                header("Location: ../controllers/AccountsController.php?error=1");
                exit;
            }
            login($_POST["username"], $_POST["password"]);
            break;
        }

        case "register": {
            if (!isset($username) || !isset($password)) {
                header("Location: ../controllers/AccountsController.php?page=register&error=1");
                exit;
            }
            register($_POST["username"], $_POST["password"]);
            break;
        }

        case "checkusername": {
            header('Content-Type: application/json; charset=utf-8');
            if (!isset($_POST["username"])) {
                echo json_encode(["error" => true, "message" => "Username is required."]);
                exit;
            }

            [$success, $errorMessage] = $userModel->checkUserExists($_POST["username"]);

            if ($success) {
                echo json_encode(["exists" => true]);
            } else if ($errorMessage) {
                echo json_encode(["error" => true, "message" => $errorMessage]);
            } else {
                echo json_encode(["exists" => false]);
            }
            exit;
        }

        case "logout": {
            setcookie("autologin", "", time() - 3600, "/");
            session_unset();
            session_destroy();
            header("Location: ../controllers/AccountsController.php");
            exit;
        }

        default: {
            header("Location: ../controllers/AccountsController.php?error=1");
            exit;
        }
    }
}

if (isset($_GET["error"])) {
    $error = true;
    $errorMessage = $_SESSION["errorMessage"] ?? "";
}

switch ($_GET["page"] ?? "") {
    case "login": {
        $isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
        $username = $_SESSION["username"] ?? "";
        include '../views/LoginView.php';
        break;
    }

    case "register": {
        include '../views/RegisterView.php';
        break;
    }

    case "error": {
        include '../views/Error.php';
        break;
    }

    default: {
        $isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
        $username = $_SESSION["username"] ?? "";
        include '../views/LoginView.php';
        break;
    }
}