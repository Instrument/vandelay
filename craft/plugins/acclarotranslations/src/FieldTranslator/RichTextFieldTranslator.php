<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\BaseElementModel;
use Craft\FieldModel;
use Craft\RichTextData;
use Craft\HandleValidator;
use Craft\AcclaroTranslations\ElementTranslator;

class RichTextFieldTranslator extends GenericFieldTranslator
{
    /**
     * {@inheritdoc}
     */
    public function getFieldValue(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $richText = $element->getFieldValue($field->getAttribute('handle'));

        return $richText instanceof RichTextData ? $richText->getRawContent() : (string) $richText;
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArrayFromTranslationTarget(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field, $sourceLanguage, $targetLanguage, $fieldData)
    {
        if (strpos($fieldData, '{') !== false)
        {
            // Preserve the ref tags with hashes {type:id:url} => {type:id:url}#type:id
            $fieldData = preg_replace_callback(
                '/(href=|src=)([\'"])(\{(\w+\:\d+\:'.HandleValidator::$handlePattern.')\})(#[^\'"#]+)?\2/',
                function ($matches) {
                    return $matches[1].$matches[2].$matches[3].(!empty($matches[5]) ? $matches[5] : '').'#'.$matches[4].$matches[2];
                },
                $fieldData
            );

            $fieldData = $this->craft->elements->parseRefs($fieldData);
        }

        return $fieldData;
    }

    /**
     * {@inheritdoc}
     */
    public function getWordCount(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $value = $this->getFieldValue($elementTranslator, $element, $field);

        return $this->wordCounter->getWordCount(strip_tags($value));
    }
}
