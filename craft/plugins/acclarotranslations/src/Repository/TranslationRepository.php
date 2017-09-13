<?php

namespace Craft\AcclaroTranslations\Repository;

use Craft\AcclaroTranslations_TranslationRecord;
use Craft\AcclaroTranslations_TranslationModel;
use CApplication;
use CMessageSource;
use Exception;
use ReflectionClass;

class TranslationRepository
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

    public function addTranslation($sourceLanguage, $targetLanguage, $source, $target)
    {
        $translations = $this->find(compact('sourceLanguage', 'targetLanguage', 'source', 'target'));

        if (!$translations) {
            $translation = $this->makeNewTranslation();
            $translation->sourceLanguage = $sourceLanguage;
            $translation->targetLanguage = $targetLanguage;
            $translation->source = $source;
            $translation->target = $target;
            $this->saveTranslation($translation);
        }
    }

    public function find($attributes)
    {
        $records = AcclaroTranslations_TranslationRecord::model()->findAllByAttributes($attributes);

        return $records ? AcclaroTranslations_TranslationModel::populateModels($records) : array();
    }

    public function makeNewTranslation()
    {
        return new AcclaroTranslations_TranslationModel();
    }

    public function saveTranslation(AcclaroTranslations_TranslationModel $translation)
    {
        $isNew = !$translation->id;

        if (!$isNew) {
            $record = AcclaroTranslations_TranslationRecord::model()->findById($translation->id);

            if (!$record) {
                throw new Exception('No translation exists with that ID.');
            }
        } else {
            $record = new AcclaroTranslations_TranslationRecord();
        }

        $record->setAttributes($translation->getAttributes(), false);

        if (!$record->validate()) {
            $translation->addErrors($record->getErrors());

            return false;
        }

        if ($translation->hasErrors()) {
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

    public function getTranslations()
    {
        $records = AcclaroTranslations_TranslationRecord::model()->findAll();

        return $records ? AcclaroTranslations_TranslationModel::populateModels($records) : array();
    }

    public function loadTranslations()
    {
        $translations = $this->getTranslations();

        $reflectionClass = new ReflectionClass(CMessageSource::class);

        $reflectionProperty = $reflectionClass->getProperty('_messages');

        $reflectionProperty->setAccessible(true);

        $messages = $reflectionProperty->getValue($this->craft->messages);

        foreach ($translations as $translation) {
            $key = sprintf('%s.%s', $translation->sourceLanguage, 'craft');

            $messages[$key][$translation->source] = $translation->target;

            $key = sprintf('%s.%s', $translation->targetLanguage, 'craft');

            $messages[$key][$translation->source] = $translation->target;
        }

        $reflectionProperty->setValue($this->craft->messages, $messages);

        $reflectionProperty->setAccessible(false);
    }
}
