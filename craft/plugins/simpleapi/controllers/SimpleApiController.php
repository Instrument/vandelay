<?php

namespace Craft;

require_once(__DIR__ . '/../vendor/autoload.php');

use \Firebase\JWT\JWT;

class SimpleApiController extends BaseController
{
  private $PUBLIC_KEY = null;

  protected $allowAnonymous = true;

  function __construct() {
    $this->PUBLIC_KEY = file_get_contents(__DIR__ . '/../../../config/cert/key.pub');
    if (empty($this->PUBLIC_KEY)) {
      throw new \Exception('Public key missing from config/cert');
    }
  }

  public function getRequestJson() {
    $output = json_decode(craft()->request->rawBody, true);
    if (!$output) {
      throw new Exception('Invalid JSON Request');
    }
    return $output;
  }

  public function handleFieldType($pagevalue, $data) {
    $handle = $data->handle;
    if($data->type == 'Matrix'){
      $matrixData = [];
      foreach($pagevalue[$handle] as $block => $value){
         $matrixFields = $value->getFieldLayout()->getFields();
         foreach ($matrixFields as $field) {
          $fieldValue = $field->getField();
          $matrixData[$block][$fieldValue->handle] =  $this->handleFieldType($value, $fieldValue);
         }
      }
      return $matrixData;
    } elseif ($data->type == 'RichText') {
      if ($pagevalue[$handle] != null){
        return $pagevalue[$handle]->getRawContent();
      } else {
        return null;
      }
    } elseif ($data->type == 'Assets') {
      $assetData = [];
      if($pagevalue[$handle][0]){
        foreach($pagevalue[$handle] as $projImage) {
          return $projImage->getUrl();
        }
      }
      return $assetData;
    } elseif ($data->type == 'Tags') {
      $tags = [];
      if($pagevalue[$handle][0]){
        foreach($pagevalue[$handle] as $tag) {
          $tags[] = [
            'id' => $tag->id,
            'slug' => $tag->slug,
            'title' => $tag->slug
          ];
        }
      }
      return $tags;
    } elseif ($data->type == 'Entries'){
      $entries = [];
      foreach ($pagevalue[$handle] as $entry) {
        $entries[] = $this->getEntryDetails($entry);
      }
      return $entries;
    } else {
      return $pagevalue[$handle];
    }
  }

  public function getEntryDetails($entry) {
    $page = array();
    $fields = $entry->getFieldLayout()->getFields();
    $page['id'] = $entry->id;
    $page['title'] = $entry->title;
    $page['url'] = $entry->url;
    $page['type'] = $entry->type;
    foreach($fields as $field){
      $data = $field->getField();
      $handle = $data->handle;
      $page[$handle] = $this->handleFieldType($entry, $data);
    }
    return $page;
  }

  public function saveTag($data) {
    // First check if the tag already exists
    $criteria = craft()->elements->getCriteria(ElementType::Tag);
    $criteria->title = $data['title'];
    $criteria->group = $data['groupId'];
    $result = $criteria->find();
    if (isset($result[0])) {
      return $result[0]->id;
    } else {
      //Create a new tag if not
      $tag = new TagModel();
      $tag->groupId = $data['groupId'];
      $tag->getContent()->setAttribute('title', $data['title']);
      $success = craft()->tags->saveTag($tag);
      if ($success) {
        return $tag->id;
      }
    }
  }

  public function saveImage($image_url) {
    $imageInfo = pathinfo($image_url);
    // Download image to temp folder
    $tempPath = CRAFT_STORAGE_PATH . 'runtime/temp/' . $imageInfo['basename'];
    file_put_contents($tempPath, fopen($image_url, 'r'));
    // insert the file into assets
    $response = craft()->assets->insertFileByLocalPath(
      $tempPath,
      $imageInfo['basename'],
      1, // The id of the folder you want to upload to
      AssetConflictResolution::KeepBoth
    );
    // if the response is a success, get the file id
    if ($response && $response->isSuccess()) {
      return $response->getDataItem('fileId');
    }
  }

  public function saveMatrix($block, $data) {
    foreach ($data as $key => $value) {
      // Upload an image from a URL if the field handle is image
      if ($key == 'image') {
        $saved_image = $this->saveImage($value);
        $block->getContent()->setAttribute($key, array($saved_image));
      } else {
        $block->getContent()->setAttribute($key, $value);
      }
    }
    $success = craft()->matrix->saveBlock($block);
    return $block;
  }

  public function updateEntry($entry, $user) {
    $post_data = $this->getRequestJson();
    $post_fields = $post_data["fields"];
    $shouldArchive = isset($post_data["archive"]) && boolval($post_data["archive"]);

    if ($shouldArchive) {
      $entry->enabled = false;
    }

    $shouldArchive = isset($post_data["archive"]) && boolval($post_data["archive"]);

    if ($shouldArchive) {
      $entry->enabled = false;
    }

    $fields = $entry->getFieldLayout()->getFields();
    $matrices = array();

    $matrixFields = array();

    foreach ($fields as $field) {
      $data = $field->getField();
      $handle = $data->handle;

      if (!isset($post_fields[$handle])) {
        continue;
      }

      $value = $post_fields[$handle];

      // We need to defer saving matrix fields until after we've saved the entry
      // so that we may link it to the entry->id
      if ($data->type == 'Matrix') {
        $matrixFields[] = $field;
      } else {
        $entry->getContent()->setAttribute($handle, $value);
      }
    }

    $saved = craft()->entries->saveEntry($entry);

    if (!$saved) {
      error_log('Failed save.');
      error_log(print_r($entry->allErrors, true));

      return $this->returnJson(array(
        'status' => 500,
        'message' => 'There was an error saving the record.',
        'errors' => $entry->allErrors
      ));
    }

    foreach ($matrixFields as $field) {
      $data = $field->getField();
      $handle = $data->handle;
      $value = $post_fields[$handle];

      $blocks = [];
      foreach ($value as $idx => $blockData) {

        $blockId = $blockData["id"];
        $foundBlock = null;
        if (substr( $blockId, 0, 4 ) === "_NEW") {
          $newBlock = new MatrixBlockModel();
          $newBlock->fieldId = $data->id;
          $newBlock->ownerId = $entry->id;

          // Currently only supporting single-blockType matrix
          $newBlock->typeId = craft()->matrix->getBlockTypesByFieldId($data->id)[0]->id;

          // We need to update the fields BEFORE we save in case some fields are required.
          foreach ($blockData["_fields"] as $fieldName => $innerData) {
            $newBlock->getContent()->setAttribute($fieldName, $innerData);
          }

          $success = craft()->matrix->saveBlock($newBlock);

          if (!$success) {
            throw new Exception('Something went wrong saving block data');
          }
          $foundBlock = $newBlock;
        } else {
          $foundBlock = craft()->matrix->getBlockById($blockId);

          foreach ($blockData["_fields"] as $fieldName => $innerData) {
            $foundBlock->getContent()->setAttribute($fieldName, $innerData);
          }
        }

        if (!$foundBlock) {
          log("Invalid blockId: " . $blockId);
          continue;
        }

        $blocks[] = $foundBlock;

      }
      $entry->getContent()->setAttribute($handle, $blocks);
    }

    $saved = craft()->entries->saveEntry($entry);

    if (!$saved) {
      return $this->returnJson(array(
        'status' => 500,
        'message' => 'There was an error saving the record.',
        'errors' => $entry->allErrors
      ));
    }

    return $this->returnJson(array(
      'status' => 200,
      'message' => 'Saved!',
      'id' => $entry->id
    ));
  }

  public function addEntry($user) {
    $this->requirePostRequest();
    $post_data = $this->getRequestJson();

    //Populate new Craft EntryModel with post data
    $section = craft()->sections->getSectionByHandle('projects');
    $type = craft()->sections->getEntryTypesBySectionId($section->id);
    $entry = new EntryModel();
    $entry->sectionId = (int)$section->id;
    $entry->typeId    = (int)$type[0]->id;
    $entry->authorId  = $user->id;
    $entry->enabled   = true;

    return $this->updateEntry($entry, $user);
  }

  public function handlePostRequest($variables, $user) {
    $data = null;
    if (isset($variables['id'])) {
      $entry = craft()->entries->getEntryById($variables['id']);
      $data = $this->updateEntry($entry, $user);

    } else {
      $data = $this->addEntry($user);
    }
  }

  public function createOrUpdateUser($userData) {
    $user = craft()->users->getUserByEmail($userData->email);
    if (!empty($user)) {
      return $user;
    }

    // make user here based on onelogin creds
    $firstName = $userData->firstName;
    $lastName = $userData->lastName;
    $user = new UserModel();

    $user->username   = $userData->email;
    $user->email      = $userData->email;
    $user->firstName  = $firstName;
    $user->lastName   = $lastName;
    $user->admin      = false;

    $hasMinimumPermissions = false;
    foreach ($userData->groups as $idx => $value) {
      if ($value === 'Admin') {
        $user->admin = true;
        $hasMinimumPermissions = true;
      }

      if ($value === 'portalEditor' || $value === 'superEditor') {
        $hasMinimumPermissions = true;
      }
    }

    if ($hasMinimumPermissions === false) {
      throw new Exception('Invalid user permissions');
    }

    $result = craft()->users->saveUser($user);

    if (!$result) {
      error_log(print_r($user->allErrors, true));
      throw new \Exception('Something went wrong creating a new user');
    }

    return $user;
  }

  public function actionHandleEntry(array $variables = array()) {
    $jwt = null;
    if (isset($_SERVER['HTTP_JWT'])) {
      $jwt = $_SERVER['HTTP_JWT'];
    }

    $decoded = null;
    try {
      $decoded = JWT::decode($jwt, $this->PUBLIC_KEY, array('RS256'));
    } catch (\Exception $e) {
      return $this->returnUnauthorized();
    }

    if (empty($decoded)) { return $this->returnUnauthorized(); }

    if (!craft()->request->isPostRequest()) { return $this->returnUnknownRequest(); }

    try {
      $user = $this->createOrUpdateUser($decoded);
    } catch (\Exception $e) {
      return $this->returnUnauthorized();
    }

    try {
      return $this->handlePostRequest($variables, $user);
    } catch (\Exception $e) {
      return $this->returnUnknownError($e);
    }

  }

  public function returnUnauthorized() {
    return $this->returnJson(array(
      'status' => 500,
      'message' => 'Unauthorized Request'
    ));
  }

  public function returnUnknownRequest() {
    return $this->returnJson(array(
      'status' => 500,
      'message' => 'Unknown Request Type'
    ));
  }

  public function returnUnknownError($details) {
    return $this->returnJson(array(
      'status' => 500,
      'message' => 'Unknown Error',
      'details' => $details,
    ));
  }

}
