<?php

namespace Craft;

class AcclaroTranslations_TranslatorRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'acclarotranslations_translators';
    }

    protected function defineAttributes()
    {
        return array(
            'label' => array(AttributeType::String),
            'service' => array(AttributeType::String),
            'languages' => array(AttributeType::String, 'column' => ColumnType::Text, 'required' => true),
            'status' => array(AttributeType::Enum, 'values' => 'active,inactive', 'default' => 'inactive'),
            'settings' => array(AttributeType::String, 'column' => ColumnType::Text),
        );
    }
}
