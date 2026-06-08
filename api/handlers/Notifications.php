<?php

namespace Handlers;

class Notifications
{
    public static function handle($notificationModel, $action, $sub = '', $userId = null)
    {
        if ($action === 'unread') {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                header('Allow: GET');
                \sendJsonResponse(['error' => 'Method not allowed.'], 405);
            }

            [$ok, $notifications] = $notificationModel->getUnread($userId);
            \sendJsonResponse($ok ? $notifications : []);
        }

        if ($action === 'unread-count') {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                header('Allow: GET');
                \sendJsonResponse(['error' => 'Method not allowed.'], 405);
            }

            [$ok, $count] = $notificationModel->getUnreadCount($userId);
            \sendJsonResponse(['count' => $ok ? $count : 0]);
        }

        if ($action === 'read-all') {
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                header('Allow: PATCH');
                \sendJsonResponse(['error' => 'Method not allowed.'], 405);
            }

            if ($userId === null) {
                \sendJsonResponse(['error' => 'Unauthorized.'], 401);
            }

            [$ok, $updated] = $notificationModel->markAllAsRead($userId);
            \sendJsonResponse(['ok' => $ok, 'updated' => $updated]);
        }

        if ($action !== '' && ctype_digit($action)) {
            $id = (int) $action;

            if ($sub === 'read') {
                if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                    header('Allow: PATCH');
                    \sendJsonResponse(['error' => 'Method not allowed.'], 405);
                }

                if ($userId === null) {
                    \sendJsonResponse(['error' => 'Unauthorized.'], 401);
                }

                [$ok, $found] = $notificationModel->markAsRead($userId, $id);
                \sendJsonResponse(['ok' => $ok, 'updated' => $found]);
            }

            \sendJsonResponse(['error' => 'Not found.'], 404);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        [$ok, $notifications] = $notificationModel->getAll($userId);
        \sendJsonResponse($ok ? $notifications : []);
    }
}
