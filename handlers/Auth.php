<?php

namespace Handlers;

class Auth
{
    public static function handle($accountModel, $subAction)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            \sendJsonResponse(null, 204);
        }

        switch ($subAction) {
            case 'login':
                self::login($accountModel);
                break;
            case 'register':
                self::register($accountModel);
                break;
            case 'check-username':
                self::checkUsername($accountModel);
                break;
            case 'forgot-password':
                self::forgotPassword($accountModel);
                break;
            default:
                \sendJsonResponse(['error' => 'Not found.'], 404);
                break;
        }
    }

    private static function login($accountModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['username']) || empty($input['password'])) {
            \sendJsonResponse(['error' => 'Username and password are required.'], 400);
        }

        $login = filter_var(trim($input['username']), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = $input['password'];

        $username = $accountModel->resolveUsername($login);

        if (!$username) {
            \sendJsonResponse(['error' => 'Invalid username or password.'], 401);
        }

        if ($accountModel->checkUserAndPassword($username, $password)) {
            if (!$accountModel->isEmailVerified($username)) {
                \sendJsonResponse(['error' => 'Please verify your email address before logging in.'], 403);
            }

            session_regenerate_id(true);
            $_SESSION['isLoggedIn'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $accountModel->getRole($username) ?? 'user';
            setcookie('autologin', '1', time() + 3600, '/');
            \sendJsonResponse(['message' => 'Login successful.', 'username' => $username]);
        } else {
            \sendJsonResponse(['error' => 'Invalid username or password.'], 401);
        }
    }

    private static function register($accountModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['username']) || empty($input['password']) || empty($input['email'])) {
            \sendJsonResponse(['error' => 'Username, password, and email are required.'], 400);
        }

        $username = trim(filter_var($input['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $password = $input['password'];
        $email = trim(filter_var($input['email'], FILTER_SANITIZE_EMAIL));

        if (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
            \sendJsonResponse(['error' => 'Username must be between 3 and 30 characters.'], 400);
        }

        if (mb_strlen($password) < 3 || mb_strlen($password) > 30) {
            \sendJsonResponse(['error' => 'Password must be between 3 and 30 characters.'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            \sendJsonResponse(['error' => 'Please provide a valid email address.'], 400);
        }

        [$exists, $msg] = $accountModel->checkUserExists($username);

        if ($exists) {
            \sendJsonResponse(['error' => 'Username already exists.'], 409);
        }

        [$ok, $msg] = $accountModel->createUser($username, $password, $email);

        if (!$ok) {
            \sendJsonResponse(['error' => $msg], 500);
        }

        [$ok, $tokenOrError] = $accountModel->setVerificationToken($username);

        if ($ok) {
            \Models\Email::sendVerificationEmail($email, $username, $tokenOrError);
        }

        \sendJsonResponse(['message' => 'Registration successful. Please check your email to verify your account.'], 201);
    }

    private static function checkUsername($accountModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$username) {
            \sendJsonResponse(['error' => 'Username parameter is required.'], 400);
        }

        [$exists, $msg] = $accountModel->checkUserExists($username);

        if ($msg) {
            \sendJsonResponse(['error' => $msg], 500);
        }

        \sendJsonResponse(['exists' => (bool) $exists]);
    }

    private static function forgotPassword($accountModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['email'])) {
            \sendJsonResponse(['error' => 'Email is required.'], 400);
        }

        $email = trim(filter_var($input['email'], FILTER_SANITIZE_EMAIL));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            \sendJsonResponse(['error' => 'Please provide a valid email address.'], 400);
        }

        [$ok, $result] = $accountModel->setResetToken($email);

        if (!$ok) {
            \sendJsonResponse(['error' => $result], 400);
        }

        \Models\Email::sendPasswordResetEmail($result['email'], $result['username'], $result['token']);

        \sendJsonResponse(['message' => 'If an account with that email exists, a password reset link has been sent.']);
    }
}
