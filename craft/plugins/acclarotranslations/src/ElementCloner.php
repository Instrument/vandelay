<?php

namespace Craft\AcclaroTranslations;

use Craft\BaseElementModel;
use Craft\FieldModel;
use Craft\ElementCriteriaModel;
use Craft\MatrixBlockModel;
use Craft\SuperTable_BlockModel;
use Craft\Neo_BlockModel;
use CApplication;

class ElementCloner
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @param \CApplication $craft
     */
    public function __construct(
        CApplication $craft
    ) {
        $this->craft = $craft;
    }

    public function cloneElement(BaseElementModel $existingElement)
    {
        $elementType = $this->craft->getComponent('elements')->getElementType($existingElement->getElementType());

        $elementClass = get_class($existingElement);

        $newElement = new $elementClass();

        foreach ($existingElement->attributeNames() as $attribute) {
            if ($attribute === 'id' || $attribute === 'dateCreated' || $attribute === 'dateUpdated' || $attribute === 'uid') {
                continue;
            }

            $newElement->setAttribute($attribute, $existingElement->getAttribute($attribute));
        }

        if ($elementType->hasContent()) {
            if ($elementType->hasTitles()) {
                $newElement->getContent()->setAttribute('title', $existingElement->getContent()->getAttribute('title'));
            }

            foreach ($existingElement->getFieldLayout()->getFields() as $fieldLayoutField) {
                $this->cloneField($existingElement, $newElement, $fieldLayoutField->getField());
            }
        }

        return $newElement;
    }

    public function cloneField(BaseElementModel $existingElement, BaseElementModel $newElement, FieldModel $field)
    {
        $fieldHandle = $field->getAttribute('handle');

        $fieldValue = $existingElement->getContent()->getAttribute($fieldHandle);

        $newElement->getContent()->setAttribute($fieldHandle, $this->cloneValue($fieldValue));
    }

    public function cloneValue($fieldValue)
    {
        if (is_array($fieldValue)) {
            return array_map($fieldValue, array($this, 'cloneValue'));
        }

        if ($fieldValue instanceof ElementCriteriaModel) {
            return $this->cloneValue($fieldValue->find());
        }

        if ($fieldValue instanceof MatrixBlockModel || $fieldValue instanceof SuperTableModel || $fieldValue instanceof Neo_BlockModel) {
            return $this->cloneElement($fieldValue);
        }

        return $fieldValue;
    }
}
