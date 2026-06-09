<?php

return [
    'api_key' => getenv('RESEND_API_KEY') ?: '',
    'from' => 'COA <noreply@syu.nl.eu.org>',
    'app_url' => 'http://coa.local',
];
