<?php

namespace Craft;

class AcclaroTranslations_TranslationModel extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    protected function defineAttributes()
    {
        return array(
            'id' => array(AttributeType::Number),
            'sourceLanguage' => array(AttributeType::String, 'required' => true),
            'targetLanguage' => array(AttributeType::String, 'required' => true),
            'source' => array(AttributeType::String, 'column' => ColumnType::Text),
            'target' => array(AttributeType::String, 'column' => ColumnType::Text),
        );
    }
}
