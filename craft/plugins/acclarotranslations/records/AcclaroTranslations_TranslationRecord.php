<?php

namespace Craft;

class AcclaroTranslations_TranslationRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'acclarotranslations_translations';
    }

    protected function defineAttributes()
    {
        return array(
            'sourceLanguage' => array(AttributeType::String, 'required' => true),
            'targetLanguage' => array(AttributeType::String, 'required' => true),
            'source' => array(AttributeType::String, 'column' => ColumnType::Text),
            'target' => array(AttributeType::String, 'column' => ColumnType::Text),
        );
    }
}
