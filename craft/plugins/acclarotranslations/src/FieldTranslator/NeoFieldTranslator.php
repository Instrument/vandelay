<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\AcclaroTranslations\ElementTranslator;
use Craft\BaseElementModel;
use Craft\FieldModel;
use Craft\Neo_BlockModel;

class NeoFieldTranslator extends GenericFieldTranslator
{
    /**
     * {@inheritdoc}
     */
    public function toTranslationSource(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $source = array();

        $blocks = $element->getFieldValue($field->getAttribute('handle'));

        if ($blocks) {
            foreach ($blocks->level(1) as $block) {
                $keyPrefix = sprintf('%s.%s', $field->getAttribute('handle'), $block->getAttribute('id'));

                $source = array_merge($source, $this->blockToTranslationSource($elementTranslator, $block, $keyPrefix));
            }
        }

        return $source;
    }

    public function blockToTranslationSource(ElementTranslator $elementTranslator, Neo_BlockModel $block, $keyPrefix = '')
    {
        $source = array();

        $blockSource = $elementTranslator->toTranslationSource($block);

        foreach ($blockSource as $key => $value) {
            $key = sprintf('%s.%s', $keyPrefix, $key);

            $source[$key] = $value;
        }

        foreach ($block->getChildren() as $childBlock) {
            $key = sprintf('%s.%s', $keyPrefix, $childBlock->getAttribute('id'));

            $childBlockSource = $this->blockToTranslationSource($elementTranslator, $childBlock, $key);

            foreach ($childBlockSource as $key => $value) {
                $source[$key] = $value;
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
                'modified' => '1',
                'type' => $block->getType()->handle,
                'enabled' => $block->enabled,
                'collapsed' => $block->collapsed,
                'level' => $block->level,
                'fields' => $elementTranslator->toPostArray($block),
            );
        }

        return $post;
    }

    protected function parseBlockData(&$allBlockData, $blockData)
    {
        $newBlockData = array();
        $newToParse = array();

        foreach ($blockData as $key => $value) {
            if (is_numeric($key)) {
                $newToParse[] = $value;
            } else {
                $newBlockData[$key] = $value;
            }
        }

        if ($newBlockData) {
            $allBlockData[] = $newBlockData;
        }

        foreach ($newToParse as $blockData) {
            $this->parseBlockData($allBlockData, $blockData);
        }
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

        $allBlockData = array();

        $this->parseBlockData($allBlockData, $fieldData);

        foreach ($blocks as $i => $block) {
            $blockData = isset($allBlockData[$i]) ? $allBlockData[$i] : array();

            $post[$fieldHandle]['new'.($i+1)] = array(
                'modified' => '1',
                'type' => $block->getType()->handle,
                'enabled' => $block->enabled,
                'collapsed' => $block->collapsed,
                'level' => $block->level,
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
