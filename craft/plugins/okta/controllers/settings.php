<?php
namespace Craft;
use craft;

//$x = craft()->config->get('environmentVariables')['gcsUrl'];


    // REPLACE THIS
//$spBaseUrl = 'https://snapchat.craft.dev'; //or http://<your_domain>
$spBaseUrl = craft()->config->get('environmentVariables')['oktaSpBaseUrl'];

$settingsInfo = array (
    'sp' => array (
        'entityId' => $spBaseUrl.'/metadata.php',
        'assertionConsumerService' => array (
            'url' => $spBaseUrl.'/acs',
        ),
        'singleLogoutService' => array (
            'url' => $spBaseUrl.'/index.php?sls',
        ),
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
    ),
    'idp' => array (
        // REPLACE ENTITYID AND SINGLESIGNONSERVICE.URL VALUES
//            'entityId' => 'https://dev-737069.oktapreview.com/app/exkc0mv53l5rEjY4M0h7/sso/saml/metadata',
        'entityId' => craft()->config->get('environmentVariables')['oktaEntityId'],
        'singleSignOnService' => array (
            // LOCAL SERVER
//                'url' => 'https://dev-737069.oktapreview.com/app/instrumentdev737069_snapchatsamlapp_1/exkc0mv53l5rEjY4M0h7/sso/saml',
            'url' => craft()->config->get('environmentVariables')['oktaUrl'],
        ),
        'singleLogoutService' => array (
            // LOCAL SERVER
            'url' => 'https://demo-bnb-dev.onelogin.com/trust/saml2/http-redirect/slo/660905',
        ),
        'x509cert' => $file = file_get_contents(craft()->config->get('environmentVariables')['x509path'] .'okta.cert'),
    ),
);
