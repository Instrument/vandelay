<?php

namespace Craft\AcclaroTranslations\Repository;

use CApplication;
use Exception;
use Craft\AcclaroTranslations_TranslatorModel;
use Craft\AcclaroTranslations_TranslatorRecord;

class TranslatorRepository
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

    /**
     * @param  int|string $translatorId
     * @return \Craft\AcclaroTranslations_TranslatorModel
     */
    public function getTranslatorById($translatorId)
    {
        $record = AcclaroTranslations_TranslatorRecord::model()->findByPk($translatorId);

        return $record ? AcclaroTranslations_TranslatorModel::populateModel($record) : null;
    }

    /**
     * @return array \Craft\AcclaroTranslations_TranslatorModel
     */
    public function getTranslators()
    {
        $records = AcclaroTranslations_TranslatorRecord::model()->findAll();

        return AcclaroTranslations_TranslatorModel::populateModels($records);
    }

    /**
     * @return array \Craft\AcclaroTranslations_TranslatorModel
     */
    public function getActiveTranslators()
    {
        $records = AcclaroTranslations_TranslatorRecord::model()->findAllByAttributes(array(
            'status' => 'active',
        ));

        return AcclaroTranslations_TranslatorModel::populateModels($records);
    }

    /**
     * @param  string $service
     * @return string
     */
    public function getTranslatorServiceLabel($service)
    {
        $services = $this->getTranslationServices();

        return isset($services[$service]) ? $services[$service] : '';
    }

    /**
     * @return array id => label
     */
    public function getTranslatorOptions()
    {
        $options = array();

        foreach ($this->getActiveTranslators() as $translator) {
            $options[$translator->id] = $translator->label ? $translator->label : $this->getTranslatorServiceLabel($translator->service);
        }

        return $options;
    }

    /**
     * @return slug => label
     */
    public function getTranslationServices()
    {
        return array(
            'acclaro' => 'Acclaro',
        );
    }

    /**
     * @return \Craft\AcclaroTranslations_TranslatorModel
     */
    public function makeNewTranslator()
    {
        return new AcclaroTranslations_TranslatorModel();
    }

    /**
     * @param  \Craft\AcclaroTranslations_TranslatorModel $translator
     * @throws \Exception
     * @return bool
     */
    public function saveTranslator(AcclaroTranslations_TranslatorModel $translator)
    {
        $isNew = !$translator->id;

        if (!$isNew) {
            $record = AcclaroTranslations_TranslatorRecord::model()->findById($translator->id);

            if (!$record) {
                throw new Exception('No translator exists with that ID.');
            }
        } else {
            $record = new AcclaroTranslations_TranslatorRecord();
        }

        $record->setAttributes($translator->getAttributes(), false);

        if (!$record->validate()) {
            $translator->addErrors($record->getErrors());

            return false;
        }

        if ($translator->hasErrors()) {
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

    /**
     * @param  \Craft\AcclaroTranslations_TranslatorModel $translator
     * @return bool
     */
    public function deleteTranslator(AcclaroTranslations_TranslatorModel $translator)
    {
        $record = AcclaroTranslations_TranslatorRecord::model()->findById($translator->id);

        return $record->delete();
    }
}
