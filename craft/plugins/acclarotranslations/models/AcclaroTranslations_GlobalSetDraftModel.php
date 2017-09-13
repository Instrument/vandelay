<?php

namespace Craft;

use Craft\AcclaroTranslations\Repository\GlobalSetRepository;
use Craft\AcclaroTranslations\UrlHelper;
use CModel;

class AcclaroTranslations_GlobalSetDraftModel extends GlobalSetModel
{
    protected $_globalSet;

    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Repository\GlobalSetRepository
     */
    protected $globalSetRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\UrlHelper
     */
    protected $urlHelper;

    /**
     * @param array|null                                                     $attributes
     * @param \CApplication|null                                             $craft
     * @param \Craft\AcclaroTranslations\Repository\GlobalSetRepository|null $globalSetRepository
     * @param \Craft\AcclaroTranslations\UrlHelper|null                      $urlHelper
     */
    public function __construct(
        $attributes = null,
        CApplication $craft = null,
        GlobalSetRepository $globalSetRepository = null,
        UrlHelper $urlHelper = null
    ) {
        $this->craft = $craft ?: craft();

        $this->globalSetRepository = $globalSetRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(GlobalSetRepository::class);

        $this->urlHelper = $urlHelper ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(UrlHelper::class);

        parent::__construct($attributes);
    }

    public function getFieldLayout()
    {
        return $this->getGlobalSet()->getFieldLayout();
    }

    public function getHandle()
    {
        return $this->getGlobalSet()->getAttribute('handle');
    }

    public static function populateModel($attributes)
    {
        if ($attributes instanceof CModel) {
            $attributes = $attributes->getAttributes();
        }

        $globalSetData = $attributes['data'];
        $fieldContent = isset($globalSetData['fields']) ? $globalSetData['fields'] : null;
        $attributes['draftId'] = $attributes['id'];
        $attributes['id'] = $attributes['globalSetId'];

        $attributes = array_diff_key($attributes, array_flip(array('data', 'fields', 'globalSetId')));

        $attributes = array_merge($attributes, $globalSetData);

        $draft = parent::populateModel($attributes);

        if ($fieldContent) {
            $post = array();

            foreach ($fieldContent as $fieldId => $fieldValue) {
                $field = $draft->craft->fields->getFieldById($fieldId);

                if ($field) {
                    $post[$field->getAttribute('handle')] = $fieldValue;
                }
            }

            $draft->setContentFromPost($post);
        }

        return $draft;
    }

    protected function defineAttributes()
    {
        return array(
            'id' => AttributeType::Number,
            'draftId' => AttributeType::Number,
            'name' => array(AttributeType::String, 'required' => true),
            'locale'  => array(AttributeType::Locale, 'default' => $this->craft->getComponent('i18n')->getPrimarySiteLocaleId()),
            'data' => array(AttributeType::Mixed, 'required' => true, 'column' => ColumnType::MediumText),
            'dateCreated' => AttributeType::DateTime,
            'dateUpdated' => AttributeType::DateTime,
            /* element attributes */
            'enabled' => array(AttributeType::Bool, 'default' => true),
            'archived' => array(AttributeType::Bool, 'default' => false),
            'slug' => array(AttributeType::String),
            'uri' => array(AttributeType::String),
            'localeEnabled' => array(AttributeType::Bool, 'default' => true),
        );
    }

    public function getGlobalSet()
    {
        if (is_null($this->_globalSet)) {
            $this->_globalSet = $this->globalSetRepository->getSetById($this->id);
        }

        return $this->_globalSet;
    }

    public function getUrl()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCpEditUrl()
    {
        $globalSet = $this->getGlobalSet();

        $path = 'acclarotranslations/globals/'.$globalSet->handle.'/drafts/'.$this->draftId;

        return $this->urlHelper->getCpUrl($path);
    }
}
