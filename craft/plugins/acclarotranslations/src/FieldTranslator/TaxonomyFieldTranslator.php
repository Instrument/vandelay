<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\AcclaroTranslations\ElementTranslator;
use Craft\BaseElementModel;
use Craft\FieldModel;
use CApplication;

class TaxonomyFieldTranslator extends GenericFieldTranslator
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @param \CApplication
     */
    public function __construct(
        CApplication $craft
    ) {
        $this->craft = $craft;
    }

    /**
     * {@inheritdoc}
     */
    public function toTranslationSource(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $source = array();

        $relations = $element->getFieldValue($field->getAttribute('handle'));

        if ($relations) {
            foreach ($relations as $i => $relation) {
                foreach ($elementTranslator->toTranslationSource($relation) as $childKey => $childValue) {
                    $key = sprintf('%s.%s.%s', $field->getAttribute('handle'), $i, $childKey);

                    $source[$key] = $childValue;
                }
            }
        }

        return $source;
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArray(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $fieldHandle = $field->getAttribute('handle');

        $relations = $element->getFieldValue($fieldHandle);

        if (!$relations) {
            return '';
        }

        $post = array(
            $fieldHandle => array(),
        );

        foreach ($relations as $i => $relation) {
            $post[$fieldHandle][] = $relation->id;
        }

        return $post;
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArrayFromTranslationTarget(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field, $sourceLanguage, $targetLanguage, $fieldData)
    {
        $fieldHandle = $field->getAttribute('handle');

        $relations = $element->getFieldValue($fieldHandle);

        if (!$relations) {
            return array();
        }

        $post = $this->toPostArray($elementTranslator, $element, $field);

        $fieldData = array_values($fieldData);

        foreach ($relations as $i => $related) {
            if (isset($fieldData[$i])) {
                $post[$fieldHandle][$i] = $this->translateRelated($elementTranslator, $element, $related, $sourceLanguage, $targetLanguage, $fieldData[$i]);
            }
        }

        return $post;
    }

    /**
     * {@inheritdoc}
     */
    public function getWordCount(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $wordCount = 0;

        $relations = $element->getFieldValue($field->getAttribute('handle'));

        if ($relations) {
            foreach ($relations as $i => $relation) {
                $wordCount += $elementTranslator->getWordCount($relation);
            }
        }

        return $wordCount;
    }
}
