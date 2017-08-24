<?php
return array(
  '*' => array(
    'omitScriptNameInUrls' => true,
    'limitAutoSlugsToAscii' => true,
		'defaultWeekStartDay' => 0,
		'enableCsrfProtection' => true,
		'cpTrigger' => 'admin',
  ),
  'snapchat.craft.dev' => array(
    'devMode' => true,
    'siteUrl' => 'http://snapchat.craft.dev'
  ),
  'localhost' => array(
    'devMode' => true,
    'siteUrl' => 'http://localhost:8888'
  )
);