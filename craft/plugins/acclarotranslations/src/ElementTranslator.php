<?php

namespace Craft\AcclaroTranslations;

use Craft\BaseElementModel;
use Craft\EntryModel;
use Craft\FieldModel;
use Craft\CategoryModel;
use Craft\TagModel;
use Craft\AcclaroTranslations\FieldTranslator\Factory as FieldTranslatorFactory;
use Craft\AcclaroTranslations\WordCounter;
use DOMDocument;
use CApplication;

class ElementTranslator
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
     * @var \Craft\AcclaroTranslations\FieldTranslator\Factory
     */
    protected $fieldTranslatorFactory;

    /**
     * @param \CApplication
     * @param \Craft\AcclaroTranslations\FieldTranslator\FactoryFieldTranslatorFactory $fieldTranslatorFactory
     */
    public function __construct(
        CApplication $craft,
        FieldTranslatorFactory $fieldTranslatorFactory,
        WordCounter $wordCounter
    ) {
        $this->craft = $craft;

        $this->fieldTranslatorFactory = $fieldTranslatorFactory;

        $this->wordCounter = $wordCounter;
    }

    public function toTranslationSource(BaseElementModel $element)
    {
        $source = array();

        if ($element instanceof EntryModel || $element instanceof TagModel || $element instanceof CategoryModel) {
            $source['title'] = $element->getContent()->getAttribute('title');
            $source['slug'] = $element->getAttribute('slug');
        }

        foreach($element->getFieldLayout()->getFields() as $layoutField) {
            $field = $layoutField->getField();

            $fieldSource = $this->fieldToTranslationSource($element, $field);

            $source = array_merge($source, $fieldSource);
        }

        return $source;
    }

    public function getTargetDataFromXml($xml)
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        $dom->loadXML($xml);

        $targetData = array();

        $contents = $dom->getElementsByTagName('content');

        foreach ($contents as $content) {
            $name = (string) $content->getAttribute('resname');
            $value = (string) $content->nodeValue;

            if (strpos($name, '.') !== false) {
                $parts = explode('.', $name);
                $container =& $targetData;

                while ($parts) {
                    $key = array_shift($parts);

                    if (!isset($container[$key])) {
                        $container[$key] = array();
                    }

                    $container =& $container[$key];
                }

                $container = $value;
            } else {
                $targetData[$name] = $value;
            }
        }

        return $targetData;
    }

    public function toPostArrayFromTranslationTarget(BaseElementModel $element, $sourceLanguage, $targetLanguage, $targetData, $includeNonTranslatable = false)
    {
        $post = array();

        foreach($element->getFieldLayout()->getFields() as $layoutField) {
            $field = $layoutField->getField();

            $fieldHandle = $field->getAttribute('handle');

            $fieldType = $field->getFieldType();

            $translator = $this->fieldTranslatorFactory->makeTranslator($fieldType);

            if (!$translator) {
                if ($includeNonTranslatable) {
                    $post[$fieldHandle] = $element->getContent()->getAttribute($fieldHandle);
                }

                continue;
            }

            if (isset($targetData[$fieldHandle])) {
                $fieldPost = $translator->toPostArrayFromTranslationTarget($this, $element, $field, $sourceLanguage, $targetLanguage, $targetData[$fieldHandle]);
            } else {
                $fieldPost = $translator->toPostArray($this, $element, $field);
            }

            if (!is_array($fieldPost)) {
                $fieldPost = array($fieldHandle => $fieldPost);
            }

            $post = array_merge($post, $fieldPost);
        }

        return $post;
    }

    public function toPostArray(BaseElementModel $element)
    {
        $source = array();

        foreach($element->getFieldLayout()->getFields() as $layoutField) {
            $field = $layoutField->getField();

            $fieldSource = $this->fieldToPostArray($element, $field);

            $source = array_merge($source, $fieldSource);
        }

        return $source;
    }

    public function getWordCount(BaseElementModel $element)
    {
        $wordCount = 0;

        if ($element instanceof EntryModel || $element instanceof TagModel || $element instanceof CategoryModel) {
            $wordCount += $this->wordCounter->getWordCount($element->getContent()->getAttribute('title'));
            $wordCount += $this->wordCounter->getWordCount($element->getAttribute('slug'));
        }

        foreach($element->getFieldLayout()->getFields() as $layoutField) {
            $field = $layoutField->getField();

            $wordCount += $this->getFieldWordCount($element, $field);
        }

        return $wordCount;
    }

    public function fieldToTranslationSource(BaseElementModel $element, FieldModel $field)
    {
        $fieldType = $field->getFieldType();

        $translator = $this->fieldTranslatorFactory->makeTranslator($fieldType);

        $fieldSource = array();

        if ($translator) {
            $fieldSource = $translator->toTranslationSource($this, $element, $field);

            if (!is_array($fieldSource)) {
                $fieldSource = array($field->getAttribute('handle') => $fieldSource);
            }
        }

        return $fieldSource;
    }

    public function fieldToPostArray(BaseElementModel $element, FieldModel $field)
    {
        $fieldType = $field->getFieldType();

        $fieldHandle = $field->getAttribute('handle');

        $translator = $this->fieldTranslatorFactory->makeTranslator($fieldType);

        $fieldSource = array();

        if ($translator) {
            $fieldSource = $translator->toPostArray($this, $element, $field);

            if (!is_array($fieldSource)) {
                $fieldSource = array($fieldHandle => $fieldSource);
            }
        } else {
            $fieldSource =  array($fieldHandle => $element->getAttribute($fieldHandle));
        }

        return $fieldSource;
    }

    public function getFieldWordCount(BaseElementModel $element, FieldModel $field)
    {
        $fieldType = $field->getFieldType();

        $fieldHandle = $field->getAttribute('handle');

        $translator = $this->fieldTranslatorFactory->makeTranslator($fieldType);

        return $translator ? $translator->getWordCount($this, $element, $field) : 0;
    }
}
