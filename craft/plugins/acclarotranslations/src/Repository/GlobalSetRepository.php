<?php

namespace Craft\AcclaroTranslations\Repository;

use Craft\GlobalSetModel;
use CApplication;

class GlobalSetRepository
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

    public function getAllSets()
    {
        return $this->craft->getComponent('globals')->getAllSets();
    }

    public function getSetById($globalSetId, $locale = null)
    {
        return $this->craft->getComponent('globals')->getSetById($globalSetId, $locale);
    }

    public function getSetByHandle($globalSetHandle, $locale = null)
    {
        return $this->craft->getComponent('globals')->getSetByHandle($globalSetHandle, $locale);
    }

    public function saveSet(GlobalSetModel $globalSet)
    {
        return $this->craft->getComponent('globals')->saveSet($globalSet);
    }
}
