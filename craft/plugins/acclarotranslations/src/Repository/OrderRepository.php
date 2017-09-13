<?php

namespace Craft\AcclaroTranslations\Repository;

use CApplication;
use DateTime;
use Exception;
use Craft\AcclaroTranslations_OrderModel;
use Craft\AcclaroTranslations_OrderRecord;

class OrderRepository
{
    /**
     * \CApplication
     */
    protected $craft;

    /**
     * @param \CApplication $craft
     */
    public function __construct(CApplication $craft)
    {
        $this->craft = $craft;
    }

    /**
     * @param  int|string $orderId
     * @return \Craft\AcclaroTranslations_OrderModel|null
     */
    public function getOrderById($orderId)
    {
        return $this->craft->getComponent('elements')->getElementById($orderId);
    }

    /**
     * @return \Craft\ElementCriteriaModel
     */
    public function getDraftOrders()
    {
        return $this->craft->getComponent('elements')->getCriteria('AcclaroTranslations_Order', array(
            'status' => 'new',
        ));
    }

    /**
     * @return int
     */
    public function getOrdersCount()
    {
        return $this->craft->getComponent('db')->createCommand()->select('COUNT(*)')
            ->from('acclarotranslations_orders')
            ->queryScalar();
    }

    /**
     * @return \Craft\ElementCriteriaModel
     */
    public function getInProgressOrders()
    {
        return $this->craft->getComponent('elements')->getCriteria('AcclaroTranslations_Order', array(
            'status' => array('getting quote', 'needs approval', 'in preparation', 'in progress'),
        ));
    }

    /**
     * @return \Craft\ElementCriteriaModel
     */
    public function getInProgressOrdersByTranslatorId($translatorId)
    {
        return $this->craft->getComponent('elements')->getCriteria('AcclaroTranslations_Order', array(
            'translatorId' => $translatorId,
            'status' => array('getting quote', 'needs approval', 'in preparation', 'in progress'),
        ));
    }

    /**
     * @return \Craft\ElementCriteriaModel
     */
    public function getCompleteOrders()
    {
        return $this->craft->getComponent('elements')->getCriteria('AcclaroTranslations_Order', array(
            'status' => array('complete'),
        ));
    }

    public function getOrderStatuses()
    {
        return array(
            'new' => 'new',
            'getting quote' => 'getting quote',
            'needs approval' => 'needs approval',
            'in preparation' => 'in preparation',
            'in progress' => 'in progress',
            'complete' => 'complete',
            'canceled' => 'canceled',
            'published' => 'published',
        );
    }

    /**
     * @return return \Crfat\AcclaroTranslations_OrderModel
     */
    public function makeNewOrder($sourceLanguage = null)
    {
        $order = new AcclaroTranslations_OrderModel();

        $order->status = 'new';
        $order->sourceLanguage = $sourceLanguage ?: $this->craft->language;

        return $order;
    }

    /**
     * @param  \Craft\AcclaroTranslations_OrderModel $order
     * @throws \Exception
     * @return bool
     */
    public function saveOrder(AcclaroTranslations_OrderModel $order)
    {
        $isNew = !$order->id;

        if (!$isNew) {
            $record = AcclaroTranslations_OrderRecord::model()->findById($order->id);

            if (!$record) {
                throw new Exception('No order exists with that ID.');
            }
        } else {
            $record = new AcclaroTranslations_OrderRecord();
        }

        $record->setAttributes($order->getAttributes(), false);

        if (!$record->validate()) {
            $order->addErrors($record->getErrors());

            return false;
        }

        if ($order->hasErrors()) {
            return false;
        }

        $transaction = $this->craft->getComponent('db')->getCurrentTransaction() === null ? $this->craft->getComponent('db')->beginTransaction() : null;

        try {
            if ($this->craft->getComponent('elements')->saveElement($order)) {
                if ($isNew) {
                    $record->id = $order->id;
                }

                $record->save(false);

                if ($transaction !== null) {
                    $transaction->commit();
                }

                return true;
            }
        } catch (Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }

            throw $e;
        }
    }

    public function deleteOrder(AcclaroTranslations_OrderModel $order)
    {
        return $this->craft->getComponent('elements')->deleteElementById($order->id);
    }
}
