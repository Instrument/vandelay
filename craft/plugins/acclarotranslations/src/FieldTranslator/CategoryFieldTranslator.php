<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\WordCounter;
use Craft\AcclaroTranslations\Repository\CategoryRepository;
use Craft\BaseElementModel;
use Craft\CategoryModel;
use Craft\FieldModel;
use CApplication;

class CategoryFieldTranslator extends TaxonomyFieldTranslator
{
    /**
     * @var \Craft\AcclaroTranslations\Repository\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @param \CApplication                                            $craft
     * @param \Craft\AcclaroTranslations\WordCounter                   $wordCounter
     * @param \Craft\AcclaroTranslations\Repository\CategoryRepository $categoryRepository
     */
    public function __construct(
        CApplication $craft,
        WordCounter $wordCounter,
        CategoryRepository $categoryRepository
    ) {
        parent::__construct($craft, $wordCounter);

        $this->categoryRepository = $categoryRepository;
    }

    public function translateRelated(ElementTranslator $elementTranslator, BaseElementModel $element, CategoryModel $category, $sourceLanguage, $targetLanguage, $fieldData)
    {
        // search for existing translated category in the same group
        $translatedCategory = $this->categoryRepository->find(array(
            'slug' => $fieldData['slug'],
            'groupId' => $category->groupId,
            'locale' => $targetLanguage,
        ));

        if ($translatedCategory) {
            return $translatedCategory->id;
        }

        $translatedCategory = $this->categoryRepository->find(array(
            'id' => $category->id,
            'groupId' => $category->groupId,
            'locale' => $targetLanguage,
        ));

        if ($translatedCategory) {
            $category = $translatedCategory;
        }

        $category->getContent()->setAttribute('locale', $targetLanguage);

        if (isset($fieldData['title'])) {
            $category->getContent()->setAttribute('title', $fieldData['title']);
        }

        if (isset($fieldData['slug'])) {
            $category->setAttribute('slug', $fieldData['slug']);
        }

        $post = $elementTranslator->toPostArrayFromTranslationTarget($category, $sourceLanguage, $targetLanguage, $fieldData);

        $category->setContentFromPost($post);

        $this->categoryRepository->saveCategory($category);

        return $category->id;
    }
}
