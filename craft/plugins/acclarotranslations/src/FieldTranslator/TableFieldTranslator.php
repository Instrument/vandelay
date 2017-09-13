<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\BaseElementModel;
use Craft\FieldModel;
use Craft\AcclaroTranslations\WordCounter;
use Craft\AcclaroTranslations\ElementTranslator;

class TableFieldTranslator extends GenericFieldTranslator
{
    /**
     * {@inheritdoc}
     */
    public function toTranslationSource(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $source = array();

        $rows = $element->getFieldValue($field->getAttribute('handle'));

        $settings = $field->getAttribute('settings');

        if ($rows) {
            foreach ($rows as $i => $row) {
                foreach ($settings['columns'] as $columnId => $column) {
                    $key = sprintf('%s.%s.%s', $field->getAttribute('handle'), $i, $column['handle']);

                    $source[$key] = isset($row[$columnId]) ? $row[$columnId] : '';
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

        $fieldData = $element->getFieldValue($fieldHandle);

        return $fieldData ? array($fieldHandle => $fieldData) : array();
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArrayFromTranslationTarget(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field, $sourceLanguage, $targetLanguage, $fieldData)
    {
        $fieldHandle = $field->getAttribute('handle');

        $settings = $field->getAttribute('settings');

        $post = $this->toPostArray($elementTranslator, $element, $field);

        foreach ($fieldData as $i => $row) {
            if (isset($post[$fieldHandle][$i])) {
                $postRow = array();

                foreach ($settings['columns'] as $columnId => $column) {
                    if (isset($row[$column['handle']])) {
                        $postRow[$columnId] = $row[$column['handle']];
                    }
                }

                $post[$fieldHandle][$i] = array_merge(
                    $post[$fieldHandle][$i],
                    $postRow
                );
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

        $rows = $element->getFieldValue($field->getAttribute('handle'));

        $settings = $field->getAttribute('settings');

        if ($rows) {
            foreach ($rows as $i => $row) {
                foreach ($settings['columns'] as $columnId => $column) {
                    $value = isset($row[$columnId]) ? $row[$columnId] : '';

                    $wordCount += $this->wordCounter->getWordCount($value);
                }
            }
        }

        return $wordCount;
    }
}