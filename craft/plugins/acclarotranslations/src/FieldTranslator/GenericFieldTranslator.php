<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\BaseElementModel;
use Craft\FieldModel;
use Craft\AcclaroTranslations\WordCounter;
use Craft\AcclaroTranslations\ElementTranslator;
use CApplication;

class GenericFieldTranslator implements TranslatableFieldInterface
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\WordCounter
     */
    protected $wordCounter;

    /**
     * @param \CApplication                          $craft
     * @param \Craft\AcclaroTranslations\WordCounter $wordCounter
     */
    public function __construct(
        CApplication $craft,
        WordCounter $wordCounter
    ) {
        $this->craft = $craft;

        $this->wordCounter = $wordCounter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldValue(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        return $element->getFieldValue($field->getAttribute('handle'));
    }

    /**
     * {@inheritdoc}
     */
    public function toTranslationSource(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        return $this->getFieldValue($elementTranslator, $element, $field);
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArrayFromTranslationTarget(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field, $sourceLanguage, $targetLanguage, $fieldData)
    {
        return $fieldData;
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArray(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        return $this->getFieldValue($elementTranslator, $element, $field);
    }

    /**
     * {@inheritdoc}
     */
    public function getWordCount(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        return $this->wordCounter->getWordCount($this->getFieldValue($elementTranslator, $element, $field));
    }
}
