<?php

namespace Craft\AcclaroTranslations\FieldTranslator;

use Craft\BaseElementModel;
use Craft\FieldModel;
use Craft\AcclaroTranslations\ElementTranslator;

class SeomaticMetaFieldTranslator extends GenericFieldTranslator
{
    private $translatableAttributes = array('seoTitle', 'seoDescription', 'seoKeywords');

    /**
     * {@inheritdoc}
     */
    public function toTranslationSource(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field)
    {
        $source = array();

        $meta = $element->getFieldValue($field->getAttribute('handle'));

        if ($meta) {
            foreach ($this->translatableAttributes as $attribute) {
                $value = $meta->getAttribute($attribute);

                if ($value) {
                    $key = sprintf('%s.%s', $field->getAttribute('handle'), $attribute);

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

        $meta = $element->getFieldValue($fieldHandle);

        return array(
            $fieldHandle => array(
                'seoMainEntityCategory' => $meta ? $meta->getAttribute('seoMainEntityCategory') : 'CreativeWork',
                'seoMainEntityOfPage' => $meta ? $meta->getAttribute('seoMainEntityOfPage') : 'WebPage',
                'seoTitleSource' => 'custom',
                'seoTitleSourceField' => 'title',
                'seoTitleUnparsed' => $meta ? $meta->getAttribute('seoTitle') : '',
                'seoDescriptionSource' => 'custom',
                'seoDescriptionSourceField' => 'title',
                'seoDescriptionUnparsed' => $meta ? $meta->getAttribute('seoDescription') : '',
                'seoKeywordsSource' => 'custom',
                'seoKeywordsSourceField' => 'title',
                'seoKeywordsUnparsed' => $meta ? $meta->getAttribute('seoKeywords') : '',
                'seoImageIdSource' => 'custom',
                'seoImageId' => $meta ? $meta->getAttribute('seoImageId') : '',
                'seoImageTransform' => $meta ? $meta->getAttribute('seoImageTransform') : '',
                'twitterCardType' => $meta ? $meta->getAttribute('twitterCardType') : '',
                'seoTwitterImageIdSource' => 'custom',
                'seoTwitterImageId' => $meta ? $meta->getAttribute('seoTwitterImageId') : '',
                'seoTwitterImageTransform' => $meta ? $meta->getAttribute('seoTwitterImageTransform') : '',
                'openGraphType' => $meta ? $meta->getAttribute('openGraphType') : '',
                'seoFacebookImageIdSource' => 'custom',
                'seoFacebookImageId' => $meta ? $meta->getAttribute('seoFacebookImageId') : '',
                'seoFacebookImageTransform' => $meta ? $meta->getAttribute('seoFacebookImageTransform') : '',
                'robots' => $meta ? $meta->getAttribute('robots') : '',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArrayFromTranslationTarget(ElementTranslator $elementTranslator, BaseElementModel $element, FieldModel $field, $sourceLanguage, $targetLanguage, $fieldData)
    {
        $fieldHandle = $field->getAttribute('handle');

        $post = $this->toPostArray($elementTranslator, $element, $field);

        foreach ($this->translatableAttributes as $attribute) {
            if (isset($fieldData[$attribute])) {
                $post[$fieldHandle][$attribute] = $fieldData[$attribute];
                $post[$fieldHandle][$attribute.'Unparsed'] = $fieldData[$attribute];
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

        $meta = $element->getFieldValue($field->getAttribute('handle'));

        $attributes = array('seoTitle', 'seoDescription', 'seoKeywords');

        if ($meta) {
            foreach ($attributes as $attribute) {
                $value = $meta->getAttribute($attribute);

                $wordCount += $this->wordCounter->getWordCount($value);
            }
        }

        return $wordCount;
    }
}
