<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\BaseElementModel;
use Craft\FieldModel;
use Craft\MultiOptionsFieldData;
use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\WordCounter;
use Craft\AcclaroTranslations\Repository\TranslationRepository;
use CApplication;

class MultiOptionsFieldTranslator extends GenericFieldTranslator
{
    /**
     * @var  \Craft\AcclaroTranslations\Repository\TranslationRepository
     */
    protected $translationRepository;

    /**
     * @param \CApplication                                               $craft
     * @param \Craft\AcclaroTranslations\WordCounter                      $wordCounter
     * @param \Craft\AcclaroTranslations\Repository\TranslationRepository $translationRepository
     */
    public function __construct(
        CApplication $craft,
        WordCounter $wordCounter,
        TranslationRepository $translationRepository
    ) {
        parent::__construct($craft, $wordCounter);

        $this->translationRepository = $translationRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function toTranslationSource(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $source = array();

        $fieldData = $element->getFieldValue($field->getAttribute('handle'));

        if ($fieldData) {
            if ($fieldData instanceof MultiOptionsFieldData) {
                foreach ($fieldData->getOptions() as $option) {
                    if ($option->selected) {
                        $key = sprintf('%s.%s', $field->getAttribute('handle'), $option->value);

                        $source[$key] = $option->label;
                    }
                }
            } else {
                $settings = $field->getAttribute('settings');

                foreach ($settings['options'] as $option) {
                    if (in_array($option['value'], $fieldData, true)) {
                        $key = sprintf('%s.%s', $field->getAttribute('handle'), $option['value']);

                        $source[$key] = $option['label'];
                    }
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
        $fieldData = $element->getFieldValue($field->getAttribute('handle'));

        if ($fieldData instanceof MultiOptionsFieldData) {
            $fieldData = array_map(
                function ($option) {
                    return $option->value;
                },
                array_filter(
                    $fieldData->getOptions(),
                    function ($option) {
                        return $option->selected;
                    }
                )
            );
        }

        return array(
            $field->getAttribute('handle') => $fieldData ? $fieldData : '',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArrayFromTranslationTarget(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field, $sourceLanguage, $targetLanguage, $fieldData)
    {
        $fieldHandle = $field->getAttribute('handle');

        $values = array();

        foreach ($fieldData as $value => $target) {
            $source = null;

            //check if translation already exists
            foreach ($field->settings['options'] as $option) {
                if ($option['value'] === $value) {
                    $source = $option['label'];
                    break;
                }
            }

            if ($source) {
                $this->translationRepository->addTranslation(
                    $sourceLanguage,
                    $targetLanguage,
                    $source,
                    $target
                );

                $values[] = $value;
            }
        }

        return array($fieldHandle => $values);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldValue(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $fieldData = $element->getFieldValue($field->getAttribute('handle'));

        if ($fieldData instanceof MultiOptionsFieldData) {
            $fieldData = array_map(
                function ($option) {
                    return $option->value;
                },
                array_filter(
                    $fieldData->getOptions(),
                    function ($option) {
                        return $option->selected;
                    }
                )
            );
        }

        return $fieldData;
    }

    /**
     * {@inheritdoc}
     */
    public function getWordCount(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $value = $this->getFieldValue($elementTranslator, $element, $field);

        $wordCount = 0;

        foreach ((array) $value as $v) {
            $wordCount += $this->wordCounter->getWordCount($v);
        }

        return $wordCount;
    }
}
