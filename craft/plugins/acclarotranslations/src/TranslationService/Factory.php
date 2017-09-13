<?php

namespace Craft\AcclaroTranslations\TranslationService;

use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\UrlGenerator;
use Craft\AcclaroTranslations\Translator;
use Craft\AcclaroTranslations\ApiClient\AcclaroApiClient;
use Craft\AcclaroTranslations\Repository\DraftRepository;
use Craft\AcclaroTranslations\Repository\EntryRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetRepository;
use Craft\AcclaroTranslations\Repository\LanguageRepository;
use Exception;
use CApplication;

class Factory
{
    protected $translationServices = array(
        'acclaro' => 'Acclaro',
    );

    public function __construct(
        CApplication $craft,
        ElementTranslator $elementTranslator,
        DraftRepository $draftRepository,
        EntryRepository $entryRepository,
        GlobalSetRepository $globalSetRepository,
        GlobalSetDraftRepository $globalSetDraftRepository,
        LanguageRepository $languageRepository,
        UrlGenerator $urlGenerator,
        Translator $translator
    ) {
        $this->craft = $craft;
        $this->elementTranslator = $elementTranslator;
        $this->draftRepository = $draftRepository;
        $this->entryRepository = $entryRepository;
        $this->globalSetRepository = $globalSetRepository;
        $this->globalSetDraftRepository = $globalSetDraftRepository;
        $this->languageRepository = $languageRepository;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }

    public function getTranslationServiceNames()
    {
        return $this->translationServices;
    }

    public function makeTranslationService($serviceHandle, $settings)
    {
        if (!array_key_exists($serviceHandle, $this->translationServices)) {
            throw new Exception('Invalid translation service.');
        }

        $class = sprintf(
            '%s\\%sTranslationService',
            __NAMESPACE__,
            ucfirst($serviceHandle)
        );

        switch ($class) {
            case AcclaroTranslationService::class:
                return new AcclaroTranslationService(
                    $settings,
                    $this->craft,
                    $this->elementTranslator,
                    $this->draftRepository,
                    $this->entryRepository,
                    $this->globalSetRepository,
                    $this->globalSetDraftRepository,
                    $this->languageRepository,
                    $this->urlGenerator,
                    $this->translator,
                    new AcclaroApiClient(
                        $settings['apiToken'],
                        !empty($settings['sandboxMode'])
                    )
                );
        }

        $class = '\\'.$class;

        return new $class($settings, $this->craft);
    }
}
