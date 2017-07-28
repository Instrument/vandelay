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

function normalizeEntry($entry) {
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

function getValues($entry, $fields = []) {
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

    foreach ($entry->fieldLayout->fields as $key => $value) {
        $handle = $value->field->handle;
        if (array_key_exists($handle, $itemsRaw) && (isset($fields[$handle]) || $fields == [])) {
            $type = $value->field->type;
            $render[$handle] = [];
           if ($type == 'Neo' || $type == 'Matrix') {
               foreach ($entry[$handle] as $key1 => $value1) {
                   $newFields = [];
                   if (isset($fields[$handle])) {
                       if ($fields[$handle] !== true) {
                            $newFields = $fields[$handle];
                       }
                   }
                    $render[$handle][] = getValues($entry[$handle][$key1], $newFields);
                }
           } else if ($type == 'Assets') {
               if ($entry[$handle][0]) {
                   $render[$handle]['kind'] = $entry[$handle][0]->kind;
                   $render[$handle]['url'] = $entry[$handle][0]->url;
                   $render[$handle]['width'] = $entry[$handle][0]->width;
                   $render[$handle]['height'] = $entry[$handle][0]->height;
           $render[$handle]['title'] = $entry[$handle][0]->title;
               }
           } else if ($type == 'Entries') {
               $render[$handle]['type'] = $type;
               foreach ($entry[$handle] as $key1 => $value1) {
                   $render[$handle]['data'][$key1]['slug'] = $value1['slug'];
                   $render[$handle]['data'][$key1]['data'] = getValues($value1, isset($fields[$handle]) ? $fields[$handle] : []);
               }
           } else if ($itemsRaw[$handle] !== NULL) {
                 if ($type == "Lightswitch") {
                     $render[$handle] = $itemsRaw[$handle] == "0" ? false : true;
                 } else if ($type == "Table") {
                     $render[$handle] = $entry[$handle];
                 } else {
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
        $render['title'] = $entry['title'];
      }
    }

    return $render;
}

//teams page data
function teams($entry) {
    $teamInfo = [];
    foreach ($entry->projectTeams as $teams) {
        $teamInfo['id'] = $teams->id;
        $teamInfo['name'] = $teams->projectTeam;
    }
}

function projects($entry) {
    $cached = craft()->cache->get(\Craft\SimpleApiPlugin::getEntryCacheKey($entry));
    if (!empty($cached)) {
        return $cached;
    }

    $ownerInfo = [];
    $regionInfo = '';
    $marketInfo = '';
    $filesInfo = [];
    $teamMembersInfo = [];
    $outcomeInfo = [];
    $projectPhasesInfo = [];
    $campaignInfo = [];

    $normalized = normalizeEntry($entry);

    foreach ($entry->projectOwner as $owner) {
        $ownerInfo['id'] = $owner->id;
        $ownerInfo['name'] = $owner->firstName . ' ' . $owner->lastName;
    }

    foreach ($entry->projectRegion as $region) {
        $regionInfo = $region->regionName->value;
        $marketInfo = $region->regionMarket->value;
    }

    foreach ($entry->projectDocument as $file) {
        $fileInfo = [];
        $fileInfo['fileName'] = $file->documentUrl;
        $fileInfo['title'] = $file->documentTitle;
        array_push($filesInfo, $fileInfo);
    }

    foreach ($entry->teamMembers as $member) {
        $memberInfo = [];
        $memberInfo['name'] = $member->teamMember[0]['firstName'] . ' ' . $member->teamMember[0]['lastName'];
        $memberInfo['id'] = $member->teamMember[0]['id'];
        $memberInfo['title'] = $member->teamMemberTitle;
        $memberInfo['jobTitle'] = $member->teamMember[0]['jobTitle'];

        $peopleEntry = craft()->entries->getEntryById($member->teamMember[0]['id']);
        if ($peopleEntry) {
          $team = $peopleEntry->projectTeam[0];
          if ($team) {
            $teamEntry = craft()->entries->getEntryById($team['id']);
            $memberInfo['projectTeam'] = $teamEntry['title'];
            $memberInfo['teamid'] = $teamEntry['title'];
            $memberInfo['teamSlug'] = $teamEntry['slug'];
          }
        }

        array_push($teamMembersInfo, $memberInfo);
    }

    foreach ($entry->projectOutcome as $outcome) {
        $outcomeInfo['name'] = $outcome->outcomeSlug;
        $outcomeOwner = [];
        $outcomeOwner['name'] = $outcome->outcomeOwner[0]['firstName'] . ' ' . $outcome->outcomeOwner[0]['lastName'];
        $outcomeOwner['id'] = $outcome->outcomeOwner[0]['id'];
        $outcomeInfo['owner'] = $outcomeOwner;
        $outcomeInfo['link'] = $outcome->outcomePlan;
    }

    foreach ($entry->projectPhase as $phase) {
        $phaseInfo = [];
        $phaseInfo['id'] = $phase->id;
        $phaseInfo['description'] = $phase->description ? $phase->description : null;
        $phaseInfo['type'] = $phase->phaseType->value;
        $phaseInfo['startDate'] = $phase->startDate ? $phase->startDate->getTimestamp() * 1000 : null;
        $phaseInfo['endDate'] = $phase->endDate ? $phase->endDate->getTimestamp() * 1000 : null;
        array_push($projectPhasesInfo, $phaseInfo);
    }

    foreach ($entry->campaign as $campaign) {
        $campaignInfo['id'] = $campaign->id;
        $campaignInfo['name'] = $campaign->title;
    }

    $output = [
        'id' => $entry->id,
        'title' => $entry->title,
        'market' => $marketInfo,
        'region' => $regionInfo,
        'projectType' => $entry->projectType->value,
        'owner' => $ownerInfo,
        'startDate' => $entry->projectStartDate ? $entry->projectStartDate->getTimestamp() * 1000 : null,
        'endDate' => $entry->projectEndDate ? $entry->projectEndDate->getTimestamp() * 1000 : null,
        'files' => $filesInfo,
        'teamMembers' => $teamMembersInfo,
        'outcome' => $outcomeInfo,
        'projectPhases' => $projectPhasesInfo,
        'globalProjectLink' => $entry->globalProjectPlanUrl,
        'regionalProjectLink' => $entry->regionalProjectPlanUrl,
        'budget' => intval($entry->budget),
        'budgetSpent' => intval($entry->budgetSpent),
        'campaign' => $campaignInfo,
        'creativePartner' => $entry->creativePartner,
        '_normalized' => $normalized
    ];

    craft()->cache->set(\Craft\SimpleApiPlugin::getEntryCacheKey($entry), $output);

    return $output;
}

function pulseEdition($entry) {
    $highlights = [];
    foreach ($entry->highlights as $highlight) {
        $highlightInfo = [];
        $highlightInfo['headline'] = $highlight->headline;
        $highlightInfo['body'] = $highlight->body;
        $highlightInfo['image'] = $highlight->image[0] ? $highlight->image[0]->url : '';
        $highlightInfo['link'] = $highlight->linkURL;
        $highlightInfo['linkLabel'] = $highlight->linkLabel;
        array_push($highlights, $highlightInfo);
    }

    $brandSnapshots = [];
    foreach ($entry->brandStats as $stat) {
        if ($stat['marketRegion']) {
            $statInfo = [];
            $statInfo['name'] = $stat['marketRegion'];
            $statInfo['value'] = $stat['value'];
            if ($stat['increased'] == "1") {
                $statInfo['movement'] = 'increased';
            } else if ($stat['decreased'] == "1") {
                $statInfo['movement'] = 'decreased';
            } else {
                $statInfo['movement'] = 'unchanged';
            }
            array_push($brandSnapshots, $statInfo);
        }
    }

    $campaignStats = [];
    foreach ($entry->pulseCampaigns as $campaign) {
        $campaignInfo = [];
        $campaignInfo['title'] = $campaign->campaignTitle;
        $statsInfo = [];
        foreach ($campaign->campaignStats as $stat) {
            if ($stat['statName']) {
                $statInfo = [];
                $statInfo['name'] = $stat['statName'];
                $statInfo['value'] = $stat['value'];
                array_push($statsInfo, $statInfo);
            }
        }
        $campaignInfo['data'] = $statsInfo;
        array_push($campaignStats, $campaignInfo);
    }

    $socialStats = [];
    foreach ($entry->pulseSocials as $social) {
        $socialInfo = [];
        $socialInfo['title'] = $social->socialTitle;
        $statsInfo = [];
        foreach ($social->socialStats as $stat) {
            if ($stat['statName']) {
                $statInfo = [];
                $statInfo['name'] = $stat['statName'];
                $statInfo['value'] = $stat['value'];
                array_push($statsInfo, $statInfo);
            }
        }
        $socialInfo['data'] = $statsInfo;
        array_push($socialStats, $socialInfo);
    }

    return [
            'headline' => $entry->headline->getParsedContent(),
            'highlights' => $highlights,
            'snapshots' => [
                'brand' => [
                    'headline' => $entry->brandHeadline,
                    'subcopy1' => $entry->brandSubcopy1,
                    'subcopy2' => $entry->brandSubcopy2,
                    'alternateText' => $entry->brandAlternateText,
                    'supersetLink' => $entry->brandSupersetLink,
                    'supersetLinkLabel' => $entry->brandSupersetLinkLabel,
                    'data' => $brandSnapshots
                ],
                'campaign' => [
                    'headline' => $entry->campaignHeadline,
                    'subcopy1' => $entry->campaignSubcopy1,
                    'subcopy2' => $entry->campaignSubcopy2,
                    'alternateText' => $entry->campaignAlternateText,
                    'supersetLink' => $entry->campaignSupersetLink,
                    'supersetLinkLabel' => $entry->campaignSupersetLinkLabel,
                    'data' => $campaignStats
                ],
                'social' => [
                    'headline' => $entry->socialHeadline,
                    'subcopy1' => $entry->socialSubcopy1,
                    'alternateText' => $entry->socialAlternateText,
                    'supersetLink' => $entry->socialSupersetLink,
                    'supersetLinkLabel' => $entry->socialSupersetLinkLabel,
                    'data' => $socialStats
                ]
            ]
    ];
}

function detailsSocial($entry) {
    $entries = [];
    foreach ($entry->detailsEntries3 as $ent) {
        $entInfo = [];
        $entInfo['title'] = $ent['entryTitle'];
        $entInfo['copy'] = $ent['copy'];
        $entInfo['image'] = $ent->image[0] ? $ent->image[0]->url : '';
        array_push($entries, $entInfo);
    }
    $platforms = [];
    foreach ($entry->details3Platforms as $platform) {
        $platformInfo = [];
        $platformInfo['platform'] = $platform['platform'] ? $platform['platform']->value : null;
        $platformInfo['followers'] = $platform['followers'];
        $platformInfo['views'] = $platform['views'];
        $platformInfo['clicks'] = $platform['clicks'];
        $platformInfo['weekOverWeek'] = [
            [
                "value" => $platform['wowValue'],
                "movement" => $platform['wowMovement'],
            ]
        ];
        $platformInfo['yearOverYear'] = [
            [
                "value" => $platform['yoyValue'],
                "movement" => $platform['yoyMovement'],
            ]
        ];
        array_push($platforms, $platformInfo);
    }
    return [
        'headline' => $entry->socialHeadline,
        'copy' => $entry->detailsCopy3->getParsedContent(),
        'details' => [
            "title" => $entry->details3EntriesHeadline,
            "images" => $entries
        ],
        'platforms' => [
            "title" => $entry->details3PlatformHeadline,
            "platforms" => $platforms
        ]
    ];
}

function detailsCampaign($entry) {
    $campaigns = [];
    foreach ($entry->details2Campaigns as $campaign) {
        $campaignInfo = [];
        $campaignInfo['title'] = $campaign['campaignHeadline'];
        $campaignInfo['subtitle'] = $campaign['campaignSubtitle'];
        $campaignInfo['markets'] = [];
        foreach ($campaign->detailsMarkets2 as $market) {
            $marketInfo = [];
            $marketInfo['title'] = $market['market'];
            $marketInfo['data'] = [
                [
                    "heading" => $market['headline1'],
                    "stats" => [
                        [
                            "name" => "Visitors",
                            "value" => $market['visitors1'],
                            "movement" => $market['visitorsMovement1'] ? $market['visitorsMovement1']->value : ''
                        ],
                        [
                            "name" => "Signups",
                            "value" => $market['signups1'],
                            "movement" => $market['signupsMovement1'] ? $market['signupsMovement1']->value : ''
                        ]
                    ]
                ],
                [
                    "heading" => $market['headline2'],
                    "stats" => [
                        [
                            "name" => "Visitors",
                            "value" => $market['visitors2'],
                            "movement" => $market['visitorsMovement2'] ? $market['visitorsMovement2']->value : ''
                        ],
                        [
                            "name" => "Signups",
                            "value" => $market['signups2'],
                            "movement" => $market['signupsMovement2'] ? $market['signupsMovement2']->value : ''
                        ]
                    ]
                ]
            ];
            array_push($campaignInfo['markets'], $marketInfo);
        }
        array_push($campaigns, $campaignInfo);
    }
    return [
        'headline' => $entry->campaignHeadline,
        'copy' => $entry->detailsCopy2->getParsedContent(),
        'title' => $entry->detailsTitle2,
        'subtitle' => $entry->detailsSubtitle2,
        'campaigns' => $campaigns
    ];
}

function detailsBrand($entry) {
    $images = [];
    foreach ($entry->detailsEntries1 as $image) {
        $entInfo = [];
        $entInfo['title'] = $image['entryTitle'];
        $entInfo['copy'] = $image['copy'];
        $entInfo['image'] = $image->image[0] ? $image->image[0]->url : '';
        array_push($images, $entInfo);
    }
    return [
        'headline' => $entry->brandHeadline,
        'copy' => $entry->detailsCopy1->getParsedContent(),
        'title' => $entry->detailsTitle1,
        'subtitle' => $entry->detailsSubtitle1,
        'images' => $images
    ];
}


return [
    'endpoints' => [
        'users.json' => function() {
            return [
                'elementType' => 'Entry',
                'criteria' => ['section' => 'people'],
                'paginate' => false,
                'transformer' => function(EntryModel $entry) {
                    return transformForAutocomplete($entry);
                },
            ];
        },
        'regions.json' => function() {
            return [
                'elementType' => 'Entry',
                'criteria' => ['section' => 'regions'],
                'paginate' => false,
                'transformer' => function(EntryModel $entry) {
                    return transformForAutocomplete($entry);
                },
            ];
        },
        'campaigns.json' => function() {
            return [
                'elementType' => 'Entry',
                'criteria' => ['section' => 'globalCampaigns'],
                'paginate' => false,
                'transformer' => function(EntryModel $entry) {
                    return transformForAutocomplete($entry);
                },
            ];
        },
        'teams.json' => function() {
            return [
                'elementType' => 'Entry',
                'criteria' => ['section' => 'projectTeams'],
                'paginate' => false,
                'transformer' => function(EntryModel $entry) {
                    return teams($entry);
                },
            ];
        },
        'projects.json' => function() {
            return [
                'elementType' => 'Entry',
                'criteria' => [
                    'section' => 'projects',
                    'id' => isset(craft()->request->getQuery()['id']) ? craft()->request->getQuery()['id'] : null,
                ],
                'paginate' => false,
                'transformer' => function(EntryModel $entry) {
                    return projects($entry);
                },
            ];
        },
        'detailsBrand.json' => [
            'elementType' => 'Entry',
            'paginate' => false,
            'criteria' => ['section' => 'pulseEditions'],
            'transformer' => function(EntryModel $entry) {
                return detailsBrand($entry);
            },
        ],
        'detailsBrand.json/draft/<id:\d+>/<draft:\d+>' => function($id, $draft) {
            return [
                'elementType' => 'Entry',
                'criteria' => [
                  'section' => 'pulseEditions',
                  'id' => $id
                ],
                'paginate' => true,
                'transformer' => function(EntryModel $entry) use (&$draft) {
                    $t = craft()->entryRevisions->getDraftById($draft);
                    return detailsBrand($t);
                }
            ];
        },
        'detailsCampaign.json' => [
            'elementType' => 'Entry',
            'paginate' => false,
            'criteria' => ['section' => 'pulseEditions'],
            'transformer' => function(EntryModel $entry) {
                return detailsCampaign($entry);
            },
        ],
        'tester.json' => [
            'elementType' => 'Entry',
            'paginate' => false,
            'criteria' => [
                'section' => 'landing',
                'locale' => 'de'
            ],
            'transformer' => function(EntryModel $entry) {
                return getValues($entry);
            },
        ],
        'detailsCampaign.json/draft/<id:\d+>/<draft:\d+>' => function($id, $draft) {
            return [
                'elementType' => 'Entry',
                'criteria' => [
                  'section' => 'pulseEditions',
                  'id' => $id
                ],
                'paginate' => true,
                'transformer' => function(EntryModel $entry) use (&$draft) {
                    $t = craft()->entryRevisions->getDraftById($draft);
                    return detailsCampaign($t);
                }
            ];
        },
        'detailsSocial.json' => [
            'elementType' => 'Entry',
            'paginate' => false,
            'criteria' => ['section' => 'pulseEditions'],
            'transformer' => function(EntryModel $entry) {
                return detailsSocial($entry);
            },
        ],
        'detailsSocial.json/draft/<id:\d+>/<draft:\d+>' => function($id, $draft) {
            return [
                'elementType' => 'Entry',
                'criteria' => [
                  'section' => 'pulseEditions',
                  'id' => $id
                ],
                'paginate' => true,
                'transformer' => function(EntryModel $entry) use (&$draft) {
                    $t = craft()->entryRevisions->getDraftById($draft);
                    return detailsSocial($t);
                }
            ];
        },
        'pulseUpdates.json' => [
            'elementType' => 'Entry',
            'criteria' => ['section' => 'pulseUpdates'],
            'paginate' => false,
            'transformer' => function(EntryModel $entry) {

                $updates = [];
                foreach ($entry->updates as $update) {
                    $updateInfo = [];
                    $updateInfo['headline'] = $update->headline;
                    $updateInfo['link'] = $update->linkURL;
                    $updateInfo['linkLabel'] = $update->linkLabel;
                    $updateInfo['date'] = isset($update->date) ? strtotime($update->date) * 1000 : null;
                    array_push($updates, $updateInfo);
                }

                $teamTypes = [];
                $teamTypes['outcomePlan'] = count($entry->team[0]->outcome) ? $entry->team[0]->outcome[0]->outcomePlan : '';
                foreach ($entry->team[0]->groupType as $team) {
                    $teamInfo = [];
                    $teamInfo['name'] = $team->label;
                    $teamInfo['value'] = $team->value;
                    array_push($teamTypes, $teamInfo);
                }
                return [
                        'team' => [
                            'name' => $entry->team[0]->teamName,
                            'types' => $teamTypes,
                            'updated' => strtotime($entry->dateUpdated->iso8601()) * 1000
                        ],
                        'updates' => $updates
                ];
            },
        ],
        'pulseEdition.json' => [
            'elementType' => 'Entry',
            'criteria' => ['section' => 'pulseEditions'],
            'paginate' => false,
            'transformer' => function(EntryModel $entry) {
                return pulseEdition($entry);
            },
        ],
        'pulseEdition.json/draft/<id:\d+>/<draft:\d+>' => function($id, $draft) {
            return [
                'elementType' => 'Entry',
                'criteria' => [
                  'section' => 'pulseEditions',
                  'id' => $id
                ],
                'paginate' => true,
                'transformer' => function(EntryModel $entry) use (&$draft) {
                    $t = craft()->entryRevisions->getDraftById($draft);
                    return pulseEdition($t);
                }
            ];
        },
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
              'uri' => isset($params['uri']) ? $params['uri'] : null
            ],
            'paginate' => true,
            'elementsPerPage' => 100,
            'transformer' => function(EntryModel $entry) use (&$params) {
                $fieldsRaw = isset($params['fields']) ? explode(',', $params['fields']) : [];
                $fields = [];

                foreach($fieldsRaw as $item) {
                    $temp = &$fields;

                    foreach(explode('.', $item) as $key) {
                        $temp = &$temp[$key];
                    }

                    $temp = array();
                }

                return getValues($entry, $fields);
              }
            ];
        }
    ]
];
