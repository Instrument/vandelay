<?php

namespace Craft;

use Craft\AcclaroTranslations\Repository\TranslatorRepository;
use Craft\AcclaroTranslations\Repository\FileRepository;
use Craft\AcclaroTranslations\Repository\EntryRepository;
use Craft\AcclaroTranslations\Repository\UserRepository;
use Craft\AcclaroTranslations\UrlHelper;
use CApplication;

class AcclaroTranslations_OrderModel extends BaseElementModel
{
    public $deleteButton = true;

    protected $elementType = 'AcclaroTranslations_Order';

    protected $_elements = array();

    protected $_files;

    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Repository\TranslatorRepository
     */
    protected $translatorRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\FileRepository
     */
    protected $fileRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\EntryRepository
     */
    protected $entryRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\UserRepository
     */
    protected $userRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\UrlHelper
     */
    protected $urlHelper;

    /**
     * @param \CApplication|null                                              $craft
     * @param \Craft\AcclaroTranslations\Repository\TranslatorRepository|null $translatorRepository
     * @param \Craft\AcclaroTranslations\Repository\FileRepository|null       $fileRepository
     * @param \Craft\AcclaroTranslations\Repository\EntryRepository|null      $entryRepository
     * @param \Craft\AcclaroTranslations\Repository\UserRepository|null       $userRepository
     * @param \Craft\AcclaroTranslations\UrlHelper|null                       $urlHelper
     */
    public function __construct(
        $attributes = null,
        CApplication $craft = null,
        TranslatorRepository $translatorRepository = null,
        FileRepository $fileRepository = null,
        EntryRepository $entryRepository = null,
        UserRepository $userRepository = null,
        UrlHelper $urlHelper = null
    ) {
        parent::__construct($attributes);

        $this->craft = $craft ?: craft();

        $this->translatorRepository = $translatorRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(TranslatorRepository::class);

        $this->fileRepository = $fileRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(FileRepository::class);

        $this->entryRepository = $entryRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(EntryRepository::class);

        $this->userRepository = $userRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(UserRepository::class);

        $this->urlHelper = $urlHelper ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(UrlHelper::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), array(
            'translatorId' => array(AttributeType::Number),
            'ownerId' => array(AttributeType::Number),
            'sourceLanguage' => array(AttributeType::String, 'required' => true),
            'targetLanguages' => array(AttributeType::String, 'required' => true),
            'status' => array(AttributeType::Enum, 'values' => 'new,getting quote,needs approval,in preparation,in progress,complete,canceled,published', 'default' => 'new'),
            'requestedDueDate' => array(AttributeType::DateTime),
            'comments' => array(AttributeType::String, 'column' => ColumnType::Text),
            'service' => array(AttributeType::String),
            'activityLog' => array(AttributeType::String, 'column' => ColumnType::Text),
            'dateOrdered' => array(AttributeType::DateTime),
            'serviceOrderId' => array(AttributeType::String),
            'entriesCount' => array(AttributeType::Number),
            'wordCount' => array(AttributeType::Number),
            'elementIds' => array(AttributeType::String),
        ));
    }

    public function getElements()
    {
        $elementIds = $this->elementIds ? json_decode($this->elementIds) : array();

        $elements = array();

        foreach ($elementIds as $elementId) {
            if (!array_key_exists($elementId, $this->_elements)) {
                $this->_elements[$elementId] = $this->craft->getComponent('elements')->getElementById($elementId, null, $this->sourceLanguage);
            }

            if ($this->_elements[$elementId]) {
                $elements[] = $this->_elements[$elementId];
            }
        }

        return $elements;
    }

    public function getFiles()
    {
        if (is_null($this->_files)) {
            $this->_files = $this->fileRepository->getFilesByOrderId($this->id);
        }

        return $this->_files;
    }

    public function getTranslator()
    {
        return $this->translatorId ? $this->translatorRepository->getTranslatorById($this->translatorId) : null;
    }

    public function getOwner()
    {
        return $this->ownerId ? $this->userRepository->getUserById($this->ownerId) : null;
    }

    public function getTargetLanguagesArray()
    {
        return $this->targetLanguages ? json_decode($this->targetLanguages, true) : array();
    }

    public function getActivityLogArray()
    {
        $str = $this->activityLog;

        return $str ? json_decode($str, true) : array();
    }

    public function logActivity($message)
    {
        $activityLog = $this->getActivityLogArray();

        $activityLog[] = array(
            'date' => date('n/j/Y'),
            'message' => $message,
        );

        $this->activityLog = json_encode($activityLog);
    }

    public function getStatusLabel()
    {
        switch ($this->status) {
            case 'new':
                return 'Pending submission';
            case 'getting quote':
            case 'needs approval':
            case 'in preparation':
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

    /**
     * {@inheritdoc}
     */
    public function isEditable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCpEditUrl()
    {
        return $this->urlHelper->getCpUrl('acclarotranslations/orders/detail/'.$this->id);
    }
}
