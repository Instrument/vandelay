<?php
return array(
    '*' => array(
        'omitScriptNameInUrls' => true,
        'limitAutoSlugsToAscii' => true,
        'defaultWeekStartDay' => 0,
        'enableCsrfProtection' => false,
        'cpTrigger' => 'admin',
        'maxUploadFileSize' => 16000000,
        'defaultSearchTermOptions' => array(
            'subLeft' => true,
            'subRight' => true,
        ),
    ),

    'snapchat-craft-dev.wrkbench.in' => array(
        'devMode' => true,
        'siteUrl'  => array(
            'en_us' => 'http://snapchat-craft-dev.wrkbench.in',
            'da_dk' => 'http://snapchat-craft-dev.wrkbench.in/da-dk',
            'nl_nl' => 'http://snapchat-craft-dev.wrkbench.in/nl-nl',
            'fi_fi' => 'http://snapchat-craft-dev.wrkbench.in/fi-fi',
            'fr_fr' => 'http://snapchat-craft-dev.wrkbench.in/fr-fr',
            'de_de' => 'http://snapchat-craft-dev.wrkbench.in/de-de',
            'it_it' => 'http://snapchat-craft-dev.wrkbench.in/it-it',
            'nb_no' => 'http://snapchat-craft-dev.wrkbench.in/nb-no',
            'pt_br' => 'http://snapchat-craft-dev.wrkbench.in/pt-br',
            'es_es' => 'http://snapchat-craft-dev.wrkbench.in/es-es',
            'sv_se' => 'http://snapchat-craft-dev.wrkbench.in/sv-se',
        ),
        'environmentVariables' => array(
            'gcsUrl' => 'http://storage.googleapis.com/snapchat-assets-dev',
        )
    ),
    'snapchat-craft-staging.wrkbench.in' => array(
        'devMode' => false,
        'siteUrl'  => array(
            'en_us' => 'http://snapchat-craft-staging.wrkbench.in',
            'da_dk' => 'http://snapchat-craft-staging.wrkbench.in/da-dk',
            'nl_nl' => 'http://snapchat-craft-staging.wrkbench.in/nl-nl',
            'fi_fi' => 'http://snapchat-craft-staging.wrkbench.in/fi-fi',
            'fr_fr' => 'http://snapchat-craft-staging.wrkbench.in/fr-fr',
            'de_de' => 'http://snapchat-craft-staging.wrkbench.in/de-de',
            'it_it' => 'http://snapchat-craft-staging.wrkbench.in/it-it',
            'nb_no' => 'http://snapchat-craft-staging.wrkbench.in/nb-no',
            'pt_br' => 'http://snapchat-craft-staging.wrkbench.in/pt-br',
            'es_es' => 'http://snapchat-craft-staging.wrkbench.in/es-es',
            'sv_se' => 'http://snapchat-craft-staging.wrkbench.in/sv-se',
        ),
        'environmentVariables' => array(
            'gcsUrl' => 'http://storage.googleapis.com/snapchat-assets-staging',
        )
    )
);