<?php

namespace Craft\AcclaroTranslations\Repository;

use Craft\UserModel;
use CApplication;
use Exception;

class UserRepository
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
     * @param  int $id
     * @return \Craft\UserModel
     */
    public function getUserById($id)
    {
        return $this->craft->getComponent('users')->getUserById($id);
    }
}
