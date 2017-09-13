<?php

namespace Craft\AcclaroTranslations\Repository;

use Craft\ElementType;
use Craft\CategoryModel;
use CApplication;
use Exception;

class CategoryRepository
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

    public function find($attributes = null)
    {
        return $this->craft->getComponent('elements')->getCriteria(ElementType::Category, $attributes)->first();
    }

    public function saveCategory(CategoryModel $category)
    {
        return $this->craft->getComponent('categories')->saveCategory($category);
    }
}
