<?php

namespace Craft\AcclaroTranslations;

class UrlHelper
{
    public function __call($name, $arguments)
    {
        return call_user_func_array("\\Craft\\UrlHelper::{$name}", $arguments);
    }
}
