<?php
namespace Craft;
ini_set('memory_limit','1024M');
class DraftPreviewController extends BaseController
{
  protected $allowAnonymous = true;

  public function un_cereal_ize() {
    $string = craft()->request->rawBody;
    parse_str($string, $output);
    return $output;
  }

  public function getValues($entry, $fields = [], $parentKey, $nestedNeo = false, $normalized = false) {
    $GLOBALS['count']++;
    $items=[];
    $render = [];
    $itemsRaw = [];
    $cdnUrl = craft()->config->get('environmentVariables')['gcsUrl'];
    $imageExtensions = array("jpg", "jpeg", "png");
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
                   $vals = getValues($entry[$handle][$key1], $newFields, $handle, false, false); // , false, $normalized);
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
                    
                    $vals = getValues($entry[$handle][$key1], $newFields, $key1); // , true, $normalized);
                    $vals['handle'] = $handle;
                    if (isset($vals['sectionTitle_loc'])) {
                        $GLOBALS['currSection']++;

                        $render[$handle][$GLOBALS['currSection']] = getValues($entry[$handle][$key1], $newFields, $handle, true); // , $normalized);
                        $render[$handle][$GLOBALS['currSection']]['elements'] = [];
                        // $render[$handle][$GLOBALS['currSection']]['normalized-neo-1'] = normalizeEntry($entry);
                    } else {
                        if ($vals['handle'] === 'footerLinks' || $vals['handle'] === 'entryBuilder' || $vals['handle'] === 'primaryNavigation' || $vals['handle'] === 'storyBuilder') {
                          $GLOBALS['currSection']++;
                        }
                        $vals2 = getValues($entry[$handle][$key1], $newFields, $handle, true); // , $normalized);
                        $vals2['type'] = $entry[$handle][$key1]->type->handle;
                        $render[$handle][$GLOBALS['currSection']]['elements'][] = $vals2;
                        // $render[$handle][$GLOBALS['currSection']]['normalized-neo-2'] = normalizeEntry($entry);
                    }
                }
            } else if ($type == 'Assets') {
              if (count($entry[$handle]) === 1 && $entry[$handle][0]) {
                $render[$handle]['kind'] = $entry[$handle][0]->kind;
                $render[$handle]['url'] = $entry[$handle][0]->url;
                $render[$handle]['width'] = $entry[$handle][0]->width;
                $render[$handle]['height'] = $entry[$handle][0]->height;
                $render[$handle]['title'] = $entry[$handle][0]->title;
                if (in_array(strtolower($entry[$handle][0]->extension), $imageExtensions)) {
                    // $render[$handle]['url2x'] = transform2x($entry[$handle][0], $cdnUrl);
                    // $render[$handle]['url1x'] = transform1x($entry[$handle][0], $cdnUrl);
                    $render[$handle]['url2x'] = $entry[$handle][0]->url;
                    $render[$handle]['url1x'] = $entry[$handle][0]->url;
                }
                // $rsender['normalized-aset-1'] = normalizeEntry($entry);
              } else if (count($entry[$handle]) > 1) {
                $assets = [];
                foreach ($entry[$handle] as $key1 => $value1) {
                    $assets[$key1] = [];
                    $assets[$key1]['kind'] = $value1->kind;
                    $assets[$key1]['url'] = $value1->url;
                    $assets[$key1]['width'] = $value1->width;
                    $assets[$key1]['height'] = $value1->height;
                    $assets[$key1]['title'] = $value1->title;
                    if (in_array(strtolower($value1->extension), $imageExtensions)) {
                        // $assets[$key1]['url2x'] = transform2x($value1, $cdnUrl);
                        // $assets[$key1]['url1x'] = transform1x($value1, $cdnUrl);
                        $assets[$key1]['url2x'] = $value1->url;
                        $assets[$key1]['url1x'] = $value1->url;
                    }
                  // $assets[$key1]['normalized-asset-2'] = normalizeEntry($value1);
                }
                $render[$handle] = $assets;
               }
           } else if ($type == 'Entries') {
               $render[$handle]['type'] = $type;
               foreach ($entry[$handle] as $key4 => $value1) {
                   $render[$handle]['data'][$key4]['slug'] = $value1['slug'];
                   // $render[$handle]['data'][$key4]['parentKey'] = $parentKey;
                   if ($parentKey === 'relatedEntries') {
                    // This filteredFields is to prevent relatedEntries from getting stuck into another relatedEntries entry.
                    // May need to add more fields to this.
                    $filteredFields = [];
                    foreach ($value1->fieldLayout->fields as $fkey => $fvalue) {
                      if ($fvalue->field->handle !== 'relatedEntries' && $fvalue->field->handle !== 'storyBuilder' && $fvalue->field->handle !== 'entryBuilder') {
                        $filteredFields[$fvalue->field->handle] = [];
                      }
                    }
                    
                    // $filteredFields['title_loc'] = [];
                    // $filteredFields['brandLogo'] = [];
                    // $filteredFields['cardHeadline_loc'] = [];
                    // $filteredFields['cardDeviceImage'] = [];
                    // $filteredFields['brandColor'] = [];
                    // $filteredFields['lightText'] = [];
                    // $filteredFields['slug'] = [];
                    // $filteredFields['title'] = [];
                    // $filteredFields['spotlightDeviceAsset'] = [];
                    // $filteredFields['spotlightSummary_loc'] = [];
                    // $filteredFields['cardImage'] = [];
                    // $filteredFields['cardColor'] = [];
                    $render[$handle]['data'][$key4]['data'] = getValues($value1, $filteredFields, $handle); // , false, $normalized);                     
                   } else {
                    $render[$handle]['data'][$key4]['data'] = getValues($value1, isset($fields[$handle]) ? $fields[$handle] : [], $handle); // , false, $normalized); 
                   }

               }
           } else if ($type == 'Categories') {
              foreach ($entry[$handle] as $key1 => $value1) {
                 $render[$handle][$key1] = getValues($value1, isset($fields[$handle]) ? $fields[$handle] : [], $handle); //, false, $normalized);
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
                    if($handle === 'linkInfo' || $handle === 'button' || $handle == 'loginLink_loc' || $handle === 'ctaButton' || $handle === 'footerLink' || $handle === 'linkInfo' || $handle === 'button' || $handle === 'navItem' || $handle === 'successStoriesButton' || $handle === 'creativeSpotlightButton') {
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
                    if($handle === 'primaryCta' || $handle === 'secondaryLink' || $handle === 'ctaButton' || $handle === 'relatedContentButton') {
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
                    if (gettype($itemsRaw[$handle]) === 'string') {
                        $render[$handle] = html_entity_decode($itemsRaw[$handle]);
                    } else {
                        $render[$handle] = $itemsRaw[$handle];
                    }
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
       $render['title'] = $entry['title'];
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

  public function format404($pageData) {
    $errorPage = [
      "id" => "*",
      "slug" => "*",
      "color" => "Yellow",
      "sidenav" => false,
      "title_loc" => "404",
      "pageBuilder" => [
        [
          "sectionTitle_loc" => $pageData->headline_loc,
          "sectionBackgroundColor" => "white",
          "elements" => [
            [
              "type" => "error-page",
              "titleLoc" => $pageData->headline_loc,
              "subTitleLoc" => $pageData->subhead_loc,
              "buttonPrimaryLabelLoc" => $pageData->primaryCta->customText_loc,
              "buttonPrimaryLink" => "/",
              "image" => $pageData->image
            ]
          ]
        ]
      ]
    ];
    return $errorPage;
  }

  public function formatPage($pageData, $general, $categories) {
    $page = $pageData;
    switch ($page->slug) {
        case 'home':
            $homeHeroHeadline = $page->homeHero[0]->headline_loc;
            $intro = [
              "sectionTitle_loc" => $page->homeHero[0]->sectionTitle_loc,
              "sectionBackgroundColor" => 'white',
              "elements" => [
                [
                  "type" => 'home-page-headline',
                  "formatting" => 'vertically-center',
                  "color" => 'Yellow',
                  "headline_loc" => $homeHeroHeadline,
                  "ctaButtons" => [
                    [
                      "button" => $page->homeHero[0]->button, // TODO => update to remove _loc from button
                      "buttonStyle" => 'primaryButton',
                      "outline" => $page->homeHero[0]->buttonOutline,
                      "buttonType" => $page->homeHero[0]->buttonStyle,
                      "filled" => true,
                      "handle" => $page->homeHero[0]->handle
                    ]
                  ]
                ]
              ]
            ];
            array_unshift($page->pageBuilder, $intro);
            return $page;
          break;
        case 'creative-spotlight':
          $creativeSpotRef = $page;
          $selectOptions = [
            [
              "placeholder" => $general->productFilterTitle_loc,  //TODO come back and replace this with data from craft
              "options" => $categories['product']
            ],
            [
              "placeholder" => $general->objectiveFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['objective']
            ],
            [
              "placeholder" => $general->industryFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['industry']
            ],
            [
              "placeholder" => $general->regionFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['region']
            ]
          ];
          $response = json_decode(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/api/creativeSpotlightEntries.json?perpage=2&page=1'));
          $creativeSpotlightEntries = $response->data;
          $intro = [
            "sectionTitle_loc" => $creativeSpotRef->title_loc,
            "sectionBackgroundColor" => 'white',
            "elements" => [
              [
                "imageWithAlt" => [],
                "headline_loc" => $creativeSpotRef->headline_loc,
                "subhead_loc" => '',
                "ctaButtons" => [],
                "type" => 'centeredTextLockup'
              ],
              [
                "type" => 'successStoriesGrid',
                "selectOptions" => $selectOptions,
                "stories" => $creativeSpotlightEntries
              ]
            ]
          ]; 
          $page->pageBuilder = [$intro];
          break;
        case 'blog':
          $selectOptions = [
            [
              "placeholder" => $general->allFilter_loc,  //TODO come back and replace this with data from craft
              "options" => $categories['blog']
            ],
            [
              "placeholder" => $general->industryFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['industry']
            ],
            [
              "placeholder" => $general->regionFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['region']
            ]
          ];
          $response = json_decode(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/api/insightsAndNewsEntries.json?perpage=2&page=1'));
          $blogEntries = $response->data;
          
          foreach( $blogEntries as $blogEntry) {
            foreach ($categories['blog'] as $cat) {
              if (strpos($blogEntry->entryType, $cat->slug) !== false) {
                $blogEntry->entryTypeLabel = $cat->title_loc;
              }
            }
          }
          
          $page->pageBuilder = [
            [
              "sectionTitle_loc" => "OverView", // TODO get this from 
              "sectionBackgroundColor" => "white",
              "elements" => [
                [
                  "headline_loc" => $page->headline_loc,
                  "subhead_loc" => "",
                  "ctaButtons" => [],
                  "type" => "centeredTextLockup"
                ],

                [
                  "type" => "blog-grid",
                  "formatting" => "half-padding",
                  "color" => "black",
                  "selectOptions" => $selectOptions,
                  "cards" => $blogEntries,
                ]
              ]
            ]
          ];
          break;
        case 'success-stories':
          $successRef = $page;
          $selectOptions = [
            [
              "placeholder" => $general->productFilterTitle_loc,  //TODO come back and replace this with data from craft
              "options" => $categories['product']
            ],
            [
              "placeholder" => $general->objectiveFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['objective']
            ],
            [
              "placeholder" => $general->industryFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['industry']
            ],
            [
              "placeholder" => $general->regionFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['region']
            ]
          ];

          // *****************************************************************
          // THIS METHOD GETS DATA WITHOUT USING THE SIMPLE API
          // $criteria = craft()->elements->getCriteria(ElementType::Entry);
          // $criteria->section = 'successStoryEntries';
          // $criteria->limit = 2;
          // $stories = $criteria->find();
          // $modStories = [];
          // foreach ($stories as $store) {
          //   $tempStory = getValues($store, [], 'root', false, false);
          //   array_push($modStories, $tempStory);
          // }
          // *****************************************************************

          //Currently limiting to 2 entries to not blow up the call
          $response = json_decode(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/api/successStoryEntries.json?perpage=2&page=1'));
          $successStoriesEntries = $response->data;

          $intro = [
            "sectionTitle_loc" => $successRef->title_loc,
            "sectionBackgroundColor" => 'white',
            "elements" => [
              [
                "imageWithAlt" => [],
                "headline_loc" => $successRef->headline_loc,
                "subhead_loc" => '',
                "ctaButtons" => [],
                "type" => 'centeredTextLockup'
              ],
              [
                "type" => 'successStoriesGrid',
                "selectOptions" => $selectOptions,
                "stories" => $successStoriesEntries
              ]
            ]
          ];
          $page->pageBuilder = [$intro];
          break;
        case 'inspiration':
          $inspRef = $page;
          $selectOptions = [
            [
              "placeholder" => $general->productFilterTitle_loc,  //TODO come back and replace this with data from craft
              "options" => $categories['product']
            ],
            [
              "placeholder" => $general->objectiveFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['objective']
            ],
            [
              "placeholder" => $general->industryFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['industry']
            ]
          ];
          $selectOptionsSpotlight = [
            [
              "placeholder" => $general->productFilterTitle_loc,  //TODO come back and replace this with data from craft
              "options" => $categories['product']
            ],
            [
              "placeholder" => $general->objectiveFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['objective']
            ],
            [
              "placeholder" => $general->industryFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['industry']
            ],
            [
              "placeholder" => $general->regionFilterTitle_loc, //TODO come back and replace this with data from craft
              "options" => $categories['region']
            ]
          ];
          $response = json_decode(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/api/successStoryEntries.json?perpage=2&page=1'));
          $successStoriesEntries = $response->data;
          $response = json_decode(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/api/creativeSpotlightEntries.json?perpage=2&page=1'));
          $creativeSpotlightEntries = $response->data;
          

          $stories = [
            "type" => 'successStoriesGrid',
            "selectOptions" => $selectOptions,
            "stories" => $successStoriesEntries,
            "hideFilters" => true,
          ];
          $spotlights = [
            "type" => 'successStoriesGrid',
            "selectOptions" => $selectOptionsSpotlight,
            "stories" => $creativeSpotlightEntries,
            "hideFilters" => true,
          ];
          $elements = [
            "sectionTitle_loc" => $inspRef->title_loc,
            "sectionBackgroundColor" => 'white',
            "elements" => [
              [
                "imageWithAlt" => [],
                "headline_loc" => $inspRef->headline_loc,
                "subhead_loc" => '',
                "ctaButtons" => [],
                "type" => 'centeredTextLockup'
              ],
              [
                "type" => 'headlineCTA',
                "headlineCopy" => $inspRef->successStoriesTitle_loc,
                "buttonCopy" => $inspRef->successStoriesButton->customText_loc,
                "link" => $inspRef->successStoriesButton->type === "entry" ? ('/'.$inspRef->successStoriesButton->uri) : ($inspRef->successStoriesButton->custom)
              ],
              $stories,
              [
                "type" => 'headlineCTA',
                "headlineCopy" => $inspRef->creativeSpotlightTitle_loc,
                "buttonCopy" => $inspRef->creativeSpotlightButton->customText_loc,
                "link" => $inspRef->creativeSpotlightButton->type === "entry" ? '/'.$inspRef->creativeSpotlightButton->uri : $inspRef->creativeSpotlightButton->custom
              ],
              $spotlights,
            ]
          ];
          $page->pageBuilder = [$elements];
          break;
        default:
            $page->pageTitle_loc = $page->title_loc;
            $page->page_themeColor = 'Yellow';
            $page->color = 'Yellow';
          if($page->entryType !== 'successStory' && $page->entryType !== 'creativeSpotlightVideo' && $page->entryType !== 'creativeSpotlightDevice') {  
            foreach ($categories['blog'] as $cat) {
              if (strpos($page->entryType, $cat->slug) >= 0) {
                $page->entryTypeLabel = $cat->title_loc;
              }
            }  
          } elseif ($page->entryType === 'creativeSpotlightVideo' || $page->entryType === 'creativeSpotlightDevice') {
            $spotlightResponse = json_decode(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/'.'api/creativeSpotlight'.'.json'));

            $creativeSpotRef = $spotlightResponse->data[0];
            $selectOptions = [
              [
                "placeholder" => $general->productFilterTitle_loc,  //TODO come back and replace this with data from craft
                "options" => $categories['product']
              ],
              [
                "placeholder" => $general->objectiveFilterTitle_loc, //TODO come back and replace this with data from craft
                "options" => $categories['objective']
              ],
              [
                "placeholder" => $general->industryFilterTitle_loc, //TODO come back and replace this with data from craft
                "options" => $categories['industry']
              ],
              [
                "placeholder" => $general->regionFilterTitle_loc, //TODO come back and replace this with data from craft
                "options" => $categories['region']
              ]
            ];
            $creativeSpotlightEntries = [$page];
            $intro = [
              "sectionTitle_loc" => $creativeSpotRef->title_loc,
              "sectionBackgroundColor" => 'white',
              "elements" => [
                [
                  "imageWithAlt" => [],
                  "headline_loc" => $creativeSpotRef->headline_loc,
                  "subhead_loc" => '',
                  "ctaButtons" => [],
                  "type" => 'centeredTextLockup'
                ],
                [
                  "type" => 'successStoriesGrid',
                  "selectOptions" => $selectOptions,
                  "stories" => $creativeSpotlightEntries
                ]
              ]
            ]; 
            $spotlightResponse->data[0]->pageBuilder = [$intro];
            $page = $spotlightResponse->data[0];
          }
          break;
    }
    return $page;
  }

  public function loadCategories($cats) {
    $returnData = [];
    foreach ($cats as $value) {
      // TODO add language and draft info to urls
      $response = json_decode(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/'.$value['url'].'.json'));
      $returnData[$value['id']] = $response->data;
    };
    return $returnData;
  }

  public function handleGetRequest($variables) {
    // var_dump($this);
    $time_pre = microtime(true);
    $oldPath = craft()->path->getTemplatesPath();
    $newPath = craft()->path->getPluginsPath().'draftpreview/templates';
    craft()->path->setTemplatesPath($newPath);

    $cats = [];

    $collections = [
      [
        "id" => 'general',
        "url" => 'globals/generalTranslations'
      ],
      [
        "id" => 'navigation',
        "url" => 'globals/headerNavigation'
      ],
      [
        "id" => 'footer',
        "url" => 'globals/footer'
      ],
      [
        "id" => 'legal',
        "url" => 'globals/legal'
      ],
    ];

    $categories = [
      [
        "id" => 'product',
        "url" => 'category/product'
      ],
      [
        "id" => 'objective',
        "url" => 'category/objective'
      ],
      [
        "id" => 'industry',
        "url" => 'category/industry'
      ],
      [
        "id" => 'region',
        "url" => 'category/region'
      ],
      [
        "id" => 'blog',
        "url" => 'category/blogType'
      ],
      [
        "id" => 'months',
        "url" => 'category/months'
      ]
    ];
    
    $singles = json_decode(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/simpleapi/Singles'));
    $cats = $this->loadCategories($categories);

    $returnData = [];
    $returnData['pages'] = [];
    $cntr = 0;
    foreach ($collections as $value) {
      // TODO add language and draft info to urls
      $response = json_decode(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/'.$value['url'].'.json'));
      if (!isset($value['isPage'])) {
        if($cntr === 0) {
          $response->data[0]->months = $cats['months'];
        }
        $returnData[$value['id']] = $response->data[0];  
      } else {
        if (!isset($value['special'])) {
          if (isset($value['isPages'])) {
            foreach ($response->data as $page) {
              array_push($returnData['pages'], $this->formatPage($page, $returnData['general'], $cats));
            }
          } else {
            array_push($returnData['pages'], $this->formatPage($response->data[0], $returnData['general'], $cats));
          }
        }
        elseif ($value['special'] === '404') {
          array_push($returnData['pages'], $this->format404($response->data[0]));
        }
        
      }
      $cntr++; 
    }

    $pageRef = craft()->entryRevisions->getDraftById($variables['draft']);
    $entryRef = craft()->entries->getEntryById($variables['id']);
    $pageRef->slug = $entryRef->slug;
    $rawPage = getValues($pageRef, [], 'root', false, false);
    $rawPage = (object) $rawPage;
    $objectPage = json_decode(json_encode($rawPage));
    $currentPage = $this->formatPage($objectPage, $returnData['general'], $cats);
    
    array_push($returnData['pages'], $currentPage);
    
    $returnData['successStories'] = [];
    $returnData['blogs'] = [];
    $returnData['spotlights'] = [];

    $time_post = microtime(true);
    $exec_time = $time_post - $time_pre;

    if (isset($variables['id'])) {
       $result = $this->returnEntry($variables['id'], $variables['locale']);
       $variables['executionTime'] = $exec_time;
       $variables['previewEntry'] = $result;
       $variables['collections'] = json_encode($returnData, true);
    }
    $this->renderTemplate('index.html', $variables);
    craft()->path->setTemplatesPath($oldPath);
  }
  public function actionHandleEntry(array $variables = array()) {
    if (craft()->request->isGetRequest()) {
      $this->handleGetRequest($variables);
    }
  }
}