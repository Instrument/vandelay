<?php

namespace Craft\AcclaroTranslations;

use Craft\Craft;

class Translator
{
    public function translate($message, $parameters = array())
    {
        return Craft::t($message, $parameters);
    }
}
