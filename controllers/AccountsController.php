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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["action"]) && $_POST["action"] === "logout") {
        setcookie("autologin", "", time() - 3600, "/");
        session_unset();
        session_destroy();
        header("Location: ../controllers/AccountsController.php");
        exit;
    }

    if (!isset($_POST["username"]) || !isset($_POST["password"])) {
        header("Location: ../controllers/AccountsController.php?error=1");
        exit;
    }

    if ($isLoggedIn = $userModel->checkUserExists($_POST["username"], $_POST["password"])) {
        session_regenerate_id(true);
        $_SESSION["isLoggedIn"] = true;
        $_SESSION["username"] = $_POST["username"];
        setcookie("autologin", "1", time() + 3600, "/");
        header("Location: ../controllers/AccountsController.php");
        exit;
    } else {
        header("Location: ../controllers/AccountsController.php?error=1");
        exit;
    }
} else if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (isset($_GET["error"])) {
        $isLoggedIn = false;
        $error = true;
    }

    $isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
    $username = $_SESSION["username"] ?? "";
}

include '../views/AccountsView.php';