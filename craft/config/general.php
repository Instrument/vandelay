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
      'siteUrl'  => 'http://snapchat.craft.dev',
    )
  ),
  'localhost' => array(
    'devMode' => true,
    'siteUrl' => 'http://localhost:8888',
    'environmentVariables' => array(
      'siteUrl'  => 'http://localhost:8888',
    )
  )
);