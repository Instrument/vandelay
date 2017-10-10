<?php

namespace Craft\AcclaroTranslations\Repository;

use Craft\ElementType;
use Craft\EntryModel;
use CApplication;
use Exception;

class ElementRepository
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

    public function getElementById($element, $locale)
    {
        return $this->craft->getComponent('elements')->getElementById($element, null, $locale);
    }
}
