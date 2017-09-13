<?php

namespace Craft\AcclaroTranslations;

use CApplication;
use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Repository\LanguageRepository;
use Craft\AcclaroTranslations\Translator;

class OrderSearchParams
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Repository\LanguageRepository
     */
    protected $languageRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Craft\AcclaroTranslations\Translator
     */
    protected $translator;

    /**
     * @param \CApplication|null $craft
     */
    public function __construct(
        CApplication $craft,
        LanguageRepository $languageRepository,
        OrderRepository $orderRepository,
        Translator $translator
    ) {
        $this->craft = $craft;

        $this->languageRepository = $languageRepository;

        $this->orderRepository = $orderRepository;

        $this->translator = $translator;
    }

    public function getParams()
    {
        $languages = $this->languageRepository->getLanguages();
        $statuses = array_map(array($this->translator, 'translate'), $this->orderRepository->getOrderStatuses());

        $query = $this->craft->getComponent('request')->getParam('criteria') ? $this->craft->getComponent('request')->getParam('criteria') : $this->craft->getComponent('request')->getQuery();

        $sourceLanguage = isset($query['sourceLanguage']) ? $query['sourceLanguage'] : null;
        $targetLanguage = isset($query['targetLanguage']) ? $query['targetLanguage'] : null;
        $startDate = isset($query['startDate']) ? $query['startDate'] : null;
        $endDate = isset($query['endDate']) ? $query['endDate'] : null;
        $status = isset($query['status']) ? $query['status'] : null;

        $params = array();

        if ($sourceLanguage && array_key_exists($sourceLanguage, $languages)) {
            $params['sourceLanguage'] = $sourceLanguage;
        }

        if ($targetLanguage && array_key_exists($targetLanguage, $languages)) {
            $params['targetLanguage'] = $targetLanguage;
        }

        if ($startDate && isset($startDate['date']) && preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $startDate['date'])) {
            $params['startDate'] = $startDate['date'];
        }

        if ($endDate && isset($endDate['date']) && preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $endDate['date'])) {
            $params['endDate'] = $endDate['date'];
        }

        if ($status && array_key_exists($status, $statuses)) {
            $params['status'] = $status;
        }

        return $params;
    }
}
