<?php

namespace Craft;

use Craft\AcclaroTranslations\Job\SyncOrders;
use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Repository\FileRepository;
use Craft\AcclaroTranslations\TranslationService\Factory as TranslationServiceFactory;
use Craft\AcclaroTranslations\Job\Factory as JobFactory;
use Craft\AcclaroTranslations\Translator;
use CApplication;

class AcclaroTranslations_SyncOrdersTool extends BaseTool
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
     * @var \Craft\AcclaroTranslations\Translator
     */
    protected $translator;

    /**
     * @param \CApplication|null                          $craft
     * @param \Craft\AcclaroTranslations\Job\Factory|null $jobFactory
     * @param \Craft\AcclaroTranslations\Translator|null  $translator
     */
    public function __construct(
        CApplication $craft = null,
        JobFactory $jobFactory = null,
        Translator $translator = null
    ) {
        $this->craft = $craft ?: craft();

        $this->jobFactory = $jobFactory ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(JobFactory::class);

        $this->translator = $translator ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(Translator::class);
    }

    /**
     * @inheritDoc IComponentType::getName()
     *
     * @return string
     */
    public function getName()
    {
        return $this->translator->translate('Sync');
    }

    /**
     * @inheritDoc ITool::performAction()
     *
     * @param array $params
     *
     * @return array
     */
    public function performAction($params = array())
    {
        $this->jobFactory->dispatchJob(SyncOrders::class);

        return parent::performAction();
    }
}
