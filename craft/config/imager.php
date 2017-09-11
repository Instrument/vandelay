<?php
return array(
  'imagerSystemPath' => $_SERVER['DOCUMENT_ROOT'] . '/imager/',
  'imagerUrl' => '/imager/',
  'cacheEnabled' => true,
  'cacheDuration' => 31536000, // 1 year
  'cacheDurationRemoteFiles' => 1209600, // 14 days
  'jpegQuality' => 80,
  'pngCompressionLevel' => 2,
  'webpQuality' => 80,
  'webpImagickOptions' => array(), // additional options you want to pass to Imagick via '$instance->setOption('webp:option', 'value')'.
  'interlace' => true, // false, true ('line'), 'none', 'line', 'plane', 'partition'
  'allowUpscale' => false,
  'smartResizeEnabled' => true,
  'removeMetadata' => false,
  'position' => '50% 50%',
  
  'jpegoptimEnabled' => true,
  'optipngEnabled' => true,
  'optipngPath' => '/usr/bin/optipng',
  'optipngOptionString' => '-o5',

  'gcsEnabled' => false,
  'gcsAccessKey' => '',
  'gcsSecretAccessKey' => '',
  'gcsBucket' => '',
  'gcsFolder' => '',
  'gcsCacheDuration' => 1209600, // 14 days for optimized files or when optimization is disabled
  'gcsCacheDurationNonOptimized' => 300, // 5 minutes for the non-optimized file when any optimization is enabled
);