<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\BaseElementModel;
use Craft\FieldModel;
use Craft\SingleOptionFieldData;
use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\WordCounter;
use Craft\AcclaroTranslations\Repository\TranslationRepository;
use CApplication;

class SingleOptionFieldTranslator extends GenericFieldTranslator
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
        $fieldData = $element->getFieldValue($field->getAttribute('handle'));

        if ($fieldData instanceof SingleOptionFieldData) {
            if ($fieldData->selected) {
                $key = sprintf('%s.%s', $field->getAttribute('handle'), $fieldData->value);

                return array($key => $fieldData->label);
            }

            return array();
        }

        $settings = $field->getAttribute('settings');

        foreach ($settings['options'] as $option) {
            if ($option['value'] === $fieldData) {
                $key = sprintf('%s.%s', $field->getAttribute('handle'), $option['value']);

                return array($key => $option['label']);
            }
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArrayFromTranslationTarget(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field, $sourceLanguage, $targetLanguage, $fieldData)
    {
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

                return $value;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldValue(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $fieldData = $element->getFieldValue($field->getAttribute('handle'));

        if ($fieldData instanceof SingleOptionFieldData) {
            $fieldData = $fieldData->selected ? $fieldData->value : '';
        }

        return $fieldData;
    }
}
