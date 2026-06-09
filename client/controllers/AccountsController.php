<?php
require_once __DIR__ . '/../../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBaseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';

$userModel = new \ClientModels\Account($apiBaseUrl);

$isLoggedIn = false;
$username = "";
$error = false;
$errorMessage = "";

function login($login, $password)
{
    global $userModel;

    $username = $userModel->resolveUsername($login);

    if (!$username) {
        $_SESSION["errorMessage"] = "Invalid username or password.";
        header("Location: login?error=1");
        exit;
    }

    if ($userModel->checkUserAndPassword($username, $password)) {
        if (!$userModel->isEmailVerified($username)) {
            $_SESSION["errorMessage"] = "Please verify your email address before logging in.";
            header("Location: login?error=1");
            exit;
        }

        session_regenerate_id(true);
        $_SESSION["isLoggedIn"] = true;
        $_SESSION["username"] = $username;
        $_SESSION["role"] = $userModel->getRole($username) ?? 'user';
        $_SESSION["user_id"] = $userModel->getUserId($username);
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

    [$ok, $tokenOrError] = $userModel->setVerificationToken($username);

    if ($ok) {
        \Models\Email::sendVerificationEmail($email, $username, $tokenOrError);
    }

    $_SESSION["successMessage"] = "Registration successful. Please check your email to verify your account.";
    header("Location: check-email");
    exit;
}

function handleForgotPassword()
{
    global $userModel;

    $email = trim(filter_var($_POST["email"] ?? "", FILTER_SANITIZE_EMAIL));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["errorMessage"] = "Please provide a valid email address.";
        header("Location: forgot-password?error=1");
        exit;
    }

    [$ok, $result] = $userModel->setResetToken($email);

    if ($ok) {
        \Models\Email::sendPasswordResetEmail($result['email'], $result['username'], $result['token']);
    }

    $_SESSION["successMessage"] = "If an account with that email exists, a password reset link has been sent.";
    header("Location: forgot-password");
    exit;
}

function handleResetPassword()
{
    global $userModel;

    $token = $_POST["token"] ?? "";
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if (!$token || !$password || !$confirm) {
        $_SESSION["errorMessage"] = "All fields are required.";
        header("Location: reset-password?token=" . urlencode($token) . "&error=1");
        exit;
    }

    if (strlen($password) < 3 || strlen($password) > 30) {
        $_SESSION["errorMessage"] = "Password must be between 3 and 30 characters.";
        header("Location: reset-password?token=" . urlencode($token) . "&error=1");
        exit;
    }

    if ($password !== $confirm) {
        $_SESSION["errorMessage"] = "Passwords do not match.";
        header("Location: reset-password?token=" . urlencode($token) . "&error=1");
        exit;
    }

    [$ok, $msg] = $userModel->resetPassword($token, $password);

    if (!$ok) {
        $_SESSION["errorMessage"] = $msg;
        header("Location: reset-password?token=" . urlencode($token) . "&error=1");
        exit;
    }

    $_SESSION["successMessage"] = "Password reset successful. You can now log in.";
    header("Location: login");
    exit;
}

function showVerify()
{
    global $userModel;

    $token = $_GET["token"] ?? "";
    if (!$token) {
        $verifyError = "Invalid verification link.";
        include __DIR__ . '/../views/VerifyView.php';
        return;
    }

    [$ok, $msg] = $userModel->verifyEmail($token);

    if ($ok) {
        $verifiedUsername = $msg;
        $email = $userModel->getEmailByUsername($msg);
        if ($email) {
            \Models\Email::sendWelcomeEmail($email, $msg);
        }
    } else {
        $verifyError = $msg;
    }

    include __DIR__ . '/../views/VerifyView.php';
}

function showResetPassword()
{
    global $userModel;

    $token = $_GET["token"] ?? "";
    $resetError = null;

    if ($token) {
        [$ok, $msg] = $userModel->verifyResetToken($token);
        if (!$ok) {
            $resetError = $msg;
        }
    }

    include __DIR__ . '/../views/ResetPasswordView.php';
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

            if (strlen($username) < 3 || strlen($username) > 30) {
                $_SESSION["errorMessage"] = "Username must be between 3 and 30 characters.";
                header("Location: register?error=1");
                exit;
            }

            if (strlen($password) < 3 || strlen($password) > 30) {
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

        case "forgot-password":
            handleForgotPassword();
            break;

        case "reset-password":
            handleResetPassword();
            break;

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
    unset($_SESSION["errorMessage"]);
}

$successMessage = $_SESSION["successMessage"] ?? "";
unset($_SESSION["successMessage"]);

switch ($route ?? "") {
    case "login": {
        $isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
        $username = $_SESSION["username"] ?? "";
        include __DIR__ . '/../views/LoginView.php';
        break;
    }

    case "register":
        include __DIR__ . '/../views/RegisterView.php';
        break;

    case "verify":
        showVerify();
        break;

    case "check-email":
        include __DIR__ . '/../views/CheckEmailView.php';
        break;

    case "forgot-password":
        include __DIR__ . '/../views/ForgotPasswordView.php';
        break;

    case "reset-password":
        showResetPassword();
        break;

    case "error":
        include __DIR__ . '/../views/Error.php';
        break;

    default: {
        $isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
        $username = $_SESSION["username"] ?? "";
        include __DIR__ . '/../views/LoginView.php';
        break;
    }
}
