<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\AcclaroTranslations\ElementTranslator;
use Craft\BaseElementModel;
use Craft\FieldModel;

class MatrixFieldTranslator extends GenericFieldTranslator
{
    /**
     * {@inheritdoc}
     */
    public function toTranslationSource(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $source = array();

        $blocks = $element->getFieldValue($field->getAttribute('handle'));

        if ($blocks) {
            foreach ($blocks as $block) {
                $blockSource = $elementTranslator->toTranslationSource($block);

                foreach ($blockSource as $key => $value) {
                    $key = sprintf('%s.%s.%s', $field->getAttribute('handle'), $block->getAttribute('id'), $key);

                    $source[$key] = $value;
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

        $blocks = $element->getFieldValue($fieldHandle);

        if (!$blocks) {
            return '';
        }

        $post = array(
            $fieldHandle => array(),
        );

        foreach ($blocks as $i => $block) {
            $post[$fieldHandle]['new'.($i+1)] = array(
                'type' => $block->getType()->handle,
                'enabled' => $block->enabled,
                'fields' => $elementTranslator->toPostArray($block),
            );
        }
        return $post;
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArrayFromTranslationTarget(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field, $sourceLanguage, $targetLanguage, $fieldData)
    {
        $fieldHandle = $field->getAttribute('handle');

        $blocks = $element->getFieldValue($fieldHandle);

        $post = array(
            $fieldHandle => array(),
        );

        $fieldData = array_values($fieldData);

        foreach ($blocks as $i => $block) {
            $blockData = isset($fieldData[$i]) ? $fieldData[$i] : array();

            $post[$fieldHandle]['new'.($i+1)] = array(
                'type' => $block->getType()->handle,
                'enabled' => $block->enabled,
                'fields' => $elementTranslator->toPostArrayFromTranslationTarget($block, $sourceLanguage, $targetLanguage, $blockData, true),
            );
        }

        return $post;
    }

    /**
     * {@inheritdoc}
     */
    public function getWordCount(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $blocks = $this->getFieldValue($elementTranslator, $element, $field);

        if (!$blocks) {
            return 0;
        }

        $wordCount = 0;

        foreach ($blocks as $i => $block) {
            $wordCount += $elementTranslator->getWordCount($block);
        }

        return $wordCount;
    }
}
