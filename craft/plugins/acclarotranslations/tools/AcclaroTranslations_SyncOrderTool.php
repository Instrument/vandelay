<?php

namespace Craft;

use Craft\AcclaroTranslations\Job\SyncOrder;
use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Job\Factory as JobFactory;
use Craft\AcclaroTranslations\Translator;
use CApplication;

class AcclaroTranslations_SyncOrderTool extends BaseTool
{
    /**
     * @var int
     */
    protected $orderId;

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
     * @var \Craft\AcclaroTranslations\Translator
     */
    protected $translator;

    /**
     * @param int                                                        $orderId
     * @param \CApplication|null                                         $craft
     * @param \Craft\AcclaroTranslations\Repository\OrderRepository|null $orderRepository
     * @param \Craft\AcclaroTranslations\Job\Factory|null                $jobFactory
     * @param \Craft\AcclaroTranslations\Translator|null                 $translator
     */
    public function __construct(
        $orderId = 0,
        CApplication $craft = null,
        OrderRepository $orderRepository = null,
        JobFactory $jobFactory = null,
        Translator $translator = null
    ) {
        $this->orderId = $orderId;

        $this->craft = $craft ?: craft();

        $this->orderRepository = $orderRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(OrderRepository::class);

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
     * @inheritDoc ITool::getOptionsHtml()
     *
     * @return string
     */
    public function getOptionsHtml()
    {
        return sprintf('<input type="hidden" name="orderId" value="%s">', htmlentities($this->orderId, ENT_QUOTES, 'utf-8'));
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
        $order = $this->orderRepository->getOrderById($params['orderId']);

        $this->jobFactory->dispatchJob(SyncOrder::class, $order);

        return parent::performAction();
    }
}
