<?php

namespace Craft;

class SimpleApiController extends BaseController
{
  protected $allowAnonymous = true;

  public function un_cereal_ize() {
    $string = craft()->request->rawBody;
    parse_str($string, $output);
    return $output;
  }
  public function actionGetSingles() {
    $singleSection = craft()->sections->getSectionsByType('single');
    $singles = [];
    foreach ($singleSection as $key => $value) {
      $singles[] = $value['handle'];
    }
    $this->returnJson(array(
      'status' => 200,
      'entry' => $singles
    ));
  }
  public function actionGetGlobals(array $variables = array()) {
    $globals = craft()->globals->getAllSets();
    $page = [
      'locale' => 'en_us',
      'title' => 'globals',
    ];
    foreach ($globals as $value) {
      $fields = $value->getFieldLayout()->getFields();
      foreach($fields as $field){
        $data = $field->getField();
        $handle = $data->handle;
        $pattern = '/\_(loc)/';
        $loc_handle = preg_replace($pattern, "Loc", $handle);
        if ($data->type !== 'Entries' && $data->type !== 'Assets') {
          $page[$loc_handle] = $this->handleFieldType($value, $data);
        }
      }
    }
    $this->returnJson($page);
  }
  public function actionGetCategories() {
    $criteria = craft()->elements->getCriteria(ElementType::Category);
    $result = $criteria->find();
    $cats = [];
    foreach ($result as $key => $value) {
      $cats[] = [
        'id' => $value->id,
        'titleLoc' => $value->title,
        'type' => 'category',
        'locale' => $value->locale
      ];
    }
    $this->returnJson($cats);
  }
  public function actionGetSectionEntries(array $variables = array()) {
    $criteria = craft()->elements->getCriteria(ElementType::Entry);
    $criteria->section = $variables['section'];
    $result = $criteria->find();
    $entries = [];
    foreach ($result as $key => $value) {
      $entries[] = $this->getEntryDetails($value);
    }
    $this->returnJson($entries);
  }
  public function handleFieldType($pagevalue, $data) {
    $handle = $data->handle;
    $pattern = '/\_(loc)/';
    $loc_handle = preg_replace($pattern, "Loc", $handle);
    if($data->type == 'Matrix' || $data->type == 'Neo'){
      $matrixData = [];
      foreach($pagevalue[$handle] as $block => $value){
         $matrixFields = $value->getFieldLayout()->getFields();
         foreach ($matrixFields as $field) {
          $fieldValue = $field->getField();
          $field_loc_handle = preg_replace($pattern, "Loc", $fieldValue->handle);
          if ($fieldValue->type !== 'Entries' && $fieldValue->type !== 'Assets') {
            $data = $this->handleFieldType($value, $fieldValue);
            $matrixData[$block][$field_loc_handle] = $data;
            $matrixData[$block]['blockType'] = $value->type['handle'];
          }
         }
      }
      return $matrixData;
    } elseif ($data->type == 'RichText') {
      if ($pagevalue[$handle] != null){
        return html_entity_decode($pagevalue[$handle]->getRawContent());
      } else {
        return null;
      }
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
    } elseif ($data->type == 'Categories') {
      $cats = [];
      if($pagevalue[$handle][0]){
        foreach($pagevalue[$handle] as $cat) {
          $cats[] = [
            'id' => $cat->id,
            'slug' => $cat->slug,
            'title' => $cat->getTitle(),
          ];
        }
      }
      return $cats;
    } elseif ($data->type == 'PlainText') {
      return $pagevalue[$handle];
    } elseif ($data->type == 'FruitLinkIt') {
      $value = [];
      if(!empty($pagevalue[$handle])) {
        foreach($pagevalue[$handle] as $key => $obj) {
          if ($key === 'customText') {
            $value['customTextLoc'] = $obj;
          } else {
            $value[$key] = $obj;
          }
        }
        return $value;
      }
      return $pagevalue[$handle];
    } else {
      $stuff = [
        'type' => $data->type,
        'value' => $pagevalue[$handle]
      ];
      return $pagevalue[$handle];
    }
  }
  public function getEntryDetails($entry) {
    $page = array();
    $fields = $entry->getFieldLayout()->getFields();
    $page['id'] = $entry->id;
    $page['title'] = $entry->title;
    $page['slug'] = $entry->slug;
    $page['type'] = $entry->type->handle;
    $page['locale'] = $entry->locale;
    foreach($fields as $field){
      $data = $field->getField();
      $handle = $data->handle;
      $pattern = '/\_(loc)/';
      $loc_handle = preg_replace($pattern, "Loc", $handle);
      if ($data->type !== 'Entries' && $data->type !== 'Assets') {
        $page[$loc_handle] = $this->handleFieldType($entry, $data);
      }
      
    }
    return $page;
  }
  public function returnEntry($id, $locale = null) {
    $entry = craft()->entries->getEntryById($id, $locale);
    return $this->getEntryDetails($entry);
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
  public function localizeCategories($data) {
    $cats = [];
    foreach ($data as $item) {
      $cat = $cat = craft()->categories->getCategoryById($item->id, $item->locale);
      $cat->getContent()->setAttribute('title', $item->titleLoc);
      $response = craft()->categories->saveCategory($cat);
      $cats[] = $response;
    }
    return $this->returnJson($data);
  }
  public function localizeGlobals($data) {
    $locale = $data->locale;
    $globals = craft()->globals->getAllSets();
    $matrices = [];
    foreach ($globals as $value) {
      $en = craft()->globals->getSetByHandle($value->handle);
      $localized = craft()->globals->getSetByHandle($value->handle, $locale);
      $values = $en->getContent();
      $values_en = $en->getContent();
      $fields = $value->getFieldLayout()->getFields();
      foreach($fields as $field_key => $field){
        $field_val = $field->getField();
        $type = $field_val->type;
        $handle = $field_val->handle;
        $pattern = '/(_loc)/';
        $matrices = [];
        $translatable = $field_val->translatable == 1;
        $loc_handle = preg_replace($pattern, "Loc", $handle);
        if(isset($data->$loc_handle) && $translatable) {
          if ($field_val->type == 'Neo') {
            $matrices[] = $field_val;
          } elseif ($field_val->type == 'Matrix') {
            // $blockTypes = craft()->matrix->getBlockTypesByFieldId($field_val->id);
            // $type = $blockTypes[0];
            // $block = new MatrixBlockModel();
            // $block->fieldId = $field_val->id;
            // $block->typeId = $blockType[0]->id;
            // $block->ownerId = $localized->id;
            // // $this->saveMatrix($block, $data->$loc_handle);
            // $page[$loc_handle] = $block;
          } else {
            $localized->getContent()->setAttribute($handle, $data->$loc_handle);
          }
        }
        $saved = craft()->globals->saveContent($localized);
      }
    }
    $this->returnJson($page);
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
    $values = [];
    foreach ($data as $key => $value) {
      // $pattern = '/(Loc)/';
      // $loc_handle = preg_replace($pattern, "_loc", $key);
      // if (isset($block->getContent()[$key])) {
      //   $values[$loc_handle] = [
      //     'val' => $value,
      //     'original' => $block->getContent()[$loc_handle]
      //   ];
      //   $block->getContent()->setAttribute($loc_handle, $value);
      // }
    }
    $saved = craft()->matrix->saveBlock($block);
    if ($saved) {
      $this->returnJson($saved);
    } else {
      $this->returnJson($block);
    }
  }
  public function setField($field, $entry) {
    $entry->getContent()->setAttribute($handle, $value);
  }
  public function updateEntry($entry) {

    $post_data = $this->un_cereal_ize();
    $post_fields = $post_data["fields"];
    $entry->getContent()->title = $post_data["title"];
    $fields = $entry->getFieldLayout()->getFields();
    $matrices = array();
    // Populates entry fields from post request
    // Ignores data included in post request that entry does not support
    foreach ($fields as $field) {
      $data = $field->getField();
      $handle = $data->handle;
      if (isset($post_fields[$handle])) {
        $value = $post_fields[$handle];
        if ($data->type == 'Matrix') {
          //Save matrix field data to array to save after entry creation
          $matrices[] = $data;          
        } elseif ($data->type == 'Tags') {
          // Accepts an array of tags by title
          // Looks for tag group ID
          $tagGroup = craft()->tags->getTagGroupByHandle($handle);
          $tag_ids = array();
          foreach ($post_fields[$handle] as $value) {
            $tag = [
              'title' => $value,
              'groupId' => $tagGroup->id
            ];
            $tag_ids[] = $this->saveTag($tag);
          }
          $entry->getContent()->setAttribute($handle, $tag_ids);
        } else {
          $entry->getContent()->setAttribute($handle, $value);  
        }
      }
    }
    $saved = craft()->entries->saveEntry($entry);
    if ($saved) {
      // On success, populate MatrixBlockModel with matrix data and the returned entry ID
      foreach ($matrices as $key => $field) {
        foreach ($post_fields[$field->handle] as $value) {

          $block = new MatrixBlockModel();
          $blockType = craft()->matrix->getBlockTypesByFieldId($field->id);
          $block->fieldId = $field->id;
          $block->typeId = $blockType[0]->id;
          $block->ownerId = $entry->id;

          $this->saveMatrix($block, $value);
        }
      }
      return $entry;
    }
    return $post_fields;
  }
  public function actionGetLocales() {
    $locale_array = craft()->i18n->getSiteLocales();
    $locales = [];
    foreach ($locale_array as $locale) {
      $locales[] = [
        'title' => $locale->getNativeName(),
        'lang' => $locale->getId()
      ];
    }
    $this->returnJson($locales);
  }
  public function updateEntryFromFile($array_data) {
    $entries = [];
    foreach ($array_data as $file) {
      $entry = craft()->entries->getEntryById($file['id'], $file['locale']);
      $entries[] = $file;
      // $entry->getContent()->title = $data["title_loc"];
      // $fields = $entry->getFieldLayout()->getFields();
      // $matrices = array();
      // // Populates entry fields from post request
      // // Ignores data included in post request that entry does not support
      // foreach ($fields as $field) {
      //   $data = $field->getField();
      //   $handle = $data->handle;
      //   if (isset($post_fields[$handle])) {
      //     $value = $post_fields[$handle];
      //     if (isset($value['text'])) {
      //       $value = $value['text'];
      //     }
      //     if ($data->type == 'Matrix') {
      //       //Save matrix field data to array to save after entry creation
      //       $matrices[] = $data;          
      //     } elseif ($data->type == 'Tags') {
      //       // Accepts an array of tags by title
      //       // Looks for tag group ID
      //       $tagGroup = craft()->tags->getTagGroupByHandle($handle);
      //       $tag_ids = array();
      //       foreach ($post_fields[$handle] as $value) {
      //         $tag = [
      //           'title' => $value,
      //           'groupId' => $tagGroup->id
      //         ];
      //         $tag_ids[] = $this->saveTag($tag);
      //       }
      //       $entry->getContent()->setAttribute($handle, $tag_ids);
      //     } else {
      //       $entry->getContent()->setAttribute($handle, $value);  
      //     }
      //   }
      // }
      // $saved = craft()->entries->saveEntry($entry);
      // if ($saved) {
      //   // On success, populate MatrixBlockModel with matrix data and the returned entry ID
      //   foreach ($matrices as $key => $field) {
      //     foreach ($post_fields[$field->handle] as $value) {

      //       $block = new MatrixBlockModel();
      //       $blockType = craft()->matrix->getBlockTypesByFieldId($field->id);
      //       $block->fieldId = $field->id;
      //       $block->typeId = $blockType[0]->id;
      //       $block->ownerId = $entry->id;

      //       $this->saveMatrix($block, $value);
      //     }
      //   }
      //   $entries[] = $saved;
      // }
    }
    return $entries;
  }
  public function addEntry(array $variables = array()) {
    $this->requirePostRequest();
    //Parse serialized data
    $post_data = $this->un_cereal_ize();
    $fields = $post_data["fields"];
    //Populate new Craft EntryModel with post data
    $section = craft()->sections->getSectionById((int)$post_data["sectionId"]);
    $type = craft()->sections->getEntryTypesBySectionId($section->id);
    $entry = new EntryModel();
    $entry->sectionId = (int)$section->id;
    $entry->typeId    = (int)$type[0]->id;
    $entry->authorId  = (int)$post_data["authorId"];
    $entry->enabled   = true;
    $matrices = [];
    if (isset($fields["title"])) {
      $entry->getContent()->setAttribute('title', $fields["title"]);
    }
    $saved = $this->updateEntry($entry);
    if ($saved) {
      $this->returnJson(array(
        'data' => $entry,
        'status' => 200
      ));
    } else {
      $this->returnJson(array(
        'status' => 500,
        'message' => 'There was an error saving the record.',
        'data_received' => $post_data
      ));
    }
  }
  public function actionShowFields() {
    $fields = craft()->fields->getFieldsWithContent();
    $this->returnJson($fields);
  }
  public function isCategory($item) {
    return isset($item->type) && $item->type == 'category';
  }
  public function actionUploadEntry() {
    $raw_data = craft()->request->rawBody;
    $data = json_decode($raw_data);
    if (gettype($data) == 'array') {
      $items = array_values($data);
      if (isset($items[0]) && $this->isCategory($items[0])) {
        $updated = $this->localizeCategories($data);
      } 
    }
    
    if (isset($data->title) && $data->title == 'globals') {
      $updated = $this->localizeGlobals($data);
    } else {
      $updated = $this->updateEntryFromFile($data);
    }
    $this->returnJson(array(
      'status' => 200,
      'data' => $updated,
    ));
  }
  public function handlePostRequest($variables) {
    if (isset($variables['id'])) {
      $entry = craft()->entries->getEntryById($variables['id'], $variables['locale']);
      $this->updateEntry($entry);
    } else {
      $this->addEntry();
    } 
  }
  public function handleGetRequest($variables) {
    if (isset($variables['id'])) {
      $result = $this->returnEntry($variables['id'], $variables['locale']);
      if (craft()->request->getParam('download')) {
        $fname = $result['type'] . '-'. $result['slug'] . '-'. $variables['locale'] .'.json';
        $handle = fopen($fname,'w');
        fwrite($handle, json_encode($result));
        fclose($handle);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($fname));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fname));
        readfile($fname);
        exit;
      }
      $this->returnJson(array(
        'status' => 200,
        'entry' => $result
      ));
    } else {
      $this->returnJson(array(
        'status' => 500,
        'message' => 'ID parameter missing from request.'
      ));
    }
  }
  public function actionHandleEntry(array $variables = array()) {
    if (craft()->request->isPostRequest()) {
      $this->handlePostRequest($variables);
    }
    if (craft()->request->isGetRequest()) {
      $this->handleGetRequest($variables);
    }
  }
}