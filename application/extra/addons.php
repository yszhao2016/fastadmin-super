<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'cos',
            'fastflow',
            'flow',
        ],
        'upload_config_init' => [
            'cos',
        ],
        'upload_delete' => [
            'cos',
        ],
    ],
    'route' => [],
    'priority' => [],
    'domain' => '',
];
