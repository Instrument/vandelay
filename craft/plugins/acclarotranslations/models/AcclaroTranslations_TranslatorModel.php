<?php

namespace Craft;

use Craft\AcclaroTranslations\Repository\TranslatorRepository;
use CApplication;

class AcclaroTranslations_TranslatorModel extends BaseModel
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Repository\TranslatorRepository
     */
    protected $translatorRepository;

    /**
     * @param \CApplication|null                                              $craft
     * @param \Craft\AcclaroTranslations\Repository\TranslatorRepository|null $translatorRepository
     */
    public function __construct(
        $attributes = null,
        CApplication $craft = null,
        TranslatorRepository $translatorRepository = null
    ) {
        parent::__construct($attributes);

        $this->craft = $craft ?: craft();

        $this->translatorRepository = $translatorRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(TranslatorRepository::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function defineAttributes()
    {
        return array(
            'id' => array(AttributeType::Number),
            'label' => array(AttributeType::String),
            'service' => array(AttributeType::String),
            'languages' => array(AttributeType::String, 'column' => ColumnType::Text, 'required' => true),
            'status' => array(AttributeType::Enum, 'values' => 'active,inactive', 'default' => 'inactive'),
            'settings' => array(AttributeType::String, 'column' => ColumnType::Text),
        );
    }

    public function getName()
    {
        return $this->label ? $this->label : $this->translatorRepository->getTranslatorServiceLabel($this->service);
    }

    public function getLanguagesArray()
    {
        return $this->languages ? json_decode($this->languages, true) : array();
    }

    public function getSettings()
    {
        return $this->settings ? json_decode($this->settings, true) : array();
    }

    public function getSetting($setting)
    {
        $settings = $this->getSettings();

        return isset($settings[$setting]) ? $settings[$setting] : null;
    }
}
