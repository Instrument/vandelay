<?php
return [
  'transforms' => [
    'images' => [
      [
        'width' => function ($asset) {
          if ($asset->width > 2000) {
            return 2000;
          } else {
            return $asset->width;
          }
        },
      ],
      [
        'width' => function ($asset) {
          if ($asset->width > 2000) {
            return 500;
          } else {
            return $asset->width / 2;
          }
        },
      ],
    ],

    'global' => [
      [
        'noop' => true
      ]
    ],

    'video' => [
      [
        'noop' => true
      ]
    ]
  ]
];