<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userModel = new \Models\Account($mysql);

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
        header("Location: dashboard");
        exit;
    } else {
        $_SESSION["errorMessage"] = "Invalid username or password.";
        header("Location: login?error=1");
        exit;
    }
}

function register($username, $password, $email)
{
    global $userModel;

    [$success, $errorMessage] = $userModel->checkUserExists($username);

    if ($success) {
        $_SESSION["errorMessage"] = $errorMessage;
        header("Location: register?error=1");
        exit;
    }

    [$success, $errorMessage] = $userModel->createUser($username, $password, $email);

    if (!$success) {
        $_SESSION["errorMessage"] = $errorMessage;
        header("Location: register?error=1");
        exit;
    }

    // Auto-login after registration
    session_regenerate_id(true);
    $_SESSION["isLoggedIn"] = true;
    $_SESSION["username"] = $username;
    setcookie("autologin", "1", time() + 3600, "/");

    header("Location: dashboard");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $error = false;
    switch ($_POST["action"] ?? "") {
        case "login": {
            if (!isset($_POST["username"]) || !isset($_POST["password"])) {
                header("Location: login?error=1");
                exit;
            }
            $username = filter_var(trim($_POST["username"]), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST["password"];
            login($username, $password);
            break;
        }

        case "register": {
            if (!isset($_POST["username"]) || !isset($_POST["password"]) || !isset($_POST["email"])) {
                $_SESSION["errorMessage"] = "All fields are required.";
                header("Location: register?error=1");
                exit;
            }

            $username = trim(filter_var($_POST["username"], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $password = $_POST["password"];
            $email = trim(filter_var($_POST["email"], FILTER_SANITIZE_EMAIL));

            if (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
                $_SESSION["errorMessage"] = "Username must be between 3 and 30 characters.";
                header("Location: register?error=1");
                exit;
            }

            if (mb_strlen($password) < 3 || mb_strlen($password) > 30) {
                $_SESSION["errorMessage"] = "Password must be between 3 and 30 characters.";
                header("Location: register?error=1");
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION["errorMessage"] = "Please provide a valid email address.";
                header("Location: register?error=1");
                exit;
            }

            register($username, $password, $email);
            break;
        }

        case "logout": {
            setcookie("autologin", "", time() - 3600, "/");
            session_unset();
            session_destroy();
            header("Location: login");
            exit;
        }

        default: {
            header("Location: login?error=1");
            exit;
        }
    }
}

if (isset($_GET["error"])) {
    $error = true;
    $errorMessage = $_SESSION["errorMessage"] ?? "";
}

switch ($route ?? "") {
    case "login": {
        $isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
        $username = $_SESSION["username"] ?? "";
        include __DIR__ . '/../views/LoginView.php';
        break;
    }

    case "register": {
        include __DIR__ . '/../views/RegisterView.php';
        break;
    }

    case "error": {
        include __DIR__ . '/../views/Error.php';
        break;
    }

    default: {
        $isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
        $username = $_SESSION["username"] ?? "";
        include __DIR__ . '/../views/LoginView.php';
        break;
    }
}
