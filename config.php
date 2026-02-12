<?php

declare(strict_types=1);

return [
    'db_path' => __DIR__ . '/storage/holo_schedule.sqlite',

    // YouTube Data API v3 key, gebruikt voor het automatisch importeren
    // van geplande livestreams in de app via de Google API.
    // Vul hier je API key in, of zet hem als environment variable YOUTUBE_API_KEY.
    'youtube_api_key' => getenv('YOUTUBE_API_KEY') ?: 'YOUR_YOUTUBE_API_KEY_HERE',
];

