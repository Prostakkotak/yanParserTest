<?php

return [
    'yandex_parser' => [
        'url' => env('YANDEX_PARSER_URL', 'http://parser:8080'),
        'timeout' => env('YANDEX_PARSER_TIMEOUT', 300),
    ],
];
