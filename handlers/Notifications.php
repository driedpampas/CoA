<?php

namespace Handlers;

class Notifications
{
    public static function handle($notificationModel, $action, $sub = '')
    {
        if ($action === 'unread') {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                header('Allow: GET');
                \sendJsonResponse(['error' => 'Method not allowed.'], 405);
            }

            [$ok, $notifications] = $notificationModel->getUnread();
            \sendJsonResponse($ok ? $notifications : []);
        }

        if ($action === 'unread-count') {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                header('Allow: GET');
                \sendJsonResponse(['error' => 'Method not allowed.'], 405);
            }

            [$ok, $count] = $notificationModel->getUnreadCount();
            \sendJsonResponse(['count' => $ok ? $count : 0]);
        }

        if ($action === 'read-all') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Allow: POST');
                \sendJsonResponse(['error' => 'Method not allowed.'], 405);
            }

            [$ok, $updated] = $notificationModel->markAllAsRead();
            \sendJsonResponse(['ok' => $ok, 'updated' => $updated]);
        }

        if ($action !== '' && ctype_digit($action)) {
            $id = (int) $action;

            if ($sub === 'read') {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    header('Allow: POST');
                    \sendJsonResponse(['error' => 'Method not allowed.'], 405);
                }

                [$ok, $found] = $notificationModel->markAsRead($id);
                \sendJsonResponse(['ok' => $ok, 'updated' => $found]);
            }

            \sendJsonResponse(['error' => 'Not found.'], 404);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        [$ok, $notifications] = $notificationModel->getAll();
        \sendJsonResponse($ok ? $notifications : []);
    }
}
