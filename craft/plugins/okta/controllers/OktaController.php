<?php
/**
 * Okta plugin for Craft CMS
 *
 * Okta Controller
 *
 * @author    Inger Kitkatz
 * @copyright Copyright (c) 2017 Inger Kitkatz
 * @link      instrument.com
 * @package   Okta
 * @since     0.0.1
 */

namespace Craft;
require_once('_toolkit_loader.php');
require_once('lib/Saml2/AuthnRequest.php');

class OktaController extends BaseController
{

  protected $allowAnonymous = true;
  var $auth;
  var $originalUri;

  // The contentpreview route is used to deep link to preview functionality
  public function actionContentpreview( array $variables = array()){
    if(!craft()->userSession->isLoggedIn())
    {
      // clear redirect vars beforehand
      $redirect = null;

      // If we passed a var assign it
      if ($variables['originalUri'] != null) {
        $redirect = $variables['originalUri'];
      }else{

      }

      // re-authenticate the user
      $url = UrlHelper::getUrl() . $redirect;

      // Send to SsoLogin override for redirect
      $this->actionSsoLoginUpdateRedirect($url);
    }
  }

  // Effectively a method overload of Ssologin
  public function actionSsoLoginUpdateRedirect($redirect)
  {
    // Load file to edit
    require_once 'settings.php';

    // If we have a redirect var that's not null
    if($redirect != null)
    {
      // Modify the idp sso url with the relaystate query param for okta to call back with
      $settingsInfo['idp']['singleSignOnService']['url'] = $settingsInfo['idp']['singleSignOnService']['url'] . "?RedirectUrl=$redirect";
    }

    // start new auth flow
    $auth = new OneLogin_Saml2_Auth($settingsInfo);

    // if we actually have a session active, we can assign that ID to refresh it
    if (isset($_SESSION) && isset($_SESSION['AuthNRequestID'])) {
      $requestID = $_SESSION['AuthNRequestID'];
    } else {
        $requestID = null;
    }

    // add the auth session to craft runtime
    craft()->httpSession->add('auth', $auth);

    // build URL for login flow
    $ssoBuiltUrl = craft()->httpSession->get('auth')->login(null, array(), false, false, true);

    // updates session and headers and session headers :D
    craft()->httpSession->add('AuthNRequestID', $auth->getLastRequestID());
    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');
    header('Location: ' . $ssoBuiltUrl);
  }

  // We route here first in normal login flow from the button.
  public function actionSsologin()
  {

    // we have to load the file again if we hit this action from outside this controller
    require_once 'settings.php';

    // start a new auth flow
    $auth = new OneLogin_Saml2_Auth($settingsInfo);

    // add that session
    craft()->httpSession->add('auth', $auth);

    // prepare URL again
    $ssoBuiltUrl = craft()->httpSession->get('auth')->login(null, array(), false, false, true);

    // session headers
    craft()->httpSession->add('AuthNRequestID', $auth->getLastRequestID());
    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');
    header('Location: ' . $ssoBuiltUrl);
  }

  // this is the callback we get from the saml provider, in this case OKTA
  public function actionAcs(){
    // load settings again
    require_once 'settings.php';

    // create var for getting auth request on inital post
    $t = new OneLogin_Saml2_Auth($settingsInfo);

    // checking to see if decrypd worked or there is a request to begin with
    if (isset($t)) {
      if (craft()->httpSession->get('AuthNRequestID') != null) {
        $requestID = craft()->httpSession->get('AuthNRequestID');
      } else {
        $requestID = null;
        Craft::log("NULL REQUEST ID", LogLevel::Error);
      }
    } else {
        // unhandled exceptions
        var_dump(craft()->httpSession->get('auth'));
        $requestID = null;
        Craft::log("NULL REQUEST ID", LogLevel::Error);
    }

    // the decryption phase
    $t->processResponse($requestID);

    // process errors
    $errors = $t->getErrors();

    // print out what we got for errors if not empty
    if (!empty($errors)) {
        print_r('<p>'.implode(', ', $errors).'</p>');
    }

    // if we get a valid callback but not allowed to authenticate here.
    if (!$t->isAuthenticated()) {
        echo "<p>Not authenticated</p>";
        exit();
    }

    // clearning this request ID since we processed it
    craft()->httpSession->remove('AuthNRequestID');

    // start with a blank URL
    $url = null;

    // check it it's a relay state, thats' not the same route we are already at
    if (isset($_POST['RelayState']) &&
          \OneLogin_Saml2_Utils::getSelfURL() != $_POST['RelayState'] &&
          $_POST['RelayState'] != UrlHelper::getUrl() . "ssologin") {

      // Set URL for relay
      $url = $_POST['RelayState'];

      // Clear redirect in settings
      $settingsInfo['idp']['singleSignOnService']['url'] = explode("?", $settingsInfo['idp']['singleSignOnService']['url'])[0];
    }

    // load user's email
    $email = $t->getAttributes()['Email'][0];
    // try to find that user in the db
    $user = craft()->users->getUserByEmail($email);
    // if no db, go ahead and assign some things
    if($user == null) {
      // IF THIS IS A NEW CRAFT USER, CREATE IT
      $firstName = $t->getAttributes()['FirstName'][0];
      $lastName = $t->getAttributes()['LastName'][0];
      $userName = $email;
      $user = new UserModel();
      $user->username   = $userName;
      $user->email      = $email;
      $user->firstName  = $firstName;
      $user->lastName   = $lastName;
      $user->admin      = false;
      $success = craft()->users->saveUser($user);

      // Something prevented saving
      if (!$success)
      {
          Craft::log('Couldn’t save the user "'.$user->username.'"', LogLevel::Error);
      }
    }

      // CREATE ROLES AND LOCALES, OR UPDATE THEM IN CASE THEY CHANGED IN OKTA
      $user = craft()->users->getUserByEmail($email);
      $craftGroups = $t->getAttributes()['CraftGroup'];
      $activeGrouplist = [];

      // iterate over groups to make assignments
      foreach($craftGroups as $cg){

        if ($cg == "Craft Editor") {
          $activeGroup = craft()->userGroups->getGroupByHandle("craftEditor");
          array_push($activeGrouplist, $activeGroup->id);
        }

        if ($cg == "Craft Partner Editor") {
          $activeGroup = craft()->userGroups->getGroupByHandle("partnerEditor");
          array_push($activeGrouplist, $activeGroup->id);
        //craft()->userGroups->assignUserToGroups($user->id, $activeGroup->id);
        }

        if ($cg == "Craft Site Reviewer") {
          $activeGroup = craft()->userGroups->getGroupByHandle("previewer");
          array_push($activeGrouplist, $activeGroup->id);
        }
      }

    // set those assignments
    craft()->userGroups->assignUserToGroups($user->id, $activeGrouplist);

    // special security check for super admin stuff
    if(in_array("Craft Site Admin", $craftGroups)){
      $user->admin = true;
    } else {
      $user->admin = false;
    }
    // save progress
    craft()->users->saveUser($user);

    //null active groups
    $activeGroup = null;

    //update user model
    craft()->users->saveUser($user);

    //reload from saved state
    $user = craft()->users->getUserByEmail($email);

    //login again with updated creds
    craft()->userSession->loginByUserId($user['id']);

    //go to index route
    if($url == null){
      // print_r("NULL URL");
      craft()->request->redirect(UrlHelper::getCpUrl('/index'));
    }else{
      craft()->request->redirect($url);
    }
  }

  public function actionSSOLogout()
  {
    Craft::log("LOGOUT", LogLevel::Error);
    craft()->request->redirect(UrlHelper::getCpUrl('/index'));
  }

  public function actionLoginRequest()
  {
    craft()->request->redirect(UrlHelper::getCpUrl('/index'));
  }
}
