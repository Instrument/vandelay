<?php
return array(
  '*' => array(
    'omitScriptNameInUrls' => true,
    'limitAutoSlugsToAscii' => true,
    'defaultWeekStartDay' => 0,
    'defaultImageQuality' => 90,
		'enableCsrfProtection' => false,
    'cpTrigger' => 'admin',
    'maxUploadFileSize' => 16000000,
    'phpMaxMemoryLimit' => '1024M',
    'defaultSearchTermOptions' => array(
      'subLeft' => true,
      'subRight' => true,
    ),
  ),
  'YOUR.DOMAIN.NAME' => array(
      'devMode' => false,
      'siteUrl'  => array(
          'en_us' => 'https://YOUR.DOMAIN.NAME',
          'da_dk' => 'https://YOUR.DOMAIN.NAME/da-dk',
          'nl_nl' => 'https://YOUR.DOMAIN.NAME/nl-nl',
          'fi_fi' => 'https://YOUR.DOMAIN.NAME/fi-fi',
          'fr_fr' => 'https://YOUR.DOMAIN.NAME/fr-fr',
          'de_de' => 'https://YOUR.DOMAIN.NAME/de-de',
          'it_it' => 'https://YOUR.DOMAIN.NAME/it-it',
          'nb_no' => 'https://YOUR.DOMAIN.NAME/nb-no',
          'pt_br' => 'https://YOUR.DOMAIN.NAME/pt-br',
          'es_es' => 'https://YOUR.DOMAIN.NAME/es-es',
          'sv_se' => 'https://YOUR.DOMAIN.NAME/sv-se',
      ),
      // update the oktaEntityId and oktaUrl paths with your Okta domain, username and application IDs.
      // these strings left in for syntax reference
      'environmentVariables' => array(
          'oktaSpBaseUrl' => 'https://YOUR.DOMAIN.NAME',
          'oktaEntityId' => 'https://dev-737069.oktapreview.com/app/exkc9a5ndj7c1z7zF0h7/sso/saml/metadata',
          'oktaUrl' => 'https://dev-737069.oktapreview.com/app/instrumentdev737069_snapchatsamltestingapp_1/exkc9a5ndj7c1z7zF0h7/sso/saml',
          'x509path' => '/usr/share/ca-certificates/',
      )
  ),
);