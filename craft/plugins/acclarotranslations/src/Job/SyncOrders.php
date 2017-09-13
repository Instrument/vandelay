<?php

namespace Craft\AcclaroTranslations\Job;

use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Job\Factory as JobFactory;
use CApplication;

class SyncOrders implements JobInterface
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Repository\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Craft\AcclaroTranslations\Job\Factory
     */
    protected $jobFactory;

    /**
     * @param \CApplication                                         $craft
     * @param \Craft\AcclaroTranslations\Repository\OrderRepository $orderRepository
     * @param \Craft\AcclaroTranslations\Job\Factory                $jobFactory
     */
    public function __construct(
        CApplication $craft,
        OrderRepository $orderRepository,
        JobFactory $jobFactory
    ) {
        $this->craft = $craft;
        $this->orderRepository = $orderRepository;
        $this->jobFactory = $jobFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $orders = $this->orderRepository->getInProgressOrders();

        foreach ($orders as $order) {
            $this->jobFactory->dispatchJob(SyncOrder::class, $order);
        }
    }
}
