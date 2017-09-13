<?php

namespace Craft\AcclaroTranslations\TranslationService;

use Craft\AcclaroTranslations_OrderModel;
use Craft\AcclaroTranslations_FileModel;
use Craft\AcclaroTranslations\Job\Factory as JobFactory;

interface TranslationServiceInterface
{
    /**
     * Fetch order from service and update order model accordingly
     * @param  \Craft\AcclaroTranslations\Job\Factory $jobFactory
     * @param  \Craft\AcclaroTranslations_OrderModel  $order
     * @return void
     */
    public function updateOrder(JobFactory $jobFactory, AcclaroTranslations_OrderModel $order);

    /**
     * Fetch file from service and update file model accordingly
     * @param  \Craft\AcclaroTranslations\Job\Factory $jobFactory
     * @param  \Craft\AcclaroTranslations_OrderModel  $order
     * @param  \Craft\AcclaroTranslations_FileModel   $file
     * @return void
     */
    public function updateFile(JobFactory $jobFactory, AcclaroTranslations_OrderModel $order, AcclaroTranslations_FileModel $file);

    /**
     * Validate authentication credentials
     * @return boolean
     */
    public function authenticate();

    /**
     * Send order to service and update order model accordingly
     * @param  \Craft\AcclaroTranslations_OrderModel $order
     * @return void
     */
    public function sendOrder(AcclaroTranslations_OrderModel $order);
}
