<?php

namespace Craft\AcclaroTranslations\Job;

use Craft\AcclaroTranslations_OrderModel;
use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Repository\FileRepository;
use Craft\AcclaroTranslations\TranslationService\Factory as TranslationServiceFactory;
use Craft\AcclaroTranslations\Job\Factory as JobFactory;
use CApplication;

class SyncOrder implements JobInterface
{
    /**
     * @var \Craft\AcclaroTranslations_OrderModel
     */
    protected $order;

    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Repository\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\FileRepository
     */
    protected $fileRepository;

    /**
     * @var \Craft\AcclaroTranslations\TranslationService\Factory
     */
    protected $translationServiceFactory;

    /**
     * @var \Craft\AcclaroTranslations\Job\Factory
     */
    protected $jobFactory;

    /**
     * @param \Craft\AcclaroTranslations_OrderModel                 $order
     * @param \CApplication                                         $craft
     * @param \Craft\AcclaroTranslations\Repository\OrderRepository $orderRepository
     * @param \Craft\AcclaroTranslations\Repository\FileRepository  $fileRepository
     * @param \Craft\AcclaroTranslations\TranslationService\Factory $translationServiceFactory
     * @param \Craft\AcclaroTranslations\Job\Factory                $jobFactory
     */
    public function __construct(
        AcclaroTranslations_OrderModel $order,
        CApplication $craft,
        OrderRepository $orderRepository,
        FileRepository $fileRepository,
        TranslationServiceFactory $translationServiceFactory,
        JobFactory $jobFactory
    ) {
        $this->order = $order;
        $this->craft = $craft;
        $this->orderRepository = $orderRepository;
        $this->fileRepository = $fileRepository;
        $this->translationServiceFactory = $translationServiceFactory;
        $this->jobFactory = $jobFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $translationService = $this->translationServiceFactory->makeTranslationService($this->order->translator->service, $this->order->translator->getSettings());

        $translationService->updateOrder($this->jobFactory, $this->order);

        $this->orderRepository->saveOrder($this->order);

        foreach ($this->order->files as $file) {
            $translationService->updateFile($this->jobFactory, $this->order, $file);

            $this->fileRepository->saveFile($file);
        }
    }
}
