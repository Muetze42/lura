<?php

return [
    'filesystems' => [
        'default' => 'local',

        'disks' => [
            'app' => [
                'driver' => 'local',
                'root'   => dirname(__DIR__).'/storage',
            ],
        ],
    ],
];
