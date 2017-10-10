<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\ElementCloner;
use Craft\AcclaroTranslations\WordCounter;
use Craft\AcclaroTranslations\Repository\TagRepository;
use Craft\BaseElementModel;
use Craft\TagModel;
use Craft\FieldModel;
use CApplication;

class TagFieldTranslator extends TaxonomyFieldTranslator
{
    /**
     * @var \Craft\AcclaroTranslations\Repository\TagRepository
     */
    protected $tagRepository;

    /**
     * @var \Craft\AcclaroTranslations\ElementCloner
     */
    protected $elementCloner;

    /**
     * @param \CApplication                                            $craft
     * @param \Craft\AcclaroTranslations\WordCounter                   $wordCounter
     * @param \Craft\AcclaroTranslations\Repository\TagRepository      $tagRepository
     * @param \Craft\AcclaroTranslations\FieldTranslator\ElementCloner $elementTranslator
     */
    public function __construct(
        CApplication $craft,
        WordCounter $wordCounter,
        TagRepository $tagRepository,
        ElementCloner $elementCloner
    ) {
        parent::__construct($craft, $wordCounter);

        $this->tagRepository = $tagRepository;

        $this->elementCloner = $elementCloner;
    }

    public function translateRelated(ElementTranslator $elementTranslator, BaseElementModel $element, TagModel $existingTag, $sourceLanguage, $targetLanguage, $fieldData)
    {
        $translatedTag = $this->tagRepository->find(array(
            'id' => $existingTag->id,
            'groupId' => $existingTag->groupId,
            'locale' => $targetLanguage,
        ));

        if ($translatedTag) {
            $tag = $translatedTag;
        } else {
            $tag = $this->elementCloner->cloneElement($existingTag);
        }

        $tag->getContent()->setAttribute('locale', $targetLanguage);

        if (isset($fieldData['title'])) {
            $tag->getContent()->setAttribute('title', $fieldData['title']);
        }

        if (isset($fieldData['slug'])) {
            $tag->setAttribute('slug', $fieldData['slug']);
        }

        $post = $elementTranslator->toPostArrayFromTranslationTarget($tag, $sourceLanguage, $targetLanguage, $fieldData);

        $tag->setContentFromPost($post);

        $this->tagRepository->saveTag($tag);

        return $tag->id;
    }
}
