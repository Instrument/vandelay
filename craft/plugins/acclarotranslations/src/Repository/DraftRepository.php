<?php

namespace Craft\AcclaroTranslations\Repository;

use Craft\ElementType;
use Craft\EntryDraftModel;
use CApplication;
use Exception;

class DraftRepository
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

    public function makeNewDraft()
    {
        return new EntryDraftModel();
    }

    public function getDraftById($draftId)
    {
        return $this->craft->getComponent('entryRevisions')->getDraftById($draftId);
    }

    public function saveDraft(EntryDraftModel $draft)
    {
        return $this->craft->getComponent('entryRevisions')->saveDraft($draft);
    }

    public function publishDraft(EntryDraftModel $draft)
    {
        return $this->craft->getComponent('entryRevisions')->publishDraft($draft);
    }
}
