<?php
return array(
  'imagerSystemPath' => $_SERVER['DOCUMENT_ROOT'] . '/imager/',
  'imagerUrl' => '/imager/',
  'cacheEnabled' => true,
  'cacheDuration' => 31536000, // 1 year
  'cacheDurationRemoteFiles' => 1209600, // 14 days
  'jpegQuality' => 85,
  'pngCompressionLevel' => 2,
  'interlace' => true, // false, true ('line'), 'none', 'line', 'plane', 'partition'
  'allowUpscale' => false,
  'smartResizeEnabled' => true,
  'removeMetadata' => false,
  'position' => '50% 50%',
  
  'jpegoptimEnabled' => true,
  'optipngEnabled' => true,

  'gcsEnabled' => true,
  'gcsAccessKey' => 'GOOGKW5BK37TY2HI3RAV',
  'gcsSecretAccessKey' => 'DQhdBp2r+WJyg96lV3bG2bE1J9oZ/GA1r+Ru4zOd',
  'gcsBucket' => 'snapcraft-assets',
  'gcsFolder' => 'imager',
  'gcsCacheDuration' => 1209600, // 14 days for optimized files or when optimization is disabled
  'gcsCacheDurationNonOptimized' => 300, // 5 minutes for the non-optimized file when any optimization is enabled
);