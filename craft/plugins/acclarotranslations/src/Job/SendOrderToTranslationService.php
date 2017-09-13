<?php

namespace Craft\AcclaroTranslations\Job;

use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Repository\FileRepository;
use Craft\AcclaroTranslations\TranslationService\Factory as TranslationServiceFactory;
use Craft\AcclaroTranslations_OrderModel;
use CApplication;
use DateTime;

class SendOrderToTranslationService implements JobInterface
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
     * @param \Craft\AcclaroTranslations_OrderModel                 $order
     * @param \CApplication                                         $craft
     * @param \Craft\AcclaroTranslations\Repository\OrderRepository $orderRepository
     * @param \Craft\AcclaroTranslations\Repository\FileRepository  $fileRepository
     * @param \Craft\AcclaroTranslations\TranslationService\Factory $translationServiceFactory
     */
    public function __construct(
        AcclaroTranslations_OrderModel $order,
        CApplication $craft,
        OrderRepository $orderRepository,
        FileRepository $fileRepository,
        TranslationServiceFactory $translationServiceFactory
    ) {
        $this->order = $order;

        $this->craft = $craft;

        $this->orderRepository = $orderRepository;

        $this->fileRepository = $fileRepository;

        $this->translationServiceFactory = $translationServiceFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $translator = $this->order->getTranslator();

        $translationService = $this->translationServiceFactory->makeTranslationService($translator->service, $translator->getSettings());

        $translationService->sendOrder($this->order);

        $this->order->dateOrdered = new DateTime();

        $this->orderRepository->saveOrder($this->order);

        foreach ($this->order->files as $file) {
            $this->fileRepository->saveFile($file);
        }
    }
}
