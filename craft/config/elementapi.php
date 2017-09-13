<?php
namespace Craft;
use craft;

// Organize by section type and map to { label: title, value: id }
function transformForAutocomplete($entry) {
    return [
        'label' => $entry->title,
        'value' => $entry->id
    ];
}

function normalizeEntry($entry) { // TODO come back and make sure customText fields get updated to customText_loc
    $normalized = [];

    foreach ($entry->getFieldLayout()->fields as $idx => $fieldLayoutField) {
        $field = $fieldLayoutField->field;
        $classHandle = $field->fieldType->classHandle;
        $handle = $field->handle;

        $entriesData = new \stdClass();

        if ($classHandle == 'Entries') {
            $entryIds = $entry[$handle]->ids();
            $entriesData->_type = $classHandle;
            $entriesData->_data = [];
            $entriesData->_ids = $entryIds;
            $entriesData->_title = $entry[$handle]->title;

            $entries = [];
            foreach ($entryIds as $idx => $id) {
                $entriesData->_data[$id] = normalizeEntry(craft()->entries->getEntryById($id));
            }

            $normalized[$handle] = $entriesData;
        } elseif ($classHandle == 'Matrix') {
            $blockIds = $entry[$handle]->ids();
            $entriesData->_type = $classHandle;
            $entriesData->_data = new \stdClass();
            $entriesData->_ids = $blockIds;

            $blocks = [];
            foreach ($blockIds as $idx => $id) {
                $block = craft()->matrix->getBlockById($id);
                $entriesData->_data->$id = normalizeEntry($block);
            }

            $normalized[$handle] = $entriesData;
        } elseif ($classHandle == 'Neo') {
          $blockIds = $entry[$handle]->ids();
          $entriesData->_type = $classHandle;
          $entriesData->_data = new \stdClass();
          $entriesData->_ids = $blockIds;

          $blocks = [];
          foreach ($blockIds as $idx => $id) {
              $block = craft()->neo->getBlockById($id);
              $entriesData->_data->$id = normalizeEntry($block);
          }
          $normalized[$handle] = $entriesData;  
        }  elseif ($classHandle == 'Assets') {
            $entryIds = $entry[$handle]->ids();
            $normalized[$handle] = array(
                '_type' => $classHandle,
                '_ids' => $entryIds,
            );
        } else {
            $normalized[$handle] = array(
                '_type' => $classHandle,
                '_data' => $entry[$handle],
            );
            $normalized["_title"] = $entry->title;
        }

    }
    return $normalized;
}

$GLOBALS['currSection'] = -1;

function getValues($entry, $fields = [], $nestedNeo = false, $normalized = false) {
    $items=[];
    $render = [];
    $itemsRaw = [];
    if (isset($entry->content)) {
        foreach ($entry->content->attributes as $key => $value) {
             $itemsRaw[$key] = $value;
        }
    }
    
    if (isset($entry['type']['handle'])) {
        $render['type'] = $entry['type']['handle'];
    }
    // $render['shouldNormalize'] = $normalized;
    if ($normalized) {
      $render['normalized'] = normalizeEntry($entry);  
    }
    foreach ($entry->fieldLayout->fields as $key => $value) {
        $handle = $value->field->handle;
        if (array_key_exists($handle, $itemsRaw) && (isset($fields[$handle]) || $fields == [])) {
            $type = $value->field->type;
            $render[$handle] = [];
            if ($type == 'Matrix') {
               foreach ($entry[$handle] as $key1 => $value1) {
                   $newFields = [];
                   if (isset($fields[$handle])) {
                       if ($fields[$handle] !== true) {
                            $newFields = $fields[$handle];
                       }
                   }
                   $vals = getValues($entry[$handle][$key1], $newFields); // , false, $normalized);
                   $vals['handle'] = $handle;
                   $render[$handle][] = $vals;
                   // $render['normalized'] = normalizeEntry($entry);
                }
            } else if ($type == 'Neo') {
                $GLOBALS['currSection'] = -1;
                foreach ($entry[$handle] as $key1 => $value1) {
                    $newFields = [];
                    if (isset($fields[$handle])) {
                        if ($fields[$handle] !== true) {
                            $newFields = $fields[$handle];
                        }
                    }
                    
                    $vals = getValues($entry[$handle][$key1], $newFields); // , true, $normalized);
                    $vals['handle'] = $handle;
                    if (isset($vals['sectionTitle_loc'])) {
                        $GLOBALS['currSection']++;

                        $render[$handle][$GLOBALS['currSection']] = getValues($entry[$handle][$key1], $newFields, true); // , $normalized);
                        $render[$handle][$GLOBALS['currSection']]['elements'] = [];
                        // $render[$handle][$GLOBALS['currSection']]['normalized-neo-1'] = normalizeEntry($entry);
                    } else {
                        if ($vals['handle'] === 'footerLinks' || $vals['handle'] === 'entryBuilder' || $vals['handle'] === 'primaryNavigation' || $vals['handle'] === 'storyBuilder' || $vals['handle'] === 'blogBuilder') {
                          $GLOBALS['currSection']++;
                        }
                        $vals2 = getValues($entry[$handle][$key1], $newFields, true);
                        $vals2['type'] = $entry[$handle][$key1]->type->handle;
                        if ($handle === 'entryBuilder' || $handle === 'storyBuilder' || $handle === 'blogBuilder') {
                          $render[$handle][$GLOBALS['currSection']] = $vals2;
                        } else {
                          $render[$handle][$GLOBALS['currSection']]['elements'][] = $vals2;
                        }
                    }
                }
            } else if ($type == 'Assets') {
              
              if (count($entry[$handle]) === 1 && $entry[$handle][0]) {
                $render[$handle]['kind'] = $entry[$handle][0]->kind;
                $render[$handle]['url'] = $entry[$handle][0]->url;
                $render[$handle]['width'] = $entry[$handle][0]->width;
                $render[$handle]['height'] = $entry[$handle][0]->height;
                $render[$handle]['title'] = $entry[$handle][0]->title;
                // $render['normalized-asset-1'] = normalizeEntry($entry);
              } else if (count($entry[$handle]) > 1) {
                $assets = [];
                foreach ($entry[$handle] as $key1 => $value1) {
                  $assets[$key1] = [];
                  $assets[$key1]['kind'] = $value1->kind;
                  $assets[$key1]['url'] = $value1->url;
                  $assets[$key1]['width'] = $value1->width;
                  $assets[$key1]['height'] = $value1->height;
                  $assets[$key1]['title'] = $value1->title;
                  // $assets[$key1]['normalized-asset-2'] = normalizeEntry($value1);
                }
                $render[$handle] = $assets;
               }
           } else if ($type == 'Entries') {
               $render[$handle]['type'] = $type;
               foreach ($entry[$handle] as $key1 => $value1) {
                   $render[$handle]['data'][$key1]['slug'] = $value1['slug'];
                   $render[$handle]['data'][$key1]['data'] = getValues($value1, isset($fields[$handle]) ? $fields[$handle] : []); // , false, $normalized);
               }
           } else if ($type == 'Categories') {
              foreach ($entry[$handle] as $key1 => $value1) {
                 $render[$handle][$key1] = getValues($value1, isset($fields[$handle]) ? $fields[$handle] : []); //, false, $normalized);
              }
           } else if ($itemsRaw[$handle] !== NULL) {
                 if ($type == "Lightswitch") {
                     $render[$handle] = $itemsRaw[$handle] == "0" ? false : true;
                 } else if ($type == "Table") {
                     $render[$handle] = $entry[$handle];
                 } else {
                    if (isset($entry[$handle]->attributes['customText'])) {
                      $itemsRaw[$handle]['customText_loc'] = $entry[$handle]->attributes['customText'];
                      unset($itemsRaw[$handle]['customText']);
                    }
                    if($handle === 'linkInfo' || $handle === 'button' || $handle == 'loginLink_loc' || $handle === 'ctaButton' || $handle === 'footerLink' || $handle === 'linkInfo' || $handle === 'button' || $handle === 'navItem') {
                      $render['buttonStyle'] = $entry->type->handle;
                    //   if ($entry[$handle]) {
                        if ($entry[$handle]->type === 'entry') {
                            if (isset($entry[$handle]->entry->content->attributes['page_uri'])) {
                            $itemsRaw[$handle]['uri'] = $entry[$handle]->entry->content->attributes['page_uri'];  
                            } elseif (isset($entry[$handle]->entry->content->attributes['page_uri_loc'])) { // TODO update to remove the _loc
                            $itemsRaw[$handle]['uri'] = $entry[$handle]->entry->content->attributes['page_uri_loc'];  
                            } else {
                            if (isset($entry[$handle]->entry->attributes['slug'])) {
                                $itemsRaw[$handle]['uri'] = $entry[$handle]->entry->attributes['slug'];
                            }
                            }
                        }
                    // }
                    }
                    if(($handle === 'mainNavigationCta' || $handle === 'mainNavigationCta_loc') && $entry[$handle]->attributes['type'] === 'entry') { // TODO update to remove the _loc from mainNavigationCta_loc from craft
                      if (isset($entry[$handle]->entry->content->attributes['page_uri'])) {
                        $itemsRaw[$handle]['uri'] = $entry[$handle]->entry->content->attributes['page_uri'];
                      } elseif (isset($entry[$handle]->entry->content->attributes['page_uri_loc'])) { // TODO pdate to remove _loc from page_uri in craft
                        $itemsRaw[$handle]['uri'] = $entry[$handle]->entry->content->attributes['page_uri_loc'];
                      }
                    }
                    if($handle === 'primaryCta' || $handle === 'secondaryLink' || $handle === 'ctaButton') {
                      if (isset($entry[$handle]->attributes)) {
                        if ($entry[$handle]->attributes['type'] === 'entry') {
                          if (isset($entry[$handle]->entry->content->attributes['page_uri'])) {
                            $itemsRaw[$handle]['uri'] = $entry[$handle]->entry->content->attributes['page_uri'];    
                          } elseif (isset($entry[$handle]->entry->content->attributes['page_uri_loc'])) {
                            $itemsRaw[$handle]['uri'] = $entry[$handle]->entry->content->attributes['page_uri_loc'];
                          }
                        }
                      }
                    }
                    $render[$handle] = $itemsRaw[$handle];
                 }
           } else {
               $render[$handle] = $itemsRaw[$handle];
           }
       }
    }

    if (isset($entry->slug)) {
      if ($entry['slug'] != '') {
        $render['slug'] = $entry['slug'];
      }
    }

    if (isset($entry->title)) {
      if ($entry['title'] != '') {
        $render['title_loc'] = $entry['title'];
      }
    }
    if (isset($entry->type) && isset($entry->section)) {
      $entryType = $entry->type;
      $render['entryType'] = $entryType->handle;
      if (isset($entry->attributes['postDate'])) {
        $render['postDate'] = $entry->attributes['postDate']->getTimestamp();  
      }
    }
    return $render;
}

return [
    'endpoints' => [
        'tester.json' => [
            'elementType' => 'Entry',
            'paginate' => false,
            'criteria' => [
                'section' => 'siteNavigation',
                'locale' => 'en'
            ],
            'transformer' => function(EntryModel $entry) {
                return getValues($entry);
            },
        ],
        'api/draft/<id:\d+>/<draft:\d+>' => function($id, $draft) {
            $params = craft()->request->getQuery();
            return [
                'elementType' => 'Entry',
                'criteria' => [
                  'id' => $id
                ],
                'paginate' => false,
                'transformer' => function(EntryModel $entry) use (&$draft, &$params) {
                    $fieldsRaw = isset($params['fields']) ? explode(',', $params['fields']) : [];
                    $fields = [];

                    foreach($fieldsRaw as $item) {
                        $temp = &$fields;

                        foreach(explode('.', $item) as $key) {
                            $temp = &$temp[$key];
                        }

                        $temp = array();
                    }

                    $t = craft()->entryRevisions->getDraftById($draft);
                    return getValues($t, $fields);
                }
            ];
        },
        'api/<section:[a-zA-Z0-9]*>.json' => function($section) {
            $params = craft()->request->getQuery();
            
            return [
            'elementType' => 'Entry',
            'criteria' => [
              'section' => $section,
              'id' => isset($params['id']) ? $params['id'] : null,
              'slug' => isset($params['slug']) ? $params['slug'] : null,
              'uri' => isset($params['uri']) ? $params['uri'] : null,
              'locale' => isset($params['locale']) ? $params['locale'] : 'en_us'
            ],
            'paginate' => true,
            'elementsPerPage' => 100,
            'transformer' => function(EntryModel $entry) use (&$params) {
                $fieldsRaw = isset($params['fields']) ? explode(',', $params['fields']) : [];
                $fields = [];
                $normalized = isset($params['normalize']); //  && $params['normalize'] == 'true';
                foreach($fieldsRaw as $item) {
                    $temp = &$fields;

                    foreach(explode('.', $item) as $key) {
                        $temp = &$temp[$key];
                    }

                    $temp = array();
                }

                return getValues($entry, $fields, false, $normalized);
              }
            ];
        },
        'category/<slug:[a-zA-Z0-9]*>.json' => function($slug) {
            $params = craft()->request->getQuery();
            return [
            'elementType' => 'Category',
            'criteria' => [
              'group' => $slug,
              'id' => isset($params['id']) ? $params['id'] : null,
            ],
            'paginate' => true,
            'elementsPerPage' => 100,
            'transformer' => function(CategoryModel $entry) use (&$params) {
                $fieldsRaw = isset($params['fields']) ? explode(',', $params['fields']) : [];
                $fields = [];
                $normalized = isset($params['normalize']);
                foreach($fieldsRaw as $item) {
                    $temp = &$fields;

                    foreach(explode('.', $item) as $key) {
                        $temp = &$temp[$key];
                    }

                    $temp = array();
                }
                return getValues($entry, $fields, false, $normalized);
              }
            ];
        },
        'globals/<slug:[a-zA-Z0-9]*>.json' => function($slug) {
            $params = craft()->request->getQuery();
            return [
            'elementType' => 'GlobalSet',
            'criteria' => [
              'handle' => $slug,
            ],
            'paginate' => true,
            'elementsPerPage' => 100,
            'transformer' => function(GlobalSetModel $entry) use (&$params) {
                $fieldsRaw = isset($params['fields']) ? explode(',', $params['fields']) : [];
                $fields = [];
                $normalized = isset($params['normalize']);
                foreach($fieldsRaw as $item) {
                    $temp = &$fields;

                    foreach(explode('.', $item) as $key) {
                        $temp = &$temp[$key];
                    }

                    $temp = array();
                }
                return getValues($entry, $fields, false, $normalized);
              }
            ];
        }
    ]
];