<?php
return array(
  '*' => array(
    'omitScriptNameInUrls' => true,
    'limitAutoSlugsToAscii' => true,
		'defaultWeekStartDay' => 0,
		'enableCsrfProtection' => false,
    'cpTrigger' => 'admin',
    'defaultSearchTermOptions' => array(
      'subLeft' => true,
      'subRight' => true,
    ),
  ),
  'snapchat.craft.dev' => array(
    'devMode' => true,
    'siteUrl' => 'http://snapchat.craft.dev',
    'environmentVariables' => array(
      'siteUrl' => 'http://snapchat.craft.dev',
      'gcsUrl' => 'http://storage.googleapis.com/snapcraft-assets',
    )
  ),
  'localhost' => array(
    'devMode' => true,
    'siteUrl' => 'http://localhost:8888',
    'environmentVariables' => array(
      'siteUrl'  => 'http://localhost:8888',
      'gcsUrl' => 'http://storage.googleapis.com/snapcraft-assets',
    )
  ),
  '35.199.148.117' => array(
    'devMode' => true,
    'siteUrl' => 'http://35.199.148.117',
    'environmentVariables' => array(
      'siteUrl'  => 'http://35.199.148.117',
      'gcsUrl' => 'http://storage.googleapis.com/snapcraft-assets',
    )
  )
);