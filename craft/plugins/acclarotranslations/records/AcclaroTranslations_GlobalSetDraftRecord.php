<?php

namespace Craft;

class AcclaroTranslations_GlobalSetDraftRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'acclarotranslations_globalsetdrafts';
    }

    public function defineRelations()
    {
        return array(
            'globalSet' => array(static::BELONGS_TO, 'GlobalSetRecord', 'required' => true, 'onDelete' => static::CASCADE),
            'locale'  => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
        );
    }

    public function defineIndexes()
    {
        return array(
            array('columns' => array('globalSetId', 'locale')),
        );
    }

    protected function defineAttributes()
    {
        return array(
            'name' => array(AttributeType::String, 'required' => true),
            'locale' => array(AttributeType::Locale, 'required' => true),
            'data' => array(AttributeType::Mixed, 'required' => true, 'column' => ColumnType::MediumText),
        );
    }
}
