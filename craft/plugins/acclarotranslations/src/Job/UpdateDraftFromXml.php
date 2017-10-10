<?php

namespace Craft\AcclaroTranslations\Job;

use Craft\EntryDraftModel;
use Craft\AcclaroTranslations_GlobalSetDraftModel;
use Craft\BaseElementModel;
use Craft\AcclaroTranslations\Repository\DraftRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository;
use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\Job\Factory as JobFactory;
use CApplication;

class UpdateDraftFromXml implements JobInterface
{
    /**
     * @var \Craft\BaseElementModel
     */
    protected $element;

    /**
     * @var \Craft\EntryDraftModel|\Craft\AcclaroTranslations_GlobalSetDraftModel
     */
    protected $draft;

    /**
     * @var string
     */
    protected $sourceLanguage;

    /**
     * @var string
     */
    protected $targetLanguage;

    /**
     * @var string
     */
    protected $xml;

    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Repository\DraftRepository
     */
    protected $draftRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository
     */
    protected $globalSetDraftRepository;

    /**
     * @var \Craft\AcclaroTranslations\ElementTranslator
     */
    protected $elementTranslator;

    /**
     * @var \Craft\AcclaroTranslations\Job\Factory
     */
    protected $jobFactory;

    /**
     * @param \Craft\BaseElementModel                                               $element
     * @param \Craft\EntryDraftModel|\Craft\AcclaroTranslations_GlobalSetDraftModel $draft
     * @param string                                                                $xml
     * @param string                                                                $sourceLanguage
     * @param string                                                                $targetLanguage
     * @param \CApplication                                                         $craft
     * @param \Craft\AcclaroTranslations\Repository\DraftRepository                 $draftRepository
     * @param \Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository        $globalSetDraftRepository
     * @param \Craft\AcclaroTranslations\ElementTranslator                          $elementTranslator
     * @param \Craft\AcclaroTranslations\Job\Factory                                $jobFactory
     */
    public function __construct(
        BaseElementModel $element,
        $draft,
        $xml,
        $sourceLanguage,
        $targetLanguage,
        CApplication $craft,
        DraftRepository $draftRepository,
        GlobalSetDraftRepository $globalSetDraftRepository,
        ElementTranslator $elementTranslator,
        JobFactory $jobFactory
    ) {
        $this->element = $element;

        $this->draft = $draft;

        $this->xml = $xml;

        $this->sourceLanguage = $sourceLanguage;

        $this->targetLanguage = $targetLanguage;

        $this->craft = $craft;

        $this->draftRepository = $draftRepository;

        $this->globalSetDraftRepository = $globalSetDraftRepository;

        $this->elementTranslator = $elementTranslator;

        $this->jobFactory = $jobFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $targetData = $this->elementTranslator->getTargetDataFromXml($this->xml);

        if ($this->draft instanceof EntryDraftModel) {
            if (isset($targetData['title'])) {
                $this->draft->getContent()->setAttribute('title', $targetData['title']);
            }

            if (isset($targetData['slug'])) {
                $this->draft->setAttribute('slug', $targetData['slug']);
            }
        }

        $post = $this->elementTranslator->toPostArrayFromTranslationTarget($this->element, $this->sourceLanguage, $this->targetLanguage, $targetData);

        $this->draft->setContentFromPost($post);

        $this->draft->setAttribute('locale', $this->targetLanguage);

        // save the draft
        if ($this->draft instanceof EntryDraftModel) {
            $this->draftRepository->saveDraft($this->draft);
        } elseif ($this->draft instanceof AcclaroTranslations_GlobalSetDraftModel) {
            $this->globalSetDraftRepository->saveDraft($this->draft);
        }
    }
}
