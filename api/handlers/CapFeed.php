<?php

namespace Handlers;

class CapFeed
{
    private static array $severityMap = [
        'low' => 'Minor',
        'moderate' => 'Moderate',
        'high' => 'Severe',
        'extreme' => 'Extreme',
    ];

    private static array $urgencyMap = [
        'low' => 'Expected',
        'moderate' => 'Expected',
        'high' => 'Immediate',
        'extreme' => 'Immediate',
    ];

    public static function handle($eventModel)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            \sendJsonResponse(['error' => 'Method not allowed.'], 405);
        }

        [$ok, $events] = $eventModel->getActive();
        $events = $ok ? $events : [];

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $alerts = $doc->createElement('alerts');
        $alerts->setAttribute('xmlns', 'urn:oasis:names:tc:emergency:cap:1.2');
        $doc->appendChild($alerts);

        foreach ($events as $event) {
            $identifier = 'emergency-' . $event['id'] . '@' . date('Y-m-d\TH:i:s', strtotime($event['started_at']));
            $sent = gmdate('c');
            $effective = gmdate('c', strtotime($event['started_at']));
            $expires = gmdate('c', strtotime($event['started_at'] . ' +24 hours'));

            $alert = $doc->createElement('alert');

            $alert->appendChild($doc->createElement('identifier', htmlspecialchars($identifier)));
            $alert->appendChild($doc->createElement('sender', 'coa-emergency@localhost'));
            $alert->appendChild($doc->createElement('sent', $sent));
            $alert->appendChild($doc->createElement('status', 'Actual'));
            $alert->appendChild($doc->createElement('msgType', 'Alert'));
            $alert->appendChild($doc->createElement('scope', 'Public'));

            $info = $doc->createElement('info');
            $info->appendChild($doc->createElement('language', 'en-US'));
            $info->appendChild($doc->createElement('category', 'Geo'));
            $info->appendChild($doc->createElement('event', htmlspecialchars($event['event_type'])));
            $info->appendChild($doc->createElement('urgency', self::$urgencyMap[$event['severity']] ?? 'Unknown'));
            $info->appendChild($doc->createElement('severity', self::$severityMap[$event['severity']] ?? 'Unknown'));
            $info->appendChild($doc->createElement('certainty', 'Observed'));
            $info->appendChild($doc->createElement('effective', $effective));
            $info->appendChild($doc->createElement('expires', $expires));

            $headline = $event['title'] . ' - ' . ucfirst($event['severity']) . ' severity';
            $info->appendChild($doc->createElement('headline', htmlspecialchars($headline)));
            $info->appendChild($doc->createElement('description', htmlspecialchars($event['description'] ?? '')));

            if ($event['latitude'] && $event['longitude']) {
                $area = $doc->createElement('area');
                $areaDesc = $doc->createElement('areaDesc', htmlspecialchars($event['title']));
                $area->appendChild($areaDesc);
                $circle = $doc->createElement('circle', $event['latitude'] . ',' . $event['longitude'] . ' 0');
                $area->appendChild($circle);
                $info->appendChild($area);
            }

            $alert->appendChild($info);
            $alerts->appendChild($alert);
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo $doc->saveXML();
    }
}
