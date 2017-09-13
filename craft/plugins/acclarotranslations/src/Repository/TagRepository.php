<?php

namespace Craft\AcclaroTranslations\Repository;

use Craft\TagModel;
use Craft\ElementType;
use CApplication;
use Exception;

class TagRepository
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
        return $this->craft->getComponent('elements')->getCriteria(ElementType::Tag, $attributes)->first();
    }

    public function saveTag(TagModel $tag)
    {
        return $this->craft->getComponent('tags')->saveTag($tag);
    }
}
