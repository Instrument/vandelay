<?php

namespace Craft;

use Craft\AcclaroTranslations\Job\SyncOrders;
use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Repository\FileRepository;
use Craft\AcclaroTranslations\TranslationService\Factory as TranslationServiceFactory;
use Craft\AcclaroTranslations\Job\Factory as JobFactory;
use CApplication;

class AcclaroTranslationsCommand extends BaseCommand
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Job\Factory
     */
    protected $jobFactory;

    /**
     * @param string                                                     $commandName
     * @param \Craft\ConsoleCommandRunner                                $consoleCommandRunner
     * @param \CApplication|null                                         $craft
     * @param \Craft\AcclaroTranslations\Job\Factory|null                $jobFactory
     */
    public function __construct(
        $commandName,
        ConsoleCommandRunner $consoleCommandRunner,
        CApplication $craft = null,
        JobFactory $jobFactory = null
    ) {
        parent::__construct($commandName, $consoleCommandRunner);

        $this->craft = $craft ?: craft();

        $this->jobFactory = $jobFactory ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(JobFactory::class);
    }

    public function actionSyncOrders()
    {
        echo 'Syncing orders...'.PHP_EOL;

        $this->jobFactory->dispatchJob(SyncOrders::class);

        echo 'Finished.'.PHP_EOL;
    }
}