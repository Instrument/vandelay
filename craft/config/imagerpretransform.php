<?php
return [
    'transforms' => [
        // Images source, with handle images
        'images' => [
            [
                'width' => 1400,
            ],
            [
                'width' => 800,
            ],
            [
                'width' => 400
            ],

            'defaults' => [

            ],

            'configOverrides' => [
                'resizeFilter'         => 'catrom',
                'instanceReuseEnabled' => true,
            ]
        ],

        'cards' => [
            [
                'width' => 600
            ]
        ]
    ]
];