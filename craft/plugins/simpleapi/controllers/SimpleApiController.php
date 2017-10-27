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
    $locale = isset($variables['locale']) ? $variables['locale'] : craft()->i18n->primarySiteLocaleId;
    $globals = craft()->globals->getAllSets();
    $page = [
      'locale' => $locale,
      'title' => 'globals',
    ];
    foreach ($globals as $value) {
      $set  = craft()->globals->getSetByHandle($value->handle, $locale);
      $fields = $set->getFieldLayout()->getFields();
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
    return $data;
  }
  public function localizeGlobals($data) {
    $locale = $data->locale;
    $globals = craft()->globals->getAllSets();
    $matrices = [];
    $raw = [];
    $neos = [];
    foreach ($globals as $value) {
      $en = craft()->globals->getSetByHandle($value->handle);
      $localized = craft()->globals->getSetByHandle($value->handle, $locale);
      $fields = $value->getFieldLayout()->getFields();
      foreach($fields as $field_key => $field){
        $field_val = $field->getField();
        $type = $field_val->type;
        $handle = $field_val->handle;
        $pattern = '/(_loc)/';
        $loc_handle = preg_replace($pattern, "Loc", $handle);
        if(isset($data->$loc_handle)) {
          if ($type === 'Neo') {
            $neoblocks = $this->saveNeo($field_val, $locale, $localized, $data->$loc_handle);
            $neos[$handle] = $neoblocks;
          } elseif ($type === 'PlainText') {
            $localized->getContent()->setAttribute($handle, $data->$loc_handle);
          } elseif ($type === 'Matrix') {
            $fresh_matrix = $this->cleanMatrixBlocks($localized, $field_val, $locale, $data->$loc_handle);
            $raw[] = $fresh_matrix;
            $matrices = array_merge($fresh_matrix, $matrices);
          } elseif ($type === 'FruitLinkIt') {
            $og_link = $localized->getContent()[$handle];
            if (!empty($en->$handle)) {
              $og_link = $en->$handle;
            }
            $link = $this->saveFruitLink($og_link, $data->$loc_handle);
            $localized->getContent()->setAttribute($handle, $link);
          } else {
            $localized->getContent()->setAttribute($handle, $data->$loc_handle);
          }
        } elseif ($type === 'Assets') {
          $image = $en->$handle->first();
          if (!isset($localized->$handle[0])) {
            $localized->getContent()->setAttribute($handle, [$image->id]);
          }
        }
        $saved = craft()->globals->saveContent($localized);
        $result = [];
        if ($saved) {
          foreach ($matrices as $block_value) {
            $result[] = $this->saveMatrix($block_value['block'], $block_value['data'], $block_value['original']);
          }
        }
      }
    }
    return [$raw, $matrices, $neos];
  }
  public function localizeEntry($data) {
    $locale = $data->locale;
    $matrices = [];
    $neos = [];
    $structures = [];
    $entry = craft()->entries->getEntryById($data->id, $locale);
    $english_entry = craft()->entries->getEntryById($data->id);
    //Exit if entry is not available in target locale
    if (empty($entry)) { return $data; }
    $fields = $entry->getFieldLayout()->getFields();
    foreach ($fields as $field) {
      $field_val = $field->getField();
      $type = $field_val->type;
      $handle = $field_val->handle;
      $pattern = '/(_loc)/'; 
      $loc_handle = preg_replace($pattern, "Loc", $handle);
      if (isset($data->$loc_handle)) {
        if ($type === 'Neo') {
          $neoblocks = $this->saveNeo($field_val, $locale, $entry, $data->$loc_handle);
          $structures[$handle] = $neoblocks;
        } elseif ($type === 'PlainText') {
            $entry->getContent()->setAttribute($handle, $data->$loc_handle);
        } elseif ($type === 'Categories') {
          $cats = [];
          foreach ($data->$loc_handle as $category) {
            $cats[] = $category->id;
          }
          $entry->getContent()->setAttribute($handle, $cats);
          $matrices[$handle] = $data->$loc_handle;
        } elseif ($type === 'Matrix') {
          if (sizeof($data->$loc_handle) > 0) {
            $criteria = craft()->elements->getCriteria(ElementType::MatrixBlock);
            $criteria->ownerId = $entry->id;
            $criteria->fieldId = $field_val->id;
            if ($field_val->translatable == 1) {
              $criteria->locale = $locale;
            } else {
              $criteria->locale = craft()->language;
            }
            $results = $criteria->find();
            $blockIds = [];
            foreach ($results as $key => $result) {
              $result = craft()->matrix->getBlockById($result->id, $locale);
              $matrices[] = $this->saveMatrix($result, $data->$loc_handle[$key]);
              $blockIds[] = [$result, $data->$loc_handle];
            }
          }
        } elseif ($type === 'FruitLinkIt') {
          if (gettype($data->$loc_handle) === 'Array') {
            $og_link = $entry->$handle;
            if (!empty($english_entry->$handle)) {
              $og_link = $english_entry->$handle;
            }
            $link = $this->saveFruitLink($og_link, $data->$loc_handle);
            $entry->getContent()->setAttribute($handle, $link);
          }
        }
      } elseif ($type === 'Assets') {
        $image = $english_entry->$handle->first();
        if (!isset($entry->$handle[0])) {
          $entry->getContent()->setAttribute($handle, [$image->id]);
        }
      } elseif ($type === 'RichText') {
        $entry->getContent()->setAttribute($handle, $data->$loc_handle);
      }
    }
    $saved = craft()->entries->saveEntry($entry);
    if ($saved) {
      return [$entry, $matrices, $structures];
    } else {
      return $data;
    }
  }
  public function saveFruitLink($link, $data) {
    if (gettype($link) === 'NULL' || gettype($data) !== 'object') { return $link; }
    $link['customText'] = isset($data->customTextLoc) ? $data->customTextLoc : false;
    if (gettype($data->value) === "array") {
      $data->value = $data->value[0];
    }
    foreach ($link as $fruitkey => $fruitvalue) {
      if (isset($data->$fruitkey)) {
        $link[$fruitkey] = $data->$fruitkey;
      }
    }
    return $link;
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
  public function getBlockIds($block_array) {
    $ids = [];
    foreach ($block_array as $block) {
      $ids[] = $block->id;
    }
    return $ids;
  }
  public function cleanNeos($field, $owner, $locale) {
    $criteria = craft()->elements->getCriteria(Neo_ElementType::NeoBlock);
    $criteria->fieldId = $field->id;
    $criteria->ownerId = $owner->id;
    $criteria->locale = $locale;
    $criteria->limit = null;
    $blocks = $criteria->find();
    $newBlocks = [];
    return $blocks;
  }
  public function saveNeo($field, $locale, $owner, $data) {
    // Delete blocks in locale and create copies of english versions
    $blocks = $this->cleanNeos($field, $owner, $locale);
    $neoblocks = [];
    
    $pattern = '/(_loc)/';
    foreach ($blocks as $key => $block) {
      if (isset($data[$key])) {
        $neo_matrix = [];
        $fruitlinks = [];
        $text = [];
        $neo_fields = $block->getFieldLayout()->getFields();
        foreach ($neo_fields as $neo_field) {
          $neoblock_val = $neo_field->getField();
          $neohandle = $neoblock_val->handle;
          $neo_loc_handle = preg_replace($pattern, "Loc", $neohandle);
          $fruitLink = $neoblock_val->type === 'FruitLinkIt';
          if (isset($data[$key]->$neo_loc_handle)) {
            if ($neoblock_val->type === 'PlainText') {
              $block->getContent()->setAttribute($neohandle, $data[$key]->$neo_loc_handle);
              $text[$neo_loc_handle] = [
                'lang' => $data[$key]->$neo_loc_handle,
                'og' => $block->$neohandle
              ];
            } elseif ($neoblock_val->type === 'RichText') {
              $block->getContent()->setAttribute($neohandle, $data[$key]->$neo_loc_handle);
            } elseif ($neoblock_val->type === 'Matrix') {
              $criteria = craft()->elements->getCriteria(ElementType::MatrixBlock);
              $criteria->ownerId = $block->id;
              $criteria->fieldId = $neoblock_val->id;
              if ($neoblock_val->translatable == 1) {
                $criteria->locale = $locale;
              }
              $results = $criteria->find();
              $blockIds = [];
              $result_blocks = [];
              $index = 0;
              foreach ($results as $result) {
                if (isset($data[$key]->$neo_loc_handle[$index])) {
                  $result = craft()->matrix->getBlockById($result->id, $locale);
                  $result_blocks[] = $this->saveMatrix($result, $data[$key]->$neo_loc_handle[$index]);
                  $blockIds[] = $result;
                  $index++;
                }
              }
              $neo_matrix[] = [
                'val' => $neoblock_val,
                'matrix' => $results,
                'result' => $result_blocks
              ];
              $block->getContent()->setAttribute($neohandle, $blockIds);
            } elseif ($fruitLink) {
              $og_link = $block->getContent()[$neohandle];
              $link = $this->saveFruitLink($og_link, $data[$key]->$neo_loc_handle);
              $block->getContent()->setAttribute($neohandle, $link);
              $fruitlinks[$neo_loc_handle] = [$link, $og_link];
            }
          }
        }
        $saved_neo = craft()->neo->saveBlock($block);
        $neoblocks[] = [
            'data' => $data[$key],
            'block' => $block,
            'valid' => $block->getContent()->getErrors(),
            'structure' => $neo_matrix,
            'text' => $text
          ];
      }
    }
    return $neoblocks;
  }
  public function cleanMatrixBlocks($entry, $field, $target_locale, $data) {
    $criteria = craft()->elements->getCriteria(ElementType::MatrixBlock);
    $criteria->ownerId = $entry->id;
    $criteria->fieldId = $field->id;
    // $criteria->ownerLocale = craft()->i18n->primarySiteLocaleId;
    $english_results = $criteria->find();
    $matrices = [];
    foreach ($english_results as $key => $matrix_block) {
      if ($matrix_block->ownerLocale == NULL) {
        $matrix_block->ownerLocale = craft()->language;
        $matrix_block->locale = craft()->language;
        craft()->matrix->saveBlock($matrix_block);
      }
      $is_english = ($matrix_block->ownerLocale == craft()->language) || ($matrix_block->ownerLocale == NULL);
      if ($data[$key] && $is_english) {
        $block = craft()->matrix->getBlockById($matrix_block->id, $target_locale);
        $matrices[] = [
          'block' => $block,
          'data' => $data[$key],
          'original' => $matrix_block
        ];

      }
    }
    return $matrices;
  }
  public function saveMatrix($block, $data, $original = array()) {
    if (!$block) { return null; }
    $type = craft()->matrix->getBlockTypeById($block->typeId);
    $fields = $type->getFieldLayout()->getFields();
    foreach ($fields as $field) {
      $field_data = $field->getField();
      $handle = $field_data->handle;
      $pattern = '/(_loc)/';
      $loc_handle = preg_replace($pattern, "Loc", $handle);
      $fruitLink = $field_data->type === 'FruitLinkIt';
      $required = $block->isAttributeRequired($handle); 
      if (isset($data->$loc_handle)) {
        if ($fruitLink) {
          $og_link = $block->getContent()[$handle];
          if (!empty($original)) {
            $og_link = $original->getContent()[$handle];
          }
          $link = $this->saveFruitLink($og_link, $data->$loc_handle);
          $block->getContent()->setAttribute($handle, $link);
        } elseif ($field_data->type === 'Assets') {
          if (sizeof($original) < 1) {
            $original = craft()->matrix->getBlockById($block->id);
          }
          $image = $original->$handle->first();
          if (!isset($block->$handle[0])) {
            $block->getContent()->setAttribute($handle, [$image->id]);
          }
        } elseif ($field_data->type === 'Dropdown') {
          $block->getContent()->setAttribute($handle, $block->$handle);
        } elseif ($field_data->type === 'Categories') {
          $cats = [];
          foreach ($data->$loc_handle as $category) {
            $cats[] = $category->id;
          }
          $block->getContent()->setAttribute($handle, $cats);
        } elseif (($required && sizeof($data->$loc_handle) > 0) || !$required){
          $block->getContent()->setAttribute($handle, $data->$loc_handle);
        }
      } elseif ($field_data->type === 'Assets') {
        $original = craft()->matrix->getBlockById($block->id);
        $image = $original->$handle->first();
        $block->getContent()->setAttribute($handle, [$image->id]);
      }
    }
    $saved = craft()->matrix->saveBlock($block);
    return [$data, $block, 'errors' => $block->getContent()->getErrors()];
  }
  public function setField($field, $entry) {
    $type = $field->type;
    if ($type === 'Matrix') {
      $this->saveMatrix($field, $entry);
    } elseif ($type === 'Asset') {
      $this->saveImage($field->image);
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
    } else {
      $entry->getContent()->setAttribute($handle, $value);
    }
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

    $updated = [];
    if (gettype($data) == 'array') {
      $items = array_values($data);
      if (isset($items[0]) && $this->isCategory($items[0])) {
        $updated = $this->localizeCategories($data);
      } else {
        foreach ($items as $item) {
          $updated[] = $this->localizeEntry($item);
        }
      }
    } elseif (isset($data->title) && $data->title == 'globals') {
      $updated = $this->localizeGlobals($data);
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
        $fname = CRAFT_STORAGE_PATH . "/" . $result['type'] . '-'. $result['slug'] . '-'. $variables['locale'] .'.json';
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
        sleep(5);
        unlink($fname);
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