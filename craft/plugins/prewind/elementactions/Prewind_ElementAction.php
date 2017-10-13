<?php
/**
 * Prewind plugin for Craft CMS
 *
 * @author    Mort
 * @copyright Copyright (c) 2017 Mortscode
 * @package   Prewind
 * @since     1.0.0
 */

namespace Craft;

class Prewind_PrewindElementAction extends BaseElementAction
{
    public function getName ()
    {
        return Craft::t('Prewind');
    }

    public function isDestructive ()
    {
        return false;
    }

    public function performAction (ElementCriteriaModel $criteria)
    {
        craft()->tasks->createTask('Prewind_set', 'Transforming assets', [ 'assetIds' => $criteria->ids() ]);
        $this->setMessage(Craft::t('Transforming assets'));

        return true;
    }
}