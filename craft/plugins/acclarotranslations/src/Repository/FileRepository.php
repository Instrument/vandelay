<?php

namespace Craft\AcclaroTranslations\Repository;

use CApplication;
use Exception;
use Craft\AcclaroTranslations_FileModel;
use Craft\AcclaroTranslations_FileRecord;

class FileRepository
{
    /**
     * @var \CApplication
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
     * @param  int|string $fileId
     * @return \Craft\AcclaroTranslations_FileModel
     */
    public function getFileById($fileId)
    {
        $record = AcclaroTranslations_FileRecord::model()->findByPk($fileId);

        return $record ? AcclaroTranslations_FileModel::populateModel($record) : null;
    }

    /**
     * @param  int|string $draftId
     * @return \Craft\AcclaroTranslations_FileModel
     */
    public function getFileByDraftId($draftId, $elementId = null)
    {
        $attributes = array('draftId' => $draftId);

        if ($elementId) {
            $attributes['elementId'] = $elementId;
        }

        $record = AcclaroTranslations_FileRecord::model()->findByAttributes($attributes);

        return $record ? AcclaroTranslations_FileModel::populateModel($record) : null;
    }

    /**
     * @param  int|string $orderId
     * @return array \Craft\AcclaroTranslations_FileModel
     */
    public function getFilesByOrderId($orderId, $elementId = null)
    {
        $attributes = array('orderId' => $orderId);

        if ($elementId) {
            $attributes['elementId'] = $elementId;
        }

        $records = AcclaroTranslations_FileRecord::model()->findAllByAttributes($attributes);

        return $records ? AcclaroTranslations_FileModel::populateModels($records) : array();
    }

    /**
     * @return \Craft\AcclaroTranslations_FileModel
     */
    public function makeNewFile()
    {
        $file = new AcclaroTranslations_FileModel();

        return $file;
    }

    /**
     * @param  \Craft\AcclaroTranslations_FileModel $file
     * @throws \Exception
     * @return bool
     */
    public function saveFile(AcclaroTranslations_FileModel $file)
    {
        $isNew = !$file->id;

        if (!$isNew) {
            $record = AcclaroTranslations_FileRecord::model()->findById($file->id);

            if (!$record) {
                throw new Exception('No file exists with that ID.');
            }
        } else {
            $record = new AcclaroTranslations_FileRecord();
        }

        $record->setAttributes($file->getAttributes(), false);

        if (!$record->validate()) {
            $file->addErrors($record->getErrors());

            return false;
        }

        if ($file->hasErrors()) {
            return false;
        }

        $transaction = $this->craft->getComponent('db')->getCurrentTransaction() === null ? $this->craft->getComponent('db')->beginTransaction() : null;

        try {
            if ($record->save(false)) {
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

        return false;
    }
}
