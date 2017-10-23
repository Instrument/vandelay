<?php
namespace Craft;

class Okta_AuthcheckService extends BaseApplicationComponent
{
  public function checkOktaAuth($url) {
    $oktaCheck = new OktaController($url);
    $oktaCheck->actionSsoLoginUpdateRedirect($url);
  }
}
