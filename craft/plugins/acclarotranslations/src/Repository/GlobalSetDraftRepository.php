<?php

namespace Craft\AcclaroTranslations\Repository;

use Craft\ElementType;
use Craft\AcclaroTranslations_GlobalSetDraftModel as GlobalSetDraftModel;
use Craft\AcclaroTranslations_GlobalSetDraftRecord as GlobalSetDraftRecord;
use Craft\AcclaroTranslations\Translator;
use CApplication;
use Exception;

class GlobalSetDraftRepository
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Translator
     */
    protected $translator;

    /**
     * @param \CApplication $craft
     */
    public function __construct(
        CApplication $craft,
        Translator $translator
    ) {
        $this->craft = $craft;

        $this->translator = $translator;
    }

    public function makeNewDraft()
    {
        return new GlobalSetDraftModel();
    }

    public function getDraftById($draftId)
    {
        $record = GlobalSetDraftRecord::model()->findById($draftId);

        return $record ? GlobalSetDraftModel::populateModel($record) : null;
    }

    public function getDraftsByGlobalSetId($globalSetId, $locale = null)
    {
        $records = GlobalSetDraftRecord::model()->findAllByAttributes(array(
            'globalSetId' => $globalSetId,
            'locale' => $locale ?: $this->craft->language,
        ));

        return $records ? GlobalSetDraftModel::populateModels($records) : array();
    }

    public function getDraftRecord(GlobalSetDraftModel $draft)
    {
        if ($draft->draftId) {
            $record = GlobalSetDraftRecord::model()->findById($draft->draftId);

            if (!$record) {
                throw new Exception($this->translator->translate('No draft exists with the ID “{id}”.', array('id' => $draft->draftId)));
            }
        } else {
            $record = new GlobalSetDraftRecord();
            $record->globalSetId = $draft->id;
            $record->locale = $draft->locale;
            $record->name = $draft->name;
        }

        return $record;
    }

    public function getDraftData(GlobalSetDraftModel $draft)
    {
    }

    public function saveDraft(GlobalSetDraftModel $draft)
    {
        $record = $this->getDraftRecord($draft);

        if (!$draft->name && $draft->id) {
            $totalDrafts = $this->craft->getComponent('db')->createCommand()
                ->from('acclarotranslations_globalsetdrafts')
                ->where(
                    array('and', 'globalSetId = :globalSetId', 'locale = :locale'),
                    array(':globalSetId' => $draft->id, ':locale' => $draft->locale)
                )
                ->count('id');

            $draft->name = $this->translator->translate('Draft {num}', array('num' => $totalDrafts + 1));
        }

        $record->globalSetId = $draft->id;
        $record->locale = $draft->locale;
        $record->name = $draft->name;

        $data = array(
            'fields' => array(),
        );

        $content = $draft->getContentFromPost();

        foreach ($draft->getFieldLayout()->getFields() as $layoutField) {
            $field = $layoutField->getField();

            if (isset($content[$field->handle]) && $content[$field->handle] !== null) {
                $data['fields'][$field->id] = $content[$field->handle];
            }
        }

        $record->data = $data;

        if (!$record->save()) {
            return false;
        }

        $draft->draftId = $record->id;

        return true;
    }

    public function publishDraft(GlobalSetDraftModel $draft)
    {
        if (!$this->craft->getComponent('globals')->saveSet($draft)) {
            return false;
        }

        $this->deleteDraft($draft);

        return true;
    }

    public function deleteDraft(GlobalSetDraftModel $draft)
    {
        try {
            $record = $this->getDraftRecord($draft);
        } catch (Exception $e) {
            return false;
        }

        $transaction = $this->craft->getComponent('db')->getCurrentTransaction() === null ? $this->craft->getComponent('db')->beginTransaction() : null;

        try {
            $record->delete();
        } catch (Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }

            return false;
        }

        if ($transaction !== null) {
            $transaction->commit();
        }

        return true;
    }
}
