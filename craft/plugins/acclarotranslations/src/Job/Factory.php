<?php

namespace Craft\AcclaroTranslations\Job;

use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\Repository\DraftRepository;
use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Repository\FileRepository;
use Craft\AcclaroTranslations\Repository\EntryRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository;
use Craft\AcclaroTranslations\TranslationService\Factory as TranslationServiceFactory;
use CApplication;
use ReflectionClass;

class Factory
{
    public function __construct(
        CApplication $craft,
        ElementTranslator $elementTranslator,
        DraftRepository $draftRepository,
        OrderRepository $orderRepository,
        FileRepository $fileRepository,
        EntryRepository $entryRepository,
        GlobalSetRepository $globalSetRepository,
        GlobalSetDraftRepository $globalSetDraftRepository,
        TranslationServiceFactory $translationServiceFactory
    ) {
        $this->craft = $craft;
        $this->elementTranslator = $elementTranslator;
        $this->draftRepository = $draftRepository;
        $this->orderRepository = $orderRepository;
        $this->fileRepository = $fileRepository;
        $this->entryRepository = $entryRepository;
        $this->globalSetRepository = $globalSetRepository;
        $this->globalSetDraftRepository = $globalSetDraftRepository;
        $this->translationServiceFactory = $translationServiceFactory;
    }

    public function makeJob($class)
    {
        $args = array_slice(func_get_args(), 1);

        switch ($class) {
            case UpdateDraftFromXml::class:
                $args[] = $this->craft;
                $args[] = $this->draftRepository;
                $args[] = $this->globalSetDraftRepository;
                $args[] = $this->elementTranslator;
                $args[] = $this;
                break;
            case SyncOrders::class:
                $args[] = $this->craft;
                $args[] = $this->orderRepository;
                $args[] = $this;
                break;
            case SyncOrder::class:
                $args[] = $this->craft;
                $args[] = $this->orderRepository;
                $args[] = $this->fileRepository;
                $args[] = $this->translationServiceFactory;
                $args[] = $this;
                break;
            case SendOrderToTranslationService::class:
                $args[] = $this->craft;
                $args[] = $this->orderRepository;
                $args[] = $this->fileRepository;
                $args[] = $this->translationServiceFactory;
                break;
            case CreateOrderTranslationDrafts::class:
                $args[] = $this->craft;
                $args[] = $this->draftRepository;
                $args[] = $this->entryRepository;
                $args[] = $this->globalSetDraftRepository;
                $args[] = $this->elementTranslator;
                break;
        }

        $reflectionClass = new ReflectionClass($class);

        return $reflectionClass->newInstanceArgs($args);
    }

    public function dispatchJob($class)
    {
        $job = call_user_func_array(array($this, 'makeJob'), func_get_args());

        return $job->handle();
    }
}
