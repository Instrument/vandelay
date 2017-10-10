<?php

namespace Craft\AcclaroTranslations\Job;

use Craft\AcclaroTranslations_OrderModel;
use Craft\AcclaroTranslations\Repository\DraftRepository;
use Craft\AcclaroTranslations\Repository\EntryRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository;
use Craft\AcclaroTranslations\ElementTranslator;
use Craft\ElementCriteriaModel;
use Craft\ElementType;
use Craft\EntryModel;
use Craft\GlobalSetModel;
use CApplication;

class CreateOrderTranslationDrafts implements JobInterface
{
    /**
     * @var array string
     */
    protected $targetLanguages;

    /**
     * @var array \Craft\BaseElementModel
     */
    protected $elements;

    /**
     * @var string
     */
    protected $orderName;

    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Repository\DraftRepository
     */
    protected $draftRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\EntryRepository
     */
    protected $entryRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository
     */
    protected $globalSetDraftRepository;

    /**
     * @var \Craft\AcclaroTranslations\ElementTranslator
     */
    protected $elementTranslator;

    /**
     * @param array                                                          $targetLanguages
     * @param array|\Craft\ElementCriteriaModel                              $elements
     * @param string                                                         $orderName
     * @param \CApplication                                                  $craft
     * @param \Craft\AcclaroTranslations\Repository\DraftRepository          $draftRepository
     * @param \Craft\AcclaroTranslations\Repository\EntryRepository          $entryRepository
     * @param \Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository $globalSetDraftRepository
     * @param \Craft\AcclaroTranslations\ElementTranslator                   $elementTranslator
     */
    public function __construct(
        array $targetLanguages,
        $elements,
        $orderName,
        CApplication $craft,
        DraftRepository $draftRepository,
        EntryRepository $entryRepository,
        GlobalSetDraftRepository $globalSetDraftRepository,
        ElementTranslator $elementTranslator
    ) {
        $this->targetLanguages = $targetLanguages;

        $this->elements = ($elements instanceof ElementCriteriaModel) ? $elements->find() : (array) $elements;

        $this->orderName = $orderName;

        $this->craft = $craft;

        $this->draftRepository = $draftRepository;

        $this->entryRepository = $entryRepository;

        $this->globalSetDraftRepository = $globalSetDraftRepository;

        $this->elementTranslator = $elementTranslator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $drafts = array();

        foreach ($this->targetLanguages as $language) {
            foreach ($this->elements as $element) {
                switch ($element->getElementType()) {
                    case ElementType::Entry:
                        $drafts[] = $this->createEntryDraft($element, $language);
                        break;
                    case ElementType::GlobalSet:
                        $drafts[] = $this->createGlobalSetDraft($element, $language);
                        break;
                }
            }
        }

        return $drafts;
    }

    public function createEntryDraft(EntryModel $entry, $language)
    {
        $draft = $this->draftRepository->makeNewDraft();
        $draft->name = sprintf('%s [%s]', $this->orderName, $language);
        $draft->id = $entry->id;
        $draft->sectionId = $entry->sectionId;
        $draft->creatorId = $this->craft->userSession && $this->craft->getComponent('userSession')->getUser() ? $this->craft->getComponent('userSession')->getUser()->id : '1';
        $draft->locale = $language;
        $draft->getContent()->locale = $language;
        $draft->typeId = $entry->typeId;
        $draft->slug = $entry->slug;
        $draft->postDate = $entry->postDate;
        $draft->expiryDate = $entry->expiryDate;
        $draft->enabled = $entry->enabled;
        $draft->getContent()->title = $entry->getContent()->title;
        $draft->authorId = $entry->authorId;
        $draft->parentId = $entry->parentId;

        $post = $this->elementTranslator->toPostArray($entry);

        $draft->setContentFromPost($post);

        $entryType = $draft->getType();

        if (!$entryType->hasTitleField) {
            $draft->getContent()->title = $this->craft->getComponent('templates')->renderObjectTemplate($entryType->titleFormat, $draft);
        }

        // create an entry in this locale if it doesnt exist
        if ($entry->locale !== $language && !$this->entryRepository->getEntryById($entry->id, $language)) {
            $entry->setAttribute('id', 0);
            $entry->getContent()->setAttribute('locale', $language);

            if ($entry->getUrlFormat()) {
                $entry->uri = $this->craft->getComponent('templates')->renderObjectTemplate($entry->getUrlFormat(), $entry);
            }

            $this->entryRepository->saveEntry($entry);
        }

        $this->draftRepository->saveDraft($draft);

        return $draft;
    }

    public function createGlobalSetDraft(GlobalSetModel $globalSet, $language)
    {
        $draft = $this->globalSetDraftRepository->makeNewDraft();
        $draft->name = sprintf('%s [%s]', $this->orderName, $language);
        $draft->id = $globalSet->id;
        $draft->locale = $language;
        $draft->getContent()->locale = $language;

        $post = $this->elementTranslator->toPostArray($globalSet);

        $draft->setContentFromPost($post);

        $this->globalSetDraftRepository->saveDraft($draft);

        return $draft;
    }
}
