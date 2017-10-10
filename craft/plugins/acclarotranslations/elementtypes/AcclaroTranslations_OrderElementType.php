<?php

namespace Craft;

use Craft\AcclaroTranslations\TranslationService\Factory as TranslationServiceFactory;
use Craft\AcclaroTranslations\OrderSearchParams;
use Craft\AcclaroTranslations\Translator;
use CApplication;

class AcclaroTranslations_OrderElementType extends BaseElementType
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\TranslationService\Factory
     */
    protected $translationServiceFactory;

    /**
     * @var \Craft\AcclaroTranslations\OrderSearchParams
     */
    protected $orderSearchParams;

    /**
     * @var \Craft\AcclaroTranslations\Translator
     */
    protected $translator;

    /**
     * @param \CApplication|null                                         $craft
     * @param \Craft\AcclaroTranslations\TranslationService\Factory|null $translationServiceFactory
     * @param \Craft\AcclaroTranslations\OrderSearchParams|null          $orderSearchParams
     * @param \Craft\AcclaroTranslations\Translator|null                 $translator
     */
    public function __construct(
        CApplication $craft = null,
        TranslationServiceFactory $translationServiceFactory = null,
        OrderSearchParams $orderSearchParams = null,
        Translator $translator = null
    ) {
        $this->craft = $craft ?: craft();

        $this->translationServiceFactory = $translationServiceFactory ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(TranslationServiceFactory::class);

        $this->orderSearchParams = $orderSearchParams ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(OrderSearchParams::class);

        $this->translator = $translator ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(Translator::class);
    }

    public function getName()
    {
        return $this->translator->translate('Order');
    }

    /**
     * @inheritDoc IElementType::hasContent()
     *
     * @return bool
     */
    public function hasContent()
    {
        return true;
    }

    /**
     * @inheritDoc IElementType::hasTitles()
     *
     * @return bool
     */
    public function hasTitles()
    {
        return true;
    }

    /**
     * Returns this element type's sources.
     *
     * @param string|null $context
     * @return array|false
     */
    public function getSources($context = null)
    {
        $defaultCriteria = array(
            'status' => '*',
        );

        $queryCriteria = $this->orderSearchParams->getParams();

        $sources = array(
            '*' => array(
                'label' => $this->getName(),
                'criteria' => array_merge($defaultCriteria, $queryCriteria),
            ),
            'in-progress' => array(
                'label' => $this->getName(),
                'criteria' => array('status' => array('new', 'in progress', 'in preparation', 'getting quote', 'needs approval', 'complete')),
            ),
        );

        return $sources;
    }

    public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes)
    {
        $source = $this->getSource($sourceKey);

        $criteria->setAttributes($source['criteria']);

        $html = parent::getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);

        $isTableEmpty = !!preg_match('/<tbody>\s+<\/tbody>/', $html);

        if ($isTableEmpty) {
            return $this->translator->translate('No orders found.');
        }

        return $html;
    }

    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        $value = $element->$attribute;

        switch ($attribute) {
            case 'targetLanguages':
                $targetLanguages = $value ? json_decode($value, true) : array();

                $targetLanguages = array_map(function ($language) {
                    return craft()->i18n->getLocaleData()->getLanguage($language);
                }, $targetLanguages);

                return implode(', ', $targetLanguages);
                //return $targetLanguages ? '<ul class="bullets"><li>'.implode('</li><li>', $targetLanguages).'</li></ul>' : '';
            case 'title':
            case 'entriesCount':
            case 'wordCount':
                return $value;
            case 'serviceOrderId':
                if (!$value) {
                    return '';
                }

                $translator = $element->getTranslator();

                if (!$translator) {
                    return $value;
                }

                $translationService = $this->translationServiceFactory->makeTranslationService($translator->service, json_decode($translator->settings, true));

                return sprintf('<a href="%s" target="_blank">#%s</a>', $translationService->getOrderUrl($element), $value);
            case 'status':
                switch ($element->statusLabel) {
                    case 'Pending submission':
                        return '<span class="status"></span>'.$this->translator->translate($element->statusLabel);
                    case 'Submitted to translator':
                        return '<span class="status orange"></span>'.$this->translator->translate($element->statusLabel);
                    case 'Ready to publish':
                        return '<span class="status blue"></span>'.$this->translator->translate($element->statusLabel);
                    case 'Canceled':
                        return '<span class="status red"></span>'.$this->translator->translate($element->statusLabel);
                    case 'Published':
                        return '<span class="status green"></span>'.$this->translator->translate($element->statusLabel);
                }
            case 'requestedDueDate':
            case 'dateOrdered':
                return $value ? $value->format('n/j/y') : '';
            case 'deleteButton':
                if ($element->status !== 'new') {
                    return '';
                }

                return sprintf(
                    '<a class="icon delete acclarotranslations-delete-order" data-order-id="%s"></a>',
                    $element->id
                );
            case 'ownerId':
                return $element->getOwner() ? $element->getOwner()->username : '';
        }

        return parent::getTableAttributeHtml($element, $attribute);
    }

    /**
     * Returns the attributes that can be shown/sorted by in table views.
     *
     * @param string|null $source
     * @return array
     */
    public function defineTableAttributes($source = null)
    {
        switch ($source) {
            case 'in-progress':
                return array(
                    'title' => $this->translator->translate('Name'),
                    'serviceOrderId' => $this->translator->translate('ID'),
                    'ownerId' => $this->translator->translate('Owner'),
                    'entriesCount' => $this->translator->translate('Entries'),
                    'wordCount' => $this->translator->translate('Words'),
                    'targetLanguages' => $this->translator->translate('Languages'),
                    'status' => $this->translator->translate('Status'),
                    'requestedDueDate' => $this->translator->translate('Requested Due Date'),
                    'deleteButton' => '',
                );

            case '*':
                return array(
                    'title' => $this->translator->translate('Name'),
                    'serviceOrderId' => $this->translator->translate('ID'),
                    'ownerId' => $this->translator->translate('Owner'),
                    'entriesCount' => $this->translator->translate('Entries'),
                    'wordCount' => $this->translator->translate('Words'),
                    'targetLanguages' => $this->translator->translate('Languages'),
                    'status' => $this->translator->translate('Status'),
                    'dateOrdered' => $this->translator->translate('Created'),
                    'deleteButton' => '',
                );

            default:
                return array(
                    'title' => $this->translator->translate('Name'),
                    'serviceOrderId' => $this->translator->translate('ID'),
                    'ownerId' => $this->translator->translate('Owner'),
                    'entriesCount' => $this->translator->translate('Entries'),
                    'wordCount' => $this->translator->translate('Words'),
                    'targetLanguages' => $this->translator->translate('Languages'),
                    'status' => $this->translator->translate('Status'),
                    'requestedDueDate' => $this->translator->translate('Requested Due Date'),
                    'dateOrdered' => $this->translator->translate('Created'),
                    'deleteButton' => '',
                );
        }
    }

    /**
     * Defines any custom element criteria attributes for this element type.
     *
     * @return array
     */
    public function defineCriteriaAttributes()
    {
        return array(
            'sourceLanguage' => array(AttributeType::String),
            'targetLanguages' => array(AttributeType::String),
            'status' => array(AttributeType::Enum, 'values' => 'new,getting quote,needs approval,in preparation,in progress,complete,canceled,published', 'default' => '*'),
        );
    }

    /**
     * Modifies an element query targeting elements of this type.
     *
     * @param DbCommand $query
     * @param ElementCriteriaModel $criteria
     * @return mixed
     */
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query->addSelect('acclarotranslations_orders.*');

        $query->join('acclarotranslations_orders acclarotranslations_orders', 'acclarotranslations_orders.id = elements.id');

        if ($criteria->status) {
            if (is_array($criteria->status)) {
                $query->andWhere(array('in', 'acclarotranslations_orders.status', $criteria->status));
            } else if ($criteria->status !== '*') {
                $query->andWhere('acclarotranslations_orders.status = :status', array(':status' => $criteria->status));
            }
        }

        if ($criteria->getAttribute('translatorId')) {
            $query->andWhere('acclarotranslations_orders.translatorId = :translatorId', array(':translatorId' => $criteria->translatorId));
        }

        if ($criteria->getAttribute('sourceLanguage')) {
            $query->andWhere('acclarotranslations_orders.sourceLanguage = :sourceLanguage', array(':sourceLanguage' => $criteria->sourceLanguage));
        }

        if ($criteria->getAttribute('targetLanguage')) {
            $query->andWhere('acclarotranslations_orders.targetLanguage LIKE :targetLanguage', array(':targetLanguage' => '%"'.$criteria->targetLanguage.'"%'));
        }

        if ($criteria->getAttribute('startDate')) {
            $query->andWhere('acclarotranslations_orders.dateOrdered >= :dateOrdered', array(':dateOrdered' => DateTime::createFromFormat('n/j/Y', $criteria->startDate)->format('Y-m-d H:i:s')));
        }

        if ($criteria->getAttribute('endDate')) {
            $query->andWhere('acclarotranslations_orders.dateOrdered <= :dateOrdered', array(':dateOrdered' => DateTime::createFromFormat('n/j/Y', $criteria->endDate)->format('Y-m-d H:i:s')));
        }
    }

    /**
     * Populates an element model based on a query result.
     *
     * @param array $row
     * @return array
     */
    public function populateElementModel($row)
    {
        return AcclaroTranslations_OrderModel::populateModel($row);
    }
}
