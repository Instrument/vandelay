<?php

namespace Craft;

class AcclaroTranslations_FileModel extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    protected function defineAttributes()
    {
        return array(
            'id' => array(AttributeType::Number),
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

    public function getStatusLabel()
    {
        switch ($this->status) {
            case 'new':
            case 'preview':
            case 'in progress':
                return 'Submitted to translator';
            case 'complete':
                return 'Ready to publish';
            case 'canceled':
                return 'Canceled';
            case 'published':
                return 'Published';
        }
    }

    public function getStatusColor()
    {
        switch ($this->status) {
            case 'new':
            case 'preview':
            case 'in progress':
                return 'orange';
            case 'complete':
                return 'blue';
            case 'canceled':
                return 'red';
            case 'published':
                return 'green';
            default:
                return '';
        }
    }
}
