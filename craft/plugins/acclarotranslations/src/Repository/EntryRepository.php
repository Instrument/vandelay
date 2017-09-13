<?php

namespace Craft\AcclaroTranslations\Repository;

use Craft\ElementType;
use Craft\EntryModel;
use CApplication;
use Exception;

class EntryRepository
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

    public function makeNewEntry()
    {
        return new EntryModel();
    }

    public function getEntryById($entryId, $locale)
    {
        return $this->craft->getComponent('entries')->getEntryById($entryId, $locale);
    }

    public function getEntriesById($entryIds, $locale)
    {
        $criteria = $this->craft->getComponent('elements')->getCriteria(ElementType::Entry);
        $criteria->id = $entryIds;
        $criteria->locale = $locale;
        $criteria->status = null;
        $criteria->localeEnabled = null;
        return $criteria->find();
    }

    public function saveEntry(EntryModel $entry)
    {
        return $this->craft->getComponent('entries')->saveEntry($entry);
    }
}
