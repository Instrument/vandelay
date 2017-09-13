<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\FieldModel;
use Craft\BaseFieldType;
use Craft\CategoriesFieldType;
use Craft\CheckboxesFieldType;
use Craft\DropdownFieldType;
use Craft\MatrixFieldType;
use Craft\MultiSelectFieldType;
use Craft\NumberFieldType;
use Craft\PlainTextFieldType;
use Craft\RadioButtonsFieldType;
use Craft\RichTextFieldType;
use Craft\TagsFieldType;
use Craft\TableFieldType;
use Craft\Seomatic_MetaFieldType;
use Craft\NeoFieldType;
use CApplication;
use Craft\AcclaroTranslations\ElementCloner;
use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\WordCounter;
use Craft\AcclaroTranslations\Repository\CategoryRepository;
use Craft\AcclaroTranslations\Repository\TagRepository;
use Craft\AcclaroTranslations\Repository\TranslationRepository;

class Factory
{
    private $nativeFieldTypes = array(
        CategoriesFieldType::class => CategoryFieldTranslator::class,
        CheckboxesFieldType::class => MultiOptionsFieldTranslator::class,
        DropdownFieldType::class => SingleOptionFieldTranslator::class,
        MatrixFieldType::class => MatrixFieldTranslator::class,
        MultiSelectFieldType::class => MultiOptionsFieldTranslator::class,
        NumberFieldType::class => GenericFieldTranslator::class,
        PlainTextFieldType::class => GenericFieldTranslator::class,
        RadioButtonsFieldType::class => SingleOptionFieldTranslator::class,
        RichTextFieldType::class => RichTextFieldTranslator::class,
        TableFieldType::class => TableFieldTranslator::class,
        TagsFieldType::class => TagFieldTranslator::class,
        NeoFieldType::class => NeoFieldTranslator::class,
        Seomatic_MetaFieldType::class => SeomaticMetaFieldTranslator::class,
    );

    public function __construct(
        CApplication $craft,
        ElementCloner $elementCloner,
        TranslationRepository $translationRepository,
        CategoryRepository $categoryRepository,
        TagRepository $tagRepository,
        WordCounter $wordCounter
    ) {
        $this->craft = $craft;

        $this->elementCloner = $elementCloner;

        $this->translationRepository = $translationRepository;

        $this->categoryRepository = $categoryRepository;

        $this->tagRepository = $tagRepository;

        $this->wordCounter = $wordCounter;
    }

    public function makeTranslator(BaseFieldType $fieldType)
    {
        if ($fieldType instanceof TranslatableFieldInterface) {
            return $fieldType;
        }

        $class = get_class($fieldType);

        if (array_key_exists($class, $this->nativeFieldTypes)) {
            $translatorClass = $this->nativeFieldTypes[$class];

            switch($translatorClass) {
                case MultiOptionsFieldTranslator::class:
                    return new MultiOptionsFieldTranslator($this->craft, $this->wordCounter, $this->translationRepository);
                case SingleOptionFieldTranslator::class:
                    return new SingleOptionFieldTranslator($this->craft, $this->wordCounter, $this->translationRepository);
                case TagFieldTranslator::class:
                    return new TagFieldTranslator($this->craft, $this->wordCounter, $this->tagRepository, $this->elementCloner);
                case CategoryFieldTranslator::class:
                    return new CategoryFieldTranslator($this->craft, $this->wordCounter, $this->categoryRepository);
            }

            return new $translatorClass($this->craft, $this->wordCounter);
        }

        return null;
    }
}
