<?php

namespace Handlers;

class Events
{
    public static function handle($eventModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        [$ok, $events] = $eventModel->getActive();
        \sendJsonResponse($ok ? $events : []);
    }
}
