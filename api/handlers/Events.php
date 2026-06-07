<?php

namespace Handlers;

class Events
{
    public static function handle($eventModel, $accountModel, $notificationModel, $userId = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $days = filter_input(INPUT_GET, 'days', FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => 30,
            ],
        ]);

        if ($days === false) {
            \sendJsonResponse(['error' => 'Interval invalid.'], 400);
        }

        if ($days !== null) {
            [$ok, $events] = $eventModel->getRecentForMap($days);
            \sendJsonResponse($ok ? $events : []);
        }

        [$ok, $events] = $eventModel->getActive();

        \sendJsonResponse($ok ? $events : []);
    }
}
