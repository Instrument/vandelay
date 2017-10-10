<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\AcclaroTranslations\ElementTranslator;
use Craft\BaseElementModel;
use Craft\FieldModel;

interface TranslatableFieldInterface
{
    /**
     * Extract element field data suitable for a translation source document
     *
     * a scalar value
     * - OR -
     * array of key => value pairs
     *
     * @param  \Craft\AcclaroTranslations\ElementTranslator $elementTranslator
     * @param  \Craft\BaseElementModel                      $element
     * @param  \Craft\FieldModel                            $field
     * @return string|bool|int|float|array
     */
    public function toTranslationSource(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field);

    /**
     * Update an element with data from translation target document
     *
     * @param  \Craft\AcclaroTranslations\ElementTranslator $elementTranslator
     * @param  \Craft\BaseElementModel                      $element
     * @param  \Craft\FieldModel                            $field
     * @param  string                                       $sourceLanguage
     * @param  string                                       $targetLanguage
     * @param  mixed                                        $fieldData
     * @return string|bool|int|float|array
     */
    public function toPostArrayFromTranslationTarget(
        ElementTranslator $elementTranslator,
        BaseElementModel $element,
        FieldModel $field,
        $sourceLanguage,
        $targetLanguage,
        $fieldData
    );

    /**
     * Extract element field value as it would appear in POST array
     *
     * a scalar value
     * - OR -
     * array of key => value pairs
     *
     * @param  \Craft\AcclaroTranslations\ElementTranslator $elementTranslator
     * @param  \Craft\BaseElementModel                      $element
     * @param  \Craft\FieldModel                            $field
     * @return string|bool|int|float|array
     */
    public function toPostArray(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field);

    /**
     * Get word count of an element field
     *
     * @param  \Craft\AcclaroTranslations\ElementTranslator $elementTranslator
     * @param  \Craft\BaseElementModel                      $element
     * @param  \Craft\FieldModel                            $field
     * @return int
     */
    public function getWordCount(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field);
}
