<?php

namespace Craft\AcclaroTranslations;

class WordCounter
{
    public function getWordCount($str)
    {
        if ($str === '' || $str === null || $str === false) {
            return 0;
        }

        return count(preg_split('~[^\p{L}\p{N}\']+~u', $str));
    }
}
