<?php

namespace Craft;

class AcclaroTranslations_OrderRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'acclarotranslations_orders';
    }

    protected function defineAttributes()
    {
        return array(
            'translatorId' => array(AttributeType::Number),
            'ownerId' => array(AttributeType::Number),
            'sourceLanguage' => array(AttributeType::String, 'required' => true),
            'targetLanguages' => array(AttributeType::String, 'required' => true),
            'status' => array(AttributeType::Enum, 'values' => 'new,getting quote,needs approval,in preparation,in progress,complete,canceled,published', 'default' => 'new'),
            'requestedDueDate' => array(AttributeType::DateTime),
            'comments' => array(AttributeType::String, 'column' => ColumnType::Text),
            'activityLog' => array(AttributeType::String, 'column' => ColumnType::Text),
            'dateOrdered' => array(AttributeType::DateTime),
            'serviceOrderId' => array(AttributeType::String),
            'entriesCount' => array(AttributeType::Number),
            'wordCount' => array(AttributeType::Number),
            'elementIds' => array(AttributeType::String),
        );
    }

    public function defineRelations()
    {
        return array(
            'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
            'owner' => array(static::BELONGS_TO, 'UserRecord', 'ownerId'),
            'translator' => array(static::BELONGS_TO, 'AcclaroTranslations_TranslatorRecord', 'translatorId', 'onDelete' => static::SET_NULL),
            'files' => array(static::HAS_MANY, 'AcclaroTranslations_FileRecord', 'orderId'),
        );
    }
}
