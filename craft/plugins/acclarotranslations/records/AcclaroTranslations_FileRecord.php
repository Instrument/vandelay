<?php

namespace Craft;

class AcclaroTranslations_FileRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'acclarotranslations_files';
    }

    protected function defineAttributes()
    {
        return array(
            'orderId' => array(AttributeType::Number),
            'elementId' => array(AttributeType::Number),
            'draftId' => array(AttributeType::Number),
            'sourceLanguage' => array(AttributeType::String, 'required' => true),
            'targetLanguage' => array(AttributeType::String, 'required' => true),
            'status' => array(AttributeType::Enum, 'values' => 'new,in progress,preview,complete,canceled,published', 'default' => 'new'),
            'wordCount' => array(AttributeType::Number),
            'source' => array(AttributeType::String, 'column' => ColumnType::LongText),
            'target' => array(AttributeType::String, 'column' => ColumnType::LongText),
            'previewUrl' => array(AttributeType::String),
            'serviceFileId' => array(AttributeType::String),
        );
    }

    public function defineRelations()
    {
        return array(
            'order' => array(static::BELONGS_TO, 'AcclaroTranslations_OrderRecord', 'orderId', 'required' => true, 'onDelete' => static::CASCADE),
            'element' => array(static::BELONGS_TO, 'ElementRecord', 'elementId', 'required' => true, 'onDelete' => static::CASCADE),
        );
    }
}
