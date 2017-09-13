<?php

namespace Craft\AcclaroTranslations\Repository;

use CApplication;
use ReflectionClass;

class LanguageRepository
{
    /**
     * \CApplication
     */
    protected $craft;

    protected $supportedLanguages = array(
        'ar',
        'zh-CN',
        'zh-TW',
        'ht',
        'hr',
        'cs',
        'da',
        'nl',
        'en-ca',
        'en-gb',
        'en-us',
        'fi',
        'fr-ca',
        'fr-fr',
        'de-de',
        'hu',
        'it',
        'ja',
        'ko',
        'no',
        'pl',
        'pt-br',
        'pt-pt',
        'ru',
        'sk',
        'sl',
        'es-la',
        'es-es',
        'es',
        'es-us',
        'sv',
        'th',
        'tr',
        'vi',
    );

    protected $aliases = array(
        /* supported 3 letter codes */
        'ara' => 'ar',
        'zh-cn' => 'zh-CN',
        'zh-tw' => 'zh-TW',
        'zho-cn' => 'zh-CN',
        'zho-tw' => 'zh-TW',
        'hat' => 'ht',
        'hrv' => 'hr',
        'cze' => 'cs',
        'dan' => 'da',
        'dut' => 'nl',
        'eng-ca' => 'en-ca',
        'eng-gb' => 'en-gb',
        'eng-us' => 'en-us',
        'fin' => 'fi',
        'fre-ca' => 'fr-ca',
        'fre-fr' => 'fr-fr',
        'ger-de' => 'de-de',
        'hun' => 'hu',
        'ita' => 'it',
        'jpn' => 'ja',
        'kor' => 'ko',
        'nor' => 'no',
        'pol' => 'pl',
        'por-br' => 'pt-br',
        'por-pt' => 'pt-pt',
        'rus' => 'ru',
        'slo' => 'sk',
        'slv' => 'sl',
        'spa-lat' => 'es-la',
        'spa-es' => 'es-es',
        'spa' => 'es',
        'spa-us' => 'es-us',
        'swe' => 'sv',
        'tha' => 'th',
        'tur' => 'tr',
        'vie' => 'vi',
        /* aliases */
        'de' => 'de-de',
        'en' => 'en-us',
        'es' => 'es-es',
        'fr' => 'fr-fr',
        'pt' => 'pt-pt',
        'ja-jp' => 'jp',
        'zh-hans' => 'zh-CN',
        'zh-hant' => 'zh-TW',
    );

    /**
     * @param \CApplication $craft
     */
    public function __construct(CApplication $craft)
    {
        $this->craft = $craft;
    }

    public function isLanguageSupported($language)
    {
        return $this->normalizeLanguage($language) !== null;
    }

    public function normalizeLanguage($language)
    {
        $language = mb_strtolower($language);

        $language = str_replace('_', '-', $language);

        if (isset($this->aliases[$language])) {
            $language = $this->aliases[$language];
        }

        if (!in_array($language, $this->supportedLanguages)) {
            return null;
        }

        return $language;
    }

    public function getLanguages($namePrefix = '', $excludeLanguage = null)
    {
        $languages = array();

        $locales = $this->craft->getComponent('i18n')->getSiteLocales();

        foreach ($locales as $locale) {
            if ($excludeLanguage === $locale->getId()) {
                continue;
            }

            if ($this->normalizeLanguage($locale->getId()) === null) {
                continue;
            }

            $languages[$locale->getId()] = $namePrefix.$locale->getName();
        }

        asort($languages);

        return $languages;
    }
}
