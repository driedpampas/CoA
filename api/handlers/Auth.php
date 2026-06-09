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
            case 'update-location':
                self::updateLocation($accountModel);
                break;
            case 'profile':
                self::profile($accountModel);
                break;
            case 'resolve-username':
                self::resolveUsername($accountModel);
                break;
            case 'is-email-verified':
                self::isEmailVerified($accountModel);
                break;
            case 'role':
                self::role($accountModel);
                break;
            case 'user-id':
                self::userId($accountModel);
                break;
            case 'set-verification-token':
                self::setVerificationToken($accountModel);
                break;
            case 'verify':
                self::verify($accountModel);
                break;
            case 'email':
                self::email($accountModel);
                break;
            case 'verify-reset-token':
                self::verifyResetToken($accountModel);
                break;
            case 'reset-password':
                self::resetPassword($accountModel);
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
            $_SESSION['user_id'] = $accountModel->getUserId($username);
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

        if (strlen($username) < 3 || strlen($username) > 30) {
            \sendJsonResponse(['error' => 'Username must be between 3 and 30 characters.'], 400);
        }

        if (strlen($password) < 3 || strlen($password) > 30) {
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

    private static function updateLocation($accountModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            header('Allow: PATCH');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        if (!($_SESSION['isLoggedIn'] ?? false)) {
            \sendJsonResponse(['error' => 'Unauthorized.'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $latitude = isset($input['latitude']) ? filter_var($input['latitude'], FILTER_VALIDATE_FLOAT) : false;
        $longitude = isset($input['longitude']) ? filter_var($input['longitude'], FILTER_VALIDATE_FLOAT) : false;

        if ($latitude === false || $longitude === false) {
            \sendJsonResponse(['error' => 'Invalid latitude or longitude.'], 400);
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $userId = $accountModel->getUserId($_SESSION['username'] ?? '');
            if ($userId) {
                $_SESSION['user_id'] = $userId;
            }
        }

        if (!$userId) {
            \sendJsonResponse(['error' => 'Unauthorized.'], 401);
        }

        [$ok, $result] = $accountModel->updateLocation($userId, $latitude, $longitude);
        if (!$ok) {
            \sendJsonResponse(['error' => $result], 500);
        }

        \sendJsonResponse(['message' => 'Location updated.']);
    }

    private static function profile($accountModel)
    {
        if (!($_SESSION['isLoggedIn'] ?? false)) {
            \sendJsonResponse(['error' => 'Unauthorized.'], 401);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $userId = $accountModel->getUserId($_SESSION['username'] ?? '');
            if ($userId) {
                $_SESSION['user_id'] = $userId;
            }
        }

        if (!$userId) {
            \sendJsonResponse(['error' => 'Unauthorized.'], 401);
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            [$ok, $data] = $accountModel->getProfile($userId);
            if (!$ok) {
                \sendJsonResponse(['error' => $data], 404);
                return;
            }
            \sendJsonResponse($data);
            return;
        }

        if ($method === 'PATCH') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $bio = isset($input['bio']) ? trim((string) $input['bio']) : '';
            $radius = isset($input['notification_radius_km']) ? (int) $input['notification_radius_km'] : 25;
            $radius = max(1, min(500, $radius));
            $shelterId = isset($input['preferred_shelter_id']) && $input['preferred_shelter_id'] !== '' && $input['preferred_shelter_id'] !== null
                ? (int) $input['preferred_shelter_id']
                : null;

            [$ok, $msg] = $accountModel->updateProfile($userId, $bio, $radius, $shelterId);
            if (!$ok) {
                \sendJsonResponse(['error' => $msg], 500);
                return;
            }

            [$ok, $profile] = $accountModel->getProfile($userId);
            if (!$ok) {
                \sendJsonResponse(['error' => $profile], 500);
                return;
            }

            \sendJsonResponse(['message' => 'Profile updated.', 'profile' => $profile]);
            return;
        }

        \sendJsonResponse(['error' => 'Method not allowed.'], 405);
    }

    private static function resolveUsername($accountModel)
    {
        $login = filter_input(INPUT_GET, 'login', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!$login) {
            \sendJsonResponse(['error' => 'Login parameter is required.'], 400);
        }
        $username = $accountModel->resolveUsername($login);
        \sendJsonResponse(['username' => $username]);
    }

    private static function isEmailVerified($accountModel)
    {
        $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!$username) {
            \sendJsonResponse(['error' => 'Username parameter is required.'], 400);
        }
        $verified = $accountModel->isEmailVerified($username);
        \sendJsonResponse(['verified' => $verified]);
    }

    private static function role($accountModel)
    {
        $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!$username) {
            \sendJsonResponse(['error' => 'Username parameter is required.'], 400);
        }
        $role = $accountModel->getRole($username);
        \sendJsonResponse(['role' => $role]);
    }

    private static function userId($accountModel)
    {
        $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!$username) {
            \sendJsonResponse(['error' => 'Username parameter is required.'], 400);
        }
        $userId = $accountModel->getUserId($username);
        \sendJsonResponse(['user_id' => $userId]);
    }

    private static function setVerificationToken($accountModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $username = trim(filter_var($input['username'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        if (!$username) {
            \sendJsonResponse(['error' => 'Username is required.'], 400);
        }
        [$ok, $tokenOrError] = $accountModel->setVerificationToken($username);
        if (!$ok) {
            \sendJsonResponse(['error' => $tokenOrError], 500);
        }
        \sendJsonResponse(['token' => $tokenOrError]);
    }

    private static function verify($accountModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $token = trim(filter_var($input['token'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        if (!$token) {
            \sendJsonResponse(['error' => 'Token is required.'], 400);
        }
        [$ok, $msg] = $accountModel->verifyEmail($token);
        if ($ok) {
            $username = $msg;
            $email = $accountModel->getEmailByUsername($username);
            if ($email) {
                \Models\Email::sendWelcomeEmail($email, $username);
            }
            \sendJsonResponse(['username' => $username]);
        } else {
            \sendJsonResponse(['error' => $msg], 400);
        }
    }

    private static function email($accountModel)
    {
        $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!$username) {
            \sendJsonResponse(['error' => 'Username parameter is required.'], 400);
        }
        $email = $accountModel->getEmailByUsername($username);
        \sendJsonResponse(['email' => $email]);
    }

    private static function verifyResetToken($accountModel)
    {
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!$token) {
            \sendJsonResponse(['error' => 'Token parameter is required.'], 400);
        }
        [$ok, $msg] = $accountModel->verifyResetToken($token);
        if ($ok) {
            \sendJsonResponse(['username' => $msg]);
        } else {
            \sendJsonResponse(['error' => $msg], 400);
        }
    }

    private static function resetPassword($accountModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $token = trim(filter_var($input['token'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $password = $input['password'] ?? '';
        if (!$token || !$password) {
            \sendJsonResponse(['error' => 'Token and password are required.'], 400);
        }
        [$ok, $msg] = $accountModel->resetPassword($token, $password);
        if ($ok) {
            \sendJsonResponse(['username' => $msg]);
        } else {
            \sendJsonResponse(['error' => $msg], 400);
        }
    }

    private static function shelterStatus($accountModel)
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            \sendJsonResponse(['error' => 'Invalid or missing id.'], 400);
        }
        $status = $accountModel->getShelterStatus($id);
        \sendJsonResponse($status);
    }

    private static function nearestOpenShelter($accountModel)
    {
        $lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
        $lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);
        $excludeId = filter_input(INPUT_GET, 'exclude_id', FILTER_VALIDATE_INT);

        if ($lat === false || $lng === false || $lat === null || $lng === null) {
            \sendJsonResponse(['error' => 'Invalid or missing lat/lng parameters.'], 400);
        }

        [$ok, $res] = $accountModel->findNearestOpenShelter($lat, $lng, $excludeId);
        if (!$ok) {
            \sendJsonResponse(['error' => $res], 500);
        }
        \sendJsonResponse($res);
    }
}
